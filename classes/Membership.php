<?php

/* Check the absolute path to the Social Auto Poster directory. */
if ( !defined( 'SAP_APP_PATH' ) ) {
    // If SAP_APP_PATH constant is not defined, perform some action, show an error, or exit the script
    // Or exit the script if required
    exit();
}

/**
 * Membership Class function
 * 
 * A class contains common function to be used to throughout the System
 *
 * @package Social Auto Poster
 * @since 2.0.0
 */
class SAP_Membership{

	private $db;
	private $table_name; 
	public $common;
	public $flash;
	public $settings;
	public $payments;
	public $sap_common, $plan_table_name, $_table_membership, $_table_payment_history, $plan, $user;
	

	public function __construct() {

		global $sap_db_connect, $sap_common;

		$this->db = $sap_db_connect;
		$this->table_name = 'sap_users';
		$this->plan_table_name = 'sap_plans';
		$this->_table_membership = 'sap_membership';
		$this->_table_payment_history = 'sap_payment_history';

		$this->flash = new Flash();
		$this->common = new Common();
		$this->sap_common = $sap_common;

		if( !class_exists('SAP_Plans')){
			require_once CLASS_PATH.'/Plans.php';
		}
		$this->plan = new SAP_Plans();

		if( !class_exists('SAP_Payments')){
			require_once CLASS_PATH.'/Payments.php';
		}
		$this->payments = new SAP_Payments();

		if( !class_exists('SAP_Users')){
			require_once CLASS_PATH.'/Users.php';
		}
		$this->user = new SAP_Users();
		$this->settings = new SAP_Settings();
	}

	/**
	 * Listing page of membership
	 * 
	 * @package Social Auto Poster
	 * @since 2.0.0
	 */
	public function index() {

		// Includes Html files for Posts list
		if ( !sap_current_user_can('membership') ) {

			if ( !empty($_SESSION['user_details']) ) {

				$allplan = $this->plan->get_plans();
				
				$template_path = $this->common->get_template_path('Membership' . DS . 'index.php' );
				include_once( $template_path );
				
			} 
		}
		else {
			$this->common->redirect('login');
		}
	}

	/**
	 * AJax membership listing
	 * 
	 * @package Social Auto Poster
	 * @since 2.0.0
	 */
	public function membership_datasource_response() {
		
		if ( !sap_current_user_can('membership') ) {

			if ( !empty($_SESSION['user_details']) ) {

				$result = array();
				$data 	= array();

				$curUserId = isset( $_SESSION['user_details']['user_id'] ) ? $_SESSION['user_details']['user_id'] : 0;

				try {

					$start  = $_GET['start'];
					$length = $_GET['length'];

					$order_column 	=  isset( $_GET['order'][0]['column'] ) ? $_GET['order'][0]['column'] :'';
					$order_dir 		=  isset( $_GET['order'][0]['dir'] ) ? $_GET['order'][0]['dir'] :'';

					switch( $order_column ) {
						
						case '1':
							$orderby = 'm.customer_name';
						break;
						case '2':
							$orderby = 'm.customer_id';
						break;
						case '3':
							$orderby = 'p.name';
						break;

						case '4':
							$orderby = 'm.membership_status';
						break;

						case '5':
							$orderby = 'm.recurring';
						break;

						case '6':
							$orderby = 'm.expiration_date';
						break;

						case '7':
							$orderby = 'm.created_date';
						break;

						case '8':
							$orderby = 'm.id';
						break;						
					}

					$query = ' SELECT m.*,p.name as plan_name FROM '. $this->_table_membership .' as m inner join '.$this->table_name .' as u ON m.user_id = u.id LEFT JOIN '.$this->plan_table_name.' as p ON m.plan_id = p.id WHERE 1 = 1 ';
					
					if( !empty( $_GET['search']['value'] ) ) {
						$search = $_GET['search']['value'];

						$query .= 'AND (m.customer_name like "'.$search.'%" )';
					}

					if( isset($_GET['searchByStatus']) && $_GET['searchByStatus'] != '' ) {

						$searchByStatus = $_GET['searchByStatus'];
						$query .= 'AND (m.membership_status = "'.$searchByStatus.'")';
					}

					if( isset($_GET['searchByPlan']) && $_GET['searchByPlan'] != '' ) {

						$searchByPlan = $_GET['searchByPlan'];
						$query .= 'AND (p.name = "'.$searchByPlan.'")';
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

				foreach ( $result as $membership ) {

					if( $curUserId == $membership->id ) {
						$checkbox = '<input type="checkbox" value="current" />';
					}
					else {
						$checkbox = '<input type="checkbox" name="membership_id[]" value="' . $membership->id . '" />';
					}

					$customer_name = '<a href="'.SAP_SITE_URL . '/member/edit/' . $membership->user_id.'">'.$membership->customer_name;

					$membership_name = '<a href="'.SAP_SITE_URL . '/membership/edit/' . $membership->id.'">'.$membership->plan_name;
										
					$action_links = '<a href="'.SAP_SITE_URL . '/membership/edit/' . $membership->id.'"><i class="fa fa-pencil-square-o" aria-hidden="true"></i></a>';

					$expiration = '';
					$test_mode  = $this->settings->get_options('stripe_test_mode');
					$stripe_endpoint = ( $test_mode == 'yes' ) ? 'https://dashboard.stripe.com/test/customers/' : 'https://dashboard.stripe.com/customers/';

					$stripe_customer_id = '<a target="_blank" href="'.$stripe_endpoint.$membership->customer_id.'"> '.$membership->customer_id.'</a>';					

					$data[] = array(
						$number,
						$membership_name,
						$customer_name,
						$stripe_customer_id,
						get_membership_status_label($membership->membership_status),
						get_recuring_status_label($membership->recurring),
						sap_get_membership_expiration_date( $membership->expiration_date),
						sap_format_date($membership->created_date),
						$action_links
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
		}
		else {
			$this->common->redirect('login');
		}
	}

	/**
	 * Add new membership
	 *
	 * @package Social Auto Poster
	 * @since 2.0.0
	 */
	public function add_new_membership() {

		// Includes Html files for Posts list
		if (  !sap_current_user_can('membership') && !empty($_SESSION['user_details']) ) {
			$template_path = $this->common->get_template_path('Membership' . DS . 'add.php' );
			include_once( $template_path );
		}
		else {
			$this->common->redirect('login');
		}
	}

	/**
	 * cancel user membership membership
	 *
	 * @package Social Auto Poster
	 * @since 2.0.0
	 */
	public function cancle_user_membership( $user_data ){
		
		
		global $match;

		if( isset( $user_data['user_id'] ) && !empty( $user_data['user_id'] ) ){

			$user_id = $user_data['user_id'];

			// Get current user
			$current_user = sap_get_current_user();

			$test_publishable_key 	= $this->settings->get_options('test_publishable_key');
			$test_secret_key 		= $this->settings->get_options('test_secret_key');

			$live_publishable_key 	= $this->settings->get_options('live_publishable_key');
			$live_secret_key 		= $this->settings->get_options('live_secret_key');


			$cancelled_membership_email_subject 	= $this->settings->get_options('cancelled_membership_email_subject');
			$cancelled_membership_email_content 		= $this->settings->get_options('cancelled_membership_email_content');

			$stripe_test_mode 		= $this->settings->get_options('stripe_test_mode');

			if( $stripe_test_mode == 'yes' ) {			
				$publish_key	= $test_publishable_key;
				$secret_key		= $test_secret_key;
			}
			else {
				$publish_key	= $live_publishable_key;
				$secret_key		= $live_secret_key;
			}

			$result = $this->db->get_row('SELECT p.* ,m.id as membership_id,m.expiration_date,m.subscription_id, plan.name  from '.$this->_table_membership.' as m inner join '.$this->_table_payment_history.' as p ON m.id = p.membership_id INNER JOIN '.$this->plan_table_name.' as plan where m.user_id = '.$user_id.' AND m.recurring = "1" ORDER BY p.id DESC LIMIT 1','ARRAY_A');


			$subscription_id =  '';
			if( isset( $result->subscription_id ) && !empty( $result->subscription_id) ){
				$subscription_id = $result->subscription_id;
			}
			else{
				$subscription_id = $result->transaction_id;
			}		


			$user_membership_detail = $this->user->get_user_subscription_details($user_id);
			$expiration_date = sap_format_date($result->expiration_date);

			if( !empty($subscription_id) ){

				$membership_id 	= $result->membership_id;	

				require_once( LIB_PATH . '/stripe/init.php' );

				try {

					$stripe 	= new \Stripe\StripeClient($secret_key);					
					$cancle 	= $stripe->subscriptions->cancel(
					  	$subscription_id
					);

					$prepare_membership_data['recurring'] = '0';
					$prepare_membership_data['membership_status'] = '3';
					$prepare_membership_data['cancellation_date'] = date('Y-m-d H:i:s');

					$this->db->update($this->_table_membership, $prepare_membership_data, array('id' => $membership_id));
					
					if( !empty( $cancelled_membership_email_subject ) && !empty( $cancelled_membership_email_content ) ){

						$email = new Sap_Email();				

						$template_path = $this->common->get_template_path('Membership' . DS . 'cancel-membership-notification-temp.php' );
						include_once( $template_path );

						$message = ob_get_clean();

						$smtp_setting = $this->settings->get_options('sap_smtp_setting');

						if( isset($smtp_setting['enable'] ) && $smtp_setting['enable'] == 'yes' ){
							$email->send($result->customer_email, $cancelled_membership_email_subject, $message);
						}
						else{
							$headers = "MIME-Version: 1.0\r\n";
							$headers .= "Content-Type: text/html; charset=ISO-8859-1\r\n";
							$mailstatus = $email->send($result->customer_email,$cancelled_membership_email_subject, $message,$headers);
						}
					}
				}
				catch (Exception $e) {
					// redirect on failed payment
					$this->flash->setFlash($e->getMessage(), 'error');
				}


				if( $match['name'] != 're-payment' ){
					if( isset( $current_user['role'] ) && $current_user['role'] == 'superadmin'){
						
						header( "Location:" . SAP_SITE_URL . "/membership/edit/".$membership_id );
					}
					else{
						header( "Location:" . SAP_SITE_URL . "/subscription/" );
					}
				}
			}
			else{

				if( $match['name'] != 're-payment' ){
					if( isset( $current_user['role'] ) && $current_user['role'] == 'superadmin'){
						$this->flash->setFlash($this->sap_common->lang('something_went_wrong'), 'error');
						header( "Location:" . SAP_SITE_URL . "/membership/" );
					}
					else{
						$this->flash->setFlash($this->sap_common->lang('something_went_wrong'), 'error');
						header( "Location:" . SAP_SITE_URL . "/subscription/" );
					}
				}
			}
		}
	}


	/**
	 * Exipre user membership
	 *
	 * @package Social Auto Poster
	 * @since 2.0.0
	 */
	public function expire_user_membership( $user_data ){

		if( isset( $user_data['user_id'] ) && !empty( $user_data['user_id'] ) ){

			$user_id = $user_data['user_id'];
			
			// Get current user
			$current_user = sap_get_current_user();

			$test_publishable_key 	= $this->settings->get_options('test_publishable_key');
			$test_secret_key 		= $this->settings->get_options('test_secret_key');

			$live_publishable_key 	= $this->settings->get_options('live_publishable_key');
			$live_secret_key 		= $this->settings->get_options('live_secret_key');

			$stripe_test_mode 		= $this->settings->get_options('stripe_test_mode');

			if( $stripe_test_mode == 'yes' ) {			
				$publish_key	= $test_publishable_key;
				$secret_key		= $test_secret_key;
			}
			else {
				$publish_key	= $live_publishable_key;
				$secret_key		= $live_secret_key;
			}

			$expired_membership_email_subject 	= $this->settings->get_options('expired_membership_email_subject');
			$expired_membership_email_content 		= $this->settings->get_options('expired_membership_email_content');

			$membership = $this->db->get_row('SELECT p.* ,m.id as membership_id,m.expiration_date,m.subscription_id, plan.name  from '.$this->_table_membership.' as m inner join '.$this->_table_payment_history.' as p ON m.id = p.membership_id INNER JOIN '.$this->plan_table_name.' as plan where m.user_id = '.$user_id.' ORDER BY p.id DESC LIMIT 1','ARRAY_A');			

			$user_membership_detail = $this->user->get_user_subscription_details($user_id);			
			$expiration_date = sap_format_date($membership->expiration_date);

			$subscription_id =  '';
			if( isset( $membership->subscription_id ) && !empty( $membership->subscription_id) ){
				$subscription_id = $membership->subscription_id;
			}
			else{
				$subscription_id = $membership->transaction_id;
			}

			if( !empty($subscription_id ) ){

				$membership_id 	= $membership->membership_id;

				require_once( LIB_PATH . '/stripe/init.php' );

				try {

					$stripe 	= new \Stripe\StripeClient($secret_key);
					
					$cancle 	= $stripe->subscriptions->cancel(
					  	$subscription_id
					);

					$prepare_membership_data['recurring'] = '0';
					$prepare_membership_data['membership_status'] = '2';					

					$this->db->update($this->_table_membership, $prepare_membership_data, array('id' => $membership_id));
					
					if( !empty( $expired_membership_email_subject ) && !empty( $expired_membership_email_content ) ){

						$email = new Sap_Email();				

						$template_path = $this->common->get_template_path('Membership' . DS . 'expire-membership-notification-temp.php' );
						include_once( $template_path );

						$message = ob_get_clean();

						$smtp_setting = $this->settings->get_options('sap_smtp_setting');

						if( isset($smtp_setting['enable'] ) && $smtp_setting['enable'] == 'yes' ){
							$email->send($membership->customer_email, $expired_membership_email_subject, $message);
						}
						else{
							$headers = "MIME-Version: 1.0\r\n";
							$headers .= "Content-Type: text/html; charset=ISO-8859-1\r\n";
							$mailstatus = $email->send($membership->customer_email,$expired_membership_email_subject, $message,$headers);
						}
					}
				}
				catch (Exception $e) {					
					//redirect on failed payment
					$this->flash->setFlash($e->getMessage(), 'error');
				}

				if( isset( $current_user['role'] ) && $current_user['role'] == 'superadmin'){					
					header( "Location:" . SAP_SITE_URL . "/membership/edit/".$membership_id );
				}
				else{
					header( "Location:" . SAP_SITE_URL . "/subscription/" );
				}
			}
			else{

				if( isset( $current_user['role'] ) && $current_user['role'] == 'superadmin'){
					$this->flash->setFlash($this->sap_common->lang('something_went_wrong'), 'error');
					header( "Location:" . SAP_SITE_URL . "/membership/" );
				}
				else{
					$this->flash->setFlash($this->sap_common->lang('something_went_wrong'), 'error');
					header( "Location:" . SAP_SITE_URL . "/subscription/" );
				}
			}
		}
	}

	
	/**
	 * Edit membership
	 *
	 * @package Social Auto Poster
	 * @since 2.0.0
	 */
	public function edit_membership() {

		if ( !sap_current_user_can('membership') &&  !empty($_SESSION['user_details']) ) {
			$template_path = $this->common->get_template_path('Membership' . DS . 'edit.php' );
			include_once( $template_path );

		} else {
			$this->common->redirect('login');
		}
	}

	/**
	 * Get all membership
	 * 
	 * Handels post listing
	 * 
	 * @package Social Auto Poster
	 * @since 2.0.0
	 */
	public function get_membership() {

		$result = array();
		try {
			$result = $this->db->get_results( 'SELECT * from '.$this->_table_membership.' WHERE 1=1' );
		} catch (Exception $e) {
			return $e->getMessage();
		}
		 
		// Return result
		return $result;
	}


	/**
	 * Get membership by id 
	 * 
	 * Handels post listing
	 * 
	 * @package Social Auto Poster
	 * @since 2.0.0
	 */
	public function get_membership_by_id($membership_id , $object) {

		$result = array();
		try {
			
			$result = $this->db->get_row( 'SELECT m.*,p.name,p.description,p.price,p.subscription_expiration_days from '.$this->_table_membership.' as m inner join '.$this->plan_table_name.' as p ON m.plan_id = p.id WHERE m.id ='.$membership_id , $object );
		}
		catch (Exception $e) {
			return $e->getMessage();
		}
		 
		// Return result
		return $result;
	}

	/**
	 * Get post settings
	 * 
	 * Handels list setting Option get
	 * 
	 * @package Social Auto Poster
	 * @since 2.0.0
	 */
	public function get_member($member_id, $object) {
		
		$result = array();

		if ( !empty($member_id) ) {
			try {
				$result = $this->db->get_row( "SELECT * FROM " . $this->table_name . " where `id` = '{$member_id}'", $object );
			} catch (Exception $e) {
				return $e->getMessage();
			}
			// Return result
			return $result;
		}
	}

	
	/**
	 * save membership 
	 * 
	 * Handels post listing
	 * 
	 * @package Social Auto Poster
	 * @since 2.0.0
	 */
	public function save_membership() {
		 
		//Check form submit request
		if ( isset($_POST['form-submitted']) ) {

			$error = false;

			// check the first name is empty
			if ( empty(trim($_POST['sap_customer'])) ) {
				$error = true;
				$this->flash->setFlash($this->sap_common->lang('select_customer_msg'), 'error' );
			}

			if ( !isset($_POST['membership_status']) ) {
				$error = true;
				$this->flash->setFlash($this->sap_common->lang('select_status'), 'error' );
			}
			
			if ( empty(trim($_POST['sap_plan'])) ) {
				$error = true;
				$this->flash->setFlash( $this->sap_common->lang('select_plan'), 'error' );
			}

			if ( empty(trim($_POST['membership_start_date'])) ) {
				$error = true;
				$this->flash->setFlash( $this->sap_common->lang('membership_start_date'), 'error' );
			}
			
			// Check if no error
			if( $error ) {
				header( "Location:" . SAP_SITE_URL . "/membership/add/" );
				exit;
			}


			//Get User data by id
			$user_data = $this->user->get_user_by_id($_POST['sap_customer'] , true);

			//Get plan Data by id
			$plan_data  = $this->plan->get_plan( $_POST['sap_plan'] , true );


			if( isset( $_POST['expiration_date'] ) && !empty( $_POST['expiration_date']) ){
				$expiration_date = $_POST['expiration_date'];
			}
			elseif( !isset($_POST['no_expiration'] ) && !empty( $plan_data->subscription_expiration_days) ){

				$expiration_days = !empty( $plan_data->subscription_expiration_days ) ? $plan_data->subscription_expiration_days : 0;

				//completed
				$current_date 	 = date('Y-m-d H:i:s');
				$expiration_date = date('Y-m-d H:i:s', strtotime($current_date. ' + '.$expiration_days.' day'));
			}
				

			$membership_start_date = isset( $_POST['membership_start_date'] ) ? $_POST['membership_start_date'] : date('Y-m-d');

			$membership_duration_days = 0;

			if( !empty( $expiration_date ) && !empty( $membership_start_date ) ){

				$now 		= strtotime( $expiration_date );
				$your_date 	= strtotime( $membership_start_date );

				$datediff 	= $now - $your_date;
				$membership_duration_days = round($datediff / (60 * 60 * 24));	
			}			
			
			// Prepare data for store post in DB
			$membership_data = array(					
				'user_id'           => $_POST['sap_customer'],
				'plan_id'           => $_POST['sap_plan'],
				'customer_id' 	    => isset( $_POST['customer_id'] ) ? $_POST['customer_id'] : '',
				'subscription_id' 	=> isset( $_POST['subscription_id'] ) ? $_POST['subscription_id'] : '',
				'customer_name'     => $user_data->first_name .' '.$user_data->last_name,				
				'membership_status' => isset($_POST['membership_status']) ? $_POST['membership_status'] : '0',
				'recurring'			=> isset($_POST['auto_renew']) ? $_POST['auto_renew'] : 0,
				'gateway'			=> 'manual',
				'expiration_date'   => $expiration_date,
				'created_date'      => date('Y-m-d H:i:s'),
				'membership_created_date' => $membership_start_date,
				'membership_duration_days' => $membership_duration_days,
				'modified_date'      => date('Y-m-d H:i:s'),
			);			
			
			$membership_data = $this->db->escape($membership_data);


			if ( $this->db->insert( $this->_table_membership, $membership_data ) ) {

				$membership_id = $this->db->lastid();

				if( !empty( $membership_id ) ){

					$insert_payment = array(
						'user_id' 		=> $_POST['sap_customer'],
						'membership_id' => $membership_id,
						'plan_id' 		=> $_POST['sap_plan'],
						'customer_id' 	=> '',
						'customer_name' => $user_data->first_name .' '.$user_data->last_name,
						'customer_email' => $user_data->email,
						'payment_date' 	=> date('Y-m-d H:i:s'),
						'amount' 		=> $plan_data->price,
						'type' 			=> '0',
						'gateway' 		=> 'manual',
						'payment_status'=> '1',
						'transaction_id'=> '',
						'created_date' 	=> date('Y-m-d H:i:s'),
						'modified_date' => date('Y-m-d H:i:s'),
					);

					$insert_payment = $this->db->escape($insert_payment);
					$result = $this->db->insert($this->_table_payment_history, $insert_payment);
				}
				
				$this->flash->setFlash($this->sap_common->lang('new_membership_success_msg'), 'success');

				header( "Location:" . SAP_SITE_URL . "/membership/" );
				exit;
			}

			$this->flash->setFlash($this->sap_common->lang('saving_data_error_msg'), 'error');

			header( "Location:" . SAP_SITE_URL . "/membership/add/" );
			exit;
		}
	}

	
	/**
	 * Update member data
	 * 
	 * Handels post listing
	 * 
	 * @package Social Auto Poster
	 * @since 2.0.0
	 */
	public function update_membership() {

		if ( isset($_POST['form-updated']) ) {

			$member_id = $_POST['id'];

			$error = false;
			
			// check the first name is empty
			// Check if no error
			if( $error ) {
				header( "Location:" . SAP_SITE_URL . "/member/edit/" . $member_id );
				exit;
			}

			//Prepare data for store membership in DB
			$prepare_data = array(
				'expiration_date'	=> isset( $_POST['expiration_date'] ) ? trim($_POST['expiration_date']) : '',
				'membership_status'	=> isset( $_POST['membership_status'] ) ? $_POST['membership_status'] : '0',
				'modified_date'     => date('Y-m-d H:i:s'),
				'customer_id'     => isset( $_POST['customer_id'] ) ? $_POST['customer_id'] : '',
				'subscription_id' => isset( $_POST['subscription_id'] ) ? $_POST['subscription_id'] : '',
				'recurring'			=> isset($_POST['auto_renew']) ? $_POST['auto_renew'] : 0,
				'membership_created_date' => $_POST['membership_created_date'],
			);
			
			// Update the data
			$prepare_data = $this->db->escape($prepare_data);

			if( isset( $_POST['no_expiration']) ){
				$prepare_data['expiration_date'] = '';
			}
			if ( $this->db->update($this->_table_membership, $prepare_data, array('id' => $member_id)) ) {

				$this->flash->setFlash($this->sap_common->lang('memberships_update_success_msg'), 'success');
				header( "Location:" . SAP_SITE_URL . "/membership/edit/" . $member_id );
				exit;
			}
		}
	}

	/**
	 * Delete membership
	 * 
	 * Handels post listing
	 * 
	 * @package Social Auto Poster
	 * @since 2.0.0
	 */
	public function delete_member() {
		
		if ( !empty($_REQUEST['member_id']) ) {

			$result = array();
			
			$member_id = $_REQUEST['member_id'];
			$conditions = array('id' => $member_id);
			$is_deleted = $this->db->delete( $this->table_name, $conditions );

			if ( $is_deleted ) {
				$result = array('status' => '1');
			} else {
				$result = array('status' => '0');
			}
			
			echo json_encode($result);
			die;
		}
	}

	/**
	 * Delete membership member
	 * 
	 * Handels post listing
	 * 
	 * @package Social Auto Poster
	 * @since 2.0.0
	 */
	public function delete_multiple_member() {

		if ( !empty($_REQUEST['id']) ) {
			
			$result = array();
			
			$member_id = $_REQUEST['id'];
			foreach ( $member_id as $key => $value ) {
				$conditions = array( 'id' => $value );
				$is_deleted = $this->db->delete( $this->table_name, $conditions );
			}

			if ( $is_deleted ) {
				$result = array('status' => '1');
				$this->flash->setFlash($this->sap_common->lang('membership_deleted') , 'success' );
			} else {
				$result = array('status' => '0');
			}

			echo json_encode($result);
			die;
		}
	}


	/**
	 * search membership my status
	 * 
	 * Handels post listing
	 * 
	 * @package Social Auto Poster
	 * @since 2.0.0
	 */
	public function searchByStatus() {
		
		if ( isset($_POST['selected_val']) ) {
			
			$result = array();
			try{
				// Get status
				$status = $_POST['selected_val'];

				// Create query
				$query = "SELECT u.id as uid, u.first_name , u.last_name, u.email, u.role, u.status, u.created as ucreated, plan.name as pname FROM " . $this->table_name . " as u Inner join ".$this->plan_table_name." as plan ON plan.id = u.plan";

				// Check if status is not empty
				if ( $status != '' ) {
					$query .= " WHERE u.status = '" . $status . "'";
				}

				// Add order parameters
				$query .= " ORDER BY u.created DESC";

				$result = $this->db->get_results( $query );

			} catch (Exception $e) {
				return $e->getMessage();
			}

			echo json_encode($result);
			die; 
			// Return result
		}
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
	 * search customer without membership
	 * 
	 * Handels post listing
	 * 
	 * @package Social Auto Poster
	 * @since 2.0.0
	 */
	public function get_customer_without_membership() {
		$result = array();
		try {
			$result = $this->db->get_results( "SELECT id, first_name, last_name FROM ".$this->table_name." WHERE id NOT IN(select user_id from ".$this->_table_membership.") AND role = 'user' ORDER BY `created` DESC" );
		} catch (Exception $e) {
			return $e->getMessage();
		}

		//Return result
		return $result;
	}


	/**
	 * Get Membership by user id
	 * 
	 * Handels post listing
	 * 
	 * @package Social Auto Poster
	 * @since 2.0.0
	 */
	public function get_membership_by_user_id($user_id , $object ){
		
		$result = array();
		try {			
			$result = $this->db->get_row( 'SELECT m.*,p.name,p.description,p.price,p.subscription_expiration_days from '.$this->_table_membership.' as m inner join '.$this->plan_table_name.' as p ON m.plan_id = p.id WHERE m.user_id ='.$user_id , $object );
		} catch (Exception $e) {
			return $e->getMessage();
		}
		 
		// Return result
		return $result;

	}


	/**
	 * Get customer list who has membership
	 * 
	 * Handels post listing
	 * 
	 * @package Social Auto Poster
	 * @since 2.0.0
	 */
	public function get_customer_list_with_membership(){
		
		$result = array();
		try {			
			$result = $this->db->get_results('SELECT u.*,p.name,p.id as plan_id FROM '.$this->_table_membership.' as m INNER JOIN '.$this->table_name.' as u on m.user_id = u.id INNER JOIN '.$this->plan_table_name.' as p on m.plan_id = p.id', true );
		}
		catch (Exception $e) {
			return $e->getMessage();
		}		 
		// Return result
		return $result;
	}
}