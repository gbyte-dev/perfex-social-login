<?php

/* Check the absolute path to the Social Auto Poster directory. */
if ( !defined( 'SAP_APP_PATH' ) ) {
    // If SAP_APP_PATH constant is not defined, perform some action, show an error, or exit the script
    // Or exit the script if required
    exit();
}

/**
 * Common Class function
 * 
 * A class contains common function to be used to throughout the System
 *
 * @package Social Auto Poster
 * @since 1.0.0
 */
class Common {
	public $sap_common;
	
	public function __construct() {
		global $sap_common;
		$this->sap_common = $sap_common;
	}
	/**
	 * Get user IP
	 * 
	 * @package Social Auto Poster
	 * @since 1.0.0
	 */
	public function get_user_ip() {
		// check for shared internet/ISP IP
		if (!empty($_SERVER['HTTP_CLIENT_IP']) && $this->validate_ip($_SERVER['HTTP_CLIENT_IP'])) {
			return $_SERVER['HTTP_CLIENT_IP'];
		}

		// check for IPs passing through proxies
		if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
			// check if multiple ips exist in var
			if (strpos($_SERVER['HTTP_X_FORWARDED_FOR'], ',') !== false) {
				$iplist = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
				foreach ($iplist as $ip) {
					if ($this->validate_ip($ip))
						return $ip;
				}
			} else {
				if ($this->validate_ip($_SERVER['HTTP_X_FORWARDED_FOR']))
					return $_SERVER['HTTP_X_FORWARDED_FOR'];
			}
		}
		if (!empty($_SERVER['HTTP_X_FORWARDED']) && $this->validate_ip($_SERVER['HTTP_X_FORWARDED']))
			return $_SERVER['HTTP_X_FORWARDED'];
		if (!empty($_SERVER['HTTP_X_CLUSTER_CLIENT_IP']) && $this->validate_ip($_SERVER['HTTP_X_CLUSTER_CLIENT_IP']))
			return $_SERVER['HTTP_X_CLUSTER_CLIENT_IP'];
		if (!empty($_SERVER['HTTP_FORWARDED_FOR']) && $this->validate_ip($_SERVER['HTTP_FORWARDED_FOR']))
			return $_SERVER['HTTP_FORWARDED_FOR'];
		if (!empty($_SERVER['HTTP_FORWARDED']) && $this->validate_ip($_SERVER['HTTP_FORWARDED']))
			return $_SERVER['HTTP_FORWARDED'];

		// return unreliable ip since all else failed
		return $_SERVER['REMOTE_ADDR'];
	}

	/**
	 * Ensure check validate IP
	 * 
	 * @package Social Auto Poster
	 * @since 1.0.0
	 */
	function validate_ip($ip) {

		if (strtolower($ip) === 'unknown')
			return false;

		// generate ipv4 network address
		$ip = ip2long($ip);

		// if the ip is set and not equivalent to 255.255.255.255
		if ($ip !== false && $ip !== -1) {
			// make sure to get unsigned long representation of ip
			// due to discrepancies between 32 and 64 bit OSes and
			// signed numbers (ints default to signed in PHP)
			$ip = sprintf('%u', $ip);
			// do private network range checking
			if ($ip >= 0 && $ip <= 50331647) {
				return false;
			}
			if ($ip >= 167772160 && $ip <= 184549375) {
				return false;
			}
			if ($ip >= 2130706432 && $ip <= 2147483647) {
				return false;
			}
			if ($ip >= 2851995648 && $ip <= 2852061183) {
				return false;
			}
			if ($ip >= 2886729728 && $ip <= 2887778303) {
				return false;
			}
			if ($ip >= 3221225984 && $ip <= 3221226239) {
				return false;
			}
			if ($ip >= 3232235520 && $ip <= 3232301055) {
				return false;
			}
			if ($ip >= 4294967040) {
				return false;
			}
		}
		return true;
	}

	
	/**
	 * Insert all log data from here
	 * 
	 * @package Social Auto Poster
	 * @since 1.0.0
	 */
	public function sap_script_logs( $log_msg, $user_id = '' ) {
		
		if( empty( $user_id ) ) {
			$user_id = sap_get_current_user_id();
		}
	   
		$log_filename = SAP_LOG_DIR;
		if ( !file_exists($log_filename) ) {
			// create directory/folder uploads.
			mkdir($log_filename, 0777, true);
		}

		$log_file_data = $log_filename . '/mingle_log_' . md5('123456789ABCDEFGHI') . '--' . $user_id . '.txt';
		$log_msg = "\n" . date('m-d-Y') . ' @ ' . date("h:m:s") . ' - ' . $log_msg;
		
		file_put_contents($log_file_data, $log_msg . "\n", FILE_APPEND);
	}

	/**
	 * Redirect too page
	 * 
	 * @package Social Auto Poster
	 * @since 1.0.0
	 */
	public function redirect($url, $status = 302) {
		global $router;

		$redirectUrl = $this->_getBaseUrl() . $router->generate($url);
		//Redirect to given url
		return header('Location:' . $redirectUrl);
	}

	
	/**
	 * get base URL
	 * 
	 * @package Social Auto Poster
	 * @since 1.0.0
	 */
	public function _getBaseUrl($dirname = false) {
		// output: /myproject/index.php
		$currentPath = $_SERVER['PHP_SELF'];

		// output: Array ( [dirname] => /myproject [basename] => index.php [extension] => php [filename] => index ) 
		$pathInfo = pathinfo($currentPath);

		// output: localhost
		$hostName = $_SERVER['HTTP_HOST'];

		// output: http://
		$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
		// return: http://localhost/myproject/
		if ($dirname) {
			return $protocol . $hostName . $pathInfo['dirname'];
		}
		// return http://localhost
		return $protocol . $hostName;
	}

	/**
	 * Check string have serialize data or not
	 * 
	 * @package Social Auto Poster
	 * @since 1.0.0
	 */
	public function is_serialized($data) {
		return (@unserialize($data) !== false);
	}

	/**
	 * Get URL shorteners
	 * 
	 * @package Social Auto Poster
	 * @since 1.0.0
	 */
	public function sap_get_all_url_shortners() {

		$shortner_options = array(
			'' => $this->sap_common->lang('select_shortener_type'),
			'tinyurl' => $this->sap_common->lang('select_shortener_tinyURL'),
			'bitly' => $this->sap_common->lang('select_shortener_bitly'),
			'shorte.st' => $this->sap_common->lang('select_shortener_shortest')
		);

		return $shortner_options;
	}

	/**
	 * Add new options settings
	 * 
	 * Handels to Adding new setting options
	 * 
	 * @package Social Auto Poster
	 * @since 1.0.0
	 */
	public function sap_script_short_post_link($link = '', $customlink = 'false', $socialtype = 'fb', $socialslug = 'facebook', $user_id='') {

		if (!defined('SAP_UTM_SOURCE')) {
			define('SAP_UTM_SOURCE', 'SocialAutoPoster'); // Google tracking source name
		}

		if (!defined('SAP_UTM_MEDIUM')) {
			define('SAP_UTM_MEDIUM', 'Social'); // Google tracking source name
		}

		$custom_formatted_link = '';
		$settings = new SAP_Settings();
		$social_options = $settings->get_user_setting('sap_' . $socialslug . '_options',$user_id);

				
		$shortner_type = $social_options[$socialtype . '_type_shortner_opt'];

		$sap_general_options = $settings->get_options('sap_general_options');
		$google_camp_tracking = ( isset($sap_general_options['google_campaign_tracking']) && !empty($sap_general_options['google_campaign_tracking']) ) ? $sap_general_options['google_campaign_tracking'] : '';

		if ($google_camp_tracking == '1') {
			$campaign = $socialslug;

			if ($campaign == 'google_business') {
				$campaign = 'google my business';
			}

			if (!empty($link)) {
				if (strpos($link, '?') !== false) {
					$link .= '&';
				} else {
					$link .= '?';
				}

				$link .= 'utm_source=' . SAP_UTM_SOURCE;
				$link .= '&utm_medium=' . SAP_UTM_MEDIUM;
				$link .= '&utm_campaign=' . $campaign;
			}
		}

		if (!empty($shortner_type) && !empty($link)) {
			switch ($shortner_type) {
				case 'tinyurl':
					require_once ( CLASS_PATH . 'shorteners/tinyurl.php');
					$tinyurl = new SAP_Tiny_Url();
					$custom_formatted_link = $tinyurl->shorten($link);

					break;

				case 'bitly':
					require_once (CLASS_PATH . 'shorteners/bitly.php');
					$bitly_access_token = $social_options[$socialtype . '_bitly_access_token'];
					$bitly_url = new SAP_Bitly_Url($bitly_access_token);
				
					$custom_formatted_link = $bitly_url->shorten($link);
							
						
					break;

				case 'shorte.st':
					require_once (CLASS_PATH . 'shorteners/shortest.php');
					$shortest_api_token = $social_options[$socialtype . '_shortest_api_token'];
					$shorte_st = new SAP_Shorte_Url();
					$custom_formatted_link = $shorte_st->shorten($shortest_api_token, $link);
					break;
			}
		} else {

			$custom_formatted_link = $link;
		}

		return $custom_formatted_link;
	}

	/**
	 * Get One Diemention Array
	 * 
	 * Handles to get one diemention array by two diemention array
	 * 
	 * @package Social Auto Poster
	 * @since 1.0.0
	 */
	public function get_one_dim_array($multi_dim_array) {

		$one_dim_array = array();
		if (!empty($multi_dim_array)) { // Check dim array are not empty
			foreach ($multi_dim_array as $multi_dim_keys) {

				if (!empty($multi_dim_keys)) { // Check dim keys are not empty
					foreach ($multi_dim_keys as $multi_dim_values) {

						$one_dim_array[] = $multi_dim_values;
					}
				}
			}
		}
		return $one_dim_array;
	}

	
	/**
	 * Check SSL is available or not
	 * 
	 * @package Social Auto Poster
	 * @since 1.0.0
	 */
	function is_ssl() {
		if (isset($_SERVER['HTTPS'])) {
			if ('on' == strtolower($_SERVER['HTTPS'])) {
				return true;
			}

			if ('1' == $_SERVER['HTTPS']) {
				return true;
			}
		} elseif (isset($_SERVER['SERVER_PORT']) && ( '443' == $_SERVER['SERVER_PORT'] )) {
			return true;
		}
		return false;
	}

	/**
	 * Short the Content As Per Character Limit
	 * 
	 * Handles to return short content as per character 
	 * limit
	 * 
	 * @package Social Auto Poster
	 * @since 1.0.0
	 * */
	public function sap_content_excerpt($content, $charlength = 140) {

		$excerpt = '';
		$charlength++;

		//check content length is greater then character length
		if (strlen($content) > $charlength) {

			$subex = substr($content, 0, $charlength - 5);
			$exwords = explode(' ', $subex);
			$excut = - ( strlen($exwords[count($exwords) - 1]) );

			if ($excut < 0) {
				$excerpt = substr($subex, 0, $excut);
				$excerpt .= '...';
			} else {
				$excerpt = $subex;
				$excerpt .= '...';
			}
		} else {
			$excerpt = $content;
		}

		//return short content
		return $excerpt;
	}

	/**
	 * Handle to return translated text
	 * 
	 * 
	 * @package Social Auto Poster
	 * @since 2.0.0
	 * */
	public function lang( $key ) {

		$lang = !defined('DEFAULT_LANG') || empty( DEFAULT_LANG ) ? 'en': DEFAULT_LANG;

		if( file_exists( SAP_APP_PATH.'custom/languages/'.$lang.'.php') ) {
			include SAP_APP_PATH.'custom/languages/'.$lang.'.php';
		} elseif( file_exists( SAP_APP_PATH.'contrib/languages/'.$lang.'.php') ){

			include SAP_APP_PATH.'contrib/languages/'.$lang.'.php';
		}

		$value = ($key == '' OR ! isset($language[$key])) ? FALSE : $language[$key];

		return $value;
	}


	/**
	 * Handle to print translated text
	 * 
	 * 
	 * @package Social Auto Poster
	 * @since 2.0.0
	 * */
	public function e_lang( $key ) {

		$lang = !defined('DEFAULT_LANG') || empty( DEFAULT_LANG ) ? 'en': DEFAULT_LANG;
		
		if( file_exists( SAP_APP_PATH.'custom/languages/'.$lang.'.php') ) {
			include SAP_APP_PATH.'custom/languages/'.$lang.'.php';
		} elseif( file_exists( SAP_APP_PATH.'contrib/languages/'.$lang.'.php') ){
			include SAP_APP_PATH.'contrib/languages/'.$lang.'.php';
		}

		$value = ($key == '' OR ! isset($language[$key])) ? FALSE : $language[$key];

		echo $value;
	}

	/**
	 * Handle to override template
	 * 
	 * 
	 * @package Social Auto Poster
	 * @since 2.0.0
	 * */
	public function get_template_path( $path = '' ){

		if( !empty( $path ) ) {

			if( file_exists(SAP_APP_PATH . 'custom/view/'.$path) ){
				return SAP_APP_PATH . 'custom/view/'.$path;
			}
			elseif( file_exists(SAP_APP_PATH . 'view/'.$path) ){
				return SAP_APP_PATH . 'view/'.$path;
			}
		}
	}

	/**
	 * WooCommerce - PDF Vouchers license check
	 *
	 * @package WooCommerce - PDF Vouchers
	 * @since 3.7.0
	 */
	public function sap_is_license_activated() {
		$settings = new SAP_Settings();
		$activated = $settings->get_options('sap_license_activated');
	    $status = false;

	    if( $activated ) {
	        $activated = base64_decode( $activated );
	        $data = explode( '%', $activated );
	        $license_data = $settings->get_options( 'sap_license_data' );
	        $license_code = !empty( $license_data['license_key'] ) ? $license_data['license_key'] : '';
	        $email = !empty( $license_data['license_email'] ) ? $license_data['license_email'] : '';
	        if( ! empty( $data ) && $data[0] === $license_code && $data[1] === $email ) {
	            $status = true;
	        }
	    }
	    return $status;
	}
}
