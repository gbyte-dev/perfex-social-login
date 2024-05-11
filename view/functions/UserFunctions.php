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
 */
function sap_get_current_user() {
	return isset( $_SESSION['user_details'] ) ? $_SESSION['user_details'] : array();
}

/**
 * Get current user id
 */
function sap_get_current_user_id() {
	$user = sap_get_current_user();
	return isset( $user['user_id'] ) ? $user['user_id'] : false;
}

/**
 * If Admin session not set then redirect to login page
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
 */
function sap_get_users_networks() {
	$user = sap_get_current_user();
	return isset( $user['networks'] ) ? $user['networks'] : array(); 
}

/**
 * Get users network from database, by id
 */
function sap_get_users_networks_by_id( $user_id ) {

	global $sap_db_connect;

	$_db = $sap_db_connect;
	if( empty($_db) ) {
		$_db = new Sap_Database();
	}

	$networks = array();

	try {
		$result = $_db->get_row("SELECT plan.networks FROM sap_users AS user INNER JOIN sap_plans AS plan ON plan.id = user.plan where user.id = '{$user_id}'", true);

		$networks = !empty( $result->networks ) ? unserialize( $result->networks ) : array();

	} catch (Exception $e) {
		return $e->getMessage();
	}

	return $networks;
}

/**
 * Set user role
 */
function sap_current_user_can( $capability ) {
	
	$userRole = sap_get_current_user_role();

	if( 'user' == $userRole ) {
		return true;
	}

	return false;
}
