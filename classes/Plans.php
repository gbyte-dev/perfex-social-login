<?php

/* Check the absolute path to the Social Auto Poster directory. */
if ( !defined( 'SAP_APP_PATH' ) ) {
    // If SAP_APP_PATH constant is not defined, perform some action, show an error, or exit the script
    // Or exit the script if required
    exit();
}

/**
 * Plans Class function
 * 
 * A class contains common function to be used to throughout the System
 *
 * @package Social Auto Poster
 * @since 2.0.0
 */
class SAP_Plans{
	
	private $table_name;
	private $db;
	public $flash;
	public $common;	
	public $membership;
	public $sap_common, $users_table, $membership_table;

	public function __construct() {

		global $sap_common;
		$this->db = new Sap_Database();
		$this->table_name = 'sap_plans';
		$this->users_table = 'sap_users';
		$this->membership_table = 'sap_membership';
		$this->flash = new Flash();
		$this->common = new Common();
		$this->sap_common = $sap_common;
	}

	/**
	 * Listing page of Plans
	 * 
	 * @package Social Auto Poster
	 * @since 2.0.0
	 */
	public function index() {

		
		//Includes Html files for Posts list
		if ( !sap_current_user_can('plans') ) {

			$template_path = $this->common->get_template_path('Plans' . DS . 'index.php' );
			include_once( $template_path );
		}
		else {
			$this->common->redirect('login');
		}
	}

	/**
	 * AJax members listing
	 * 
	 * @package Social Auto Poster
	 * @since 2.0.0
	 */
	public function plan_datasource_response() {
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

						case '7':
							$orderby = 'u.id';
						break;
						case '2':
							$orderby = 'u.name';
						break;
						case '3':
							$orderby = 'u.description';
						break;

						case '4':
							$orderby = 'u.price';
						break;

						case '5':
							$orderby = 'u.networks';
						break;

						case '6':
							$orderby = 'u.status';
						break;

					}

					$query = 'SELECT u.* FROM '.$this->table_name .' as u WHERE 1=1 ';
					
					if( !empty( $_GET['search']['value'] ) ) {

						$search = $_GET['search']['value'];
						$query .= 'AND (u.name like "'.$search.'%")';
					}

					if( isset($_GET['searchByStatus']) && $_GET['searchByStatus'] != '' ) {


						$searchByStatus = $_GET['searchByStatus'];

						$query .= 'AND (u.status = "'.$searchByStatus.'")';
					}

					if( !empty( $orderby )){
						$query .= ' ORDER BY '.$orderby.' '.$order_dir;
					}
					else{
						$query .= ' ORDER BY u.id desc';
					}

					$query .= ' LIMIT '.$start.' , '.$length;					
					$result = $this->db->get_results( $query );

					$total_count = $this->db->get_row('SELECT count(*)  as count FROM '.$this->table_name,'ARRAY_A' );
					
					
				} catch (Exception $e) {
					return $e->getMessage();
				}

				$number  = 1;
				foreach ( $result as $plan ) {

					// Make plans empty
					$network_val = '';

					if( $curUserId == $plan->id ) {
						$checkbox = '<input type="checkbox" value="current" />';
					} else{
						$checkbox = '<input type="checkbox" name="plan_id[]" value="' . $plan->id . '" />';
					}

					$name = '<a href="'.SAP_SITE_URL . '/plan/edit/' . $plan->id.'">'.$plan->name ;
					$status = $plan->status == '1' ? '<div class="plan-active">Active</div>':'<div class="plan-inactive">In-active</div>';
					$action_links = '<a href="'.SAP_SITE_URL . '/plan/edit/' . $plan->id.'"><i class="fa fa-pencil-square-o" aria-hidden="true"></i></a>';

					$action_links .= ' <a class="delete_plan" id="plan_'.$plan->id.'" aria-data-id="'.$plan->id.'"><i class="fa fa-trash" aria-hidden="true"></i></a>';
					
					$networks = !empty( $plan->networks ) ? unserialize( $plan->networks ) : array();
					
					if( !empty( $networks ) ) {
						
						foreach ( $networks as $key => $network) {
							if( $network == 'gmb'){
								$networks[$key] = 'Google my business';
							}
						}
					}

					if( !empty( $networks ) ) {
						foreach ( $networks as $key => $network) {
							// Convert to lowercase
							$lowercaseString = strtolower($network);

							// Replace spaces with hyphens
							$finalkey = str_replace(' ', '-', $lowercaseString);
							$network_val .= '<div class="'. $finalkey .' finalnetwork">'. $network .'</div>';
						}
					}
					
					$data[] = array(
						$checkbox,
						$number,
						$name,
						mb_strimwidth($plan->description,0,200,'...'),
						'$'.$plan->price,	
						$network_val,			
						$status,						
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
	 * Add new plan
	 *
	 * @package Social Auto Poster
	 * @since 2.0.0
	 */
	public function add_new_plan() {


		// Includes Html files for Posts list
		if ( !sap_current_user_can('plans') && !empty($_SESSION['user_details']) ) {

			$template_path = $this->common->get_template_path('Plans' . DS . 'add.php' );
	
			include_once( $template_path );
		}
		else {
			$this->common->redirect('login');
		}
	}

	
	/**
	 * Edit Plan
	 * 
	 * @package Social Auto Poster
	 * @since 2.0.0
	 */
	public function edit_plan() {

		if ( !sap_current_user_can('plans') &&  !empty($_SESSION['user_details']) ) {			
			$template_path = $this->common->get_template_path('Plans' . DS . 'edit.php' );
			include_once( $template_path );

		}
		else {
			$this->common->redirect('login');
		}
	}

	
	/**
	 * Save Plan
	 * 
	 * @package Social Auto Poster
	 * @since 2.0.0
	 */
	public function save_plan() {

		// Check form submit request
		if ( isset($_POST['form-submitted']) ) {

			$error = false;

			// check the first name is empty
			if ( empty(trim($_POST['sap_name'])) ) {
				$error = true;
				$this->flash->setFlash($this->sap_common->lang('enter_plan_name'), 'error' );
			}

			// Check if no error
			if( $error ) {
				header( "Location:" . SAP_SITE_URL . "/plan/add/" );
				exit;
			}

			// Get active networks
			$networks = !empty( $_POST['sap_network'] ) ? $_POST['sap_network'] : array();
			$networks = serialize( $networks );

			// Prepare data for store post in DB
			$prepare_data = array(
				'name'				=> isset( $_POST['sap_name'] ) ? trim( $this->db->filter($_POST['sap_name'])) : '',
				'description'		=> isset( $_POST['sap_description'] ) ? trim($this->db->filter($_POST['sap_description'])) : '',
				'price'				=> isset( $_POST['sap_price'] ) ? trim($_POST['sap_price']) : '',				
				'subscription_expiration_days'		=> !empty( $_POST['subscription_expiration_days'] ) ? $this->db->filter($_POST['subscription_expiration_days']) : 0,
				
				'stripe_subscription_id'		=> isset( $_POST['sap_stripe_subscription_id'] ) ? trim($_POST['sap_stripe_subscription_id']) : '',

				'networks'			=> $networks,
				'status'			=> isset( $_POST['status'] ) ? $_POST['status'] : '0',
				'created'			=> date( 'Y-m-d H:i:s' ),
				'modified_date'		=> date( 'Y-m-d H:i:s' ),
			);
			
			$prepare_data = $this->db->escape( $prepare_data );
			
			
			if ( $this->db->insert($this->table_name, $prepare_data) ) {
				
				$plan_id = $this->db->lastid();
				
				$this->flash->setFlash($this->sap_common->lang('new_membership_level_success_msg'), 'success');

				header( "Location:" . SAP_SITE_URL . "/plan/edit/" . $plan_id );
				exit;
			}

			$this->flash->setFlash($this->sap_common->lang('saving_data_error_msg'), 'error');

			header( "Location:" . SAP_SITE_URL . "/plan/add/" );
			exit;
		}
	}

	/**
	 * Update Plan
	 * 
	 * @package Social Auto Poster
	 * @since 2.0.0
	 */
	public function update_plan() {

		if ( isset($_POST['form-updated']) ) {

			$plan_id = $_POST['id'];

			$error = false;

			// check the first name is empty
			if ( empty(trim($_POST['sap_name'])) ) {
				$error = true;
				$this->flash->setFlash( $this->sap_common->lang('enter_plan_name'), 'error' );
			}

			// Check if no error
			if( $error ) {
				header( "Location:" . SAP_SITE_URL . "/plan/edit/" . $plan_id );
				exit;
			}

			// Get active networks
			$networks = !empty( $_POST['sap_network'] ) ? $_POST['sap_network'] : array();
			$networks = serialize( $networks );

			// Prepare data for store post in DB
			$prepare_data = array(
				'name'				=> isset( $_POST['sap_name'] ) ? trim( $this->db->filter($_POST['sap_name'])) : '',
				'description'		=> isset( $_POST['sap_description'] ) ? trim( $this->db->filter($_POST['sap_description'])) : '',
				'price'				=> isset( $_POST['sap_price'] ) ? trim($_POST['sap_price']) : '',				
				'stripe_subscription_id'		=> isset( $_POST['sap_stripe_subscription_id'] ) ? trim($_POST['sap_stripe_subscription_id']) : '',
				'status'		=> isset( $_POST['status'] ) ? $_POST['status'] : '0',
				
				'subscription_expiration_days'		=> isset( $_POST['subscription_expiration_days'] ) ? $this->db->filter($_POST['subscription_expiration_days']) : 1,

				'networks'	=> $networks,
				'modified_date'			=> date( 'Y-m-d H:i:s' ),
			);

			$prepare_data = $this->db->escape($prepare_data);			

		
			// Update the data
			if ( $this->db->update($this->table_name, $prepare_data, array('id' => $plan_id) ) ) {

				$this->flash->setFlash($this->sap_common->lang('membership_update_success_msg'), 'success');
				header( "Location:" . SAP_SITE_URL . "/plan/edit/" . $plan_id );
				exit;
			}
			else{
				$this->flash->setFlash($this->sap_common->lang('saving_data_error_msg'), 'error');
				header( "Location:" . SAP_SITE_URL . "/plan/edit/" . $plan_id );
				exit;
			}
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

			$result = $this->db->get_results( "SELECT * FROM " . $this->table_name . " WHERE status = '1' ORDER BY convert(`price`, decimal) ASC" );
			
		} catch (Exception $e) {
			return $e->getMessage();
		}
		
		return $result;
	}

	/**
	 * Get plan by id
	 * 
	 * Handels plan data by plan id
	 * 
	 * @package Social Auto Poster
	 * @since 2.0.0
	 */
	public function get_plan($plan_id, $object) {
		
		$result = array();

		if ( !empty($plan_id) ) {
			try {
				$result = $this->db->get_row( "SELECT * FROM " . $this->table_name . " where `id` = '{$plan_id}'", $object );
			} catch (Exception $e) {
				return $e->getMessage();
			}
			// Return result
			return $result;
		}
	}

	/**
	 * Hendle to get upgrade plan
	 * 
	 * @package Social Auto Poster
	 * @since 2.0.0
	 */
	public function get_upgrade_plans($user_id ) {
		
		$result = array();

		if ( !empty($user_id) ) {
			try {

				if( !class_exists('SAP_Membership')){
					require_once CLASS_PATH.'/Membership.php';
				}

				$this->membership = new SAP_Membership();

				$current_plan = $this->membership->get_membership_by_user_id($user_id , true);
				
				$result = $this->db->get_results('select p.* FROM '.$this->table_name.' as p WHERE p.price > '.$current_plan->price .' ORDER BY convert(`price`, decimal) ASC');

			} catch (Exception $e) {
				return $e->getMessage();
			}
			// Return result
			return $result;
		}
	}

	
	/**
	 * Delete Plan
	 * 
	 * Handels Delete Plan
	 * 
	 * @package Social Auto Poster
	 * @since 2.0.0
	 */
	public function delete_plan() {
		

		if ( !empty($_REQUEST['plan_id']) ) {

			$result = array();
			
			$plan_id = $_REQUEST['plan_id'];

			$exists = $this->db->exists( $this->membership_table, 'plan_id', array(
				'plan_id' => $plan_id
			) );
		

			$conditions = array('id' => $plan_id);
			$membership_conditions = array('plan_id' => $plan_id);
			$is_deleted = $this->db->delete( $this->table_name, $conditions );
			
			$this->db->delete( $this->membership_table, $membership_conditions );

			if ( $is_deleted ) {
				$result = array('status' => '1');
			}
			else {
				$result = array('status' => '0');
			}
			
			echo json_encode($result);
			die;
		}
	}

	

	/**
	 * Delete multiple plan
	 * 
	 * Handels Delete multiple plan
	 * 
	 * @package Social Auto Poster
	 * @since 1.0.0
	 */
	public function delete_multiple_plan() {

		if ( !empty($_REQUEST['id']) ) {
			
			$result = array();

			$plan_id = $_REQUEST['id']; $is_exists = false;

			foreach ( $plan_id as $key => $value ) {

				$exists = $this->db->exists( $this->membership_table, 'plan_id', array(
					'plan_id' => $value
				) );

				
				$conditions = array( 'id' => $value );					
				$is_deleted = $this->db->delete( $this->table_name, $conditions );

				$membership_conditions = array('id' => $value);
				$this->db->delete( $this->membership_table, $membership_conditions );
				
			}

			$result = array('status' => '1');
			$this->flash->setFlash( $this->sap_common->lang('selected_membership_level_delete'), 'success' );			

			echo json_encode($result);
			die;
		}
	}


	/**
	 * Hendle to get plan expiration date
	 * 
	 * @package Social Auto Poster
	 * @since 2.0.0
	 */
	public function get_plan_expiry_date(){

		$plan_id = isset( $_POST['plan_id']) ? $_POST['plan_id'] : 0;

		$expiration_date = '';

		if( !empty( $plan_id ) ){

			$current_date 	 = date('Y-m-d H:i:s');				

			$plan_data = $this->get_plan( $plan_id,true );
			
			if( !empty( $plan_data->subscription_expiration_days )){
				$expiration_date = date('Y-m-d', strtotime($current_date. ' + '.$plan_data->subscription_expiration_days.' day'));
			}
		}
		echo $expiration_date;
		die();
	}
}