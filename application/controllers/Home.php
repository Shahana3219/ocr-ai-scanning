<?php
defined('BASEPATH') or exit('No direct script access allowed');
class Home extends CI_Controller {
		
	public $ci;
    public function __construct() {
        parent::__construct();
        // $this->load->database();
        $this->load->model([
            'ocr_model',
        ]);
		
        $this->load->library(['form_validation', 'session']);
        $this->load->helper(['url', 'form']);
		$this->load->helper('timesheet');
        $this->ci = &get_instance();

       
    }

	public function view_function($pageName, $rdata = '', $sdata = '', $ndata = '')
    {
        $sdata['bcdp'] = 2;
        $sdata = array();
        $ndata = array();
        $this->load->view('template/header', $sdata);
        $this->load->view('template/navbar', $ndata);
        $this->load->view($pageName, $rdata);
        $this->load->view('template/footer', $rdata);
        $this->load->view('template/script', $rdata);
        $this->load->view('template/last', $rdata);
    }
    
    public function index() {

		$rdata = array();
        $sdata = array();
        $ndata = array();
        $rdata['pagetitle'] = '';
		print_r('ss');die;
		// print_r($rdata);die;	
        $this->view_function('', $rdata, $sdata, $ndata);
    }
    public function invoice_form() 
    {
        $this->load->view('invoice_form');
    }
    public function upload_invoice_ocr()
{
    // Always return JSON
    header('Content-Type: application/json; charset=utf-8');

    // Only POST
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        echo json_encode([
            'status'  => 'error',
            'message' => 'Invalid request method'
        ]);
        return;
    }

    // Check file exists
    if (empty($_FILES['invoice_file']['name'])) {
        echo json_encode([
            'status'  => 'error',
            'message' => 'No file selected'
        ]);
        return;
    }

    // ---------- Server-side validation (MIME + size) ----------
    $allowed_mime = [
        'image/png',
        'image/jpeg',
        'application/pdf',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
    ];

    $tmp  = $_FILES['invoice_file']['tmp_name'];
    $mime = function_exists('mime_content_type') ? mime_content_type($tmp) : ($_FILES['invoice_file']['type'] ?? '');
    $size = (int) ($_FILES['invoice_file']['size'] ?? 0);

    if (!in_array($mime, $allowed_mime)) {
        echo json_encode([
            'status'  => 'error',
            'message' => 'Invalid file type'
        ]);
        return;
    }

    if ($size > 5 * 1024 * 1024) {
        echo json_encode([
            'status'  => 'error',
            'message' => 'File must be below 5MB'
        ]);
        return;
    }

    // ---------- Create folders ----------
    $invoice_id = uniqid('inv_');

    // Use your required folder: assets/uploads
    $basePath       = FCPATH . 'assets/uploads/invoices/' . $invoice_id . '/';
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

    // ---------- Upload using CI Upload library ----------
    $config['upload_path']   = $originalPath;
    $config['allowed_types'] = 'jpg|jpeg|png|pdf|doc|docx';
    $config['max_size']      = 5120; // 5MB in KB
    $config['encrypt_name']  = true;

    $this->load->library('upload', $config);

    if (!$this->upload->do_upload('invoice_file')) {
        echo json_encode([
            'status'  => 'error',
            'message' => $this->upload->display_errors('', '')
        ]);
        return;
    }

    $up = $this->upload->data();
    $fullPath = $up['full_path'];       // absolute server path
    $fileName = $up['file_name'];
    $ext      = strtolower($up['file_ext']); // ".pdf", ".png"

    // ---------- Day 3: Normalization ----------
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
            // DOC/DOCX later
            echo json_encode([
                'status'  => 'error',
                'message' => 'DOC/DOCX normalization not implemented yet. Upload PDF/Image for now.'
            ]);
            return;
        }

        // ---------- Prefill (dummy now; later replace with OCR JSON) ----------
        $prefill = [
            'invoice_id'     => $invoice_id,
            'invoice_date'   => date('Y-m-d'),
            'invoice_time'   => date('H:i'),
            'due_date'       => date('Y-m-d'),
            'order_no'       => '',
            'reference_no'   => '',
            'employee'       => '',
            'subject'        => '',
            'invoice_no'     => '',
            'customer_code'  => '',
            'customer_name'  => '',
            'vat'            => '',
            'address'        => '',
            'currency'       => '',
            'conversion_rate'=> '',
        ];

        // Build web-preview URLs for normalized images (optional)
        $webBase = base_url('assets/uploads/invoices/' . $invoice_id . '/normalized/');
        $previewUrls = [];
        for ($i=1; $i<=count($normalizedFiles); $i++) {
            $previewUrls[] = $webBase . 'page_' . $i . '.png';
        }

        echo json_encode([
            'status'          => 'success',
            'message'         => 'Uploaded & normalized successfully',
            'prefill'         => $prefill,
            'uploaded_file'   => $fileName,
            'normalized_pages'=> count($normalizedFiles),
            'preview_urls'    => $previewUrls
        ]);
        return;

    } catch (Exception $e) {
        echo json_encode([
            'status'  => 'error',
            'message' => 'Normalization failed: ' . $e->getMessage()
        ]);
        return;
    }
}

/**
 * PDF -> PNG pages using Imagick (300 DPI)
 * Returns an array of absolute file paths of created images.
 */


private function pdf_to_png_pdftoppm($pdfPath, $outputDir)
{
    if (!is_dir($outputDir) && !mkdir($outputDir, 0777, true)) {
        throw new Exception('Cannot create output directory: ' . $outputDir);
    }

    // pdftoppm output uses page-1.png, page-2.png
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

    // Rename to page_1.png, page_2.png ...
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
    // Best: Poppler if available (cloud)
    if ($this->command_exists('pdftoppm')) {
        return $this->pdf_to_png_pdftoppm($pdfPath, $outputDir);
    }

    // Windows Ghostscript exe name
    if ($this->command_exists('gswin64c')) {
        return $this->pdf_to_png_imagick($pdfPath, $outputDir);
    }

    // Linux/mac Ghostscript
    if ($this->command_exists('gs')) {
        return $this->pdf_to_png_imagick($pdfPath, $outputDir);
    }

    throw new Exception('No PDF renderer available. Install poppler (pdftoppm) or ghostscript (gs/gswin64c).');
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


}






