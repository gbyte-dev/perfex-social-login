<?php

/* Check the absolute path to the Social Auto Poster directory. */
if ( !defined( 'SAP_APP_PATH' ) ) {
    // If SAP_APP_PATH constant is not defined, perform some action, show an error, or exit the script
    // Or exit the script if required
    exit();
}

/**
 * Tumblr posting
 *
 * Handles all the functions to post on tumblr
 * 
 * @package Social auto poster
 * @since 1.0.0
 */
class SAP_Tumblr {

    private $db, $common, $flash, $settings, $posts, $quick_posts, $logs, $sap_common;

    public function __construct($user_id='') {
        global $sap_common,$sap_db_connect;
        //Check Settings class not exit then call class
        if (!class_exists('SAP_Settings')) {
            include_once( CLASS_PATH . 'Settings.php' );
        }

        //Check Settings class not exit then call class
        if (!class_exists('SAP_Posts')) {
            include_once( CLASS_PATH . 'Posts.php' );
        }

        if (!class_exists('SAP_Quick_Posts')) {
            require_once( CLASS_PATH . 'Quick_Posts.php' );
        }

        $this->posts = new SAP_Posts();
        $this->quick_posts = new SAP_Quick_Posts();

        //Set Database
        $this->db = $sap_db_connect;
        $this->settings = new SAP_Settings();
        $this->flash = new Flash();
        $this->common = new Common();
        $this->logs = new SAP_Logs();
        $this->sap_common = $sap_common;

        //initialize some tumblr data
        $this->sap_tumblr_initialize($user_id);
    }

    /**
     * Include Tumblr Class
     * 
     * Handles to load tumblr class
     * 
     * @package Social Auto Poster
     * @since 1.0.0
     */
    public function sap_load_tumblr($app_id = false, $user_id = '') {

        // Getting tumblr apps
        $tb_apps = $this->sap_fetch_tumblr_apps($user_id);
        if (empty($app_id)) {

            $tb_apps_keys = array_keys($tb_apps);
            $app_id = reset($tb_apps_keys);
        }

        //tumblr declaration
        if (!empty($app_id) && !empty($tb_apps[$app_id])) {

            if ( !class_exists( 'Client' ) ) {
                require_once( LIB_PATH . 'Social' . DS . 'Tumblr' . DS . 'vendor' . DS . 'autoload.php' );
            }

            return true;
        } else {

            return false;
        }
    }

    /**
     * Get Self URL
     * 
     * Handles to return current URL
     * 
     * @package Social Auto Poster
     * @since 1.0.0
     */
    public function sap_self_url() {

        $s = empty($_SERVER["HTTPS"]) ? '' : ( ($_SERVER["HTTPS"] == "on") ? "s" : "" );
        $str1 = strtolower($_SERVER["SERVER_PROTOCOL"]);
        $str2 = "/";
        $protocol = substr($str1, 0, strpos($str1, $str2)) . $s;
        $port = ($_SERVER["SERVER_PORT"] == "80") ? "" : (":" . $_SERVER["SERVER_PORT"]);
        return $protocol . "://" . $_SERVER['SERVER_NAME'] . $port . $_SERVER['REQUEST_URI'];
    }

    /**
     * Get Tumblr Login URL
     * 
     * Handles to Return Tumblr URL
     * 
     * @package Social Auto Poster
     * @since 1.0.0
     * 
     */
    public function sap_get_login_url() {

        return '?authtumb=1&sap=tumblr';
    }

    /**
     * Get Tumblr Grant Extend Permission
     * 
     * Handles to Return Tumblr URL and peermission
     * 
     * @package Social Auto Poster
     * @since 1.0.0
     * 
     */
    public function sap_grant_extended_permissions($user_id='') {

        if (!(PHP_VERSION_ID >= 80100)) {
            $issues = 'Tumblr Posting require a PHP version ">= 8.1.0". You are running ' . PHP_VERSION . '.';
            $this->flash->setFlash($issues, 'error');
            return false;
        }

        //load tumblr class
        $tumblr_load = $this->sap_load_tumblr(false, $user_id);

        $sap_tumblr_options = $this->settings->get_user_setting('sap_tumblr_options',$user_id);

        //check tumblr loaded or not
        if (!$tumblr_load)
            return false;

        $pageurl = $this->sap_self_url();
        $tumblr_callback_url = $pageurl . '&auth=tumblr_auth&authtumb=0';
        $app_id = isset($_GET['app-id']) ? $_GET['app-id'] : '';
        if (!empty($app_id)) {
            $tumblr_key_data = array();

            foreach ($sap_tumblr_options['tumblr_keys'] as $key => $tumblr_option) {
                if ($tumblr_option['tumblr_consumer_key'] == $app_id) {
                    $tumblr_key_data[] = $tumblr_option;
                    break;
                }
            }

            $tumblr_oauth = new Tumblr\API\Client($tumblr_key_data[0]['tumblr_consumer_key'], $tumblr_key_data[0]['tumblr_consumer_secret']);
                
            $requestHandler = $tumblr_oauth->getRequestHandler();
            $requestHandler->setBaseUrl('https://www.tumblr.com/');
            $sap_tumb_request_token = $requestHandler->request('POST', 'oauth/request_token', array(
                'oauth_callback' => $tumblr_callback_url
            ));

            $result = (string) $sap_tumb_request_token->body;
            parse_str($result, $sap_tumb_request_token);

            if ( empty( $sap_tumb_request_token ) ) {
                $this->sap_common->sap_script_logs('Tumblr Request token not generated', $user_id);
                return false;
            }

            setcookie( 'sap_tumb_request_token', json_encode($sap_tumb_request_token) );
            //record logs for token is set properly to session
            $this->sap_common->sap_script_logs('Tumblr Request token assign to the session', $user_id);
            
            $_SESSION['sap_tumblr']['request_token'] = $_SESSION['token'] = $sap_tumb_request_token;

            if( $sap_tumb_request_token['oauth_callback_confirmed'] ) {

                //record logs for token is generated successfully
                $this->sap_common->sap_script_logs('Tumblr Oauth token successfully generated', $user_id);
                $url = $tumblr_oauth->getAuthorizeURL( $sap_tumb_request_token['oauth_token'] );

                header('Location:' . $url);
                exit;
            }

        } else {
            $this->flash->setFlash('Tumblr Grant extended permissions could not be set. Consumer key is missing.', 'error');
            $this->sap_common->sap_script_logs('Tumblr Grant extended permissions could not be set. Consumer key is missing.', $user_id);
            header("Location:" . SAP_SITE_URL . "/settings/");
            exit;
        }
    }

    /**
     * Allow Premssion and connect
     * 
     * Handles to Return Tumblr URL and peermission
     * 
     * @package Social Auto Poster
     * @since 1.0.0
     * 
     */
    public function sap_tumblr_connect_data_store($user_id='') {

        if (!(PHP_VERSION_ID >= 80100)) {
            $issues = 'Tumblr Posting require a PHP version ">= 8.1.0". You are running ' . PHP_VERSION . '.';
            $this->flash->setFlash($issues, 'error');
            return false;
        }

        //check tumblr loaded or not        
        $sap_tumblr_options = $this->settings->get_user_setting('sap_tumblr_options',$user_id);
        $tumblr_settings = isset($sap_tumblr_options['tumblr_keys']) ? $sap_tumblr_options['tumblr_keys'] : '';

        if ($_GET['sap'] == 'tumblr' && $_GET['auth'] == "tumblr_auth" && isset($_GET['app-id'])) {

            $tb_app_id = $_GET['app-id'];
            $tb_app_secret = '';

            foreach ($tumblr_settings as $tumblr_key => $tumblr_value) {

                if ($tb_app_id == $tumblr_value['tumblr_consumer_key']) {

                    $tb_app_secret = $tumblr_value['tumblr_consumer_secret'];
                    break;
                }
            }

            $tumblr = $this->sap_load_tumblr($tb_app_id,$user_id);

            //check tumblr loaded or not
            if (!$tumblr) return false;

            //record logs when user is connected with tumblr
            $this->sap_common->sap_script_logs('Tumblr: User is connected successfully', $user_id);

            $sap_tumb_store_token = json_decode( $_COOKIE['sap_tumb_request_token'] );

            $tumblr_oauth = new Tumblr\API\Client($tb_app_id, $tb_app_secret, $sap_tumb_store_token->oauth_token, $sap_tumb_store_token->oauth_token_secret);
            $requestHandler = $tumblr_oauth->getRequestHandler();  
            $requestHandler->setBaseUrl('https://www.tumblr.com/');
            $verifier = trim($_REQUEST['oauth_verifier']);

            $spa_tumb_access_token = $requestHandler->request('POST', 'oauth/access_token', array('oauth_verifier' => $verifier));

            $this->sap_common->sap_script_logs('Tumblr Grant Extended Permission', $user_id);

            $out = (string) $spa_tumb_access_token->body;
            $data = array();
            parse_str($out, $spa_tumb_access_token);
            $this->sap_common->sap_script_logs('Tumblr Get Parameters Set Properly.', $user_id);

            $_SESSION['token'] = $spa_tumb_access_token;

            $_SESSION['sap_tumblr']['oauth_token'] = isset($spa_tumb_access_token['oauth_token']) ? $spa_tumb_access_token['oauth_token'] : $_SESSION['sap_tumblr']['request_token']['oauth_token'];
            $_SESSION['sap_tumblr']['oauth_token_secret'] = isset($spa_tumb_access_token['oauth_token_secret']) ? $spa_tumb_access_token['oauth_token_secret'] : $_SESSION['sap_tumblr']['request_token']['oauth_token_secret'];


            $tumblr_oauth = new Tumblr\API\Client($tb_app_id, $tb_app_secret, $_SESSION['sap_tumblr']['oauth_token'], $_SESSION['sap_tumblr']['oauth_token_secret']);
            $tumblr_account_info = $tumblr_oauth->getUserInfo();

            $tumblr_account_url = ( isset($tumblr_account_info->user->blogs[0]->url) && !empty($tumblr_account_info->user->blogs[0]->url) ) ? $tumblr_account_info->user->blogs[0]->url : '';

            $_SESSION['sap_tumblr']['user_id'] = isset($_SESSION['sap_tumblr']['user_id']) ? $_SESSION['sap_tumblr']['user_id'] : $tumblr_account_info->user->name;

            $_SESSION['sap_tumblr']['cache'] = isset($_SESSION['sap_tumblr']['cache']) ? $_SESSION['sap_tumblr']['cache'] : $tumblr_account_info->user;
            
            $this->sap_common->sap_script_logs('User authentication data assign to session successfully', $user_id );

            // start code to manage session from database 
            $sap_tumblr_sess_data = $this->settings->get_user_setting('sap_tumblr_sess_data',$user_id);

            if (empty($sap_tumblr_sess_data)) {

                $sap_tumblr_sess_data = array();
                $sess_data = !empty($_SESSION['sap_tumblr']) ? $_SESSION['sap_tumblr'] : array();

                $sap_tumblr_sess_data[$tb_app_id] = array(
                    'sap_tb_user_id' => $tumblr_account_info->user->name,
                    'sap_tb_user_cache' => $tumblr_account_info->user,
                    'sap_tb_oauth' => $_SESSION['token']['oauth_token'],
                    'sap_tb_account_url' => $tumblr_account_url,
                    'sap_tb_consumer_secret' => $tb_app_secret,
                    'sap_tb_oauth_token' => $_SESSION['sap_tumblr']['oauth_token'],
                    'sap_tb_outh_toke_secret' => $_SESSION['sap_tumblr']['oauth_token_secret']
                );

                $this->settings->update_user_setting('sap_tumblr_sess_data', $sap_tumblr_sess_data);

                $this->sap_common->sap_script_logs('Tumblr Session Data Updated to Options.', $user_id);
            }
            if (!isset($sap_tumblr_sess_data[$tb_app_id])) {

                $sess_data = array(
                    'sap_tb_user_id' => $tumblr_account_info->user->name,
                    'sap_tb_user_cache' => $tumblr_account_info->user,
                    'sap_tb_oauth' => $_SESSION['token']['oauth_token'],
                    'sap_tb_account_url' => $tumblr_account_url,
                    'sap_tb_consumer_secret' => $tb_app_secret,
                    'sap_tb_oauth_token' => $_SESSION['sap_tumblr']['oauth_token'],
                    'sap_tb_outh_toke_secret' => $_SESSION['sap_tumblr']['oauth_token_secret']
                );
                $sap_tumblr_sess_data[$tb_app_id] = $sess_data;
                $orignal_result = $this->settings->get_user_setting('sap_tumblr_sess_data', $user_id);
                if (!empty($orignal_result)) {

                    $final_data = array_merge($orignal_result, $sap_tumblr_sess_data);
                    $this->settings->update_user_setting('sap_tumblr_sess_data', $final_data);
                    $this->sap_common->sap_script_logs('Tumblr Session Data Updated to Options.', $user_id);
                } else {

                    $this->settings->update_user_setting('sap_tumblr_sess_data', $sap_tumblr_sess_data);
                    $this->sap_common->sap_script_logs('Tumblr Session Data Updated to Options.', $user_id);
                }
            }

            if (isset($_SESSION['sap_tumblr'])) {
                unset($_SESSION['sap_tumblr']);
            }

            if (isset($_SESSION['token'])) {
                unset($_SESSION['token']);
            }

            if (isset($_COOKIE['sap_tumb_request_token'])) {
                unset($_COOKIE['sap_tumb_request_token']);
            }
        }


        // end code to manage session from database
        $redirect_url = SAP_SITE_URL . '/settings/';
        $_SESSION['sap_active_tab'] = 'tumblr';
        header('Location:' . $redirect_url);
        exit;
    }

    /**
     * Multiple Accounts - Tumblr for fetching apps
     *
     * Resetting the Tumblr sessions when the admin clicks on
     * its link within the settings page.
     *
     * @package Social Auto Poster
     * @since 1.0.0
     */
    public function sap_fetch_tumblr_apps($user_id='') {

        $sap_tumblr_options = $this->settings->get_user_setting('sap_tumblr_options',$user_id);
        $tb_apps = array();
        $tb_keys = isset( $sap_tumblr_options['tumblr_keys'] ) ? $sap_tumblr_options['tumblr_keys'] : '';

        if ( !empty( $tb_keys ) ) {

            foreach ( $tb_keys as $tb_key_id => $tb_key_data ) {

                if (!empty($tb_key_data['tumblr_consumer_key']) && !empty($tb_key_data['tumblr_consumer_secret'])) {
                    $tb_apps[$tb_key_data['tumblr_consumer_key']] = $tb_key_data['tumblr_consumer_secret'];
                }
            } // End of for each
        } // End of main if

        return $tb_apps;
    }

    /**
     * Reset Sessions
     *
     * Resetting the Tumblr sessions when the admin clicks on
     * its link within the settings page.
     *
     * @package Social Auto Poster
     * @since 1.0.0
     */
    public function sap_reset_session() {

        if (!empty($_GET['tumblr_reset_user']) && $_GET['tumblr_reset_user'] == 1) {

            $app_id = $_GET['app-id'];
            $tumblr_session_data = $this->settings->get_user_setting('sap_tumblr_sess_data');
            if (isset($tumblr_session_data[$app_id])) {

                unset($tumblr_session_data[$app_id]);
                $this->settings->update_user_setting('sap_tumblr_sess_data', $tumblr_session_data);
                $this->sap_common->sap_script_logs('Tumblr ' . $app_id . ' Account Reset Successfully.', $user_id);
            }
        }

        $_SESSION['sap_active_tab'] = 'tumblr';
        header("Location:" . SAP_SITE_URL . "/settings/");
        exit;
    }

    /**
     * Initializes Some Data to session
     * 
     * @package Social Auto Poster
     * @since 1.0.0
     * 
     */
    public function sap_tumblr_initialize($user_id='') {

        $sap_tumblr_options = $this->settings->get_user_setting('sap_tumblr_options',$user_id);

        //check tumblr application id and application secret is not empty or not
        if (!empty($sap_tumblr_options['tumblr_consumer_key']) && !empty($sap_tumblr_options['tumblr_consumer_secret'])) {

            $this->sap_common->sap_script_logs('Tumblr Grant Extended Permission', $user_id);

            $this->sap_common->sap_script_logs('Tumblr Get Parameters Set Properly.', $user_id);

            // start code to manage session from database 
            $sap_tumblr_sess_data = $this->settings->get_user_setting('sap_tumblr_sess_data',$user_id);
            if (!empty($sap_tumblr_sess_data) && !isset($_SESSION['sap_tumblr']['user_id'])) {
                $_SESSION['sap_tumblr'] = $sap_tumblr_sess_data;
            }
        }
    }

    /**
     * Tumblr post
     * Handle post on tumblr
     * @package Social Auto Poster
     * @since 1.0.0
     * 
     */
    public function sap_post_to_tumblr($post_id) {


        $post = $this->posts->get_post($post_id, true);
        $user_id = isset( $post->user_id ) ? $post->user_id : '';
        $tumblr = $this->sap_load_tumblr(false, $user_id);

        //check tumblr loaded or not
        if ( !$tumblr ) {
            return false;
        }

        $sap_tumblr_options = $this->settings->get_user_setting('sap_tumblr_options', $user_id);
        $tumblr_sess_data = $this->settings->get_user_setting('sap_tumblr_sess_data', $user_id);

        // General setting
        $sap_general_options = $this->settings->get_user_setting('sap_general_options',$user_id);

        $link_timestamp = isset($sap_general_options['timestamp_link']) ? "?".time() : '';

        if ( !empty($tumblr_sess_data) ) {

            //Get posting type
            $posting_type_global = !empty($sap_tumblr_options['posting_type']) ? $sap_tumblr_options['posting_type'] : '';
            $posting_type_meta = $this->posts->get_post_meta($post_id, '_sap_tumblr_post_type');
            $posting_type = !empty($posting_type_meta) ? $posting_type_meta : $posting_type_global;
            $posting_status = $this->posts->get_post_meta($post_id, '_sap_tumblr_status');
            $post_profile = $this->posts->get_post_meta($post_id, '_sap_tumblr_post_profile');        

            //Get image url
            $setting_tumblr_img = !empty($sap_tumblr_options['tumblr_image']) ? $sap_tumblr_options['tumblr_image'] : '';

            //check post image is not empty then pass to facebook
            $post_img = !empty($post->img) ? $post->img : '';
            $sap_custom_img = $this->posts->get_post_meta($post_id, '_sap_tumblr_post_img');
            $post_img = !empty($sap_custom_img) ? $sap_custom_img : $post_img;
            $post_img = empty($post_img) ? $sap_custom_img : $post_img;
            if (empty($post_img)) {
                $post_img = $setting_tumblr_img;
            }

            //Get image url
            $setting_tumblr_link = !empty($sap_tumblr_options['tumblr_link']) ? $sap_tumblr_options['tumblr_link'] : '';

            $tumblr_custom_link = !empty($post->share_link) ? $post->share_link : '';
            $sap_custom_link = $this->posts->get_post_meta($post_id, '_sap_tumblr_post_link');
            $tumblr_custom_link = !empty($sap_custom_link) ? $sap_custom_link : $tumblr_custom_link;
            $tumblr_custom_link = empty($tumblr_custom_link) ? $sap_custom_img : $tumblr_custom_link;
            if (empty($tumblr_custom_link)) {
                $tumblr_custom_link = $setting_tumblr_link;
            }

            if(!empty($tumblr_custom_link)) {
                $tumblr_custom_link = $tumblr_custom_link."".$link_timestamp;
            }

            //$tumblr_custom_link = !empty($post->share_link) ? $post->share_link : $sap_post_link;

            $customlink = !empty($tumblr_custom_link) ? 'true' : 'false' ;
            $tumblr_custom_link = $this->common->sap_script_short_post_link($tumblr_custom_link, $customlink, 'tu', 'tumblr', $user_id);

            //description
            $description = $this->posts->get_post_meta($post_id, '_sap_tumblr_post_desc');
            $description = !empty($description) ? $description : $post->body;
            $description = stripcslashes($description);

            if (empty($sap_tumblr_options['post_content_size']) || (!empty($sap_tumblr_options['post_content_size']) && $sap_tumblr_options['post_content_size'] == 'snippets' )) { //check tumblr content is set full or snippest
                //it will consider first 200 characters when snippests is selected
                $description = $this->common->sap_content_excerpt($description, 200);
            }

            //decode html from posting content
            $description = html_entity_decode(strip_tags($description),ENT_QUOTES);

            // Tumblr limit 4096 character per post
            if (!empty($description))
                $description = $this->posts->sap_limit_character($description, 4096);

            /* Preparing data based on posting type */
            $posting_log = array();
            switch ($posting_type) {
                case 'link':
                    if ( empty( $tumblr_custom_link ) ) {
                        $this->flash->setFlash('Please Enter Link For Link Posting', 'error','',true);
                        return false;
                    }
                    //Set all params
                    $tumblrdata = array(
                        'type' => 'link',
                        'url' => $tumblr_custom_link,
                        'description' => $description,
                        'thumbnail' => SAP_IMG_URL . $post_img,
                        'excerpt' => '',
                    );
                    $posting_log['link'] = $tumblr_custom_link;
                    $posting_log['image'] = SAP_IMG_URL . $post_img;
                    $posting_log['message'] = $description;
                    $posting_log['type'] = 'link';
                    break;

                case 'photo':
                    //Set all params
                    $tumblrdata = array(
                        'type' => 'photo',
                        'caption' => $description,
                        'link' => $tumblr_custom_link,
                        'source' => SAP_IMG_URL . $post_img,
                    );
                    $posting_log['link'] = $tumblr_custom_link;
                    $posting_log['image'] = SAP_IMG_URL . $post_img;
                    $posting_log['message'] = $description;
                    $posting_log['type'] = 'photo';

                    break;
                case 'text':
                default:
                    $posting_log['message'] = $description;
                    $posting_log['type'] = 'text';
                    //Final posting description
                    $tumblrdata = array('type' => 'text', 'body' => $description);
                    break;
            }

            if (!empty($post_profile)) {
                $tumblr_profile_accounts = explode(",", $this->posts->get_post_meta($post_id, '_sap_tumblr_post_profile'));
            } else {
                $tumblr_profile_accounts = !empty($sap_tumblr_options['tumblr_type_post_accounts']) ? $sap_tumblr_options['tumblr_type_post_accounts'] : array();
            }

            $newArray = [];
            foreach ( $tumblr_profile_accounts as $accKey => $accValue ) {
                $newVal = explode( '|', $accValue );
                $newArray[$newVal[0]][] = $newVal[1];
            }

            foreach ($tumblr_sess_data as $key => $value) {
                foreach ( $value['sap_tb_user_cache']->blogs as $blogKey => $blogValue ) {
                    if ( array_key_exists( $key, $newArray ) && in_array( $blogValue->name, $newArray[$key] ) ) {

                        $tumblr_oauth = new Tumblr\API\Client( $key, $value['sap_tb_consumer_secret'], $value['sap_tb_oauth_token'], $value['sap_tb_outh_toke_secret']);
                        
                        try {

                            $postinfo = $tumblr_oauth->createPost($blogValue->name , $tumblrdata);

                            $code = [];
                            $code['status'] = $postinfo->state;
                            $code['posting_type'] = $posting_log['type'];

                            if (isset($postinfo->id) && !empty($postinfo->id)) {

                                $posting_log['account name'] = $blogValue->name;
                                $posting_log['link to post'] = 'https://www.tumblr.com/blog/' . $blogValue->name;
                                $this->logs->add_log('tumblr', $posting_log, 1, $user_id);
                                $this->flash->setFlash('Tumblr : Post sucessfully posted on - ' . $blogValue->name, 'success','',true);
                                $this->sap_common->sap_script_logs('Tumblr : Post sucessfully posted on - ' . $blogValue->name, $user_id);
                                $this->sap_common->sap_script_logs('Tumblr post data : ' . var_export($posting_log, true), $user_id);
                            } else {

                                if (is_array($postinfo->errors)) {

                                    $errorMessage = $postinfo->errors[0]->detail;
                                    if (isset($postinfo->errors[0]->message)) {
                                        $errorMessage = $postinfo->errors[0]->message;
                                    }
                                    $this->sap_common->sap_script_logs('Tumblr error : ' . $errorMessage, $user_id);
                                    $this->flash->setFlash('Tumblr error : ' . $errorMessage, 'error','',true);
                                } else {

                                    $this->flash->setFlash('Error posting on tumblr.', 'error','',true);
                                    $this->sap_common->sap_script_logs('Tumblr error : ' . $postinfo->errors[0]->detail, $user_id);
                                }
                            }
                        } catch (Exception $e) {

                            //record logs exception generated
                            $this->sap_common->sap_script_logs('Tumblr error : ' . $e->__toString(), $user_id);
                            $this->flash->setFlash($e->__toString(), 'error','',true);
                            return false;
                        }

                    }
                }
            }
            
            if (isset($code)) {
                return $code;
            }
        } else {
            $this->sap_common->sap_script_logs('Tumblr grant extended permissions not set.', $user_id);
            $this->flash->setFlash('Tumblr grant extended permissions not set.', 'error','',true);
        }
    }

    /**
     * Tumblr Quick post
     * Handle Quick post on tumblr
     * 
     * @package Social Auto Poster
     * @since 1.0.0
     */
    public function sap_quick_post_to_tumblr($post_id) {

        $status_meta_array = array();

        $quick_post = $this->quick_posts->get_post($post_id, true);
        $user_id = isset( $quick_post->user_id ) ? $quick_post->user_id : '';
        //load tumblr class
        $tumblr = $this->sap_load_tumblr(false, $user_id);

        //check tumblr loaded or not
        if ( !$tumblr ) {
            return false;
        }

        $sap_tumblr_options = $this->settings->get_user_setting('sap_tumblr_options', $user_id);
        $tumblr_sess_data = $this->settings->get_user_setting('sap_tumblr_sess_data', $user_id);

        // General setting
        $sap_general_options = $this->settings->get_user_setting('sap_general_options',$user_id);

        $link_timestamp = isset($sap_general_options['timestamp_link']) ? "?".time() : '';

        //check tumblr user id is set in session and not empty
        if (!empty($tumblr_sess_data)) {

            $sap_networks_meta = $this->quick_posts->get_post_meta($post_id, 'sap_networks');
            $posting_type = !empty($sap_networks_meta['tu_posting_type']) ? $sap_networks_meta['tu_posting_type'] : 'text';

            if (isset($sap_tumblr_options['tumblr_image'])) {

                $tumblr_settings_image = $sap_tumblr_options['tumblr_image'];
            }

            //check post image is not empty then pass to tumblr
            $post_img = !empty($quick_post->image) ? $quick_post->image : $tumblr_settings_image;

            $tumblr_sess_data = $this->settings->get_user_setting('sap_tumblr_sess_data',$user_id);
            $tumblr_custom_link = $quick_post->share_link;

            if(!empty($tumblr_custom_link)) {
                $tumblr_custom_link = $tumblr_custom_link."".$link_timestamp;
            }

            $customlink = !empty($tumblr_custom_link) ? 'true' : 'false';
            $tumblr_custom_link = $this->common->sap_script_short_post_link($tumblr_custom_link, $customlink, 'tu', 'tumblr', $user_id);

            //description
            $description = stripcslashes($quick_post->message);
            $description = html_entity_decode(strip_tags($description),ENT_QUOTES);

            // Tumblr limit 4096 character per post
            if (!empty($description))
                $description = $this->posts->sap_limit_character($description, 4096);

            $posting_log = array();
            
            switch ($posting_type) {

                case 'video':
                    $video = !empty($quick_post->video) ? $quick_post->video : "";
                    $video_path = SAP_APP_PATH.'uploads/'.$video;
                    $media_source = SAP_IMG_URL . $video;                    
                    $tumblrdata = array(
                        'type'    => 'video',
                        'caption' => $description,
                        'data'    => $video_path,
                    );    
                    $posting_log['link'] = $tumblr_custom_link;
                    $posting_log['image'] = $media_source;
                    $posting_log['message'] = $description;
                    $posting_log['type'] = 'video';
                break;    

                case 'link':

                    if ( empty( $tumblr_custom_link ) ) {
                        $this->flash->setFlash('Please Enter Link For Link Posting', 'error','',true);
                        return false;
                    }
                    //Set all params
                    $tumblrdata = array(
                        'type' => 'link',
                        'url' => $tumblr_custom_link,
                        'description' => $description,
                    );

                    if ( !empty( $post_img ) ) {
                        $tumblrdata['thumbnail'] = SAP_IMG_URL . $post_img;
                    }
                    $posting_log['link'] = $tumblr_custom_link;
                    $posting_log['image'] = SAP_IMG_URL . $post_img;
                    $posting_log['message'] = $description;
                    $posting_log['type'] = 'link';
                    break;

                case 'photo':
                    //Set all params
                    $tumblrdata = array(
                        'type' => 'photo',
                        'caption' => $description,
                        'link' => $tumblr_custom_link,
                        'source' => SAP_IMG_URL . $post_img,
                    );
                    $posting_log['link'] = $tumblr_custom_link;
                    $posting_log['image'] = SAP_IMG_URL . $post_img;
                    $posting_log['message'] = $description;
                    $posting_log['type'] = 'photo';
                    break;
                case 'text':
                default:
                    $posting_log['message'] = $description;
                    $posting_log['type'] = 'text';
                    //Final posting description
                    $tumblrdata = array('type' => 'text', 'body' => $description);
                    break;
            }

            $sap_networks_meta = $this->quick_posts->get_post_meta($post_id, 'sap_networks');

            $accounts = !empty($sap_networks_meta['tumblr_accounts']) ? $sap_networks_meta['tumblr_accounts'] : array();
            $default_accounts = !empty($sap_tumblr_options['tumblr_type_post_accounts']) ? $sap_tumblr_options['tumblr_type_post_accounts'] : array();
            $selected_accounts = array();

            $selected_accounts = !empty($accounts) ? $accounts : $default_accounts;
            $newArray = [];
            foreach ( $selected_accounts as $accKey => $accValue ) {
                $newVal = explode( '|', $accValue );
                $newArray[$newVal[0]][] = $newVal[1];
            }
            
            foreach ($tumblr_sess_data as $key => $value) {
                    
                foreach ( $value['sap_tb_user_cache']->blogs as $blogKey => $blogValue ) {
                
                   if ( array_key_exists( $key, $newArray ) && in_array( $blogValue->name, $newArray[$key] ) ) {

                        if ( isset( $blogValue ) && isset( $blogValue->name ) ) {
                            $status_meta_key = $blogValue->name;
                        } elseif ( isset( $value['sap_tb_user_id'] ) ) {
                            $status_meta_key = $value['sap_tb_user_id'];
                        } elseif ( isset( $value['sap_tb_consumer_secret'] ) && isset( $value['sap_tb_oauth_token'] ) && isset( $value['sap_tb_outh_toke_secret'] ) ) {
                            $status_meta_key = $value['sap_tb_consumer_secret'] . "|" . $value['sap_tb_oauth_token'] . "|" . $value['sap_tb_outh_toke_secret'];
                        } else {
                            $status_meta_key = $key;
                        }

                        $tumblr_oauth = new Tumblr\API\Client( $key, $value['sap_tb_consumer_secret'], $value['sap_tb_oauth_token'], $value['sap_tb_outh_toke_secret']);

                        try {
                            $postinfo = $tumblr_oauth->createPost($blogValue->name , $tumblrdata);
                            
                            $code = [];
                            $code['status'] = $postinfo->state;
                            $code['posting_type'] = $posting_log['type'];
                    
                            if (isset($postinfo->id) && !empty($postinfo->id)) {

                                $posting_log['account name'] = $blogValue->name;
                                $posting_log['link to post'] = 'https://www.tumblr.com/blog/' . $blogValue->name;
                                $this->logs->add_log('tumblr', $posting_log, 1, $user_id);
                                $this->flash->setFlash('Tumblr : Post sucessfully posted on - ' . $blogValue->name, 'success','',true);
                                $this->sap_common->sap_script_logs('Tumblr : Post sucessfully posted on -' . $blogValue->name, $user_id);
                                $this->sap_common->sap_script_logs('Tumblr post data : ' . var_export($posting_log, true), $user_id);
                                $status_meta_array[$status_meta_key] = array(
                                    "status" => 'success'
                                );
                                $this->quick_posts->update_post_meta($post_id, "sap_tumblr_link_to_post", $posting_log['link to post']);
                            } else {

                                if (is_array($postinfo->errors)) {

                                    $errorMessage = $postinfo->errors[0]->detail;
                                    if (isset($postinfo->errors[0]->message)) {
                                        $errorMessage = $postinfo->errors[0]->message;
                                    }
                                    $this->sap_common->sap_script_logs('Tumblr error : ' . $errorMessage, $user_id);
                                    $this->flash->setFlash('Tumblr error : ' . $errorMessage, 'error','',true);
                                    $status_meta_array[$status_meta_key] = array(
                                        "status" => 'error',
                                        "message" => $errorMessage
                                    );
                                } else {

                                    $this->sap_common->sap_script_logs('Tumblr error : Error posting on tumblr.', $user_id);
                                    $this->flash->setFlash('Error posting on tumblr.', 'error','',true);
                                    $status_meta_array[$status_meta_key] = array(
                                        "status" => 'error',
                                        "message" => 'Error posting on tumblr.'
                                    );
                                }
                            }
                        } catch (Exception $e) {
                            //record logs exception generated
                            $this->sap_common->sap_script_logs('Tumblr error : ' . $e->__toString(), $user_id);
                            $this->flash->setFlash($e->__toString(), 'error','',true);
                            $status_meta_array[$status_meta_key] = array(
                                "status" => 'error',
                                "message" => $e->__toString()
                            );
                            return false;
                        }
                   }
                    
                }
            }
           
            $this->quick_posts->update_post_meta($post_id, "sap_tumblr_posting_error", $status_meta_array);

            if (isset($code)) {
                return $code;
            }
        } else {
            $this->sap_common->sap_script_logs('Tumblr grant extended permissions not set.',$user_id);
            $this->flash->setFlash('Tumblr grant extended permissions not set.', 'error','',true);
            $status_meta_array[] = array(
                "status" => 'error',
                "message" => 'Tumblr grant extended permissions not set.'
            );
            $this->quick_posts->update_post_meta($post_id, "sap_tumblr_posting_error", $status_meta_array);
        }
    }

    /**
     * Tumblr Fetch Multiple Accounts
     * Handles accounts of Tumblr
     * 
     * @package Social Auto Poster
     * @since 1.0.0
     */
    public function sap_fetch_tumblr_accounts($user_id='') {

        // Taking some defaults
        $res_data = array();

        //Get stored tb app grant data 
        $sap_tumblr_sess_data = $this->settings->get_user_setting('sap_tumblr_sess_data',$user_id);
        if (is_array($sap_tumblr_sess_data) && !empty($sap_tumblr_sess_data)) {

            foreach ($sap_tumblr_sess_data as $tb_key => $tb_data) {
                
                if (is_array($tb_data) && !empty($tb_data)) {

                    if ( isset( $tb_data['sap_tb_user_id'] ) ) {

                        if ( !empty( $tb_data['sap_tb_user_cache']->blogs ) && is_array( $tb_data['sap_tb_user_cache']->blogs )  ) {

                            $tumblr_blogs = array();
                            $tumblr_blogs = $tb_data['sap_tb_user_cache']->blogs;

                            foreach ( $tumblr_blogs as $tb_blog_key => $tb_blog_data  ) {

                                $res_data[$tb_key . '|' . $tb_blog_data->name] = $tb_blog_data->name;

                            }

                        }

                    }
                }
            }
        }

        return $res_data;
    }

}
