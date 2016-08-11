<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}
/*
 * Description: Options controller class
 */

class Options extends MY_Controller {

    private $fail_safes = array(
        array('settings' => array('disable_document_delivery' => 0, 'itin_queue_enabled' => 1, 's2g_enabled' => 1), 'products' => array(1, 7, 12, 13, 14, 15, 18), 'name' => 'Document Delivery'),
        array('settings' => array('utm_enabled' => 1, 'verify_enabled' => 1, 'store_pre_trip_data' => 1, 'write_to_profile' => 1), 'products' => array(3, 16), 'name' => 'OnTrack'),
        array('settings' => array('enable_queue' => 1), 'products' => array(15), 'name' => 'MTCC Document Delivery'),
        array('settings' => array('enable_aux_pre_trip_queue_scan' => 1), 'products' => array(17), 'name' => 'Aux Pre Trip Queue Scanning')
    );

    function __construct() {
        parent::__construct();
        $this->load->model('options_model');
    }

    public function index() {
        
    }

    public function edit_options($agency_id) {
        $data = $this->options_model->get_onq_options($agency_id);
        $data['agency_id'] = $agency_id;

        foreach ($data as $k => $d) {
            if (strpos($k, '_time') !== false && strpos($k, 'next_') === false) {
                $data[$k] = date('g:i a', strtotime($data[$k]));
            }
        }

        if (!empty($data['next_worklist_scan_time'])) {
            $data['next_worklist_scan_time'] = date('d/m/y H:i:s', strtotime($data['next_worklist_scan_time']));
        }

        if (!empty($data['next_utm_verify_scan_date'])) {
            $data['next_utm_verify_scan_date'] = date('d/m/y H:i:s', strtotime($data['next_utm_verify_scan_date']));
        }

        $this->load->model('common_model');

        $data['agencies'] = $this->common_model->get_agencies();
        $data['onq_templates'] = $this->options_model->get_onq_templates($agency_id);
        $data['utm_vendor_interface_options'] = $this->options_model->get_utm_vendor_interface_options($agency_id);
        $data['vendor_interfaces'] = $this->options_model->get_vendor_interfaces();
        $data['utm_xml_feed_accounts'] = $this->options_model->get_utm_xml_feed_accounts($agency_id);
        $data['onq_pdf_accounts'] = $this->options_model->get_onq_pdf_accounts($agency_id);
        $data['extra_data_commands'] = $this->options_model->get_extra_data_commands($agency_id);
        $data['whosaway_accounts'] = $this->options_model->get_whosaway_accounts($agency_id);
        $data['report_options'] = $this->options_model->get_report_options($agency_id);
        $data['s2g_accounts'] = $this->options_model->get_safetogo_accounts($agency_id);
        $data['ops_accounts'] = $this->options_model->get_ops_accounts($agency_id);
        $data['utm_air_fare_filters'] = $this->options_model->get_utm_air_fare_filters($agency_id);

        $this->load_main_view('onq_options_view', $data);
    }

    public function save_options() {
        $post = $this->input->post();

        $this->onq_settings_fail_safes($post);

        unset($post['select']);
        unset($post['select_all']);
        unset($post['check_all']);

        /*
            Modify by : Stephanie Rodriguez
            Date : 2016-04-28
            Ticket #13676
            Reason : We need to add 5 to whatever the actual timezone is, since Eastern is GMT - 5hrs. 
        */
        if (isset($post['time_zone'])) {
            $post['time_zone'] = floatval($post['time_zone']) + 5;
        }

        if (isset($post['next_worklist_scan_time'])) {
            if (empty($post['next_worklist_scan_time'])) {
                $post['next_worklist_scan_time'] = date('Y-m-d 02:00:00', strtotime('+1 week'));
            } else {
                $scan_time = DateTime::CreateFromFormat('d/m/Y H:i:s', $post['next_worklist_scan_time']);
                $post['next_worklist_scan_time'] = $scan_time->format('Y-m-d H:i:s');
            }
        }

        if (isset($post['next_utm_verify_scan_date'])) {
            if (empty($post['next_utm_verify_scan_date'])) {
                $post['next_utm_verify_scan_date'] = date('Y-m-d 02:00:00', strtotime('+1 week'));
            } else {
                $scan_time = DateTime::CreateFromFormat('d/m/Y H:i:s', $post['next_utm_verify_scan_date']);
                $post['next_utm_verify_scan_date'] = $scan_time->format('Y-m-d H:i:s');
            }
        }

        $this->options_model->save_onq_options($post);
    }

    public function save_template() {
        $post = $this->input->post();
        $template_id = $this->options_model->save_onq_template($post);

        if ($template_id) {
            $template = $this->options_model->get_onq_templates($post['agency_id'], $template_id);

            echo json_encode($template);
        }
    }

    public function delete_template() {
        $template_id = $this->input->post('template_id');

        if (!empty($template_id)) {
            if ($this->options_model->delete_template($template_id)) {
                echo 'ok';
            }
        }
    }

    public function delete_utm_vendor_interface_options() {
        $options_id = $this->input->post('options_id');

        if (!empty($options_id)) {
            if ($this->options_model->delete_utm_vendor_interface_options($options_id)) {
                echo 'ok';
            }
        }
    }

    public function save_utm_vendor_interface_options() {
        $post = $this->input->post();
        $options_id = $this->options_model->save_utm_vendor_interface_options($post);

        if ($options_id) {
            $options = $this->options_model->get_utm_vendor_interface_options($post['agency_id'], $options_id);

            echo json_encode($options);
        }
    }

    public function delete_utm_xml_feed_account() {
        $utm_xml_feed_id = $this->input->post('utm_xml_feed_id');

        if (!empty($utm_xml_feed_id)) {
            if ($this->options_model->delete_utm_xml_feed_accounts($utm_xml_feed_id)) {
                echo 'ok';
            }
        }
    }

    public function save_utm_xml_feed_accounts() {
        $post = $this->input->post();
        $utm_xml_feed_id = $this->options_model->save_utm_xml_feed_accounts($post);

        if ($utm_xml_feed_id) {
            $utm_xml_feed = $this->options_model->get_utm_xml_feed_accounts($post['agency_id'], $utm_xml_feed_id);

            echo json_encode($utm_xml_feed);
        }
    }

    public function delete_onq_pdf_account() {
        $pdf_id = $this->input->post('pdf_id');
        if (!empty($pdf_id)) {
            if ($this->options_model->delete_onq_pdf_account($pdf_id)) {
                echo 'ok';
            }
        }
    }

    public function save_onq_pdf_accounts() {
        $post = $this->input->post();
        $pdf_id = $this->options_model->save_onq_pdf_accounts($post);
        
        if ($pdf_id) {
            $pdf = $this->options_model->get_onq_pdf_accounts($post['agency_id'], $pdf_id);
            
            echo json_encode($pdf);
        }
    }

    public function save_profiler_options() {
        $post = $this->input->post();
        $use_sabre_profiles = $post['use_sabre_profiles'];

        unset($post['use_sabre_profiles']);

        $this->onq_settings_fail_safes($post);
        $this->options_model->save_profiler_options($post);
        $this->options_model->save_onq_options(array('use_sabre_profiles' => $use_sabre_profiles, 'agency_id' => $post['agency_id']));
    }

    public function get_table_columns($table_name) {
        $columns = $this->common_model->get_table_columns($table_name);

        echo json_encode($columns);
    }

    public function delete_extra_data_command() {
        $command_id = $this->input->post('command_id');

        if (!empty($command_id)) {
            if ($this->options_model->delete_extra_data_command($command_id)) {
                echo 'ok';
            }
        }
    }

    public function save_extra_data_command() {
        $post = $this->input->post();

        if ($post['prefix_keyword_type'] === 'regex') {
            $post['prefix_keyword'] = 'REGEX' . $post['prefix_keyword'];
        } elseif (strpos($post['prefix_keyword'], 'REGEX') === 0) {
            $post['prefix_keyword'] = str_replace('REGEX', '', $post['prefix_keyword']);
        }

        unset($post['prefix_keyword_type']);
        unset($post['copy_extra_data_command_to']);
        
        $command_id = $this->options_model->save_extra_data_command($post);
        
        if ($command_id) {
            $command = $this->options_model->get_extra_data_commands($post['agency_id'], $command_id);
        
            echo json_encode($command);
        }
    }

    public function copy_extra_data_command() {
        $post = $this->input->post();
        
        if ($post['prefix_keyword_type'] === 'regex') {
            $post['prefix_keyword'] = 'REGEX' . $post['prefix_keyword'];
        } elseif (strpos($post['prefix_keyword'], 'REGEX') === 0) {
            $post['prefix_keyword'] = str_replace('REGEX', '', $post['prefix_keyword']);
        }
        
        $post['agency_id'] = $post['copy_extra_data_command_to'];
        
        unset($post['command_id']);
        unset($post['prefix_keyword_type']);
        unset($post['copy_extra_data_command_to']);
        
        $this->options_model->save_extra_data_command($post);
    }

    public function delete_whosaway_account() {
        $whosaway_id = $this->input->post('whosaway_id');
        
        if (!empty($whosaway_id)) {
            if ($this->options_model->delete_whosaway_account($whosaway_id)) {
                echo 'ok';
            }
        }
    }

    public function save_whosaway_account() {
        $post = $this->input->post();
        $whosaway_id = $this->options_model->save_whosaway_account($post);
        
        if ($whosaway_id) {
            $account = $this->options_model->get_whosaway_accounts($post['agency_id'], $whosaway_id);
        
            echo json_encode($account);
        }
    }

    public function delete_report_option() {
        $report_option_id = $this->input->post('report_option_id');
        
        if (!empty($report_option_id)) {
            if ($this->options_model->delete_report_option($report_option_id)) {
                echo 'ok';
            }
        }
    }

    public function save_report_option() {
        $post = $this->input->post();
        
        foreach ($post as $k => $p) {
            if (strpos($k, 'Date')) {
                if (!empty($post[$k . 'Time'])) {
                    $post[$k] = $p . ' ' . $post[$k . 'Time'];
                    unset($post[$k . 'Time']);
                }
            }
        }
        
        unset($post['copy_report_options_to']);
        
        $report_option_id = $this->options_model->save_report_option($post);
        
        if ($report_option_id) {
            $report = $this->options_model->get_report_options($post['AgencyID'], $report_option_id);
        
            echo json_encode($report);
        }
    }

    public function get_report_option() {
        $post = $this->input->post();
        
        if (!empty($post['agency_id']) && !empty($post['report_option_id'])) {
            $reports = $this->options_model->get_report_options($post['agency_id'], $post['report_option_id']);
        
            if (!empty($reports)) {
                foreach ($reports as $k => $report) {
                    if (strpos($k, 'Date')) {
                        $reports[$k . 'Time'] = date('g:i a', strtotime($report));
                        $reports[$k] = date('Y-m-d', strtotime($report));
                    }
                }
        
                echo json_encode($reports);
            }
        }
    }

    public function copy_report_options() {
        $post = $this->input->post();
        
        if (!empty($post['copy_report_options_to'])) {
            $post['AgencyID'] = $post['copy_report_options_to'];
        
            foreach ($post as $k => $p) {
                if (strpos($k, 'Date')) {
                    if (!empty($post[$k . 'Time'])) {
                        $post[$k] = $p . ' ' . $post[$k . 'Time'];
                        unset($post[$k . 'Time']);
                    }
                }
            }
        
            unset($post['report_option_id']);
            unset($post['copy_report_options_to']);
        
            $this->options_model->save_report_option($post);
        }
    }

    public function save_mtcc_options() {
        $post = $this->input->post();
        
        $this->onq_settings_fail_safes($post);
        $this->options_model->save_mtcc_options($post);
    }

    public function delete_safetogo_account() {
        $s2g_id = $this->input->post('s2g_id');
        
        if (!empty($s2g_id)) {
            if ($this->options_model->delete_safetogo_account($s2g_id)) {
                echo 'ok';
            }
        }
    }

    public function save_safetogo_account() {
        $post = $this->input->post();
        $s2g_id = $this->options_model->save_safetogo_account($post);
        
        if ($s2g_id) {
            $account = $this->options_model->get_safetogo_accounts($post['agency_id'], $s2g_id);
        
            echo json_encode($account);
        }
    }

    public function delete_ops_account() {
        $account_id = $this->input->post('ops_account_id');
        
        if (!empty($account_id)) {
            if ($this->options_model->delete_ops_account($account_id)) {
                echo 'ok';
            }
        }
    }

    public function save_ops_account() {
        $post = $this->input->post();
        $account_id = $this->options_model->save_ops_account($post);
        
        if ($account_id) {
            $account = $this->options_model->get_ops_accounts($post['agency_id'], $account_id);
        
            echo json_encode($account);
        }
    }
    
    public function delete_utm_air_fare_filter() {
        $filter_id = $this->input->post('filter_id');
        
        if (!empty($filter_id)) {
            if ($this->options_model->delete_utm_air_fare_filter($filter_id)) {
                echo 'ok';
            }
        }
    }

    public function save_utm_air_fare_filter() {
        $post = $this->input->post();
        
        unset($post['copy_utm_air_fare_filter_to']);
        
        $filter_id = $this->options_model->save_utm_air_fare_filter($post);
        
        if ($filter_id) {
            $filter = $this->options_model->get_utm_air_fare_filters($post['agency_id'], $filter_id);
        
            echo json_encode($filter);
        }
    }
    
    public function copy_utm_air_fare_filter() {
        $post = $this->input->post();
        $post['agency_id'] = $post['copy_utm_air_fare_filter_to'];
        
        unset($post['copy_utm_air_fare_filter_to']);
        
        $post['filter_id'] = '';
        
        $this->options_model->save_utm_air_fare_filter($post);
    }

    public function get_template_preview() {
        $template_path = $this->input->post('template_path');
        
        if (!empty($template_path) && file_exists($template_path)) {
            echo file_get_contents($template_path);
        } else {
            echo '<div class="error"></div>';
        }
    }

    public function get_unc_path() {
        $file = 'D:\www\random.php';

        list($drive, $path) = explode(':', $file, 2);
        
        $shellOutput = shell_exec('net use');

        $matches = array();
        $regex = '/\b' . $drive . ':\s*([^\s]+)/';
        
        preg_match($regex, $shellOutput, $matches);
        
        $remote = $matches[1];

        $unc = $remote . $path;

        echo "$unc\n";
    }

    /*
        Modify by : Stephanie Rodriguez
        Date : 2016-06-13
        Ticket RFC #13493
    */
    public function get_all_utm_air_fare_filters() {
        $post = $this->input->post();
        $response = $this->options_model->get_all_utm_air_fare_filters($post['filter_id']);

        if (!empty($response)) {
            echo json_encode($response);
        }
    }

    public function delete_all_utm_air_fare_filter() {
        $filter_id = $this->input->post('filter_id');
        
        if (!empty($filter_id)) {
            if ($this->options_model->delete_all_utm_air_fare_filter($filter_id)) {
                echo 'ok';
            }
        }
    }

    # This function is to verify if there's already a filter for taht agency
    public function validate_if_exist() {
        $post = $this->input->post();
        $response = $this->options_model->validate_if_exist($post);
        $html = "";
        
        foreach ($response as $key => $value) {
            if (!empty($value)) {
                $html .= (!empty($value[0]['pcc'])) ? "PCC : ".$value[0]['pcc']."<br>" : '' ;
                $html .= (!empty($value[0]['air_vendor'])) ? "Air Vendor : ".$value[0]['air_vendor']."<br>" : '' ;
                $html .= (!empty($value[0]['filter_amount'])) ? "Filter Amount : ".$value[0]['filter_amount']."<br>" : '' ;
                $html .= (!empty($value[0]['xcl_filter_gds_command'])) ? "Filter GDS Command : ".$value[0]['xcl_filter_gds_command']."<br>" : '' ;
                $html .= (!empty($value[0]['xcl_filter_keyword'])) ? "Filter Keyboard : ".$value[0]['xcl_filter_keyword']."<br>" : '' ;
                $html .= "<br>";
            }
        }

        echo $html;
    }

    public function copy_all_utm_air_fare_filter() {
        $post = $this->input->post();

        $response = $this->options_model->copy_all_utm_air_fare_filter($post);

        if (!empty($response)) {
            echo "ok";
        }        
    }

    public function insert_update_all_utm_air_fare_filter() {
        $post = $this->input->post();

        $response = $this->options_model->insert_update_all_utm_air_fare_filter($post);

        if (!empty($response)) {
            echo "ok";
        }        
    }

    private function onq_settings_fail_safes($post) {
        // get agency packages
        $this->load->model('package_model');
        $this->load->model('agency_model');
        $this->agency_model->set_agency_id($post['agency_id']);
        
        $group_billing_id = $this->agency_model->get_group_billing_id();
        $packages = $this->package_model->get_packages($post['agency_id'], 'product_id', $group_billing_id, true);
        $pending_packages = $this->package_model->get_pending_packages($post['agency_id'], 'product_id', $group_billing_id);
        $check = array();

        foreach ($this->fail_safes as $key => $fail_safe) {
            foreach ($fail_safe['settings'] as $option_name => $option_value) {
                if (isset($post[$option_name]) && $post[$option_name] == $option_value) {
                    $check[] = $key;
                }
            }
        }
        
        foreach ($check as $k => $key) {
            $fail = 1;
            $pending_fail = 1;
        
            foreach ($this->fail_safes[$key]['products'] as $product_id) {
                if (isset($packages[$product_id])) {
                    $fail = 0;
        
                    break;
                } elseif (isset($pending_packages[$product_id])) {
                    $pending_fail = 0;
                }
            }
        
            if ($fail == 1) {
                echo ($k = count($check) - 1 ? '<br/><br/>' : '') . 'Warning: There are ' . $this->fail_safes[$key]['name'] . ' settings which are enabled (' . implode(', ', array_keys($this->fail_safes[$key]['settings'])) . '). This will start scanning/processing for this agency';
                if ($pending_fail == 1) {
                    echo ', but the agency is not being billed for a ' . $this->fail_safes[$key]['name'] . ' product.';
                } else {
                    echo ', but the registration is still pending. Please make sure to start billing for this product.';
                }
            }
        }
    }

    public function clone_options() {
        $post = $this->input->post();
        
        if ((int) $post['clone_onq_options_table'] === 1) {
            $options = $this->options_model->get_onq_options_row($post['agency_id']);
            $options['agency_id'] = $post['clone_options_to'];
        
            unset($options['onq_options_id']);
            unset($options['access_db_agency_id']);
            unset($options['pcc']);
            unset($options['gds_id']);
        
            $this->options_model->save_onq_options($options);
        }
        
        if ((int) $post['clone_profiler_options'] === 1) {
            $profiler_options = $this->options_model->get_profiler_options_row($post['agency_id']);
            $profiler_options['agency_id'] = $post['clone_options_to'];
        
            unset($profiler_options['profiler_id']);
        
            $this->options_model->save_profiler_options($profiler_options);
        }
        
        if ((int) $post['clone_mtcc_doc_options'] === 1) {
            $mtcc_doc_options = $this->options_model->get_mtcc_options_row($post['agency_id']);
            $mtcc_doc_options['agency_id'] = $post['clone_options_to'];
        
            unset($mtcc_doc_options['options_id']);
        
            $this->options_model->save_mtcc_options($mtcc_doc_options);
        }
        
        if ((int) $post['clone_onq_templates'] === 1) {
            $template_options = $this->options_model->get_onq_templates($post['agency_id']);
        
            if (!empty($template_options)) {
                foreach ($template_options as $k => $template_option) {
                    $template_options[$k]['agency_id'] = $post['clone_options_to'];
        
                    unset($template_options[$k]['template_id']);
                }
        
                $this->options_model->save_onq_template($template_options);
            }
        }
        
        if ((int) $post['clone_onq_pdf_attachments'] === 1) {
            $pdf_options = $this->options_model->get_onq_pdf_accounts($post['agency_id']);
        
            if (!empty($pdf_options)) {
                foreach ($pdf_options as $k => $pdf_option) {
                    $pdf_options[$k]['agency_id'] = $post['clone_options_to'];
        
                    unset($pdf_options[$k]['pdf_id']);
                }
        
                $this->options_model->save_onq_pdf_accounts($pdf_options);
            }
        }
        
        if ((int) $post['clone_utm_xml_feed_accounts'] === 1) {
            $utm_xml_feed_accounts = $this->options_model->get_utm_xml_feed_accounts($post['agency_id']);
        
            if (!empty($utm_xml_feed_accounts)) {
                foreach ($utm_xml_feed_accounts as $k => $utm_xml_feed_account) {
                    $utm_xml_feed_accounts[$k]['agency_id'] = $post['clone_options_to'];
        
                    unset($utm_xml_feed_accounts[$k]['utm_xml_feed_id']);
                }
        
                $this->options_model->save_utm_xml_feed_accounts($utm_xml_feed_accounts);
            }
        }
        
        if ((int) $post['clone_interface_options'] === 1) {
            $interface_options = $this->options_model->get_utm_vendor_interface_options($post['agency_id']);
        
            if (!empty($interface_options)) {
                foreach ($interface_options as $k => $interface_option) {
                    $interface_options[$k]['agency_id'] = $post['clone_options_to'];
        
                    unset($interface_options[$k]['options_id']);
                    unset($interface_options[$k]['interface_short_name']);
                }
        
                $this->options_model->save_utm_vendor_interface_options($interface_options);
            }
        }
    }
}
