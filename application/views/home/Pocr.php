<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Pocr extends CI_Controller
{
    // Remote Python OCR (PaddleOCR-VL + Claude Bedrock validator) service.
    // Update this if the EC2 instance / IP changes.
    //this is 30 s resul goving m7i-flex-large
    
    private $ocr_api_url = 'http://127.0.0.1:8001/ocr/process';
    
    public function __construct()
    {
        parent::__construct();

        $this->load->model('ocr_model');
        $this->load->library(['form_validation', 'session', 'upload']);
        $this->load->helper(['url', 'form']);
    }
// ==========================================
// LOG ENTRY + REFERENCE GENERATION
// ==========================================
// public function create_log()
// {
//     header('Content-Type: application/json; charset=utf-8');

//     try {
//         $type = $this->input->get('type');

//         if (!$type) {
//             echo json_encode(['status' => 'error', 'message' => 'Type is required']);
//             return;
//         }

//         $logData = [
//             'type'       => $type,
//             'profile_id' => $this->input->get('profile_id') ?: 0,
//             'ip_address' => $this->input->ip_address(),
//             'date_time'  => date('Y-m-d H:i:s'),
//             'status'     => 0,
//         ];

//         $this->db->insert('tbl_logs', $logData);
//         $insert_id = $this->db->insert_id();

//         if (!$insert_id) {
//             echo json_encode([
//                 'status'   => 'error',
//                 'message'  => 'Failed to create log',
//                 'db_error' => $this->db->error()
//             ]);
//             return;
//         }

//         echo json_encode([
//             'status'  => 'success',
//             'message' => 'Log created successfully',
//             'data'    => ['insert_id' => $insert_id]
//         ]);

//     } catch (Exception $e) {
//         echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
//     }
// }

public function generate_reference($type = 0)
{
    header('Content-Type: application/json; charset=utf-8');

    try {
        if (!$type) {
            echo json_encode(['status' => 'error', 'message' => 'Type is required']);
            return;
        }

        $logData = [
            'type'       => $type,
            'profile_id' => 0,
            'ip_address' => $this->input->ip_address(),
            'date_time'  => date('Y-m-d H:i:s'),
            'status'     => 0,
        ];

        $this->db->insert('tbl_logs', $logData);
        $reference = $this->db->insert_id();

        if (!$reference) {
            echo json_encode([
                'status'   => 'error',
                'message'  => 'Failed to generate reference',
                'db_error' => $this->db->error()
            ]);
            return;
        }

        echo json_encode([
            'status'  => 'success',
            'message' => 'Reference generated',
            'data'    => ['reference' => $reference]
        ]);

    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
}
public function pocr()
{
    $data['customers'] = $this->db->where('type',0)->where('status',0)->get('tbl_profile')->result_array();
    $data['vendors']   = $this->db->where('type',2)->where('status',0)->get('tbl_profile')->result_array();
    $data['employees'] = $this->db->where('status',0)->where('type',1)->get('tbl_profile')->result_array();
    $data['projects']  = $this->db->where('status',0)->get('tbl_project')->result_array();

    $data['items'] = $this->db
        ->select('id, name')
        ->from('tbl_items')
        ->get()
        ->result_array();

    $itemIds = array_column($data['items'], 'id');
    $unitsByItem = $this->ocr_model->get_units_for_items($itemIds);
    foreach ($data['items'] as &$it) {
        $it['units'] = $unitsByItem[$it['id']] ?? [];
    }
    unset($it);

    // ✅ pick up the reference generated on the Upload Purchase click
    $data['prefill_ref'] = $this->input->get('ref') ?: '';

    $data['bn2']  = 'Home';
    $data['bnh1'] = 'home/index';
    $data['bn3']  = 'Invoice';

    $this->load->view('template/header', $data);
    $this->load->view('template/navbar', $data);
    $this->load->view('pocr/pocr', $data);
    $this->load->view('template/footer');
    $this->load->view('template/last');
}

    // ==========================================
    // 3) SAVE INVOICE (final, human-reviewed data)
    //    Called by the main "Save Invoice" form —
    //    normal POST + redirect (not AJAX), so flash
    //    messages show after the page reloads.
    // ==========================================
   public function save_invoice()
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        redirect('pocr/invoice_form');
        return;
    }

    $document_id = (int)$this->input->post('document_id');

    // line items come in as parallel arrays: description[], qty[], unit_price[], vat_rate[], total[]
    $descriptions = $this->input->post('description') ?: [];
    $qtys         = $this->input->post('qty') ?: [];
    $unitPrices   = $this->input->post('unit_price') ?: [];
    $vatRates     = $this->input->post('vat_rate') ?: [];
    $totals       = $this->input->post('total') ?: [];

    $subtotal   = 0;
    $taxTotal   = 0;
    $grandTotal = 0;
    $items      = [];

    $count = count($descriptions);
    for ($i = 0; $i < $count; $i++) {
        $qty       = (float)($qtys[$i] ?? 0);
        $unitPrice = (float)($unitPrices[$i] ?? 0);
        $vatRate   = (float)($vatRates[$i] ?? 0);
        $lineTotal = (float)($totals[$i] ?? 0);

        $lineWithoutVat = $qty * $unitPrice;
        $lineVatAmount  = $lineWithoutVat * ($vatRate / 100);

        $subtotal   += $lineWithoutVat;
        $taxTotal   += $lineVatAmount;
        $grandTotal += $lineTotal;

        $items[] = [
            'item_code_text'    => '',
            'item_name_text'    => $descriptions[$i] ?? '',
            'qty'               => $qty,
            'unit'              => null,
            'unit_price'        => $unitPrice,
            'total_without_vat' => $lineWithoutVat,
            'vat_amount'        => $lineVatAmount,
            'total_amount'      => $lineTotal,
        ];
    }

    // project_id select — sanitize non-numeric values to null
    $projectId = $this->input->post('project_id');
    $projectId = is_numeric($projectId) ? (int)$projectId : null;

    $data = [
        'document_id'              => $document_id ?: null,
        'invoice_date'             => $this->input->post('invoice_date') ?: null,
        'invoice_time'             => $this->input->post('invoice_time') ?: null,
        'due_date'                 => $this->input->post('due_date') ?: null,
        'invoice_period'           => $this->input->post('invoice_period'),
        'order_no'                 => $this->input->post('order_no'),
        'reference_no'             => $this->input->post('reference_no'),
        'contract_po_no'           => $this->input->post('contract_po_no'),
        'contract_po_date'         => $this->input->post('contract_po_date') ?: null,
        'other_reference_no'       => $this->input->post('other_reference_no'),
        'supplier_invoice_no'      => $this->input->post('supplier_invoice_no'),
        'supplier_invoice_date'    => $this->input->post('supplier_invoice_date') ?: null,
        'employee'                 => $this->input->post('employee'),
        'sale_account'             => $this->input->post('sale_account'),
        'invoice_type'             => $this->input->post('invoice_type'),
        'vat_type'                 => $this->input->post('vat_type'),
        'subject'                  => $this->input->post('subject'),
        'customer_id'              => $this->input->post('customer_id') ?: null,
        'vendor_id'                => $this->input->post('vendor_id') ?: null,
        'project_id'               => $projectId,
        'currency'                 => $this->input->post('currency'),
        'conversion_rate'          => $this->input->post('conversion_rate') ?: 1,
        // Our details
        'our_vat_reg_no'           => $this->input->post('our_vat_reg_no'),
        'our_cr_no'                => $this->input->post('our_cr_no'),
        // Customer address
        'customer_vat_reg_no'      => $this->input->post('customer_vat_reg_no'),
        'customer_cr_no'           => $this->input->post('customer_cr_no'),
        'customer_building_no'     => $this->input->post('customer_building_no'),
        'customer_street'          => $this->input->post('customer_street'),
        'customer_district'        => $this->input->post('customer_district'),
        'customer_city'            => $this->input->post('customer_city'),
        'customer_country'         => $this->input->post('customer_country'),
        'customer_postal_code'     => $this->input->post('customer_postal_code'),
        // Vendor/Seller address
        'vendor_vat_no'            => $this->input->post('vendor_vat_no'),
        'vendor_building_no'       => $this->input->post('vendor_building_no'),
        'vendor_street'            => $this->input->post('vendor_street'),
        'vendor_district'          => $this->input->post('vendor_district'),
        'vendor_city'              => $this->input->post('vendor_city'),
        'vendor_country'           => $this->input->post('vendor_country'),
        'vendor_postal_code'       => $this->input->post('vendor_postal_code'),
        // Bank details
        'bank_name'                => $this->input->post('bank_name'),
        'bank'                     => $this->input->post('bank'),
        'account_no'               => $this->input->post('account_no'),
        'iban'                     => $this->input->post('iban'),
        'swift_code'               => $this->input->post('swift_code'),
        'branch'                   => $this->input->post('branch'),
        // Totals
        'subtotal'                 => $subtotal,
        'tax_total'                => $taxTotal,
        'grand_total'              => $grandTotal,
        'is_confirmed'             => 1,
        'created_at'               => date('Y-m-d H:i:s'),
        'updated_at'               => date('Y-m-d H:i:s'),
    ];

    $invoice_id = $this->ocr_model->create_invoice($data);

    if (!$invoice_id) {
        $this->session->set_flashdata('error', 'Failed to save invoice: ' . json_encode($this->db->error()));
        redirect('home/invoice_form');
        return;
    }

    if (!empty($items)) {
        foreach ($items as &$it) {
            $it['invoice_id'] = $invoice_id;
            $it['created_at'] = date('Y-m-d H:i:s');
        }
        unset($it);
        $this->ocr_model->create_invoice_items_batch($items);
    }

    $this->session->set_flashdata('success', "Invoice #{$invoice_id} saved successfully.");
    redirect('home/invoice_form');
}

    // ==========================================
    // 1) UPLOAD + NORMALIZE + INSERT DB
    //    Supports: PDF, JPG/JPEG/PNG, DOC/DOCX
public function upload_invoice_ocr()
{
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
    header('Content-Type: application/json; charset=utf-8');

    try {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode([
                'status' => 'error',
                'message' => 'Invalid request method'
            ]);
            return;
        }

        if (empty($_FILES['invoice_file']['name'])) {
            echo json_encode([
                'status' => 'error',
                'message' => 'No file selected'
            ]);
            return;
        }

        $allowedExt = ['png', 'jpg', 'jpeg', 'pdf'];
        $tmp = $_FILES['invoice_file']['tmp_name'];
        $size = (int)$_FILES['invoice_file']['size'];
        $extFromName = strtolower(pathinfo($_FILES['invoice_file']['name'], PATHINFO_EXTENSION));

        if (!in_array($extFromName, $allowedExt)) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Invalid file type. Allowed: ' . implode(', ', $allowedExt)
            ]);
            return;
        }

        if ($size > (5 * 1024 * 1024)) {
            echo json_encode([
                'status' => 'error',
                'message' => 'File must be below 5MB'
            ]);
            return;
        }

        // Read file content into memory
        $fileContent = file_get_contents($tmp);
        $fileName = $_FILES['invoice_file']['name'];
        
        // Determine mime type
        if (function_exists('finfo_open')) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mimeType = finfo_file($finfo, $tmp);
            finfo_close($finfo);
        } else {
            $mimeType = $_FILES['invoice_file']['type'];
        }

        // Return the file content as base64 or raw data
        echo json_encode([
            'status' => 'success',
            'message' => 'File read successfully.',
            'file_data' => base64_encode($fileContent), // Send as base64
            'file_name' => $fileName,
            'file_size' => $size,
            'file_ext' => $extFromName,
            'mime_type' => $mimeType
        ]);

    } catch (Exception $e) {
        echo json_encode([
            'status' => 'error',
            'message' => $e->getMessage()
        ]);
    }
}

// ==========================================
// UTF-8 sanitizer — used before every json_encode()
// that carries OCR/Arabic text, so one bad byte
// degrades a single field instead of killing the
// entire JSON response.
// ==========================================
private function force_utf8($value)
{
    if (is_array($value)) {
        foreach ($value as $k => $v) {
            $value[$k] = $this->force_utf8($v);
        }
        return $value;
    }
    if (is_string($value)) {
        return mb_convert_encoding($value, 'UTF-8', 'UTF-8');
    }
    return $value;
}

    // ==========================================
    // 2) RUN OCR (PYTHON PaddleOCR) + SAVE + PARSE
    // ==========================================
   public function run_ocr()
{
    set_time_limit(0);
    ini_set('max_execution_time', 0);
    ini_set('memory_limit', '1024M');
    header('Content-Type: application/json; charset=utf-8');

    try {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
            return;
        }

        // Get file data from POST
        $file_data = $this->input->post('file_data');
        $file_name = $this->input->post('file_name');
        $mime_type = $this->input->post('mime_type');
        
        if (empty($file_data)) {
            echo json_encode(['status' => 'error', 'message' => 'file_data is required']);
            return;
        }

        // Decode base64
        $fileContent = base64_decode($file_data);
        
        // Create a temporary file in memory
        $tempFile = tempnam(sys_get_temp_dir(), 'ocr_');
        file_put_contents($tempFile, $fileContent);

        // Call Python OCR service
        $ch = curl_init($this->ocr_api_url);
        
        // Determine mime type from file extension
        $ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        if ($ext === 'pdf') {
            $mime = 'application/pdf';
        } elseif ($ext === 'jpg' || $ext === 'jpeg') {
            $mime = 'image/jpeg';
        } elseif ($ext === 'png') {
            $mime = 'image/png';
        } else {
            $mime = $mime_type;
        }

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => [
                'file' => new CURLFile($tempFile, $mime, $file_name),
                'doc_type' => 'invoice',
            ],
            CURLOPT_ENCODING => '', // auto-negotiate/decode gzip if the Python service compresses responses
            CURLOPT_TIMEOUT => 2290,
        ]);

        $resp = curl_exec($ch);
        $http = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err = curl_error($ch);
        curl_close($ch);

        // Clean up temp file
        @unlink($tempFile);

        if ($resp === false || $http !== 200) {
            echo json_encode([
                'status' => 'error',
                'message' => 'OCR failed: ' . ($err !== '' ? $err : $resp),
                'http_code' => $http
            ]);
            return;
        }

        $py = json_decode($resp, true);
        if (!is_array($py)) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Invalid OCR response',
                'response' => substr($resp, 0, 500)
            ]);
            return;
        }

        if (!empty($py['success'])) {
            $data = $py['data'] ?? [];
            $fields = $data['fields'] ?? [];
        } elseif (in_array($py['status'] ?? '', ['success', 'partial'], true)) {
            $data = [];
            $prefill = $py['prefill'] ?? [];
            $fields = [
                'invoice_date' => $prefill['invoice_date'] ?? '',
                'due_date' => $prefill['due_date'] ?? '',
                'invoice_number' => $prefill['invoice_no'] ?? '',
                'po_number' => $prefill['order_no'] ?? '',
                'reference_number' => $prefill['reference_no'] ?? '',
                'vendor_name' => $prefill['vendor_name'] ?? '',
                'vendor_vat' => $prefill['vat'] ?? '',
                'customer_vat' => $prefill['vat'] ?? '',
                'vendor_address' => $prefill['vendor_address'] ?? '',
                'customer_address' => $prefill['customer_address'] ?? '',
                'supplier_invoice_number' => $prefill['supplier_invoice_number'] ?? '',
                'currency' => $prefill['currency'] ?? '',
                'subtotal' => $prefill['subtotal'] ?? '',
                'vat_amount' => $prefill['vat_amount'] ?? '',
                'vat_rate' => $prefill['vat_rate'] ?? '',
                'total' => $prefill['total'] ?? '',
                'line_items' => $py['parsed']['line_items'] ?? [],
            ];
        } else {
            echo json_encode([
                'status' => 'error',
                'message' => 'Invalid OCR response',
                'response' => substr($resp, 0, 500)
            ]);
            return;
        }

        if (empty($fields['customer_name'])) {
            $customerName = '';
            foreach ([
                $fields['buyer_name'] ?? null,
                $fields['client_name'] ?? null,
                $fields['customer'] ?? null,
                $fields['buyer'] ?? null,
                $data['customer_name'] ?? null,
                $data['buyer_name'] ?? null,
                $data['customer'] ?? null,
                $data['buyer'] ?? null,
                $data['parsed']['customer_name'] ?? null,
                $data['parsed']['buyer_name'] ?? null,
                $py['parsed']['customer_name'] ?? null,
                $py['parsed']['buyer_name'] ?? null,
                $py['prefill']['customer_name'] ?? null,
            ] as $candidate) {
                if (is_string($candidate) && trim($candidate) !== '') {
                    $customerName = trim($candidate);
                    break;
                }
            }

            if ($customerName === '') {
                foreach ([
                    $data['ocr_text'] ?? null,
                    $data['raw_text'] ?? null,
                    $data['extracted_text'] ?? null,
                    $data['text'] ?? null,
                    $py['ocr_text'] ?? null,
                    $py['raw_text'] ?? null,
                    $py['extracted_text'] ?? null,
                    $py['text'] ?? null,
                ] as $ocrText) {
                    if (!is_string($ocrText) || trim($ocrText) === '') {
                        continue;
                    }

                    $parsedText = $this->parse_invoice_comprehensive($ocrText);
                    if (!empty($parsedText['customer_name'])) {
                        $customerName = $parsedText['customer_name'];
                        break;
                    }
                }
            }

            $fields['customer_name'] = $customerName;
        }

        // DEBUG: log the exact keys/values the Python service sent back,
        // BEFORE we map anything — this tells us whether Arabic text
        // exists here under a different key name (e.g. vendor_name_ar).
      log_message('debug', 'RAW OCR FIELDS: ' . print_r($fields, true));

        // Match vendors and customers
        $vendorMatches = [];
        $customerMatches = [];
        
        if (!empty($fields['vendor_name'])) {
            $vendorMatches = $this->ocr_model->match_vendor($fields['vendor_name']);
        }
        if (!empty($fields['customer_name'])) {
            $customerMatches = $this->ocr_model->match_customer($fields['customer_name']);
        }

        $matchedVendor = $vendorMatches[0] ?? null;
        $matchedCustomer = $customerMatches[0] ?? null;

        // Match items
        $lineItems = $fields['line_items'] ?? [];
        foreach ($lineItems as &$li) {
            $li['item_matches'] = !empty($li['description'])
                ? $this->ocr_model->match_item($li['description'])
                : [];
        }
        unset($li);

        $payload = [
            'status' => 'success',
            'message' => 'OCR completed successfully',
            'vendor_matches' => $vendorMatches,
            'customer_matches' => $customerMatches,
            'timing' => $py['timing'] ?? [],
            'processing_time_ms' => $py['processing_time_ms'] ?? 0,
            'prefill' => [
                'invoice_date' => !empty($fields['invoice_date']) ? $this->date_to_ymd($fields['invoice_date']) : date('Y-m-d'),
                'invoice_time' => date('H:i'),
                'due_date' => !empty($fields['due_date']) ? $this->date_to_ymd($fields['due_date']) : date('Y-m-d'),
                'order_no' => $fields['invoice_number'] ?? '',
                'reference_no' => $fields['po_number'] ?? '',
                'invoice_no' => $fields['invoice_number'] ?? '',
                'vendor_name' => $fields['vendor_name'] ?? '',
                'customer_name' => $fields['customer_name'] ?? '',
                'vendor_id' => $matchedVendor['id'] ?? '',
                'customer_id' => $matchedCustomer['id'] ?? '',
                'vat' => $fields['customer_vat'] ?? '',
                'address' => $fields['customer_address'] ?? $fields['vendor_address'] ?? '',
                'vendor_address' => $fields['vendor_address'] ?? '',
                'customer_address' => $fields['customer_address'] ?? '',
                'supplier_invoice_number' => $fields['supplier_invoice_number'] ?? '',
                'currency' => $fields['currency'] ?? '',
                'subtotal' => $fields['subtotal'] ?? '',
                'vat_amount' => $fields['vat_amount'] ?? '',
                'vat_rate' => $fields['vat_rate'] ?? '',
                'total' => $fields['total'] ?? '',
            ],
            'parsed' => [
                'line_items' => $lineItems
            ]
        ];

        // Clean any invalid byte sequences so json_encode can never
        // silently fail on a single bad character in the OCR payload.
        $payload = $this->force_utf8($payload);

        $json = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        if ($json === false) {
            echo json_encode([
                'status' => 'error',
                'message' => 'JSON encode failed: ' . json_last_error_msg()
            ]);
            return;
        }

        echo $json;

    } catch (Throwable $e) {
        echo json_encode([
            'status' => 'error',
            'message' => 'OCR crashed: ' . $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine()
        ]);
    }
}
private function run_structured_parse($document_id, $doc, $ext)
{
    $fullPath = FCPATH . ltrim($doc['original_file_path'], '/');

    $rows = [];

    if (in_array($ext, ['csv','tsv'])) {
        $delimiter = ($ext === 'tsv') ? "\t" : ',';
        if (($handle = fopen($fullPath, 'r')) !== false) {
            $header = fgetcsv($handle, 0, $delimiter);
            $header = array_map(fn($h) => strtolower(trim($h)), $header);
            while (($line = fgetcsv($handle, 0, $delimiter)) !== false) {
                $rows[] = array_combine($header, $line);
            }
            fclose($handle);
        }
    } else {
        // xls/xlsx — requires phpoffice/phpspreadsheet via composer
        if (!class_exists(\PhpOffice\PhpSpreadsheet\IOFactory::class)) {
            echo json_encode(['status'=>'error','message'=>'PhpSpreadsheet library not installed. Run: composer require phpoffice/phpspreadsheet']);
            return;
        }
        $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($fullPath);
        $sheetData = $spreadsheet->getActiveSheet()->toArray(null, true, true, false);
        $header = array_map(fn($h) => strtolower(trim((string)$h)), $sheetData[0]);
        for ($i = 1; $i < count($sheetData); $i++) {
            $rows[] = array_combine($header, $sheetData[$i]);
        }
    }

    // Map flexible column names -> line items
    // Adjust these key guesses to match your actual spreadsheet headers
    $lineItems = [];
    foreach ($rows as $r) {
        $desc = $r['description'] ?? $r['item'] ?? $r['item_name'] ?? '';
        if ($desc === '') continue; // skip blank rows

        $lineItems[] = [
            'description' => $desc,
            'quantity'    => $r['qty'] ?? $r['quantity'] ?? 1,
            'unit_price'  => $r['unit_price'] ?? $r['price'] ?? 0,
            'vat_rate'    => $r['vat_rate'] ?? $r['vat'] ?? 15,
            'total'       => $r['total'] ?? $r['amount'] ?? '',
        ];
    }

    foreach ($lineItems as &$li) {
        $li['item_matches'] = !empty($li['description'])
            ? $this->ocr_model->match_item($li['description'])
            : [];
    }
    unset($li);

    // Header-level fields, if your spreadsheet has a single-row "meta" section
    // Otherwise these stay blank and the user fills them in manually during review
    $headerFields = [
        'invoice_number' => $rows[0]['invoice_no'] ?? '',
        'invoice_date'   => $rows[0]['invoice_date'] ?? '',
        'due_date'       => $rows[0]['due_date'] ?? '',
        'vendor_name'    => $rows[0]['vendor_name'] ?? '',
        'customer_name'  => $rows[0]['customer_name'] ?? '',
        'customer_vat'   => $rows[0]['vat'] ?? '',
        'currency'       => $rows[0]['currency'] ?? '',
        'line_items'     => $lineItems,
    ];

    $vendorMatches   = !empty($headerFields['vendor_name']) ? $this->ocr_model->match_vendor($headerFields['vendor_name']) : [];
    $customerMatches = !empty($headerFields['customer_name']) ? $this->ocr_model->match_customer($headerFields['customer_name']) : [];
    $matchedVendor   = $vendorMatches[0] ?? null;
    $matchedCustomer = $customerMatches[0] ?? null;

    $this->ocr_model->update_document_status($document_id, 'ocr_done');

    $payload = [
        'status' => 'success',
        'message' => 'Structured file parsed successfully.',
        'document_id' => $document_id,
        'vendor_matches'   => $vendorMatches,
        'customer_matches' => $customerMatches,
        'prefill' => [
            'invoice_date'  => !empty($headerFields['invoice_date']) ? $this->date_to_ymd($headerFields['invoice_date']) : date('Y-m-d'),
            'invoice_time'  => date('H:i'),
            'due_date'      => !empty($headerFields['due_date']) ? $this->date_to_ymd($headerFields['due_date']) : date('Y-m-d'),
            'order_no'      => $headerFields['invoice_number'] ?? '',
            'reference_no'  => '',
            'invoice_no'    => $headerFields['invoice_number'] ?? '',
            'vendor_name'   => $headerFields['vendor_name'] ?? '',
            'vendor_id'     => $matchedVendor['id'] ?? '',
            'currency_id'   => $matchedVendor['currency_id'] ?? '',
            'customer_id'   => $matchedCustomer['id'] ?? '',
            'vat'           => $headerFields['customer_vat'] ?? '',
            'currency'      => $headerFields['currency'] ?? '',
        ],
        'parsed' => $headerFields
    ];

    $payload = $this->force_utf8($payload);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}
    // ==========================================
    // PARSING (python text -> fields + items)
    // ==========================================
    private function parse_invoice_comprehensive($text)
    {
        $t = (string)$text;
        $t = preg_replace("/[ \t]+/", " ", $t);
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

        // Customer
        $out['customer_name'] = $this->extract_customer_name($t);

        // Invoice no
        if (preg_match('/Invoice\s*No[\.:]?\s*([A-Z0-9][A-Z0-9\/\-\.\s]{3,30})/i', $t, $m)) {
            $out['invoice_no'] = trim($m[1]);
        } elseif (preg_match('/\bINV[-_ ]?([A-Z0-9]{3,})\b/i', $t, $m)) {
            $out['invoice_no'] = 'INV-' . trim($m[1]);
        }

        // Dates
        if (preg_match('/Invoice\s*Date[\.:]?\s*([0-9]{1,2}[\/\-\.][0-9]{1,2}[\/\-\.][0-9]{4})/i', $t, $m)) {
            $out['invoice_date'] = trim($m[1]);
        }
        if (preg_match('/Due\s*Date[\.:]?\s*([0-9]{1,2}[\/\-\.][0-9]{1,2}[\/\-\.][0-9]{4})/i', $t, $m)) {
            $out['due_date'] = trim($m[1]);
        }

        // VAT
        if (preg_match('/VAT\s*(?:NO|No|Number|ID)?[\.:]?\s*([0-9]{6,})/i', $t, $m)) {
            $out['vat_no'] = trim($m[1]);
        }

        // Totals
        if (preg_match('/Total\s*(?:Without|Exc\.?)\s*VAT[^\d]*([\d,]+\.\d{2})/i', $t, $m)) {
            $out['totals']['total_without_vat'] = $m[1];
        }
        if (preg_match('/VAT\s*Total[^\d]*([\d,]+\.\d{2})/i', $t, $m)) {
            $out['totals']['total_vat'] = $m[1];
        }
        if (preg_match('/Total\s*(?:With|Inc\.?)\s*VAT[^\d]*([\d,]+\.\d{2})/i', $t, $m)) {
            $out['totals']['total_with_vat'] = $m[1];
        }
        if (preg_match('/Discount[^\d]*([\d,]+\.\d{2})/i', $t, $m)) {
            $out['totals']['discount'] = $m[1];
        }

        // Bank (optional)
        if (preg_match('/Account\s*Name[\.:]?\s*([^\n]+)/i', $t, $m)) $out['bank']['account_name'] = trim($m[1]);
        if (preg_match('/Bank\s*Name[\.:]?\s*([^\n]+)/i', $t, $m)) $out['bank']['bank_name'] = trim($m[1]);
        if (preg_match('/Account\s*Number[\.:]?\s*([A-Za-z0-9\-]+)/i', $t, $m)) $out['bank']['account_number'] = trim($m[1]);
        if (preg_match('/IBAN[\s:.-]*([A-Za-z0-9]{10,})/i', $t, $m)) $out['bank']['iban'] = trim($m[1]);

        // Items (best effort from OCR text lines)
        $out['items'] = $this->extract_items_from_text($t);

        return $out;
    }

    private function extract_customer_name($text)
    {
        $patterns = [
            '/Buyer\s*(?:Name)?[\.:]?\s*(?:Name[\.:]?\s*)?([^\n]{3,60})/i',
            '/Customer\s*Name[\.:]?\s*([^\n]{3,60})/i',
            '/Bill\s*To[\.:]?\s*([^\n]{3,60})/i',
            '/Client[\.:]?\s*([^\n]{3,60})/i',
            '/Customer[\.:]?\s*([^\n]{3,60})/i',
            '/To[\.:]?\s*([^\n]{3,60})/i',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $text, $m)) {
                $name = trim($m[1]);
                $name = preg_replace('/\d{1,2}[\/\-\.]\d{1,2}[\/\-\.]\d{2,4}/', '', $name);
                $name = preg_replace('/Invoice.*/i', '', $name);
                $name = preg_replace('/[^a-zA-Z0-9\s\-&]/', '', $name);
                $name = trim(preg_replace('/\s+/', ' ', $name));
                if (strlen($name) >= 3 && preg_match('/[a-zA-Z]/', $name)) return $name;
            }
        }
        return '';
    }

    private function extract_items_from_text($text)
    {
        $items = [];
        $lines = preg_split("/\n+/", (string)$text);

        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '') continue;

            // skip headers/totals
            if (preg_match('/Item\s*Code|Description\s*Qty|Qty\s*Unit|Unit\s*Price|Subtotal|Total\s*With|Total\s*Without|VAT\s*Total/i', $line)) {
                continue;
            }

            // require at least 2 money values
            preg_match_all('/\d[\d,]*\.\d{2}/', $line, $m);
            $money = $m[0] ?? [];
            if (count($money) < 2) continue;

            // item code (flexible)
            $code = '';
            if (preg_match('/\b([A-Z]{1,6}[-_]?\d{2,10})\b/i', $line, $c)) $code = strtoupper($c[1]);

            // qty + unit
            $qty = '';
            $unit = '';
            if (preg_match('/\b(\d{1,6})\s*(BOX|PCS|PC|EA|UNIT|CTN|PACK)\b/i', $line, $q)) {
                $qty = $q[1];
                $unit = strtoupper($q[2]);
            }

            // description cleanup
            $desc = $line;
            if ($code) $desc = preg_replace('/\b'.preg_quote($code,'/').'\b/i', ' ', $desc);
            if ($qty) {
                if ($unit) $desc = preg_replace('/\b'.preg_quote($qty,'/').'\b\s*'.preg_quote($unit,'/').'\b/i', ' ', $desc);
                else $desc = preg_replace('/\b'.preg_quote($qty,'/').'\b/', ' ', $desc);
            }
            foreach ($money as $mv) $desc = preg_replace('/'.preg_quote($mv,'/').'/', ' ', $desc, 1);
            $desc = trim(preg_replace('/\s+/', ' ', $desc));
            if ($desc === '') $desc = 'ITEM';

            // map amounts
            $unit_price    = $money[0] ?? '';
            $without_vat   = $money[1] ?? '';
            $vat_amount    = (count($money) >= 3) ? $money[count($money)-2] : '';
            $total_inc_vat = end($money) ?: '';

            $items[] = [
                'item_code'     => $code,
                'description'   => $desc,
                'qty'           => $qty,
                'unit'          => $unit,
                'unit_price'    => $unit_price,
                'without_vat'   => $without_vat,
                'vat_amount'    => $vat_amount,
                'total_inc_vat' => $total_inc_vat,
                'raw_line'      => $line
            ];
        }

        return $items;
    }

    // ==========================================
    // Helpers used by run_ocr response
    // ==========================================
    private function date_to_ymd($d)
    {
        $ts = strtotime($d);
        return $ts ? date('Y-m-d', $ts) : '';
    }

    // ==========================================
    // DOC/DOCX -> PDF (LibreOffice)
    // ==========================================
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
  public function add_vendor()
{
    header('Content-Type: application/json; charset=utf-8');

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
        return;
    }

    $name = trim($this->input->post('name'));
    if ($name === '') {
        echo json_encode(['status' => 'error', 'message' => 'Vendor name is required']);
        return;
    }

    $data = [
        'name'         => $name,
        'profile_code' => trim($this->input->post('profile_code')) ?: null,
        'cr_no'        => trim($this->input->post('cr_no')) ?: null,
        'tax_id'       => $this->input->post('tax_id') ?: null,
        'currency_id'  => $this->input->post('currency_id') ?: null,
        'contact_no1'  => trim($this->input->post('contact_no1')) ?: null,
        'contact_no2'  => trim($this->input->post('contact_no2')) ?: null,
        'email'        => trim($this->input->post('email')) ?: null,
        'branch_id'    => $this->session->userdata('branch_id') ?: 1,
        'added_by'     => $this->session->userdata('user_id') ?: 0,
    ];

    $vendor_id = $this->ocr_model->create_vendor($data);

    if (!$vendor_id) {
        echo json_encode([
            'status'   => 'error',
            'message'  => 'Failed to save vendor',
            'db_error' => $this->db->error()
        ]);
        return;
    }

    echo json_encode([
        'status' => 'success',
        'message' => 'Vendor added successfully',
        'vendor' => [
            'id'           => $vendor_id,
            'name'         => $name,
            'profile_code' => $data['profile_code'],
            'currency_id'  => $data['currency_id'],
            'tax_id'       => $data['tax_id'],
        ]
    ]);
}
// ==========================================
// SAVE PURCHASE (OCR-confirmed data)
//    Inserts into tbl_purchase, tbl_purchase_ocr,
//    tbl_purchase_items using the hidden reference
// ==========================================
public function save_purchase_ocr()
{
    header('Content-Type: application/json; charset=utf-8');
    $transactionStarted = false;

    try {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
            return;
        }

        $referenceId = (int)$this->input->post('reference_id');
        $vendor_id = (int)$this->input->post('vendor_id');
        $customerId = (int)$this->input->post('customer_id');
        $items_json = $this->input->post('items_json');
        $items = json_decode($items_json, true);

        if ($referenceId <= 0) {
            echo json_encode(['status' => 'error', 'message' => 'Purchase reference is missing. Please start from Upload Purchase.']);
            return;
        }

        if ($vendor_id <= 0 || !$this->db->where('id', $vendor_id)->where('type', 2)->where('status', 0)->get('tbl_profile')->row_array()) {
            echo json_encode(['status' => 'error', 'message' => 'Please select a valid active vendor.']);
            return;
        }

        $referenceLog = $this->db
            ->where('id', $referenceId)
            ->where('type', 109)
            ->get('tbl_logs')
            ->row_array();
        if (!$referenceLog) {
            echo json_encode(['status' => 'error', 'message' => 'The purchase reference was not found. Please start from Upload Purchase again.']);
            return;
        }

        if (empty($items) || !is_array($items)) {
            echo json_encode(['status' => 'error', 'message' => 'Please add at least one item.']);
            return;
        }

        foreach ($items as $index => $item) {
            if (!is_array($item)) {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Invalid item data for line ' . ((int)$index + 1) . '.'
                ]);
                return;
            }
            $itemId = isset($item['item_id']) ? (int)$item['item_id'] : 0;
            if ($itemId <= 0 || !$this->db->where('id', $itemId)->get('tbl_items')->row_array()) {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Please select a valid item for line ' . ((int)$index + 1) . '.'
                ]);
                return;
            }
        }

        if ($this->db->where('reference', $referenceId)->get('tbl_purchase_pocr')->row_array()) {
            echo json_encode(['status' => 'error', 'message' => 'This purchase reference has already been saved.']);
            return;
        }

        if (!$this->db->trans_begin()) {
            throw new RuntimeException('Could not start purchase transaction.');
        }
        $transactionStarted = true;

        // Calculate totals
        $total = 0;
        $grandTotal = 0;
        $vatTotal = 0;
        $discountTotal = 0;

        // Process items first to calculate totals
        foreach ($items as &$item) {
            $qty = (float)($item['quantity'] ?? 1);
            $unitPrice = (float)($item['unit_price'] ?? 0);
            $vatRate = (float)($item['vat_rate'] ?? 0);
            $discount = (float)($item['discount'] ?? 0);
            $lineTotal = ($qty * $unitPrice) - $discount;
            $vatAmount = $lineTotal * ($vatRate / 100);
            $lineGrandTotal = $lineTotal + $vatAmount;

            $total += $lineTotal;
            $vatTotal += $vatAmount;
            $grandTotal += $lineGrandTotal;
            $discountTotal += $discount;
            
            // Store calculated values back to item
            $item['line_total'] = $lineTotal;
            $item['vat_amount'] = $vatAmount;
            $item['grand_total'] = $lineGrandTotal;
        }
        unset($item);

        // 2. Prepare purchase data for tbl_purchase_pocr
        $purchaseData = [
            'reference' => $referenceId,
            'entry_date' => date('Y-m-d'),
            'inv_no' => (int)($this->input->post('supplier_invoice_no') ?: 0),
            'inv_date' => $this->input->post('invoice_date') ?: null,
            'profile_id' => $vendor_id,
            'status' => 0
        ];
        if (!$this->db->insert('tbl_purchase_pocr', $purchaseData)) {
            throw new RuntimeException('Failed to create purchase record.');
        }
        $purchaseId = $this->db->insert_id();

        if (!$purchaseId || $this->db->trans_status() === false) {
            throw new RuntimeException('Failed to create purchase record.');
        }

        // 3. Save items to tbl_purchase_items
        foreach ($items as $index => $item) {
            $itemData = [
                'reference' => $referenceId,
                'invoice_id' => $purchaseId,
                'item_id' => $item['item_id'] ?? null,
                'item_code' => $item['item_code'] ?? '',
                'item_name' => $item['description'] ?? '',
                'description' => $item['description'] ?? '',
                'quantity' => (float)($item['quantity'] ?? 1),
                'unit_id' => $item['unit_id'] ?? null,
                'price' => (float)($item['unit_price'] ?? 0),
                'discount' => (float)($item['discount'] ?? 0),
                'vat_perc' => (float)($item['vat_rate'] ?? 0),
                'vat_amt' => (float)($item['vat_amount'] ?? 0),
                'total_price' => (float)($item['grand_total'] ?? 0),
                'status' => 2
            ];

            if (!$this->db->insert('tbl_purchase_items', $itemData)) {
                throw new RuntimeException('Failed to save purchase item ' . ((int)$index + 1) . '.');
            }
        }

        // Update the log created by Upload Purchase instead of creating a second reference.
        $this->db->where('id', $referenceId)->update('tbl_logs', [
            'profile_id' => $vendor_id,
            'status' => 1
        ]);
        if ($this->db->trans_status() === false || !$this->db->trans_commit()) {
            throw new RuntimeException('Failed to finish saving the purchase.');
        }
        $transactionStarted = false;

        // 5. Return success with redirect URL
        $redirectUrl = site_url('home/newpurchase?' . http_build_query([
            'ref' => $referenceId,
            'cust' => $customerId
        ]));
        
        echo json_encode([
            'status' => 'success',
            'message' => 'Purchase saved successfully',
            'log_id' => $referenceId,
            'purchase_id' => $purchaseId,
            'reference' => $referenceId,
            'grand_total' => $grandTotal,
            'total' => $total,
            'redirect_url' => $redirectUrl
        ]);

    } catch (Exception $e) {
        if ($transactionStarted) {
            $this->db->trans_rollback();
        }
        log_message('error', "save_purchase_ocr error: " . $e->getMessage());
        echo json_encode([
            'status' => 'error',
            'message' => $e->getMessage()
        ]);
    }
}

}