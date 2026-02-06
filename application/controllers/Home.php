<?php
defined('BASEPATH') OR exit('No direct script access allowed');
define('PY_OCR_ROOT', 'C:/Users/shaha/Downloads/ocr-api-main/');

class Home extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('ocr_model');
        $this->load->library(['upload','session']);
        $this->load->helper(['url','form']);
    }

    public function invoice_form()
    {
        $this->load->view('invoice_form');
    }

    // ======================================================
    // 1) UPLOAD + NORMALIZE + INSERT DOCUMENT + PAGES
    // ======================================================
    public function upload_invoice_ocr()
    {
        header('Content-Type: application/json');

        if (empty($_FILES['invoice_file']['name'])) {
            echo json_encode(['status'=>'error','message'=>'No file selected']);
            return;
        }

        $folderKey = uniqid('inv_');
        $basePath  = FCPATH.'assets/uploads/invoices/'.$folderKey.'/';
        $origPath  = $basePath.'original/';
        $normPath  = $basePath.'normalized/';

        @mkdir($origPath,0777,true);
        @mkdir($normPath,0777,true);

        $config = [
            'upload_path'   => $origPath,
            'allowed_types' => 'png|jpg|jpeg|pdf|doc|docx',
            'max_size'      => 5120,
            'encrypt_name'  => true
        ];
        $this->upload->initialize($config);

        if (!$this->upload->do_upload('invoice_file')) {
            echo json_encode(['status'=>'error','message'=>$this->upload->display_errors()]);
            return;
        }

        $up = $this->upload->data();
        $fullPath = $up['full_path'];
        $ext = strtolower($up['file_ext']);

        // normalize → PNG pages
        if ($ext === '.pdf') {
            $pages = $this->normalize_pdf_to_images($fullPath,$normPath);
        } else {
            $dst = $normPath.'page_1.png';
            copy($fullPath,$dst);
            $pages = [$dst];
        }

        // insert document
        $docId = $this->ocr_model->create_document([
            'original_file_path'=>str_replace(FCPATH,'',$fullPath),
            'original_file_name'=>$up['file_name'],
            'file_ext'=>ltrim($ext,'.'),
            'mime_type'=>$up['file_type'],
            'file_size'=>$up['file_size'],
            'page_count'=>count($pages),
            'status'=>'converted',
            'created_at'=>date('Y-m-d H:i:s'),
            'updated_at'=>date('Y-m-d H:i:s'),
        ]);

        // insert pages
        $rows=[];
        $i=1;
        foreach($pages as $p){
            $rows[]=[
                'document_id'=>$docId,
                'page_no'=>$i++,
                'image_path'=>str_replace(FCPATH,'',$p),
                'ocr_text'=>null,
                'ocr_confidence'=>null,
                'created_at'=>date('Y-m-d H:i:s'),
                'updated_at'=>date('Y-m-d H:i:s'),
            ];
        }
        $this->ocr_model->create_pages_batch($rows);

        echo json_encode([
            'status'=>'success',
            'document_id'=>$docId
        ]);
    }

    // ======================================================
    // 2) CALL PYTHON OCR → READ JSON/TXT → SAVE DB
    // ======================================================
   public function run_ocr()
{
    set_time_limit(0);
    ini_set('max_execution_time', 0);
    header('Content-Type: application/json');

    $document_id = (int)$this->input->post('document_id');
    if (!$document_id) {
        echo json_encode(['status'=>'error','message'=>'document_id required']);
        return;
    }

    $pages = $this->ocr_model->get_pages($document_id);
    if (empty($pages)) {
        echo json_encode(['status'=>'error','message'=>'No pages found']);
        return;
    }

    // === SEND FIRST PAGE IMAGE TO PYTHON ===
    $imgAbs = realpath(FCPATH . ltrim($pages[0]['image_path'], '/'));
    if (!$imgAbs || !file_exists($imgAbs)) {
        echo json_encode(['status'=>'error','message'=>'Image not found','path'=>$imgAbs]);
        return;
    }

    $cfile = new CURLFile(
        $imgAbs,
        mime_content_type($imgAbs),
        basename($imgAbs)
    );

    $postFields = [];
    $postFields = [
    'files' => $cfile
];


    $ch = curl_init('http://127.0.0.1:8000/process-documents');
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $postFields,
        CURLOPT_RETURNTRANSFER => true,
       CURLOPT_TIMEOUT => 600,        // 10 minutes
        CURLOPT_CONNECTTIMEOUT => 60,

    ]);

    $resp = curl_exec($ch);
    $http = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err  = curl_error($ch);
    curl_close($ch);

    if ($http !== 200) {
        echo json_encode([
            'status' => 'error',
            'message' => "Python OCR HTTP {$http}",
            'curl_error' => $err,
            'raw' => $resp
        ]);
        return;
    }

    // ✅ DECODE RESPONSE (YOU WERE MISSING THIS)
    $py = json_decode($resp, true);
    if (!is_array($py) || empty($py['batch_id'])) {
        echo json_encode([
            'status'=>'error',
            'message'=>'Invalid Python OCR response',
            'raw'=>$resp
        ]);
        return;
    }

    // === READ PYTHON OUTPUT FILES ===
    $batchId = $py['batch_id'];
    $batchJson = PY_OCR_ROOT . 'processed_documents/Batch_Details_' . $batchId . '.json';


    if (!file_exists($batchJson)) {
        echo json_encode([
            'status'=>'error',
            'message'=>'Batch JSON not found',
            'path'=>$batchJson
        ]);
        return;
    }

    $batch = json_decode(file_get_contents($batchJson), true);

    $allText = '';
    $pageNo = 1;

    foreach ($batch as $b) {
        $txtPath = PY_OCR_ROOT . $b['text_file_path'];
        $text = file_exists($txtPath) ? file_get_contents($txtPath) : '';
        $conf = (float)($b['overall_confidence'] ?? 0);

        // ✅ SAVE OCR TO DB
        $this->ocr_model->update_page_ocr(
            $document_id,
            $pageNo,
            $text,
            $conf
        );

        $allText .= $text . "\n\n";
        $pageNo++;
    }

    echo json_encode([
        'status'=>'success',
        'message'=>'OCR completed',
        'document_id'=>$document_id,
        'text_length'=>strlen($allText)
    ]);
}


    // ======================================================
    // PARSER (UNCHANGED)
    // ======================================================
    private function parse_invoice_comprehensive($text)
    {
        return [
            'invoice_no'=>'',
            'invoice_date'=>'',
            'customer_name'=>'',
            'items'=>[]
        ];
    }

    // ======================================================
    // PDF → PNG HELPERS (UNCHANGED)
    // ======================================================
    private function normalize_pdf_to_images($pdf,$out)
    {
        exec("pdftoppm -png -r 300 ".escapeshellarg($pdf)." ".escapeshellarg($out.'/page'));
        $files = glob($out.'/page*.png');
        natsort($files);
        $outFiles=[];
        $i=1;
        foreach($files as $f){
            $n=$out.'/page_'.$i++.'.png';
            rename($f,$n);
            $outFiles[]=$n;
        }
        return $outFiles;
    }
}
