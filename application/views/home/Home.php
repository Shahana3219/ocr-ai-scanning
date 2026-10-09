<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Home extends CI_Controller
{
    // Remote Python OCR (PaddleOCR-VL + Claude Bedrock validator) service.
    // Update this if the EC2 instance / IP changes.
    //this is 30 s resul goving m7i-flex-large
    //  private $ocr_api_url = 'http://13.233.24.12:8001/ocr/process';
     private $ocr_api_url = 'http://65.2.161.212:8001/ocr/process';

     //private $ocr_api_url = 'http://13.235.75.2:5000/ocr/process';
    public function __construct()
    {
        parent::__construct();

        $this->load->model('ocr_model');
        $this->load->library(['form_validation', 'session', 'upload']);
        $this->load->helper(['url', 'form']);
    }
public function index()
{
    // Minimal invoice page: show upload button only that redirects to POCR flow
    $data = [];
    $this->load->view('template/header', $data);
    $this->load->view('template/navbar', $data);
    $this->load->view('home/upload_only', $data);
    $this->load->view('template/footer');
    $this->load->view('template/last');
}
public function invoice_form()
{
    // Minimal invoice page: show upload button only that redirects to POCR flow
    $data = [];
    $this->load->view('template/header', $data);
    $this->load->view('template/navbar', $data);
    $this->load->view('home/upload_only', $data);
    $this->load->view('template/footer');
    $this->load->view('template/last');
}
public function rbac()
{
    // Minimal invoice page: show upload button only that redirects to POCR flow
    $data = [];
    $this->load->view('template/header', $data);
    $this->load->view('template/navbar', $data);
    $this->load->view('rbac/rbac', $data);
    $this->load->view('template/footer');
    $this->load->view('template/last');
}
  
}