<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Ocr_model extends CI_Model
{
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
}
