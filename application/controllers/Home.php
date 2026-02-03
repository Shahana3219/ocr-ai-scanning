<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Home extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();

        $this->load->model('ocr_model');
        $this->load->library(['form_validation', 'session', 'upload']);
        $this->load->helper(['url', 'form']);
    }

    public function invoice_form()
    {
        $this->load->view('invoice_form');
    }

    // ==========================================
    // 1) UPLOAD + NORMALIZE + INSERT DB
    //    Supports: PDF, JPG/JPEG/PNG, DOC/DOCX
    // ==========================================
    public function upload_invoice_ocr()
    {
        header('Content-Type: application/json; charset=utf-8');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['status'=>'error','message'=>'Invalid request method']);
            return;
        }

        if (empty($_FILES['invoice_file']['name'])) {
            echo json_encode(['status'=>'error','message'=>'No file selected']);
            return;
        }

        $allowed_mime = [
            'image/png',
            'image/jpeg',
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
        ];

        $tmp  = $_FILES['invoice_file']['tmp_name'];
        $mime = function_exists('mime_content_type') ? mime_content_type($tmp) : ($_FILES['invoice_file']['type'] ?? '');
        $size = (int)($_FILES['invoice_file']['size'] ?? 0);

        $extFromName = strtolower(pathinfo($_FILES['invoice_file']['name'], PATHINFO_EXTENSION));
        $allowedExt  = ['png','jpg','jpeg','pdf','doc','docx'];

        if (!in_array($mime, $allowed_mime) && !in_array($extFromName, $allowedExt)) {
            echo json_encode(['status'=>'error','message'=>'Invalid file type']);
            return;
        }

        if ($size > 5 * 1024 * 1024) {
            echo json_encode(['status'=>'error','message'=>'File must be below 5MB']);
            return;
        }

        // folders
        $folderKey = uniqid('inv_');
        $basePath       = FCPATH . 'assets/uploads/invoices/' . $folderKey . '/';
        $originalPath   = $basePath . 'original/';
        $normalizedPath = $basePath . 'normalized/';

        if (!is_dir($originalPath) && !mkdir($originalPath, 0777, true)) {
            echo json_encode(['status'=>'error','message'=>'Cannot create original folder']);
            return;
        }
        if (!is_dir($normalizedPath) && !mkdir($normalizedPath, 0777, true)) {
            echo json_encode(['status'=>'error','message'=>'Cannot create normalized folder']);
            return;
        }

        // Upload
        $config = [
            'upload_path'   => $originalPath,
            'allowed_types' => 'jpg|jpeg|png|pdf|doc|docx',
            'max_size'      => 5120,
            'encrypt_name'  => true
        ];
        $this->upload->initialize($config);

        if (!$this->upload->do_upload('invoice_file')) {
            echo json_encode(['status'=>'error','message'=>$this->upload->display_errors('', '')]);
            return;
        }

        $up = $this->upload->data();
        $fullPath = $up['full_path'];
        $fileName = $up['file_name'];
        $ext      = strtolower($up['file_ext']); // ".pdf", ".png", ".docx" ...

        try {
            $normalizedFiles = [];

            // ----- Normalize all inputs -> PNG pages -----
            if ($ext === '.pdf') {

                $normalizedFiles = $this->normalize_pdf_to_images($fullPath, $normalizedPath);

            } elseif (in_array($ext, ['.jpg', '.jpeg', '.png'])) {

                // Always store as PNG for consistent OCR
                $target = $normalizedPath . 'page_1.png';
                if (!copy($fullPath, $target)) {
                    throw new Exception('Failed to copy image to normalized folder');
                }
                $normalizedFiles[] = $target;

            } elseif (in_array($ext, ['.doc', '.docx'])) {

                // DOC/DOCX -> PDF -> images
                $pdfPath = $normalizedPath . 'doc_converted.pdf';
                $this->convert_doc_to_pdf($fullPath, $pdfPath);
                $normalizedFiles = $this->normalize_pdf_to_images($pdfPath, $normalizedPath);

            } else {
                echo json_encode(['status'=>'error','message'=>'Unsupported file type']);
                return;
            }

            // ----- Insert document -----
            $docData = [
                'original_file_path' => str_replace(FCPATH, '', $fullPath),
                'original_file_name' => $fileName,
                'file_ext'           => ltrim($ext, '.'),
                'mime_type'          => $mime,
                'file_size'          => $size,
                'page_count'         => count($normalizedFiles),
                'status'             => 'converted',
                'created_at'         => date('Y-m-d H:i:s'),
                'updated_at'         => date('Y-m-d H:i:s'),
            ];

            $document_id = $this->ocr_model->create_document($docData);
            if (!$document_id) {
                echo json_encode([
                    'status'   => 'error',
                    'message'  => 'Failed to insert into invoice_documents',
                    'db_error' => $this->db->error()
                ]);
                return;
            }

            // Insert pages
            $rows = [];
            $now = date('Y-m-d H:i:s');
            $pageNo = 1;

            foreach ($normalizedFiles as $imgPath) {
                $rows[] = [
                    'document_id'    => $document_id,
                    'page_no'        => $pageNo,
                    'image_path'     => str_replace(FCPATH, '', $imgPath),
                    'ocr_text'       => null,
                    'ocr_confidence' => null,
                    'created_at'     => $now,
                    'updated_at'     => $now,
                ];
                $pageNo++;
            }

            $ok = $this->ocr_model->create_pages_batch($rows);
            if (!$ok) {
                echo json_encode([
                    'status'   => 'error',
                    'message'  => 'Failed to insert pages into invoice_document_pages',
                    'db_error' => $this->db->error()
                ]);
                return;
            }

            // preview urls
            $previewUrls = [];
            foreach ($normalizedFiles as $imgPath) {
                $previewUrls[] = base_url(str_replace(FCPATH, '', $imgPath));
            }

            echo json_encode([
                'status'           => 'success',
                'message'          => 'Uploaded & normalized successfully',
                'document_id'      => $document_id,
                'uploaded_file'    => $fileName,
                'normalized_pages' => count($normalizedFiles),
                'preview_urls'     => $previewUrls
            ]);
            return;

        } catch (Exception $e) {
            echo json_encode(['status'=>'error','message'=>'Normalization failed: '.$e->getMessage()]);
            return;
        }
    }

    // ==========================================
    // 2) RUN OCR + SAVE + PARSE + RETURN JSON
public function run_ocr()
{
    header('Content-Type: application/json; charset=utf-8');

    ini_set('display_errors', 1);
    error_reporting(E_ALL);

    $DEBUG = true;

    try {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['status'=>'error','message'=>'Invalid request method']);
            return;
        }

        $document_id = (int)$this->input->post('document_id');
        if (!$document_id) {
            echo json_encode(['status'=>'error','message'=>'document_id is required']);
            return;
        }

        $pages = $this->ocr_model->get_pages($document_id);
        if (empty($pages)) {
            echo json_encode(['status'=>'error','message'=>'No pages found for this document']);
            return;
        }

        $tmpDir = FCPATH . 'assets/uploads/tmp/';
        if (!is_dir($tmpDir)) mkdir($tmpDir, 0777, true);

        $this->ocr_model->update_document_status($document_id, 'ocr_running');

        $done = 0;
        $errors = [];

        $documentConfidence = 0;
        $pageConfidenceMode = "max(eng, ara+eng)";
        
        // Variables for text accumulation
        $allEngText = '';
        $allAraText = '';
        $allTableText = '';

        foreach ($pages as $p) {
            $pageNo = (int)$p['page_no'];

            $imgRel = ltrim($p['image_path'], '/\\');
            $imgAbs = FCPATH . $imgRel;

            if (!file_exists($imgAbs)) {
                $errors[] = "Missing image page {$pageNo}: $imgAbs";
                continue;
            }

            // ---- Work copy (never change original) ----
            $tmpBase = $tmpDir . 'ocr_' . $document_id . '_' . $pageNo;
            $workImg = $tmpDir . 'work_' . $document_id . '_' . $pageNo . '.png';

            if (!@copy($imgAbs, $workImg)) {
                $errors[] = "Failed to copy workImg page {$pageNo}";
                continue;
            }

            if ($DEBUG) {
                $errors[] = "DEBUG_WORKIMG_PAGE{$pageNo}=" . (file_exists($workImg) ? filesize($workImg) : 0);
            }

            // preprocess work copy
            try {
                $this->preprocess_image_for_ocr($workImg);
            } catch (Exception $ex) {
                $errors[] = "Preprocess failed page {$pageNo}: ".$ex->getMessage();
            }

            // ---- Main OCR (ENG + ARA+ENG) ----
            $eng = $this->tess_run_with_conf($workImg, $tmpBase . '_eng', 'eng', 6, 1);
            $ara = $this->tess_run_with_conf($workImg, $tmpBase . '_ara', 'ara+eng', 6, 1);

            $pageBest = max((float)$eng['conf'], (float)$ara['conf']);
            if ($pageBest > $documentConfidence) $documentConfidence = $pageBest;

            // Accumulate text for parsing
            $allEngText .= $eng['text'] . "\n\n";
            $allAraText .= $ara['text'] . "\n\n";

            if ($DEBUG) {
                $errors[] = "DEBUG_PAGE{$pageNo} ENG: conf={$eng['conf']} words={$eng['words']}";
                $errors[] = "DEBUG_PAGE{$pageNo} ARA: conf={$ara['conf']} words={$ara['words']}";
                $errors[] = "DEBUG_ENG_SAMPLE_PAGE{$pageNo}=" . substr($eng['text'], 0, 100);
            }

            // ---- SMART TABLE DETECTION ----
            // First, let's find where the item table might be
            $tableFound = false;
            $tableText = '';
            
            // Strategy 1: Look for item patterns in the entire image first
            $fullTableTest = $this->tess_run_with_conf($workImg, $tmpBase . '_table_full', 'eng', 4, 1);
            
            // Check if this contains item-like content
            if (preg_match('/\b(P[IL]O?\d{4,8})\b/i', $fullTableTest['text']) || 
                preg_match('/Item\s*Code/i', $fullTableTest['text'])) {
                $tableText = $fullTableTest['text'];
                $tableFound = true;
                $errors[] = "DEBUG_TABLE_STRATEGY_PAGE{$pageNo}=FULL_PAGE_SUCCESS";
            }
            
            // Strategy 2: If not found, try specific vertical bands
            if (!$tableFound) {
                $tableBands = [
                    ['name'=>'b15_45', 's'=>15, 'e'=>45, 'score'=>0, 'text'=>''],
                    ['name'=>'b20_50', 's'=>20, 'e'=>50, 'score'=>0, 'text'=>''],
                    ['name'=>'b25_55', 's'=>25, 'e'=>55, 'score'=>0, 'text'=>''],
                    ['name'=>'b30_60', 's'=>30, 'e'=>60, 'score'=>0, 'text'=>''],
                ];
                
                foreach ($tableBands as &$band) {
                    $tblImg = $tmpDir . "table_{$document_id}_{$pageNo}_{$band['name']}.png";
                    $this->crop_image_band_percent($workImg, $tblImg, $band['s'], $band['e']);
                    
                    $result = $this->tess_run_with_conf($tblImg, $tmpBase . "_tbl_{$band['name']}", 'eng', 4, 1);
                    $band['text'] = $result['text'];
                    $band['score'] = $this->score_table_content($result['text']);
                    
                    if ($DEBUG) {
                        $errors[] = "DEBUG_BAND_{$band['name']}_SCORE={$band['score']}";
                    }
                }
                
                // Find best band
                usort($tableBands, function($a, $b) {
                    return $b['score'] <=> $a['score'];
                });
                
                $bestBand = $tableBands[0];
                if ($bestBand['score'] > 50) { // Threshold for table detection
                    $tableText = $bestBand['text'];
                    $tableFound = true;
                    $errors[] = "DEBUG_TABLE_STRATEGY_PAGE{$pageNo}=BAND_{$bestBand['name']}_SCORE_{$bestBand['score']}";
                }
            }
            
            // Strategy 3: Try bottom section for totals if item table not found
            if (!$tableFound) {
                $bottomImg = $tmpDir . "bottom_{$document_id}_{$pageNo}.png";
                $this->crop_image_band_percent($workImg, $bottomImg, 60, 90);
                $bottomResult = $this->tess_run_with_conf($bottomImg, $tmpBase . '_bottom', 'eng', 4, 1);
                $tableText = $bottomResult['text'];
                $errors[] = "DEBUG_TABLE_STRATEGY_PAGE{$pageNo}=BOTTOM_FALLBACK";
            }

            $allTableText .= $tableText . "\n\n";

            if ($DEBUG && $tableText) {
                $errors[] = "DEBUG_TABLE_TEXT_PAGE{$pageNo}=" . substr(preg_replace('/\s+/', ' ', $tableText), 0, 150);
            }

            // ---- Save OCR ----
            $ocrText =
                "=== ENG ===\n{$eng['text']}\n\n" .
                "=== ARA+ENG ===\n{$ara['text']}\n\n" .
                "=== TABLE_ENG ===\n{$tableText}\n";

            $ok = $this->ocr_model->update_page_ocr($document_id, $pageNo, $ocrText, $pageBest);
            if (!$ok) {
                $errors[] = "DB update failed page {$pageNo}: ".json_encode($this->db->error());
                continue;
            }

            $done++;
        }

        // ==========================
        // PARSE EXTRACTED TEXT
        // ==========================
        
        // Clean and parse
        $parsed = $this->parse_invoice_comprehensive($allEngText, $allAraText, $allTableText);
        
        // Extract items with multiple strategies
        $itemsParsed = [];
        
        // Strategy 1: From table text
        if (!empty($allTableText)) {
            $itemsParsed = $this->extract_items_from_text($allTableText);
        }
        
        // Strategy 2: From main English text
        if (empty($itemsParsed) && !empty($allEngText)) {
            $itemsParsed = $this->extract_items_from_text($allEngText);
        }
        
        // Strategy 3: Direct regex search for item patterns
        if (empty($itemsParsed)) {
            $itemsParsed = $this->find_items_by_pattern($allEngText . "\n" . $allTableText);
        }
        
        $parsed['items'] = $itemsParsed;

        if ($DEBUG) {
            $errors[] = 'DEBUG_PARSED_ITEMS_COUNT=' . count($parsed['items']);
            $errors[] = 'DEBUG_INVOICE_NO=' . ($parsed['invoice_no'] ?? '');
            $errors[] = 'DEBUG_CUSTOMER=' . ($parsed['customer_name'] ?? '');
            $errors[] = 'DEBUG_ALL_ENG_LENGTH=' . strlen($allEngText);
            $errors[] = 'DEBUG_ALL_TABLE_LENGTH=' . strlen($allTableText);
            
            // Show first few lines of ENG text for debugging
            $engLines = explode("\n", $allEngText);
            for ($i = 0; $i < min(5, count($engLines)); $i++) {
                $errors[] = "DEBUG_ENG_LINE_{$i}=" . trim($engLines[$i]);
            }
            
            if (!empty($parsed['items'])) {
                $errors[] = 'DEBUG_FIRST_ITEM=' . json_encode($parsed['items'][0]);
            }
        }

        $this->ocr_model->update_document_status($document_id, ($done > 0 ? 'ocr_done' : 'ocr_failed'));

        echo json_encode([
            'status' => ($done > 0 ? 'success' : 'error'),
            'message' => "OCR finished. Saved: {$done}/" . count($pages),
            'document_id' => $document_id,
            'document_confidence' => $documentConfidence,
            'page_confidence_mode' => $pageConfidenceMode,
            'processed_pages' => $done,
            'total_pages' => count($pages),
            'errors' => $errors,
            'prefill' => [
                'invoice_date' => $parsed['invoice_date'] ? $this->date_to_ymd($parsed['invoice_date']) : date('Y-m-d'),
                'invoice_time' => date('H:i'),
                'due_date'     => $parsed['due_date'] ? $this->date_to_ymd($parsed['due_date']) : date('Y-m-d'),
                'order_no'     => $parsed['invoice_no'] ?? '',
                'reference_no' => '',
                'subject'      => 'Customer: ' . ($parsed['customer_name'] ?? '')
            ],
            'parsed' => $parsed
        ]);
        return;

    } catch (Throwable $e) {
        echo json_encode([
            'status'=>'error',
            'message'=>'OCR crashed',
            'error'=>$e->getMessage(),
            'file'=>$e->getFile(),
            'line'=>$e->getLine()
        ]);
        return;
    }
}

// Add this helper method for table scoring
private function score_table_content($text)
{
    $score = 0;
    
    // High value for item codes
    if (preg_match_all('/\b(P[IL]O?\d{4,8})\b/i', $text, $matches)) {
        $score += count($matches[0]) * 100;
    }
    
    // Item code patterns
    if (preg_match_all('/\b[A-Z]{2,6}\d{4,8}\b/', $text, $matches)) {
        $score += count($matches[0]) * 80;
    }
    
    // Quantity indicators
    if (preg_match('/\b(Qty|Quantity)\b/i', $text)) $score += 50;
    
    // Unit indicators
    if (preg_match_all('/\b(BOX|PCS|PC|EA|UNIT|CTN|PACK)\b/i', $text, $matches)) {
        $score += count($matches[0]) * 30;
    }
    
    // Price columns
    preg_match_all('/\d[\d,]*\.\d{2}/', $text, $matches);
    $score += count($matches[0]) * 20;
    
    // Negative scoring for totals/footer
    if (preg_match('/\b(Total|Subtotal|Balance|Amount Due|Grand Total)\b/i', $text)) {
        $score -= 100;
    }
    
    if (preg_match('/\b(VAT|Tax|Discount)\b/i', $text)) {
        $score -= 50;
    }
    
    return $score;
}

// Comprehensive parsing method
private function parse_invoice_comprehensive($engText, $araText, $tableText)
{
    $out = [
        'invoice_no' => '',
        'invoice_date' => '',
        'due_date' => '',
        'customer_name' => '',
        'vat_no' => '',
        'address' => '',
        'sales_person' => '',
        'items' => [],
        'totals' => [
            'total_without_vat' => '',
            'total_vat' => '',
            'total_with_vat' => '',
            'discount' => ''
        ],
        'bank' => [
            'account_name' => '',
            'bank_name' => '',
            'account_number' => '',
            'iban' => ''
        ]
    ];
    
    // Combine all text for parsing
    $allText = $engText . "\n" . $araText . "\n" . $tableText;
    $allText = preg_replace("/[ \t]+/", " ", $allText);
    $allText = preg_replace("/\r\n|\r/", "\n", $allText);
    
    // 1. Find Customer Name - multiple strategies
    $customerName = $this->extract_customer_name($allText);
    if ($customerName) {
        $out['customer_name'] = $customerName;
    }
    
    // 2. Find Invoice Number
    if (preg_match('/Invoice\s*No[\.:]?\s*([A-Z0-9][A-Z0-9\/\-\.\s]{3,20})/i', $allText, $m)) {
        $out['invoice_no'] = trim($m[1]);
    } elseif (preg_match('/INV[-_]?([A-Z0-9]+)/i', $allText, $m)) {
        $out['invoice_no'] = 'INV-' . trim($m[1]);
    }
    
    // 3. Find Dates
    if (preg_match('/Invoice\s*Date[\.:]?\s*([0-9]{1,2}[\/\-\.][0-9]{1,2}[\/\-\.][0-9]{4})/i', $allText, $m)) {
        $out['invoice_date'] = trim($m[1]);
    }
    
    if (preg_match('/Due\s*Date[\.:]?\s*([0-9]{1,2}[\/\-\.][0-9]{1,2}[\/\-\.][0-9]{4})/i', $allText, $m)) {
        $out['due_date'] = trim($m[1]);
    }
    
    // 4. Find VAT Number
    if (preg_match('/VAT\s*NO[\.:]?\s*([0-9]{6,})/i', $allText, $m)) {
        $out['vat_no'] = trim($m[1]);
    }
    
    // 5. Find Totals
    if (preg_match('/Total\s*(?:Without|Exc\.?)\s*VAT[^\d]*([\d,]+\.\d{2})/i', $allText, $m)) {
        $out['totals']['total_without_vat'] = $m[1];
    }
    
    if (preg_match('/VAT\s*Total[^\d]*([\d,]+\.\d{2})/i', $allText, $m)) {
        $out['totals']['total_vat'] = $m[1];
    }
    
    return $out;
}

// Improved customer name extraction
private function extract_customer_name($text)
{
    // Multiple patterns to try
    $patterns = [
        '/Customer\s*Name[\.:]?\s*([^\n]{3,50})/i',
        '/Bill\s*To[\.:]?\s*([^\n]{3,50})/i',
        '/Client[\.:]?\s*([^\n]{3,50})/i',
        '/Customer[\.:]?\s*([^\n]{3,50})/i',
        '/To[\.:]?\s*([^\n]{3,50})/i',
    ];
    
    foreach ($patterns as $pattern) {
        if (preg_match($pattern, $text, $matches)) {
            $name = trim($matches[1]);
            
            // Clean up the name
            $name = preg_replace('/\d{1,2}[\/\-\.]\d{1,2}[\/\-\.]\d{2,4}/', '', $name); // Remove dates
            $name = preg_replace('/Invoice.*/i', '', $name); // Remove invoice references
            $name = preg_replace('/[^a-zA-Z0-9\s\-&]/', '', $name); // Remove special chars
            $name = preg_replace('/\s+/', ' ', $name); // Normalize spaces
            
            $name = trim($name);
            
            // Validate it looks like a name (not empty, not just symbols)
            if (strlen($name) >= 3 && preg_match('/[a-zA-Z]/', $name)) {
                return $name;
            }
        }
    }
    
    return '';
}

// Simple item extraction
private function extract_items_from_text($text)
{
    $items = [];
    $lines = explode("\n", $text);
    
    foreach ($lines as $line) {
        $line = trim($line);
        if (empty($line)) continue;
        
        // Look for item patterns
        if (preg_match('/\b(P[IL]O?\d{4,8})\b/i', $line, $codeMatch)) {
            $itemCode = strtoupper(preg_replace('/\s+/', '', $codeMatch[1]));
            
            // Extract amounts
            preg_match_all('/\d[\d,]*\.\d{2}/', $line, $amounts);
            $amounts = $amounts[0] ?? [];
            
            if (count($amounts) >= 2) {
                // Extract quantity
                $qty = '';
                if (preg_match('/\b(\d{1,6})\b/', $line, $qtyMatch)) {
                    $qty = $qtyMatch[1];
                }
                
                // Extract unit
                $unit = '';
                if (preg_match('/\b(BOX|PCS|PC|EA|UNIT|CTN|PACK)\b/i', $line, $unitMatch)) {
                    $unit = strtoupper($unitMatch[1]);
                }
                
                // Build description
                $desc = preg_replace('/\b' . preg_quote($codeMatch[1], '/') . '\b/i', '', $line);
                foreach ($amounts as $amt) {
                    $desc = str_replace($amt, '', $desc);
                }
                $desc = preg_replace('/\b' . $qty . '\b/', '', $desc);
                $desc = preg_replace('/\b' . $unit . '\b/i', '', $desc);
                $desc = preg_replace('/[^a-zA-Z0-9\s\-]/', '', $desc);
                $desc = trim(preg_replace('/\s+/', ' ', $desc));
                
                $items[] = [
                    'item_code'     => $itemCode,
                    'description'   => $desc ?: 'ITEM',
                    'qty'           => $qty,
                    'unit'          => $unit,
                    'unit_price'    => $amounts[0] ?? '',
                    'without_vat'   => (count($amounts) >= 2) ? $amounts[1] : '',
                    'vat_amount'    => (count($amounts) >= 3) ? $amounts[2] : '',
                    'total_inc_vat' => end($amounts) ?: '',
                    'raw_line'      => $line
                ];
            }
        }
    }
    
    return $items;
}

// Direct pattern search for items
private function find_items_by_pattern($text)
{
    $items = [];
    
    // Look for specific item patterns in the text
    if (preg_match_all('/(P[IL]O?\d{4,8})\s+([^\n]{10,80}?\d[\d,]*\.\d{2}\s+\d[\d,]*\.\d{2}\s+\d[\d,]*\.\d{2}\s+\d[\d,]*\.\d{2})/i', $text, $matches, PREG_SET_ORDER)) {
        foreach ($matches as $match) {
            $itemCode = strtoupper(preg_replace('/\s+/', '', $match[1]));
            $rest = $match[2];
            
            // Extract amounts
            preg_match_all('/\d[\d,]*\.\d{2}/', $rest, $amounts);
            $amounts = $amounts[0] ?? [];
            
            if (count($amounts) >= 4) {
                $items[] = [
                    'item_code'     => $itemCode,
                    'description'   => 'ITEM', // Simplified for now
                    'qty'           => '',
                    'unit'          => '',
                    'unit_price'    => $amounts[0],
                    'without_vat'   => $amounts[1],
                    'vat_amount'    => $amounts[2],
                    'total_inc_vat' => $amounts[3],
                ];
            }
        }
    }
    
    return $items;
}
    // ==========================================
    // Helpers
    // ==========================================

    private function date_to_ymd($d)
    {
        $ts = strtotime($d);
        return $ts ? date('Y-m-d', $ts) : '';
    }

    private function split_ocr_blocks($text)
    {
        $eng = '';
        $ara = '';

        if (preg_match('/===\s*ENG\s*===\s*(.*?)\s*===\s*ARA\+ENG\s*===\s*(.*)/s', $text, $m)) {
            $eng = trim($m[1]);
            $ara = trim($m[2]);
        } else {
            $eng = $text;
        }
        return [$eng, $ara];
    }

    // -------- DOC/DOCX -> PDF (LibreOffice) --------
    private function convert_doc_to_pdf($inputDoc, $outputPdf)
    {
        $soffice = '"C:\Program Files\LibreOffice\program\soffice.exe"';
        $bin = str_replace('"','',$soffice);

        if (!file_exists($bin)) {
            throw new Exception('LibreOffice (soffice.exe) not found: ' . $bin);
        }

        $outDir = dirname($outputPdf);

        $cmd = $soffice
            . ' --headless --nologo --nofirststartwizard'
            . ' --convert-to pdf'
            . ' --outdir ' . escapeshellarg($outDir)
            . ' ' . escapeshellarg($inputDoc)
            . ' 2>&1';

        $out = [];
        $code = 0;
        exec($cmd, $out, $code);

        if ($code !== 0) {
            throw new Exception('DOC->PDF conversion failed: ' . implode("\n", $out));
        }

        $expected = $outDir . DIRECTORY_SEPARATOR . pathinfo($inputDoc, PATHINFO_FILENAME) . '.pdf';
        if (!file_exists($expected)) {
            throw new Exception('Converted PDF not created. Expected: ' . $expected);
        }

        if (!@rename($expected, $outputPdf)) {
            if (!@copy($expected, $outputPdf)) {
                throw new Exception('Cannot move converted PDF to: ' . $outputPdf);
            }
        }
    }

    // -------- Image preprocess for better OCR --------
    private function preprocess_image_for_ocr($imagePath)
    {
        if (!file_exists($imagePath)) return;
        if (!extension_loaded('imagick')) return;

        $img = new Imagick();
        $img->readImage($imagePath);

        $img->setImageBackgroundColor('white');
        $img = $img->mergeImageLayers(Imagick::LAYERMETHOD_FLATTEN);

        $w = $img->getImageWidth();
        $h = $img->getImageHeight();
        if ($w < 2200) {
            $newH = (int) round($h * (2200 / $w));
            $img->resizeImage(2200, $newH, Imagick::FILTER_LANCZOS, 1);
        }

        $img->setImageColorspace(Imagick::COLORSPACE_GRAY);

        $img->normalizeImage();
        $img->contrastImage(1);

        // mild sharpen
        $img->unsharpMaskImage(0, 1, 0.7, 0.02);

        $img->setImageResolution(300, 300);
        $img->resampleImage(300, 300, Imagick::FILTER_LANCZOS, 1);

        $img->setImageFormat('png');
        $img->writeImage($imagePath);

        $img->clear();
        $img->destroy();
    }

    // -------- Tesseract run -> text + TSV confidence --------
    private function tess_run_with_conf($imagePath, $outBase, $lang = 'eng', $psm = 6, $oem = 1)
    {
        $tesseract = '"C:\Program Files\Tesseract-OCR\tesseract.exe"';
        putenv('TESSDATA_PREFIX=C:\Program Files\Tesseract-OCR\tessdata');

        $cmd = $tesseract . ' '
            . escapeshellarg($imagePath) . ' '
            . escapeshellarg($outBase)
            . ' --dpi 400'
            . ' -l ' . escapeshellarg($lang)
            . ' --oem ' . (int)$oem
            . ' --psm ' . (int)$psm
            . ' -c preserve_interword_spaces=1'
            . ' tsv 2>&1';

        $out = [];
        $code = 0;
        exec($cmd, $out, $code);

        $tsvFile = $outBase . '.tsv';
        $txtFile = $outBase . '.txt';

        $avg = 0; $cnt = 0;
        $wordsArr = [];
        $headerOk = false;
        $sample1 = '';

        if (file_exists($tsvFile)) {
            $lines = file($tsvFile, FILE_IGNORE_NEW_LINES);

            if (!empty($lines) && stripos($lines[0], "conf") !== false) {
                $headerOk = true;
            }
            $sample1 = $lines[1] ?? '';

            foreach ($lines as $i => $ln) {
                if ($i === 0) continue; // header
                $cols = explode("\t", $ln);

                // conf=10, text=11
                if (!isset($cols[10], $cols[11])) continue;

                $conf = $cols[10];
                $text = trim($cols[11]);

                if ($text !== '' && is_numeric($conf) && (int)$conf >= 0) {
                    $avg += (float)$conf;
                    $cnt++;
                    $wordsArr[] = $text;
                }
            }
        }

        $avgConf = ($cnt > 0) ? round($avg / $cnt, 1) : 0;

        $textOut = '';
        if (file_exists($txtFile) && filesize($txtFile) > 0) {
            $textOut = trim(file_get_contents($txtFile));
        } else {
            $textOut = trim(implode(' ', $wordsArr));
        }

        return [
            'text' => $textOut,
            'conf' => $avgConf,
            'words' => $cnt,
            'tsv' => file_exists($tsvFile),
            'tsv_size' => file_exists($tsvFile) ? filesize($tsvFile) : 0,
            'tsv_header_ok' => $headerOk,
            'code' => $code,
            'raw' => $out,
            'tsv_sample_1' => $sample1
        ];
    }

    // ==========================================
    // ✅ GLOBAL ITEMS EXTRACTOR (TSV-based)
    // ==========================================
    private function items_from_tsv($tsvPath)
    {
        if (!file_exists($tsvPath)) return [];

        $lines = file($tsvPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if (!$lines || count($lines) < 2) return [];

        $rows = [];
        $current = null;

        // tuneable threshold
        $rowThreshold = 12;

        foreach ($lines as $i => $ln) {
            if ($i === 0) continue; // header

            $cols = explode("\t", $ln);
            if (count($cols) < 12) continue;

            // columns: left=6, top=7, conf=10, text=11
            $left = (int)$cols[6];
            $top  = (int)$cols[7];
            $conf = (float)$cols[10];
            $txt  = trim($cols[11]);

            if ($txt === '' || $conf < 0) continue;

            if ($current === null) {
                $current = ['top'=>$top, 'words'=>[]];
            }

            if (abs($top - $current['top']) <= $rowThreshold) {
                $current['words'][] = ['left'=>$left, 'text'=>$txt];
            } else {
                $rows[] = $current;
                $current = ['top'=>$top, 'words'=>[['left'=>$left, 'text'=>$txt]]];
            }
        }
        if ($current) $rows[] = $current;

        // to line strings
        $rowLines = [];
        foreach ($rows as $r) {
            usort($r['words'], fn($a,$b)=>$a['left'] <=> $b['left']);
            $lineText = implode(' ', array_map(fn($w)=>$w['text'], $r['words']));
            $rowLines[] = trim(preg_replace("/\s+/", " ", $lineText));
        }

        // parse lines into item rows
        $items = [];
        foreach ($rowLines as $line) {
            $item = $this->parse_item_line_generic($line);
            if ($item) $items[] = $item;
        }

        return $items;
    }

    private function parse_item_line_generic($line)
    {
        $ln = trim($line);
        if ($ln === '') return null;

        // must contain at least 2 money-like values
        preg_match_all('/\d[\d,]*\.\d{2}/', $ln, $m);
        $money = $m[0] ?? [];
        if (count($money) < 2) return null;

        // qty + unit patterns (expand anytime)
        $unitPattern = '(PCS|PC|EA|UNIT|BOX|PACK|CTN|SET|KG|G|L|ML|M|M2|M3|HR|HOUR|DAY)';

        $qty = null;
        $unit = '';

        if (preg_match('/\b(\d{1,6})\b\s*(' . $unitPattern . ')\b/i', $ln, $q)) {
            $qty  = (int)$q[1];
            $unit = strtoupper($q[2]);
        } elseif (preg_match('/\b(' . $unitPattern . ')\b\s*(\d{1,6})\b/i', $ln, $q2)) {
            $qty  = (int)$q2[2];
            $unit = strtoupper($q2[1]);
        } else {
            // some invoices may miss unit; still try qty alone
            if (preg_match('/\b(\d{1,6})\b/', $ln, $q3)) {
                $qty = (int)$q3[1];
                $unit = '';
            } else {
                return null;
            }
        }

        // code: flexible
        $code = '';
        if (preg_match('/\b([A-Z]{1,6}[-_]?\d{2,10})\b/', $ln, $c)) {
            $code = $c[1];
        } elseif (preg_match('/\b\d{6,12}\b/', $ln, $c2)) {
            $code = $c2[0];
        }

        // build description by removing code, qty/unit, money values
        $desc = $ln;

        if ($code) {
            $desc = preg_replace('/\b'.preg_quote($code,'/').'\b/', ' ', $desc, 1);
        }

        if ($qty !== null) {
            if ($unit !== '') {
                $desc = preg_replace('/\b'.preg_quote((string)$qty,'/').'\b\s*'.preg_quote($unit,'/').'\b/i', ' ', $desc, 1);
            } else {
                $desc = preg_replace('/\b'.preg_quote((string)$qty,'/').'\b/', ' ', $desc, 1);
            }
        }

        foreach ($money as $mv) {
            $desc = preg_replace('/'.preg_quote($mv,'/').'/', ' ', $desc, 1);
        }

        // remove common serial markers like "1]" or "#"
        $desc = preg_replace('/^\s*\d+\]?\s*/', '', $desc);

        $desc = trim(preg_replace("/\s+/", " ", $desc));

        // map values from end (more stable)
        $total_inc  = $money[count($money)-1] ?? '';
        $vat_amt    = $money[count($money)-2] ?? '';
        $without    = $money[count($money)-3] ?? '';
        $unit_price = $money[0] ?? '';

        return [
            'item_code'     => $code,
            'description'   => $desc,
            'qty'           => $qty,
            'unit'          => $unit,
            'unit_price'    => $unit_price,
            'without_vat'   => $without,
            'vat_amount'    => $vat_amt,
            'total_inc_vat' => $total_inc,
            'raw_line'      => $ln
        ];
    }

    // ==========================================
    // PDF normalize (Poppler pdftoppm first, else Ghostscript)
    // ==========================================
    private function normalize_pdf_to_images($pdfPath, $outputDir)
    {
        if ($this->command_exists('pdftoppm')) {
            return $this->pdf_to_png_pdftoppm($pdfPath, $outputDir);
        }
        return $this->pdf_to_png_ghostscript($pdfPath, $outputDir);
    }

    private function command_exists($cmd)
    {
        $out = [];
        $code = 0;

        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            exec("where $cmd 2>NUL", $out, $code);
        } else {
            exec("command -v " . escapeshellarg($cmd) . " 2>/dev/null", $out, $code);
        }

        return ($code === 0 && !empty($out));
    }

    private function pdf_to_png_pdftoppm($pdfPath, $outputDir)
    {
        if (!is_dir($outputDir) && !mkdir($outputDir, 0777, true)) {
            throw new Exception('Cannot create output directory: ' . $outputDir);
        }

        $prefix = rtrim($outputDir, '/\\') . DIRECTORY_SEPARATOR . 'page';

        $cmd = 'pdftoppm -png -r 400 '
            . escapeshellarg($pdfPath) . ' '
            . escapeshellarg($prefix);

        $out = [];
        $code = 0;
        exec($cmd . ' 2>&1', $out, $code);

        if ($code !== 0) {
            throw new Exception('pdftoppm failed: ' . implode("\n", $out));
        }

        $files = glob($outputDir . DIRECTORY_SEPARATOR . 'page-*.png');
        if (!$files) throw new Exception('pdftoppm produced no PNG files');

        natsort($files);

        $renamed = [];
        $i = 1;
        foreach ($files as $f) {
            $new = $outputDir . DIRECTORY_SEPARATOR . "page_{$i}.png";
            @rename($f, $new);
            $renamed[] = $new;
            $i++;
        }
        return $renamed;
    }

    private function pdf_to_png_ghostscript($pdfPath, $outputDir)
    {
        if (!is_dir($outputDir) && !mkdir($outputDir, 0777, true)) {
            throw new Exception('Cannot create output directory: ' . $outputDir);
        }

        $gs = '"C:\Program Files\gs\gs10.06.0\bin\gswin64c.exe"';
        $bin = str_replace('"','',$gs);
        if (!file_exists($bin)) {
            throw new Exception('Ghostscript not found at: ' . $bin);
        }

        $cmd = $gs
            . ' -dSAFER -dBATCH -dNOPAUSE'
            . ' -sDEVICE=png16m -r400'
            . ' -dTextAlphaBits=4 -dGraphicsAlphaBits=4'
            . ' -sOutputFile=' . escapeshellarg($outputDir . '/page_%03d.png')
            . ' ' . escapeshellarg($pdfPath)
            . ' 2>&1';

        $out = [];
        $code = 0;
        exec($cmd, $out, $code);

        if ($code !== 0) throw new Exception('Ghostscript failed: ' . implode("\n", $out));

        $files = glob($outputDir . '/page_*.png');
        if (!$files) throw new Exception('Ghostscript produced no images');

        natsort($files);

        $renamed = [];
        $i = 1;
        foreach ($files as $f) {
            $new = rtrim($outputDir, '/\\') . DIRECTORY_SEPARATOR . "page_{$i}.png";
            @rename($f, $new);
            $renamed[] = $new;
            $i++;
        }
        return $renamed;
    }

    // ==========================================
    // OCR text -> structured parse (header/totals/bank)
    // Items will be overwritten by TSV extractor
    // ==========================================
   private function parse_invoice_from_text($text)
{
    $t = preg_replace("/[ \t]+/", " ", $text);
    $t = preg_replace("/\r\n|\r/", "\n", $t);

    $out = [
        'invoice_no' => '',
        'invoice_date' => '',
        'due_date' => '',
        'customer_name' => '',
        'vat_no' => '',
        'address' => '',
        'sales_person' => '',
        'items' => [],
        'totals' => [
            'total_without_vat' => '',
            'total_vat' => '',
            'total_with_vat' => '',
            'discount' => ''
        ],
        'bank' => [
            'account_name' => '',
            'bank_name' => '',
            'account_number' => '',
            'iban' => ''
        ]
    ];

    // IMPROVED CUSTOMER NAME EXTRACTION
    // Multiple patterns to catch different invoice formats
    if (preg_match('/Customer\s*Name\s*[:\-]?\s*([^\n]{3,})/i', $t, $m)) {
        $out['customer_name'] = trim($m[1]);
    } elseif (preg_match('/Bill\s*To\s*[:\-]?\s*([^\n]{3,})/i', $t, $m)) {
        $out['customer_name'] = trim($m[1]);
    } elseif (preg_match('/(?:Client|Customer)\s*:\s*([^\n]{3,})/i', $t, $m)) {
        $out['customer_name'] = trim($m[1]);
    }

    // IMPROVED INVOICE NUMBER EXTRACTION
    if (preg_match('/Invoice\s*No\s*[:\-]?\s*([A-Z0-9][A-Z0-9\/\-\.\s]{3,})/i', $t, $m)) {
        $out['invoice_no'] = trim($m[1]);
    } elseif (preg_match('/INV[-_]?([A-Z0-9]+)/i', $t, $m)) {
        $out['invoice_no'] = 'INV-' . trim($m[1]);
    } elseif (preg_match('/\b(INV\d{4,})\b/i', $t, $m)) {
        $out['invoice_no'] = trim($m[1]);
    }

    // Date formats - support more variations
    if (preg_match('/Invoice\s*Date\s*[:\-]?\s*([0-9]{1,2}[\/\-\.][0-9]{1,2}[\/\-\.][0-9]{4})/i', $t, $m)) {
        $out['invoice_date'] = trim($m[1]);
    } elseif (preg_match('/Invoice\s*Date\s*[:\-]?\s*([0-9]{1,2}\-[A-Za-z]{3}\-[0-9]{4})/i', $t, $m)) {
        $out['invoice_date'] = trim($m[1]);
    }
    
    if (preg_match('/Due\s*Date\s*[:\-]?\s*([0-9]{1,2}[\/\-\.][0-9]{1,2}[\/\-\.][0-9]{4})/i', $t, $m)) {
        $out['due_date'] = trim($m[1]);
    } elseif (preg_match('/Due\s*Date\s*[:\-]?\s*([0-9]{1,2}\-[A-Za-z]{3}\-[0-9]{4})/i', $t, $m)) {
        $out['due_date'] = trim($m[1]);
    }

    // VAT Number - multiple patterns
    if (preg_match_all('/VAT\s*NO\s*[:\-]?\s*([0-9]{6,})/i', $t, $m) && !empty($m[1])) {
        $out['vat_no'] = trim(end($m[1]));
    } elseif (preg_match('/VAT\s*ID\s*[:\-]?\s*([0-9]{6,})/i', $t, $m)) {
        $out['vat_no'] = trim($m[1]);
    }

    // Totals - improved patterns
    if (preg_match('/Total\s*(?:Without|Exc\.?)\s*VAT[^\d]*([\d,]+\.\d{2})/i', $t, $m)) {
        $out['totals']['total_without_vat'] = $m[1];
    }
    if (preg_match('/Discount[^\d]*([\d,]+\.\d{2})/i', $t, $m)) {
        $out['totals']['discount'] = $m[1];
    }
    if (preg_match('/VAT\s*Total[^\d]*([\d,]+\.\d{2})/i', $t, $m)) {
        $out['totals']['total_vat'] = $m[1];
    }
    if (preg_match('/Total\s*(?:With|Inc\.?)\s*VAT[^\d]*([\d,]+\.\d{2})/i', $t, $m)) {
        $out['totals']['total_with_vat'] = $m[1];
    }

    // Bank details
    if (preg_match('/Account\s*Name\s*[:\-]?\s*([^\n]+)/i', $t, $m)) {
        $out['bank']['account_name'] = trim($m[1]);
    }
    if (preg_match('/Bank\s*Name\s*[:\-]?\s*([^\n]+)/i', $t, $m)) {
        $out['bank']['bank_name'] = trim($m[1]);
    }
    if (preg_match('/Account\s*Number\s*[:\-]?\s*([A-Za-z0-9]+)/i', $t, $m)) {
        $out['bank']['account_number'] = trim($m[1]);
    }
    if (preg_match('/IBAN\s*(?:No|Number)?\s*[:\-]?\s*([A-Za-z0-9]{6,})/i', $t, $m)) {
        $out['bank']['iban'] = trim($m[1]);
    }

    return $out;
}
 private function extract_items_from_table_text($text)
{
    $text = preg_replace("/\r\n|\r/", "\n", $text);
    $text = preg_replace("/[ \t]+/", " ", $text);

    $items = [];
    $lines = preg_split("/\n+/", $text);

    foreach ($lines as $ln) {
        $ln = trim($ln);
        if ($ln === '') continue;

        // normalize OCR separators
        $pipe = preg_replace('/\s*\|\s*/', '|', $ln);

        // A) Pipe style
        if (preg_match(
            '/\b([A-Za-z]{1,6}\s*\d{3,8})\b\s+(.+?)\s+(\d{1,6})\s*\|\s*([A-Za-z]{1,6})\s*\|\s*([\d,]+\.\d{2})\s*\|\s*([\d,]+\.\d{2})\s+([\d,]+\.\d{2})\s+([\d,]+\.\d{2})/i',
            $pipe, $m
        )) {
            $items[] = [
                'item_code'     => strtoupper(preg_replace('/\s+/', '', $m[1])),
                'description'   => trim($m[2]),
                'qty'           => $m[3],
                'unit'          => strtoupper($m[4]),
                'unit_price'    => $m[5],
                'without_vat'   => $m[6],
                'vat_amount'    => $m[7],
                'total_inc_vat' => $m[8],
            ];
            continue;
        }

        // B) No pipe style (OCR squashed)
        if (preg_match(
            '/\b([A-Za-z]{1,6}\s*\d{3,8})\b\s+(.+?)\s+(\d{1,6})\s+(BOX|Box|PCS|PC|EA|UNIT|CTN|PACK)\s+([\d,]+\.\d{2})\s+([\d,]+\.\d{2})\s+([\d,]+\.\d{2})\s+([\d,]+\.\d{2})/i',
            $ln, $m
        )) {
            $items[] = [
                'item_code'     => strtoupper(preg_replace('/\s+/', '', $m[1])),
                'description'   => trim($m[2]),
                'qty'           => $m[3],
                'unit'          => strtoupper($m[4]),
                'unit_price'    => $m[5],
                'without_vat'   => $m[6],
                'vat_amount'    => $m[7],
                'total_inc_vat' => $m[8],
            ];
            continue;
        }
    }

    return $items;
}



    private function extract_items_from_ocr_text($ocrText)
{
    // Work on ENG block mostly (less Arabic noise in numbers)
    list($eng, $ara) = $this->split_ocr_blocks($ocrText);
    $t = $eng ?: $ocrText;

    // Normalize
    $t = str_replace(["\r\n", "\r"], "\n", $t);
    $t = preg_replace("/[ \t]+/", " ", $t);

    // 1) Cut table area: between header and totals
    $startPos = -1;
    $endPos   = -1;

    if (preg_match('/(Item\s*Code|Item\s*&\s*Description|Description\s*Qty|Qty\s*Unit)/i', $t, $m, PREG_OFFSET_CAPTURE)) {
        $startPos = $m[0][1];
    }

    if (preg_match('/(Total\s*Without\s*VAT|VAT\s*Total|Total\s*With\s*VAT|Discount)/i', $t, $m, PREG_OFFSET_CAPTURE)) {
        $endPos = $m[0][1];
    }

    if ($startPos >= 0 && $endPos > $startPos) {
        $table = substr($t, $startPos, $endPos - $startPos);
    } else {
        // fallback: use whole text if we can't locate boundaries
        $table = $t;
    }

    $lines = array_values(array_filter(array_map('trim', explode("\n", $table))));
    if (empty($lines)) return [];

    // 2) Merge wrapped lines: if a line has too few numbers, it may be continuation of previous description
    $merged = [];
    foreach ($lines as $ln) {
        preg_match_all('/[\d,]+\.\d{2}/', $ln, $nums);
        $numCount = count($nums[0]);

        if (!empty($merged)) {
            // If this line does not look like a row but previous exists, append
            if ($numCount < 2 && !preg_match('/\b(BOX|PCS|PC|EA|UNIT|CTN|PACK)\b/i', $ln)) {
                $merged[count($merged)-1] .= ' ' . $ln;
                continue;
            }
        }
        $merged[] = $ln;
    }

    // 3) Parse each probable row with flexible heuristics
    $items = [];

    foreach ($merged as $ln) {

        // Skip header lines
        if (preg_match('/Item\s*Code|Item\s*&\s*Description|Qty|Unit\s*Price|Total\s*inc/i', $ln)) {
            continue;
        }

        // Need at least 2 decimal numbers to be a row
        preg_match_all('/[\d,]+\.\d{2}/', $ln, $nums);
        $nums = $nums[0] ?? [];
        if (count($nums) < 2) continue;

        // Item code: very flexible (global)
        // Examples: PI01168, Plo1168, A12-99, SKU-0099, 12345, etc.
        $itemCode = '';
        if (preg_match('/\b([A-Z]{1,6}\s*[-_\/]?\s*\d{2,8}[A-Z0-9\-\/_]*)\b/i', $ln, $m)) {
            $itemCode = strtoupper(preg_replace('/\s+/', '', $m[1]));
        } elseif (preg_match('/\b(\d{3,10})\b/', $ln, $m2)) {
            $itemCode = $m2[1];
        }

        // Qty + Unit
        $qty = '';
        $unit = '';
        if (preg_match('/\b(\d{1,6})\s*(\|\s*)?(BOX|PCS|PC|EA|UNIT|CTN|PACK)\b/i', $ln, $m)) {
            $qty  = $m[1];
            $unit = strtoupper($m[3]);
        } else {
            // fallback: take first integer as qty
            if (preg_match('/\b(\d{1,6})\b/', $ln, $m3)) $qty = $m3[1];
        }

        // Description: remove code + qty/unit + numbers, keep the text chunk
        $desc = $ln;
        if ($itemCode) $desc = preg_replace('/\b' . preg_quote($itemCode, '/') . '\b/i', '', $desc);
        if ($qty) $desc = preg_replace('/\b' . preg_quote($qty, '/') . '\b/', '', $desc);
        if ($unit) $desc = preg_replace('/\b' . preg_quote($unit, '/') . '\b/i', '', $desc);
        $desc = preg_replace('/[\d,]+\.\d{2}/', '', $desc);
        $desc = trim(preg_replace('/\s{2,}/', ' ', $desc));
        if ($desc === '') $desc = 'ITEM';

        // Amount mapping:
        // Many invoices: unit_price, amount_without_vat, vat_amount, total_inc_vat
        $unit_price    = $nums[0] ?? '';
        $without_vat   = $nums[1] ?? '';
        $vat_amount    = (count($nums) >= 3) ? $nums[count($nums)-2] : '';
        $total_inc_vat = end($nums) ?: '';

        $items[] = [
            'item_code'     => $itemCode,
            'description'   => $desc,
            'qty'           => $qty,
            'unit'          => $unit,
            'unit_price'    => $unit_price,
            'without_vat'   => $without_vat,
            'vat_amount'    => $vat_amount,
            'total_inc_vat' => $total_inc_vat,
            'raw_line'      => $ln
        ];
    }

    return $items;
}
private function extract_items_from_tsv($tsvFile)
{
    if (!file_exists($tsvFile)) return [];

    $lines = file($tsvFile, FILE_IGNORE_NEW_LINES);
    if (!$lines || count($lines) < 2) return [];

    $rowsByLine = [];

    foreach ($lines as $i => $ln) {
        if ($i === 0) continue; // header
        $c = explode("\t", $ln);

        // Expect at least 12 cols (Tesseract TSV)
        if (count($c) < 12) continue;

        $level    = (int)$c[0];
        $lineNum  = (int)$c[4];
        $left     = (int)$c[6];
        $conf     = (int)$c[10];
        $word     = trim($c[11]);

        // use only WORD level (level=5) and valid conf
        if ($level !== 5) continue;
        if ($conf < 0) continue;
        if ($word === '') continue;

        // group by line number
        $rowsByLine[$lineNum][] = ['x'=>$left, 'w'=>$word, 'conf'=>$conf];
    }

    if (empty($rowsByLine)) return [];

    // Sort words per line by x position, join to line text
    ksort($rowsByLine);
    $lineTexts = [];

    foreach ($rowsByLine as $lnum => $words) {
        usort($words, fn($a,$b) => $a['x'] <=> $b['x']);
        $text = implode(' ', array_map(fn($x) => $x['w'], $words));
        $lineTexts[] = $text;
    }

    // Now reuse Stage-A parser logic on reconstructed lines:
    $fakeOcr = implode("\n", $lineTexts);

    return $this->extract_items_from_ocr_text($fakeOcr);
}
private function find_table_y_from_tsv($tsvFile)
{
    if (!file_exists($tsvFile)) return 0;

    $lines = file($tsvFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if (!$lines || count($lines) < 2) return 0;

    // TSV columns: left=6, top=7, width=8, height=9, conf=10, text=11
    foreach ($lines as $i => $ln) {
        if ($i === 0) continue; // header
        $cols = explode("\t", $ln);
        if (!isset($cols[7], $cols[11])) continue;

        $text = strtolower(trim($cols[11]));
        if ($text === 'item' || $text === 'items' || $text === 'code' || $text === 'description') {
            $top = (int)$cols[7];
            // take a little above
            return max(0, $top - 20);
        }
        // also handle "itemcode" merged
        if (strpos($text, 'item') !== false && strpos($text, 'code') !== false) {
            $top = (int)$cols[7];
            return max(0, $top - 20);
        }
    }
    return 0;
}

private function crop_image_from_y($srcImage, $destImage, $startY)
{
    if (!extension_loaded('imagick')) {
        // no imagick => just use original
        @copy($srcImage, $destImage);
        return;
    }

    $img = new Imagick();
    $img->readImage($srcImage);

    $w = $img->getImageWidth();
    $h = $img->getImageHeight();

    if ($startY <= 0 || $startY >= $h) {
        $img->setImageFormat('png');
        $img->writeImage($destImage);
        $img->clear(); $img->destroy();
        return;
    }

    $cropH = $h - $startY;
    $img->cropImage($w, $cropH, 0, $startY);
    $img->setImagePage(0, 0, 0, 0);

    // help table text
    $img->setImageColorspace(Imagick::COLORSPACE_GRAY);
    $img->normalizeImage();
    $img->contrastImage(1);
    $img->unsharpMaskImage(0, 1, 0.8, 0.02);

    $img->setImageFormat('png');
    $img->writeImage($destImage);

    $img->clear(); $img->destroy();
}

private function extract_items_loose($text)
{
    // works with or without pipes; expects 3+ amounts
    $items = [];

    $t = preg_replace("/[ \t]+/", " ", $text);
    $t = preg_replace("/\r\n|\r/", "\n", $t);

    $lines = preg_split("/\n/", $t);

    foreach ($lines as $ln) {
        $ln = trim($ln);
        if ($ln === '') continue;

        // find item-like lines: CODE + QTY + UNIT + prices
        // Example: Plo1168 MUG SET 100 | Box | 70.00 | 5,999.70 899.96 6,899.66
        // Example without pipes: PI01168 MUG SET 100 Box 70.00 5,999.70 899.96 6,899.66
        if (!preg_match('/\b(P[IL]O?\s*\d{3,6})\b/i', $ln, $mcode)) {
            continue;
        }

        preg_match_all('/([\d,]+\.\d{2})/', $ln, $nums);
        $nums = $nums[1] ?? [];

        if (count($nums) < 3) continue; // not enough money fields

        // qty + unit
        $qty = '';
        $unit = '';
        if (preg_match('/\b(\d{1,6})\b\s*(\|\s*)?(BOX|Box|PCS|PC|EA|UNIT|CTN|PACK)\b/i', $ln, $mq)) {
            $qty  = $mq[1];
            $unit = strtoupper($mq[3]);
        }

        // description: text after code up to qty (best effort)
        $codeRaw = preg_replace('/\s+/', '', strtoupper($mcode[1]));
        $code = str_replace(['PLO','PL'], 'PI', $codeRaw); // normalize PLO/PL -> PI (optional)

        $desc = '';
        if ($qty !== '' && preg_match('/\b'.preg_quote($mcode[1],'/').'\b\s*(.+?)\s+\b'.$qty.'\b/i', $ln, $md)) {
            $desc = trim($md[1]);
        } else {
            // fallback: remove code and numbers
            $desc = trim(preg_replace('/\b'.preg_quote($mcode[1],'/').'\b/i','',$ln));
            $desc = trim(preg_replace('/[\d,]+\.\d{2}/','',$desc));
            $desc = trim(preg_replace('/\b(BOX|PCS|PC|EA|UNIT|CTN|PACK)\b/i','',$desc));
        }

        // map amounts: assume [unit_price, without_vat, vat, total] if 4; else guess last is total
        $unitPrice = $nums[0] ?? '';
        $without   = $nums[1] ?? '';
        $vatAmt    = $nums[2] ?? '';
        $total     = $nums[3] ?? end($nums);

        $items[] = [
            'item_code'     => $code,
            'description'   => $desc,
            'qty'           => $qty,
            'unit'          => $unit,
            'unit_price'    => $unitPrice,
            'without_vat'   => $without,
            'vat_amount'    => $vatAmt,
            'total_inc_vat' => $total,
            'raw_line'      => $ln, // useful debug
        ];
    }

    return $items;
}
private function crop_image_bottom_percent($srcImage, $destImage, $startPercent = 45)
{
    if (!extension_loaded('imagick')) {
        @copy($srcImage, $destImage);
        return;
    }

    $img = new Imagick();
    $img->readImage($srcImage);

    $w = $img->getImageWidth();
    $h = $img->getImageHeight();

    $startY = (int) round(($startPercent / 100) * $h);
    if ($startY < 0) $startY = 0;
    if ($startY >= $h) $startY = 0;

    $cropH = $h - $startY;

    $img->cropImage($w, $cropH, 0, $startY);
    $img->setImagePage(0, 0, 0, 0);

    // table-friendly enhancement
    $img->setImageColorspace(Imagick::COLORSPACE_GRAY);
    $img->normalizeImage();
    $img->contrastImage(1);
    $img->unsharpMaskImage(0, 1, 0.9, 0.02);

    $img->setImageFormat('png');
    $img->writeImage($destImage);

    $img->clear();
    $img->destroy();
}
private function crop_image_band_percent($src, $dst, $startPercent, $endPercent)
{
    if (!file_exists($src)) return;
    if (!extension_loaded('imagick')) return;

    $img = new Imagick();
    $img->readImage($src);

    $w = $img->getImageWidth();
    $h = $img->getImageHeight();

    $y1 = (int) round($h * ($startPercent / 100));
    $y2 = (int) round($h * ($endPercent / 100));
    $cropH = max(1, $y2 - $y1);

    $img->cropImage($w, $cropH, 0, $y1);
    $img->setImageFormat('png');
    $img->writeImage($dst);

    $img->clear();
    $img->destroy();
}

private function items_from_tsv_table($tsvPath)
{
    if (!file_exists($tsvPath)) return [];

    $lines = file($tsvPath, FILE_IGNORE_NEW_LINES);
    if (!$lines || count($lines) < 2) return [];

    // TSV header: level page block par line word left top width height conf text
    $rowsByLine = []; // key: "topBucket" => words[]

    foreach ($lines as $i => $ln) {
        if ($i === 0) continue;

        $cols = explode("\t", $ln);
        if (count($cols) < 12) continue;

        $level = (int)$cols[0];
        $left  = (int)$cols[6];
        $top   = (int)$cols[7];
        $conf  = (int)$cols[10];
        $text  = trim($cols[11]);

        // We want WORD level only
        if ($level !== 5) continue;
        if ($conf < 0) continue;
        if ($text === '') continue;

        // bucket lines by Y position
        $bucket = (int) floor($top / 12); // adjust bucket size if needed
        $rowsByLine[$bucket][] = ['left'=>$left, 'text'=>$text];
    }

    // Build line strings
    $lineStrings = [];
    ksort($rowsByLine);
    foreach ($rowsByLine as $bucket => $words) {
        usort($words, fn($a,$b)=>$a['left'] <=> $b['left']);
        $lineStrings[] = trim(implode(' ', array_column($words, 'text')));
    }

    // Now parse each line to extract items
    $items = [];
    foreach ($lineStrings as $ln) {
        $lnClean = preg_replace('/\s+/', ' ', $ln);

        // Pattern: CODE DESC QTY UNIT PRICE WITHOUT VAT VATAMT TOTAL
        if (preg_match(
            '/\b([A-Za-z]{1,6}\d{3,8})\b\s+(.+?)\s+(\d{1,6})\s+(BOX|PCS|PC|EA|UNIT|CTN|PACK)\s+([\d,]+\.\d{2})\s+([\d,]+\.\d{2})\s+([\d,]+\.\d{2})\s+([\d,]+\.\d{2})/i',
            $lnClean, $m
        )) {
            $items[] = [
                'item_code'     => strtoupper($m[1]),
                'description'   => trim($m[2]),
                'qty'           => $m[3],
                'unit'          => strtoupper($m[4]),
                'unit_price'    => $m[5],
                'without_vat'   => $m[6],
                'vat_amount'    => $m[7],
                'total_inc_vat' => $m[8],
            ];
        }
    }

    return $items;
}
private function extract_items_robust($text)
{
    $items = [];
    $text = preg_replace("/\r\n|\r/", "\n", $text);
    $text = preg_replace("/[ \t]+/", " ", $text);
    
    // Split into lines and look for item patterns
    $lines = explode("\n", $text);
    
    foreach ($lines as $line) {
        $line = trim($line);
        if (empty($line)) continue;
        
        // Skip header lines
        if (preg_match('/Item\s*Code|Description\s*Qty|Qty\s*Unit|Unit\s*Price|Total/i', $line)) {
            continue;
        }
        
        // Look for item codes (PI01168, PLO1168, etc.)
        if (preg_match('/\b(P[IL]O?\s*\d{4,8})\b/i', $line, $codeMatch)) {
            $itemCode = strtoupper(preg_replace('/\s+/', '', $codeMatch[1]));
            
            // Extract all decimal amounts
            preg_match_all('/\d[\d,]*\.\d{2}/', $line, $amounts);
            $amounts = $amounts[0] ?? [];
            
            if (count($amounts) >= 2) {
                // Extract quantity
                $qty = '';
                if (preg_match('/\b(\d{1,6})\s*(?:BOX|PCS|PC|EA|UNIT|CTN|PACK)?\b/i', $line, $qtyMatch)) {
                    $qty = $qtyMatch[1];
                }
                
                // Extract unit
                $unit = '';
                if (preg_match('/\b(BOX|PCS|PC|EA|UNIT|CTN|PACK)\b/i', $line, $unitMatch)) {
                    $unit = strtoupper($unitMatch[1]);
                }
                
                // Build description
                $desc = preg_replace('/\b' . preg_quote($codeMatch[1], '/') . '\b/i', '', $line);
                $desc = preg_replace('/\d[\d,]*\.\d{2}/', '', $desc);
                $desc = preg_replace('/\b' . $qty . '\b/', '', $desc);
                $desc = preg_replace('/\b' . $unit . '\b/i', '', $desc);
                $desc = trim(preg_replace('/\s+/', ' ', $desc));
                
                // Map amounts (most invoices have: unit_price, amount, vat, total)
                $unit_price = $amounts[0] ?? '';
                $without_vat = (count($amounts) >= 2) ? $amounts[1] : '';
                $vat_amount = (count($amounts) >= 3) ? $amounts[2] : '';
                $total_inc_vat = end($amounts);
                
                $items[] = [
                    'item_code'     => $itemCode,
                    'description'   => $desc ?: 'ITEM',
                    'qty'           => $qty,
                    'unit'          => $unit,
                    'unit_price'    => $unit_price,
                    'without_vat'   => $without_vat,
                    'vat_amount'    => $vat_amount,
                    'total_inc_vat' => $total_inc_vat,
                    'raw_line'      => $line
                ];
            }
        }
    }
    
    return $items;
}
}
