<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Ocr_model extends CI_Model
{
    private $tbl_invoices      = 'invoices';
    private $tbl_invoice_items = 'tbl_invoice_items'; 
    private $tbl_docs  = 'invoice_documents';
    private $tbl_pages = 'invoice_document_pages';

    public function __construct()
    {
        parent::__construct();
        $this->load->database();
    }

    // -----------------------------
    // READ
    // -----------------------------
    public function get_document($document_id)
    {
        return $this->db
            ->get_where($this->tbl_docs, ['id' => (int)$document_id])
            ->row_array();
    }

    public function get_pages($document_id)
    {
        return $this->db
            ->order_by('page_no', 'ASC')
            ->get_where($this->tbl_pages, ['document_id' => (int)$document_id])
            ->result_array();
    }

    // -----------------------------
    // CREATE
    // -----------------------------
    public function create_document($data)
    {
        $ok = $this->db->insert($this->tbl_docs, $data);
        if (!$ok) return false;

        $id = (int)$this->db->insert_id();
        return ($id > 0) ? $id : false;
    }

    public function create_pages_batch($rows)
    {
        if (empty($rows) || !is_array($rows)) return false;

        $ok = $this->db->insert_batch($this->tbl_pages, $rows);
        // insert_batch returns number of rows inserted OR false
        return ($ok !== false);
    }

    // -----------------------------
    // UPDATE
    // -----------------------------
    public function update_document_status($document_id, $status)
    {
        $this->db->where('id', (int)$document_id);

        $ok = $this->db->update($this->tbl_docs, [
            'status'     => (string)$status,
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        // If update query executed successfully, treat as success
        return ($ok === true);
    }

    /**
     * IMPORTANT FIX:
     * affected_rows() can be 0 even when UPDATE succeeded
     * (e.g., same data written again). So return TRUE if query ok.
     */
    public function update_page_ocr($document_id, $page_no, $ocr_text, $ocr_confidence = null)
    {
        $update_data = [
            'ocr_text'       => $ocr_text,
            'ocr_confidence' => $ocr_confidence,
            'updated_at'     => date('Y-m-d H:i:s'),
        ];

        $this->db->where('document_id', (int)$document_id);
        $this->db->where('page_no', (int)$page_no);

        $ok = $this->db->update($this->tbl_pages, $update_data);

        // If query failed -> false
        if ($ok !== true) return false;

        /**
         * If query succeeded:
         * - affected_rows() may be 1 (changed)
         * - OR 0 (same values written) => still success
         */
        return true;
    }

    // -----------------------------
    // OPTIONAL: helpful debug methods
    // -----------------------------
    public function get_last_db_error()
    {
        return $this->db->error();
    }

    
public function match_vendor($vendor_name, $limit = 20)
{
    return $this->fuzzy_match_profiles($vendor_name, 2, $limit);
}

public function match_customer($customer_name, $limit = 20)
{
    return $this->fuzzy_match_profiles($customer_name, 0, $limit);
}

private function fuzzy_match_profiles($ocr_name, $type, $limit = 20)
{
    if (!is_string($ocr_name) || trim($ocr_name) === '') return [];

    $ocr = $this->normalize_profile_name($ocr_name);
    $ocr_clean = $this->normalize_profile_name(preg_replace('/[^\x20-\x7E]/', '', $ocr_name));
    if ($ocr === '' && $ocr_clean === '') return [];

    $this->db->reset_query();

    $candidates = $this->db
        ->select('id,name,profile_code,currency_id,tax_id')
        ->from('tbl_profile')
        ->where('type', $type)
        ->where('status', 0)
        ->get()
        ->result_array();

    if (empty($candidates)) return [];

    $ocr_words = preg_split('/\s+/', $ocr, -1, PREG_SPLIT_NO_EMPTY);
    sort($ocr_words);
    $ocr_words = implode(' ', $ocr_words);

    foreach ($candidates as &$c) {
        $db = $this->normalize_profile_name($c['name'] ?? '');
        similar_text($ocr, $db, $similar);

        $db_words = preg_split('/\s+/', $db, -1, PREG_SPLIT_NO_EMPTY);
        sort($db_words);
        similar_text($ocr_words, implode(' ', $db_words), $word_order_score);

        $lev_score = 0;
        if (mb_check_encoding($ocr, 'ASCII') && mb_check_encoding($db, 'ASCII')) {
            $distance = levenshtein($ocr, $db);
            $lev_score = max(0, 100 - ($distance * 10));
        }

        $db_clean = $this->normalize_profile_name(preg_replace('/[^\x20-\x7E]/', '', $c['name'] ?? ''));
        $clean_score = 0;
        if ($ocr_clean !== '' && $db_clean !== '') {
            similar_text($ocr_clean, $db_clean, $clean_similar);
            $distance = levenshtein($ocr_clean, $db_clean);
            $clean_lev = max(0, 100 - ($distance * 10));
            $clean_score = max($clean_similar, $clean_lev);
        }

        if (
            ($ocr !== '' && (
                mb_strpos($db, $ocr, 0, 'UTF-8') !== false ||
                mb_strpos($ocr, $db, 0, 'UTF-8') !== false
            )) ||
            ($ocr_clean !== '' && $db_clean !== '' && (
                mb_strpos($db_clean, $ocr_clean, 0, 'UTF-8') !== false ||
                mb_strpos($ocr_clean, $db_clean, 0, 'UTF-8') !== false
            ))
        ) {
            $similar = max($similar, 90);
        }

        $c['_score'] = max($similar, $word_order_score, $lev_score, $clean_score);
    }
    unset($c);

    usort($candidates, function($a, $b) {
        return $b['_score'] <=> $a['_score'];
    });

    $results = array_values(array_filter($candidates, function($c) {
        return $c['_score'] >= 30;
    }));
    $results = array_slice($results, 0, max(0, (int)$limit));

    foreach ($results as &$result) {
        unset($result['_score']);
    }
    unset($result);

    return $results;
}

private function normalize_profile_name($name)
{
    $name = mb_strtolower(trim((string)$name), 'UTF-8');
    $name = preg_replace('/[^\p{L}\p{N}]+/u', ' ', $name);
    return trim(preg_replace('/\s+/u', ' ', $name));
}
public function match_item($item_name, $limit = 20)
{
    if (empty($item_name)) return [];

    $words = preg_split('/\s+/', trim($item_name));
    $words = array_filter($words, function($w) { return strlen($w) >= 3; });
    if (empty($words)) return [];

    $prefixes = [];
    foreach ($words as $w) {
        $prefixes[] = mb_strtolower(mb_substr($w, 0, 4), 'UTF-8');
    }
    $prefixes = array_unique($prefixes);

    $query = $this->db
        ->select('id, name, item_code,sales_price') // <-- add real code column here
        ->from('tbl_items')
        ->group_start();

    foreach ($prefixes as $i => $prefix) {
        $name_match = 'LOWER(name) LIKE ' . $this->db->escape('%' . $prefix . '%');
        $code_match = 'LOWER(item_code) LIKE ' . $this->db->escape('%' . $prefix . '%');

        if ($i === 0) {
            $query->group_start()
                  ->where($name_match, null, false)
                  ->or_where($code_match, null, false)
                  ->group_end();
        } else {
            $query->or_group_start()
                  ->where($name_match, null, false)
                  ->or_where($code_match, null, false)
                  ->group_end();
        }
    }

    $candidates = $query->group_end()->limit(50)->get()->result_array();
    if (empty($candidates)) return [];

    $needle = mb_strtolower($item_name, 'UTF-8');

    foreach ($candidates as &$c) {
        similar_text($needle, mb_strtolower($c['name'], 'UTF-8'), $pctName);
        similar_text($needle, mb_strtolower($c['item_code'] ?? '', 'UTF-8'), $pctCode);
        $c['_score'] = max($pctName, $pctCode);
    }
    unset($c);

    usort($candidates, function($a, $b) { return $b['_score'] <=> $a['_score']; });
    $candidates = array_slice($candidates, 0, $limit);
    foreach ($candidates as &$c) unset($c['_score']);
    unset($c);
if (!empty($candidates)) {
    $ids = array_column($candidates, 'id');
    $unitsByItem = $this->get_units_for_items($ids);
    foreach ($candidates as &$c) {
        $c['units'] = $unitsByItem[$c['id']] ?? [];
    }
    unset($c);
}

return $candidates;
    
}
public function create_invoice($data)
{
    $ok = $this->db->insert($this->tbl_invoices, $data);
    if (!$ok) return false;

    $id = (int)$this->db->insert_id();
    return ($id > 0) ? $id : false;
}

public function create_invoice_items_batch($rows)
{
    if (empty($rows) || !is_array($rows)) return false;

    $ok = $this->db->insert_batch($this->tbl_invoice_items, $rows);
    return ($ok !== false);
}
/**
 * Returns unit options per item_id from tbl_unit_conversions + tbl_unitofmeasure.
 * Shape: [ item_id => [ {unit_id, unit_name, symbol, is_primary, sales_price}, ... ] ]
 */
/**
 * Returns unit options per item_id, sourced from:
 *  - tbl_items.unit_id (the item's base unit — always included)
 *  - tbl_unit_conversions primary_unit/secondary_unit (extra sellable units, deduped)
 * Display label uses tbl_unitofmeasure.quantity (name/symbel are unused/blank in this schema).
 */
public function get_units_for_items(array $itemIds)
{
    $itemIds = array_values(array_unique(array_map('intval', $itemIds)));
    if (empty($itemIds)) return [];

    // 1) base unit_id + base sales_price per item, straight from tbl_items
    $baseRows = $this->db
        ->select('id, unit_id, sales_price')
        ->from('tbl_items')
        ->where_in('id', $itemIds)
        ->get()
        ->result_array();

    $baseUnitByItem  = [];
    $basePriceByItem = [];
    foreach ($baseRows as $r) {
        $baseUnitByItem[(int)$r['id']]  = (int)$r['unit_id'];
        $basePriceByItem[(int)$r['id']] = $r['sales_price'];
    }

    // 2) all unit ids we'll need to resolve to display labels
    $allUnitIds = array_filter(array_values($baseUnitByItem));

    // 3) extra units from tbl_unit_conversions
    $convRows = $this->db
        ->select('item_id, primary_unit, secondary_unit, sales_price, sales_price1')
        ->from('tbl_unit_conversions')
        ->where_in('item_id', $itemIds)
        ->where('status', 0) // 0 = active, matches project convention
        ->get()
        ->result_array();

    foreach ($convRows as $r) {
        if (!empty($r['primary_unit']))   $allUnitIds[] = (int)$r['primary_unit'];
        if (!empty($r['secondary_unit'])) $allUnitIds[] = (int)$r['secondary_unit'];
    }
    $allUnitIds = array_values(array_unique(array_filter($allUnitIds)));

    // 4) resolve unit id -> label
    $unitLabels = [];
    if (!empty($allUnitIds)) {
        $uomRows = $this->db
            ->select('id, quantity, uqc')
            ->from('tbl_unitofmeasure')
            ->where_in('id', $allUnitIds)
            ->get()
            ->result_array();
        foreach ($uomRows as $u) {
            $unitLabels[(int)$u['id']] = $u['quantity'] ?: $u['uqc'];
        }
    }

    // 5) build per-item unit list, deduped by unit_id, base unit first
    $byItem = [];
    $seen = []; // 'itemid_unitid' => true

    foreach ($itemIds as $iid) $byItem[$iid] = [];

    foreach ($itemIds as $iid) {
        if (!empty($baseUnitByItem[$iid])) {
            $uid = $baseUnitByItem[$iid];
            $byItem[$iid][] = [
                'unit_id'     => $uid,
                'unit_name'   => $unitLabels[$uid] ?? ('Unit #' . $uid),
                'is_base'     => 1,
                'sales_price' => $basePriceByItem[$iid] ?? null,
            ];
            $seen[$iid . '_' . $uid] = true;
        }
    }

    foreach ($convRows as $r) {
        $iid = (int)$r['item_id'];
        if (!isset($byItem[$iid])) continue;

        foreach ([
            ['unit' => $r['primary_unit'],   'price' => $r['sales_price']],
            ['unit' => $r['secondary_unit'], 'price' => $r['sales_price1']],
        ] as $pair) {
            $uid = (int)($pair['unit'] ?? 0);
            if (!$uid) continue;
            $key = $iid . '_' . $uid;
            if (isset($seen[$key])) continue;
            $seen[$key] = true;

            $byItem[$iid][] = [
                'unit_id'     => $uid,
                'unit_name'   => $unitLabels[$uid] ?? ('Unit #' . $uid),
                'is_base'     => 0,
                'sales_price' => $pair['price'], // was $pair['sales_price'] — key never existed
            ];
        }
    }

    return $byItem;
}
public function create_vendor($data)
{
    $insert = [
        'name'         => $data['name'],
        'type'         => 2,  // vendor
        'status'       => 0,  // active
        'profile_code' => $data['profile_code'] ?? null,
        'cr_no'        => $data['cr_no'] ?? null,
        'tax_id'       => is_numeric($data['tax_id']) ? (int)$data['tax_id'] : null,
        'currency_id'  => $data['currency_id'] ?? null,
        'contact_no1'  => $data['contact_no1'] ?? null,
        'contact_no2'  => $data['contact_no2'] ?? null,
        'email'        => $data['email'] ?? null,
        'branch_id'    => $data['branch_id'] ?? 1, // see note below
        'added_by'     => $data['added_by'] ?? 0,
        'date_time'    => date('Y-m-d H:i:s'),
    ];

    $ok = $this->db->insert('tbl_profile', $insert);
    if (!$ok) return false;

    $id = (int)$this->db->insert_id();
    return ($id > 0) ? $id : false;
}

// -----------------------------
// Additional helpers for saving purchases/logs
// -----------------------------
public function create_log($data)
{
    $insert = [
        'action' => $data['action'] ?? 'purchase_import',
        'notes'  => $data['notes'] ?? null,
        'created_at' => date('Y-m-d H:i:s')
    ];

    $ok = $this->db->insert('tbl_logs', $insert);
    if (!$ok) return false;
    $id = (int)$this->db->insert_id();
    return ($id > 0) ? $id : false;
}

public function create_purchase($data)
{
    $insert = [
        'reference_no' => $data['reference_no'] ?? null,
        'vendor_id'    => $data['vendor_id'] ?? null,
        'total'        => $data['total'] ?? 0,
        'created_at'   => date('Y-m-d H:i:s')
    ];

    $ok = $this->db->insert('tbl_purchase', $insert);
    if (!$ok) return false;
    $id = (int)$this->db->insert_id();
    return ($id > 0) ? $id : false;
}

public function create_purchase_items_batch($rows)
{
    if (empty($rows) || !is_array($rows)) return false;
    $ok = $this->db->insert_batch('tbl_purchase_items', $rows);
    return ($ok !== false);
}

}
