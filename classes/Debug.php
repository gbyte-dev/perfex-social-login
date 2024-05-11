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
class SAP_Debug {

	//Set Database variable
	private $db;
	//Set table name
	private $table_name;
	public $flash;
	public $common;
	private $settings;

	public function __construct() {
		
		global $sap_db_connect;
		
		if (!class_exists('SAP_Posts')) {
			include_once( CLASS_PATH . 'Posts.php');
		}

		$this->db = $sap_db_connect;
		$this->common = new Common();
		$this->settings = new SAP_Settings();
		 
		//sap_check_user_payment_status();
		
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
		$template_path = $this->common->get_template_path('Debug' . DS . 'index.php' );
			include_once( $template_path );
	}

	/**
	 * Listing page of Report
	 * Handels to Report html view
	 * 
	 * @package Social Auto Poster
	 * @since 1.0.0
	 */
	public function debug() {

		//Includes Html files for Posts list
		if ( sap_current_user_can('debug') ) {
			
			$template_path = $this->common->get_template_path('Debug' . DS . 'index.php' );
			include_once( $template_path );			
		}
		else {
			$this->common->redirect('login');
		}
	}

	/**
	 * Clear Debug Log
	 * 
	 * @package Social Auto Poster
	 * @since 1.0.0
	 */
	public function clear() {

		$user_id = sap_get_current_user_id();

		$log_filename = SAP_LOG_DIR;
		if ( file_exists($log_filename) ) {
			$log_file_data = $log_filename . 'mingle_log_' . md5('123456789ABCDEFGHI') . '--' . $user_id . '.txt';
			$log_msg = "";
			file_put_contents($log_file_data,"");
		}
		$this->common->redirect('debug');
		exit();
	}

	/**
	 * Clear Debug Log
	 * 
	 * @package Social Auto Poster
	 * @since 1.0.0
	 */
	public function schedule_cleaner() {
		$log_filename = SAP_LOG_DIR;
		
		if ( file_exists($log_filename) ) {

			$files = @scandir( $log_filename );

			$result = array();
			if ( ! empty( $files ) ) {
				foreach ( $files as $key => $value ) {
					if ( ! in_array( $value, array( '.', '..' ), true ) ) {
						$log_msg = "";
						file_put_contents( $value, "" );
					}
				}
			}
		   
			/*
			$log_file_data = $log_filename . 'mingle_log_' . md5('123456789ABCDEFGHI') . '.txt';
			$log_msg = "";
			file_put_contents( $log_file_data, "" ); */
			
			$this->settings->update_options( 'schedule_debug_clear', date("Y-m-d") );
		}
	}

}
