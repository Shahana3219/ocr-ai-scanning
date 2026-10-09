<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Rbac_model extends CI_Model
{
    protected $table = 'tbl_rbac_modules';

    public function __construct()
    {
        parent::__construct();
        $this->load->database(); // guarantees $this->db exists even if not autoloaded
    }

    /**
     * All modules, optionally filtered by a search keyword
     * (matches module_group, module_title, function_name).
     */
    public function get_all($search = null, $include_deleted = false)
    {
        $this->db->select('*');
        $this->db->from($this->table);

        if (!$include_deleted) {
            $this->db->where('status', 0); // hide soft-deleted (status=1) rows
        }

        if ($search !== null && $search !== '') {
            $this->db->group_start();
            $this->db->like('module_group', $search);
            $this->db->or_like('module_title', $search);
            $this->db->or_like('function_name', $search);
            $this->db->group_end();
        }

        $this->db->order_by('id', 'ASC');

        return $this->db->get()->result();
    }

    public function get($id)
    {
        return $this->db->get_where($this->table, ['id' => $id])->row();
    }

    /**
     * Distinct module_group values straight from the table data,
     * used to populate the autofill <datalist> on the modal form.
     */
    public function get_groups()
    {
        $this->db->distinct();
        $this->db->select('module_group');
        $this->db->where('module_group !=', '');
        $this->db->order_by('module_group', 'ASC');
        $query = $this->db->get($this->table);

        return array_column($query->result_array(), 'module_group');
    }

    public function insert($data)
    {
        $this->db->insert($this->table, $data);
        return $this->db->insert_id();
    }

    public function update($id, $data)
    {
        $this->db->where('id', $id);
        return $this->db->update($this->table, $data);
    }

    /**
     * Soft delete: set status = 1 (inactive/deleted) instead of removing the row.
     */
    public function delete($id)
    {
        $this->db->where('id', $id);
        return $this->db->update($this->table, ['status' => 1]);
    }

    /**
     * Duplicate guard: same group + same function_name.
     * $exclude_id lets edit-save skip the row being edited.
     */
    public function exists($module_group, $function_name, $exclude_id = null)
    {
        if ($function_name === '') {
            return false;
        }

        $this->db->where('module_group', $module_group);
        $this->db->where('function_name', $function_name);
        $this->db->where('status', 0); // ignore soft-deleted rows
        if ($exclude_id) {
            $this->db->where('id !=', $exclude_id);
        }
        return $this->db->get($this->table)->num_rows() > 0;
    }

    /**
     * Function Name must be unique across the WHOLE table, regardless of group.
     * Blank function_name is allowed to repeat (e.g. rows like "Edit" that have none).
     * $exclude_id lets edit-save skip the row being edited.
     */
    public function function_name_exists($function_name, $exclude_id = null)
    {
        if ($function_name === '') {
            return false;
        }

        $this->db->where('function_name', $function_name);
        $this->db->where('status', 0); // ignore soft-deleted rows
        if ($exclude_id) {
            $this->db->where('id !=', $exclude_id);
        }
        return $this->db->get($this->table)->num_rows() > 0;
    }
}