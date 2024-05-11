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
class SAP_Shedule_Posts
{

	//Set Database variable
	private $db;
	//Set table name
	private $quick_post_table_name;
	private $quick_post_meta_table_name;
	private $post_table_name;
	private $post_meta_table_name;
	public $flash;
	public $common;

	public function __construct()
	{
		global $sap_db_connect;
		$this->db = $sap_db_connect;

		//Assign table name
		$this->quick_post_table_name = 'sap_quick_posts';
		$this->quick_post_meta_table_name = 'sap_quick_postmeta';

		$this->post_table_name = 'sap_posts';
		$this->post_meta_table_name = 'sap_postmeta';
		$this->settings = new SAP_Settings(); // to fix timezone issue
	}

	/**
	 * Get schedule Posts
	 * 
	 * Handels list of schedule Posts
	 * 
	 * @package Social Auto Poster
	 * @since 1.0.0
	 */
	public function get_sheduled_post_ids()
	{

		$result = array();

		try {
			//Query for get schedule post only
			$query = "SELECT DISTINCT Qp.post_id, Qp.user_id FROM " . $this->quick_post_table_name . " Qp JOIN " . $this->quick_post_meta_table_name . " Qpm ON Qp.post_id = Qpm.post_id WHERE Qp.status='2'";

			$result['quick_posts'] = $this->db->get_results($query, true);
		} catch (Exception $e) {
			return $e->getMessage();
		}

		try {
			$query = "SELECT DISTINCT P.post_id, P.user_id FROM " . $this->post_table_name . " P JOIN " . $this->post_meta_table_name . " Pm ON P.post_id = Pm.post_id WHERE P.status='2'";
			$result['posts'] = $this->db->get_results($query, true);
		} catch (Exception $e) {
			return $e->getMessage();
		}

		//Return result
		return $result;
	}

	/**
	 *
	 * check time and published post 
	 * 
	 * Set Post Status 1 Punlished if all enable social media are post
	 * 
	 * @package Social Auto Poster
	 * @since 1.0.0
	 */
	public function sap_check_time_and_published_post($post_id, $user_id, $is_quick_post)
	{
		$sap_fun = '';
		if ($is_quick_post) {
			$sap_fun = $this->sap_quick_post;
			$status_name_meta = array(
				'facebook' => 'fb_status',
				'blogger' => '_sap_blogger_status',
				'twitter' => '_sap_tw_status',
				'linkedin' => '_sap_li_status',
				'tumblr' => '_sap_tumblr_status',
				'pinterest' => 'pin_status',
				'gmb' => '_sap_gmb_status',
				'instagram' => '_sap_instagram_status',
				'reddit' => '_sap_reddit_status'
			);
		} else {
			$sap_fun = $this->sap_post;
			$status_name_meta = array(
				'facebook' => '_sap_fb_status',
				'blogger' => '_sap_blogger_status',
				'twitter' => '_sap_tw_status',
				'linkedin' => '_sap_li_status',
				'tumblr' => '_sap_tumblr_status',
				'pinterest' => '_sap_pin_status',
				'gmb' => '_sap_gmb_status',
				'instagram' => '_sap_instagram_status',
				'reddit' => '_sap_reddit_status'
			);
		}

		// get all schedules times
		$schedule_time = $sap_fun->get_post_meta($post_id, 'sap_schedule_time');
		$schedule_time_fb = $sap_fun->get_post_meta($post_id, 'sap_schedule_time_fb');
		$schedule_time_blogger = $sap_fun->get_post_meta($post_id, 'sap_schedule_time_blogger');
		$sap_schedule_time_twitter = $sap_fun->get_post_meta($post_id, 'sap_schedule_time_tw');
		$sap_schedule_time_linkedin = $sap_fun->get_post_meta($post_id, 'sap_schedule_time_li');
		$sap_schedule_time_tumblr = $sap_fun->get_post_meta($post_id, 'sap_schedule_time_tumblr');
		$sap_schedule_time_pinterest = $sap_fun->get_post_meta($post_id, 'sap_schedule_time_pin');
		$sap_schedule_time_gmb = $sap_fun->get_post_meta($post_id, 'sap_schedule_time_gmb');
		$sap_schedule_time_instagram = $sap_fun->get_post_meta($post_id, 'sap_schedule_time_instagram');
		$sap_schedule_time_reddit = $sap_fun->get_post_meta($post_id, 'sap_schedule_time_reddit');

		//check global time
		if (!empty($schedule_time) && $schedule_time < time()) { // check post schedule time based on user time zone

			//Manage wall posting of Socials
			$sap_fun->sap_manage_wall_social_post($post_id, 1, $user_id, false);
		}

		//Check facebook individual time
		if (!empty($schedule_time_fb) && $schedule_time_fb < time()) { // check post schedule time based on user time zone

			//Manage wall posting of Socials
			$sap_fun->sap_manage_wall_social_post($post_id, 1, $user_id, 'fb');
		}
		//check blogger individual time
		if (!empty($schedule_time_blogger) && $schedule_time_blogger < time()) { // check post schedule time based on user time zone

			//Manage wall posting of Socials
			$sap_fun->sap_manage_wall_social_post($post_id, 1, $user_id, 'blogger');
		}
		//check twitter individual time
		if (!empty($sap_schedule_time_twitter) && $sap_schedule_time_twitter < time()) { // check post schedule time based on user time zone

			//Manage wall posting of Socials
			$sap_fun->sap_manage_wall_social_post($post_id, 1, $user_id, 'twitter');
		}
		//check linkedin individual time
		if (!empty($sap_schedule_time_linkedin) && $sap_schedule_time_linkedin < time()) { // check post schedule time based on user time zone

			//Manage wall posting of Socials
			$sap_fun->sap_manage_wall_social_post($post_id, 1, $user_id, 'linkedin');
		}
		//check tumblr individual time
		if (!empty($sap_schedule_time_tumblr) && $sap_schedule_time_tumblr < time()) { // check post schedule time based on user time zone

			//Manage wall posting of Socials
			$sap_fun->sap_manage_wall_social_post($post_id, 1, $user_id, 'tumblr');
		}
		//check pinterest individual time
		if (!empty($sap_schedule_time_pinterest) && $sap_schedule_time_pinterest < time()) { // check post schedule time based on user time zone

			//Manage wall posting of Socials
			$sap_fun->sap_manage_wall_social_post($post_id, 1, $user_id, 'pinterest');
		}
		//check gmb individual time
		if (!empty($sap_schedule_time_gmb) && $sap_schedule_time_gmb < time()) { // check post schedule time based on user time zone

			//Manage wall posting of Socials
			$sap_fun->sap_manage_wall_social_post($post_id, 1, $user_id, 'gmb');
		}
		//check instagram individual time
		if (!empty($sap_schedule_time_instagram) && $sap_schedule_time_instagram < time()) { // check post schedule time based on user time zone

			//Manage wall posting of Socials
			$sap_fun->sap_manage_wall_social_post($post_id, 1, $user_id, 'instagram');
		}
		//check reddit individual time
		if (!empty($sap_schedule_time_reddit) && $sap_schedule_time_reddit < time()) { // check post schedule time based on user time zone

			//Manage wall posting of Socials
			$sap_fun->sap_manage_wall_social_post($post_id, 1, $user_id, 'reddit');
		}

		//get all Status
		$fb_status = $sap_fun->get_post_meta($post_id, $status_name_meta['facebook']);
		if (!empty($fb_status)) {
			$published['fb_status'] = $fb_status;
		}
		$blogger_status = $sap_fun->get_post_meta($post_id, $status_name_meta['blogger']);

		if (!empty($blogger_status)) {
			$published['blogger_status'] = $blogger_status;
		}
		$twitter_status = $sap_fun->get_post_meta($post_id, $status_name_meta['twitter']);
		if (!empty($twitter_status)) {
			$published['twitter_status'] = $twitter_status;
		}
		$linkedin_status = $sap_fun->get_post_meta($post_id, $status_name_meta['linkedin']);
		if (!empty($linkedin_status)) {
			$published['linkedin_status'] = $linkedin_status;
		}
		$tumblr_status = $sap_fun->get_post_meta($post_id, $status_name_meta['tumblr']);
		if (!empty($tumblr_status)) {
			$published['tumblr_status'] = $tumblr_status;
		}
		$gmb_status = $sap_fun->get_post_meta($post_id, $status_name_meta['gmb']);
		if (!empty($gmb_status)) {
			$published['gmb_status'] = $gmb_status;
		}
		$instagram_status = $sap_fun->get_post_meta($post_id, $status_name_meta['instagram']);
		if (!empty($instagram_status)) {
			$published['instagram_status'] = $instagram_status;
		}
		$reddit_status = $sap_fun->get_post_meta($post_id, $status_name_meta['reddit']);
		if (!empty($reddit_status)) {
			$published['reddit_status'] = $reddit_status;
		}
		$pin_status = $sap_fun->get_post_meta($post_id, $status_name_meta['pinterest']);
		if (!empty($pin_status)) {
			$published['pin_status'] = $pin_status;
		}

		$is_all_published = 1;
		//published only enable social media status
		foreach ($published as $network => $n_status) {
			if ($n_status == '2') {
				$is_all_published = 0;
				break;
			}
		}
		if ($is_all_published) {

			//Update Status after posting
			$status = array('status' => 1);
			$where = array('post_id' => $post_id);
			if ($is_quick_post) {
				$sap_fun->update_post($status, $where);
			} else {
				$sap_fun->update_posts($status, $where);
			}
		}
	}

	/**
	 * Handle Schedule Posting
	 * 
	 * @package Social Auto Poster
	 * @since 1.0.0
	 */
	public function handle_sheduled_posts()
	{

		$final_shedule_post = array();


		require_once(CLASS_PATH . 'Posts.php');


		require_once(CLASS_PATH . 'Quick_Posts.php');

		// Run cron every week and clear debug log
		if (!class_exists('SAP_Debug')) {

			require_once(CLASS_PATH . 'Debug.php');
			require_once(CLASS_PATH . 'Settings.php');

			$common = new Common();
			$debug_log = new SAP_Debug();
			$settings_object = new SAP_Settings();

			$schedule_debug_clear = $settings_object->get_options('schedule_debug_clear');
			$day	= date('w'); // return 0 to 6, 0 for Sunday, 6 for Saturday

			if (!empty($schedule_debug_clear) && $day == '1') {
				$today	= date('Y-m-d');
				if ($today != $schedule_debug_clear && $today < $schedule_debug_clear) {
					$debug_log->schedule_cleaner();
				}
			}
		}

		$this->sap_post 	  = new SAP_Posts();
		$this->sap_quick_post = new SAP_Quick_Posts();

		$sheduled_post_ids    = $this->get_sheduled_post_ids();

		//schedule post for Quick posting
		if (!empty($sheduled_post_ids['quick_posts'])) {

			foreach ($sheduled_post_ids['quick_posts'] as $key => $value) {

				$user_options = $this->settings->get_user_setting('sap_general_options', $value['user_id']);

				$timezone = (!empty($user_options['timezone'])) ? $user_options['timezone'] : ''; // user timezone

				//Update time zone based on user setting
				if (!empty($timezone)) { // set default timezone
					date_default_timezone_set($timezone);
				}
				// pass last parameter true for quick post 
				$this->sap_check_time_and_published_post($value['post_id'], $value['user_id'], true);
			}
		}
		//schedule post for content posting
		if (!empty($sheduled_post_ids['posts'])) {

			foreach ($sheduled_post_ids['posts'] as $value) {

				$user_options = $this->settings->get_user_setting('sap_general_options', $value['user_id']);

				$timezone = (!empty($user_options['timezone'])) ? $user_options['timezone'] : ''; // user timezone

				//Update time zone based on user setting
				if (!empty($timezone)) { // set default timezone
					date_default_timezone_set($timezone);
				}

				// pass last parameter false for multi post 
				$this->sap_check_time_and_published_post($value['post_id'], $value['user_id'], false);
			}
		}
	}


	/**
	 * Get all supported Networks
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
			'tb' => 'tumblr',
			'reddit' => 'reddit',
			'blogger' => 'blogger'
		);
	}
}
