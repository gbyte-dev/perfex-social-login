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
class SAP_Payments{

	private $db;
	private $table_name;
	public $common;
	public $flash;
	public $settings;
	public $sap_common, $plan_table_name, $_table_membership, $_table_payment_history, $plan;
	public $users;
	public $memebership;


	public function __construct() {

		global $sap_db_connect, $sap_common;;

		$this->db = $sap_db_connect;
		$this->table_name = 'sap_users';
		$this->plan_table_name = 'sap_plans';
		$this->_table_membership = 'sap_membership';
		$this->_table_payment_history = 'sap_payment_history';

		$this->flash 		= new Flash();
		$this->common 		= new Common();
		$this->settings 	= new SAP_Settings();
		$this->sap_common 	= $sap_common;

		if( !class_exists('SAP_Plans')){
			require_once CLASS_PATH.'/Plans.php';
		}
		$this->plan = new SAP_Plans();

		if( !class_exists('SAP_Membership')){
			require_once CLASS_PATH.'/Membership.php';
		}

		if( !class_exists('SAP_Users')){
			require_once CLASS_PATH.'/Users.php';
		}
		

		if( !class_exists('SAP_Settings')){
			require_once CLASS_PATH.'/Settings.php';
		}
		$this->settings = new SAP_Settings();
	}

	/**
	 * Listing page of Users
	 * 
	 * @package Social Auto Poster
	 * @since 2.0.0
	 */
	public function index() {
			
 		// Includes Html files for Posts list
		if ( !sap_current_user_can('payments') ) {
			$allplan = $this->plan->get_plans();
			$payment_gateway 	= $this->settings->get_options('payment_gateway');

			$template_path = $this->common->get_template_path('Payments' . DS . 'index.php' );
			include_once( $template_path );

		}
		else {
			$this->common->redirect('login');
		}
	}

	/**
	 * AJax Payment listing
	 * 
	 * @package Social Auto Poster
	 * @since 2.0.0
	 */
	public function payments_datasource_response() {


		// Includes Html files for Posts list
		if ( !sap_current_user_can('payments') ) {			

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
						$orderby = 'p.customer_name';
					break;
					case '3':
						$orderby = 'p.customer_email';
					break;
					case '4':
						$orderby = 'plan.name';
					break;
					case '5':
						$orderby = 'p.gateway';
					break;

					case '6':
						$orderby = 'u.role';
					break;

					case '7':
						$orderby = 'u.created';
					break;

					case '8':
						$orderby = 'p.amount';
					break;

					case '9':
						$orderby = 'p.created_date';
					break;

					case '11':
						$orderby = 'p.id';
					break;
				}

				$query = 'SELECT p.*,plan.name as plan_name,u.email,u.id as user_id from '.$this->_table_payment_history.' as p  INNER JOIN '.$this->plan_table_name.' as plan ON p.plan_id = plan.id   LEFT JOIN sap_users as u ON p.user_id = u.id  where 1=1 ';

				if( !empty( $_GET['search']['value'] ) ) {
					$search = $_GET['search']['value'];

					$query .= ' AND (p.customer_name like "'.$search.'%" OR u.email like "'.$search.'%")';
				}

				if( isset($_GET['searchByMembershipLevel']) && $_GET['searchByMembershipLevel'] != '' ) {

					$searchByMembershipLevel = trim($_GET['searchByMembershipLevel']);

					$query .= 'AND (plan.name = "'.$searchByMembershipLevel.'")';
					
				}	

				if( isset($_GET['search_payment_status']) && $_GET['search_payment_status'] != '' ) {
					
					$search_payment_status = trim($_GET['search_payment_status']);

					$query .= 'AND (p.payment_status = "'.$search_payment_status.'")';
				}	

				if( isset($_GET['searchByGateway']) && $_GET['searchByGateway'] != '' ) {
					
					$searchByGateway = trim($_GET['searchByGateway']);

					$query .= 'AND (p.gateway = "'.$searchByGateway.'")';
				}

				if( isset($_GET['search_payment_status']) && $_GET['search_payment_status'] != '' ) {
					
					$search_payment_status = trim($_GET['search_payment_status']);

					$query .= 'AND (p.payment_status = "'.$search_payment_status.'")';
				}					

				$query .= ' ORDER BY '.$orderby.' '.$order_dir;
				$query .= ' LIMIT '.$start.' , '.$length;
				
				$result = $this->db->get_results( $query );

				$total_count = $this->db->get_row('SELECT count(*)  as count FROM '.$this->_table_membership,'ARRAY_A' );

			}
			catch (Exception $e) {
				return $e->getMessage();
			}

			$number = 1;		

			foreach ( $result as $payment ) {

				if( $curUserId == $payment->id ) {
					$checkbox = '<input type="checkbox" value="current" />';
				} else{
					$checkbox = '<input type="checkbox" name="payment_id[]" value="' . $payment->id . '" />';
				}

				$test_mode = $this->settings->get_options('stripe_test_mode');
				$stripe_endpoint = ( $test_mode == 'yes' ) ? 'https://dashboard.stripe.com/test/' : 'https://dashboard.stripe.com/';

				$transaction_id = $payment->transaction_id;			
				
				if( strpos($transaction_id, 'sub_') !== false ){
					$transaction_id = '<a target="__blank" href="'.$stripe_endpoint.'subscriptions/'.$payment->transaction_id.'">'.$payment->transaction_id.'</a>';
				}
				elseif( strpos($transaction_id, 'ch_') !== false  ){
					$transaction_id = '<a target="__blank" href="'.$stripe_endpoint.'payments/'.$payment->transaction_id.'">'.$payment->transaction_id.'</a>';
				}
				$invoice = '<a target="_blank" href="'.SAP_SITE_URL.'/payment-invoice/'.$payment->id.'" class="view-Status">View</i></a>';

				$action ='<a class="delete_payment_histroy" id="member_'.$payment->id.'" aria-data-id="'.$payment->id.'"><i class="fa fa-trash" aria-hidden="true"></i></a>';
				if( !empty( sap_get_users_by_id($payment->user_id) )){
					$action .='<a class="" id="" href="'.SAP_SITE_URL.'/payments/edit/'.$payment->id.'"><i class="fa fa-pencil-square-o" aria-hidden="true"></i></a>';
				}

				$user_id = 0;
				if( !empty( $payment->user_id) ){
					$user_id = $payment->user_id;
				}
				$customer_name = '<a href="'.SAP_SITE_URL.'/member/edit/'.$user_id.'">'.$payment->customer_name.'</a>';
				$amount = round($payment->amount,2);
				if($payment->coupon_discount_amount != '' || $payment->coupon_discount_amount != null) {
					$amount = round($payment->amount,2) > round($payment->coupon_discount_amount,2) ? round($payment->amount,2) - round($payment->coupon_discount_amount,2) : "0";
				}
				$data[] = array(
					$checkbox,
					$number,
					$customer_name,					
					$payment->customer_email,
					$payment->plan_name,					
					ucfirst($payment->gateway),
					$transaction_id,
					get_payment_status_label($payment->payment_status),
					"$".round($payment->amount,2),
					sap_format_date($payment->payment_date,true),
					$invoice,
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
	 * Delete multiple payment
	 * 
	 * @package Social Auto Poster
	 * @since 2.0.0
	 */
	public function delete_multiple_payment() {

		if ( !empty($_REQUEST['id']) ) {
			
			$result = array();
			
			$payment_id = $_REQUEST['id'];
			foreach ( $payment_id as $key => $value ) {
				$conditions = array( 'id' => $value );
				$is_deleted = $this->db->delete( $this->_table_payment_history, $conditions );
			}

			if ( $is_deleted ) {
				$result = array('status' => '1');
				$this->flash->setFlash($this->sap_common->lang('payment_history_delete_msg'), 'success' );
			} else {
				$result = array('status' => '0');
			}

			echo json_encode($result);
			die;
		}
	}


	/**
	 * Delete Payment history
	 * 
	 * @package Social Auto Poster
	 * @since 2.0.0
	 */

	public function delete_payments(){

		$result = array();
		
		$payment_id = $_REQUEST['payment_id'];
		$conditions = array('id' => $payment_id);
		$is_deleted = $this->db->delete( $this->_table_payment_history , $conditions );

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
	 * AJax Payment listing
	 * 
	 * @package Social Auto Poster
	 * @since 2.0.0
	 */
	public function user_payments_datasource_response() {


		$result 	= array();
		$data 		= array();

		$curUserId 	= sap_get_current_user_id();

		if( isset( $_GET['user_id'] ) && !empty( $_GET['user_id'] ) ){
			$curUserId = $_REQUEST['user_id'];
		}

		try {

			$query = 'SELECT p.*,u.email,plan.name as plan_name from '.$this->_table_payment_history.' as p  INNER JOIN '.$this->table_name.' as u ON p.user_id = u.id LEFT JOIN '.$this->plan_table_name.' as plan ON p.plan_id = plan.id where 1=1 AND p.user_id = '.$curUserId;
			

			if( !empty( $_GET['search']['value'] ) ) {
				$search = $_GET['search']['value'];

				$query .= ' AND (p.customer_name like "'.$search.'%")';
			}

			$query .= ' ORDER BY p.created_date DESC';

			
			$result = $this->db->get_results( $query );

		}
		catch (Exception $e) {
			return $e->getMessage();
		}

		$number = 1;

		foreach ( $result as $payment ) {

			$invoice = '<a target="_blank" href="'.SAP_SITE_URL.'/payment-invoice/'.$payment->id.'">View</a>';

			$test_mode = $this->settings->get_options('stripe_test_mode');
			$stripe_endpoint = ( $test_mode == 'yes' ) ? 'https://dashboard.stripe.com/test/' : 'https://dashboard.stripe.com/';

			$transaction_id = $payment->transaction_id;
			
			if( strpos($transaction_id, 'sub_') !== false ){
				$transaction_id = '<a target="__blank" href="'.$stripe_endpoint.'subscriptions/'.$payment->transaction_id.'">'.$payment->transaction_id.'</a>';
			}
			elseif( strpos($transaction_id, 'ch_') !== false  ){
				$transaction_id = '<a href="'.$stripe_endpoint.'payments/'.$payment->transaction_id.'">'.$payment->transaction_id.'</a>';
			}

			$data[] = array(
				$number,
				$payment->plan_name,				
				ucfirst($payment->gateway),
				$transaction_id,
				get_payment_status_label($payment->payment_status),
				"$".round($payment->amount,2),
				sap_format_date($payment->payment_date,true),
				$invoice,
			);

			$number++;
		}

		$results = array(
			"draw" => $_GET['draw'],
			"recordsTotal" => count($result),
			"recordsFiltered" => count($data),
		  	"data"=> $data
		);

		echo json_encode($results);

	} 

	/**
	 * Get user payment history
	 * 
	 * @package Social Auto Poster
	 * @since 2.0.0
	 */
	public function user_payments_history( $user_id ) {

		$result 	= array();
		$data 		= array();		

		try {

			$query = 'SELECT p.*,u.email,plan.name as plan_name from '.$this->_table_payment_history.' as p  INNER JOIN '.$this->table_name.' as u ON p.user_id = u.id LEFT JOIN '.$this->plan_table_name.' as plan ON p.plan_id = plan.id where 1=1 AND p.user_id = '.$user_id;
			

			if( !empty( $_GET['search']['value'] ) ) {
				$search = $_GET['search']['value'];

				$query .= ' AND (p.customer_name like "'.$search.'%")';
			}					

			$query .= ' ORDER BY p.created_date DESC';
			
			$result = $this->db->get_results( $query );

		}
		catch (Exception $e) {
			return $e->getMessage();
		}

		$number = 1;
		return $result;
	}



	/**
	 * Get all plans
	 * 
	 * Handels plans listing
	 * 
	 * @package Social Auto Poster
	 * @since 2.0.0
	 */
	public function get_plans() {
		$result = array();
		try {
			$result = $this->db->get_results( "SELECT id, name FROM sap_plans ORDER BY `created` DESC" );
		} catch (Exception $e) {
			return $e->getMessage();
		}

		//Return result
		return $result;
	}

	/**
	 * Generate payment invoice
	 * 
	 * Handels invoice
	 * 
	 * @package Social Auto Poster
	 * @since 2.0.0
	 */
	public function user_payments_payment_invoice( $args ){

		if( !empty($args['id'])){

			$payments_details = $this->get_user_payments_details($args['id']);

			$user 	= sap_get_current_user();

			if( ( isset($user['role'] ) && $user['role'] == 'superadmin') || ( $payments_details->user_id == $user['user_id'])  ){

				$template_path = $this->common->get_template_path('Payment' . DS . 'invoice.php' );
				include_once( $template_path );
			}
			else {
				$this->common->redirect('login');
			}
		}		
	}

	/**
	 * Generate payment invoice
	 * 
	 * Handels invoice
	 * 
	 * @package Social Auto Poster
	 * @since 2.0.0
	 */
	public function get_user_payments_details( $payment_id ){
		
		$return = array();

		if( !empty( $payment_id ) ){

			$query = 'SELECT p.*,u.email,u.first_name,u.last_name,plan.networks,plan.name as plan_name from '.$this->_table_payment_history.' as p  INNER JOIN '.$this->table_name.' as u ON p.user_id = u.id LEFT JOIN '.$this->plan_table_name.' as plan ON p.plan_id = plan.id where p.id='.$payment_id;
			
			$result = $this->db->get_row($query,'ARRAY_A');
			
		}
		return $result;
	}


	/**
	 * Render add payment form
	 * 
	 * @package Social Auto Poster
	 * @since 2.0.0
	 */
	public function add_payment(){		

		$this->memebership = new SAP_Membership();
		
		$customers = $this->memebership->get_customer_list_with_membership();

		if ( !sap_current_user_can('add-payment') ) {
			
			$template_path = $this->common->get_template_path('Payment' . DS . 'add.php' );
			include_once( $template_path );
		}
		else {
			$this->common->redirect('login');
		}		
	}


	/**
	 * Render edit payment form
	 * 
	 * @package Social Auto Poster
	 * @since 2.0.0
	 */
	public function edit_payment( $payment_data ){


		if( isset( $payment_data['payment_id'] ) && !empty( $payment_data['payment_id'] ) ){

			$this->memebership = new SAP_Membership();	
			$customers = $this->memebership->get_customer_list_with_membership();
			$payment_details = $this->get_user_payments_details( $payment_data['payment_id'] );			

			if ( !sap_current_user_can('edti-payment') ) {
			
				$template_path = $this->common->get_template_path('Payment' . DS . 'edit.php' );
				include_once( $template_path );
			}
			else {
				$this->common->redirect('login');
			}
		}		
	}

	/**
	 * Save Payment data
	 * 	 
	 * 
	 * @package Social Auto Poster
	 * @since 2.0.0
	 */
	public function save_payment(){

		$error = false;
		// check the first name is empty
		if ( empty(trim($_POST['user_id'])) ) {
			$error = true;
			$this->flash->setFlash($this->sap_common->lang('select_customer_msg'), 'error' );
		}

		if ( empty($_POST['payment_date']) ) {
			$error = true;
			$this->flash->setFlash($this->sap_common->lang('enter_payment_date'), 'error' );
		}

		if ( !isset($_POST['status']) ) {
			$error = true;
			$this->flash->setFlash($this->sap_common->lang('select_status'), 'error' );
		}

		if ( empty($_POST['amount']) ) {
			$error = true;
			$this->flash->setFlash($this->sap_common->lang('enter_amount'), 'error' );
		}

		// Check if no error
		if( $error ) {
			header( "Location:" . SAP_SITE_URL . "/payments/add-payment/" );
			exit;
		}

		if ( !sap_current_user_can('add-payment') ) {
		
			$this->users = new SAP_Users();
			$user_membership_detail = $this->users->get_user_subscription_details($_POST['user_id']);		

			$user_details = sap_get_users_by_id($_POST['user_id']);

			// Prepare data for store post in DB
			$payment_data = array(					
				'user_id'           => $_POST['user_id'],
				'plan_id'           => $user_membership_detail->plan_id,
				'membership_id'     => $user_membership_detail->id,
				'customer_id'     	=> $user_membership_detail->customer_id,
				'customer_name'     => $user_membership_detail->customer_name,
				'customer_email'    => $user_details->email,
				'payment_date'     	=> $_POST['payment_date'],
				'amount'     		=> $_POST['amount'],
				'type'     			=> '0',
				'gateway'     		=> 'manual',
				'payment_status'    => !empty( $_POST['status']) ? $_POST['status'] : '0',
				'transaction_id'    => !empty($_POST['transaction_id']) ? $_POST['transaction_id']: $user_membership_detail->subscription_id,
				'created_date'      => date('Y-m-d H:i:s'),
				'modified_date'      => date('Y-m-d H:i:s')
			);

			$payment_data = $this->db->escape($payment_data);		

			
			if ( $this->db->insert( $this->_table_payment_history, $payment_data ) ) {
				$this->flash->setFlash($this->sap_common->lang('new_payment_success_msg'), 'success' );
			}
			else{
				$this->flash->setFlash($this->sap_common->lang('something_went_wrong'), 'error' );
			}
			header( "Location:" . SAP_SITE_URL . "/payments/" );
		}
		else {
			$this->common->redirect('login');
		}		
	}


	/**
	 * Update Payment data
	 * 	 
	 * 
	 * @package Social Auto Poster
	 * @since 2.0.0
	 */
	public function update_payment(){
		
		$error = false;
		// check the first name is empty
		if ( empty(trim($_POST['user_id'])) ) {
			$error = true;
			$this->flash->setFlash($this->sap_common->lang('select_customer_msg'), 'error' );
		}

		if ( empty($_POST['payment_date']) ) {
			$error = true;
			$this->flash->setFlash($this->sap_common->lang('enter_payment_date'), 'error' );
		}

		if ( !isset( $_POST['status'] ) ) {
			$error = true;
			$this->flash->setFlash($this->sap_common->lang('select_status'), 'error' );
		}

		if ( empty($_POST['amount']) ) {
			$error = true;
			$this->flash->setFlash($this->sap_common->lang('enter_amount'), 'error' );
		}

		// Check if no error
		if( $error ) {
			header( "Location:" . SAP_SITE_URL . "/payments/add-payment/" );
			exit;
		}

		if ( !sap_current_user_can('add-payment') ) {
		
			$this->users = new SAP_Users();
			$user_membership_detail = $this->users->get_user_subscription_details($_POST['user_id']);		

			$user_details = sap_get_users_by_id($_POST['user_id']);

			// Prepare data for store post in DB
			$payment_data = array(					
				'user_id'           => $_POST['user_id'],
				'plan_id'           => $user_membership_detail->plan_id,
				'membership_id'     => $user_membership_detail->id,
				'customer_id'     	=> $user_membership_detail->customer_id,
				'customer_name'     => $user_membership_detail->customer_name,
				'customer_email'    => $user_details->email,
				'payment_date'     	=> $_POST['payment_date'],
				'amount'     		=> $_POST['amount'],				
				'payment_status'    => isset($_POST['status']) ? $_POST['status'] : '0',
				'transaction_id'    => !empty($_POST['transaction_id']) ? $_POST['transaction_id']: $user_membership_detail->subscription_id,
				'modified_date'      => date('Y-m-d H:i:s')
			);

			$payment_data = $this->db->escape($payment_data);		

			if ( $this->db->update( $this->_table_payment_history, $payment_data,array('id' => $_POST['payment_id'])  ) ) {
				$this->flash->setFlash($this->sap_common->lang('edit_payment_success_msg'), 'success' );
			}
			else{
				$this->flash->setFlash($this->sap_common->lang('something_went_wrong'), 'error' );
			}
			header( "Location:" . SAP_SITE_URL . "/payments/" );
		}
		else {
			$this->common->redirect('login');
		}		
	}	



	/**
	 * AJAX function get user memebership details
	 * 	 
	 * 
	 * @package Social Auto Poster
	 * @since 2.0.0
	 */
	public function get_user_membership_details(){


		$result = array('status' => true);

		$user_id = isset( $_POST['user_id'] ) ? $_POST['user_id'] : 0;


		if( !empty( $user_id) ){

			$this->users = new SAP_Users();

			$user_membership_detail = $this->users->get_user_subscription_details($user_id);

			$result['status'] = true;
			$result['result'] = '<option value="'.$user_membership_detail->id.'" selected="selected" >'.$user_membership_detail->name.'</option>';			
		}
		echo json_encode($result);
		die();

	}	
}