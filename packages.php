<?php

if (!defined('BASEPATH'))
    exit('No direct script access allowed');
/*
 * Description: Package controller class
 */

class Package extends MY_Controller {

    function __construct() {
        parent::__construct();
        $this->load->model('package_model');
    }

    public function index() {
        $this->load_main_view('edit_products_view');
    }

    public function get_product_packages_json($date = '') {
        $packages = $this->package_model->get_all_product_packages($date);
        
        if (!empty($packages)) {
            echo json_encode($packages);
        }
    }

    public function save_packages() {
        $post = $this->input->post();

        if (!empty($post['package_type'])) {
            $error_message = '';
            $custom_values = '';

            foreach ($post['package_type'] as $product_id => $package_type) {
                if (!empty($post['effective_date'][$product_id])) {
                    $effective_date = $post['effective_date'][$product_id];
                } else {
                    $effective_date = '';
                }

                $post['total_documents'][$product_id] = $post['total_documents'][$product_id] ? $post['total_documents'][$product_id] : 0;

                if ($package_type === 'CP') {
                    if (empty($post['package_cost'][$product_id]) || empty($post['overage_cost'][$product_id])) {
                        $error_message .= 'Missing values for custom package for ' . $this->common_model->product_id2name[$product_id] . '.';
                    }
        
                    $custom_values = array('package_cost' => $post['package_cost'][$product_id], 'total_documents' => $post['total_documents'][$product_id], 'overage_cost' => $post['overage_cost'][$product_id]);
                }

                if (!empty($post['end_date'][$product_id])) {
                    $end_date = $post['end_date'][$product_id];
                } else {
                    $end_date = null;
                }

                $extra_fees = array();

                if (!empty($post['startup_fee'][$product_id])) {
                    $extra_fees['startup_fee'] = $post['startup_fee'][$product_id];
                }

                if (!empty($post['graphics_fee'][$product_id])) {
                    $extra_fees['graphics_fee'] = $post['graphics_fee'][$product_id];
                }

                if (!empty($post['misc_fee'][$product_id])) {
                    $extra_fees['misc_fee'] = $post['misc_fee'][$product_id];
                }

                if (!empty($post['startup_paid'][$product_id])) {
                    $extra_fees['startup_paid'] = 1;
                } else {
                    $extra_fees['startup_paid'] = 0;
                }

                try {
                    $this->package_model->save_package($post['agency_id'], $product_id, $package_type, $effective_date, $end_date, $custom_values, $post['bill_to'], $extra_fees);
                    
                    if (!empty($post['price_adjustment'])) {
                        $this->package_model->save_adjustment($post['agency_id'], $product_id, $post['price_adjustment']);
                    }
                } catch (Exception $e) {
                    if (!empty($error_message)) {
                        if (strpos($error_message, $e->getMessage()) === false) { // dont repeat
                            $error_message = $error_message . '</br>' . $e->getMessage();
                        }
                    } else {
                        $error_message = $e->getMessage();
                    }
                }
            }

            if (empty($error_message)) {
                echo 'ok';
                
                return true;
            }
        } else {
            $error_message = 'Missing required data.';
        }
        echo $error_message;

        return false;
    }

    public function get_packages_table() {
        $agency_id = $this->input->post('agency_id');

        if (!empty($agency_id)) {
            $data['agency_packages'] = $this->package_model->get_packages($agency_id, 'billing_id');
            $data['pending_packages'] = $this->package_model->get_pending_packages($agency_id);
            $data['agency_id'] = $agency_id;
            
            $this->load->model('agency_model');
            $this->agency_model->set_agency_id($agency_id);
            
            $data['group_billing_id'] = $this->agency_model->get_group_billing_id();
            $data['sid'] = $this->agency_model->get_sid();
            
            echo $this->load->view('agency_packages_view', $data, true);
        }
    }

    public function list_packages($agency_id) {
        if (!empty($agency_id)) {
            $data['agency_packages'] = $this->package_model->get_packages($agency_id, 'billing_id');
            $data['pending_packages'] = $this->package_model->get_pending_packages($agency_id);
            $data['products'] = $this->common_model->product_id2name;
            $data['agency_id'] = $agency_id;

            $this->load->model('agency_model');
            $this->agency_model->set_agency_id($agency_id);

            $data['agency_name'] = $this->agency_model->get_agency_name();
            $data['group_billing_id'] = $this->agency_model->get_group_billing_id();
            $data['sid'] = $this->agency_model->get_sid();
            $data['editable'] = true;

            $this->load_main_view('agency_packages_view', $data);
        }
    }

    public function add_package($agency_id, $product_id) {
        if (!empty($agency_id)) {
            $this->load->model('agency_model');
            $this->agency_model->set_agency_id($agency_id);
            $this->load->model('package_model');
            $this->load->helper('form');

            $data['default_packages'] = $this->package_model->get_all_product_packages();

            $products = $this->package_model->get_packages($agency_id, 'product_id');

            $pending_products = $this->package_model->get_pending_packages($agency_id, 'product_id');

            if (isset($products[$product_id]) && !isset($pending_products[$product_id])) {
                $data['new_product'] = false;
                $data['product'] = $products[$product_id][0];
            } else {
                $data['new_product'] = true;
                
                if (isset($pending_products[$product_id])) {
                    $data['product'] = $pending_products[$product_id][0];
                } else {
                    $data['product'] = array('product_id' => $product_id);
                }
            }

            $data['agency_id'] = $agency_id;
            $data['group_billing_id'] = $this->agency_model->get_group_billing_id();
            $data['groups'] = $this->agency_model->get_agency_groups();
            
            echo $this->load->view('edit_product_packages_view', $data, true);
        }
    }

    public function new_product_registrations_list() {
        $pending_packages = $this->package_model->get_all_pending_products();
        $table = '';

        if (empty($pending_packages)) {
            $table .= '<tr><td colspan="10">There are no pending product registrations</td>';
        }

        $this->load->model('agency_model');

        foreach ($pending_packages as $pending_package) {
            $this->agency_model->set_agency_id($pending_package['agency_id']);
            
            $agency_name = $this->agency_model->get_agency_name();
            $sid = $this->agency_model->get_sid();

            $table .= '<tr agency_id="' . $pending_package['agency_id'] . '">';
            $table .= '<td>' . $agency_name . '</td>';
            $table .= '<td>' . $sid . '</td>';
            $table .= '<td>' . $this->common_model->product_id2name[$pending_package['product_id']] . '</td>';
            $table .= '<td>' . $pending_package['inserted_date'] . '</td>';
            $table .= '<td>' . $pending_package['inserted_by'] . '</td>';
            $table .= '<td>' . ($pending_package['package_type'] == 'CP' ? 'Custom' : $pending_package['package_type']) . '</td>';
            $table .= '<td><center><i class="' . ($pending_package['startup_paid'] ? 'icon-thumbs-up-alt text-ok' : 'icon-thumbs-down-alt text-error') . ' icon-large"></i></center></td>';
            $table .= '<td><center><button class="btn btn-' . ($pending_package['status'] == 1 ? 'success' : ($pending_package['status'] == 2 ? 'danger' : 'warning')) . ' btn-xs reg_status_update" pp_id="' . $pending_package['id'] . '" status_id="' . $pending_package['status'] . '">' . $pending_package['status_desc'] . '</button></center></td>';
            
            if (strtolower($this->session->userdata('viewer_access')) === 'sales' || strtolower($this->session->userdata('viewer_access')) === 'administrator') {
                $table .= '<td><a href="' . base_url() . 'index.php/sales/account_details/' . $pending_package['sales_id'] . '" target="_blank">' . $pending_package['account_name'] . '</a></td>';
            }

            $table .= '<td class="center"><div class="dropdown access-dropdown">'
                    . '<a class="dropdown-toggle nostyle" data-toggle="dropdown" data-target="#" href="#">'
                    . '<span class="icon-caret-down" style="padding:10px"></span></a>'
                    . '<ul class="dropdown-menu left pull-right" role="menu">'
                    . '<li class="view_notes" agency_id="' . $pending_package['agency_id'] . '"><a href="#"><i class="icon-pushpin"></i>&nbsp;&nbsp; View Notes</a></li>'
                    . '<li><a href="' . base_url() . 'index.php/registration/print_order/' . $pending_package['agency_id'] . '/' . $pending_package['product_id'] . '" target="_blank"><i class="icon-print"></i>&nbsp;&nbsp; Print Order</a></li>'
                    . '<li><a href="' . base_url() . 'index.php/agency/edit_view/' . $pending_package['agency_id'] . '" target="_blank"><i class="icon-pencil"></i>&nbsp;&nbsp; Review / Edit Agency</a></li>'
                    . '<li><a href="#" onclick="add_product_package(' . $pending_package['agency_id'] . ', ' . $pending_package['product_id'] . ')"><i class="icon-shopping-cart"></i>&nbsp;&nbsp; Edit Package</a></li>'
                    . '<li><a href="#" onclick="delete_pending_package(' . $pending_package['agency_id'] . ', ' . $pending_package['product_id'] . ')"><i class="icon-trash"></i>&nbsp;&nbsp; Delete this Package</a></li></ul></div>'
                    . '</td>';
            $table .= '</tr>';
        }
        
        return $table;
    }

    public function new_product_registrations_view() {
        $data['pending_packages_table_body'] = $this->new_product_registrations_list();
        
        $this->load_main_view('new_product_registrations_view', $data);
    }

    public function new_product_registrations_update() {
        echo $this->new_product_registrations_list();
    }

    public function delete_pending_package($agency_id, $product_id) {
        if (!empty($agency_id) && !empty($product_id)) {
            try {
                $this->package_model->delete_pending_package($agency_id, $product_id);
                echo 'ok';
            } catch (Exception $e) {
                echo $e->getMessage();
            }
        }
    }
}
