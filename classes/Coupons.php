<?php

/* Check the absolute path to the Social Auto Poster directory. */
if ( !defined( 'SAP_APP_PATH' ) ) {
    // If SAP_APP_PATH constant is not defined, perform some action, show an error, or exit the script
    // Or exit the script if required
    exit();
}

/**
 * Payment Class function
 * 
 * A class contains common function to be used to throughout the System
 *
 * @package Social Auto Poster
 * @since 2.0.0
 */
class SAP_Coupons{

	private $db;
	private $table_name;
	public $common;
	public $flash;
	public $settings;
	public $sap_common;


	public function __construct() {

		global $sap_db_connect, $sap_common;

		$this->db = $sap_db_connect;
		$this->table_name = 'sap_coupons';

		$this->flash 		= new Flash();
		$this->common 		= new Common();
		$this->settings 	= new SAP_Settings();
		$this->sap_common 	= $sap_common;

		if( !class_exists('SAP_Settings')){
			require_once CLASS_PATH.'/Settings.php';
		}
		$this->settings = new SAP_Settings();
	}

	/**
	 * Listing page of coupons
	 * 
	 * @package Social Auto Poster
	 * @since 2.0.0
	 */
	public function index() {
			
 		// Includes Html files for coupons list
		if ( !sap_current_user_can('coupons') ) {

			$coupon_type = [
				'fixed_discount' => 'Fixed Discount',
				'percentage_discount' => 'Percentage Discount'
			];
			$coupon_status = [
				'draft' => 'Draft',
				'publish' => 'Publish',
				'used' => 'Used'
			];

			// set the coupon details to empty as no coupon selcted
			$coupon_details = new stdClass();
			$coupon_details->coupon_type = '';
			$coupon_details->coupon_status = '';
			
			$template_path = $this->common->get_template_path('Coupon' . DS . 'index.php' );
			include_once( $template_path );

		}
		else {
			$this->common->redirect('login');
		}
	}

	/**
	 * AJax coupon listing
	 * 
	 * @package Social Auto Poster
	 * @since 2.0.0
	 */
	public function coupons_datasource_response() {


		// Includes Html files for coupons list
		if ( !sap_current_user_can('coupons') ) {			

			$result 	= array();
			$data 		= array();
			$curUserId 	= sap_get_current_user_id();

			try {
				
				$start  = $_GET['start'];
				$length = $_GET['length'];			
				
				$order_column 	=  isset( $_GET['order'][0]['column'] ) ? $_GET['order'][0]['column'] :'';
				$order_dir 		=  isset( $_GET['order'][0]['dir'] ) ? $_GET['order'][0]['dir'] :'';
				
				switch( $order_column ) {

					case '2':
						$orderby = 'coupon_code';
					break;
					case '3':
						$orderby = 'coupon_type';
					break;
					case '4':
						$orderby = 'coupon_amount';
					break;
					case '5':
						$orderby = 'coupon_description';
					break;

					case '6':
						$orderby = 'coupon_expiry_date';
					break;

					case '7':
						$orderby = 'coupon_status';
					break;

					case '8':
						$orderby = 'id';
					break;
				}

				$query = 'SELECT * from '.$this->table_name.' where 1=1 ';

				if( !empty( $_GET['search']['value'] ) ) {
					$search = $_GET['search']['value'];

					$query .= ' AND (coupon_code like "'.$search.'%")';
				}

				if( isset($_GET['searchByCouponType']) && $_GET['searchByCouponType'] != '' ) {

					$searchByCouponType = trim($_GET['searchByCouponType']);

					$query .= 'AND (coupon_type = "'.$searchByCouponType.'")';
					
				}	

				if( isset($_GET['search_coupon_status']) && $_GET['search_coupon_status'] != '' ) {
					
					$search_coupon_status = trim($_GET['search_coupon_status']);

					$query .= 'AND (coupon_status = "'.$search_coupon_status.'")';
				}	

				if( isset($_GET['searchByCouponExpiryDate']) && $_GET['searchByCouponExpiryDate'] != '' ) {
					
					$searchByCouponExpiryDate = trim($_GET['searchByCouponExpiryDate']);

					$query .= 'AND (coupon_expiry_date = "'.$searchByCouponExpiryDate.'")';
				}					

				$query .= ' ORDER BY '.$orderby.' '.$order_dir;
				$query .= ' LIMIT '.$start.' , '.$length;
				
				$result = $this->db->get_results( $query );
				// print_r($result);exit;

				$total_count = $this->db->get_row('SELECT count(*)  as count FROM '.$this->table_name,'ARRAY_A' );

			}
			catch (Exception $e) {
				return $e->getMessage();
			}

			$number = 1;		

			foreach ( $result as $coupon ) {

				
				$checkbox = '<input type="checkbox" name="coupon_id[]" value="' . $coupon->id . '" />';

				$action ='<a class="delete_coupon" id="member_'.$coupon->id.'" aria-data-id="'.$coupon->id.'"><i class="fa fa-trash" aria-hidden="true"></i></a>';
				$action .='<a class="" id="" href="'.SAP_SITE_URL.'/coupons/edit/'.$coupon->id.'"><i class="fa fa-pencil-square-o" aria-hidden="true"></i></a>';
				$coupon_type = '';
				$amount = '';
				if($coupon->coupon_type == 'fixed_discount') {
					$coupon_type = 'Amount';
					$amount = '$'.$coupon->coupon_amount;
				} else if($coupon->coupon_type = 'percentage_discount') {
					$coupon_type = 'Percentage';
					$amount = $coupon->coupon_amount.'%';
				}

				$coupon_status = '<div class="'. strtolower($coupon->coupon_status) .'">'. ucfirst( $coupon->coupon_status ) .'</div>';

				$data[] = array(
					$checkbox,
					$number,
					$coupon->coupon_code,					
					$coupon_type,
					$amount,
					$coupon->coupon_description,
					sap_format_date($coupon->coupon_expiry_date,true),
					$coupon_status,
					$action,
				);
				$number++;
			}

			$results = array(
				"draw" => $_GET['draw'],
				"recordsTotal" => count($result),
				"recordsFiltered" => $total_count->count,
			  	"data"=> $data
			);
			echo json_encode($results);			
		} 
		else {
			$this->common->redirect('login');
		}
	}


	/**
	 * 
	 */

	/**
	 * Delete multiple coupons
	 * 
	 * @package Social Auto Poster
	 * @since 2.0.0
	 */
	public function delete_multiple_coupon() {

		if ( !empty($_REQUEST['id']) ) {
			
			$result = array();
			
			$coupon_id = $_REQUEST['id'];
			foreach ( $coupon_id as $key => $value ) {
				$conditions = array( 'id' => $value );
				$is_deleted = $this->db->delete( $this->table_name, $conditions );
			}

			if ( $is_deleted ) {
				$result = array('status' => '1');
				$this->flash->setFlash($this->sap_common->lang('coupon_delete_msg'), 'success' );
			} else {
				$result = array('status' => '0');
			}

			echo json_encode($result);
			die;
		}
	}


	/**
	 * Delete coupons
	 * 
	 * @package Social Auto Poster
	 * @since 2.0.0
	 */

	public function delete_coupons(){

		$result = array();
		
		$coupon_id = $_REQUEST['coupon_id'];
		$conditions = array('id' => $coupon_id);
		$is_deleted = $this->db->delete( $this->table_name , $conditions );

		if ( $is_deleted ) {
			$result = array('status' => '1');
		}
		else {
			$result = array('status' => '0');
		}
		
		echo json_encode($result);
		die;
	}


	/**
	 * Render add coupon form
	 * 
	 * @package Social Auto Poster
	 * @since 2.0.0
	 */
	public function add_coupon(){		

		if ( !sap_current_user_can('add-coupon') ) {
			$coupon_type = [
				'fixed_discount' => 'Fixed Discount',
				'percentage_discount' => 'Percentage Discount'
			];
            $coupon_status = [
                'publish' => 'Publish',
                'draft' => 'Draft',
                'used' => 'Used'
            ];
			$template_path = $this->common->get_template_path('Coupon' . DS . 'add.php' );
			include_once( $template_path );
		}
		else {
			$this->common->redirect('login');
		}		
	}


	/**
	 * Render edit coupon form
	 * 
	 * @package Social Auto Poster
	 * @since 2.0.0
	 */
	public function edit_coupon( $coupon_data ){


		if( isset( $coupon_data['coupon_id'] ) && !empty( $coupon_data['coupon_id'] ) ){

			$coupon_type = [
				'fixed_discount' => 'Fixed Discount',
				'percentage_discount' => 'Percentage Discount'
			];
			$coupon_status = [
				'draft' => 'Draft',
				'publish' => 'Publish',
				'used' => 'Used'
			];
			$coupon_details = $this->get_coupon_details( $coupon_data['coupon_id'] );
			if ( !sap_current_user_can('edit-coupon') ) {
			
				$template_path = $this->common->get_template_path('Coupon' . DS . 'edit.php' );
				include_once( $template_path );
			}
			else {
				$this->common->redirect('login');
			}
		}		
	}


	/**
	 * Get coupon detail by coupon id
	 * 
	 * Handels coupons
	 * 
	 * @package Social Auto Poster
	 * @since 2.0.0
	 */
	public function get_coupon_details( $coupon_id ){
		
		$return = array();

		if( !empty( $coupon_id ) ){

			$query = 'SELECT * from '.$this->table_name.' where id='.$coupon_id;
			
			$result = $this->db->get_row($query,'ARRAY_A');
			
		}
		return $result;
	}

	/**
	 * Save Coupon data
	 * 	 
	 * 
	 * @package Social Auto Poster
	 * @since 2.0.0
	 */
	public function save_coupon(){

		$error = false;
		// check the required fields are empty or not
		if ( empty(trim($_POST['coupon_code'])) ) {

			$error = true;
			$this->flash->setFlash($this->sap_common->lang('enter_coupon_code'), 'error' );
		}

		if ( !empty(trim($_POST['coupon_code'])) ) {
			$result = $this->check_unique_code(trim($_POST['coupon_code']));
			if($result){
				$error = true;
				$this->flash->setFlash($this->sap_common->lang('enter_unique_coupon_code'), 'error' );
			}
		}

		if ( empty($_POST['coupon_type']) ) {
			$error = true;
			$this->flash->setFlash($this->sap_common->lang('select_coupon_type'), 'error' );
		}

		if ( empty($_POST['coupon_amount']) ) {
			$error = true;
			$this->flash->setFlash($this->sap_common->lang('enter_coupon_amount'), 'error' );
		}

		if ( !empty($_POST['coupon_amount']) ) {
			if($_POST['coupon_type'] == 'percentage_discount' && (int)$_POST['coupon_amount'] > 100)
			{
				$error = true;
				$this->flash->setFlash($this->sap_common->lang('enter_coupon_amount_percentage'), 'error' );
			}
		}

		if ( !isset($_POST['coupon_status']) ) {
			$error = true;
			$this->flash->setFlash($this->sap_common->lang('select_coupon_status'), 'error' );
		}

		// Check if no error
		if( $error ) {
			header( "Location:" . SAP_SITE_URL . "/coupons/add-coupon/" );
			exit;
		}

		if ( !sap_current_user_can('add-coupon') ) {
		
			// Prepare data for store post in DB
			$coupon_data = array(					
				'coupon_code'       	=> trim($_POST['coupon_code']),
				'coupon_type'     		=> $_POST['coupon_type'],
				'coupon_amount'   		=> $_POST['coupon_amount'],
				'coupon_description'	=> !empty($_POST['coupon_description']) ? $_POST['coupon_description'] : '',
				'coupon_expiry_date'	=> !empty($_POST['coupon_expiry_date']) ? $_POST['coupon_expiry_date'] : '',
				'coupon_status'   		=> !empty($_POST['coupon_status']) ? $_POST['coupon_status'] : '',
				'created_date'    		=> date('Y-m-d H:i:s'),
			);

			$coupon_data = $this->db->escape($coupon_data);
			$coupon_data['coupon_expiry_date'] = empty( $coupon_data['coupon_expiry_date'] ) ? NULL : $coupon_data['coupon_expiry_date'];	

			
			if ( $this->db->insert( $this->table_name, $coupon_data ) ) {
				$this->flash->setFlash($this->sap_common->lang('new_coupon_success_msg'), 'success' );
			}
			else{
				$this->flash->setFlash($this->sap_common->lang('something_went_wrong'), 'error' );
			}
			header( "Location:" . SAP_SITE_URL . "/coupons/" );
		}
		else {
			$this->common->redirect('login');
		}		
	}


	/**
	 * Update coupon data
	 * 	 
	 * 
	 * @package Social Auto Poster
	 * @since 2.0.0
	 */
	public function update_coupon(){
		
		$error = false;
		// check the required fields are empty or not
		if ( empty(trim($_POST['coupon_code'])) ) {
			$error = true;
			$this->flash->setFlash($this->sap_common->lang('enter_coupon_code'), 'error' );
		}

		if ( !empty(trim($_POST['coupon_code'])) ) {
			$result = $this->check_unique_code(trim($_POST['coupon_code']), $_POST['coupon_id']);
			if($result){
				$error = true;
				$this->flash->setFlash($this->sap_common->lang('enter_unique_coupon_code'), 'error' );
			}
		}

		if ( empty($_POST['coupon_type']) ) {
			$error = true;
			$this->flash->setFlash($this->sap_common->lang('select_coupon_type'), 'error' );
		}

		if ( empty($_POST['coupon_amount']) ) {
			$error = true;
			$this->flash->setFlash($this->sap_common->lang('enter_coupon_amount'), 'error' );
		}

		if ( !empty($_POST['coupon_amount']) ) {
			if($_POST['coupon_type'] == 'percentage_discount' && (int)$_POST['coupon_amount'] > 100)
			{
				$error = true;
				$this->flash->setFlash($this->sap_common->lang('enter_coupon_amount_percentage'), 'error' );
			}
		}

		if ( !isset($_POST['coupon_status']) ) {
			$error = true;
			$this->flash->setFlash($this->sap_common->lang('select_coupon_status'), 'error' );
		}

		// Check if no error
		if( $error ) {
			header( "Location:" . SAP_SITE_URL . "/coupons/edit/".$_POST['coupon_id'] );
			exit;
		}

		if ( !sap_current_user_can('edit-coupon') ) {
		
			// Prepare data for store post in DB
			$coupon_data = array(					
				'coupon_code'       	=> $_POST['coupon_code'],
				'coupon_type'     		=> $_POST['coupon_type'],
				'coupon_amount'   		=> $_POST['coupon_amount'],
				'coupon_description'	=> !empty($_POST['coupon_description']) ? $_POST['coupon_description'] : '',
				'coupon_expiry_date'	=> !empty($_POST['coupon_expiry_date']) ? $_POST['coupon_expiry_date'] : '',
				'coupon_status'   		=> !empty($_POST['coupon_status']) ? $_POST['coupon_status'] : '',
				'created_date'    		=> date('Y-m-d H:i:s'),
			);

			$coupon_data = $this->db->escape($coupon_data);		

			if ( $this->db->update( $this->table_name, $coupon_data,array('id' => $_POST['coupon_id'])  ) ) {
				$this->flash->setFlash($this->sap_common->lang('edit_coupon_success_msg'), 'success' );
			}
			else{
				$this->flash->setFlash($this->sap_common->lang('something_went_wrong'), 'error' );
			}
			header( "Location:" . SAP_SITE_URL . "/coupons/" );
		}
		else {
			$this->common->redirect('login');
		}		
	}

	/**
	 * Check uinque coupon code
	 * 	 
	 * 
	 * @package Social Auto Poster
	 * @since 2.0.0
	 */
	public function check_unique_code($coupon_code, $id = -1){

		$error = false;

		/* $query = 'SELECT * from '.$this->table_name.' where coupon_code="'.$coupon_code.'"';
		$result = $this->db->get_results( $query ); */
		$tot_rows = 0;
		if($id != -1) {
			$tot_rows = $this->db->num_rows('SELECT * from '.$this->table_name.' where coupon_code="'.$coupon_code.'" and id !='.$id);
		} else {
			$tot_rows = $this->db->num_rows('SELECT * from '.$this->table_name.' where coupon_code="'.$coupon_code.'"');
		}
		if ( $tot_rows > 0 ) {
			return true;
		}
		else{
			return false;
		}
	}
}