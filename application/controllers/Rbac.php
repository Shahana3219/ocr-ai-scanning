<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Rbac extends CI_Controller
{

    public function __construct()
    {
        parent::__construct();

        $this->load->model('rbac_model');
        $this->load->library(['form_validation', 'session', 'upload']);
        $this->load->helper(['url', 'form']);
    }

    public function rbac()
    {
        $keyword = $this->input->get('q', TRUE);

        $data['modules'] = $this->rbac_model->get_all($keyword);
        $data['keyword'] = $keyword;

        $this->load->view('template/header', $data);
        $this->load->view('template/navbar', $data);
        $this->load->view('rbac/rbac', $data);
        $this->load->view('template/footer');
        $this->load->view('template/last');
    }

    public function rbac_add()
    {
        $data['module']      = null;
        $data['groups']      = $this->rbac_model->get_groups();
        $data['form_action'] = site_url('rbac/rbac_add');
        $data['page_title']  = 'Add RBAC Module';

        if ($this->input->method() === 'post') {

    $this->form_validation->set_rules(
        'module_group',
        'Module Group',
        'required|trim|max_length[100]'
    );

    $this->form_validation->set_rules(
        'module_title',
        'Module Title',
        'required|trim'
    );

    $this->form_validation->set_rules(
        'function_name',
        'Function Name',
        'trim|max_length[100]'
    );

    if ($this->form_validation->run() === TRUE) {

        $module_group  = $this->input->post('module_group', TRUE);
        $module_title  = $this->input->post('module_title', TRUE);
        $function_name = $this->input->post('function_name', TRUE);

        if ($this->rbac_model->function_name_exists($function_name)) {

            $this->session->set_flashdata(
                'error',
                'Function Name "' . $function_name . '" is already in use.'
            );

        } elseif ($this->rbac_model->exists($module_group, $function_name)) {

            $this->session->set_flashdata(
                'error',
                'This Module Group + Function Name combination already exists.'
            );

        } else {

            $payload = [
                'module_group'  => $module_group,
                'module_title'  => $module_title,
                'function_name' => $function_name,
                'status'        => 0
            ];

            $insert_id = $this->rbac_model->insert($payload);

            if ($insert_id) {
                $this->session->set_flashdata(
                    'success',
                    'Module added successfully.'
                );
            } else {
                $this->session->set_flashdata(
                    'error',
                    'Failed to add module.'
                );
            }

            redirect('rbac/rbac');
        }
    }

    $data['module'] = (object) $this->input->post();
}

        $this->load->view('template/header', $data);
        $this->load->view('template/navbar', $data);
        $this->load->view('rbac/rbac', $data);
        $this->load->view('template/footer');
        $this->load->view('template/last');
    }

    public function rbac_edit($id = null)
    {
        if (!$id) {
            show_404();
        }

        $module = $this->rbac_model->get($id);
        if (!$module) {
            show_404();
        }

        $data['module']      = $module;
        $data['groups']      = $this->rbac_model->get_groups();
        $data['form_action'] = site_url('rbac/rbac_edit/' . $id);
        $data['page_title']  = 'Edit RBAC Module';

        if ($this->input->method() === 'post') {
            $this->form_validation->set_rules('module_group', 'Module Group', 'required|trim|max_length[100]');
            $this->form_validation->set_rules('module_title', 'Module Title', 'required|trim');
            $this->form_validation->set_rules('function_name', 'Function Name', 'trim|max_length[100]');
            $this->form_validation->set_rules('status', 'Status', 'required|in_list[0,1]');

            if ($this->form_validation->run() === TRUE) {
                $module_group  = $this->input->post('module_group', TRUE);
                $function_name = $this->input->post('function_name', TRUE);

                // Function Name must be unique across the whole table, excluding this row itself
                if ($this->rbac_model->function_name_exists($function_name, $id)) {
                    $this->session->set_flashdata('error', 'Function Name "' . $function_name . '" is already in use by another module. Function names must be unique.');
                } elseif ($this->rbac_model->exists($module_group, $function_name, $id)) {
                    $this->session->set_flashdata('error', 'This Module Group + Function Name combination already exists.');
                } else {
                    $payload = [
                        'module_group'  => $module_group,
                        'module_title'  => $this->input->post('module_title', TRUE),
                        'function_name' => $function_name,
                        'status'        => (int) $this->input->post('status'),
                    ];

                    $this->rbac_model->update($id, $payload);
                    $this->session->set_flashdata('success', 'Module updated successfully.');
                    redirect('rbac/rbac');
                }
            }

            // repopulate form with submitted values on validation failure
            $data['module'] = (object) array_merge((array) $module, $this->input->post());
        }

        $this->load->view('template/header', $data);
        $this->load->view('template/navbar', $data);
        $this->load->view('rbac/rbac', $data);
        $this->load->view('template/footer');
        $this->load->view('template/last');
    }

    public function rbac_delete($id = null)
    {
        if (!$id) {
            show_404();
        }

        $this->rbac_model->delete($id);
        $this->session->set_flashdata('success', 'Module deleted successfully.');
        redirect('rbac/rbac');
    }

}