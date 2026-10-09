<?php
defined('BASEPATH') OR exit('No direct script access allowed');

// Expense voucher OCR: dropdown data + posting the voucher into
// tbl_transactions_main / tbl_transactions_sub / tbl_incomeandexpense / tbl_documents.
class Expocr_model extends CI_Model
{
    const TRANS_REF_EXPENSE = 17;   // tbl_transactions_main.trans_ref for expense entry
    const TRANS_TYPE_PAYMENT = 1;   // tbl_transactions_main.type
    const DOC_TYPE_EXPENSE = 51;    // tbl_documents.type for expense attachments
    const VAT_INPUT_ACC_FALLBACK = 19;

    public function __construct()
    {
        parent::__construct();
        $this->load->database();
    }

    // ---------- dropdown data ----------
    // Category and Mode of Payment both post to tbl_account (acc_id / mop_id).
    public function get_accounts()
    {
        return $this->db->select('id, name')->where('status', 0)
            ->order_by('name')->get('tbl_account')->result_array();
    }

    public function get_cost_centers()
    {
        return $this->db->select('id, name')->where('type', 2)->where('status', 0)
            ->order_by('name')->get('tbl_costcenter')->result_array();
    }

    public function get_jobs()
    {
        return $this->db->select('id, job_no AS name')->where('status', 0)
            ->order_by('job_no')->get('tbl_logistics_job')->result_array();
    }

    public function get_profiles($type)
    {
        return $this->db->where('type', (int)$type)->where('status', 0)->get('tbl_profile')->result_array();
    }

    public function get_branches()
    {
        return $this->db->select('id, name')->where('status', 0)->order_by('name')->get('tbl_branch_profile')->result_array();
    }

    public function get_projects()
    {
        return $this->db->where('status', 0)->get('tbl_project')->result_array();
    }

    // ---------- OCR name matching ----------
    private function normalize_name($name)
    {
        $name = mb_strtolower(trim((string)$name), 'UTF-8');
        $name = preg_replace('/[^\p{L}\p{N}]+/u', ' ', $name);
        return trim(preg_replace('/\s+/u', ' ', $name));
    }

    private function name_score($a, $b)
    {
        if ($a === '' || $b === '') {
            return 0;
        }
        similar_text($a, $b, $similar);
        $wa = preg_split('/\s+/', $a, -1, PREG_SPLIT_NO_EMPTY);
        $wb = preg_split('/\s+/', $b, -1, PREG_SPLIT_NO_EMPTY);
        sort($wa);
        sort($wb);
        similar_text(implode(' ', $wa), implode(' ', $wb), $wordOrder);
        if (mb_strpos($a, $b, 0, 'UTF-8') !== false || mb_strpos($b, $a, 0, 'UTF-8') !== false) {
            $similar = max($similar, 90);
        }
        return max($similar, $wordOrder);
    }

    // Vendors (type 2) or customers (type 0) ranked by similarity to an OCR name.
    public function match_profiles($ocrName, $type, $limit = 20)
    {
        $ocr = $this->normalize_name($ocrName);
        if ($ocr === '') {
            return [];
        }
        $rows = $this->db->select('id, name, profile_code, currency_id, tax_id')
            ->where('type', (int)$type)->where('status', 0)->get('tbl_profile')->result_array();
        foreach ($rows as &$r) {
            $r['_score'] = $this->name_score($ocr, $this->normalize_name($r['name']));
        }
        unset($r);
        usort($rows, function ($x, $y) { return $y['_score'] <=> $x['_score']; });
        $rows = array_filter($rows, function ($r) { return $r['_score'] >= 30; });
        $rows = array_slice(array_values($rows), 0, $limit);
        foreach ($rows as &$r) {
            unset($r['_score']);
        }
        return $rows;
    }

    // Id of the list entry ({id,name}) closest to $name, or '' when nothing is close enough.
    public function best_match($name, array $list, $minScore = 70)
    {
        $needle = $this->normalize_name($name);
        if ($needle === '') {
            return '';
        }
        $bestId = '';
        $best = 0;
        foreach ($list as $row) {
            $score = $this->name_score($needle, $this->normalize_name($row['name'] ?? ''));
            if ($score > $best) {
                $best = $score;
                $bestId = $row['id'];
            }
        }
        return $best >= $minScore ? $bestId : '';
    }

    // ---------- lookups ----------
    public function get_account($id)
    {
        return $this->db->where('id', (int)$id)->get('tbl_account')->row_array();
    }

    public function get_account_balance($accountId)
    {
        $query = $this->db
            ->select('COALESCE(SUM(ts.debit - ts.credit), 0) AS balance', false)
            ->from('tbl_transactions_sub ts')
            ->join('tbl_transactions_main tm', 'tm.reference = ts.reference', 'inner')
            ->where('ts.acc_id', (int)$accountId)
            ->where('ts.status', 0)
            ->where('tm.status', 0)
            ->get();

        if ($query === false) {
            throw new RuntimeException('Could not retrieve the account balance.');
        }

        $row = $query->row_array();
        return (float)($row['balance'] ?? 0);
    }

    // Ledger account that belongs to a vendor/customer profile.
    public function get_profile_account($profileId)
    {
        return $this->db->where('profile_id', (int)$profileId)->order_by('id')->get('tbl_account')->row_array();
    }

    public function get_vat_input_account()
    {
        $row = $this->db->where('name', 'VAT Input')->get('tbl_account')->row_array();
        return $row ?: $this->get_account(self::VAT_INPUT_ACC_FALLBACK);
    }

    // Next EXPnnnnn voucher number.
    public function next_voucher_no()
    {
        $row = $this->db->query(
            "SELECT MAX(CAST(SUBSTRING(voucher_no, 4) AS UNSIGNED)) AS n
             FROM tbl_transactions_main WHERE voucher_no LIKE 'EXP%' AND trans_ref = ?",
            [self::TRANS_REF_EXPENSE]
        )->row_array();
        return 'EXP' . str_pad((int)($row['n'] ?? 0) + 1, 5, '0', STR_PAD_LEFT);
    }

    public function reference_saved($reference)
    {
        return (bool)$this->db->where('reference', (string)$reference)
            ->where('trans_ref', self::TRANS_REF_EXPENSE)->get('tbl_transactions_main')->row_array();
    }

    // Insert only columns that exist (live and local schemas differ slightly).
    private function insert_row($table, array $row)
    {
        $fields = $this->db->list_fields($table);
        $row = array_intersect_key($row, array_flip($fields));
        if (!$this->db->insert($table, $row)) {
            throw new RuntimeException('Failed to insert into ' . $table . '.');
        }
        return (int)$this->db->insert_id();
    }

    /**
     * Post the voucher. $h = header, $lines = validated lines, $doc = ['image' => filename] or null.
     * $h: reference, date, branch_id, project_id, profile_id, mop_id, job_id, note,
     *     user_id, ip. Returns ['main_id', 'voucher_no', 'total'].
     * Caller owns the DB transaction.
     */
    public function post_voucher(array $h, array $lines, $doc = null)
    {
        $mop = $this->get_account($h['mop_id']);
        if (!$mop) {
            throw new RuntimeException('Invalid mode of payment.');
        }
        $profileAcc = $h['profile_id'] ? $this->get_profile_account($h['profile_id']) : null;
        $now = date('Y-m-d H:i:s');

        $taxableSum = 0; $vatSum = 0;
        foreach ($lines as &$l) {
            $l['vat_amt'] = round($l['taxable'] * $l['vat_perc'] / 100, 2);
            $l['debit']   = round($l['taxable'] + $l['vat_amt'], 2);
            $taxableSum  += $l['taxable'];
            $vatSum      += $l['vat_amt'];
        }
        unset($l);
        $total = round($taxableSum + $vatSum, 2);
        $vat = $vatSum > 0 ? $this->get_vat_input_account() : null;
        if ($vatSum > 0 && !$vat) {
            throw new RuntimeException('VAT Input account not found; cannot post VAT.');
        }

        $voucherNo = $this->next_voucher_no();
        $mainId = $this->insert_row('tbl_transactions_main', [
            'branch_id'     => $h['branch_id'],
            'project_id'    => $h['project_id'],
            'profile_id'    => 0,
            'receiver_payer' => 0,
            'in_count'      => 0,
            'cogs_distribution' => 0,
            'date'          => $h['date'],
            'reference'     => (string)$h['reference'],
            'reference2'    => 0,
            'number'        => '',
            'voucher_no'    => $voucherNo,
            'trans_ref'     => self::TRANS_REF_EXPENSE,
            'type'          => self::TRANS_TYPE_PAYMENT,
            'description'   => (string)$h['note'],
            'amount'        => $total,
            'remark'        => (string)($h['payment_to'] ?? ''),
            'branch_to'     => 0,
            'br_trans_status' => 0,
            'br_trans_reference' => 0,
            'approval_status' => 1,
            'status'        => 0,
            'added_by'      => $h['user_id'],
            'ip_address'    => $h['ip'],
            'date_time'     => $now,
        ]);

        $sub = function ($ccId, $acc, $desc, $debit, $credit) use ($h) {
            return $this->insert_row('tbl_transactions_sub', [
                'reference'   => (string)$h['reference'],
                'cc_id'       => (int)$ccId,
                'acc_id'      => (int)$acc['id'],
                'acc_name'    => $acc['name'],
                'description' => (string)$desc,
                'debit'       => $debit,
                'credit'      => $credit,
                'status'      => 0,
            ]);
        };

        // 1) one debit row per expense line (+ its tbl_incomeandexpense row)
        foreach ($lines as $l) {
            $cat = $this->get_account($l['category_id']);
            if (!$cat) {
                throw new RuntimeException('Invalid category selected.');
            }
            $subId = $sub($l['cost_center_id'], $cat, $l['description'], $l['taxable'], 0);
            $this->insert_row('tbl_incomeandexpense', [
                'reference' => $h['reference'],
                'subid'     => $subId,
                'pur_id'    => 0,
                'type'      => 1,
                'profile_id' => (int)$h['profile_id'],
                'to_or_from' => '',
                'billdate'  => $l['item_date'] ?: $h['date'],
                'billno'    => $l['bill_number'],
                'acc_id'    => $cat['id'],
                'mop_id'    => $mop['id'],
                'cc_id'     => $l['cost_center_id'],
                'logistics_job_id' => $h['job_id'] ?: null,
                'currency'  => 0,
                'ccrate'    => 1,
                'taxable'   => $l['taxable'],
                'vat_perc'  => $l['vat_perc'],
                'vat_amt'   => $l['vat_amt'],
                'igst_perc' => 0, 'igst_amt' => 0,
                'cgst_perc' => 0, 'cgst_amt' => 0,
                'sgst_perc' => 0, 'sgst_amt' => 0,
                'debit'     => $l['debit'],
                'credit'    => 0,
                'revenue'   => null,
                'status'    => 0,
            ]);
        }

        // 2) VAT input
        if ($vat) {
            $sub(0, $vat, '', round($vatSum, 2), 0);
        }

        // 3) credit mode of payment
        $sub(0, $mop, '', 0, $total);

        // 4) profile ledger (credit + debit pair, as the Add Expense form posts it)
        if ($profileAcc) {
            $sub(0, $profileAcc, '', 0, $total);
            $sub(0, $profileAcc, '', $total, 0);
        }

        // 5) attachment
        if ($doc && !empty($doc['image'])) {
            $this->insert_row('tbl_documents', [
                'type'          => self::DOC_TYPE_EXPENSE,
                'idcards_id'    => 0,
                'row_id'        => $mainId,
                'profile_id'    => 0,
                'investment_id' => 0,
                'date'          => $h['date'],
                'description'   => '',
                'image'         => $doc['image'],
                'added_by'      => $h['user_id'],
                'date_time'     => $now,
                'ip_address'    => $h['ip'],
                'status'        => 0,
            ]);
        }

        return ['main_id' => $mainId, 'voucher_no' => $voucherNo, 'total' => $total];
    }
}
