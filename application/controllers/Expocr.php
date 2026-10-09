<?php
defined('BASEPATH') OR exit('No direct script access allowed');

// Expense-voucher OCR flow. Upload + OCR reuse Pocr/upload_invoice_ocr and
// Pocr/run_ocr (same Python service); this controller owns the expense page
// and posts the reviewed voucher through Expocr_model.
class Expocr extends CI_Controller
{
    // Attachments are stored here; tbl_documents.image keeps only the file name.
    private $upload_dir = 'uploads/expense/';

    // Python OCR service (schemas/expense_voucher.py). Local dev instance;
    // switch to the EC2 URL used by Pocr when deploying.
    private $ocr_api_url = 'http://localhost:8001/ocr/process';

    public function __construct()
    {
        parent::__construct();

        $this->load->model('expocr_model');
        $this->load->library(['session']);
        $this->load->helper(['url', 'form']);
    }

    public function expocr()
    {
        $data['customers']   = $this->expocr_model->get_profiles(0);
        $data['vendors']     = $this->expocr_model->get_profiles(2);
        $data['projects']    = $this->expocr_model->get_projects();
        $data['accounts']    = $this->expocr_model->get_accounts();
        $data['costcenters'] = $this->expocr_model->get_cost_centers();
        $data['jobs']        = $this->expocr_model->get_jobs();
        $data['branches']    = $this->expocr_model->get_branches();
        $data['session_branch_id'] = (int)($this->session->userdata('branch_id') ?: 1);

        $data['prefill_ref'] = $this->input->get('ref') ?: '';

        $data['bn2']  = 'Home';
        $data['bnh1'] = 'home/index';
        $data['bn3']  = 'Expense';

        $this->load->view('template/header', $data);
        $this->load->view('template/navbar', $data);
        $this->load->view('expocr/expocr', $data);
        $this->load->view('template/footer');
        $this->load->view('template/last');
    }

    // Creates the tbl_logs reference used by the whole flow (type 109).
    public function generate_reference($type = 109)
    {
        header('Content-Type: application/json; charset=utf-8');

        $this->db->insert('tbl_logs', [
            'type'       => (int)$type,
            'profile_id' => 0,
            'ip_address' => $this->input->ip_address(),
            'date_time'  => date('Y-m-d H:i:s'),
            'status'     => 0,
        ]);
        $reference = $this->db->insert_id();

        if (!$reference) {
            return $this->fail('Failed to generate reference');
        }
        echo json_encode(['status' => 'success', 'message' => 'Reference generated', 'data' => ['reference' => $reference]]);
    }

    public function get_account_balance()
    {
        header('Content-Type: application/json; charset=utf-8');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return $this->fail('Invalid request method');
        }

        $accountId = (int)$this->input->post('account_id');
        if ($accountId <= 0) {
            return $this->fail('Please select a valid mode of payment.');
        }

        try {
            $account = $this->expocr_model->get_account($accountId);
            if (!$account || (int)($account['status'] ?? -1) !== 0) {
                return $this->fail('The selected mode of payment is not an active account.');
            }

            echo json_encode([
                'status'  => 'success',
                'balance' => $this->expocr_model->get_account_balance($accountId),
            ]);
        } catch (Throwable $e) {
            log_message('error', 'get_account_balance error: ' . $e->getMessage());
            return $this->fail('Could not retrieve the account balance.');
        }
    }

    // ==========================================
    // UPLOAD (validate + read file for OCR)
    // ==========================================
    public function upload_invoice_ocr()
    {
        header('Content-Type: application/json; charset=utf-8');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return $this->fail('Invalid request method');
        }
        if (empty($_FILES['invoice_file']['name'])) {
            return $this->fail('No file selected');
        }

        $allowed = ['png', 'jpg', 'jpeg', 'pdf'];
        $tmp  = $_FILES['invoice_file']['tmp_name'];
        $ext  = strtolower(pathinfo($_FILES['invoice_file']['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, $allowed, true)) {
            return $this->fail('Invalid file type. Allowed: ' . implode(', ', $allowed));
        }
        if ((int)$_FILES['invoice_file']['size'] > 5 * 1024 * 1024) {
            return $this->fail('File must be below 5MB');
        }

        $mime = function_exists('mime_content_type') ? mime_content_type($tmp) : $_FILES['invoice_file']['type'];
        echo json_encode([
            'status'    => 'success',
            'file_data' => base64_encode(file_get_contents($tmp)),
            'file_name' => $_FILES['invoice_file']['name'],
            'file_ext'  => $ext,
            'mime_type' => $mime,
        ]);
    }

    // ==========================================
    // RUN OCR (doc_type=expense_voucher) + map to form ids
    // ==========================================
    public function run_ocr()
    {
        set_time_limit(0);
        ini_set('memory_limit', '1024M');
        header('Content-Type: application/json; charset=utf-8');

        $tempFile = null;
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                return $this->fail('Invalid request method');
            }
            $fileData = $this->input->post('file_data');
            $fileName = (string)$this->input->post('file_name');
            if (empty($fileData)) {
                return $this->fail('file_data is required');
            }

            $tempFile = tempnam(sys_get_temp_dir(), 'exocr_');
            file_put_contents($tempFile, base64_decode($fileData));

            $ext  = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
            $mime = ['pdf' => 'application/pdf', 'png' => 'image/png', 'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg'][$ext]
                ?? (string)$this->input->post('mime_type');

            $ch = curl_init($this->ocr_api_url);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST           => true,
                CURLOPT_POSTFIELDS     => [
                    'file'     => new CURLFile($tempFile, $mime, $fileName),
                    'doc_type' => 'expense_voucher',
                ],
                CURLOPT_ENCODING       => '',
                CURLOPT_TIMEOUT        => 2290,
            ]);
            $resp = curl_exec($ch);
            $http = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $err  = curl_error($ch);
            curl_close($ch);

            if ($resp === false || $http !== 200) {
                return $this->fail('OCR failed: ' . ($err !== '' ? $err : substr((string)$resp, 0, 300)));
            }
            $py = json_decode($resp, true);
            if (!is_array($py) || empty($py['success'])) {
                return $this->fail('Invalid OCR response');
            }

            $data   = $py['data'] ?? [];
            $fields = $data['fields'] ?? [];
            $warning = '';
            if (empty($fields)) {
                $warning = 'The OCR could not read this document (' . ($data['flag'] ?? 'no fields') . '). Please fill the details manually.';
            } elseif (!empty($data['flag'])) {
                $warning = 'Low OCR confidence. Please check every field carefully.';
            }

            $m        = $this->expocr_model;
            $accounts = $m->get_accounts();
            $ccs      = $m->get_cost_centers();
            $projects = $m->get_projects();
            $jobs     = $m->get_jobs();

            // Profile: filter "All"/blank -> look in vendors and customers.
            $isPlaceholder = function ($s) {
                return (bool)preg_match('/^\s*-*\s*(select\b.*|all|none|0)?\s*-*\s*$/i', (string)$s);
            };
            $profileName = trim((string)($fields['profile'] ?? ''));
            if ($isPlaceholder($profileName)) {
                $profileName = '';
            }
            $payTo = trim((string)($fields['payment_to'] ?? ''));
            if ($isPlaceholder($payTo)) {
                $payTo = '';
            }
            $profileName = $profileName ?: $payTo;
            $filter = strtolower((string)($fields['customer_filter'] ?? ''));
            $vendorMatches   = (strpos($filter, 'customer') === false) ? $m->match_profiles($profileName, 2) : [];
            $customerMatches = (strpos($filter, 'vendor') === false)   ? $m->match_profiles($profileName, 0) : [];

            $lines = [];
            $singleLine = count($fields['line_items'] ?? []) === 1;
            foreach (($fields['line_items'] ?? []) as $li) {
                if (!is_array($li)) {
                    continue;
                }
                $bill   = trim((string)($li['bill_number'] ?? ''));
                $amount = $this->to_number($li['amount'] ?? '');
                $perc   = $this->to_number($li['tax_rate'] ?? '');
                $taxAmt = $this->to_number($li['tax_amount'] ?? '');
                $total  = $this->to_number($li['total_amount'] ?? '');

                // Repair column shifts: find the assignment of the printed numbers
                // that satisfies tax = amount*rate/100 and total = amount+tax.
                $fix = $this->fix_line_numbers(
                    [$bill, $amount, $perc, $taxAmt, $total, $singleLine ? $this->to_number($fields['total_amount'] ?? '') : ''],
                    $amount, $perc, $taxAmt, $total
                );
                if ($fix) {
                    $bill = $fix['bill'] !== '' ? $fix['bill'] : $bill;
                    $amount = $fix['amount'];
                    $perc = $fix['perc'];
                }
                if ($bill !== '' && is_numeric($bill) && (float)$bill == 0) {
                    $bill = '';
                }
                $lines[] = [
                    'description'    => (string)($li['description'] ?? ''),
                    'category_id'    => $m->best_match($li['category'] ?? '', $accounts, 55),
                    'cost_center_id' => $m->best_match($li['cost_center'] ?? '', $ccs, 55),
                    'item_date'      => $this->to_ymd($li['expense_date'] ?? ''),
                    'bill_number'    => $bill,
                    'amount'         => $amount,
                    'tax_perc'       => $perc,
                ];
            }

            $payload = [
                'status'  => 'success',
                'message' => 'OCR completed successfully',
                'warning' => $warning,
                'vendor_matches'   => $vendorMatches,
                'customer_matches' => $customerMatches,
                'prefill' => [
                    'expense_date' => $this->to_ymd($fields['voucher_date'] ?? '') ?: date('Y-m-d'),
                    'branch_id'    => $m->best_match($fields['branch_name'] ?? '', $m->get_branches(), 60),
                    'profile_name' => $profileName,
                    'filter'       => strpos($filter, 'customer') !== false ? '0' : '2',
                    'mop_id'       => $m->best_match($fields['payment_mode'] ?? '', $accounts),
                    'project_id'   => $m->best_match($fields['project'] ?? '', $projects),
                    'job_id'       => $m->best_match($fields['job'] ?? '', $jobs),
                    'note'         => (string)($fields['note'] ?? ''),
                    'bill_number'  => (string)($fields['bill_number'] ?? $fields['invoice_number'] ?? $fields['bill_no'] ?? ''),
                    'total_amount' => $this->to_number($fields['total_amount'] ?? ''),
                ],
                'parsed' => ['line_items' => $lines],
            ];

            echo json_encode($this->force_utf8($payload), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PARTIAL_OUTPUT_ON_ERROR);
        } catch (Throwable $e) {
            $this->fail('OCR crashed: ' . $e->getMessage());
        } finally {
            if ($tempFile) {
                @unlink($tempFile);
            }
        }
    }

    private function force_utf8($v)
    {
        if (is_array($v)) {
            return array_map([$this, 'force_utf8'], $v);
        }
        return is_string($v) ? mb_convert_encoding($v, 'UTF-8', 'UTF-8') : $v;
    }

    // Returns ['bill','amount','perc'] when the numbers are consistent (or can be re-aligned), else null.
    private function fix_line_numbers(array $tokens, $amount, $perc, $taxAmt, $total)
    {
        $ok = function ($a, $r, $t, $T) {
            return $a > 0 && abs($a * $r / 100 - $t) <= 0.02 && abs($a + $t - $T) <= 0.02;
        };
        if ($ok((float)$amount, (float)$perc, (float)$taxAmt, (float)$total)) {
            return null; // already consistent
        }
        $vals = [];
        foreach ($tokens as $t) {
            if ($t !== '' && is_numeric($t)) {
                $vals[] = (string)$t;
            }
        }
        $vals = array_values(array_unique($vals));
        $n = count($vals);
        for ($ia = 0; $ia < $n; $ia++) {
            for ($ir = 0; $ir < $n; $ir++) {
                for ($it = 0; $it < $n; $it++) {
                    for ($iT = 0; $iT < $n; $iT++) {
                        if (count(array_unique([$ia, $ir, $it, $iT])) < 4) {
                            continue;
                        }
                        if ($ok((float)$vals[$ia], (float)$vals[$ir], (float)$vals[$it], (float)$vals[$iT])) {
                            $bill = '';
                            foreach ($vals as $i => $v) {
                                if (!in_array($i, [$ia, $ir, $it, $iT], true) && (float)$v != 0) {
                                    $bill = $v;
                                }
                            }
                            return ['bill' => $bill, 'amount' => $vals[$ia], 'perc' => $vals[$ir]];
                        }
                    }
                }
            }
        }
        return null;
    }

    private function to_number($v)
    {
        return preg_replace('/[^\d.\-]/', '', str_replace(',', '', (string)$v));
    }

    private function to_ymd($d)
    {
        $d = trim((string)$d);
        if ($d === '') {
            return '';
        }
        foreach (['d-m-Y', 'd/m/Y', 'd.m.Y', 'd-M-Y', 'Y-m-d', 'Y/m/d', 'd M Y'] as $fmt) {
            $dt = DateTime::createFromFormat('!' . $fmt, $d);
            if ($dt && $dt->format($fmt) === $d) {
                return $dt->format('Y-m-d');
            }
        }
        $ts = strtotime($d);
        return $ts ? date('Y-m-d', $ts) : '';
    }

    private function fail($message)
    {
        echo json_encode(['status' => 'error', 'message' => $message]);
    }

    // Saves the reviewed expense voucher against the reference created by
    // Pocr/generate_reference/109.
    public function save_expense_ocr()
    {
        header('Content-Type: application/json; charset=utf-8');
        $transactionStarted = false;
        $savedFile = null;

        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                return $this->fail('Invalid request method');
            }

            $referenceId = (int)$this->input->post('reference_id');
            $filterType  = (int)$this->input->post('filter_type') === 0 ? 0 : 2;
            $profileId   = (int)$this->input->post('profile_id');
            $mopId       = (int)$this->input->post('mode_of_payment');
            $items       = json_decode((string)$this->input->post('items_json', false), true);

            if ($referenceId <= 0) {
                return $this->fail('Expense reference is missing. Please start from Upload Expense.');
            }
            if (!$this->db->where('id', $referenceId)->where('type', 109)->get('tbl_logs')->row_array()) {
                return $this->fail('The expense reference was not found. Please start again.');
            }
            if ($this->expocr_model->reference_saved($referenceId)) {
                return $this->fail('This expense reference has already been saved.');
            }
            $paymentTo = trim((string)$this->input->post('payment_to'));
            if ($profileId > 0) {
                if (!$this->db->where('id', $profileId)->where('type', $filterType)->where('status', 0)->get('tbl_profile')->row_array()) {
                    return $this->fail('Please select a valid active profile.');
                }
                $paymentTo = '';
            } elseif ($paymentTo === '') {
                return $this->fail('Please select a profile or enter Payment To.');
            }
            if ($mopId <= 0 || !$this->expocr_model->get_account($mopId)) {
                return $this->fail('Please select a mode of payment.');
            }
            if (empty($items) || !is_array($items)) {
                return $this->fail('Please add at least one expense line.');
            }

            $lines = [];
            foreach ($items as $i => $it) {
                $n = $i + 1;
                if (!is_array($it) || (int)($it['category_id'] ?? 0) <= 0) {
                    return $this->fail("Please select a category for line $n.");
                }
                if (!isset($it['amount']) || !is_numeric($it['amount']) || (float)$it['amount'] <= 0) {
                    return $this->fail("Amount must be greater than 0 for line $n.");
                }
                $lines[] = [
                    'category_id'    => (int)$it['category_id'],
                    'cost_center_id' => (int)($it['cost_center_id'] ?? 0),
                    'description'    => mb_substr((string)($it['description'] ?? ''), 0, 240),
                    'item_date'      => !empty($it['item_date']) ? $it['item_date'] : null,
                    'bill_number'    => mb_substr((string)($it['bill_number'] ?? ''), 0, 250),
                    'taxable'        => round((float)$it['amount'], 2),
                    'vat_perc'       => (float)($it['tax_perc'] ?? 0),
                ];
            }

            // Optional attachment (the OCR'd document).
            $doc = null;
            if (!empty($_FILES['document']['name']) && is_uploaded_file($_FILES['document']['tmp_name'])) {
                $ext = strtolower(pathinfo($_FILES['document']['name'], PATHINFO_EXTENSION));
                if (!in_array($ext, ['png', 'jpg', 'jpeg', 'pdf'], true) || $_FILES['document']['size'] > 5 * 1024 * 1024) {
                    return $this->fail('Attachment must be a PNG, JPG or PDF below 5 MB.');
                }
                $dir = FCPATH . $this->upload_dir;
                if (!is_dir($dir) && !mkdir($dir, 0755, true)) {
                    return $this->fail('Could not create the upload folder.');
                }
                $branchId = (int)($this->session->userdata('branch_id') ?: 1);
                $name = $branchId . '_' . $referenceId . '_' . md5(uniqid('', true)) . '.' . $ext;
                if (!move_uploaded_file($_FILES['document']['tmp_name'], $dir . $name)) {
                    return $this->fail('Could not save the attachment.');
                }
                $savedFile = $dir . $name;
                $doc = ['image' => $name];
            }

            $branchId = (int)$this->input->post('branch_id');
            if ($branchId <= 0 || !$this->db->where('id', $branchId)->get('tbl_branch_profile')->row_array()) {
                $branchId = (int)($this->session->userdata('branch_id') ?: 1);
            }

            $header = [
                'reference'  => $referenceId,
                'date'       => $this->input->post('expense_date') ?: date('Y-m-d'),
                'branch_id'  => $branchId,
                'project_id' => (int)$this->input->post('project_id'),
                'profile_id' => $profileId,
                'payment_to' => mb_substr($paymentTo, 0, 250),
                'mop_id'     => $mopId,
                'job_id'     => (int)$this->input->post('job_id'),
                'note'       => (string)$this->input->post('note'),
                'user_id'    => (int)($this->session->userdata('user_id') ?: 1),
                'ip'         => $this->input->ip_address(),
            ];

            $this->db->trans_begin();
            $transactionStarted = true;

            $res = $this->expocr_model->post_voucher($header, $lines, $doc);
            $this->db->where('id', $referenceId)->update('tbl_logs', ['profile_id' => $profileId, 'status' => 1]);

            if ($this->db->trans_status() === false || !$this->db->trans_commit()) {
                throw new RuntimeException('Failed to finish saving the expense.');
            }
            $transactionStarted = false;

            echo json_encode([
                'status'       => 'success',
                'message'      => 'Expense voucher ' . $res['voucher_no'] . ' saved successfully',
                'reference'    => $referenceId,
                'voucher_no'   => $res['voucher_no'],
                'grand_total'  => $res['total'],
                'redirect_url' => site_url('home/expense_form'),
            ]);
        } catch (Throwable $e) {
            if ($transactionStarted) {
                $this->db->trans_rollback();
            }
            if ($savedFile && is_file($savedFile)) {
                @unlink($savedFile);
            }
            log_message('error', 'save_expense_ocr error: ' . $e->getMessage());
            $this->fail($e->getMessage());
        }
    }
}
