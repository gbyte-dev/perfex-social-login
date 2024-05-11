<?php

/* Check the absolute path to the Social Auto Poster directory. */
if ( !defined( 'SAP_APP_PATH' ) ) {
    // If SAP_APP_PATH constant is not defined, perform some action, show an error, or exit the script
    // Or exit the script if required
    exit();
}

/**
 * Quick Posts Class
 * 
 * Responsible for all function related to Quick posts
 *
 * @package Social Auto Poster
 * @since 1.0.0
 */
require_once(CLASS_PATH . 'Settings.php');

// For chat-GPT message generatation
require_once(SAP_APP_PATH . DS . 'Lib' . DS . 'vendor/autoload.php');
use Orhanerday\OpenAi\OpenAi;

class SAP_Quick_Posts
{

	//Set Database variable
	private $db;
	//Set table name
	private $table_name;
	private $post_meta_table_name;
	private $settings;
	public $flash;
	public $common, $sap_common, $tumblr;


	public function __construct()
	{

		global $sap_db_connect, $sap_common;

		$this->db = $sap_db_connect;
		$this->flash = new Flash();
		$this->common = new Common();
		$this->table_name = 'sap_quick_posts';

		$this->post_meta_table_name = 'sap_quick_postmeta';

		$this->settings = new SAP_Settings();
		$this->sap_common = $sap_common;
	}

	/**
	 * Listing page of Posts
	 * 
	 * @package Social Auto Poster
	 * @since 1.0.0
	 */
	public function index()
	{
		include(SAP_APP_PATH . DS . 'Lib' . DS . 'vendor/autoload.php');
		//Includes Html files for Posts list
		if (sap_current_user_can('quick-post')) {
			if (!class_exists('SAP_Tumblr')) {
				require_once(CLASS_PATH . 'Social' . DS . 'tumblrConfig.php');
			}

			$this->tumblr = new SAP_Tumblr();

			$template_path = $this->common->get_template_path('Quick-Posts' . DS . 'index.php');
			include_once($template_path);
		} else {
			$this->common->redirect('login');
		}
	}

	/**
	 * Save post to database
	 * 
	 * @package Social Auto Poster
	 * @since 1.0.0
	 */
	public function save_post()
	{

		//Check form submit request
		if (isset($_POST['networks'])) {

			//Call Upload class and upload media
			$fileUpload = new FileUploader(array());
			$validate_size = 1;
			if (!empty($_FILES['video']['name'])) {
				//$_POST['video'] = $uploadPath;
				if ($_POST['networks']['facebook']) {
					$available_file_size = $_FILES['video']['size'];
					$size_conversion = number_format($available_file_size / 1048576, 2);
					if ($size_conversion <= 1000) {
						$validate_size = 1;
					} else {
						$this->flash->setFlash('Limit for video Size in Facebook Posting is maximum 1GB', 'error');
						$validate_size = 0;
					}
				}

				if ($_POST['networks']['twitter']) {
					$available_file_size = $_FILES['video']['size'];
					$size_conversion = number_format($available_file_size / 1048576, 2);
					if ($size_conversion <= 10) {

						$validate_size = 1;
					} else {
						$this->flash->setFlash('Limit for video size in Twitter Posting is maximum 10MB', 'error');
						$validate_size = 0;
					}
				}

				if ($_POST['networks']['tumblr']) {
					$available_file_size = $_FILES['video']['size'];
					$size_conversion = number_format($available_file_size / 1048576, 2);
					if ($size_conversion <= 17) {

						$validate_size = 1;
					} else {
						$this->flash->setFlash('Limit for video size in Tumblr Posting is maximum 17MB', 'error');
						$validate_size = 0;
					}
				}

				if ($validate_size) {
					$uploadPath = $fileUpload->uploadFile('image');
					$_POST['video'] = $uploadPath;
				}
			} else {
				//Check media uploaded
				$uploadPath = $fileUpload->uploadFile('image');
				if (!is_int($uploadPath) || !is_numeric($uploadPath)) {
					$_POST['image'] = $uploadPath;
					$validate_size = 1;
				}
			}




			$user_id = sap_get_current_user_id();
			$user_options = $this->settings->get_user_setting('sap_general_options', $user_id);
			$timezone = (!empty($user_options['timezone'])) ? $user_options['timezone'] : ''; // user timezone

			//Update time zone based on user setting
			if (!empty($timezone)) { // set default timezone
				date_default_timezone_set($timezone);
			}

			//Check post global time is scheduled
			//Status 2 means whole post set scheduled
			if (!empty($_POST['sap-schedule-time']) && strtotime($_POST['sap-schedule-time']) > strtotime(date('Y-m-d H:i:s'))) {
				$_POST['status'] = 2;
			} else {

				// Check individual post set scheduled
				if ((!empty($_POST['sap-schedule-time-fb']) && strtotime($_POST['sap-schedule-time-fb']) > strtotime(date('Y-m-d H:i:s'))) ||
					(!empty($_POST['sap-schedule-time-blogger']) && strtotime($_POST['sap-schedule-time-blogger']) > strtotime(date('Y-m-d H:i:s'))) ||
					(!empty($_POST['sap-schedule-time-tw']) && strtotime($_POST['sap-schedule-time-tw']) > strtotime(date('Y-m-d H:i:s'))) ||
					(!empty($_POST['sap-schedule-time-li']) && strtotime($_POST['sap-schedule-time-li']) > strtotime(date('Y-m-d H:i:s'))) ||
					(!empty($_POST['sap-schedule-time-tumblr']) && strtotime($_POST['sap-schedule-time-tumblr']) > strtotime(date('Y-m-d H:i:s'))) ||
					(!empty($_POST['sap-schedule-time-pin']) && strtotime($_POST['sap-schedule-time-pin']) > strtotime(date('Y-m-d H:i:s'))) ||
					(!empty($_POST['sap-schedule-time-gmb']) && strtotime($_POST['sap-schedule-time-gmb']) > strtotime(date('Y-m-d H:i:s'))) ||
					(!empty($_POST['sap-schedule-time-instagram']) && strtotime($_POST['sap-schedule-time-instagram']) > strtotime(date('Y-m-d H:i:s'))) ||
					(!empty($_POST['sap-schedule-time-reddit']) && strtotime($_POST['sap-schedule-time-reddit']) > strtotime(date('Y-m-d H:i:s')))
				) {
					$_POST['status'] = 2;
				}

				// check individual time and global time both empty but toggle is on then instant
				if ((empty($_POST['sap-schedule-time']) && empty($_POST['sap-schedule-time-fb']) && $_POST['networks']['facebook']) ||
					(empty($_POST['sap-schedule-time']) && empty($_POST['sap-schedule-time-blogger']) && $_POST['networks']['blogger']) ||
					(empty($_POST['sap-schedule-time']) && empty($_POST['sap-schedule-time-tw']) && $_POST['networks']['twitter']) ||
					(empty($_POST['sap-schedule-time']) && empty($_POST['sap-schedule-time-li']) && $_POST['networks']['linkedin']) ||
					(empty($_POST['sap-schedule-time']) && empty($_POST['sap-schedule-time-tumblr']) && $_POST['networks']['tumblr']) ||
					(empty($_POST['sap-schedule-time']) && empty($_POST['sap-schedule-time-pin']) && $_POST['networks']['pinterest']) ||
					(empty($_POST['sap-schedule-time']) && empty($_POST['sap-schedule-time-gmb']) && $_POST['networks']['gmb']) ||
					(empty($_POST['sap-schedule-time']) && empty($_POST['sap-schedule-time-instagram']) && $_POST['networks']['instagram']) ||
					(empty($_POST['sap-schedule-time']) && empty($_POST['sap-schedule-time-reddit']) && $_POST['networks']['reddit'])
				) {
					$_POST['individual_status'] = 1;
				}
			}

			$_POST['ip'] = $this->common->get_user_ip();
			if ($_POST['sap-active-tab'] == 'sap-custom-tab') {
				$_POST['message'] = $_POST['custom-message'];
				$_POST['share_link'] = $_POST['custom_share_link'];
			} else {
				$_POST['message'] = $_POST['ai-message'];
				$_POST['share_link'] = $_POST['ai_share_link'];
			}
			$_POST['message'] = !empty($_POST['message']) ? $this->db->filter($_POST['message']) : '';
			$_POST['message'] = !empty($_POST['message']) ? $_POST['message'] : '';
			$_POST['created_date'] = date('Y-m-d H:i:s');

			// get current user id
			$user_id = sap_get_current_user_id();

			//Prepare data for store post in DB
			$prepare_data = array(
				'message'		=> $_POST['message'],
				'user_id'		=> $user_id,
				'image'			=> $_POST['image'],
				'video'         => $_POST['video'],
				'ip_address'	=> $_POST['ip'],
				'status'		=> isset($_POST['status']) ? $_POST['status'] : 0,
				'created_date'	=> $_POST['created_date'],
			);

			if (isset($_POST['share_link'])) {
				$prepare_data['share_link'] = $_POST['share_link'];
			}

			$prepare_data = $this->db->escape($prepare_data);

			if ($this->db->insert($this->table_name, $prepare_data)) {

				$_POST['id'] = $this->db->lastid();

				// update meta value for the post
				$this->save_post_meta();

				//Check Post Published Or Draft
				$uploads_folder      =  SAP_APP_PATH . 'uploads/';
				$is_uploads_writable =  is_writable($uploads_folder);


				if (!$is_uploads_writable) {
					$this->sap_common->sap_script_logs('Please provide the write permission to the directory ( ' . $uploads_folder . ' )');
					$this->flash->setFlash('Please provide the write permission to the directory ( ' . $uploads_folder . ' )', 'error');
				} else {

					//instant post for whose not have individual time and global time is also not set 
					if (isset($_POST['individual_status']) && $_POST['status'] != 1 && $validate_size) {
						$this->sap_manage_wall_social_post($_POST['id'], $_POST['individual_status'], $user_id);
					}
					if (isset($_POST['status']) && $validate_size) {
						$this->sap_manage_wall_social_post($_POST['id'], $_POST['status'], $user_id);
					}
				}



				header("Location:" . SAP_SITE_URL . "/quick-post/");
				exit;
			} else {
				$this->flash->setFlash('There was error while saving data', 'error');
			}
		}
	}

	/**
	 * View Posts
	 * 
	 * @package Social Auto Poster
	 * @since 1.0.0
	 */
	public function view_post()
	{

		if (isset($_SESSION['user_details']) && !empty($_SESSION['user_details'])) {
			if (!class_exists('SAP_Tumblr')) {
				require_once(CLASS_PATH . 'Social' . DS . 'tumblrConfig.php');
			}

			$tumblr = new SAP_Tumblr();

			$template_path = $this->common->get_template_path('Quick-Posts' . DS . 'edit.php');
			include_once($template_path);
		} else {
			$this->common->redirect('login');
		}
	}

	/**
	 * Update Quick Post
	 * 
	 * @package Social Auto Poster
	 * @since 1.0.0
	 */
	public function update_post($prepare_data = array(), $where_data = array())
	{

		if (isset($_POST['quick-form-updated'])) {

			$post_id = $_POST['id'];

			//Call Upload class and upload media
			$fileUpload = new FileUploader(array());
			$validate_size = 1;
			if (!empty($_FILES['video']['name'])) {
				//$_POST['video'] = $uploadPath;
				if ($_POST['networks']['facebook']) {
					$available_file_size = $_FILES['video']['size'];
					$size_conversion = number_format($available_file_size / 1048576, 2);
					if ($size_conversion <= 1000) {
						$validate_size = 1;
					} else {
						$this->flash->setFlash('Limit for video Size in Facebook Posting is maximum 1GB', 'error');
						$validate_size = 0;
					}
				}

				if ($_POST['networks']['twitter']) {
					$available_file_size = $_FILES['video']['size'];
					$size_conversion = number_format($available_file_size / 1048576, 2);
					if ($size_conversion <= 10) {

						$validate_size = 1;
					} else {
						$this->flash->setFlash('Limit for video size in Twitter Posting is maximum 10MB', 'error');
						$validate_size = 0;
					}
				}

				if ($_POST['networks']['tumblr']) {
					$available_file_size = $_FILES['video']['size'];
					$size_conversion = number_format($available_file_size / 1048576, 2);
					if ($size_conversion <= 17) {

						$validate_size = 1;
					} else {
						$this->flash->setFlash('Limit for video size in Tumblr Posting is maximum 17MB', 'error');
						$validate_size = 0;
					}
				}

				if ($validate_size) {
					$uploadPath = $fileUpload->uploadFile('image');
					$_POST['video'] = $uploadPath;
				}
			} else {
				//Check media uploaded
				$uploadPath = $fileUpload->uploadFile('image');
				if (!is_int($uploadPath) || !is_numeric($uploadPath)) {
					$_POST['image'] = $uploadPath;
					$validate_size = 1;
				} else {
					$_POST['image'] = '';
				}
			}


			$user_id = sap_get_current_user_id();
			$user_options = $this->settings->get_user_setting('sap_general_options', $user_id);
			$timezone = (!empty($user_options['timezone'])) ? $user_options['timezone'] : ''; // user timezone

			//Update time zone based on user setting
			if (!empty($timezone)) { // set default timezone
				date_default_timezone_set($timezone);
			}

			//Check post set scheduled
			if (!empty($_POST['sap-schedule-time']) && strtotime($_POST['sap-schedule-time']) > strtotime(date('Y-m-d H:i:s'))) {
				$_POST['status'] = 2;
			} else {

				// Check individual post set scheduled
				if ((!empty($_POST['sap-schedule-time-fb']) && strtotime($_POST['sap-schedule-time-fb']) > strtotime(date('Y-m-d H:i:s'))) ||
					(!empty($_POST['sap-schedule-time-blogger']) && strtotime($_POST['sap-schedule-time-blogger']) > strtotime(date('Y-m-d H:i:s'))) ||
					(!empty($_POST['sap-schedule-time-tw']) && strtotime($_POST['sap-schedule-time-tw']) > strtotime(date('Y-m-d H:i:s'))) ||
					(!empty($_POST['sap-schedule-time-li']) && strtotime($_POST['sap-schedule-time-li']) > strtotime(date('Y-m-d H:i:s'))) ||
					(!empty($_POST['sap-schedule-time-tumblr']) && strtotime($_POST['sap-schedule-time-tumblr']) > strtotime(date('Y-m-d H:i:s'))) ||
					(!empty($_POST['sap-schedule-time-pin']) && strtotime($_POST['sap-schedule-time-pin']) > strtotime(date('Y-m-d H:i:s'))) ||
					(!empty($_POST['sap-schedule-time-gmb']) && strtotime($_POST['sap-schedule-time-gmb']) > strtotime(date('Y-m-d H:i:s'))) ||
					(!empty($_POST['sap-schedule-time-instagram']) && strtotime($_POST['sap-schedule-time-instagram']) > strtotime(date('Y-m-d H:i:s'))) ||
					(!empty($_POST['sap-schedule-time-reddit']) && strtotime($_POST['sap-schedule-time-reddit']) > strtotime(date('Y-m-d H:i:s')))
				) {
					$_POST['status'] = 2;
				}

				// check individual time and global time both empty but toggle is on then instant
				if ((empty($_POST['sap-schedule-time']) && empty($_POST['sap-schedule-time-fb']) && $_POST['networks']['facebook']) ||
					(empty($_POST['sap-schedule-time']) && empty($_POST['sap-schedule-time-blogger']) && $_POST['networks']['blogger']) ||
					(empty($_POST['sap-schedule-time']) && empty($_POST['sap-schedule-time-tw']) && $_POST['networks']['twitter']) ||
					(empty($_POST['sap-schedule-time']) && empty($_POST['sap-schedule-time-li']) && $_POST['networks']['linkedin']) ||
					(empty($_POST['sap-schedule-time']) && empty($_POST['sap-schedule-time-tumblr']) && $_POST['networks']['tumblr']) ||
					(empty($_POST['sap-schedule-time']) && empty($_POST['sap-schedule-time-pin']) && $_POST['networks']['pinterest']) ||
					(empty($_POST['sap-schedule-time']) && empty($_POST['sap-schedule-time-gmb']) && $_POST['networks']['gmb']) ||
					(empty($_POST['sap-schedule-time']) && empty($_POST['sap-schedule-time-instagram']) && $_POST['networks']['instagram']) ||
					(empty($_POST['sap-schedule-time']) && empty($_POST['sap-schedule-time-reddit']) && $_POST['networks']['reddit'])
				) {
					$_POST['individual_status'] = 1;
				}
			}


			$_POST['ip'] = $this->common->get_user_ip();
			if ($_POST['sap-active-tab'] == 'sap-custom-tab') {
				$_POST['message'] = $_POST['custom-message'];
				$_POST['share_link'] = $_POST['custom_share_link'];
			} else {
				$_POST['message'] = $_POST['ai-message'];
				$_POST['share_link'] = $_POST['ai_share_link'];
			}
			$_POST['message'] = !empty($_POST['message']) ? $this->db->filter($_POST['message']) : '';
			$_POST['message'] = !empty($_POST['message']) ? $_POST['message'] : '';
			$_POST['modified_date'] = date('Y-m-d H:i:s');

			// get current user id
			$user_id = sap_get_current_user_id();

			//Prepare data for store post in DB
			$prepare_data = array(
				'message'		=> $_POST['message'],
				'user_id'		=> $user_id,
				'image'			=> $_POST['image'],
				'video'         => $_POST['video'],
				'ip_address'	=> $_POST['ip'],
				'status'		=> isset($_POST['status']) ? $_POST['status'] : 0,
				'modified_date'	=> $_POST['modified_date'],
			);

			if (isset($_POST['share_link'])) {
				$prepare_data['share_link'] = $_POST['share_link'];
			}

			$prepare_data = $this->db->escape($prepare_data);

			if ($this->db->update($this->table_name, $prepare_data, array('post_id' => $post_id))) {

				// update meta value for the post
				$this->save_post_meta();

				//Check Post Published Or Draft
				$uploads_folder      =  SAP_APP_PATH . 'uploads/';
				$is_uploads_writable =  is_writable($uploads_folder);


				if (!$is_uploads_writable) {
					$this->sap_common->sap_script_logs('Please provide the write permission to the directory ( ' . $uploads_folder . ' )');
					$this->flash->setFlash('Please provide the write permission to the directory ( ' . $uploads_folder . ' )', 'error');
				} else {

					if (isset($_POST['individual_status']) && $_POST['status'] != 1 && $validate_size) {
						$this->sap_manage_wall_social_post($_POST['id'], $_POST['individual_status'], $user_id);
					}

					if (isset($_POST['status']) && $validate_size) {
						$this->sap_manage_wall_social_post($_POST['id'], $_POST['status'], $user_id);
					}
				}

				$this->flash->setFlash('Quick post updated successfully', 'success');
				header("Location:" . SAP_SITE_URL . "/quick-post/view/" . $post_id);
				exit;
			} else {
				$this->flash->setFlash('There was error while updating data', 'error');
			}
		}

		if (!empty($prepare_data) && !empty($where_data)) {
			//Run update query in db and return result
			return $this->db->update($this->table_name, $prepare_data, $where_data);
		}
	}

	/**
	 * Delete Posts
	 * 
	 * @package Social Auto Poster
	 * @since 1.0.0
	 */
	public function delete_post()
	{

		if (!empty($_REQUEST['post_id'])) {

			$result 	= array();
			$post_id 	= $_REQUEST['post_id'];
			$conditions = array('post_id' => $post_id);
			$result = $this->db->get_results("SELECT * FROM " . $this->table_name . " where `post_id` = " . $post_id);

			$img = $result[0]->image;

			if ($img == '0') {
				$img_path = '';
			} else {
				$img_path = SAP_APP_PATH . 'uploads/' . $img;
			}

			$video = $result[0]->video;

			if (!empty($video) || $video != '') {
				$video_path =	SAP_APP_PATH . 'uploads/' . $video;
			} else {
				$video_path = '';
			}

			$is_deleted = $this->db->delete($this->table_name, $conditions);
			$this->db->delete($this->post_meta_table_name, $conditions);

			if ($is_deleted) {
				$result = array('status' => '1');
				unlink($img_path);
				unlink($video_path);
			} else {
				$result = array('status' => '0');
			}

			echo json_encode($result);

			die;
		}
	}

	/**
	 * Delete Multiple posts
	 * 
	 * @package Social Auto Poster
	 * @since 1.0.0
	 */
	public function delete_multiple_post()
	{

		if (!empty($_REQUEST['id'])) {
			$result = array();
			$post_id = $_REQUEST['id'];
			foreach ($post_id as $key => $value) {
				$conditions = array('post_id' => $value);
				$is_deleted = $this->db->delete($this->table_name, $conditions);
				$this->db->delete($this->post_meta_table_name, $conditions);
			}
			if ($is_deleted) {
				$url = SAP_SITE_URL . "/quick-post/";
				$result = array('status' => '1', "redirect_url" => $url);
				$this->flash->setFlash('Selected posts has been deleted', 'success');
			} else {
				$result = array('status' => '0');
			}
			echo json_encode($result);
			die;
		}
	}

	/**
	 * Get post settings
	 * 
	 * Handels list setting Option get
	 * 
	 * @package Social Auto Poster
	 * @since 1.0.0
	 */
	public function get_post($post_id, $object)
	{

		$result = array();
		if (isset($post_id) && !empty($post_id)) {
			try {
				$result = $this->db->get_row("SELECT * FROM " . $this->table_name . " where `post_id` = '{$post_id}'", $object);
			} catch (Exception $e) {
				return $e->getMessage();
			}
			//Return result
			return $result;
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
	public function get_posts()
	{
		$result = array();
		try {
			$user_id = sap_get_current_user_id();
			$result = $this->db->get_results("SELECT * FROM " . $this->table_name . " WHERE `user_id` = {$user_id} ORDER BY `created_date` DESC");
		} catch (Exception $e) {
			return $e->getMessage();
		}

		//Return result
		return $result;
	}

	/**
	 * Get all posts
	 * 
	 * Handels post listing
	 * 
	 * @package Social Auto Poster
	 * @since 1.0.0
	 */
	public function get_posts_by_status($status)
	{
		$result = array();
		try {
			$user_id = sap_get_current_user_id();
			$result = $this->db->get_results("SELECT * FROM " . $this->table_name . " where `status` = '{$status}' AND `user_id` = {$user_id} ORDER BY `created_date` DESC");
		} catch (Exception $e) {
			return $e->getMessage();
		}

		//Return result
		return $result;
	}

	/**
	 * Update option settings
	 * 
	 * Handels to Update post meta
	 * 
	 * @package Social Auto Poster
	 * @since 1.0.0
	 */
	public function update_post_meta($post_id, $meta_key, $meta_value)
	{

		if (!empty($post_id) && !empty($meta_key)) {

			//Check option exist in Database
			$check_postmeta_exist = $this->db->num_rows("SELECT * FROM " . $this->post_meta_table_name . " WHERE post_id = '{$post_id}' AND meta_key = '{$meta_key}' ");

			//Exist database set update query another insert option
			if ($check_postmeta_exist) {

				//Prepare data for update
				$post_meta_data = array('post_id' => $post_id, 'meta_key' => $meta_key, 'meta_value' => is_array($meta_value) ? serialize($meta_value) : $this->db->filter($meta_value));
				$where_data = array('post_id' => $post_id, 'meta_key' => $meta_key);

				//Run update query in db and return result
				return $this->db->update($this->post_meta_table_name, $post_meta_data, $where_data);
			} else {

				//Prepare data for insert
				$post_meta_data = array('post_id' => $post_id, 'meta_key' => $meta_key, 'meta_value' => is_array($meta_value) ? serialize($meta_value) : $this->db->filter($meta_value));

				//Run query and insert option in db
				$this->db->insert($this->post_meta_table_name, $post_meta_data);

				//Return inserted ID
				return $this->db->lastid();
			}
		}
	}

	/**
	 * Delete option settings
	 * 
	 * Handels to delete post meta
	 * 
	 * @package Social Auto Poster
	 * @since 1.0.0
	 */
	public function delete_post_meta($post_id, $meta_key)
	{

		if (!empty($post_id) && !empty($meta_key)) {
			$post_meta_data = array('post_id' => $post_id, 'meta_key' => $meta_key);
			//Run database and Insert options in table
			$result = $this->db->delete($this->post_meta_table_name, $post_meta_data);

			//Return result
			return $result;
		}
	}

	/**
	 * Update option settings
	 * 
	 * Handels to get post meta
	 * 
	 * @package Social Auto Poster
	 * @since 1.0.0
	 */
	public function get_post_meta($post_id, $meta_key)
	{

		if (!empty($post_id) && !empty($meta_key)) {
			//Check option exist in Database
			$postmeta_data = $this->db->get_row("SELECT * FROM " . $this->post_meta_table_name . " WHERE post_id = '{$post_id}' AND meta_key = '{$meta_key}' ");

			if (isset($postmeta_data[3]) && $this->common->is_serialized($postmeta_data[3])) {
				$result = unserialize($postmeta_data[3]);
			} elseif (isset($postmeta_data[3]) && is_string($postmeta_data[3])) {
				$result = $postmeta_data[3];
			} else {
				$result = '';
			}
			return $result;
		}
	}

	/**
	 * Reset option settings
	 * 
	 * Handels to reset post status
	 * 
	 * @package Social Auto Poster
	 * @since 1.0.0
	 */
	public function reset_post_status()
	{

		if (!empty($_REQUEST['post_id']) && !empty($_REQUEST['meta_key'])) {

			$result = array();
			$post_id = $_REQUEST['post_id'];
			$meta_key = $_REQUEST['meta_key'];

			$is_deleted = $this->delete_post_meta($post_id, $meta_key);
			if ($is_deleted) {
				$result = array('status' => '1');
			} else {
				$result = array('status' => '0');
			}
			echo json_encode($result);
			die;
		}
	}

	/**
	 * Saving post meta 
	 * 
	 * @package Social Auto Poster
	 * @since 1.0.0
	 */
	public function save_post_meta()
	{

		//Preapare Data global time 
		if (!empty($_POST['status']) && $_POST['status'] == 2 && !empty($_POST['sap-schedule-time'])) {
			$this->update_post_meta($_POST['id'], 'sap_schedule_time', strtotime($_POST['sap-schedule-time']));
		}

		//Preapare Data individual facebook time
		if (!empty($_POST['status']) && $_POST['status'] == 2 && !empty($_POST['sap-schedule-time-fb'])) {
			$this->update_post_meta($_POST['id'], 'sap_schedule_time_fb', strtotime($_POST['sap-schedule-time-fb']));
		}
		//Preapare Data individual blogger time
		if (!empty($_POST['status']) && $_POST['status'] == 2 && !empty($_POST['sap-schedule-time-blogger'])) {
			$this->update_post_meta($_POST['id'], 'sap_schedule_time_blogger', strtotime($_POST['sap-schedule-time-blogger']));
		}
		//Preapare Data twitter blogger time
		if (!empty($_POST['status']) && $_POST['status'] == 2 && !empty($_POST['sap-schedule-time-tw'])) {
			$this->update_post_meta($_POST['id'], 'sap_schedule_time_tw', strtotime($_POST['sap-schedule-time-tw']));
		}
		//Preapare Data linkedin blogger time
		if (!empty($_POST['status']) && $_POST['status'] == 2 && !empty($_POST['sap-schedule-time-li'])) {
			$this->update_post_meta($_POST['id'], 'sap_schedule_time_li', strtotime($_POST['sap-schedule-time-li']));
		}
		//Preapare Data tumblr blogger time
		if (!empty($_POST['status']) && $_POST['status'] == 2 && !empty($_POST['sap-schedule-time-tumblr'])) {
			$this->update_post_meta($_POST['id'], 'sap_schedule_time_tumblr', strtotime($_POST['sap-schedule-time-tumblr']));
		}
		//Preapare Data pinterest blogger time
		if (!empty($_POST['status']) && $_POST['status'] == 2 && !empty($_POST['sap-schedule-time-pin'])) {
			$this->update_post_meta($_POST['id'], 'sap_schedule_time_pin', strtotime($_POST['sap-schedule-time-pin']));
		}
		//Preapare Data gmb blogger time
		if (!empty($_POST['status']) && $_POST['status'] == 2 && !empty($_POST['sap-schedule-time-gmb'])) {
			$this->update_post_meta($_POST['id'], 'sap_schedule_time_gmb', strtotime($_POST['sap-schedule-time-gmb']));
		}
		//Preapare Data instagram blogger time
		if (!empty($_POST['status']) && $_POST['status'] == 2 && !empty($_POST['sap-schedule-time-instagram'])) {
			$this->update_post_meta($_POST['id'], 'sap_schedule_time_instagram', strtotime($_POST['sap-schedule-time-instagram']));
		}
		//Preapare Data reddit blogger time
		if (!empty($_POST['status']) && $_POST['status'] == 2 && !empty($_POST['sap-schedule-time-reddit'])) {
			$this->update_post_meta($_POST['id'], 'sap_schedule_time_reddit', strtotime($_POST['sap-schedule-time-reddit']));
		}

		if (!empty($_POST['networks'])) {
			$this->update_post_meta($_POST['id'], 'sap_networks', $_POST['networks']);
		}
		if (!empty($_POST['sap_caption_words'])) {
			$this->update_post_meta($_POST['id'], 'sap_caption_words', $_POST['sap_caption_words']);
		}
	}

	/**
	 * Handle all Socail posts
	 * @package Social Auto Poster
	 * @since 1.0.0
	 */
	public function sap_manage_wall_social_post($post_id, $scheduled = false, $user_id = '', $individual_post = '')
	{

		// If current user then take accesible networks from session
		$networks = sap_get_users_networks_by_id($user_id);
		$networks = unserialize($networks->networks);
		//Get general options of Facebook;
		$sap_facebook_options = array();



		if (in_array('facebook', $networks)) {
			$sap_facebook_options = $this->settings->get_user_setting('sap_facebook_options', $user_id);
			$sap_facebook_options = !empty($sap_facebook_options) ? $sap_facebook_options : array();
		}

		//Get general options of Twiiter;
		$sap_twitter_options = array();
		if (in_array('twitter', $networks)) {
			$sap_twitter_options = $this->settings->get_user_setting('sap_twitter_options', $user_id);
			$sap_twitter_options = !empty($sap_twitter_options) ? $sap_twitter_options : array();
		}

		//Get general options of linkedin;
		$sap_linkedin_options = array();
		if (in_array('linkedin', $networks)) {
			$sap_linkedin_options = $this->settings->get_user_setting('sap_linkedin_options', $user_id);
			$sap_linkedin_options = !empty($sap_linkedin_options) ? $sap_linkedin_options : array();
		}

		//Get general options of Tumblr;
		$sap_tumblr_options = array();
		if (in_array('tumblr', $networks)) {
			$sap_tumblr_options = $this->settings->get_user_setting('sap_tumblr_options', $user_id);
			$sap_tumblr_options = !empty($sap_tumblr_options) ? $sap_tumblr_options : array();
		}

		//Get general options of Pinterest
		$sap_pinterest_options = array();
		if (in_array('pinterest', $networks)) {
			$sap_pinterest_options = $this->settings->get_user_setting('sap_pinterest_options', $user_id);
			$sap_pinterest_options = !empty($sap_pinterest_options) ? $sap_pinterest_options : array();
		}

		$sap_gmb_options = array();
		if (in_array('gmb', $networks)) {
			$sap_gmb_options = $this->settings->get_user_setting('sap_google_business_options', $user_id);
			$sap_gmb_options = !empty($sap_gmb_options) ? $sap_gmb_options : array();
		}


		$sap_instagram_options = array();
		if (in_array('instagram', $networks)) {
			$sap_instagram_options = $this->settings->get_user_setting('sap_fb_sess_data_for_insta', $user_id);
			$sap_instagram_options = !empty($sap_instagram_options) ? $sap_instagram_options  : array();
		}


		$sap_reddit_options = array();
		if (in_array('reddit', $networks)) {
			$sap_reddit_options = $this->settings->get_user_setting('sap_reddit_sess_data', $user_id);
			$sap_reddit_options = !empty($sap_reddit_options) ? $sap_reddit_options  : array();
		}


		$sap_blogger_options = array();
		if (in_array('blogger', $networks)) {
			$sap_blogger_options = $this->settings->get_user_setting('sap_blogger_sess_data', $user_id);
			$sap_blogger_options = !empty($sap_blogger_options) ? $sap_blogger_options  : array();
		}



		//Check post first time inserting or updating...
		$sap_networks_meta = $this->get_post_meta($post_id, 'sap_networks');
		$schedule_time_fb = $this->get_post_meta($post_id, 'sap_schedule_time_fb');
		$fb_status = $this->get_post_meta($post_id, 'fb_status');

		//Check facebook enable
		if (!empty($sap_networks_meta['facebook'])) {
			// if facebook is enable and sap_fb_status is empty or 2 then go inside if condition
			if ($fb_status != '1' && $fb_status != '3') {
				//Check schedule enable
				if (!empty($_POST['status']) && $_POST['status'] == 2 && $scheduled == 2) {
					$this->update_post_meta($post_id, 'fb_status', 2);
					$this->flash->setFlash($this->sap_common->lang('quick_post_facebook'), 'success');
				} else {

					// if individual schedule time is not set and individual_post  parameter is false then post with global time
					// if individual schedule time is set and individual_post parameter is fb given then post with individual time
					if ((empty($schedule_time_fb) && $individual_post == false) || $individual_post == 'fb') {

						if (!class_exists('SAP_Facebook')) {
							require_once(CLASS_PATH . 'Social' . DS . 'fbConfig.php');
						}

						$facebook = new SAP_Facebook($user_id);

						$fb_result = $facebook->sap_quick_post_on_fb_post($post_id);

						if (!empty($fb_result)) {
							$this->update_post_meta($post_id, 'fb_status', '1');
						} else {
							// '3' for tried to published when schedule time occur but error occur  
							$this->update_post_meta($post_id, 'fb_status', '3');
						}
					}
				}
			}
		}

		$schedule_time_twitter = $this->get_post_meta($post_id, 'sap_schedule_time_tw');
		$twitter_status = $this->get_post_meta($post_id, '_sap_tw_status');
		//Check twitter enable
		if (!empty($sap_networks_meta['twitter'])) {
			if ($twitter_status != '1' && $twitter_status != '3') {
				//Check schedule enable
				if (!empty($_POST['status']) && $_POST['status'] == 2 && $scheduled == 2) {
					$this->update_post_meta($post_id, '_sap_tw_status', 2);
					$this->flash->setFlash($this->sap_common->lang('quick_post_twitter'), 'success');
				} else {
					if ((empty($schedule_time_twitter) && $individual_post == false) || $individual_post == 'twitter') {
						//Upload custom image if exist
						if (!class_exists('SAP_Twitter')) {
							require_once(CLASS_PATH . 'Social' . DS . 'twitterConfig.php');
						}

						$this->twposting = new SAP_Twitter($user_id);
						$tw_result = $this->twposting->sap_quick_post_to_twitter($post_id);

						if (!empty($tw_result)) {
							$this->update_post_meta($post_id, '_sap_tw_status', '1');
						} else {
							// '3' for tried to published when schedule time occur but error occur  
							$this->update_post_meta($post_id, '_sap_tw_status', '3');
						}
					}
				}
			}
		}

		$schedule_time_linkedin = $this->get_post_meta($post_id, 'sap_schedule_time_li');
		$linkedin_status = $this->get_post_meta($post_id, '_sap_li_status');
		if (!empty($sap_networks_meta['linkedin'])) {
			if ($linkedin_status != '1' && $linkedin_status != '3') {
				//Check schedule enable
				if (!empty($_POST['status']) && $_POST['status'] == 2 && $scheduled == 2) {
					$this->update_post_meta($post_id, '_sap_li_status', 2);
					$this->flash->setFlash($this->sap_common->lang('quick_post_li'), 'success');
				} else {
					if ((empty($schedule_time_linkedin) && $individual_post == false) || $individual_post == 'linkedin') {
						if (!class_exists('SAP_Linkedin')) {
							require_once(CLASS_PATH . 'Social' . DS . 'liConfig.php');
						}

						$linkedin = new SAP_Linkedin($user_id);

						$li_result = $linkedin->sap_quick_post_to_linkedin($post_id);

						if (!empty($li_result['success'])) {
							$this->update_post_meta($post_id, '_sap_li_status', '1');
						} else {
							// '3' for tried to published when schedule time occur but error occur  
							$this->update_post_meta($post_id, '_sap_li_status', '3');
						}
					}
				}
			}
		}

		$schedule_time_youtube = $this->get_post_meta($post_id, 'sap_schedule_time_yt');
		$youtube_status = $this->get_post_meta($post_id, '_sap_yt_status');
		if (!empty($sap_networks_meta['youtube'])) {
			if ($youtube_status != '1' && $youtube_status != '3') {
				//Check schedule enable
				if (!empty($_POST['status']) && $_POST['status'] == 2 && $scheduled == 2) {
					$this->update_post_meta($post_id, '_sap_yt_status', 2);
					$this->flash->setFlash($this->sap_common->lang('quick_post_yt'), 'success');
				} else {
					if ((empty($schedule_time_youtube) && $individual_post == false) || $individual_post == 'youtube') {
						if (!class_exists('SAP_Youtube')) {
							require_once(CLASS_PATH . 'Social' . DS . 'youtubeConfig.php');
						}

						$youtube = new SAP_Youtube($user_id);

						$yt_result = $youtube->sap_quick_post_to_youtube($post_id);

						if (!empty($yt_result)) {
							$this->update_post_meta($post_id, '_sap_yt_status', '1');
						} else {
							// '3' for tried to published when schedule time occur but error occur  
							$this->update_post_meta($post_id, '_sap_yt_status', '3');
						}
					}
				}
			}
		}

		$schedule_time_tumblr = $this->get_post_meta($post_id, 'sap_schedule_time_tumblr');
		$tumblr_status = $this->get_post_meta($post_id, '_sap_tumblr_status');
		//Check Tumblr enable
		if (!empty($sap_networks_meta['tumblr'])) {
			if ($tumblr_status != '1' && $tumblr_status != '3') {
				//Check schedule enable
				if (!empty($_POST['status']) && $_POST['status'] == 2 && $scheduled == 2) {
					$this->update_post_meta($post_id, '_sap_tumblr_status', 2);
					$this->flash->setFlash($this->sap_common->lang('quick_post_tumb'), 'success');
				} else {
					if ((empty($schedule_time_tumblr) && $individual_post == false) || $individual_post == 'tumblr') {

						if (!class_exists('SAP_Tumblr')) {
							require_once(CLASS_PATH . 'Social' . DS . 'tumblrConfig.php');
						}
						$tumblr = new SAP_Tumblr($user_id);

						$tm_result = $tumblr->sap_quick_post_to_tumblr($post_id);
						if ((isset($tm_result) && $tm_result['status'] == 'published') || ($tm_result['status'] == 'transcoding' && $tm_result['posting_type'] == 'video')) {
							$this->update_post_meta($post_id, '_sap_tumblr_status', '1');
						} else {
							// '3' for tried to published when schedule time occur but error occur  
							$this->update_post_meta($post_id, '_sap_tumblr_status', '3');
						}
					}
				}
			}
		}

		$schedule_time_gmb = $this->get_post_meta($post_id, 'sap_schedule_time_gmb');
		$gmb_status = $this->get_post_meta($post_id, '_sap_gmb_status');
		if (!empty($sap_networks_meta['gmb'])) {
			if ($gmb_status != '1' && $gmb_status != '3') {
				//Check schedule enable
				if (!empty($_POST['status']) && $_POST['status'] == 2 && $scheduled == 2) {

					$this->update_post_meta($post_id, '_sap_gmb_status', 2);
					$this->flash->setFlash($this->sap_common->lang('quick_post_gmb'), 'success');
				} else {
					if ((empty($schedule_time_gmb) && $individual_post == false) || $individual_post == 'gmb') {
						if (!class_exists('SAP_Gmb')) {
							require_once(CLASS_PATH . 'Social' . DS . 'gmbConfig.php');
						}
						$google_business = new SAP_Gmb($user_id);

						$gmb_result = $google_business->sap_send_quick_post_to_gmb($post_id);

						if (isset($gmb_result) && $gmb_result['success'] == '1') {

							$this->update_post_meta($post_id, '_sap_gmb_status', '1');
						} else {
							// '3' for tried to published when schedule time occur but error occur  
							$this->update_post_meta($post_id, '_sap_gmb_status', '3');
						}
					}
				}
			}
		}

		$schedule_time_instagram = $this->get_post_meta($post_id, 'sap_schedule_time_instagram');
		$instagram_status = $this->get_post_meta($post_id, '_sap_instagram_status');
		//Instagram Business Posting - Logic
		if (!empty($sap_networks_meta['instagram'])) {
			if ($instagram_status != '1' && $instagram_status != '3') {
				//Check schedule enable
				if (!empty($_POST['status']) && $_POST['status'] == 2 && $scheduled == 2) {

					$this->update_post_meta($post_id, '_sap_instagram_status', 2);
					$this->flash->setFlash($this->sap_common->lang('quick_post_instagram'), 'success');
				} else {
					if ((empty($schedule_time_instagram) && $individual_post == false) || $individual_post == 'instagram') {
						if (!class_exists('SAP_Instagram')) {
							require_once(CLASS_PATH . 'Social' . DS . 'instaConfig.php');
						}
						$instagram = new SAP_Instagram($user_id);

						$instagram_result = $instagram->sap_quick_post_on_insta_post($post_id);
						if (isset($instagram_result) && $instagram_result) {

							$this->update_post_meta($post_id, '_sap_instagram_status', '1');
						} else {
							// '3' for tried to published when schedule time occur but error occur  
							$this->update_post_meta($post_id, '_sap_instagram_status', '3');
						}
					}
				}
			}
		}

		$schedule_time_reddit = $this->get_post_meta($post_id, 'sap_schedule_time_reddit');
		$reddit_status = $this->get_post_meta($post_id, '_sap_reddit_status');
		//Reddit Quick Posting - Logic
		if (!empty($sap_networks_meta['reddit'])) {
			if ($reddit_status != '1' && $reddit_status != '3') {
				//Check schedule enable
				if (!empty($_POST['status']) && $_POST['status'] == 2 && $scheduled == 2) {

					$this->update_post_meta($post_id, '_sap_reddit_status', 2);
					$this->flash->setFlash($this->sap_common->lang('quick_post_reddit'), 'success');
				} else {
					if ((empty($schedule_time_reddit) && $individual_post == false) || $individual_post == 'reddit') {

						if (!class_exists('SAP_Reddit')) {
							require_once(CLASS_PATH . 'Social' . DS . 'redditConfig.php');
						}

						$reddit = new SAP_Reddit($user_id);

						$reddit_result = $reddit->sap_quick_post_on_reddit_post($post_id);


						if (isset($reddit_result) && $reddit_result) {

							$this->update_post_meta($post_id, '_sap_reddit_status', '1');
						} else {
							// '3' for tried to published when schedule time occur but error occur  
							$this->update_post_meta($post_id, '_sap_reddit_status', '3');
						}
					}
				}
			}
		}

		$schedule_time_blogger = $this->get_post_meta($post_id, 'sap_schedule_time_blogger');
		$_sap_blogger_status = $this->get_post_meta($post_id, '_sap_blogger_status');

		//Blogger Quick Posting - Logic
		if (!empty($sap_networks_meta['blogger'])) {
			if ($_sap_blogger_status != '1' && $_sap_blogger_status != '3') {
				//Check schedule enable
				if (!empty($_POST['status']) && $_POST['status'] == 2 && $scheduled == 2) {

					$this->update_post_meta($post_id, '_sap_blogger_status', 2);
					$this->flash->setFlash($this->sap_common->lang('quick_post_blogger'), 'success');
				} else {
					if ((empty($schedule_time_blogger) && $individual_post == false) || $individual_post == 'blogger') {
						if (!class_exists('SAP_Blogger')) {
							require_once(CLASS_PATH . 'Social' . DS . 'bloggerConfig.php');
						}

						$blogger = new SAP_Blogger($user_id);
						$blogger_result = $blogger->sap_quick_post_on_blogger_post($post_id);

						if (!empty($blogger_result)) {
							$this->update_post_meta($post_id, '_sap_blogger_status', '1');
						} else {
							// '3' for tried to published when schedule time occur but error occur  
							$this->update_post_meta($post_id, '_sap_blogger_status', '3');
						}
					}
				}
			}
		}

		$schedule_time_pinterest = $this->get_post_meta($post_id, 'sap_schedule_time_pin');
		$pinterest_status = $this->get_post_meta($post_id, 'pin_status');
		//Check pinterest enable
		if (!empty($sap_networks_meta['pinterest'])) {
			if ($pinterest_status != '1' && $pinterest_status != '3') {
				//Check schedule enable
				if (!empty($_POST['status']) && $_POST['status'] == 2 && $scheduled == 2) {
					$this->update_post_meta($post_id, 'pin_status', 2);
					$this->flash->setFlash($this->sap_common->lang('quick_post_pit'), 'success');
				} else {
					if ((empty($schedule_time_pinterest) && $individual_post == false) || $individual_post == 'pinterest') {

						if (!class_exists('SAP_Pinterest')) {
							require_once(CLASS_PATH . 'Social' . DS . 'pinConfig.php');
						}

						$pinterest = new SAP_Pinterest($user_id);

						$pin_result = $pinterest->sap_quick_post_on_pin_post($post_id);

						if (!empty($pin_result)) {
							$this->update_post_meta($post_id, 'pin_status', '1');
							if (empty($scheduled)) {
								$this->flash->setFlash($this->sap_common->lang('quick_post_publish_pit'), 'success');
							}
						} else {
							// '3' for tried to published when schedule time occur but error occur  
							$this->update_post_meta($post_id, 'pin_status', '3');
						}
					}
				}
			}
		}
	}

	/**
	 * Short the Content As Per Character Limit
	 * 
	 * Handles to return short content as per character 
	 * limit
	 * 
	 * @package Social Auto Poster
	 * */
	public function sap_limit_character($content, $charlength = 140)
	{

		$excerpt = '';
		$charlength++;

		//check content length is greater then character length
		if (strlen($content) > $charlength) {

			$subex = substr($content, 0, $charlength - 5);
			$exwords = explode(' ', $subex);
			$excut = - (strlen($exwords[count($exwords) - 1]));

			if ($excut < 0) {
				$excerpt = substr($subex, 0, $excut);
			} else {
				$excerpt = $subex;
			}
		} else {
			$excerpt = $content;
		}

		//return short content
		return $excerpt;
	}


	/**
	 * get all supported Social Networks
	 * 
	 * @package Social Auto Poster
	 * @since 1.0.0
	 */
	public function sap_get_supported_networks()
	{
		return array(
			'fb' => 'facebook',
			'tw' => 'twitter',
			'li' => 'linkedin',
			'tumblr' => 'tumblr',
			'pin' => 'pinterest',
			'gmb' => 'google my business',
			'instagram' => 'instagram',
			'reddit' => 'reddit',
			'blogger' => 'blogger'
		);
	}

	/**
	 * Ajax call for generate caption
	 * 
	 * @package Social Auto Poster
	 * @since 1.0.0
	 */
	public function sap_generate_caption()
	{

		$number_of_words = $_POST['number_of_words'];
		$link = $_POST['sap_ai_content_link'];

		$prompt = 'Read this article and Give me an exciting ' . $number_of_words . ' word social media caption for this article:' . $link;
		$api_key = $this->settings->get_options('sap_chatgpt_api_key');
		$open_ai = new OpenAi($api_key);
		$result = $open_ai->chatcompletions(array(
			'model' => 'gpt-3.5-turbo',
			'messages' => array(array('role' => 'user', 'content' => $prompt))
		));

		echo $result;
		exit();
	}
}
