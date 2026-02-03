<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Ocr_model extends CI_Model {

    private $tbl_docs  = 'invoice_documents';
    private $tbl_pages = 'invoice_document_pages';

    public function __construct() {
        parent::__construct();
        $this->load->database();
    }

    public function get_pages($document_id)
    {
        return $this->db
            ->order_by('page_no', 'ASC')
            ->get_where($this->tbl_pages, ['document_id' => $document_id])
            ->result_array();
    }

    public function update_page_ocr($document_id, $page_no, $ocr_text, $ocr_confidence = null)
    {
        $this->db->where('document_id', $document_id);
        $this->db->where('page_no', $page_no);
        $update_data = [
            'ocr_text'       => $ocr_text,
            'ocr_confidence' => $ocr_confidence,
            'updated_at'     => date('Y-m-d H:i:s')
        ];
        $result = $this->db->update($this->tbl_pages, $update_data);
        return ($this->db->affected_rows() > 0);
    }

    public function update_document_status($document_id, $status)
    {
        $this->db->where('id', $document_id);
        return $this->db->update($this->tbl_docs, [
            'status'     => $status,
            'updated_at' => date('Y-m-d H:i:s')
        ]);
    }

    public function create_document($data)
    {
        if ($this->db->insert($this->tbl_docs, $data)) {
            $id = $this->db->insert_id();
            return ($id > 0) ? $id : false;
        }
        return false;
    }

    public function create_pages_batch($rows)
    {
        if (empty($rows)) {
            return false;
        }
        $result = $this->db->insert_batch($this->tbl_pages, $rows);
        return ($result !== false);
    }

    public function get_document($document_id)
    {
        return $this->db
            ->get_where($this->tbl_docs, ['id' => $document_id])
            ->row_array();
    }
    


    
    // public function get_all($branch_id, $filters = []) {
    //     $this->db->select('et.*, p.name, e.equipment_code, e.make_model, eq_type.type_name');
    //     $this->db->from($this->table . ' et');
    //     $this->db->join('tbl_project p', 'p.id = et.project_id AND p.status = 0', 'left');
    //     $this->db->join('tbl_equipments e', 'e.id = et.equipment_id', 'left');
    //     $this->db->join('tbl_equipment_types eq_type', 'eq_type.id = e.equipment_type_id', 'left');
	// 	$this->db->where('et.status !=', 'deleted');
    //     $this->db->where('et.branch_id', $branch_id);
        
    //     // Apply filters
    //     if (!empty($filters['project_id'])) {
    //         $this->db->where('et.project_id', $filters['project_id']);
    //     }
    //     if (!empty($filters['equipment_id'])) {
    //         $this->db->where('et.equipment_id', $filters['equipment_id']);
    //     }
    //     if (!empty($filters['status'])) {
    //         $this->db->where('et.status', $filters['status']);
    //     }
    //     if (!empty($filters['month'])) {
    //         $this->db->where('et.month', $filters['month']);
    //     }
    //     if (!empty($filters['year'])) {
    //         $this->db->where('et.year', $filters['year']);
    //     }
        
    //     $this->db->order_by('et.created_at', 'DESC');
    //     return $this->db->get()->result();
    // }
    
    // public function get_by_id($id, $branch_id) {
	// 	// print_r($this ->table);die;
    //     $this->db->select('et.*, p.name, e.equipment_code, e.make_model, e.hourly_rate, e.overtime_rate');
    //     $this->db->from($this->table . ' et');
    //     $this->db->join('tbl_project p', 'p.id = et.project_id AND p.status = 0', 'left');
    //     $this->db->join('tbl_equipments e', 'e.id = et.equipment_id', 'left');
    //     // $this->db->join('equipment_types eq_type', 'eq_type.id = e.equipment_type_id', 'left');
    //     $this->db->where('et.id', $id);
    //     $this->db->where('et.branch_id', $branch_id);

	// 	$res = $this->db->get()->row();
	// 	// print_r($res);die;

    //     return $res;
    // }
    
    // public function create($data) {
    //     $this->db->insert($this->table, $data);
    //     return $this->db->insert_id();
    // }
    
    // public function update($id, $data,$branch_id) {
    //     $this->db->where('id', $id);
    //     $this->db->where('branch_id', $branch_id);
    //     return $this->db->update($this->table, $data);
    // }
    
    // public function delete($id, $branch_id,$data) {
    //     $this->db->where('id', $id);
    //     $this->db->where('branch_id', $branch_id);
    //     return $this->db->update($this->table, $data);
    // }
    
    // public function generate_timesheet_number($branch_id) {
    //     $this->load->model('Timesheet_helper_model');
    //     $settings = $this->Timesheet_helper_model->get_by_company($branch_id);
    //     $prefix = $settings ? $settings->timesheet_prefix_equipment : 'EQ-TS';
        
    //     // Get next number
    //     $this->db->select('MAX(RIGHT(timesheet_number, 6)) as max_num');
    //     $this->db->where('branch_id', $branch_id);
    //     $this->db->where('timesheet_number LIKE', $prefix . '%');
    //     $result = $this->db->get($this->table)->row();
        
    //     $next_num = ($result && $result->max_num) ? $result->max_num + 1 : 1;
    //     return $prefix . '-' . str_pad($next_num, 6, '0', STR_PAD_LEFT);
    // }
    
    // // Timesheet Details Methods
    // public function get_details($timesheet_id) {
    //     $this->db->where('timesheet_id', $timesheet_id);
    //     $this->db->order_by('work_date', 'ASC');
    //     return $this->db->get($this->details_table)->result();
    // }
    
    // public function create_detail($data) {
    //     return $this->db->insert($this->details_table, $data);
    // }
    
    // public function update_detail($id, $data) {
    //     $this->db->where('id', $id);
    //     return $this->db->update($this->details_table, $data);
    // }
    
    // public function delete_details($timesheet_id) {
    //     $this->db->where('timesheet_id', $timesheet_id);
    //     return $this->db->delete($this->details_table);
    // }
    
    // public function calculate_totals($timesheet_id) {
    //     $this->db->select('SUM(normal_hours) as total_normal, SUM(overtime_hours) as total_overtime, SUM(day_total_hours) as total_hours');
    //     $this->db->where('timesheet_id', $timesheet_id);
    //     $totals = $this->db->get($this->details_table)->row();
        
    //     if ($totals) {
    //         $update_data = [
    //             'total_normal_hours' => $totals->total_normal ?: 0,
    //             'total_overtime_hours' => $totals->total_overtime ?: 0,
    //             'total_hours' => $totals->total_hours ?: 0
    //         ];
            
    //         // Calculate total amount
    //         $timesheet = $this->db->get_where($this->table, ['id' => $timesheet_id])->row();
    //         if ($timesheet) {
    //             $equipment = $this->db->get_where('equipments', ['id' => $timesheet->equipment_id])->row();
    //             if ($equipment) {
    //                 $normal_amount = $totals->total_normal * $equipment->hourly_rate;
    //                 $overtime_amount = $totals->total_overtime * $equipment->overtime_rate;
    //                 $update_data['total_amount'] = $normal_amount + $overtime_amount;
    //             }
    //         }
            
    //         $this->db->where('id', $timesheet_id);
    //         $this->db->update($this->table, $update_data);
    //     }
    // }
    
    // public function bulk_create_monthly_details($timesheet_id, $month, $year) {
    //     $days_in_month = cal_days_in_month(CAL_GREGORIAN, $month, $year);
    //     $details = [];
        
    //     for ($day = 1; $day <= $days_in_month; $day++) {
    //         $details[] = [
    //             'timesheet_id' => $timesheet_id,
    //             'work_date' => sprintf('%04d-%02d-%02d', $year, $month, $day),
    //             'normal_hours' => 0.00,
    //             'overtime_hours' => 0.00,
    //             'day_total_hours' => 0.00
    //         ];
    //     }
        
    //     return $this->db->insert_batch($this->details_table, $details);
    // }


	// //////////////////////////////////// Equipment model ////////////////////////////////////////////

	// public function get_all_equipment($branch_id, $status = null) {
    //     $this->db->select('e.*, et.type_name, et.category, v.name');
    //     $this->db->from($this->equipments_table . ' e');
    //     $this->db->join('tbl_equipment_types et', 'et.id = e.equipment_type_id', 'left');
    //     $this->db->join('tbl_profile v', 'v.id = e.vendor_id', 'left');
    //     $this->db->where('e.branch_id', $branch_id);
        
    //     if ($status) {
    //         $this->db->where('e.status', $status);
    //     }
        
    //     $this->db->order_by('e.equipment_code', 'ASC');
    //     return $this->db->get()->result();
    // }
    
    // public function get_equipment_by_id($id, $branch_id) {
    //     $this->db->where('id', $id);
    //     $this->db->where('branch_id', $branch_id);
    //     return $this->db->get($this->equipments_table)->row();
    // }
    
    // public function equipment_create($data) {
    //     $this->db->insert($this->equipments_table, $data);
    //     return $this->db->insert_id();
    // }
    
    // public function equipment_update($id, $data, $branch_id) {
    //     $this->db->where('id', $id);
    //     $this->db->where('branch_id', $branch_id);
    //     return $this->db->update($this->equipments_table, $data);
    // }
    
    // public function equipment_delete($id, $branch_id) {
    //     $this->db->where('id', $id);
    //     $this->db->where('branch_id', $branch_id);
    //     return $this->db->delete($this->equipments_table);
    // }



	// ////////////// Equipment Managing ///////////////

	//  public function get_equipments($branch_id, $limit = null, $offset = null, $search = null) {
    //     $this->db->select('e.*, et.type_name, et.category');
    //     $this->db->from($this->equipments_table . ' e');
    //     $this->db->join('tbl_equipment_types et', 'e.equipment_type_id = et.id', 'left');
    //     $this->db->where('e.status !=', 'deleted');
    //     $this->db->where('e.branch_id', $branch_id);
        
    //     if (!empty($search)) {
    //         $this->db->group_start();
    //         $this->db->like('e.equipment_code', $search);
    //         $this->db->or_like('e.make_model', $search);
    //         $this->db->or_like('e.registration_number', $search);
    //         $this->db->or_like('et.type_name', $search);
    //         $this->db->group_end();
    //     }
        
    //     $this->db->order_by('e.created_at', 'DESC');
        
    //     if ($limit !== null && $offset !== null) {
    //         $this->db->limit($limit, $offset);
    //     }
        
    //     return $this->db->get()->result();
    // }

    // /**
    //  * Get total count for pagination
    //  */
    // public function count_equipments($branch_id, $search = null) {
    //     $this->db->from($this->equipments_table . ' e');
    //     $this->db->join('tbl_equipment_types et', 'e.equipment_type_id = et.id', 'left');
    //     $this->db->where('e.branch_id', $branch_id);
        
    //     if (!empty($search)) {
    //         $this->db->group_start();
    //         $this->db->like('e.equipment_code', $search);
    //         $this->db->or_like('e.make_model', $search);
    //         $this->db->or_like('e.registration_number', $search);
    //         $this->db->or_like('et.type_name', $search);
    //         $this->db->group_end();
    //     }
        
    //     return $this->db->count_all_results();
    // }

    // /**
    //  * Get single equipment by ID
    //  */
    // public function get_equipment($id, $branch_id) {
    //     $this->db->select('e.*, et.type_name, et.category');
    //     $this->db->from($this->equipments_table . ' e');
    //     $this->db->join('tbl_equipment_types et', 'e.equipment_type_id = et.id', 'left');
    //     $this->db->where('e.id', $id);
    //     $this->db->where('e.branch_id', $branch_id);
    //     return $this->db->get()->row();
    // }

    // /**
    //  * Insert new equipment
    //  */
    // public function insert_equipment($data) {
    //     $data['created_at'] = date('Y-m-d H:i:s');
    //     $data['updated_at'] = date('Y-m-d H:i:s');
        
    //     if ($this->db->insert($this->equipments_table, $data)) {
    //         return $this->db->insert_id();
    //     }
    //     return false;
    // }

    // /**
    //  * Update equipment
    //  */
    // public function update_equipment($id, $branch_id, $data) {
    //     $data['updated_at'] = date('Y-m-d H:i:s');
        
    //     $this->db->where('id', $id);
    //     $this->db->where('branch_id', $branch_id);
    //     return $this->db->update($this->equipments_table, $data);
    // }

    // /**
    //  * Delete equipment
    //  */
    // public function delete_equipment($id, $branch_id,$data) {
    //     $this->db->where('id', $id);
    //     $this->db->where('branch_id', $branch_id);
	//  return $this->db->update($this->equipments_table, $data);    }

    // /**
    //  * Get available equipments for dropdown
    //  */
    // public function get_available_equipments($branch_id, $equipment_type_id = null) {
    //     $this->db->select('e.id, e.equipment_code, e.make_model, et.type_name');
    //     $this->db->from($this->equipments_table . ' e');
    //     $this->db->join('tbl_equipment_types et', 'e.equipment_type_id = et.id', 'left');
    //     $this->db->where('e.branch_id', $branch_id);
    //     $this->db->where('e.status', 'available');
        
    //     if ($equipment_type_id) {
    //         $this->db->where('e.equipment_type_id', $equipment_type_id);
    //     }
        
    //     $this->db->order_by('e.equipment_code', 'ASC');
    //     return $this->db->get()->result();
    // }

    // /**
    //  * Check if equipment code exists
    //  */
    // public function check_equipment_code_exists($equipment_code, $branch_id, $exclude_id = null) {
    //     $this->db->where('equipment_code', $equipment_code);
    //     $this->db->where('branch_id', $branch_id);
        
    //     if ($exclude_id) {
    //         $this->db->where('id !=', $exclude_id);
    //     }
        
    //     $query = $this->db->get($this->equipments_table);
    //     return $query->num_rows() > 0;
    // }

    // /**
    //  * Get equipments by type
    //  */
    // public function get_equipments_by_type($branch_id, $equipment_type_id) {
    //     $this->db->select('*');
    //     $this->db->from($this->equipments_table);
    //     $this->db->where('branch_id', $branch_id);
    //     $this->db->where('equipment_type_id', $equipment_type_id);
    //     $this->db->order_by('equipment_code', 'ASC');
    //     return $this->db->get()->result();
    // }



	// 	//////////////////////////////////// Equipment type model ////////////////////////////////////////////

	// 	public function get_equipment_types($branch_id, $limit = null, $offset = null, $search = null) {
    //     $this->db->select('*');
    //     $this->db->from($this->equipment_types_table);
    //     $this->db->where('branch_id', $branch_id);
    //     $this->db->where('status !=', 2);
        
    //     if (!empty($search)) {
    //         $this->db->group_start();
    //         $this->db->like('type_name', $search);
    //         $this->db->or_like('category', $search);
    //         $this->db->group_end();
    //     }
        
    //     $this->db->order_by('created_at', 'DESC');
        
    //     if ($limit !== null && $offset !== null) {
    //         $this->db->limit($limit, $offset);
    //     }
        
    //     return $this->db->get()->result();
    // }

    // /**
    //  * Get total count for pagination
    //  */
	// public function count_equipment_types($branch_id, $search = null) {
	// 	// print_r($this->equipment_types_table);die;
	// 	$this->db->select('*');

	// 	$this->db->from($this->equipment_types_table);
    //     // $this->db->where('branch_id', $branch_id);
        
    //     if (!empty($search)) {
	// 		$this->db->group_start();
    //         $this->db->like('type_name', $search);
    //         $this->db->or_like('category', $search);
    //         $this->db->group_end();
    //     }
	// 	// print_r('s');die;
        
    //     return $this->db->count_all_results();
    // }

    // /**
    //  * Get single equipment type by ID
    //  */
    // public function get_equipment_type($id, $branch_id) {
    //      $res = $this->db->get_where($this->equipment_types_table, array('id' => $id, 'branch_id' => $branch_id))->row();
	// 	//  print_r($res);die;
	// 	 return $res;
		
    // }

    // /**
    //  * Insert new equipment type
    //  */
    // public function insert_equipment_type($data) {
    //     $data['created_at'] = date('Y-m-d H:i:s');
    //     $data['updated_at'] = date('Y-m-d H:i:s');
        
    //     if ($this->db->insert($this->equipment_types_table, $data)) {
    //         return $this->db->insert_id();
    //     }
    //     return false;
    // }

    // /**
    //  * Update equipment type
    //  */
    // public function update_equipment_type($id, $branch_id, $data) {
    //     $data['updated_at'] = date('Y-m-d H:i:s');
        
    //     $this->db->where('id', $id);
    //     $this->db->where('branch_id', $branch_id);
    //     return $this->db->update($this->equipment_types_table, $data);
    // }

    // /**
    //  * Delete equipment type
    //  */
    // public function delete_equipment_type($id, $branch_id) {
    //     $this->db->where('id', $id);
	// 	$data = [
	// 		'status' => 2
	// 	];

    //     $this->db->where('branch_id', $branch_id);
    //     return $this->db->update($this->equipment_types_table, $data);
    // }

    // /**
    //  * Get active equipment types for dropdown
    //  */
    // public function get_active_equipment_types($branch_id) {
    //     $this->db->select('id, type_name, category');
    //     $this->db->from($this->equipment_types_table);
    //     $this->db->where('branch_id', $branch_id);
    //     $this->db->where('status', 1);
    //     $this->db->order_by('type_name', 'ASC');
    //     return $this->db->get()->result();
    // }

	//  public function get_vendors($branch_id) {
    //     $this->db->select('*');
    //     $this->db->from($this->sec_table);
    //     // $this->db->where('branch_id', $branch_id);
    //     $this->db->where('status', 0);
    //     $this->db->where('type', 2);
    //     // $this->db->order_by('type_name', 'ASC');
    //     $res = $this->db->get()->result();

	// 	// print_r($res);die;

	// 	return $res;
    // }

    // /**
    //  * Check if equipment type name exists
    //  */
    // public function check_type_name_exists($type_name, $branch_id, $exclude_id = null) {
    //     $this->db->where('type_name', $type_name);
    //     $this->db->where('branch_id', $branch_id);
        
    //     if ($exclude_id) {
    //         $this->db->where('id !=', $exclude_id);
    //     }
        
    //     $query = $this->db->get($this->equipment_types_table);
    //     return $query->num_rows() > 0;
    // }


	

	// /////////////////////////// Project /////////////////////////////

	// public function get_all_project($branch_id, $status = null) {

	// 	// print_r($branch_id);die;
    //     $this->db->where('branch_id', $branch_id);
        
       
    //     $this->db->where('status', 0);
        
    //     $this->db->order_by('name', 'ASC');
    //     return $this->db->get($this->project_table)->result();
    // }
    
    // public function get_project_by_id($id, $branch_id) {
    //     $this->db->where('id', $id);
    //     $this->db->where('status', 0);
    //     $this->db->where('branch_id', $branch_id);
    //     return $this->db->get($this->project_table)->row();
    // }
    
    // public function create_project($data) {
    //     $this->db->insert($this->project_table, $data);
    //     return $this->db->insert_id();
    // }
    
    // public function update_project($id, $data, $branch_id) {
    //     $this->db->where('id', $id);
    //     $this->db->where('branch_id', $branch_id);
    //     return $this->db->update($this->project_table, $data);
    // }
    
    // public function delete_project($id, $branch_id) {
    //     $this->db->where('id', $id);
    //     $this->db->where('branch_id', $branch_id);
    //     return $this->db->delete($this->project_table);
    // }

}


