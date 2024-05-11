<?php

/* Check the absolute path to the Social Auto Poster directory. */
if ( !defined( 'SAP_APP_PATH' ) ) {
    // If SAP_APP_PATH constant is not defined, perform some action, show an error, or exit the script
    // Or exit the script if required
    exit();
}

/**
 * Settings Class
 *
 * To handles all SAP Settings
 * 
 * @package Social Auto Poster
 * @since 1.0.0
 */
include (SAP_APP_PATH . 'Lib' . DS . 'Media' . DS . 'FileUploader.php');

class SAP_Settings {

	//Set Database variable
	private $db;
	//Set table name
	private $table_name;
	//Set table name
	private $table_user_settings;
	//Set Msg
	public $flash;
	//Common
	public $common;
	public $sap_common;

	/**
	 * constructor function
	 * 
	 * @package Social Auto Poster
	 * @since 1.0.0
	 */
	public function __construct() {
		global $sap_common,$sap_db_connect;
		//Set Database
		$this->db = $sap_db_connect;
		$this->table_name = 'sap_options';
		$this->table_user_settings = 'sap_user_settings';
		$this->flash = new Flash();
		$this->common = new Common();
		$this->sap_common = $sap_common;
		$this->get_version();
		
	}
	
	
	/**
	 * get SAP latest version
	 * 
	 * @package Social Auto Poster
	 * @since 1.0.0
	 */
	public function get_version() {
		$get_version = $this->get_options('sap_version');
		if (isset($get_version) && !empty($get_version)) {
			if (!defined('SAP_VERSION'))
				define('SAP_VERSION', $get_version);
		}else{
			if (!defined('SAP_VERSION'))
				define('SAP_VERSION', '1.0.4');
		}
	}

	/**
	 * View settings
	 * 
	 * Handels to view setting html
	 * 
	 * @package Social Auto Poster
	 * @since 1.0.0
	 */
	public function view() {
		

		//Includes Html files of settings
		if ( sap_current_user_can('settings') ) {


			$template_path = $this->common->get_template_path('Settings' . DS . 'settings.php' );
			include_once( $template_path );			
		}
		else {
			$this->common->redirect('login');
		}
	}

	/**
	 * Render  smtp settings form
	 * 
	 * @package Social Auto Poster
	 * @since 1.0.0
	 */
	public function smtp_settings() {
		if ( !sap_current_user_can('smtp-settings') ) {

			$template_path = $this->common->get_template_path('Settings' . DS . 'Smtp-settings.php' );
			include_once( $template_path );
		}
		else {
			$this->common->redirect('login');
		}
	}

	/**
	 * Add new options settings
	 * 
	 * Handels to Adding new setting options
	 * 
	 * @package Social Auto Poster
	 * @since 1.0.0
	 */
	public function add_options($option_name, $option_value) {

		//Set in arrays
		$option_data = array('option_name' => $option_name, 'option_value' => $option_value);

		//Run database and Insert options in table
		$this->db->insert($this->table_name, $option_data);

		//Return inserted ID
		return $this->db->lastid();
	}

	/**
	 * Update option settings
	 * 
	 * Handels to Update setting options
	 * 
	 * @package Social Auto Poster
	 * @since 1.0.0
	 */
	public function update_options($option_name, $option_value) {

		//Check option exist in Database
		$check_option_exist = $this->db->num_rows("SELECT option_name FROM " . $this->table_name . " WHERE option_name = '{$option_name}'");

		//Exist database set update query another insert option
		if ($check_option_exist) {

			//Prepare data for update
			$option_data = array('option_value' => is_array($option_value) ? addslashes(serialize($option_value)) : $option_value);
			$where_data = array('option_name' => $option_name);

			//Run update query in db and return result
			return $this->db->update($this->table_name, $option_data, $where_data);
		} else {

			//Prepare data for insert
			$option_data = array('option_name' => $option_name, 'option_value' => is_array($option_value) ? addslashes(serialize($option_value)) : $option_value);

			//Run query and insert option in db
			$this->db->insert($this->table_name, $option_data);

			//Return inserted ID
			return $this->db->lastid();
		}
	}

	/**
	 * Delete option settings
	 * 
	 * Handels delete setting Option
	 * 
	 * @package Social Auto Poster
	 * @since 1.0.0
	 */
	public function delete_options($option_name) {

		//Set in arrays
		$option_data = array('option_name' => $option_name);

		//Run database and Insert options in table
		$result = $this->db->delete($this->table_name, $option_data);

		//Return result
		return $result;
	}

	/**
	 * Get option settings
	 * 
	 * Handels list setting Option get
	 * 
	 * @package Social Auto Poster
	 * @since 1.0.0
	 */
	public function get_options($option_name) {

		//Get result of option
		$result = $this->db->get_row("SELECT option_value FROM " . $this->table_name . " where `option_name` = '{$option_name}' ORDER BY option_name DESC");

		//Built formate for output
		if (!empty($result[0]) && $this->common->is_serialized($result[0])) {
			$result = unserialize(stripslashes($result[0]));
		} elseif (!empty($result[0]) && is_string($result[0])) {
			$result = $result[0];
		} else {
			$result = '';
		}

		//Return result
		return $result;
	}

	/**
	 * Update user settings
	 * 
	 * Handels to Update user setting
	 * 
	 * @package Social Auto Poster
	 * @since 1.0.0
	 */
	public function update_user_setting($setting_name, $setting_value, $user_id='' ) {

		// current user id
		if ( empty($user_id) ) {
			$user_id = sap_get_current_user_id();
		}

		//Check setting exist in Database
		$check_setting_exist = $this->db->num_rows("SELECT setting_name FROM " . $this->table_user_settings . " WHERE setting_name = '{$setting_name}' AND user_id = {$user_id};");

		//Exist database set update query another insert setting
		if ( $check_setting_exist ) {

			//Prepare data for update
			$setting_data = array( 'setting_value' => is_array($setting_value) ? addslashes(serialize($setting_value)) : $setting_value );
			$where_data = array( 'setting_name' => $setting_name, 'user_id' => $user_id );

			//Run update query in db and return result
			return $this->db->update($this->table_user_settings, $setting_data, $where_data);

		} else {

			//Prepare data for insert
			$setting_data = array( 'setting_name' => $setting_name, 'setting_value' => is_array($setting_value) ? addslashes(serialize($setting_value)) : $setting_value, 'user_id' => $user_id );

			//Run query and insert setting in db
			$this->db->insert($this->table_user_settings, $setting_data);

			//Return inserted ID
			return $this->db->lastid();
		}
	}

	/**
	 * Get option settings
	 * 
	 * Handels list setting Option get
	 * 
	 * @package Social Auto Poster
	 * @since 1.0.0
	 */
	public function get_user_setting($setting_name, $user_id = '') {

		// current user id
		if( empty($user_id)){
			$user_id = sap_get_current_user_id();	
		}
		
		  
		// if ( empty($user_id) ) {

		// 	$post_id='';
		// 	$shedule_object = new SAP_Shedule_Posts();
		// 	$quick_posts = new SAP_Quick_Posts();
		// 	$sheduled_post_ids    = $shedule_object->get_sheduled_post_ids();
			
		// 	if (!empty($sheduled_post_ids['quick_posts'])) {

		// 		foreach ($sheduled_post_ids['quick_posts'] as $key => $value) {
		// 				$schedule_time = $quick_posts->get_post_meta($value['post_id'], 'sap_schedule_time');
		// 				if( $schedule_time < time() ) {
		// 					$post_id=$value['post_id'];
   
		// 				}
		// 		}
		// 	}
		// 	  $quick_post = $quick_posts->get_post($post_id, true);
          
        //   $user_id = isset( $quick_post->user_id ) ? $quick_post->user_id : '';
            
      	// }
    
    
  
		//Get result of option
		$result = $this->db->get_row("SELECT setting_value FROM " . $this->table_user_settings . " where `setting_name` = '{$setting_name}' AND `user_id` = {$user_id} ORDER BY setting_name DESC");

		//Built formate for output
		if ( !empty($result[0]) && $this->common->is_serialized($result[0]) ) {
			$result = unserialize( ($result[0]) );
		} elseif ( !empty($result[0]) && is_string($result[0]) ) {
			$result = $result[0];
		} else {
			$result = '';
		}

		//Return result
		return $result;
	}

	/**
	 * Save all settings
	 * Handle Request and save data
	 * @package Social Auto Poster
	 * @since 1.0.0
	 */
	public function save_all_settings() {

		if (isset($_POST['sap_instagram_submit']) && !empty($_POST['sap_instagram_options'])) {
			
			if (!empty($_FILES['insta_image']['name'])) {

				$fileUpload = new FileUploader(array());
				$uploadPath = $fileUpload->uploadFile('insta_image');
				$_POST['sap_instagram_options']['insta_image'] = $uploadPath;
			}

			$update_setting = $this->update_user_setting('sap_instagram_options', $_POST['sap_instagram_options']);

			//Check response for DB Update
			if (!empty($update_setting)) {
				$this->flash->setFlash($this->sap_common->lang('instagram_settings_update_msg'), 'success');
			} else {
				$this->flash->setFlash($this->sap_common->lang('setting_saving_data_error_msg'), 'error');
			}

			$_SESSION['sap_active_tab'] = 'instagram';
			$this->common->redirect('settings');
			exit();

		}


		//Save All the settings of  - GMB Autopost account
		if (isset($_POST['sap_google_business_submit']) && !empty($_POST['sap_google_business_options'])) {

			if (!empty($_FILES['gmb_image']['name'])) {

				$fileUpload = new FileUploader(array());
				$uploadPath = $fileUpload->uploadFile('gmb_image');
				$_POST['sap_google_business_options']['gmb_image'] = $uploadPath;
			}

			$update_setting = $this->update_user_setting('sap_google_business_options', $_POST['sap_google_business_options']);

			//Check response for DB Update
			if (!empty($update_setting)) {
				$this->flash->setFlash($this->sap_common->lang('emb_settings_update_msg'), 'success');
			} else {
				$this->flash->setFlash($this->sap_common->lang('setting_saving_data_error_msg'), 'error');
			}

			$_SESSION['sap_active_tab'] = 'gmb';
			$this->common->redirect('settings');
			exit();
		}


		//Save General Settings Section
		if (!empty($_POST['sap_general_options']) && isset($_POST['sap_general_submit'])) {

			//Update option in DB
			$update_setting = $this->update_user_setting('sap_general_options', $_POST['sap_general_options']);

			//Check response for DB Update
			if (!empty($update_setting)) {
				$this->flash->setFlash($this->sap_common->lang('general_settings_updated_msg'), 'success');
			} else {
				$this->flash->setFlash($this->sap_common->lang('setting_saving_data_error_msg'), 'error');
			}
			$_SESSION['sap_active_tab'] = 'general';
			$this->common->redirect('settings');
			exit();
		}

		//Save Facebook Settings Section
		if (!empty($_POST['sap_facebook_options']) && isset($_POST['sap_facebook_submit'])) {

			if ($_POST['sap_facebook_options']['facebook_auth_options'] == 'graph') {

				// Get facebook account details
				if (!empty($_POST['sap_facebook_options']['facebook_keys'])) {

					$sap_facebook_options = $this->get_user_setting('sap_facebook_options');

					$facebook_keys = $_POST['sap_facebook_options']['facebook_keys'];

					// Check difference of arrays
					$facebook_keys_old_data = $this->common->get_one_dim_array($sap_facebook_options['facebook_keys']);
					$facebook_keys_new_data = $this->common->get_one_dim_array($facebook_keys);

					$facebook_keys_result = array_diff($facebook_keys_new_data, $facebook_keys_old_data);
					$facebook_keys_result_vise = array_diff($facebook_keys_old_data, $facebook_keys_new_data);

					// Check any one array is different then reindex all values so if any blank row set it will not consider it.
					if (!empty($facebook_keys_result) || !empty($facebook_keys_result_vise)) {

						$new_fb_keys = array();
						$fb_count_key = 0;
						$sap_facebook_keys = array();

						foreach ($facebook_keys as $fb_key => $fb_value) {
							$fb_app_id = trim($fb_value['app_id']);
							$fb_app_secret = trim($fb_value['app_secret']);

							if (!empty($fb_app_id) && !empty($fb_app_secret)) { // Check any one key is set as not empty
								$sap_facebook_keys[$fb_count_key]['app_id'] = $fb_app_id;
								$sap_facebook_keys[$fb_count_key]['app_secret'] = $fb_app_secret;

								$fb_count_key++;
							}

							// Just taking fb app ids
							if (!empty($fb_app_id) && !empty($fb_app_secret)) {
								$new_fb_keys[] = $fb_app_id;
							}
						}

						$_POST['sap_facebook_options']['facebook_keys'] = $sap_facebook_keys;

						/*                         * *** Reset facebook session data is app key or appid is deleted **** */
						// Note : wpw_auto_poster_fb_reset_session() Function is called just to flush the session variable not options
						// If data is not empty then check which existing key
						$get_fb_sess_data = array();
						// Getting facebook keys from the stored session data
						$old_fb_keys = (!empty($get_fb_sess_data) && is_array($get_fb_sess_data) ) ? array_keys($get_fb_sess_data) : array();

						// Getting difference between stored fb keys and setting fb keys
						$diff_fb_keys = array_diff($old_fb_keys, $new_fb_keys);

						if (!empty($diff_fb_keys)) {

							foreach ($diff_fb_keys as $flush_app_key => $flush_app_data) {
								// Removing app data from the stored fb session data
								if (isset($wpw_auto_poster_fb_sess_data[$flush_app_data])) {
									unset($wpw_auto_poster_fb_sess_data[$flush_app_data]);
								}
							}

						}
						/*                         * *** Reset facebook session ends **** */
					}
					// end code for reindexing
				}
			}// end if selection method is graph
			//Upload image of facebook
			if (!empty($_FILES['fb_image']['name'])) {

				$fileUpload = new FileUploader(array());
				$uploadPath = $fileUpload->uploadFile('fb_image');
				$_POST['sap_facebook_options']['fb_image'] = $uploadPath;
			}

			//Update option in DB
			$update_setting = $this->update_user_setting('sap_facebook_options', $_POST['sap_facebook_options']);
	
			//Check response for DB Update
			if (!empty($update_setting)) {
				$this->flash->setFlash($this->sap_common->lang('fb_settings_update_msg'), 'success');
			} else {
				$this->flash->setFlash($this->sap_common->lang('setting_saving_fb_data_error_msg'), 'error');
			}
			$_SESSION['sap_active_tab'] = 'facebook';
			$this->common->redirect('settings');
			exit();
		}

		//Save Twitter Settings Section
		if (!empty($_POST['sap_twitter_options']) && isset($_POST['sap_twitter_submit'])) {

			if (!empty($_POST['sap_twitter_options']['twitter_keys'])) {

				if (!class_exists('SAP_Twitter')) {
					include ( CLASS_PATH . 'Social' . DS . 'twitterConfig.php' );
				}

				//Twitter class
				$twitter = new SAP_Twitter();

				$sap_twitter_options = $this->get_user_setting('sap_twitter_options');
				$post_twitter_keys = $_POST['sap_twitter_options']['twitter_keys'];

				// remove duplicate key
				if (!empty($post_twitter_keys)) {

					$checked_array = array();

					foreach ($post_twitter_keys as $key => $value) {

						if (!in_array($value['consumer_key'], $checked_array)) {
							$checked_array[] = $value['consumer_key'];
						} else {
							unset($_POST['sap_twitter_options']['twitter_keys'][$key]);
						}
					}
				}

				$twitter_keys = $_POST['sap_twitter_options']['twitter_keys'];

				$twitter_keys_old_data = array();
				if( !empty( $sap_twitter_options ) ) {
					//Get multidimension to one dimension
					$twitter_keys_old_data = $this->common->get_one_dim_array($sap_twitter_options['twitter_keys']);
				}
				
				$twitter_keys_new_data = $this->common->get_one_dim_array($twitter_keys);

				//Check difference of arrays
				$twitter_keys_result = array_diff($twitter_keys_new_data, $twitter_keys_old_data);
				$twitter_keys_result_vise = array_diff($twitter_keys_old_data, $twitter_keys_new_data);

				$sap_twitter_accounts_details = $this->get_user_setting('sap_twitter_accounts_details');

				// Check any one array is different 
				if (!empty($twitter_keys_result) || !empty($twitter_keys_result_vise)) {

					$tw_count_key = 0;
					$sap_twitter_accounts = $sap_twitter_accounts_details = array();

					foreach ($twitter_keys as $key => $value) {

						//Remove if space exist
						$tw_consumer_key = trim($value['consumer_key']);
						$tw_consumer_secret = trim($value['consumer_secret']);
						$tw_auth_token = trim($value['oauth_token']);
						$tw_auth_token_secret = trim($value['oauth_secret']);

						//Check all key is set as not empty
						if (!empty($tw_consumer_key) && !empty($tw_consumer_secret) && !empty($tw_auth_token) && !empty($tw_auth_token_secret)) {

							$sap_twitter_accounts[$tw_count_key]['consumer_key'] = $tw_consumer_key;
							$sap_twitter_accounts[$tw_count_key]['consumer_secret'] = $tw_consumer_secret;
							$sap_twitter_accounts[$tw_count_key]['oauth_token'] = $tw_auth_token;
							$sap_twitter_accounts[$tw_count_key]['oauth_secret'] = $tw_auth_token_secret;

							$user_profile_data = $twitter->sap_get_user_data($tw_consumer_key, $tw_consumer_secret, $tw_auth_token, $tw_auth_token_secret);

							// Check user data are not empty
							if (!empty($user_profile_data)) {

								// Check user name is not empty
								if (isset($user_profile_data->name) && !empty($user_profile_data->name)) {

									$sap_twitter_accounts_details[$tw_count_key]['name'] = $user_profile_data->name;
								}
							}
							$tw_count_key++;
						}
					}

					$_POST['sap_twitter_options']['twitter_keys'] = $sap_twitter_accounts;
				}

				// end code for reindexing
			}

			//Upload image of tweet
			if (!empty($_FILES['tweet_image']['name'])) {

				$fileUpload = new FileUploader(array());
				$uploadPath = $fileUpload->uploadFile('tweet_image');
				$_POST['sap_twitter_options']['tweet_image'] = $uploadPath;
			}

			//Update option in DB
			$update_setting = $this->update_user_setting('sap_twitter_options', $_POST['sap_twitter_options']);

			//Check name update or exist then update
			 
			//Update twitter acoount details
			$this->update_user_setting('sap_twitter_accounts_details', $sap_twitter_accounts_details);
			// }
			//Check response for DB Update
			if (!empty($update_setting)) {

				$this->flash->setFlash($this->sap_common->lang('twitter_settings_update_msg'), 'success');
			} else {

				$this->flash->setFlash($this->sap_common->lang('setting_saving_twitter_data_error_msg'), 'error');
			}

			$_SESSION['sap_active_tab'] = 'twitter';

			$this->common->redirect('settings');
			exit();
		}

		//Save Youtube Settings Section
		if (!empty($_POST['sap_youtube_options']) && isset($_POST['sap_youtube_submit'])) {
			
			// Get linkedin account details
			if (!empty($_POST['sap_youtube_options']['youtube_keys'])) {

				$sap_youtube_options = $this->get_user_setting('sap_youtube_options');

				$youtube_keys = $_POST['sap_youtube_options']['youtube_keys'];


				$old_youtube_keys = isset($sap_youtube_options['youtube_keys']) ? $sap_youtube_options['youtube_keys'] : array();
				// Check difference of arrays
				$youtube_keys_old_data = $this->common->get_one_dim_array($old_youtube_keys);


				$youtube_keys_new_data = $this->common->get_one_dim_array($youtube_keys);

				$youtube_keys_result = array_diff($youtube_keys_new_data, $youtube_keys_old_data);
				$youtube_keys_result_vise = array_diff($youtube_keys_old_data, $youtube_keys_new_data);

				// Check any one array is different then reindex all values so if any blank row set it will not consider it.
				if (!empty($youtube_keys_result) || !empty($youtube_keys_result_vise)) {

					$new_yt_keys = array();
					$yt_count_key = 0;
					$sap_youtube_keys = array();

					foreach ($youtube_keys as $yt_key => $yt_value) {
						$yt_app_id = trim($yt_value['consumer_key']);
						$yt_app_secret = trim($yt_value['consumer_secret']);

						if (!empty($yt_app_id) || !empty($yt_app_secret)) { // Check any one key is set as not empty
							$sap_youtube_keys[$yt_count_key]['consumer_key'] = $yt_app_id;
							$sap_youtube_keys[$yt_count_key]['consumer_secret'] = $yt_app_secret;

							$yt_count_key++;
						}

						// Just taking li app ids
						if (!empty($yt_app_id) && !empty($yt_app_secret)) {
							$new_yt_keys[] = $yt_app_id;
						}
					}

					$_POST['sap_youtube_options']['youtube_keys'] = $sap_youtube_keys;


					$get_yt_sess_data = array();

					$old_yt_keys = (!empty($get_yt_sess_data) && is_array($get_yt_sess_data) ) ? array_keys($get_yt_sess_data) : array();

					// Getting difference between stored li keys and setting li keys
					$diff_yt_keys = array_diff($old_yt_keys, $new_yt_keys);

					if (!empty($diff_yt_keys)) {

						foreach ($diff_yt_keys as $flush_app_key => $flush_app_data) {
							// Removing app data from the stored li session data
							if (isset($wpw_auto_poster_yt_sess_data[$flush_app_data])) {
								unset($wpw_auto_poster_yt_sess_data[$flush_app_data]);
							}
						}

					}
					/** *** Reset facebook session ends **** */
				}
				// end code for reindexing
			}

			//Upload video of tweet
			if (!empty($_FILES['sap_yt_video']['name'])) {

				$fileUpload = new FileUploader(array());
				$uploadPath = $fileUpload->uploadFile('sap_yt_video');
				$_POST['sap_youtube_options']['sap_yt_video'] = $uploadPath;
				
			}

			//Upload image of Linkedin
			if (!empty($_FILES['linkedin_image']['name'])) {
				$fileUpload = new FileUploader(array());
				$uploadPath = $fileUpload->uploadFile('linkedin_image');
				$_POST['sap_linkedin_options']['linkedin_image'] = $uploadPath;
			}

			//Update option in DB
			$update_setting = $this->update_user_setting('sap_youtube_options', $_POST['sap_youtube_options']);

			//Check name update or exist then update
			 
			//Update youtube acoount details
			$this->update_user_setting('sap_youtube_accounts_details', $sap_youtube_accounts_details);
			// }
			//Check response for DB Update
			if (!empty($update_setting)) {

				$this->flash->setFlash($this->sap_common->lang('youtube_settings_update_msg'), 'success');
			} else {

				$this->flash->setFlash($this->sap_common->lang('setting_saving_youtube_data_error_msg'), 'error');
			}

			$_SESSION['sap_active_tab'] = 'youtube';

			$this->common->redirect('settings');
			exit();
		}

		//Save LinkedIn Settings Section
		if (!empty($_POST['sap_linkedin_options']) && isset($_POST['sap_linkedin_submit'])) {
			
			// Get linkedin account details
			if (!empty($_POST['sap_linkedin_options']['linkedin_keys'])) {

				$sap_linkedin_options = $this->get_user_setting('sap_linkedin_options');

				$linkedin_keys = $_POST['sap_linkedin_options']['linkedin_keys'];


				$old_linkedin_keys = isset($sap_linkedin_options['linkedin_keys']) ? $sap_linkedin_options['linkedin_keys'] : array();
				// Check difference of arrays
				$linkedin_keys_old_data = $this->common->get_one_dim_array($old_linkedin_keys);


				$linkedin_keys_new_data = $this->common->get_one_dim_array($linkedin_keys);

				$linkedin_keys_result = array_diff($linkedin_keys_new_data, $linkedin_keys_old_data);
				$linkedin_keys_result_vise = array_diff($linkedin_keys_old_data, $linkedin_keys_new_data);

				// Check any one array is different then reindex all values so if any blank row set it will not consider it.
				if (!empty($linkedin_keys_result) || !empty($linkedin_keys_result_vise)) {

					$new_li_keys = array();
					$li_count_key = 0;
					$sap_linkedin_keys = array();

					foreach ($linkedin_keys as $li_key => $li_value) {
						$li_app_id = trim($li_value['app_id']);
						$li_app_secret = trim($li_value['app_secret']);

						if (!empty($li_app_id) || !empty($li_app_secret)) { // Check any one key is set as not empty
							$sap_linkedin_keys[$li_count_key]['app_id'] = $li_app_id;
							$sap_linkedin_keys[$li_count_key]['app_secret'] = $li_app_secret;

							$li_count_key++;
						}

						// Just taking li app ids
						if (!empty($li_app_id) && !empty($li_app_secret)) {
							$new_li_keys[] = $li_app_id;
						}
					}

					$_POST['sap_linkedin_options']['linkedin_keys'] = $sap_linkedin_keys;


					$get_li_sess_data = array();

					$old_li_keys = (!empty($get_li_sess_data) && is_array($get_li_sess_data) ) ? array_keys($get_li_sess_data) : array();

					// Getting difference between stored li keys and setting li keys
					$diff_li_keys = array_diff($old_li_keys, $new_li_keys);

					if (!empty($diff_li_keys)) {

						foreach ($diff_li_keys as $flush_app_key => $flush_app_data) {
							// Removing app data from the stored li session data
							if (isset($wpw_auto_poster_li_sess_data[$flush_app_data])) {
								unset($wpw_auto_poster_li_sess_data[$flush_app_data]);
							}
						}

					}
					/** *** Reset facebook session ends **** */
				}
				// end code for reindexing
			}

			//Upload image of Linkedin
			if (!empty($_FILES['linkedin_image']['name'])) {
				$fileUpload = new FileUploader(array());
				$uploadPath = $fileUpload->uploadFile('linkedin_image');
				$_POST['sap_linkedin_options']['linkedin_image'] = $uploadPath;
			}

			$_POST['sap_linkedin_options']['enable_company_pages'] = $_POST['sap_linkedin_options']['enable_company_pages'];

			//Update option in DB
			$update_setting = $this->update_user_setting('sap_linkedin_options', $_POST['sap_linkedin_options']);

			//Check response for DB Update
			if (!empty($update_setting)) {
				$this->flash->setFlash($this->sap_common->lang('li_settings_update_msg'), 'success');
			} else {
				$this->flash->setFlash($this->sap_common->lang('setting_saving_li_data_error_msg'), 'error');
			}

			$_SESSION['sap_active_tab'] = 'linkedin';
			$this->common->redirect('settings');
			exit();
		}

		//Save Tumblr Settings Section
		if (!empty($_POST['sap_tumblr_options']) && isset($_POST['sap_tumblr_submit'])) {

			if (!empty($_POST['sap_tumblr_options']['tumblr_keys'])) {


				$sap_tumblr_options = $this->get_user_setting('sap_tumblr_options');
				$tumblr_keys = $_POST['sap_tumblr_options']['tumblr_keys'];

				$old_tumblr_keys = isset($sap_tumblr_options['tumblr_keys']) ? $sap_tumblr_options['tumblr_keys']: array();
				$tumblr_keys_old_data = $this->common->get_one_dim_array($old_tumblr_keys);
				$tumblr_keys_new_data = $this->common->get_one_dim_array($tumblr_keys);

				$tumblr_keys_result = array_diff($tumblr_keys_new_data, $tumblr_keys_old_data);
				$tumblr_result_vise = array_diff($tumblr_keys_old_data, $tumblr_keys_new_data);

				//// Check any one array is different then reindex all values so if any blank row set it will not consider it.
				if (!empty($tumblr_keys_result) || !empty($tumblr_result_vise)) {

					$new_tumblr_keys = array();
					$tumblr_count_key = 0;
					$sap_tumblr_keys = array();
					foreach ($tumblr_keys as $tum_key => $tum_value) {

						$tum_consu_key = trim($tum_value['tumblr_consumer_key']);
						$tum_consum_secret = trim($tum_value['tumblr_consumer_secret']);

						if (!empty($tum_consu_key) || !empty($tum_consum_secret)) { // Check any one key is set as not empty
							$sap_tumblr_keys[$tumblr_count_key]['tumblr_consumer_key'] = $tum_consu_key;
							$sap_tumblr_keys[$tumblr_count_key]['tumblr_consumer_secret'] = $tum_consum_secret;

							$tumblr_count_key++;
						}

						// Just taking tumblr app ids
						if (!empty($tum_consu_key) && !empty($tum_consum_secret)) {
							$new_tumblr_keys[] = $tum_consu_key;
						}
					}

					$_POST['sap_tumblr_options']['tumblr_keys'] = $sap_tumblr_keys;
				}
			}


			//Upload image of tumblr
			if (!empty($_FILES['tumblr_image']['name'])) {

				$fileUpload = new FileUploader(array());
				$uploadPath = $fileUpload->uploadFile('tumblr_image');

				$_POST['sap_tumblr_options']['tumblr_image'] = $uploadPath;
			}

			//Check if multiple accounts value is not empty'
			if (!empty($_POST['sap_tumblr_options']['tumblr_type_post_accounts']) || isset($_POST['sap_tumblr_options']['tumblr_type_post_accounts'])) {

				$_POST['sap_tumblr_options']['tumblr_type_post_accounts'] = $_POST['sap_tumblr_options']['tumblr_type_post_accounts'];
			}

			//Update option in DB  
			$update_setting = $this->update_user_setting('sap_tumblr_options', $_POST['sap_tumblr_options']);

			//Check response for DB Update
			if (!empty($update_setting)) {
				$this->flash->setFlash($this->sap_common->lang('tum_settings_update_msg'), 'success');
			} else {
				$this->flash->setFlash($this->sap_common->lang('setting_saving_tum_data_error_msg'), 'error');
			}

			$_SESSION['sap_active_tab'] = 'tumblr';
			$this->common->redirect('settings');
			exit();
		}

		//Save Pinterest Settings Section
		if (!empty($_POST['sap_pinterest_options']) && isset($_POST['sap_pinterest_submit'])) {

			// Get pinterest account details
			if (!empty($_POST['sap_pinterest_options']['pinterest_keys'])) {

				$sap_pinterest_options = $this->get_user_setting('sap_pinterest_options');

				$pinterest_keys = $_POST['sap_pinterest_options']['pinterest_keys'];

				// Check difference of arrays
				$pinterest_keys_old_data = array();
				if( !empty( $sap_pinterest_options['pinterest_keys'] ) && is_array($sap_pinterest_options['pinterest_keys']  ) ) {
					$pinterest_keys_old_data = $this->common->get_one_dim_array($sap_pinterest_options['pinterest_keys']);
				}
				
				$pinterest_keys_new_data = $this->common->get_one_dim_array($pinterest_keys);

				$pinterest_keys_result = array_diff($pinterest_keys_new_data, $pinterest_keys_old_data);
				$pinterest_keys_result_vise = array_diff($pinterest_keys_old_data, $pinterest_keys_new_data);

				// Check any one array is different then reindex all values so if any blank row set it will not consider it.
				if (!empty($pinterest_keys_result) || !empty($pinterest_keys_result_vise)) {

					$new_pin_keys = array();
					$pin_count_key = 0;
					$sap_pinterest_keys = array();

					foreach ($pinterest_keys as $pin_key => $pin_value) {
						$pin_app_id = trim($pin_value['app_id']);
						$pin_app_secret = trim($pin_value['app_secret']);

						if (!empty($pin_app_id) && !empty($pin_app_secret)) { // Check any one key is set as not empty
							$sap_pinterest_keys[$pin_count_key]['app_id'] = $pin_app_id;
							$sap_pinterest_keys[$pin_count_key]['app_secret'] = $pin_app_secret;

							$pin_count_key++;
						}

						// Just taking fb app ids
						if (!empty($pin_app_id) && !empty($pin_app_secret)) {
							$new_pin_keys[] = $pin_app_id;
						}
					}

					$_POST['sap_pinterest_options']['pinterest_keys'] = $sap_pinterest_keys;

					/*                     * *** Reset facebook session data is app key or appid is deleted **** */
					// Note : wpw_auto_poster_fb_reset_session() Function is called just to flush the session variable not options
					// If data is not empty then check which existing key
					$get_pin_sess_data = array();
					// Getting facebook keys from the stored session data
					$old_pin_keys = (!empty($get_pin_sess_data) && is_array($get_pin_sess_data) ) ? array_keys($get_pin_sess_data) : array();

					// Getting difference between stored fb keys and setting fb keys
					$diff_pin_keys = array_diff($old_pin_keys, $new_pin_keys);

					if (!empty($diff_pin_keys)) {

						foreach ($diff_pin_keys as $flush_app_key => $flush_app_data) {
							// Removing app data from the stored fb session data
							if (isset($wpw_auto_poster_pin_sess_data[$flush_app_data])) {
								unset($wpw_auto_poster_pin_sess_data[$flush_app_data]);
							}
						}

					}
					/*                     * *** Reset facebook session ends **** */
				}
				// end code for reindexing
			}

			//Upload image of facebook
			if (!empty($_FILES['pin_image']['name'])) {

				$fileUpload = new FileUploader(array());
				$uploadPath = $fileUpload->uploadFile('pin_image');
				$_POST['sap_pinterest_options']['pin_image'] = $uploadPath;
			}


			//Update option in DB
			$update_setting = $this->update_user_setting('sap_pinterest_options', $_POST['sap_pinterest_options']);

			//Check response for DB Update
			if (!empty($update_setting)) {
				$this->flash->setFlash($this->sap_common->lang('pit_settings_update_msg'), 'success');
			} else {
				$this->flash->setFlash($this->sap_common->lang('setting_saving_pit_data_error_msg'), 'error');
			}
			$_SESSION['sap_active_tab'] = 'pinterest';
			$this->common->redirect('settings');
			exit();
		}

		//Save Google Plus Settings Section
		if (!empty($_POST['sap_google_plus_options']) && isset($_POST['sap_google_plus_submit'])) {

			//Update option in DB
			$update_setting = $this->update_user_setting('sap_google_plus_options', $_POST['sap_google_plus_options']);

			//Check response for DB Update
			if (!empty($update_setting)) {
				$this->flash->setFlash($this->sap_common->lang('gp_settings_update_msg'), 'success');
			} else {
				$this->flash->setFlash($this->sap_common->lang('setting_saving_gp_data_error_msg'), 'error');
			}
			$_SESSION['sap_active_tab'] = 'goole_plus';
			$this->common->redirect('settings');
			exit();
		}

		//Save Reddit Settings Section
		if (!empty($_POST['sap_reddit_options']) && isset($_POST['sap_reddit_submit'])) {

			if (!empty($_FILES['reddit_image']['name'])) {

				$fileUpload = new FileUploader(array());
				$uploadPath = $fileUpload->uploadFile('reddit_image');
				$_POST['sap_reddit_options']['reddit_image'] = $uploadPath;
			}
				
			//Update option in DB
			$update_setting = $this->update_user_setting('sap_reddit_options', $_POST['sap_reddit_options']);

			//Check response for DB Update
			if (!empty($update_setting)) {
				$this->flash->setFlash($this->sap_common->lang('reddit_settings_update_msg'), 'success');
			} else {
				$this->flash->setFlash($this->sap_common->lang('setting_saving_reddit_data_error_msg'), 'error');
			}
			$_SESSION['sap_active_tab'] = 'reddit';
			$this->common->redirect('settings');
			exit();
		}

		// Save mingle SMTP settings
		if( isset($_POST['sap_smtp_submit']) && !empty($_POST['sap_smtp']) ) {
			
			$update_setting = $this->update_options('sap_smtp_setting', serialize( $_POST['sap_smtp'] ) );

			if ( !empty($update_setting) ) {
				$this->flash->setFlash($this->sap_common->lang('smtp_settings_update_msg'), 'success');
			} else {
				$this->flash->setFlash($this->sap_common->lang('setting_saving_smtp_data_error_msg'), 'error');
			}

			$this->common->redirect('smtp_settings');
			exit();
		}

		// Save Blogger settings
		if ( isset( $_POST['sap_blogger_submit'] ) && !empty( $_POST['sap_blogger_options'] ) ) {
			
			if ( !isset( $_POST['sap_blogger_options']['posts_users'] ) && empty( $_POST['sap_blogger_options']['posts_users'] ) ) {
				
				$this->flash->setFlash( $this->sap_common->lang('blogger_account_error'), 'error');
				$_SESSION['sap_active_tab'] = 'blogger';
				$this->common->redirect('settings');
				exit();
			}

			if ( !empty( $_FILES['blogger_image']['name'] ) ) {
				$fileUpload = new FileUploader(array());
				$uploadPath = $fileUpload->uploadFile('blogger_image');
				$_POST['sap_blogger_options']['blogger_image'] = $uploadPath;
			}
				
			//Update option in DB
			$update_setting = $this->update_user_setting( 'sap_blogger_options', $_POST['sap_blogger_options'] );

			//Check response for DB Update
			if ( !empty( $update_setting ) ) {
				$this->flash->setFlash( $this->sap_common->lang('blogger_settings_update_msg'), 'success');
			} else {
				$this->flash->setFlash( $this->sap_common->lang('setting_saving_blogger_data_error_msg'), 'error');
			}
			$_SESSION['sap_active_tab'] = 'blogger';
			$this->common->redirect('settings');
			exit();

		}
	}

	/**
	 * Get option settings
	 * 
	 * Handels list setting Option get
	 * 
	 * @package SAP Script
	 * @since 1.0.0
	 */
	public function get_timezones() {

		//Get result of option
		$result = $this->db->get_results("SELECT * from sap_zone ORDER BY `zone_name` ASC", true);

		return $result;
	}

	/**
	 * reset facebook account
	 * 
	 * @package Social Auto Poster
	 * @since 1.0.0
	 */
	public function sap_get_fb_rest_accounts() {

		// Taking some defaults
		$res_data = array();

		// Get stored fb app grant data
		$sap_fb_sess_data = $this->get_user_setting('sap_fb_sess_data');

		if (is_array($sap_fb_sess_data) && !empty($sap_fb_sess_data)) {

			foreach ($sap_fb_sess_data as $fb_sess_key => $fb_sess_data) {
				if ($fb_sess_key == $fb_sess_data['sap_fb_user_id']) {
					$res_data[$fb_sess_key] = $fb_sess_data['sap_fb_user_cache'];
				}
			}
		}

		return $res_data;
	}


	/**
	 * reset instagram account for insta
	 * 
	 * @package Social Auto Poster
	 * @since 1.0.0
	 */
	public function sap_get_fb_rest_accounts_for_insta() {

		// Taking some defaults
		$res_data = array();

		// Get stored insta app grant data
		$sap_insta_data = $this->get_user_setting('sap_fb_sess_data_for_insta');

		if (is_array($sap_insta_data) && !empty($sap_insta_data)) {

			foreach ($sap_insta_data as $insta_sess_key => $insta_sess_data) {
				if ($insta_sess_key == $insta_sess_data['sap_fb_user_id']) {
					$res_data[$insta_sess_key] = $insta_sess_data['sap_fb_user_cache'];
				}
			}
		}

		return $res_data;
	}

	/**
	 * Assign Pinterest User's all Data to session through cookie method
	 * 
	 * Handles to assign user's pinterest data
	 * to sessoin & save to database
	 * 
	 * @package Social Auto Poster
	 * @since 2.6.0
	 */
	public function sap_auto_poster_pinterest_add_accounts() {
		$response = array();

		if ( !empty($_POST['pin_cookie_data']) ) {
			$sessID = $_POST['pin_cookie_data'];
			$siteName = isset($_POST['pin_name']) ? $_POST['pin_name'] : '';
			$response = $this->sap_pinterest_cookie_add_accounts($sessID, $siteName);
		}
		
		$_SESSION['sap_active_tab'] = 'pinterest';
		echo json_encode($response);
	}

	/**
	 * Fetch Account details based on cookie id provided
	 * 
	 * Handles to assign user's pinterest data
	 * to sessoin & save to database
	 * 
	 * @package Social Auto Poster
	 * @since 2.6.0
	 */
	public function sap_pinterest_cookie_add_accounts( $sessID ) {
		
		$sap_auto_poster_pin_sess_data = $this->get_user_setting('sap_pin_sess_data');

		$apiURL = 'https://www.pinterest.com/resource/HomefeedBadgingResource/get/';
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $apiURL);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_ENCODING, '');
		curl_setopt($ch, CURLOPT_MAXREDIRS, 10); // Good leeway for redirections.
		curl_setopt($ch, CURLOPT_TIMEOUT, 30);
		curl_setopt($ch, CURLOPT_REFERER, 'https://pinterest.com/login/');
		curl_setopt($ch, CURLOPT_HTTPHEADER, array("X-Requested-With:XMLHttpRequest", "Accept:application/json"));
		curl_setopt($ch, CURLOPT_COOKIE, '_pinterest_sess="' . $sessID . '"');

		$response = curl_exec($ch);
		$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		curl_close($ch);

		$data = json_decode($response, true);
		if ($httpCode == '200') {

			$userData = isset($data['client_context']['user']) ? $data['client_context']['user'] : array();

			$user = array();
			if (!empty($userData['username'])) {
				$user['username'] = $userData['username'];
				$user['sessid'] = $sessID;
				$user['id'] = isset($userData['id']) ? $userData['id'] : '';
				$user['email'] = isset($userData['email']) ? $userData['email'] : '';
				$user['full_name'] = isset($userData['full_name']) ? $userData['full_name'] : '';
				$user['auth_type'] = 'cookie';

				// Get account boards
				$user['boards'] = $this->sap_get_pin_boards($userData['username']);

				$allPinData[$userData['username']] = $user;

				if (!empty($sap_auto_poster_pin_sess_data)) {
					$pin_sess_data = array_replace($sap_auto_poster_pin_sess_data, $allPinData);
				} else {
					$pin_sess_data = $allPinData;
				}
				
				//record logs for grant extended permission
				$this->sap_common->sap_script_logs('Pinterest Grant Extended Permission');

				//record logs for get parameters set properly
				$this->sap_common->sap_script_logs('Get Parameters Set Properly.');

				$this->update_user_setting('sap_pin_sess_data', $pin_sess_data);
				
				$this->sap_common->sap_script_logs('Pinterest Session Data Updated to Options');
				$_SESSION['sap_active_tab'] = 'pinterest';
				$res['status'] = 'success';
				$res['message'] = 'Account has been added successfully.';

				
			} else {

				$res['status'] = 'error';
				$res['message'] = 'Userdata does not found.';
			}
		} else {
			$res['status'] = 'error';
			$res['message'] = isset($data['resource_response']['error']['message']) ? $data['resource_response']['error']['message'] : 'Somethig goes wrong, please try later.';
		}

		return $res;
	}

	/**
	 * Get all the options available for Url shortners
	 * 
	 * @package Social Auto Poster
	 * @since 2.6.0
	 */
	public function sap_get_url_shortner_options($username) {

		$all_shortner_options = array(
			'tinyurl' => 'TinyURL',
			'bitly' => 'bit.ly',
			'shorte.st' => 'shorte.st',
		);

		return $all_shortner_options;
	}

	/**
	 * Get all Boards from user name
	 * 
	 * Handles to fetch boards based on username
	 *  
	 * 
	 * @package Social Auto Poster
	 * @since 2.6.0
	 */
	public function sap_get_pin_boards($username) {


		$apiURL = 'https://www.pinterest.com/resource/BoardsResource/get/?data=';

		$URL_Data = array(
			"options" => array("username" => $username),
		);

		$apiURL .= urlencode(json_encode($URL_Data));

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $apiURL);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_ENCODING, '');
		curl_setopt($ch, CURLOPT_MAXREDIRS, 10); // Good leeway for redirections.
		curl_setopt($ch, CURLOPT_TIMEOUT, 30);

		$response = curl_exec($ch);

		$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		curl_close($ch);

		$boardsArr = array();
		if ($httpCode == '200') {
			$data = json_decode($response, true);

			$boards = !empty($data['resource_response']['data']) ? $data['resource_response']['data'] : array();

			foreach ($boards as $key => $board) {
				$boardsArr[$board['id']] = array(
					'id' => $board['id'],
					'name' => $board['name'],
					'url' => ltrim($board['url'], '/'),
				);
			}
		}

		return $boardsArr;
	}

	/**
	 * get all facebook account
	 * 
	 * @package Social Auto Poster
	 * @since 1.0.0
	 */

	public function  sap_get_insta_fb_app_accounts(){

		// Taking some defaults
		$res_data = array();

		// Get stored fb app grant data
		$sap_insta_sess_data = $this->get_user_setting('sap_fb_sess_data_for_insta');

		if (is_array($sap_insta_sess_data) && !empty($sap_insta_sess_data)) {

			foreach ($sap_insta_sess_data as $insta_sess_key => $fb_sess_data) {
				if ($insta_sess_key == $fb_sess_data['sap_insta_user_id']) {
					$res_data[$insta_sess_key] = $fb_sess_data['sap_insta_user_cache'];
				}
			}
		}

		return $res_data;		

	}

	/**
	 * get all facebook account
	 * 
	 * @package Social Auto Poster
	 * @since 1.0.0
	 */
	public function sap_get_fb_app_accounts() {

		// Taking some defaults
		$res_data = array();

		// Get stored fb app grant data
		$sap_fb_sess_data = $this->get_user_setting('sap_fb_sess_data');

		if (is_array($sap_fb_sess_data) && !empty($sap_fb_sess_data)) {

			foreach ($sap_fb_sess_data as $fb_sess_key => $fb_sess_data) {
				if ($fb_sess_key == $fb_sess_data['sap_fb_user_id']) {
					$res_data[$fb_sess_key] = $fb_sess_data['sap_fb_user_cache'];
				}
			}
		}

		return $res_data;
	}


	/**
	 * get all facebook account
	 * 
	 * @package Social Auto Poster
	 * @since 1.0.0
	 */
	public function sap_get_fb_app_accounts_for_insta() {

		// Taking some defaults
		$res_data = array();

		// Get stored fb app grant data
		$sap_instaData = $this->get_user_setting('sap_fb_sess_data_for_insta');
		

		if (is_array($sap_instaData) && !empty($sap_instaData)) {

			foreach ($sap_instaData as $insta_sess_key => $insta_sess_data) {
				if ($insta_sess_key == $insta_sess_data['sap_fb_user_id']) {
				 $res_data[$insta_sess_key]=$insta_sess_data['sap_fb_user_cache'];
				}
			}
		}

		return $res_data;
	}


	/**
	 * Payment setting page
	 * 
	 * @package Social Auto Poster
	 * @since 1.0.0
	 */
	public function general_settings(){


		$test_publishable_key 	= $this->get_options('test_publishable_key');
		$test_secret_key 		= $this->get_options('test_secret_key');

		$live_publishable_key 	= $this->get_options('live_publishable_key');
		$live_secret_key 		= $this->get_options('live_secret_key');
		
		$renewal_email_subject 	= $this->get_options('renewal_email_subject');		
		$renewal_email_content	= $this->get_options('renewal_email_content');

		$enable_billing_details	= $this->get_options('enable_billing_details');

		
		$stripe_test_mode	= $this->get_options('stripe_test_mode');
		$stripe_label	= $this->get_options('stripe_label');

		$payment_gateway 	= $this->get_options('payment_gateway');		
		$default_payment_method	= $this->get_options('default_payment_method');	
		

		//Includes Html files for Posts list
		if ( !sap_current_user_can('general-settings') ) {					
			$template_path = $this->common->get_template_path('Settings/' . DS . 'stripe-settings.php' );
			include_once( $template_path );
		}
		else {
			$this->common->redirect('login');
		}		
	}


	/**
	 * Save stripe setting
	 * 
	 * @package Social Auto Poster
	 * @since 1.0.0
	 */
	public function save_stripe_settings(){

		$uploads_folder      =  SAP_APP_PATH.'uploads/';
		$is_uploads_writable =  is_writable($uploads_folder);
		//Call Upload class and upload media
				
		if($is_uploads_writable){
		
			$fileUpload = new FileUploader(array());
			$uploadPathLogo = $fileUpload->uploadFile('mingle_logo');

			if ( !is_int($uploadPathLogo) || !is_numeric($uploadPathLogo) ) {
				 
				 	
				 	
				$mingle_favicon = isset( $uploadPathLogo ) ? $this->db->filter($uploadPathLogo) : '';
						
					
				$this->update_options('mingle_logo',$mingle_favicon);
			}else if(isset($_POST['mingle_logo_file']) && empty($_POST['mingle_logo_file']) ){
				$this->update_options('mingle_logo','');
			}
			
		}
				
	
		//Check media uploaded

		if($is_uploads_writable){

			$fileUpload = new FileUploader(array());
			$uploadPathFavIcon = $fileUpload->uploadFile('mingle_favicon');

			if (!is_int($uploadPathFavIcon) || !is_numeric($uploadPathFavIcon)) {
				
				$mingle_favicon = isset( $uploadPathFavIcon ) ? $this->db->filter($uploadPathFavIcon) : '';
				$this->update_options('mingle_favicon',$mingle_favicon);

			}else if(isset($_POST['mingle_favicon_file']) && empty($_POST['mingle_favicon_file']) ){
				$this->update_options('mingle_favicon','');
			}

		}
		
		//save chatGPT Api Key
		if(isset($_POST['sap_chatgpt_api_key'])){
			$this->update_options('sap_chatgpt_api_key', $_POST['sap_chatgpt_api_key'] );
		}
		
		//save site name
		if( isset( $_POST['mingle_site_name'] ) ){
			$this->update_options('mingle_site_name', $_POST['mingle_site_name'] );
		}

		//save meta title
		if( isset( $_POST['mingle_meta_title'] ) ){
			$this->update_options('mingle_meta_title', $_POST['mingle_meta_title'] );
		}

		//save meta description
		if( isset( $_POST['mingle_meta_description'] ) ){
			$this->update_options('mingle_meta_description', $_POST['mingle_meta_description'] );
		} 

		//Check media uploaded
		
		$enable_email_verification = isset( $_POST['enable_email_verification'] ) ? 'yes' : 'no';
		$enable_misc_relative_path = isset( $_POST['enable_misc_relative_path'] ) ? 'yes' : 'no';
		$test_publishable_key = isset( $_POST['test_publishable_key'] ) ? $this->db->filter($_POST['test_publishable_key']) : '';
		$test_secret_key = isset( $_POST['test_secret_key'] ) ? $this->db->filter($_POST['test_secret_key']) : '';
		
		
		$enable_billing_details = isset( $_POST['enable_billing_details'] ) ? $this->db->filter($_POST['enable_billing_details']) : '';


		$live_publishable_key = isset( $_POST['live_publishable_key'] ) ? $this->db->filter($_POST['live_publishable_key']) : '';
		$live_secret_key      = isset( $_POST['live_secret_key'] ) ? $this->db->filter($_POST['live_secret_key']) : '';

		$footer_content = isset($_POST['footer_content']) ? $this->db->escape($_POST['footer_content']) : '';

		$renewal_email_subject = isset($_POST['renewal_email_subject']) ? $this->db->escape($_POST['renewal_email_subject']) : '';
		$renewal_email_content = isset($_POST['renewal_email_content']) ? $this->db->escape($_POST['renewal_email_content']) : '';


		$cancelled_membership_email_subject = isset($_POST['cancelled_membership_email_subject']) ? $this->db->escape($_POST['cancelled_membership_email_subject']) : '';
		$cancelled_membership_email_content = isset($_POST['cancelled_membership_email_content']) ? $this->db->escape($_POST['cancelled_membership_email_content']) : '';


		$expired_membership_email_subject = isset($_POST['expired_membership_email_subject']) ? $this->db->escape($_POST['expired_membership_email_subject']) : '';
		$expired_membership_email_content = isset($_POST['expired_membership_email_content']) ? $this->db->escape($_POST['expired_membership_email_content']) : '';


		if( isset( $_POST['payment_gateway'] ) ){

			if(!empty($_POST['payment_gateway'])){

				$payment_gateway = implode(',',$_POST['payment_gateway']);
			}			
		} 
		else{
			$payment_gateway = '';
		}

		$this->update_options('payment_gateway',$payment_gateway);
		$default_payment_method = isset($_POST['default_payment_method']) ?  $_POST['default_payment_method'] : '';
		$this->update_options('default_payment_method',$default_payment_method);
		
		$stripe_test_mode = isset( $_POST['stripe_test_mode'] ) ? 'yes' : 'no';
		$stripe_label = isset( $_POST['stripe_label'] ) ? $_POST['stripe_label'] : '';

		$this->update_options('test_publishable_key',$test_publishable_key);
		$this->update_options('test_secret_key',$test_secret_key);
		
		$this->update_options('enable_billing_details',$enable_billing_details);
		
		$this->update_options('live_publishable_key',$live_publishable_key);
		$this->update_options('live_secret_key',$live_secret_key);

		$this->update_options('stripe_test_mode',$stripe_test_mode);		
		$this->update_options('stripe_label',$stripe_label);		

		$this->update_options('renewal_email_subject',$renewal_email_subject);
		$this->update_options('renewal_email_content',$renewal_email_content);	
		$this->update_options('footer_content',$footer_content);	
	
		$this->update_options('cancelled_membership_email_subject',$cancelled_membership_email_subject);
		$this->update_options('cancelled_membership_email_content',$cancelled_membership_email_content);

		$this->update_options('expired_membership_email_subject',$expired_membership_email_subject);
		$this->update_options('expired_membership_email_content',$expired_membership_email_content);	

		$this->update_options('enable_email_verification',$enable_email_verification);
		$this->update_options('enable_misc_relative_path',$enable_misc_relative_path);

		$this->flash->setFlash($this->sap_common->lang('settings_update_msg'), 'success');
		$this->common->redirect('general_settings');	
		
		$_SESSION['sap_active_tab'] =  !empty($_POST['sap_active_tab']) ? $_POST['sap_active_tab']:"";

		$sap_smtp = $this->db->escape($_POST['sap_smtp']);
		$update_setting = $this->update_options('sap_smtp_setting', serialize( $sap_smtp ) );

	}
}