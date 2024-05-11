<?php

/* Check the absolute path to the Social Auto Poster directory. */
if ( !defined( 'SAP_APP_PATH' ) ) {
    // If SAP_APP_PATH constant is not defined, perform some action, show an error, or exit the script
    // Or exit the script if required
    exit();
}

/**
 * Youtube posting
 *
 * Handles all the functions to post on youtube
 * 
 * @package Social auto poster
 * @since 1.0.0
 */
class SAP_Youtube {

    //private $db, $common, $flash, $youtube, $settings, $user_id, $client;
    public $youtube, $settings, $common, $posts, $youtubeconfig, $flash, $logs, $sap_common, $quick_posts, $client;
    private $db;

    public function __construct( $user_id='' ) {
        
        global $sap_common,$sap_db_connect;

        if (!class_exists('SAP_Quick_Posts')) {
            require_once( CLASS_PATH . 'Quick_Posts.php' );
        }

        $this->settings = new SAP_Settings();
        $this->common = new Common();
        $this->db = $sap_db_connect;
        $this->flash = new Flash();
        $this->logs = new SAP_Logs();
        $this->sap_common = $sap_common;
        $this->quick_posts = new SAP_Quick_Posts();

        $sap_youtube_options = $this->settings->get_user_setting('sap_youtube_options', $user_id);

        if (isset($sap_youtube_options['youtube_keys']) && !empty($sap_youtube_options['youtube_keys'])) {
            if (!defined('YT_APP_ID')) {
                define('YT_APP_ID', $sap_youtube_options['youtube_keys'][0]['consumer_key']);
            }
            if (!defined('YT_APP_SECRET')) {
                define('YT_APP_SECRET', $sap_youtube_options['youtube_keys'][0]['consumer_secret']);
            }
        }

        $this->sap_yt_user_logged_in($user_id);
    }


    /**
     * Make Logged In User to Youtube
     * 
     * @package Social Auto Poster
     * @since 1.0.0
     */
    public function sap_yt_user_logged_in( $user_id = '' ) {
        
        $sap_youtube_options = $this->settings->get_user_setting('sap_youtube_options', $user_id);

        $youtube_keys = isset($sap_youtube_options['youtube_keys']) ? $sap_youtube_options['youtube_keys'] : array();

        //check if user is logged in to youtube
        if (isset($_GET['sap']) && $_GET['sap'] == 'youtube' && !empty( $_GET['code'] ) && isset( $_GET['yt_app_id'] )) {
 
            //record logs for grant extended permission
            $this->sap_common->sap_script_logs('Youtube Grant Extended Permission', $user_id);

            //record logs for get parameters set properly
            $this->sap_common->sap_script_logs('Get Parameters Set Properly.', $user_id);
            
            // Get youtube app key/Id
            $yt_app_id = $_GET['yt_app_id'];

            
            $callbackUrl = SAP_SITE_URL .'/settings/' . '?sap=youtube&yt_app_id=' . $yt_app_id;

            //load youtube class
            $youtube = $this->sap_load_youtube($yt_app_id);
            
            try {

                //check youtube loaded or not
                if (!$youtube)  return false;

                //check youtube loaded or not
                $this->client->authenticate($_GET['code']);
                
                //Get Access token
                $access_token  = $this->client->getAccessToken();
               
                // code will excute when user does connect with youtube
                if( !empty( $access_token ) ) { // if user allows access to youtube

                    //record logs for get type initiate called
                    $this->sap_common->sap_script_logs( 'YouTube grant initiate called' );

                    //record logs for get type response called
                    $this->sap_common->sap_script_logs( 'YouTube permission granted by user' );

                    //record logs for get type initiate called
                    $this->sap_common->sap_script_logs( 'YouTube Request token retrieval success when clicked on allow access by user' );

                    // the request went through without an error, gather user's 'access' tokens
                    $sap_yt_oauth['youtube']['access']['access_token'] = $access_token;
                                 
                    // the request went through without an error, gather user's 'access' tokens
                    $_SESSION['sap_yt_oauth']['youtube']['access'] = $access_token;

                    // set the user as authorized for future quick reference
                    $_SESSION['sap_yt_oauth']['youtube']['authorized'] = TRUE;
                     
                    if( !empty( $access_token ) ){
                        
                        //Get User Profiles
                        $resultdata = $this->sap_get_processed_profile_data( $yt_app_id );

                        //set user data to sesssion for further use
                        $_SESSION['sap_yt_cache'] = $resultdata;
                        $_SESSION['yt_user_id'] = isset($yt_app_id) ? $yt_app_id : '';

                        // // redirect the user back to the demo page
                        // $this->message->add_session( 'poster-selected-tab', 'youtube' );

                        //set user data  to session
                        $this->sap_set_yt_data_to_session( $yt_app_id, $user_id ); // pending

                        // unset session data so there will be no probelm to grant extend another account
                        unset($_SESSION['sap_yt_oauth']);
                        unset($_SESSION['sap_yt_oauth']);

                        $_SESSION['sap_active_tab'] = 'youtube';
                        header("Location:" . SAP_SITE_URL . "/settings/");
                        exit;
                    }
                    else {

                        $this->flash->setFlash($_GET['error_description'], 'error');
                        $_SESSION['sap_active_tab'] = 'youtube';
                        header("Location:" . SAP_SITE_URL . "/settings/");
                        exit;
                    }
                }
            } catch (Google_Exception $e) {
                return false;
            }
        }
    }

    public function sap_get_processed_profile_data( $app_id ){
        $user_data['id'] = $app_id;

        return $user_data;
    }

    /**
     * Get Youtube Login URL
     * 
     * Handles to Return Youtube URL
     * 
     * @package Social Auto Poster - You Tube
     * @since 1.0.0
     */
    public function sap_get_yt_login_url( $app_id = false ) {

        //load youtube class
        $youtube = $this->sap_load_youtube( $app_id );

        //check youtube loaded or not
        if( !$youtube ) return false;
        
        $callbackUrl = SAP_SITE_URL .'/settings/?sap=youtube&yt_app_id='.$app_id;
        
        try {//Prepare login URL
            $preparedurl    = $this->client->createAuthUrl();
        } catch( Exception $e ) {
            $preparedurl    = '';
        }
        return $preparedurl;
    }

    /**
     * Include Youtube Class
     * 
     * Handles to load Youtube class
     * 
     * @package Social Auto Poster - You Tube
     * @since 1.0.0
     */
    public function sap_load_youtube( $app_id = false ) {

        // Getting youtube apps
        $yt_apps = $this->sap_get_yt_apps();
        
        // If app id is not passed then take first yt app data
        if( empty($app_id) ) {
            $yt_apps_keys = array_keys( $yt_apps );
            $app_id = reset( $yt_apps_keys );
        }

        //youtube declaration
        if( !empty( $app_id ) && !empty( $yt_apps[$app_id] ) ) {
            
            // Include google client libraries
            if (!function_exists('google_api_php_client_autoload')) {
                include LIB_PATH . 'Social/youtube/autoload.php';
            }
            if (!class_exists('Google_Client')) {
                include LIB_PATH . 'Social/youtube/Client.php';
            }
            if (!class_exists('Google_Service_YouTube')) {
                include LIB_PATH . 'Social/youtube/Service/YouTube.php';
            }
            
            $this->client = new Google_Client();
        
            $this->client->setClientId( $app_id ); 

            $this->client->setClientSecret( $yt_apps[$app_id] ); 

            $this->client->setScopes( 'https://www.googleapis.com/auth/youtube.upload' ); 
            $this->client->setAccessType('offline');
            $this->client->setApprovalPrompt("force");

            $portvalue = $this->common->is_ssl() ? 'https://' : 'http://';
            $redirect_URL = $portvalue . $_SERVER['SERVER_NAME'] . $_SERVER['REQUEST_URI'];
            if ( isset( $_SERVER['SERVER_PORT'] ) && !empty( $_SERVER['SERVER_PORT'] ) ) {
                if ( strpos( $_SERVER['HTTP_HOST'] , $_SERVER['SERVER_PORT'] ) && $_SERVER['SERVER_NAME'] !== $_SERVER['HTTP_HOST'] ) {
                    $redirect_URL = $portvalue . $_SERVER['SERVER_NAME'] . ':' . $_SERVER['SERVER_PORT'] . $_SERVER['REQUEST_URI'];
                }
            }

            $url_args = '/settings/?sap=youtube&yt_app_id='.$app_id;
            $callbackUrl = SAP_SITE_URL . $url_args;
            
            $this->client->setRedirectUri( $callbackUrl ); 
            
            // Get access token
            $access_tocken   = $this->sap_yt_get_access_token( $app_id );
            
            // Load youtube outh2 class
            $this->youtube = new Google_Service_YouTube( $this->client  );
            
            return true;
        } else {
           return false;
        }
    }

    /**
     * Get all youtube apps
     *
     * @package Social Auto Poster You Tube
     * @since 1.0.0
     */
    public function sap_get_yt_apps( $user_id ='' ) {

        $sap_youtube_options = $this->settings->get_user_setting('sap_youtube_options', $user_id);
        
        $yt_apps = array();
        $yt_keys = !empty($sap_youtube_options['youtube_keys']) ? $sap_youtube_options['youtube_keys'] : array();
        
        if( !empty($yt_keys) ) {
            foreach ($yt_keys as $yt_key_data) {
                if (!empty($yt_key_data['consumer_key']) && !empty($yt_key_data['consumer_secret'])) {
                    $consumer_key = $yt_key_data['consumer_key'];
                    $consumer_secret = $yt_key_data['consumer_secret'];
                    $yt_apps[$consumer_key] = $consumer_secret;
                }
            }
        } // End of main if
        
        return $yt_apps;
    }

    /**
     * Set Session Data of youtube to session
     * 
     * Handles to set user data to session
     * 
     * @package Social Auto Poster - You Tube
     * @since 1.0.0
     */
    public function sap_set_yt_data_to_session( $li_app_id = false, $user_id='' ) {

        //fetch user data who is grant the premission
        $ytuserdata = $this->sap_get_yt_user_data($user_id);
        
        if( isset( $ytuserdata['id'] ) && !empty( $ytuserdata['id'] ) ) {

            //record logs for user id
            $this->sap_common->sap_script_logs('YouTube User ID : '.$ytuserdata['id']);
            
            try {

                $sap_yt_user_id = isset($_SESSION['sap_yt_user_id']) ? $_SESSION['sap_yt_user_id'] : $ytuserdata['id'];
                $parts = explode('.', $sap_yt_user_id);
                $yt_user_id = $parts[0];

                $sap_yt_cache = isset($_SESSION['sap_yt_cache']) ? $_SESSION['sap_yt_cache'] : $ytuserdata;

                $sap_youtube_oauth = isset($_SESSION['sap_yt_oauth']) ? $_SESSION['sap_yt_oauth'] : $_SESSION['sap_youtube_oauth'];
                
                // start code to manage session from database 			
                $sap_yt_sess_data = $this->settings->get_user_setting('sap_yt_sess_data', $user_id );
                
                if (empty($sap_yt_sess_data)) {

                    $sap_yt_sess_data = array();
                    $sap_yt_sess_data[$yt_user_id] = array(
                        'sap_yt_user_id'    => $sap_yt_user_id,
                        'sap_yt_cache'      => $sap_yt_cache,
                        'sap_yt_oauth'      => $sap_youtube_oauth,
                    );
                    
                    $this->settings->update_user_setting('sap_yt_sess_data', $sap_yt_sess_data);
                    
                    $this->sap_common->sap_script_logs('YouTube Session Data Updated to Options', $sap_yt_user_id);
                }

                if (!isset($sap_yt_sess_data[$yt_user_id])) {

                    $sess_data = array(
                        'sap_yt_user_id'    => $sap_yt_user_id,
                        'sap_yt_cache'      => $sap_yt_cache,
                        'sap_yt_oauth'      => $sap_youtube_oauth,
                    );

                    $sap_yt_sess_data[$yt_user_id] = $sess_data;
                    $orignal_result = $this->settings->get_user_setting('sap_yt_sess_data',$yt_user_id);
                    if (!empty($orignal_result) && $yt_user_id) {

                        $final_data = array_merge($orignal_result, $sap_yt_sess_data);
                        $this->settings->update_user_setting('sap_yt_sess_data', $final_data);
                        $this->sap_common->sap_script_logs('Linkedin Session Data Updated to Options', $sap_yt_user_id);
                    } else {

                        $this->settings->update_user_setting('sap_yt_sess_data', $sap_yt_sess_data);
                        $this->sap_common->sap_script_logs('Linkedin Session Data Updated to Options', $sap_yt_user_id);
                    }
                }
            } catch( Exception $e ) {

                $ytuserdata = null;
            }
        }
    }

     /**
     * Get LinkedIn User Data
     *
     * Function to get LinkedIn User Data
     *
     * @package Social Auto Poster
     * @since 1.0.0
     */
    public function sap_get_yt_user_data( $user_id='' ) {

        $sap_yt_sess_data = $this->settings->get_user_setting('sap_yt_sess_data',$user_id);

        $user_profile_data = '';

        if (isset($_SESSION['sap_yt_cache']) && !empty($_SESSION['sap_yt_cache'])) {

            $user_profile_data = $_SESSION['sap_yt_cache'];
        }

        return $user_profile_data;
    }


    /**
     * Youtube Get Access Tocken
     * 
     * @package Social Auto Poster - You Tube
     * @since 1.0.0
     */
    public function sap_yt_get_access_token( $app_id ) {

        $access_tocken  = array();

        $sap_youtube_options = $this->settings->get_user_setting('sap_youtube_options', $user_id);
        
        //Get stored yt app grant data
        $sap_yt_sess_data = $this->settings->get_user_setting('sap_yt_sess_data',$user_id);
        
        $youtube_auth_options = !empty($sap_youtube_options['youtube_auth_options']) ? $sap_youtube_options['youtube_auth_options'] : '';

        $parts = explode('.', $app_id);
		$yt_user_id = $parts[0];        
        if( isset( $sap_yt_sess_data ) && !empty( $sap_yt_sess_data ) && isset( $sap_yt_sess_data[$yt_user_id]['sap_yt_oauth']['youtube']['access'] ) ) {
            
            $access_tocken = $sap_yt_sess_data[$yt_user_id]['sap_yt_oauth']['youtube']['access'];
            $access_tocken  = isset( $yt_access_data['access_token'] ) ? $yt_access_data['access_token'] : '';

        } elseif( isset( $wpw_auto_poster_youtube_oauth ) ) {
            
            $access_tocken = $wpw_auto_poster_youtube_oauth;
            
            $access_tocken  = isset( $yt_access_data['access_token'] ) ? $yt_access_data['access_token'] : '';
        }
        
        return $access_tocken;
    }

    /**
     * Youtube Get Access Tocken
     * 
     * @package Social Auto Poster - You Tube
     * @since 1.0.0
     */
    public function sap_yt_get_access_token_quickpost( $app_id ) {

        $access_tocken  = array();

        $sap_youtube_options = $this->settings->get_user_setting('sap_youtube_options', $user_id);
        
        //Get stored yt app grant data
        $sap_yt_sess_data = $this->settings->get_user_setting('sap_yt_sess_data',$user_id);
        
        $youtube_auth_options = !empty($sap_youtube_options['youtube_auth_options']) ? $sap_youtube_options['youtube_auth_options'] : '';

        $parts = explode('.', $app_id);
		$yt_user_id = $parts[0];        
        if( isset( $sap_yt_sess_data ) && !empty( $sap_yt_sess_data ) && isset( $sap_yt_sess_data[$yt_user_id]['sap_yt_oauth']['youtube']['access'] ) ) {
            
            $access_tocken = $sap_yt_sess_data[$yt_user_id]['sap_yt_oauth']['youtube']['access'];
            //$access_tocken  = isset( $yt_access_data['access_token'] ) ? $yt_access_data['access_token'] : '';

        } elseif( isset( $wpw_auto_poster_youtube_oauth ) ) {
            
            $access_tocken = $wpw_auto_poster_youtube_oauth;
            
            //$access_tocken  = isset( $yt_access_data['access_token'] ) ? $yt_access_data['access_token'] : '';
        }
        
        return $access_tocken;
    }


    /**
     * Reset Sessions
     *
     * Resetting the Linkedin sessions when the admin clicks on
     * its link within the settings page.
     *
     * @package Social Auto Poster
     * @since 1.0.0
     */
    public function sap_yt_reset_session() {

        if (isset($_GET['yt_reset_user']) && $_GET['yt_reset_user'] == '1' && !empty($_GET['yt_app_id'])) {

            $parts = explode('.', $_GET['yt_app_id']);
            $yt_app_id = $parts[0];

            // Getting stored li app data
            $sap_yt_sess_data = $this->settings->get_user_setting('sap_yt_sess_data');
            $sap_youtube_options = $this->settings->get_user_setting('sap_youtube_options');
            
            // Unset particular app value data and update the option
            if (isset($sap_yt_sess_data[$yt_app_id])) {
                unset($sap_yt_sess_data[$yt_app_id]);
                $this->settings->update_user_setting('sap_yt_sess_data', $sap_yt_sess_data);
                
                $sap_youtube_options['yt_type_post_user'] = array_diff($sap_youtube_options['yt_type_post_user'], array($_GET['yt_app_id']));
                $this->settings->update_user_setting('sap_youtube_options', $sap_youtube_options);

                $this->sap_common->sap_script_logs('Youtube ' . $yt_app_id . ' Account Reset Successfully.',$user_id);
            }

            if (isset($_SESSION['sap_yt_user_id'])) {//destroy userId session
                unset($_SESSION['sap_yt_user_id']);
            }
            if (isset($_SESSION['sap_yt_cache'])) {//destroy cache
                unset($_SESSION['sap_yt_cache']);
            }
            if (isset($_SESSION['sap_yt_oauth'])) {//destroy youtube session
                unset($_SESSION['sap_yt_oauth']);
            }
            
            $_SESSION['sap_active_tab'] = 'youtube';
            header("Location:" . SAP_SITE_URL . "/settings/");
            exit;
        }
    }

    /**
     * Quick Post To Youtube
     * 
     * Handles to Quick Post on Youtube account
     * 
     * @package Social Auto Poster
     * @since 1.0.0
     */
    public function sap_quick_post_to_youtube($post_id) {

        $postflg = false;
        //Get Post details
        $status_meta_array = array();

        // Include google client libraries
        if (!function_exists('google_api_php_client_autoload')) {
            include LIB_PATH . 'Social/youtube/autoload.php';
        }
        if (!class_exists('Google_Client')) {
            include LIB_PATH . 'Social/youtube/Client.php';
        }
        if (!class_exists('Google_Service_YouTube')) {
            include LIB_PATH . 'Social/youtube/Service/YouTube.php';
        }
        
        $this->posts = new SAP_Posts();
        $this->client = new Google_Client();
        
        $quick_post = $this->quick_posts->get_post($post_id, true);

        $user_id = isset( $quick_post->user_id ) ? $quick_post->user_id : '';
        
        $sap_networks_meta = $this->quick_posts->get_post_meta($post_id, 'sap_networks');

        $accounts = !empty($sap_networks_meta['youtube_accounts']) ? $sap_networks_meta['youtube_accounts'] : array();

        // General setting
        $sap_general_options = $this->settings->get_user_setting('sap_general_options',$user_id);

        $link_timestamp = isset($sap_general_options['timestamp_link']) ? "?".time() : '';

        //Get general options;
        $sap_youtube_options = $this->settings->get_user_setting('sap_youtube_options', $user_id);
        $default_accounts = !empty($sap_youtube_options['yt_type_post_user']) ? $sap_youtube_options['yt_type_post_user'] : '';

        $twitter_keys = !empty($sap_youtube_options['youtube_keys']) ? $sap_youtube_options['youtube_keys'] : array();
        
        $sap_yt_sess_data = $this->settings->get_user_setting('sap_yt_sess_data', $user_id);

        $post_link = strip_tags($quick_post->share_link);
        
        if(!empty($post_link)) {
            $post_link = $post_link."".$link_timestamp;
        }
        $customlink  = !empty($post_link) ? 'true' : 'false';
        $post_link     = $this->common->sap_script_short_post_link($post_link,$customlink,'yt','youtube', $user_id);

        $post_body = !empty($quick_post->message) ? htmlentities(strip_tags($quick_post->message)) : '';
        $post_body = !empty($post_body) ? html_entity_decode(strip_tags($post_body),ENT_QUOTES) : '';

        $post_desc = $image = '';
        $post_desc .= (!empty($post_link) ) ? $post_link . "\r\n" : '';
        $post_desc .= (!empty($post_body) ) ? $post_body . "\r\n" : '';

        $posting_type = $this->quick_posts->get_post_meta($post_id, '_sap_yt_status');
        
        // Twitter limit 140 character for tweet
        if (!empty($post_desc)){
            $post_desc = $this->posts->sap_limit_character($post_desc, 280);
        }

        $accounts = !empty($accounts) ? $accounts : $default_accounts;
        
        //Check Accounts exist
        if (empty($accounts)) {
            $this->flash->setFlash('Youtube posting users are not selected.', 'error','',true);
            $this->sap_common->sap_script_logs('Youtube posting users are not selected.', $user_id );
            $status_meta_array[] = array(
                "status" => 'error',
                "message" => 'Youtube posting users are not selected.'
            );
            $this->quick_posts->update_post_meta($post_id,"sap_yt_posting_error", $status_meta_array);
            return false;
        }

        if (isset($sap_youtube_options['sap_yt_video'])) {
            $general_yt_video = $sap_youtube_options['sap_yt_video'];
        }

        $video = !empty($quick_post->video) ? $quick_post->video : $general_yt_video;

        //posting logs data
        $posting_logs_data = array();
        
        if (!empty($accounts)) { // Check all user ids
            foreach ($accounts as $key => $yt_post_profile) {

                //Initilize log user details
                $posting_logs_user_details  = array();

                $profile_id     = $yt_post_profile;
                $yt_post_app_id = $yt_post_profile; // Youtube App Id

                $app_access_token = $this->sap_yt_get_access_token_quickpost( $yt_post_app_id );

                // Load youtube class
                $youtube = $this->sap_load_youtube( $yt_post_app_id );

                //check twitter class is loaded or not
                if (!$youtube)
                    return false;
                    
                try {
                    
                     // Getting stored youtube app data
                     $parts = explode('.', $yt_post_app_id);
					 $yt_user_id = $parts[0];
                     
                     $yt_stored_app_data = isset($sap_yt_sess_data[$yt_user_id]) ? $sap_yt_sess_data[$yt_user_id] : array();

                     // Get user cache data
                     $user_cache_data = isset($yt_stored_app_data['sap_yt_cache']) ? $yt_stored_app_data['sap_yt_cache'] : array();

                     
                     //Youtube Log user details
                     $posting_logs_user_details['account_id']		= $profile_id;
                     $posting_logs_user_details['youtube_app_id']	= $yt_post_app_id;
                     
                     if( !empty( $profile_id ) && !empty( $app_access_token ) ) {
                        $app_access_token = json_decode($app_access_token);
                        $this->client->refreshToken($app_access_token->refresh_token);
                        $app_access_token = $this->client->getAccessToken();
                        
                        $postvideo = "";
                        
                        $enable_misc_relative_path = $this->settings->get_options('enable_misc_relative_path');

                        if( !empty( $video ) ){
                            if ( $enable_misc_relative_path == 'yes' ) {
                                $postvideo = SAP_APP_PATH.'uploads/' . $video;
                            }else{
                                $postvideo = SAP_IMG_URL . $video;
                            }
                        }                       

                        $file_data = pathinfo($postvideo);
                        if($file_data['extension'] == 'mp4' || $file_data['extension'] == 'mov' || $file_data['extension'] == 'mkv'){
                            $postvideo = SAP_APP_PATH.'uploads/'.$video;
                        }
                        
                        $this->client->setAccessToken($app_access_token);
                        
                        $snippet = new Google_Service_YouTube_VideoSnippet();
                        // $snippet->setTitle($content['title']); 
                        $snippet->setDescription($post_desc); 

                        $video_status = new Google_Service_YouTube_VideoStatus(); 
                        $video_status->privacyStatus = "public";
                        
                        $videoObj = new Google_Service_YouTube_Video(); 

                        $videoObj->setSnippet($snippet); 

                        $videoObj->setStatus($video_status);

                        $chunkSizeBytes = 1 * 1024 * 1024;

                        $this->client->setDefer(true); 

                        $request = $this->youtube->videos->insert("status,snippet", $videoObj);

                        $mediaObj = new Google_Http_MediaFileUpload( 
                            $this->client, 
                            $request, 
                            'video/*', 
                            null,
                            true,
                            $chunkSizeBytes
                        );

                        $mediaObj->setFileSize(filesize($postvideo));
                        
                        $status = false; 

                        $handle = fopen($postvideo, "rb"); 
                        
                        while( !$status && !feof($handle) ) {
                            $chunk = fread($handle, $chunkSizeBytes);
                            $status = $mediaObj->nextChunk($chunk);
                        }
                        fclose($handle);

                        $this->client->setDefer(false);

                        if( !empty( $status ) && isset( $status->id ) && !empty( $status->id ) ) {

                            $posting_logs_data['link to post'] = "http://www.youtube.com/watch?v=" . $status->id;
                            $posting_logs_data['message'] = $post_body;
                            $this->logs->add_log('youtube', $posting_logs_data, $posting_type, $user_id);
                            $this->quick_posts->update_post_meta($post_id,"sap_yt_link_to_post", $posting_logs_data['link to post']);

                            //record logs for post posted to twitter
                            $this->flash->setFlash( 'Youtube posted to User ID : ' . $profile_id  . ' Media Id: '.$status->id, 'success','',true );
                            $this->sap_common->sap_script_logs('Youtube posted to User ID : ' . $profile_id  . ' Media Id: '.$status->id, $user_id );
                            $this->sap_common->sap_script_logs('Youtube video url : ' . "http://www.youtube.com/watch?v=" . $status->id);

                            $status_meta_array[$status_meta_key] = array(
                                "status" => 'success'
                            );

                            $postflg    = true;
                        }

                        if( $postflg ) {
                            //posting logs store into database
                            $yt_posting['success'] = 1;

                            $status_meta_array[$status_meta_key] = array(
                                "status" => 'success',
                                "message" => 'Youtube posted to User ID : ' . $profile_id  . ' Media Id: '.$status->id,
                            );
    
                        } else {
    
                            $yt_posting['fail'] = 1;

                            //record logs for twitter posting exception
                            $this->flash->setFlash('Youtube posting exception : ' . $status->id, 'error','',true);
                            $this->sap_common->sap_script_logs('Youtube posting error : ' . $status->id, $profile_id );
                            $status_meta_array[$status_meta_key] = array(
                                "status" => 'error',
                                "message" => 'Youtube posting error : ' . $status->id
                            );
                        }
                    }

                    //return $result;
                } catch (Exception $e) {

                    //record logs exception generated
                    $this->flash->setFlash('Youtube error: ' . $e->getMessage(), 'error','',true);
                    $this->sap_common->sap_script_logs('Youtube posting time out, Please try again.', $user_id );
                    $this->sap_common->sap_script_logs('Youtube error: ' . $e->getMessage(), $user_id );
                    $status_meta_array[$status_meta_key] = array(
                        "status" => 'error',
                        "message" => 'Youtube error: ' . $e->getMessage()
                    );
                   
                    //return false;
                }
            }

            $this->quick_posts->update_post_meta($post_id,"sap_yt_posting_error", $status_meta_array);

        }

        //returning post flag
        return $postflg;
    }
}