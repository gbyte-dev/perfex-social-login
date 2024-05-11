<?php

/* Check the absolute path to the Social Auto Poster directory. */
if ( !defined( 'SAP_APP_PATH' ) ) {
    // If SAP_APP_PATH constant is not defined, perform some action, show an error, or exit the script
    // Or exit the script if required
    exit();
}

/**
 * To Change data as per admin and user login 
 */

/**
 * Get user details
 *
 * @package Social Auto Poster
 * @since 1.0.4
 */
function sap_get_current_user() {
	return isset( $_SESSION['user_details'] ) ? $_SESSION['user_details'] : array();
}

/**
 * Get current user id
 *
 * @package Social Auto Poster
 * @since 1.0.4
 */
function sap_get_current_user_id() {
	$user = sap_get_current_user();
	return isset( $user['user_id'] ) ? $user['user_id'] : false;
}

/**
 * If Admin session not set then redirect to login page
 *
 * @package Social Auto Poster
 * @since 1.0.4
 */
function sap_get_current_user_role() {

	$common = new Common();
	$user = sap_get_current_user();

	if( empty($user['role']) ) {
        unset( $_SESSION['user_details'] );
        $common->redirect('login');
    }

	return $user['role'];
}

/**
 * Get available networks
 *
 * @package Social Auto Poster
 * @since 1.0.4
 */
function sap_get_users_networks() {


	$user = sap_get_current_user();
		
	return !empty( $user['networks'] ) ? $user['networks'] : array(); 
}

/**
 * Get network by id
 *
 * @package Social Auto Poster
 * @since 1.0.4
 */
function sap_get_users_networks_by_id( $user_id ) {

	global $sap_db_connect;

	$_db = $sap_db_connect;

	if( empty($_db) ) {
		$_db = new Sap_Database();
	}

	$query = "SELECT plan.networks FROM sap_membership AS m INNER JOIN sap_plans AS plan ON plan.id = m.plan_id WHERE m.user_id = '{$user_id}'";

	$result = $_db->get_row( $query, true );
	
	return $result;
	
}



/**
 * Get user details
 *
 * @package Social Auto Poster
 * @since 1.0.4
 */
function sap_get_users_by_id( $user_id ) {

	global $sap_db_connect;

	$_db = $sap_db_connect;

	if( empty($_db) ) {
		$_db = new Sap_Database();
	}

	$query = "SELECT * FROM sap_users WHERE id = '{$user_id}'";
	$result = $_db->get_row( $query, true );	
	return $result;
	
}



/**
 * Set user role
 *
 * @package Social Auto Poster
 * @since 1.0.4
 */
function sap_current_user_can( $capability ) {
	$userRole = sap_get_current_user_role();

	if( 'user' == $userRole ) {
		return true;
	}

	return false;
}


/**
 * Function to check user payment status
 *
 * @package Social Auto Poster
 * @since 2.0.0
 */
function sap_check_user_payment_status(){

	global $sap_db_connect,$match;

	$_db = $sap_db_connect;

	if( empty($_db) ) {
		$_db = new Sap_Database();
	}
	
	$payment_status = false;
	$user = sap_get_current_user();

	if( !empty( $user ) && $user['role'] != 'superadmin'){
		
		$user_id = $user['user_id'];
		
		$planquery 	 = "SELECT * FROM sap_membership WHERE user_id = '{$user_id}'";
		$result = $_db->get_row( $planquery, true );

		$url 			= $_SERVER['REQUEST_URI'];
		$is_payment 	= strpos($url,'payment');
		$str_time 		= !empty( $result->expiration_date ) ? strtotime($result->expiration_date) : '';
		
		if( !empty( $result ) && ( ( $result->membership_status != '1' &&  $result->membership_status != '3' ) || ( !empty( $str_time ) && date('Y-m-d') > date('Y-m-d', $str_time ) ) ) &&  ( $is_payment <= 0 ) ){
			header("Location:" . SAP_SITE_URL . "/payment/");
			die();
		}
	}

	return $payment_status;	
}

/**
 * Get date after given number of days
 *
 * @package Social Auto Poster
 * @since 2.0.0
 */
function get_date_after_x_date( $date = '', $days = 0 ){

	if( empty( $date ) ){
		$date = date('Y-m-d');
	}

	if( empty($days ) ){
		return date('Y-m-d H:i:s');
	}	

	$new_date =  date('Y-m-d H:i:s', strtotime($date . ' +'.$days.' day'));

	return $new_date;

}


/**
 * Formate date
 *
 * @package Social Auto Poster
 * @since 2.0.0
 */
function sap_format_date( $date = '', $time = false ){

	if( empty( $date ) ){
		$date = date('Y-m-d');
	}

	if( $time )	{
		$formated_date = date( "M j, Y g:i a", strtotime( $date ));		
	}
	else{
		$formated_date = date( "M j, Y", strtotime( $date ));		
	}	

	return $formated_date;

}


/**
 * Get social networks labels
 *
 * @package Social Auto Poster
 * @since 2.0.0
 */
function sap_get_networks_label( $network ){


	switch ( $network ) {
	  case "linkedin":
	  	$lable = 'LinkedIn';	    
	    break;
	  case "tumblr":
	    $lable = 'Tumblr';
	    break;
	  case "pinterest":
	    $lable = 'Pinterest';
	    break;
	  case "gmb":
	    $lable = 'Google My Business';
	    break;
	  case "facebook":
	    $lable = 'Facebook';
	    break;
	  case "twitter":
	    $lable = 'Twitter';
	    break;
	  case "instagram":
		$lable = 'Instagram';
		break;
	  case "youtube":
		$lable = 'Youtube';
		break;
	 case "reddit":
		$lable = 'Reddit';
		break;
	case "blogger":
		$lable = 'Blogger';
		break;	
	  default:	    
	}

	return $lable;

}



/**
 * Function to generate random string
 *
 * @package Social Auto Poster
 * @since 2.0.0
 */
function generate_random_string($length = 10) {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $charactersLength = strlen($characters);
    $randomString = '';
    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[rand(0, $charactersLength - 1)];
    }
    return $randomString;
}


/**
 * Function to get membership status
 *
 * @package Social Auto Poster
 * @since 2.0.0
 */
function get_membership_status_label( $status ){
	global $sap_common;

	$membership_status = '';

	if( $status == '1' ){
    	$membership_status = '<div class="active">'. $sap_common->lang('active') .'</div>';
  	}
  	elseif( $status == '0' ){
  		$membership_status = '<div class="pending">'. $sap_common->lang('pending') .'</div>';
  	} 
  	elseif( $status == '2' ){
  		$membership_status = '<div class="pending">'. $sap_common->lang('pending') .'</div>'; 
  	} 
  	elseif( $status  == '3'){
  	 	$membership_status = '<div class="cancelled">'. $sap_common->lang('cancelled') .'</div>'; 
  	}
  	return $membership_status;
}


/**
 * Function to get recuring status
 *
 * @package Social Auto Poster
 * @since 2.0.0
 */
function get_recuring_status_label( $status ){
	global $sap_common;
	$recuring_status = '';

	if( $status == '1' ){
    	$recuring_status = $sap_common->lang('yes');
  	}
  	elseif( $status == '0' ){
  		$recuring_status = $sap_common->lang('no');
  	}   	
  	return $recuring_status;
}


/**
 * Function to get payment status
 *
 * @package Social Auto Poster
 * @since 2.0.0
 */
function get_payment_status_label( $status ){
	global $sap_common;

	$recuring_status = '';

	if( $status == '1' ){
    	$recuring_status = '<div class="completed">'. $sap_common->lang('install_menu_completed') .'</div>';
  	}
  	elseif( $status == '0' ){
  		$recuring_status = '<div class="pending">'. $sap_common->lang('pending') .'</div>';
  	} 
  	elseif( $status == '2' ){
  		$recuring_status = '<div class="failed">'. $sap_common->lang('failed') .'</div>';
  	}
  	elseif( $status == '3' ){
  		$recuring_status = '<div class="refunded">'. $sap_common->lang('refunded') .'</div>';
  	}
  	return $recuring_status;
}


/**
 * Function Get membership expiration date
 *
 * @package Social Auto Poster
 * @since 2.0.0
 */
function sap_get_membership_expiration_date( $exp_date ){
	
	$date = 'Never';
	if( !empty($exp_date) ){
        $date =  sap_format_date( $exp_date );
    }
    return $date;

}