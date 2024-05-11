<?php

/* Check the absolute path to the Social Auto Poster directory. */
if ( !defined( 'SAP_APP_PATH' ) ) {
    // If SAP_APP_PATH constant is not defined, perform some action, show an error, or exit the script
    // Or exit the script if required
    exit();
}

/**
 * Logs Class
 * 
 * Responsible for all function related to posts
 *
 * @package Social Auto Poster
 * @since 1.0.0
 */
class SAP_Logs {

    //Set Database variable
    private $db;
    //Set table name
    private $table_name;
    public $flash;
    public $common;
    private $settings;
    public $sap_common, $posts;

    public function __construct() {
       
        global $sap_db_connect,$sap_common;
        
        if (!class_exists('SAP_Posts')) {
            include_once( CLASS_PATH . 'Posts.php');
        }

        $this->db = $sap_db_connect;
        $this->flash = new Flash();
        $this->common = new Common();
        $this->posts = new SAP_Posts();
        $this->table_name = 'sap_logs';
        $this->settings = new SAP_Settings();
        $this->sap_common = $sap_common;

        
    }

    /**
     * Listing page of logs
     * Handels to Logs html view
     * 
     * @package Social Auto Poster
     * @since 1.0.0
     */
    public function index() {

        //Includes Html files for Posts list
        if ( sap_current_user_can('logs') ) {
            if (isset($_SESSION['user_details']) && !empty($_SESSION['user_details'])){                
                $template_path = $this->common->get_template_path('Logs' . DS . 'index.php' );
                include_once( $template_path );
            } 
        }
        else {
            $this->common->redirect('login');
        }
    }

    /**
     * Listing page of Report
     * Handels to Report html view
     * 
     * @package Social Auto Poster
     * @since 1.0.0
     */
    public function report() {

        //Includes Html files for Posts list
        if ( sap_current_user_can('report') ) {
            if (isset($_SESSION['user_details']) && !empty($_SESSION['user_details'])){

                $template_path = $this->common->get_template_path('Report' . DS . 'index.php' );
                include_once( $template_path );
                
            } 
        }
        else {
            $this->common->redirect('login');
        }    
    }

    /**
     * Add logs
     * Handels to add logs
     * 
     * @package Social Auto Poster
     * @since 1.0.0
     */
    public function add_log($social_type, $log = array(), $posting_type = '', $user_id = '') {

        if ( empty($user_id) ) {
            $user_id = sap_get_current_user_id();
        }

        $general_settings = $this->settings->get_user_setting('sap_general_options', $user_id);
        $enable_logs = ( isset($general_settings['social_posting_logs']) && $general_settings['social_posting_logs'] == '1' ) ? true : false;

        if (!empty($log) && $enable_logs) {

            $prepare_data['user_id'] = $user_id;
            $prepare_data['social_source'] = is_array($log) ? serialize($log) : $log;
            $prepare_data['social_type'] = $social_type;
            $prepare_data['posting_type'] = !empty($posting_type) ? $posting_type : 1;
            $prepare_data['created'] = date('Y-m-d H:i:s');
            $prepare_data['status'] = 1;

            $prepare_data = $this->db->escape($prepare_data);

            if ($this->db->insert($this->table_name, $prepare_data)) {
                return $this->db->lastid();
            } else {
                $this->flash->setFlash($this->sap_common->lang('errorsaving_log'), 'error');
            }
        }

        return false;
    }

    /**
     * Get log details by id
     * 
     * Handels log by id and get full details 
     * 
     * @package Social Auto Poster
     * @since 1.0.0
     */
    public function get_log($log_id, $object = false) {

        $result = array();
        if (!empty($log_id)) {
            try {
                $result = $this->db->get_results("SELECT * FROM " . $this->table_name . " where `id` = '{$log_id}';", $object);
            } catch (Exception $e) {
                return $e->getMessage();
            }
            //Return result
            return $result;
        }
    }

    /**
     * Get all logs
     * 
     * Handels logs listing
     * 
     * @package Social Auto Poster
     * @since 1.0.0
     */
    public function get_logs() {
        $result = array();

        try {
            $user_id = sap_get_current_user_id();
            $result = $this->db->get_results("SELECT * FROM " . $this->table_name . " WHERE `user_id` = {$user_id} ORDER BY `created` DESC");
        } catch (Exception $e) {
            return $e->getMessage();
        }

        //Return result
        return $result;
    }

    /**
     * Delete Log
     * 
     * Handels log delete functionality
     * 
     * @package Social Auto Poster
     * @since 1.0.0
     */
    public function delete_log() {

        if (!empty($_REQUEST['log_id'])) {

            $result = array();
            $log_id = $_REQUEST['log_id'];
            $conditions = array('id' => $log_id);
            $is_deleted = $this->db->delete($this->table_name, $conditions);

            if ($is_deleted) {
                $result = array('status' => '1');
            } else {
                $result = array('status' => '0');
            }
            echo json_encode($result);
            die;
        }
    }

    /**
     * Delete Multiple Logs
     * 
     * Handels logs delete functionality
     * 
     * @package Social Auto Poster
     * @since 1.0.0
     */
    public function delete_multiple_logs() {

        if (!empty($_REQUEST['id'])) {
            $result = array();
            $log_ids = $_REQUEST['id'];
            foreach ($log_ids as $key => $value) {
                $conditions = array('id' => $value);
                $is_deleted = $this->db->delete($this->table_name, $conditions);
            }
            if ($is_deleted) {
                $result = array('status' => '1');
                $this->flash->setFlash($this->sap_common->lang('selected_logs_deleted'), 'success');
            } else {
                $result = array('status' => '0');
            }
            echo json_encode($result);
            die;
        }
    }

    /**
     * Social logs view in details
     * 
     * Handels logs details
     * 
     * @package Social Auto Poster
     * @since 1.0.0
     */
    public function log_view_details() {

        if (!empty($_REQUEST['log_id'])) {

            $final_result = array();
            $log_id = $_REQUEST['log_id'];

            $result = $this->get_log($log_id);
            ob_start();
            if (!empty($result[0]->social_source)) {

                $social_source = is_array( $result[0]->social_source ) ? $result[0]->social_source : array();

                if ($this->common->is_serialized($result[0]->social_source)) {
                    $social_source = unserialize($result[0]->social_source);
                }

                $date_created = !empty($result[0]->created) ? $result[0]->created : '';

                $social_source['Date/Time'] = date('Y-m-d h:i A', strtotime($date_created));

                foreach ($social_source as $key => $value) {
                     
                    if (isset($value) && $value != '') {

                        if ($key == 'display_name') {
                            $key = 'Account name';
                        }
                        if ($key == 'account_id') {
                            $key = 'Account id';
                        }
                        ?>
                        <tr>

                            <td class="<?php echo str_replace(" ", '_', $key); ?>"><strong><?php echo ucfirst($key); ?></strong></td>   


                            <td>
                                <?php
                                if ($key == 'image') {
                                    echo '<img class="img-responsive img-thumbnail" src="' . $value . '">';
                                } elseif ($key == 'link' && $value != '') {
                                    echo '<a href="' . $value . '" target="_blank">' . $value . '</a>';
                                } else {
                                    echo $value;
                                }
                                ?>
                            </td>
                        </tr>
                        <?php
                    }
                }
            }
            $final_result['html'] = ob_get_clean();

            echo json_encode($final_result);
            die;
        }
    }

    /**
     * Get all logs
     * 
     * Handels logs listing
     * 
     * @package Social Auto Poster
     * @since 1.0.0
     */
    public function sap_poster_logs_graph() {

        $prepare = $query_var = $final_array = array();
        $where = '';        

        $social_types = sap_get_users_networks();

        $social_types_list = array();
        if ( !empty($social_types) ) { // Check social types are not empty

            foreach ($social_types as $social_key => $social_name) {

                $value = $social_name;
                $label = $social_name;

                switch ($social_name) {
                    case 'facebook':
                        $label = sap_get_networks_label('facebook');
                        break;
                     case 'twitter':
                        $label = sap_get_networks_label('twitter');
                        break;
                     case 'linkedIn':
                        $label = sap_get_networks_label('linkedIn');
                        break;
                     case 'tumblr':
                        $label = sap_get_networks_label('tumblr');
                        break; 
                    case 'reddit':
                        $label = sap_get_networks_label('reddit');
                        break; 
                     case 'gmb':
                        $value = 'googlemybusiness';
                        $label = sap_get_networks_label('gmb');
                        break;
                    case 'pinterest':
                        $label = sap_get_networks_label('pinterest');
                        break;
                    case 'instagram':
                        $label = sap_get_networks_label('instagram');
                        break;
                    case 'youtube':
                        $label = sap_get_networks_label('youtube');
                        break;
                    case 'blogger':
                        $label = sap_get_networks_label('blogger');
                        break;
                }

                $social_types_list[$value] = $label;
            }
        }
        
        if (!empty($_REQUEST['social_type'])) {
            $final_array[] = array('Month', $social_types_list[$_REQUEST['social_type']]);
        } 
        else {            
            $final_array[] = array_merge( array( 'Month' ), array_values($social_types_list) );
        }

        //Check id exist
        if (!empty($_REQUEST['social_type'])) {
            $query_var[] = " social_type ='" . $_REQUEST['social_type'] . "'";
        }

        if (!empty($_REQUEST['filter_type']) && $_REQUEST['filter_type'] == 'custom') {

            //Check Start date and set it in query
            if (!empty($_REQUEST['start_date'])) {
                $query_var[] = " `created` > '" . date('Y-m-d H:i:s', strtotime($_REQUEST['start_date'])) . "'";
            }

            //Check End date and set it in query
            if (!empty($_REQUEST['end_date'])) {
                $query_var[] = " `created` < '" . date('Y-m-d 24:59:59', strtotime($_REQUEST['end_date'])) . "'";
            }

            //Check Start date and End date if empty then month set
            if (empty($_REQUEST['start_date']) && empty($_REQUEST['end_date'])) {
                $query_var[] = ' MONTH(created) = MONTH(CURRENT_DATE())';
            }
        } else if (!empty($_REQUEST['filter_type']) && $_REQUEST['filter_type'] == 'current_year') {
            //Set Current year
            $query_var[] = ' YEAR(created) =  YEAR(CURDATE())';
        } else if (!empty($_REQUEST['filter_type']) && $_REQUEST['filter_type'] == 'last_7days') {
            //Set Current Week
            //$query_var[] = ' WEEK(created) = WEEK(CURRENT_DATE())';
            $strtadate = date('Y-m-d', strtotime('-7 days'));
            $end_date = date('Y-m-d');
            if (!empty($strtadate)) {
                $query_var[] = " `created` > '" . date('Y-m-d H:i:s', strtotime($strtadate)) . "'";
            }

            //Check End date and set it in query
            if (!empty($end_date)) {
                $query_var[] = " `created` < '" . date('Y-m-d 24:59:59', strtotime($end_date)) . "'";
            }
        } else {
            //Default set current month
            $query_var[] = ' MONTH(created) = MONTH(CURRENT_DATE())';
        }

        // add by default user
        $user_id = sap_get_current_user_id();
        $query_var[] = ' user_id = ' . $user_id;

        if (!empty($query_var)) {
            $where = 'WHERE ' . implode(' AND ', $query_var);
        }

        
        $results = $this->db->get_results("SELECT * FROM " . $this->table_name . " " . $where . " ORDER BY `created` ASC");
        //Check data exist
        if (!empty($results)) {

            foreach ($results as $key => $value) {

                $post_date = date('d-M-Y', strtotime($value->created));
                $social_type = $value->social_type;
                //Check post network type
                if (!empty($prepare[$post_date][$social_type])) {
                    $prepare[$post_date][$social_type] = $prepare[$post_date][$social_type] + 1;
                } else {
                    $prepare[$post_date][$social_type] = 1;
                }
            }
            //Finalize prepared data
            foreach ($prepare as $key => $value) {

                $facebook = !empty($value['facebook']) ? $value['facebook'] : 0;
                $twitter = !empty($value['twitter']) ? $value['twitter'] : 0;
                $linkedin = !empty($value['linkedIn']) ? $value['linkedIn'] : 0;
                $tumblr = !empty($value['tumblr']) ? $value['tumblr'] : 0;
                $googlemybusiness = !empty($value['googlemybusiness']) ? $value['googlemybusiness'] : 0;
                $pinterest = !empty($value['pinterest']) ? $value['pinterest'] : 0;
                $reddit = !empty($value['reddit']) ? $value['reddit'] : 0;
                $instagram = !empty($value['instagram']) ? $value['instagram'] : 0;
                $youtube = !empty($value['youtube']) ? $value['youtube'] : 0;
                $blogger = !empty($value['blogger']) ? $value['blogger'] : 0;
                if (!empty($_REQUEST['social_type'])) {
                    if( $_REQUEST['social_type'] == 'linkedin' ){
                        $_REQUEST['social_type'] = 'linkedIn';
                    }
                    
                    $final_array[] = array($key, $value[$_REQUEST['social_type']],$value[$_REQUEST['social_type']]);
                }
                else {
                    
                    $social_slugs = array_keys( $social_types_list );
                    $array = array( $key );
                    if( in_array('facebook', $social_slugs) ) {
                        $array[] = !is_null($facebook) ? $facebook : 0;
                        $array[] = !is_null($facebook) ? $facebook : 0;
                    }
                    if( in_array('twitter', $social_slugs) ) {
                        $array[] = !is_null($twitter) ? $twitter : 0;
                        $array[] = !is_null($twitter) ? $twitter : 0;
                    }
                    if( in_array('linkedin', $social_slugs) ) {
                        $array[] = !is_null($linkedin) ? $linkedin : 0;
                        $array[] = !is_null($linkedin) ? $linkedin : 0;
                    }
                    if( in_array('tumblr', $social_slugs) ) {
                        $array[] = !is_null($tumblr) ? $tumblr : 0;
                        $array[] = !is_null($tumblr) ? $tumblr : 0;
                    }
                    if( in_array('googlemybusiness', $social_slugs) ) {
                        $array[] = !is_null($googlemybusiness) ? $googlemybusiness : 0;
                        $array[] = !is_null($googlemybusiness) ? $googlemybusiness : 0;
                    }
                    if( in_array('pinterest', $social_slugs) ) {
                        $array[] = !is_null($pinterest) ? $pinterest : 0;
                        $array[] = !is_null($pinterest) ? $pinterest : 0;
                    }
                    if( in_array('reddit', $social_slugs) ) {
                        $array[] = !is_null($reddit) ? $reddit : 0;
                        $array[] = !is_null($reddit) ? $reddit : 0;
                    }
                    if( in_array('instagram', $social_slugs) ) {
                        $array[] = !is_null($instagram) ? $instagram : 0;
                        $array[] = !is_null($instagram) ? $instagram : 0;
                    }
                    if( in_array('youtube', $social_slugs) ) {
                        $array[] = !is_null($youtube) ? $youtube : 0;
                        $array[] = !is_null($youtube) ? $youtube : 0;
                    }
                    if( in_array('blogger', $social_slugs) ) {
                        $array[] = !is_null($blogger) ? $blogger : 0;
                        $array[] = !is_null($blogger) ? $blogger : 0;
                    }

                    $final_array[] = $array;
                }
            }
          
        } else {
            
            if (!empty($_REQUEST['social_type'])) {
                $final_array[] = array(date('d-M-Y'), 0, 0);
            } else {
                $final_array[] = array('No Data Found', 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0);
            }
        }
        
        if(!empty($_REQUEST['filter_type']) && ($_REQUEST['filter_type'] == 'current_year' || $_REQUEST['filter_type'] == 'current_month')) {
            if(!empty($final_array)){
                $count_array = array();
                $count_total = count($final_array);
                $newvaluesum1 = 0;
                $newvaluesum2 = 0;
                $newvaluesum3 = 0;
                $newvaluesum4 = 0;
                $newvaluesum5 = 0;
                $newvaluesum6 = 0;
                $newvaluesum7 = 0;
                $newvaluesum8 = 0;
                foreach ($final_array as $key => $value) {
                    if($key > 0){
                        
                        $final_array[$key][1] += $newvaluesum1;
                        $newvaluesum1 += $value[1];
                        $final_array[$key][2] = $final_array[$key][1];
                        
                        $final_array[$key][3] += $newvaluesum2;
                        $newvaluesum2 += $value[3];
                        $final_array[$key][4] = $final_array[$key][3];

                        $final_array[$key][5] += $newvaluesum3;
                        $newvaluesum3 += $value[5];
                        $final_array[$key][6] = $final_array[$key][5];

                        $final_array[$key][7] += $newvaluesum4;
                        $newvaluesum4 += $value[7];
                        $final_array[$key][8] = $final_array[$key][7];

                        $final_array[$key][9] += $newvaluesum5;
                        $newvaluesum5 += $value[9];
                        $final_array[$key][10] = $final_array[$key][9];

                        $final_array[$key][11] += $newvaluesum5;
                        $newvaluesum5 += $value[11];
                        $final_array[$key][12] = $final_array[$key][11];
                        
                        $final_array[$key][13] += $newvaluesum6;
                        $newvaluesum6 += $value[13];
                        $final_array[$key][14] = $final_array[$key][13];
                        
                        $final_array[$key][15] += $newvaluesum7;
                        $newvaluesum7 += $value[15];
                        $final_array[$key][16] = $final_array[$key][15];

                        $final_array[$key][17] += $newvaluesum8;
                        $newvaluesum8 += $value[17];
                        $final_array[$key][18] = $final_array[$key][17];
                    }
                }
            }  
            if($_REQUEST['filter_type'] == 'current_year'){
                $final_array[0][0] = 'Year';
                $final_array[$count_total - 1][0] = date('Y');
            }elseif ($_REQUEST['filter_type'] == 'current_month') {
                $final_array[0][0] = 'Month';
                $final_array[$count_total - 1][0] = date('F');
            }
            if (!empty($_REQUEST['social_type'])) {
                echo json_encode(array($final_array[0],array($final_array[$count_total - 1][0],$final_array[$count_total - 1][1],$final_array[$count_total - 1][1])));
            }else{
                echo json_encode(array($final_array[0],$final_array[$count_total - 1]));
            }
        }else{
            echo json_encode($final_array);
        }
        exit();
    }

}
