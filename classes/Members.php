<?php

/* Check the absolute path to the Social Auto Poster directory. */
if ( !defined( 'SAP_APP_PATH' ) ) {
    // If SAP_APP_PATH constant is not defined, perform some action, show an error, or exit the script
    // Or exit the script if required
    exit();
}

/**
 * Members Class function
 * 
 * A class contains common function to be used to throughout the System
 *
 * @package Social Auto Poster
 * @since 1.0.0
 */
class SAP_Members{

	private $db;
	private $table_name;
	public $common;
	public $flash;
	public $user;
	public $payments;	
	public $settings;
	public $sap_common, $plan_table_name, $table_membership, $plan;

	public function __construct() {
		global $sap_db_connect, $sap_common;

		$this->db = $sap_db_connect;
		$this->table_name = 'sap_users';
		$this->plan_table_name = 'sap_plans';
		$this->table_membership = 'sap_membership';

		$this->flash 	= new Flash();
		$this->common 	= new Common();
		$this->settings = new SAP_Settings();
		$this->sap_common = $sap_common;


		if( !class_exists('SAP_Users')){
			require_once CLASS_PATH.'/Users.php';
		}
		$this->user = new SAP_Users();

		if( !class_exists('SAP_Payments')){
			require_once CLASS_PATH.'/Payments.php';
		}
		$this->payments = new SAP_Payments();

		if( !class_exists('SAP_Plans')){
			require_once CLASS_PATH.'/Plans.php';
		}
		if( !class_exists('SAP_Plans')){
			require_once CLASS_PATH.'/Plans.php';
		}

		$this->plan = new SAP_Plans();	


	}

	/**
	 * Listing page of Users
	 * 
	 * @package Social Auto Poster
	 * @since 1.0.4
	 */
	public function index() {
		// Includes Html files for Posts list
		if ( !sap_current_user_can('members') ) {
			if ( !empty($_SESSION['user_details']) ) {

				$template_path = $this->common->get_template_path('Members' . DS . 'index.php' );
				include_once( $template_path );				
			} 
		}
		else {
			$this->common->redirect('login');
		}
	}

	/**
	 * AJax members listing
	 * 
	 * @package Social Auto Poster
	 * @since 1.0.4
	 */
	public function members_datasource_response() {
		// Includes Html files for Posts list


		if ( !sap_current_user_can('members') ) {

			if ( !empty($_SESSION['user_details']) ) {

				$result = array();
				$data = array();
				$curUserId = isset( $_SESSION['user_details']['user_id'] ) ? $_SESSION['user_details']['user_id'] : 0;

				try {	

					$start  = $_GET['start'];
					$length = $_GET['length'];


					$order_column 	=  isset( $_GET['order'][0]['column'] ) ? $_GET['order'][0]['column'] :'';
					$order_dir 		=  isset( $_GET['order'][0]['dir'] ) ? $_GET['order'][0]['dir'] :'';
					
					switch( $order_column ) {

						case '0':
							$orderby = 'u.id';
						break;
						case '1':
							$orderby = 'u.first_name';
						break;
						case '2':
							$orderby = 'u.email';
						break;

						case '3':
							$orderby = 'u.role';
						break;

						case '4':
							$orderby = 'u.status';
						break;

						case '5':
							$orderby = 'u.role';
						break;

						case '6':
							$orderby = 'u.created';
						break;
						case '7':
							$orderby = 'u.id';
						break;
					}
					

					$query = 'SELECT u.* FROM '.$this->table_name .' as u WHERE 1=1 ';
					
					if( !empty( $_GET['search']['value'] ) ) {
						$search = trim($_GET['search']['value']);

						$query .= 'AND (u.first_name like "'.$search.'%" OR u.last_name like "'.$search.'%" OR u.email like "'.$search.'%")';
					}

					if( isset($_GET['searchByStatus']) && $_GET['searchByStatus'] != '' ) {


						$searchByStatus = $_GET['searchByStatus'];

						$query .= 'AND (u.status = "'.$searchByStatus.'")';
					}

					$query .= ' ORDER BY '.$orderby.' '.$order_dir;
					
					$query .= ' LIMIT '.$start.' , '.$length;

					$result = $this->db->get_results( $query );

					$total_count = $this->db->get_row('SELECT count(*)  as count FROM '.$this->table_name,'ARRAY_A' );
					
					
				} catch (Exception $e) {
					return $e->getMessage();
				}

				$number = 1;
				foreach ( $result as $member ) {
					if( $curUserId == $member->id ) {
						$checkbox = '<input type="checkbox" value="current" />';
					} else{
						$checkbox = '<input type="checkbox" name="member_id[]" value="' . $member->id . '" />';
					}

					$name = '<a href="'.SAP_SITE_URL . '/member/edit/' . $member->id.'">'.$member->first_name . ' ' . $member->last_name;
					$status = $member->status == '1' ? '<div class="plan-active">Active</div>':'<div class="plan-inactive">In-active</div>';
					$action_links = '<a href="'.SAP_SITE_URL . '/member/edit/' . $member->id.'"><i class="fa fa-pencil-square-o" aria-hidden="true"></i></a>';

					if( $curUserId != $member->id ) {
						$action_links .= ' <a class="delete_member" id="member_'.$member->id.'" aria-data-id="'.$member->id.'"><i class="fa fa-trash" aria-hidden="true"></i></a>';
					}
					
					$data[] = array(
						$checkbox,
						$number,
						$name,
						$member->email,
						ucwords($member->role),						
						$status,						
						sap_format_date($member->created,true),
						$action_links
					);
					$number++;
				}

				$results = array(
					"draw" => $_GET['draw'],
					"recordsTotal" 	=> count($result),
					"recordsFiltered" => $total_count->count,
				  	"data"=> $data
				);

				echo json_encode($results);
			} 
		} else {
			$this->common->redirect('login');
		}
	}

	/**
	 * Add new member
	 *
	 * @package Social Auto Poster
	 * @since 1.0.4
	 */
	public function add_new_member() {
		// Includes Html files for Posts list
		if ( !sap_current_user_can('plans') && !empty($_SESSION['user_details']) ) {

			$template_path = $this->common->get_template_path('Members' . DS . 'add.php' );
			include_once( $template_path );
		} else {
			$this->common->redirect('login');
		}
	}

	/**
	 * Edit member
	 *
	 * @package Social Auto Poster
	 * @since 1.0.4
	 */
	public function edit_member() {
		
		if ( !sap_current_user_can('plans') && !empty($_SESSION['user_details']) ) {			
			$template_path = $this->common->get_template_path('Members' . DS . 'edit.php' );
			include_once( $template_path );
		}
		else {
			$this->common->redirect('login');
		}
	}

	/**
	 * Get all posts
	 * 
	 * Handels post listing
	 * 
	 * @package Social Auto Poster
	 * @since 1.0.0
	 */
	public function get_members() {
		$result = array();
		try {			
			$result = $this->db->get_results( "SELECT u.* FROM " . $this->table_name . " as u WHERE 1=1 ORDER BY u.created DESC" );
		} catch (Exception $e) {
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
	 * @since 1.0.0
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
	 * Save member
	 *
	 * @package Social Auto Poster
	 * @since 1.0.4
	 */
	public function save_member() {
		 
		//Check form submit request
		if ( isset($_POST['form-submitted']) ) {

			$error = false;

			// check the first name is empty
			if ( empty(trim($_POST['sap_firstname'])) ) {
				$error = true;
				$this->flash->setFlash( $this->sap_common->lang('enter_fname_msg'), 'error' );
			}

			if ( empty(trim($_POST['sap_email'])) ) {
				$error = true;
				$this->flash->setFlash( $this->sap_common->lang('enter_email_msg'), 'error' );
			} elseif ( $this->db->exists($this->table_name, 'email', array('email' => trim($_POST['sap_email']))) ) {
				$error = true;
				$this->flash->setFlash( $this->sap_common->lang('email_exists_msg'), 'error' );
			}

			if ( empty(trim($_POST['sap_password'])) ) {
				$error = true;
				$this->flash->setFlash( $this->sap_common->lang('enter_password_msg'), 'error' );
			} elseif ( empty(trim($_POST['sap_repassword'])) ) {
				$error = true;
				$this->flash->setFlash( $this->sap_common->lang('re_enter_password_msg'), 'error' );
			} elseif ( trim($_POST['sap_password']) != trim($_POST['sap_repassword']) ) {
				$error = true;
				$this->flash->setFlash( $this->sap_common->lang('both_password_same_msg'), 'error' );
			}

			

			// Check if no error
			if( $error ) {
				header( "Location:" . SAP_SITE_URL . "/member/add/" );
				exit;
			}

			// Prepare data for store post in DB
			$member_data = array(
				'first_name'	=> isset( $_POST['sap_firstname'] ) ? trim($this->db->filter($_POST['sap_firstname'])) : '',
				'last_name'		=> isset( $_POST['sap_lastname'] ) ? $this->db->filter(trim($_POST['sap_lastname'])) : '',
				'email'			=> isset( $_POST['sap_email'] ) ? $this->db->filter(trim($_POST['sap_email'])) : '',
				'password'		=> isset( $_POST['sap_password'] ) ? md5(trim($_POST['sap_password'])) : '',
				'role'			=> isset( $_POST['sap_role'] ) ? trim($_POST['sap_role']) : '',
				
				'status'		=> isset( $_POST['sap_status'] ) ? '1' : '0',
				'token'			=> '',
				'forgot_time'	=> '',
				'modified'		=> date( 'Y-m-d H:i:s' ),
				'created'		=> date( 'Y-m-d H:i:s' ),
			);
			
			$member_data = $this->db->escape($member_data);
			if ( $this->db->insert($this->table_name, $member_data) ) {
				
				$member_id = $this->db->lastid();

				// Notify member
				if ( isset($_POST['sap_notify']) && $_POST['sap_notify'] == 'yes' ) {

					$email = new Sap_Email();

					$to = isset( $member_data['email'] ) ? $member_data['email'] : '';
					$subject = "Your account created at Mingle - " . empty( $this->settings->get_options('mingle_site_name') ) ? SAP_NAME : $this->settings->get_options('mingle_site_name');;

					ob_start();
					
					$template_path = $this->common->get_template_path('Members' . DS . 'new-account-user-notification-temp.php' );
					include_once( $template_path );

					$message = ob_get_clean();

					$retval = $email->send($to, $subject, $message);
				}
				
				$this->flash->setFlash($this->sap_common->lang('new_member_success_msg'), 'success');

				header( "Location:" . SAP_SITE_URL . "/members/" );
				exit;
			}

			$this->flash->setFlash($this->sap_common->lang('saving_data_error_msg'), 'error');

			header( "Location:" . SAP_SITE_URL . "/member/add/" );
			exit;
		}
	}

	/**
	 * Update member data
	 *
	 * @package Social Auto Poster
	 * @since 1.0.4
	 */
	public function update_member() {

		if ( isset($_POST['form-updated']) ) {

			$member_id = $_POST['id'];

			$error = false;

			// check the first name is empty
			if ( empty(trim($_POST['sap_firstname'])) ) {
				$error = true;
				$this->flash->setFlash( $this->sap_common->lang('enter_fname_msg'), 'error' );
			}

			if ( empty(trim($_POST['sap_email'])) ) {
				$error = true;
				$this->flash->setFlash( $this->sap_common->lang('enter_email_msg'), 'error' );
			} else {

				$email = trim($_POST['sap_email']);
				$row = $this->db->get_row( "SELECT id FROM {$this->table_name} WHERE email = '{$email}' AND id != {$member_id};" );

				if( !empty($row) ) {
					$error = true;
					$this->flash->setFlash( $this->sap_common->lang('email_exists_msg'), 'error' );
				}
			}

			if ( ! empty(trim($_POST['sap_password'])) && empty(trim($_POST['sap_repassword'])) ) {
				$error = true;
				$this->flash->setFlash( $this->sap_common->lang('re_enter_password_msg'), 'error' );
			} elseif ( !empty(trim($_POST['sap_password'])) && !empty(trim($_POST['sap_repassword'])) &&
				trim($_POST['sap_password']) != trim($_POST['sap_repassword']) ) {

				$error = true;
				$this->flash->setFlash( $this->sap_common->lang('both_password_same_msg'), 'error' );
			}			

			// Check if no error
			if( $error ) {
				header( "Location:" . SAP_SITE_URL . "/member/edit/" . $member_id );
				exit;
			}

			//Prepare data for store post in DB
			$prepare_data = array(
				'first_name'	=> isset( $_POST['sap_firstname'] ) ? $this->db->filter(trim($_POST['sap_firstname'])) : '',
				'last_name'		=> isset( $_POST['sap_lastname'] ) ? $this->db->filter(trim($_POST['sap_lastname'])) : '',
				'email'			=> isset( $_POST['sap_email'] ) ? $this->db->filter(trim($_POST['sap_email']) ): '',
				'role'			=> isset( $_POST['sap_role'] ) ? trim($_POST['sap_role']) : '',
				'status'		=> isset( $_POST['sap_status'] ) ? '1' : '0',
				'modified'		=> date( 'Y-m-d H:i:s' ),
			);

			

			if ( !empty($_POST['sap_password']) ) {
				$prepare_data['password'] = md5( trim($_POST['sap_password']) );
			}

			// Update the data

			$member_data = $this->db->escape($member_data);
			if ( $this->db->update($this->table_name, $prepare_data, array('id' => $member_id)) ) {

				$this->flash->setFlash($this->sap_common->lang('customer_update_success_msg'), 'success');
				header( "Location:" . SAP_SITE_URL . "/member/edit/" . $member_id );
				exit;
			}
			else{
				
				$this->flash->setFlash($this->sap_common->lang('saving_data_error_msg'), 'error');
				header( "Location:" . SAP_SITE_URL . "/member/edit/" . $member_id );
				exit;
			}
		}
	}

	/**
	 * Delete Member
	 *
	 * @package Social Auto Poster
	 * @since 1.0.4
	 */
	public function delete_member() {
		
		if ( !empty($_REQUEST['member_id']) ) {

			$result = array();
			
			$member_id = $_REQUEST['member_id'];
			$conditions = array('id' => $member_id);
			$is_deleted = $this->db->delete( $this->table_name, $conditions );

			if ( $is_deleted ) {
				$result = array('status' => '1');
				$memebership_where = array('user_id' => $member_id );
				$this->db->delete( $this->table_membership, $memebership_where );
			} else {
				$result = array('status' => '0');
			}
			
			echo json_encode($result);
			die;
		}
	}

	/**
	 * Delete multiple member
	 *
	 * @package Social Auto Poster
	 * @since 1.0.4
	 */
	public function delete_multiple_member() {

		if ( !empty($_REQUEST['id']) ) {
			
			$result = array();
			
			$member_id = $_REQUEST['id'];

			foreach ( $member_id as $key => $value ) {

				$conditions = array( 'id' => $value );
				$is_deleted = $this->db->delete( $this->table_name, $conditions );
				
				$memebership_where = array('user_id' => $value );
				$this->db->delete( $this->table_membership, $memebership_where );
			}

			if ( $is_deleted ) {
				$result = array('status' => '1');
				$this->flash->setFlash( $this->sap_common->lang('selected_customers_delete'), 'success' );
			}
			else {
				$result = array('status' => '0');
			}
			
			echo json_encode($result);
			die;
		}
	}


	/**
	 * Get selected status posts
	 * 
	 * Handels post listing
	 * 
	 * @package Social Auto Poster
	 * @since 1.0.0
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
	 * @since 1.0.0
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
	 * Hendle to verify member email
	 *
	 * @package Social Auto Poster
	 * @since 1.0.4
	 */
	public function member_email_verification(){

		$email_tokan = '';

		if( isset( $_GET['params']) && !empty( $_GET['params'] ) ){
			
			$email_tokans = array_filter(explode("/",$_GET['params'])); 
			$email_tokan  = isset($email_tokans[1]) ? $email_tokans[1] : '';


			if( !empty( $email_tokan ) ){

				$result = $this->db->get_row( "SELECT email_verification_tokan,id FROM " . $this->table_name . " where `email_verification_tokan` = '{$email_tokan}'", true );
								

				if( $result->email_verification_tokan == $email_tokan ){

					$prepare_data['email_verification_tokan'] = '';
					$prepare_data['status'] = '1';

					$update = $this->db->update($this->table_name, $prepare_data, array('id' => $result->id));

					$this->flash->setFlash( $this->sap_common->lang('email_verified_successfully'), 'success' );
					header( "Location:" . SAP_SITE_URL );
					die;
				}
				else{
					$this->flash->setFlash( $this->sap_common->lang('invalid_email_verification_tokan'), 'error' );
					header( "Location:" . SAP_SITE_URL );
					die;
				}				
			}			
		}
		else{
			$this->flash->setFlash($this->sap_common->lang('invalid_email_verification_tokan'), 'error' );
			header( "Location:" . SAP_SITE_URL );
			die;
		}		
	}



	/**
	 * show user subscription details
	 *
	 * @package Social Auto Poster
	 * @since 2.0.0
	 */
	public function your_subscription() {
		

		if (isset($_SESSION['user_details']) && !empty($_SESSION['user_details'])) {

			// Get logged in user details
            $user_details = $this->get_member($_SESSION['user_details']['user_id'],true);
            $user_role    =  $user_details->role;

            $user_details = json_decode(json_encode($user_details), true);
            
            $plan = isset($user_details['plan']) ? $user_details['plan'] : 0;
            $plan_data  = $this->plan->get_plan($plan, true );            
            
            $subscription_details 	= $this->user->get_user_subscription_details($user_details['id']);

            $max_plan = '';

            if( $user_role   != 'superadmin' ){ 
            	$max_plan = $this->plan->get_upgrade_plans($user_details['id']);
        	}          
           	
           	$template_path = $this->common->get_template_path('Members' . DS . 'subscription-details.php' );
			include_once( $template_path );
			
		}
		else {
			$this->common->redirect('login');
		}


	}
}