<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Home extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();

        // Load model (must exist: application/models/ocr_model.php)
        $this->load->model('ocr_model');

        $this->load->library(['form_validation', 'session', 'upload']);
        $this->load->helper(['url', 'form']);
    }

    // Page
    public function invoice_form()
    {
        $this->load->view('invoice_form');
    }

    // AJAX Upload + Normalize + Insert DB
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

        // Validate mime + size
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

        if (!in_array($mime, $allowed_mime)) {
            echo json_encode(['status'=>'error','message'=>'Invalid file type']);
            return;
        }

        if ($size > 5 * 1024 * 1024) {
            echo json_encode(['status'=>'error','message'=>'File must be below 5MB']);
            return;
        }

        // Create folders
        $folderKey = uniqid('inv_'); // folder id (can keep this)
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
        $ext      = strtolower($up['file_ext']); // ".pdf", ".png"

        // Normalize
        try {
            $normalizedFiles = [];

            if ($ext === '.pdf') {
                $normalizedFiles = $this->normalize_pdf_to_images($fullPath, $normalizedPath);
            } elseif (in_array($ext, ['.jpg', '.jpeg', '.png'])) {
                $target = $normalizedPath . 'page_1' . $ext;
                if (!copy($fullPath, $target)) {
                    throw new Exception('Failed to copy image to normalized folder');
                }
                $normalizedFiles[] = $target;
            } else {
                echo json_encode(['status'=>'error','message'=>'DOC/DOCX not implemented yet. Upload PDF/Image.']);
                return;
            }

            // =======================
            // INSERT INTO DATABASE
            // =======================
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

            $rows = [];
            $now = date('Y-m-d H:i:s');
            $pageNo = 1;

            foreach ($normalizedFiles as $imgPath) {
                $rows[] = [
                    'document_id' => $document_id,
                    'page_no'     => $pageNo,
                    'image_path'  => str_replace(FCPATH, '', $imgPath),
                    'ocr_text'    => null,
                    'ocr_confidence' => null,
                    'created_at'  => $now,
                    'updated_at'  => $now,
                ];
                $pageNo++;
            }

            $pagesInserted = $this->ocr_model->create_pages_batch($rows);
            if (!$pagesInserted) {
                echo json_encode([
                    'status'   => 'error',
                    'message'  => 'Failed to insert pages into invoice_document_pages',
                    'db_error' => $this->db->error()
                ]);
                return;
            }

            // Prefill dummy (later from OCR)
            $prefill = [
                'invoice_date' => date('Y-m-d'),
                'invoice_time' => date('H:i'),
                'due_date'     => date('Y-m-d')
            ];

            // preview urls
            $webBase = base_url('assets/uploads/invoices/' . $folderKey . '/normalized/');
           $previewUrls = [];
foreach ($normalizedFiles as $imgPath) {
    $previewUrls[] = base_url(str_replace(FCPATH, '', $imgPath));
}


            echo json_encode([
                'status'           => 'success',
                'message'          => 'Uploaded & normalized successfully',
                'document_id'      => $document_id,
                'prefill'          => $prefill,
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

    // -----------------------------
    // PDF helpers
    // -----------------------------
    private function pdf_to_png_pdftoppm($pdfPath, $outputDir)
    {
        if (!is_dir($outputDir) && !mkdir($outputDir, 0777, true)) {
            throw new Exception('Cannot create output directory: ' . $outputDir);
        }

        $prefix = rtrim($outputDir, '/\\') . DIRECTORY_SEPARATOR . 'page';

        $cmd = 'pdftoppm -png -r 300 '
            . escapeshellarg($pdfPath) . ' '
            . escapeshellarg($prefix);

        $out = [];
        $code = 0;
        exec($cmd . ' 2>&1', $out, $code);

        if ($code !== 0) {
            throw new Exception('pdftoppm failed: ' . implode("\n", $out));
        }

        $files = glob($outputDir . DIRECTORY_SEPARATOR . 'page-*.png');
        if (!$files) {
            throw new Exception('pdftoppm produced no PNG files');
        }

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

 private function normalize_pdf_to_images($pdfPath, $outputDir)
{
    if ($this->command_exists('pdftoppm')) {
        return $this->pdf_to_png_pdftoppm($pdfPath, $outputDir);
    }

    // ✅ Windows fallback: Ghostscript
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
    private function parse_invoice_from_text($text)
{
    // Normalize spaces/newlines
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

    // ---------- Header fields ----------
    if (preg_match('/Customer Name\s+(.+?)(?:\n|Invoice|No)/i', $t, $m)) {
        $out['customer_name'] = trim($m[1]);
    }

    if (preg_match('/Invoice\s*No\s*[:\-]?\s*([A-Za-z0-9\-\/]+)/i', $t, $m) ||
        preg_match('/No\s*Invoice\s*([A-Za-z0-9\-\/]+)/i', $t, $m)) {
        $out['invoice_no'] = trim($m[1]);
    }

    // Dates like 17-Jan-2026 / 20-Jan-2026
    if (preg_match('/Invoice\s*Date\s*[:\-]?\s*([0-9]{1,2}\-[A-Za-z]{3}\-[0-9]{4})/i', $t, $m)) {
        $out['invoice_date'] = trim($m[1]);
    }
    if (preg_match('/Due\s*Date\s*[:\-]?\s*([0-9]{1,2}\-[A-Za-z]{3}\-[0-9]{4})/i', $t, $m) ||
        preg_match('/Date\s*Due.*?([0-9]{1,2}\-[A-Za-z]{3}\-[0-9]{4})/i', $t, $m)) {
        $out['due_date'] = trim($m[1]);
    }

    if (preg_match('/VAT\s*NO\s*[:\-]?\s*([0-9]{6,})/i', $t, $m) ||
        preg_match('/NO\s*VAT\s*([0-9]{6,})/i', $t, $m)) {
        $out['vat_no'] = trim($m[1]);
    }

    if (preg_match('/Sales\s*Person\s*[:\-]?\s*([A-Za-z0-9_ ]+)/i', $t, $m)) {
        $out['sales_person'] = trim($m[1]);
    }

    // Address block (simple heuristic)
    if (preg_match('/Customer\'?s\s*Address\s*(.+?)(?:ZIP\/Postal|Payment Terms|VAT NO)/is', $t, $m)) {
        $out['address'] = trim(preg_replace("/\n+/", " ", $m[1]));
    }

    // ---------- Totals ----------
    if (preg_match('/Total\s*Without\s*VAT\s*[^0-9]*([\d,]+\.\d{2})/i', $t, $m)) {
        $out['totals']['total_without_vat'] = $m[1];
    }
    if (preg_match('/Discount\s*[^0-9]*([\d,]+\.\d{2})/i', $t, $m)) {
        $out['totals']['discount'] = $m[1];
    }
    if (preg_match('/Total\s*VAT\s*[^0-9]*([\d,]+\.\d{2})/i', $t, $m) ||
        preg_match('/VAT\s*Total\s*15%\s*[^0-9]*([\d,]+\.\d{2})/i', $t, $m)) {
        $out['totals']['total_vat'] = $m[1];
    }
    if (preg_match('/Total\s*With\s*VAT.*?([\d,]+\.\d{2})/i', $t, $m) ||
        preg_match('/Total\s*inc\.\s*VAT.*?([\d,]+\.\d{2})/i', $t, $m)) {
        $out['totals']['total_with_vat'] = $m[1];
    }

    // ---------- Bank ----------
    if (preg_match('/Account\s*Name\s*([^\n]+)/i', $t, $m)) {
        $out['bank']['account_name'] = trim($m[1]);
    }
    if (preg_match('/Bank\s*Name\s*([^\n]+)/i', $t, $m)) {
        $out['bank']['bank_name'] = trim($m[1]);
    }
    if (preg_match('/Account\s*Number\s*([A-Za-z0-9]+)/i', $t, $m)) {
        $out['bank']['account_number'] = trim($m[1]);
    }
    if (preg_match('/IBAN\s*No\s*([A-Za-z0-9]+)/i', $t, $m) ||
        preg_match('/IBAN\s*([A-Za-z0-9]{6,})/i', $t, $m)) {
        $out['bank']['iban'] = trim($m[1]);
    }

    // ---------- Items (robust line parsing) ----------
    // Works for the row like: PI01168 MUG SET ... Unit BOX ... Price 70.00 ... Without VAT 5,999.70 ... VAT Amount 899.96 ... Total inc. VAT 6,899.66
    // We'll capture:
    // code, desc, qty, unit, price, without_vat, vat_amount, total_inc_vat
    $lines = preg_split("/\n/", $t);
    foreach ($lines as $ln) {
        $ln = trim($ln);
        if ($ln === '') continue;

        // Match item code + description
        if (preg_match('/\b([A-Z]{1,4}\d{3,})\b\s+(.+?)\s+(\d{1,6})\s+(BOX|PCS|PC|EA|UNIT|CTN|PACK)\b/i', $ln, $m)) {
            $code = $m[1];
            $desc = trim($m[2]);
            $qty  = $m[3];
            $unit = strtoupper($m[4]);

            // Try to find amounts in same line (some invoices place them earlier/later)
            preg_match_all('/([\d,]+\.\d{2})/', $ln, $nums);
            $nums = $nums[1] ?? [];

            // Heuristic for this invoice format:
            // Price, Without VAT, VAT Amount, Total inc VAT appear as 4 decimal numbers.
            $price = $nums[0] ?? '';
            $without = $nums[1] ?? '';
            $vatAmt = $nums[2] ?? '';
            $total = $nums[3] ?? end($nums);

            $out['items'][] = [
                'item_code' => $code,
                'description' => $desc,
                'qty' => $qty,
                'unit' => $unit,
                'unit_price' => $price,
                'without_vat' => $without,
                'vat_amount' => $vatAmt,
                'total_inc_vat' => $total
            ];
        }

        // Fallback for your sample where qty/unit appear elsewhere:
        // PI01168 MUG SET ... BOX 100 ...
        if (empty($out['items']) && preg_match('/\b(PI\d{5})\b\s+(.+?)\s+.*?\b(BOX|PCS|PC|EA)\b\s+(\d{1,6})/i', $ln, $m2)) {
            preg_match_all('/([\d,]+\.\d{2})/', $ln, $nums2);
            $nums2 = $nums2[1] ?? [];

            $out['items'][] = [
                'item_code' => $m2[1],
                'description' => trim($m2[2]),
                'qty' => $m2[4],
                'unit' => strtoupper($m2[3]),
                'unit_price' => $nums2[0] ?? '',
                'without_vat' => $nums2[1] ?? '',
                'vat_amount' => $nums2[2] ?? '',
                'total_inc_vat' => $nums2[3] ?? (end($nums2) ?: '')
            ];
        }
    }

    return $out;
}

private function preprocess_image_for_ocr($imagePath)
{
    if (!file_exists($imagePath)) {
        throw new Exception("Image not found: " . $imagePath);
    }

    // ✅ if Imagick not enabled, do nothing (DON'T CRASH)
    if (!extension_loaded('imagick')) {
        return;
    }

    $img = new Imagick();
    if (!$img->readImage($imagePath)) {
        throw new Exception("Imagick cannot read image: " . $imagePath);
    }

    $img->setImageBackgroundColor('white');
    $img = $img->mergeImageLayers(Imagick::LAYERMETHOD_FLATTEN);

    $img->setImageColorspace(Imagick::COLORSPACE_GRAY);

    $w = $img->getImageWidth();
    $h = $img->getImageHeight();
    $img->resizeImage($w * 2, $h * 2, Imagick::FILTER_LANCZOS, 1);

    $img->normalizeImage();
    $img->contrastImage(1);

    $img->blurImage(1, 0.5);
    $img->sharpenImage(1, 0.5);

    $img->setImageResolution(300, 300);
    $img->resampleImage(300, 300, Imagick::FILTER_LANCZOS, 1);

    $img->setImageFormat('png');
    $img->writeImage($imagePath);

    $img->clear();
    $img->destroy();
}



public function run_ocr()
{
    header('Content-Type: application/json; charset=utf-8');

    ini_set('display_errors', 1);
    error_reporting(E_ALL);

    set_error_handler(function($severity,$message,$file,$line){
        throw new ErrorException($message, 0, $severity, $file, $line);
    });

    try {

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['status'=>'error','message'=>'Invalid request method']);
            return;
        }

        $document_id = $this->input->post('document_id');
        if (!$document_id) {
            echo json_encode(['status'=>'error','message'=>'document_id is required']);
            return;
        }

        $pages = $this->ocr_model->get_pages($document_id);
        if (empty($pages)) {
            echo json_encode(['status'=>'error','message'=>'No pages found for this document']);
            return;
        }

        // tmp folder
        $tmpDir = FCPATH . 'assets/uploads/tmp/';
        if (!is_dir($tmpDir)) {
            mkdir($tmpDir, 0777, true);
        }

        // tesseract path
        $tesseract = '"C:\Program Files\Tesseract-OCR\tesseract.exe"';

        // languages
        $lang = 'eng';
        $psm  = 6;
        $oem  = 1;

        $this->ocr_model->update_document_status($document_id, 'ocr_running');

        $done = 0;
        $errors = [];
        // Combine all OCR text from DB pages (more reliable than tmp files)
$allText = '';
$pages2 = $this->ocr_model->get_pages($document_id);
foreach ($pages2 as $pp) {
    $allText .= "\n\n" . ($pp['ocr_text'] ?? '');
}

$parsed = $this->parse_invoice_from_text($allText);


        foreach ($pages as $p) {

            $imgRel = ltrim($p['image_path'], '/\\');
            $imgAbs = FCPATH . $imgRel;

            if (!file_exists($imgAbs)) {
                $errors[] = "Missing image page {$p['page_no']}: $imgAbs";
                continue;
            }

            // preprocess
            $this->preprocess_image_for_ocr($imgAbs);

            $tmpBase = $tmpDir . 'ocr_' . $document_id . '_' . $p['page_no'];

            // ✅ TEXT output (no "tsv" at end)
            $cmd = $tesseract . ' '
                . escapeshellarg($imgAbs) . ' '
                . escapeshellarg($tmpBase)
                . ' -l ' . escapeshellarg($lang)
                . ' --oem ' . $oem
                . ' --psm ' . $psm;

            $out = [];
            $code = 0;
            exec($cmd . ' 2>&1', $out, $code);

            if ($code !== 0) {
                $errors[] = "Tesseract failed page {$p['page_no']}: " . implode(" | ", $out);
                continue;
            }

            $txtFile = $tmpBase . '.txt';
            if (!file_exists($txtFile)) {
                $errors[] = "TXT not created page {$p['page_no']} (expected $txtFile)";
                continue;
            }

            $ocrText = trim(file_get_contents($txtFile));

            // ✅ Save to DB
            $ok = $this->ocr_model->update_page_ocr($document_id, $p['page_no'], $ocrText, null);
            if (!$ok) {
                $errors[] = "DB update failed page {$p['page_no']}: " . $this->db->error();
                continue;
            }

            $done++;
        }

        $this->ocr_model->update_document_status($document_id, ($done > 0 ? 'ocr_done' : 'ocr_failed'));

     echo json_encode([
    'status' => ($done > 0 ? 'success' : 'error'),
    'message' => "OCR finished. Saved: {$done}/" . count($pages),
    'document_id' => $document_id,
    'processed_pages' => $done,
    'total_pages' => count($pages),
    'errors' => $errors,
    'prefill' => [
        // map to your form fields
        'invoice_date' => date('Y-m-d'),
        'invoice_time' => date('H:i'),
        'due_date'     => date('Y-m-d'),
        'order_no'     => $parsed['invoice_no'],     // optional mapping
        'reference_no' => '',
        'subject'      => 'Customer: '.$parsed['customer_name']
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


}