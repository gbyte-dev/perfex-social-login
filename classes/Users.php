<?php

/* Check the absolute path to the Social Auto Poster directory. */
if ( !defined( 'SAP_APP_PATH' ) ) {
    // If SAP_APP_PATH constant is not defined, perform some action, show an error, or exit the script
    // Or exit the script if required
    exit();
}

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of Users
 *
 * @author PC10
 */
class SAP_Users {
	
	private $_table_name;
	private $_plan_table;
	private $_db;
	public $flash;
	public $common;		
	public $payment;
	public $sap_common, $_table_users, $_table_membership, $_table_payment_history, $_table_coupons, $plan, $setting;

	public function __construct() {



		global $sap_common;

		$this->_db = new Sap_Database();		
		$this->_table_name = 'sap_users';
		$this->_plan_table = 'sap_plans';
		$this->_table_users = 'sap_users';
		$this->_table_membership = 'sap_membership';
		$this->_table_payment_history = 'sap_payment_history';
		$this->_table_coupons = 'sap_coupons';
		$this->flash = new Flash();
		$this->common = new Common();
		$this->sap_common = $sap_common;
		//$this->_table_option = 'sap_options';

		if( !class_exists('SAP_Payment')){
			require_once CLASS_PATH.'/Payment.php';
		}

		if( !class_exists('SAP_Plans')){
			require_once CLASS_PATH.'/Plans.php';
		}

		if( !class_exists('SAP_Settings')){
			require_once CLASS_PATH.'/Settings.php';
		}

		$this->payment = new SAP_Payment();	
		$this->plan = new SAP_Plans();
		$this->setting = new SAP_Settings();
		
	}


	/**
	 * Get Webook data from stripe return url 
	 * 
	 * @package Social Auto Poster
	 * @since 1.0.0
	 */
	public function get_stripe_data(){
		if( isset($_REQUEST['user_id']) && !empty($_REQUEST['user_id']) ){
			if(isset($_REQUEST['payment_intent']) && !empty($_REQUEST['payment_intent'])){

			$user_id = $_REQUEST['user_id'];

			$payment_intent= $_REQUEST['payment_intent'];

			$test_secret_key 		= $this->setting->get_options('test_secret_key');

			$live_secret_key 		= $this->setting->get_options('live_secret_key');

			$stripe_test_mode 		= $this->setting->get_options('stripe_test_mode');	

			// IF send box enabled
			if( $stripe_test_mode == 'yes' ) {			
			
				$secret_key		= $test_secret_key;
			}
			else {			
				
				$secret_key		= $live_secret_key;
			}
			// load the stripe libraries
			require_once( LIB_PATH . '/stripe/init.php');
				$stripe 	= new \Stripe\StripeClient($secret_key);
				$retrieve = $stripe->paymentIntents->retrieve($payment_intent);
				
				if($retrieve->status == 'succeeded' && $retrieve->amount_received > 0 ){
					$membership_status = '1';		
				}else{
					$membership_status = '0';

				}
				$update_data = array(	
						'membership_status' => $membership_status,
					);	
				$update_data = $this->_db->escape( $update_data );
				$result = $this->_db->update($this->_table_membership, $update_data, array('user_id' => $user_id));
				
				header( "Location:" . SAP_SITE_URL.'/thank-you/'.$user_id );
				exit;
			}

		}
	}

	

	/**
	 * Login 
	 * 
	 * @package Social Auto Poster
	 * @since 1.0.0
	 */
	public function login() {

		$user = sap_get_current_user();
		
		//Includes Html files for Login page
		if ( !empty($user) ) {
			if( 'superadmin' == $user['role'] ) {
				$this->common->redirect('plan_list');
				
			}
			else {				
				$this->common->redirect('quick_posts');
			}
		}
		else {
			
			$template_path = $this->common->get_template_path('Users' . DS . 'login.php' );
			include_once( $template_path );			
		}
	}

	/**
	 * Login User
	 * 
	 * @package Social Auto Poster
	 * @since 1.0.0
	 */
	public function login_user() {
		global $sap_common;
		extract($_POST);

		$password = md5($user_password);

		$result = $this->_db->get_row("SELECT * FROM " . $this->_table_name . " where `email` = '{$user_email}' and `password` = '{$password}'");

		if( !empty($result) )
		if ( count($result) > 0) {

			$user_id = $result[0];

			$email_verification = $this->setting->get_options('enable_email_verification');

			if( $email_verification == 'yes' && $result[6] != '1' ){

				$resend_link = '<a href="'.SAP_SITE_URL.'/resend-email/'.$user_id.'">Resend Email</a>';
				
				if( $result[5] != 'superadmin' && isset($result[7] ) && !empty($result[7] ) ){

					$this->flash->setFlash(sprintf($this->sap_common->lang('account_verified_msg'),$resend_link) , 'error');
					$this->common->redirect('login');	
					exit;				
				}

				if(empty( $result[6] ) ){

					$this->flash->setFlash($this->sap_common->lang('account_active_msg'), 'error');
					$this->common->redirect('login');	
					exit;	
				}
			}		

			$membership_data = $this->_db->get_row("SELECT plan_id FROM " . $this->_table_membership . " where `user_id` = '$result[0]' ",'ARRAY_A');
			

			$plan_id = isset( $membership_data->plan_id ) ? $membership_data->plan_id : '';
			$available_networks = '';

			if( !empty($plan_id) ) {

				$available_networks = $this->_db->get_row("SELECT networks FROM " . $this->_plan_table . " where `id` = '{$plan_id}'");

				$available_networks = isset( $available_networks[0] ) ? unserialize( $available_networks[0] ) : '';
			}

			// Store user data to the session
			$_SESSION['user_details'] = array(
				'user_id'		=> $result[0],
				'first_name'	=> $result[1],
				'last_name'		=> $result[2],
				'user_email'	=> $result[3],
				'role'			=> $result[5],
				'plan'			=> $plan_id,
				'networks'		=> $available_networks
			);

			if (isset($_POST['remember_me'])) {
				$user_data = array(
					'user_email' => $user_email,
					'password' => base64_encode($user_password)
				);
				$serialize_user_data = serialize($user_data);
				setcookie("user_login", $serialize_user_data, time() + (10 * 365 * 24 * 60 * 60), "/");
			}
			else {
				if (isset($_COOKIE["user_login"])) {
					setcookie("user_login", "", time(), "/");
				}
			}

			$this->flash->setFlash($this->sap_common->lang('success_login_msg'), 'success');

			if( $result[5] != 'user'){
				header( "Location:" . SAP_SITE_URL . "/general-settings/" );	
			}
			else{
				header( "Location:" . SAP_SITE_URL);		
			}			
			exit;

		}
		else {
			$this->common->redirect('login');
			$this->flash->setFlash($this->sap_common->lang('invalid_up_msg'), 'error');
			exit;
		}else{
			$this->common->redirect('login');
			$this->flash->setFlash($this->sap_common->lang('invalid_up_msg'), 'error');
			exit;
		}
	}

	/**
	 * My Account
	 * 
	 * @package Social Auto Poster
	 * @since 1.0.0
	 */
	public function my_account() {
		//Includes Html files for My account page
		if (isset($_SESSION['user_details']) && !empty($_SESSION['user_details'])) {

			// Get logged in user details
            $user_details = $this->login_user_details($_SESSION['user_details']['user_id']);
            $user_role    =  $user_details->role;

            $user_details = json_decode(json_encode($user_details), true);
            
           $plan = isset($user_details['plan']) ? $user_details['plan'] : 0;

            $plan_data  = $this->get_plan( $plan, true );            
            
            $subscription_details 	= $this->get_user_subscription_details($user_details['id']);

            $max_plan = '';
            if( $user_role   != 'superadmin' ){ 
            	$max_plan = $this->plan->get_upgrade_plans($user_details['id']);
        	}          
           	
           	$template_path = $this->common->get_template_path('Users' . DS . 'my-account.php' );
			include_once( $template_path );
			
		}
		else {
			$this->common->redirect('login');
		}
	}

	/**
	 * Update Users
	 * 
	 * @package Social Auto Poster
	 * @since 1.0.0
	 */
	public function update_user() {
		global $sap_common;

		extract($_POST);

		unset($_SESSION['sap_my_account_tab']);

		if (isset($sap_user_id) && !empty($sap_user_id)) {

			$data = array(
				'first_name'=> $this->_db->filter($sap_user_fname),
				'last_name' => $this->_db->filter($sap_user_lname),
				'email' 	=> $this->_db->filter($sap_user_email),
				'token' 	=> md5($sap_user_email
				)
			);


			$conditions = array('id' => $sap_user_id);
			if (!empty($sap_user_password)) {
				$data['password'] = md5($sap_user_password);
			}
			try {

				$data   = $this->_db->escape($data);
				$result = $this->_db->update($this->_table_name, $data, $conditions);

				if ($result) {

					$_SESSION['user_details']['first_name'] = $sap_user_fname;
					$_SESSION['user_details']['last_name']  = $sap_user_lname;

					$this->flash->setFlash($this->sap_common->lang('profile_update_msg'), 'success');
				}
				else {
					$this->flash->setFlash($this->sap_common->lang('error_update_msg'), 'error');
				}

			}
			catch (Exception $e) {
				return $e->getMessage();
			}
			//Return result
			$this->common->redirect('my_account');
		}
	}

	/**
	 * Login user details
	 * 
	 * @package Social Auto Poster
	 * @since 1.0.0
	 */
	public function login_user_details($user_id) {
		$result = array();
		if (isset($user_id) && !empty($user_id)) {
			try {
				$result = $this->_db->get_row("SELECT * FROM " . $this->_table_name . " where `id` = '{$user_id}'", true);
			} catch (Exception $e) {
				return $e->getMessage();
			}
			//Return result
			return $result;
		}
	}

	/**
	 * Logout
	 * 
	 * @package Social Auto Poster
	 * @since 1.0.0
	 */
	public function logout() {
		unset($_SESSION['user_details']);
		$this->common->redirect('login');
		$this->flash->setFlash($this->sap_common->lang('success_logout_msg'), 'success');
	}

	/**
	 * Forgot password
	 * 
	 * @package Social Auto Poster
	 * @since 1.0.0
	 */
	public function forgot_password() {

		//Includes Html files for Login page
		$template_path = $this->common->get_template_path('Users' . DS . 'forgot-password.php' );
		include_once( $template_path );	
		
	}

	/**
	 * Forgot password email process
	 * 
	 * @package Social Auto Poster
	 * @since 1.0.0
	 */
	public function forgot_password_process() {

		global $router, $match;

		extract($_POST);

		$result = $this->_db->get_results("SELECT * FROM " . $this->_table_name . " where `email` = '{$user_email}' ");

		if ( !empty( $result ) && count( $result ) > 0 ) {

			$data = array('forgot_time' => date('Y-m-d'), 'token' => sha1(mt_rand(1, 90000) . 'SALT'));
			$this->_db->update($this->_table_name, $data, array('email' => $user_email));

			$result = $this->_db->get_results("SELECT * FROM " . $this->_table_name . " where `email` = '{$user_email}' ");
			
			$smtp_setting = $this->setting->get_options('sap_smtp_setting');			

			$token = $result[0]->token;

			//Forgot Mail send process
			$to = $result[0]->email;
			$subject = "Reset your Mingle - " . empty( $this->setting->get_options('mingle_site_name') ) ? SAP_NAME : $this->setting->get_options('mingle_site_name') . " password";

			ob_start();

			$template_path = $this->common->get_template_path('Users' . DS . 'reset-password-email-temp.php' );
			include_once( $template_path );

			$message = ob_get_clean();			

			$email = new Sap_Email();

			if( isset($smtp_setting['enable'] ) && $smtp_setting['enable'] == 'yes' ){
				$retval = $email->send($to, $subject, $message);
			}
			else{
				$headers = "MIME-Version: 1.0\r\n";
				$headers .= "Content-Type: text/html; charset=ISO-8859-1\r\n";
				
				$retval = $email->send($to,$subject, $message,$headers);
			}
			
			if ($retval == true) {
				$this->flash->setFlash($this->sap_common->lang('reset_password_link_email_msg'), 'success');
				$this->common->redirect('forgot_password');
			} else {
				$this->flash->setFlash($this->sap_common->lang('email_not_sent_msg'), 'error');
				$this->common->redirect('forgot_password');
			}
		} else {
			$this->flash->setFlash($this->sap_common->lang('email_not_found_msg'), 'error');
			$this->common->redirect('forgot_password');
		}
	}

	/**
	 * Reset Password
	 * 
	 * @package Social Auto Poster
	 * @since 1.0.0
	 */
	public function reset_password() {

		global $router, $match;

		$result = '';

		if (!empty($_GET['token'])) {

			$result = $this->_db->get_results("SELECT * FROM " . $this->_table_name . " where `token` = '{$_GET['token']}' ");

			if (count($result) <= 0) {
				$this->flash->setFlash($this->sap_common->lang('token_expired_msg'), 'error');
				$this->common->redirect('forgot_password');
			}
		} else {
			$this->flash->setFlash($this->sap_common->lang('token_expired_msg'), 'error');
			$this->common->redirect('forgot_password');
		}

		if (isset($_POST['reset_password_submit'])) {
			extract($_POST);

			if (empty($password)) {
				$this->flash->setFlash($this->sap_common->lang('enter_password_msg'), 'error');
			}

			if (empty($confirm_password)) {
				$this->flash->setFlash($this->sap_common->lang('re_enter_new_password_msg'), 'error');
			}

			if ($password != $confirm_password) {
				$this->flash->setFlash($this->sap_common->lang('re_enter_same_password_msg'), 'error');
			} elseif (!empty($password) && !empty($confirm_password)) {

				$result = $this->_db->update($this->_table_name, array('password' => md5($password)), array('token' => $_GET['token']));
				if ($result) {
					$this->flash->setFlash($this->sap_common->lang('password_update_success_msg'), 'success');
				} else {
					$this->flash->setFlash($this->sap_common->lang('error_update_msg'), 'error');
				}
			}
		}

		//Includes Html files for reset password page
		$template_path = $this->common->get_template_path('Users' . DS . 'reset-password.php' );
		include_once( $template_path );
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
			$result = $this->_db->get_results( "SELECT * FROM sap_plans where status = '1' ORDER BY `created` DESC" );
		} catch (Exception $e) {
			return $e->getMessage();
		}

		//Return result
		return $result;
	}

	/**
	 * Render Sign Up form
	 * 
	 * @package Social Auto Poster
	 * @since 2.0.0
	 */
	public function user_signup(){	
		

		$user 	= sap_get_current_user();
		
		if( empty( $user) ){
			$template_path = $this->common->get_template_path('Users' . DS . 'signup.php' );
			include_once( $template_path );	
		}
		else {
			$this->common->redirect('login');
		}
	}


	/**
	 * 
	 */	

	/**
	 * Save Customer
	 * 
	 * Hendle to save customer
	 * 
	 * @package Social Auto Poster
	 * @since 2.0.0
	 */
	public function save_user(){
		 
		//Check form submit request
		if ( isset($_POST['form-submitted']) ) {


			$plans = $this->get_plans();
			
			$smtp_setting = $this->setting->get_options('sap_smtp_setting');


			$_SESSION['register_data'] = $_POST;
			
			$error = false;

			// check the first name is empty
			if ( empty(trim($_POST['sap_firstname'])) ) {
				$error = true;
				$this->flash->setFlash( $this->sap_common->lang('enter_fname_msg'), 'error' );
			}

			if ( empty(trim($_POST['sap_email'])) ) {
				$error = true;
				$this->flash->setFlash( $this->sap_common->lang('enter_email_msg'), 'error' );
			}
			elseif ( $this->_db->exists($this->_table_users, 'email', array('email' => trim($_POST['sap_email']))) ) {
				$error = true;
				$this->flash->setFlash( $this->sap_common->lang('email_exists_msg'), 'error' );
			}

			if ( empty(trim($_POST['sap_password'])) ) {
				$error = true;
				$this->flash->setFlash( $this->sap_common->lang('enter_password_msg'), 'error' );
			}
			elseif ( empty(trim($_POST['sap_repassword'])) ) {
				$error = true;
				$this->flash->setFlash( $this->sap_common->lang('re_enter_password_msg'), 'error' );
			}
			elseif ( trim($_POST['sap_password']) != trim($_POST['sap_repassword']) ) {
				$error = true;
				$this->flash->setFlash( $this->sap_common->lang('both_password_same_msg'), 'error' );
			}

			$role = isset( $_POST['sap_role'] ) ? $_POST['sap_role'] : '';
			if ( !empty( $plans ) && 'user' == $role && empty(trim($_POST['sap_plan'])) ) {
				$error = true;
				$this->flash->setFlash($this->sap_common->lang('select_valid_plan_msg') , 'error' );
			}

			// Check if no error
			if( $error ) {
				header( "Location:" . SAP_SITE_URL . "/signup/" );
				exit;
			}

			$email_verification_tokan = generate_random_string();
			// Prepare data for store post in DB
			$member_data = array(
				'first_name'	=> isset( $_POST['sap_firstname'] ) ? trim($this->_db->filter($_POST['sap_firstname'])) : '',
				'last_name'		=> isset( $_POST['sap_lastname'] ) ? trim( $this->_db->filter($_POST['sap_lastname'])) : '',
				'email'			=> isset( $_POST['sap_email'] ) ? trim( $this->_db->filter($_POST['sap_email'])) : '',
				'password'		=> isset( $_POST['sap_password'] ) ? md5(trim($_POST['sap_password'])) : '',
				'role'			=> isset( $_POST['sap_role'] ) ? trim($_POST['sap_role']) : '',
				'email_verification_tokan' => $email_verification_tokan,				
				'status'		=> isset( $_POST['sap_status'] ) ? '1' : '0',
				'token'			=> '',
				'forgot_time'	=> '',
				'modified'		=> date( 'Y-m-d H:i:s' ),
				'created'		=> date( 'Y-m-d H:i:s' ),
			);

			$email_verification = $this->setting->get_options('enable_email_verification');
				
			if( $email_verification != 'yes'){
				$member_data['email_verification_tokan'] = '';
				$member_data['status'] = '1';
			}

			$member_data = $this->_db->escape($member_data);

			$stripe_payment_result = '';

			if ( $this->_db->insert($this->_table_users, $member_data) ) {

				$user_id = $this->_db->lastid();				

				$_POST['user_id'] = $user_id;				

				//Check Payment gateway type
				if(isset($_POST['gateway_type']) && !empty($_POST['gateway_type']) && $_POST['gateway_type'] == 'stripe' && !empty($_POST['stripe_payment_id'])){

					$stripe_payment_result = $this->payment->make_other_payments( $_POST );
					$stripe_payment_id = $_POST['stripe_payment_id'];
					$stripe_payment_next_action = $_POST['stripe_payment_next_action'];
					

				}

				if(isset($_POST['gateway_type']) && !empty($_POST['gateway_type']) && $_POST['gateway_type'] == 'manual'){ 

					$this->payment->make_manual_payment( $_POST );
				}
				
				if( !isset( $_POST['gateway_type'] ) ){ //For Free Plan
				
					$login_user = $this->login_user_details($user_id);
					$plan_data  = $this->plan->get_plan( $_POST['sap_plan'], true );

					$expiration_days = !empty($plan_data->subscription_expiration_days)? $plan_data->subscription_expiration_days :0;

					//completed
					$expiration_date = '';
					if( !empty( $expiration_days ) ){
						$current_date 	 = date('Y-m-d H:i:s');
						$expiration_date = date('Y-m-d', strtotime($current_date. ' + '.$expiration_days.' day'));
					}
					$update_data = array(
						'user_id'           => $user_id,
						'plan_id'           => $_POST['sap_plan'],
						'customer_id' 	    => '',
						'customer_name'     => $this->_db->filter($_POST['sap_firstname']) .' '. $this->_db->filter($_POST['sap_lastname']),
						'membership_status' => '1',
						'recurring'			=> '0',
						'expiration_date'   => $expiration_date,
						'created_date'      => date('Y-m-d H:i:s'),
						'modified_date'     => date('Y-m-d H:i:s'),
					);					
						
					$update_data = $this->_db->escape($update_data);
					$result = $this->_db->insert($this->_table_membership, $update_data);

					$membership_id = $this->_db->lastid();

					$insert_payment = array(
						'user_id' 		=> $user_id,
						'membership_id' => $membership_id,
						'plan_id' 		=> $_POST['sap_plan'],
						'customer_id' 	=> '',
						'customer_name' => $login_user->first_name .' '.$login_user->last_name,
						'customer_email' => $login_user->email,
						'payment_date' 	=> date('Y-m-d H:i:s'),
						'amount' 		=> 0,
						'type' 			=> '0',
						'gateway' 		=> 'manual',
						'payment_status'=> '1',
						'transaction_id'=> '',
						'transaction_data' => '',
						'created_date' 	=> date('Y-m-d H:i:s'),
						'modified_date'     => date('Y-m-d H:i:s'),
					);

					$insert_payment = $this->_db->escape($insert_payment);
					$result = $this->_db->insert($this->_table_payment_history, $insert_payment);
				}

				// Notify member
				if ( isset($_POST['sap_notify']) && $_POST['sap_notify'] == 'yes' ) {

					$email 		= new Sap_Email();
					$to 		= isset( $member_data['email'] ) ? $member_data['email'] : '';

					$mingle_site_name  = $this->setting->get_options('mingle_site_name' );

					if( ! empty( $mingle_site_name ) ) {
						$subject 	= "Your account created at Mingle - " . $mingle_site_name;
					} else {
						$subject 	= "Your account created at Mingle - " . SAP_NAME;
					}


					$subscription_details 	= $this->get_user_subscription_details( $user_id );

					ob_start();
					$template_path = $this->common->get_template_path('Members' . DS . 'new-account-user-notification-temp.php' );
					include_once( $template_path );										
					$message = ob_get_clean();

					ob_start();
					$template_path = $this->common->get_template_path('Members' . DS . 'new-account-email-verification-temp.php' );
					include_once( $template_path );
						
					$email_verification_message = ob_get_clean();

					if( isset($smtp_setting['enable'] ) && $smtp_setting['enable'] == 'yes' ){
						$email->send($to, $subject, $message);

						$email_verification = $this->setting->get_options('enable_email_verification');

						if( $email_verification == 'yes' ){
							$email->send($to, 'Email Verification', $email_verification_message);
						}
					}
					else{
						
						$headers = "MIME-Version: 1.0\r\n";
						$headers .= "Content-Type: text/html; charset=ISO-8859-1\r\n";

						$email->send($to, $subject, $message,$headers);

						$email_verification = $this->setting->get_options('enable_email_verification');

						if( $email_verification == 'yes' ){
							$email->send($to, 'Email Verification', $email_verification_message,$headers);
						}
					}
				}

				$email_verification = $this->setting->get_options('enable_email_verification');
				
				if( $email_verification == 'yes'){
					$this->flash->setFlash($this->sap_common->lang('verification_link_send'), 'success');
				}
				else{
					$this->flash->setFlash($this->sap_common->lang('account_added_successfully_msg'), 'success');
				}

				// IF user register without membership
				if( empty( $plans ) ){
					$email_verification = $this->setting->get_options('enable_email_verification');

					//$this->flash->setFlash($this->sap_common->lang('account_added_successfully_msg'), 'success');					
					header( "Location:" . SAP_SITE_URL );
					exit;
				}

				if( isset($_POST['gateway_type'] )  && $_POST['gateway_type'] == 'stripe' && empty( $stripe_payment_id )){

					/* header( "Location:" . SAP_SITE_URL . "/signup/" );
					exit; */
					if($_POST['apply_coupon_amount'] != 0) {
						header( "Location:" . SAP_SITE_URL . "/signup/" );
						exit;
					} else {
						header( "Location:" . SAP_SITE_URL.'/thank-you/'.$user_id );
						exit;
					}
					
				}
				else{
					header( "Location:" . SAP_SITE_URL.'/thank-you/'.$user_id );
					exit;
				}
			}

			$this->flash->setFlash($this->sap_common->lang('saving_data_error_msg'), 'error');

			header( "Location:" . SAP_SITE_URL . "/signup/" );
			exit;
		}
	}


	/**
	 * Get get plan details by plan id
	 * 
	 * Handels list setting Option get
	 * 
	 * @package Social Auto Poster
	 * @since 2.0.0
	 */
	public function get_plan($plan_id, $object) {
		
		$result = array();

		if ( !empty($plan_id) ) {
			try {
				$result = $this->_db->get_row( "SELECT * FROM " . $this->_plan_table . " where `id` = '{$plan_id}'", $object );
			} catch (Exception $e) {
				return $e->getMessage();
			}
			// Return result
			return $result;
		}
	}	



	/**
	 * Get user details by ID
	 * 	 
	 * 
	 * @package Social Auto Poster
	 * @since 1.0.0
	 */
	public function get_user_by_id($user_id,$object) {
		
		$result = array();

		if ( !empty($user_id) ) {
			try {
				$result = $this->_db->get_row( "SELECT * FROM " . $this->_table_name . " where `id` = '{$user_id}'", $object);
			} catch (Exception $e) {
				return $e->getMessage();
			}
			// Return result
			return $result;
		}
	}


	/**
	 * Get user subscription details
	 * 	 
	 * 
	 * @package Social Auto Poster
	 * @since 1.0.0
	 */
	public function get_user_subscription_details( $user_id ){

		if ( !empty($user_id) ) {
			try {
				$result = $this->_db->get_row( "SELECT m.*,p.name,p.subscription_expiration_days,p.networks,p.price , sp.id as payment_id FROM sap_membership as m inner join sap_plans as p on m.plan_id = p.id INNER JOIN sap_payment_history as sp on sp.membership_id = m.id  WHERE m.user_id = ".$user_id." ",'ARRAY_A');

			} catch (Exception $e) {
				return $e->getMessage();
			}

			// Return result
			return $result;
		}
	}


	/**
	 * Thank you page after payment success
	 * 
	 * @package Social Auto Poster
	 * @since 2.0.0
	 */
	public function thank_you( $user_details ){


		// Force Logout after payment success
		session_destroy();
		
		if ( !empty($user_details['id']) ) {

			$subscription_details 	= $this->get_user_subscription_details($user_details['id']);
				
			$template_path = $this->common->get_template_path('Users' . DS . 'thank-you.php' );
			include_once( $template_path );
		}
		else{
			header( "Location:" . SAP_SITE_URL );
			exit;
		}
	}



	/**
	 * Thank you page for upgdrade membership
	 * 
	 * @package Social Auto Poster
	 * @since 2.0.0
	 */

	public function upgrade_thank_you( $user_details ){

		// Force Logout after payment success
		session_destroy();
		
		if ( !empty($user_details['id']) ) {

			$this->flash->setFlash($this->sap_common->lang('plan_upgraded_successfully'), 'success');

			$subscription_details 	= $this->get_user_subscription_details($user_details['id']);
			
			$template_path = $this->common->get_template_path('Users' . DS . 'upgrade-thank-you.php' );
			include_once( $template_path );

				
		}
		else{
			header( "Location:" . SAP_SITE_URL );
			exit;
		}
	}	


	/**
	 * Resend email verification email
	 * 
	 * @package Social Auto Poster
	 * @since 2.0.0
	 */
	public function resend_verification_email( $user_data ){	

		if( isset($user_data['id']) && !empty( $user_data['id'] ) ){

			$email 		= new Sap_Email();

			$smtp_setting = $this->setting->get_options('sap_smtp_setting');

			$user = $this->get_user_by_id($user_data['id'],true);

			$to = $user->email;

			$email_verification_tokan = generate_random_string();

			$update_data['email_verification_tokan'] = $email_verification_tokan;

			ob_start();

			$template_path = $this->common->get_template_path('Members' . DS . 'new-account-email-verification-temp.php' );
			include_once( $template_path );			

			$email_verification_message = ob_get_clean();
			
			if( isset($smtp_setting['enable'] ) && $smtp_setting['enable'] == 'yes' ){			
				$email->send($to, 'Email Verification', $email_verification_message);
			}
			else{

				$headers  = "MIME-Version: 1.0\r\n";
				$headers .= "Content-Type: text/html; charset=ISO-8859-1\r\n";

				$email_verification = $this->setting->get_options('enable_email_verification');

				if( $email_verification == 'yes' ){
					$email->send($to, 'Email Verification', $email_verification_message,$headers);
				}
			}

			$update_data = $this->_db->escape($update_data);
			$result = $this->_db->update($this->_table_name, $update_data, array('id'=>$user->id ) );

			$this->flash->setFlash($this->sap_common->lang('verification_link_send'), 'success');
			$this->common->redirect('login');
			exit;
		}
	}

	/**
	 * Check coupon code valid or not
	 * 
	 * @package Social Auto Poster
	 * @since 2.0.0
	 */
	public function check_coupon_code(){
		$coupon_array = array();
		if(!empty(trim($_POST['coupon_code']))){
			$tot_rows = $this->_db->num_rows('SELECT * from '.$this->_table_coupons.' where coupon_code="'.trim($_POST['coupon_code']).'"');
			if ( $tot_rows > 0 ) {
				$query = 'SELECT * from '.$this->_table_coupons.' where coupon_code="'.trim($_POST['coupon_code']).'"';
				$result = $this->_db->get_results( $query );
				if(isset($result[0]) && ($result[0]->coupon_amount != "" || $result[0]->coupon_amount != null)) {
					if($result[0]->coupon_expiry_date != "" || $result[0]->coupon_expiry_date != "") {
						if(date('Y-m-d', strtotime($result[0]->coupon_expiry_date)) >= date('Y-m-d')) {
							if($result[0]->coupon_status == 'draft') {
								$coupon_array = [
									'status' => 422,
									'message' => $this->sap_common->lang('coupon_code_is_not_valid'),
									'data' => (object)[]
								];
							} else if($result[0]->coupon_status == 'used') {
								$coupon_array = [
									'status' => 422,
									'message' => $this->sap_common->lang('this_coupon_code_is_already_used'),
									'data' => (object)[]
								];
							} else {
								$coupon_array = [
									'status' => 200,
									'message' => $this->sap_common->lang('coupon_code_is_applied_successfully'),
									'data' => $result[0]
								];
							}
							echo json_encode($coupon_array);
						} else {
							$coupon_array = [
								'status' => 422,
								'message' => $this->sap_common->lang('cupon_code_is_expired'),
								'data' => (object)[]
							];
							echo json_encode($coupon_array);
						}
					} else if($result[0]->coupon_status == 'draft') {
						$coupon_array = [
							'status' => 422,
							'message' => $this->sap_common->lang('coupon_code_is_not_valid'),
							'data' => (object)[]
						];
						echo json_encode($coupon_array);
					} else if($result[0]->coupon_status == 'used') {
						$coupon_array = [
							'status' => 422,
							'message' => 'This coupon code is already used',
							'data' => (object)[]
						];
						echo json_encode($coupon_array);
					} else {
						$coupon_array = [
							'status' => 200,
							'message' => $this->sap_common->lang('coupon_code_is_applied_successfully'),
							'data' => $result[0]
						];
						echo json_encode($coupon_array);
					}
				}
			} else {
				$coupon_array = [
					'status' => 422,
					'message' => $this->sap_common->lang('coupon_code_is_not_valid'),
					'data' => (object)[]
				];
				echo json_encode($coupon_array);
			}
		} else {
			$coupon_array = [
				'status' => 422,
				'message' => $this->sap_common->lang('enter_coupon_code'),
				'data' => (object)[]
			];
			echo json_encode($coupon_array);
		}
	}
}
