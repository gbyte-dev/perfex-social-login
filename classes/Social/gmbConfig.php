<?php

/* Check the absolute path to the Social Auto Poster directory. */
if ( !defined( 'SAP_APP_PATH' ) ) {
    // If SAP_APP_PATH constant is not defined, perform some action, show an error, or exit the script
    // Or exit the script if required
    exit();
}

require_once( LIB_PATH . 'Social/gmb/vendor/autoload.php' );

/**
 * Google Business Posting
 *
 * Handles all the functions to post on google business
 * 
 * @package Social auto poster
 * @since 1.0.0
 */
class SAP_Gmb {

    private $db, $common, $flash, $twitter, $settings, $posts, $logs, $quick_posts, $sap_common, $mybusiness;

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

        //Set Database
        $this->db = $sap_db_connect;
        $this->settings = new SAP_Settings();
        $this->flash = new Flash();
        $this->posts = new SAP_Posts();
        $this->common = new Common();
        $this->logs = new SAP_Logs();
        $this->quick_posts = new SAP_Quick_Posts();
        $this->sap_common = $sap_common;

        $param = array(
            'client_id' => SAP_NEW_GMB_CLIENT_ID,
            'client_secret' => SAP_NEW_GMB_CLIENT_SECRET,
            'redirect_uri' => SAP_NEW_GMB_REDIRECT_URL,
            'scope' => SAP_NEW_GMB_SCOPE
        );

        $myBusiness = new Google_my_business($param);
        $this->mybusiness = $myBusiness;

        $this->sap_auto_poster_gmb_initialize($user_id);
    }

    /**
     * Assign Google My Business User's all Data to session
     * 
     * Handles to assign user's google my business data
     * to session & save to database
     * 
     * @package Social Auto Poster
     * @since 1.0.0
     */
    public function sap_auto_poster_gmb_initialize($user_id='') {

        $sap_auto_poster_gmb_sess_data = $this->settings->get_user_setting('sap_google_business_sess_data', $user_id);

        $user_accounts = array();
        $gmb_sess_data = array();

        if (isset($_GET['code']) && $_GET['wpw_auto_poster_gmb_verification'] == 'true') {

            //record logs for grant extended permission
            $this->sap_common->sap_script_logs('Google My Business Grant Extended Permission', $user_id);

            //record logs for get parameters set properly
            $this->sap_common->sap_script_logs('Get Parameters Set Properly.', $user_id);

            $access_token = $this->mybusiness->get_access_token($_GET['code']);
            
            $refresh_token = $access_token['refresh_token'];
            if (!empty($access_token)) {

                $access_token = $this->mybusiness->get_exchange_token($access_token['refresh_token']);

                if ($access_token['access_token'] != '') {

                    $accounts = $this->mybusiness->get_accounts($access_token['access_token']);
                   
                    if (isset($accounts['accounts']) && count($accounts['accounts']) > 0) {

                        $accountID = explode("/", $accounts['accounts'][0]['name']);
                        $user_accounts['auth_accounts'][$accountID[1]] = $accounts['accounts'][0]['name'];
                        $user_accounts['details'][$accountID[1]] = array(
                            'name' => $accounts['accounts'][0]['name'],
                            'display_name' => $accounts['accounts'][0]['accountName'],
                            'accountid' => $accountID[1],
                            'refresh_token' => $refresh_token,
                            'driver' => 'gmb',
                            'account_name' => $accounts['accounts'][0]['accountName'],
                            //'profile_picture' => $accounts['accounts'][0]['profilePhotoUrl'],
                        );

                        $locations = $this->mybusiness->get_locations($accounts['accounts'][0]['name'], $access_token['access_token']);
                        
                        $location_verified_status = false;

                        if (!empty($locations)) {

                            foreach ($locations['locations'] as $key => $value) {
                                if (isset($value['metadata']['hasVoiceOfMerchant']) && $value['metadata']['hasVoiceOfMerchant'] == '1' ) {
                                    $locationID = explode("/", $value['name']);
                                    $user_accounts[$accountID[1]][] = array(
                                        'id' => $locationID[1],
                                        'name' => $value['title'],
                                        'category' => $value['primaryCategory']['displayName'],
                                        'refresh_token' => $refresh_token,
                                        'locationname' => $value['name'],
                                    );
                                    $location_verified_status = true;
                                }
                            }

                            if (!$location_verified_status) {

                                $redirect_url = SAP_SITE_URL . '/settings/?wpw_auto_poster_gmb_verification=false';
                                $_SESSION['sap_active_tab'] = 'google-business';
                                header("Location: " . $redirect_url);
                                exit;
                            }
                        }

                        $gmb_sess_data[$accountID[1]] = array(
                            'sap_gmb_user_id' => $accountID[1],
                            'sap_gmb_user_accounts' => $user_accounts,
                        );

                        if (!empty($sap_auto_poster_gmb_sess_data)) {
                            $gmb_sess_data = array_merge($sap_auto_poster_gmb_sess_data, $gmb_sess_data);
                        } else {
                            $gmb_sess_data = $gmb_sess_data;
                        }
                       

                        $this->settings->update_user_setting('sap_google_business_sess_data', $gmb_sess_data);

                        $this->sap_common->sap_script_logs('Google My Business Session Data Updated to Options', $user_id);

                        $redirect_url = SAP_SITE_URL . '/settings/?gmb_verification=true';
                        $_SESSION['sap_active_tab'] = 'google-business';
                        header("Location: " . $redirect_url);
                    }
                }
            } else {
                return false;
            }
        } else {
            return false;
        }
    }

    /**
     * Google My Business
     * 
     * Check Users for Google My Business
     * 
     * @package Social Auto Poster
     * @since 1.0.0
     */
    public function sap_get_gmb_app_method_login_url() {

        $state = SAP_SITE_URL . '/settings/';
        return $this->mybusiness->gmb_login($state);
    }

    /**
     * Google My Business
     * 
     * Handles logic to Reset session of specific user
     * 
     * @package Social Auto Poster
     * @since 1.0.0
     */
    public function sap_gmb_reset_user_session() {

        $sap_gmb_app_id = $_GET['sap_gmb_userid'];

        $sap_auto_poster_gmb_sess_data = $this->settings->get_user_setting('sap_google_business_sess_data');
        // Unset particular app value data and update the option
        if (isset($sap_auto_poster_gmb_sess_data[$sap_gmb_app_id])) {

            unset($sap_auto_poster_gmb_sess_data[$sap_gmb_app_id]);
            $this->settings->update_user_setting('sap_google_business_sess_data', $sap_auto_poster_gmb_sess_data);
            $this->sap_common->sap_script_logs('Google My Business ' . $sap_gmb_app_id . ' Account Reset Successfully.', $user_id);
        }

        $_SESSION['sap_active_tab'] = 'google-business';
        $this->common->redirect('settings');
    }

    /**
     * Google My Business
     * 
     * Get Locations based on user accounts
     * 
     * @package Social Auto Poster
     * @since 1.0.0
     */
    public function sap_add_gmb_locations($user_id='') {

        // Taking some defaults
        $res_data = array();
        $sap_auto_poster_gmb_sess_data = $this->settings->get_user_setting('sap_google_business_sess_data',$user_id);

        if (is_array($sap_auto_poster_gmb_sess_data) && !empty($sap_auto_poster_gmb_sess_data)) {

            foreach ($sap_auto_poster_gmb_sess_data as $gmb_sess_key => $gmb_sess_data) {

                if (!empty($gmb_sess_data['sap_gmb_user_accounts'][$gmb_sess_key])) {

                    foreach ($gmb_sess_data['sap_gmb_user_accounts'][$gmb_sess_key] as $locations) {
                         
                        $locationname = isset($locations['locationname']) ? $locations['locationname'] : '';

                        $accountid = isset($locations['id']) ? $locations['id'] : '';

                        $accountname = isset($locations['name']) ? $locations['name'] : '';

                        $res_data[$locationname."/".$gmb_sess_key] = $accountid . ' | ' . $accountname;
                    }

                }
            }
        }
        return $res_data;
    }

    /**
     * Google My Business
     * 
     * Get user accounts of GMB
     * 
     * @package Social Auto Poster
     * @since 1.0.0
     */
    public function sap_get_gmb_accounts($user_id='') {

        // Taking some defaults
        $res_data = array();

        $sap_auto_poster_gmb_sess_data = $this->settings->get_user_setting('sap_google_business_sess_data',$user_id);
        if (is_array($sap_auto_poster_gmb_sess_data) && !empty($sap_auto_poster_gmb_sess_data)) {

            foreach ($sap_auto_poster_gmb_sess_data as $gmb_key => $gmb_sess_data) {

                if ($gmb_key == $gmb_sess_data['sap_gmb_user_id']) {
                    $res_data[$gmb_key] = $gmb_sess_data['sap_gmb_user_accounts']['details'][$gmb_key];
                }
            }
        }

        return $res_data;
    }

    /**
     * Google My Business
     * 
     * Handles posts to send on GMB
     * 
     * @package Social Auto Poster
     * @since 1.0.0
     */
    public function sap_send_post_to_gmb($post_id) {

        $postflg = false;
        $post = $this->posts->get_post($post_id, true);

        $user_id = isset( $post->user_id ) ? $post->user_id : '';

        $sap_gmb_options = $this->settings->get_user_setting('sap_google_business_options', $user_id);
        $sap_gmb_sess_data = $this->settings->get_user_setting('sap_google_business_sess_data', $user_id);

         // General setting
        $sap_general_options = $this->settings->get_user_setting('sap_general_options',$user_id);

        $link_timestamp = isset($sap_general_options['timestamp_link']) ? "?".time() : '';
        
        $posting_log = array();
        $gmb_user_locations = array();
        if ( !empty($sap_gmb_sess_data) ) {

            //Code for assigning locations - if empty it will consider locations from General settings
            $post_gmb_locations = $this->posts->get_post_meta($post_id, '_sap_gmb_post_accounts');
            $post_gmb_button_type = $this->posts->get_post_meta($post_id, '_sap_gmb_post_button_type');
            $sap_post_link = $this->posts->get_post_meta($post_id, 'sap_gmb_custom_link');
            $sap_custom_post_link = $this->posts->get_post_meta($post_id, '_sap_gmb_post_link');

            if (!empty($post_gmb_locations) && is_array($post_gmb_locations)) {

                $gmb_user_locations = $post_gmb_locations;
            } else {

                if (!empty($sap_gmb_options['google_business_post_users']) && !empty($sap_gmb_options['google_business_post_users'])) {

                    $gmb_user_locations = $sap_gmb_options['google_business_post_users'];
                }
            }


            //Check Accounts exist
            if (empty($gmb_user_locations)) {
                $this->flash->setFlash('Google My Business location is not selected.', 'error' ,'',true);
                $this->sap_common->sap_script_logs('Google My Business location is not selected.', $user_id);
                return false;
            }


            /* Code for checking the button type - from the main settings */
            $link_button_text = '';
            if (!empty($post_gmb_button_type)) {

                $link_button_text = $post_gmb_button_type;
            } else {

                if (!empty($sap_gmb_options['google_business_button_type'])) {

                    $link_button_text = $sap_gmb_options['google_business_button_type'];
                }
            }

            //Code for populating content - conditionally
            $post_title = $this->posts->get_post_meta($post_id, '_sap_gmb_post_msg');
            $post_img = $this->posts->get_post_meta($post_id, '_sap_gmb_post_image');

            $title = !empty($post_title) ? html_entity_decode(strip_tags($post_title),ENT_QUOTES) : html_entity_decode(strip_tags($post->body),ENT_QUOTES);


            
            $postlink = !empty($post->share_link) ? $post->share_link : $sap_custom_post_link;

            if(!empty($postlink)) {
                $postlink = $postlink."".$link_timestamp;
            }

            $postimage = !empty($post_img) ? $post_img : $post->img;

            $customlink = !empty($postlink) ? 'true' : 'false';
            $postlink = $this->common->sap_script_short_post_link($postlink, $customlink, 'gmb', 'google_business', $user_id);
            
            if (empty($post_img) && empty($post->img)) {
                if (!empty($sap_gmb_options['gmb_image'])) {
                    $postimage = $sap_gmb_options['gmb_image'];
                }
            } else {
                $postimage = !empty($post_img) ? $post_img : $post->img;
            }

            if (empty($postlink) && $link_button_text != 'CALL') {
                $this->flash->setFlash('Google My Business : link is requires for posting.', 'error' ,'',true);
                $this->sap_common->sap_script_logs('Google My Business : link is requires for posting.', $user_id);
                return false;
            }

            if (empty($postimage)) {
                $this->flash->setFlash('Google My Business : Image for posting is not selected.', 'error','',true);
                $this->sap_common->sap_script_logs('Google My Business : Image for posting is not selected.', $user_id);
                return false;
            }
            
            // GMB limit 1500 character per post
            if (!empty($title))
                $title = $this->posts->sap_limit_character($title, 1500);

            $content = array(
                'title' => $title,
                'submitted-url' => $postlink,
                'submitted-image-url' => SAP_IMG_URL . $postimage,
            );
            
            //initial value of posting flag
            try {
                
                if (!empty($gmb_user_locations)) {

                    foreach ($gmb_user_locations as $gmb_user_location) {
                        
                        $proxy = '';
                        $gmb_users_id = $gmb_user_location;
                        $gmb_users_id_array = explode("/", $gmb_user_location);

                        $allLocations = $sap_gmb_sess_data[$gmb_users_id_array[2]]['sap_gmb_user_accounts'][$gmb_users_id_array[2]];
                        $locIDs = array_column( $allLocations, 'id' );

                        $gmb_array_key = array_search( $gmb_users_id_array[1], $locIDs );
                        $gmb_array = $allLocations[$gmb_array_key];
                        

                        $posting_logs_user_details['display_name'] = $gmb_array['name'];
                        $posting_logs_user_details['id'] = $gmb_array['id'];
                        $refresh_token = $gmb_array['refresh_token'];
                        $access_token = $this->mybusiness->get_exchange_token($refresh_token);
                        
            
                       // if (!empty($gmb_array)) {
                            //foreach ($gmb_array as $key => $send_gmb) {
                                if (!empty($access_token) && $access_token['access_token'] != '') {
                                    if (isset($gmb_array['id']) && !empty($gmb_array['id'])) {
                                        $post_data = array(
                                            'topicType' => "STANDARD",
                                            'languageCode' => "en_US",
                                            'summary' => $content['title'],
                                            'callToAction' => array(
                                                'actionType' => $link_button_text,
                                                'url' => $content['submitted-url'],
                                            ),
                                            'media' => array(
                                                'mediaFormat' => 'PHOTO',
                                                'sourceUrl' => $content['submitted-image-url'],
                                            ),
                                            'name' => $content['title'],
                                        );

                                        //$response = $this->mybusiness->post_local_post($gmb_array['id'] . '/localPosts', $access_token['access_token'], $post_data);                                
                                        $response = $this->mybusiness->post_local_post('accounts/'.$gmb_users_id_array[2].'/locations/'.$gmb_array['id'] . '/localPosts', $access_token['access_token'], $post_data);                                       

                                        $state = isset( $response['state'] ) ? $response['state'] : '';

                                        if (!empty($response) && ($state == 'LIVE' || $state == 'PROCESSING' )) {
                                            $postflg = true;
                                            $gmb_posting['success'] = 1;
                                            $this->flash->setFlash('Google My Business : Post sucessfully posted on - ' . $gmb_array['name'], 'success','',true);
                                            $this->sap_common->sap_script_logs('Google My Business : Post sucessfully posted on - ' . $gmb_array['name'], $user_id);
                                        } else {
                                            if ($response['error']['details'][0]['errorDetails'][0]['field'] == 'photos.additional_photo_urls') {

                                                $postflg = false;
                                                $gmb_posting['fail'] = 0;
                                                $this->sap_common->sap_script_logs('Google My Business error : ' . $response['error']['details'][0]['errorDetails'][0]['message'], $user_id);
                                                $this->flash->setFlash('Something was wrong while posting on Google My Business - ' . $response['error']['details'][0]['errorDetails'][0]['message'], 'error' ,'',true);
                                            } else if($response['error']['details'][0]['errorDetails'][0]['field'] == 'media[0].source_url'){
                                                $postflg = false;
                                                $gmb_posting['fail'] = 0;
                                                $this->sap_common->sap_script_logs('Google My Business error : The image link you have entered is invalid', $user_id);
                                                $this->flash->setFlash('Google My Business error : The image link you have entered is invalid ', 'error' ,'',true);
                                            } else {

                                                $postflg = false;
                                                $gmb_posting['fail'] = 0;
                                                $this->sap_common->sap_script_logs('Google My Business error : Something was wrong while posting on Google My Business', $user_id);
                                                $this->flash->setFlash('Google My Business error : Something was wrong while posting on Google My Business', 'error','',true);
                                            }
                                        }
                                    }
                                //}
                            //}
                        }

                       
                        if ($postflg) {
                            $posting_log = array(
                                'link' => $content['submitted-url'],
                                'image' => $content['submitted-image-url'],
                                'message' => $content['title'],
                                'account name' => $sap_gmb_sess_data[$gmb_users_id_array[1]]['sap_gmb_user_accounts'][$gmb_users_id_array[1]]['display_name'],
                                'link to post' => 'https://business.google.com/posts/l/' . $gmb_users_id_array[1]
                            );
                            $this->sap_common->sap_script_logs('Google My Business post data : ' . var_export($posting_log, true), $user_id);
                            $this->logs->add_log('Google My Business', $posting_log, 1, $user_id);
                        }

                        
                         
                    }
                }
            } catch (Exception $e) {
                // display error notice on post page
                $this->sap_common->sap_script_logs('Google My Business error : ' . $e->getMessage(), $user_id);
                $this->flash->setFlash('Google My Business error :' . $e->getMessage(), 'error','',true);
                return false;
            }
        } else {
            $this->sap_common->sap_script_logs('Google My Business : Please select location before posting to the Google My Business.', $user_id);
            //record logs when grant extended permission not set
            $this->flash->setFlash('Google My Business : Please select location before posting to the Google My Business.', 'error','',true);
        }
        return $gmb_posting;
    }

    /**
     * Google My Business
     * 
     * Handles quick post to send on GMB
     * 
     * @package Social Auto Poster
     * @since 1.0.0
     */
    public function sap_send_quick_post_to_gmb($post_id) {

        $quick_post = $this->quick_posts->get_post($post_id, true);

        $user_id    = isset( $quick_post->user_id ) ? $quick_post->user_id : '';

        // General setting
        $sap_general_options = $this->settings->get_user_setting('sap_general_options',$user_id);

        $link_timestamp = isset($sap_general_options['timestamp_link']) ? "?".time(): '';

        $sap_gmb_options = $this->settings->get_user_setting('sap_google_business_options', $user_id);

        $sap_gmb_sess_data = $this->settings->get_user_setting('sap_google_business_sess_data', $user_id);

        $accountID_datas = array_keys($sap_gmb_sess_data);      
       
        $posting_account = $sap_gmb_sess_data[$accountID_datas[0]]['sap_gmb_user_id'];      

        $postflg = false;
        $status_meta_array = array();
        $gmb_user_locations = array();
        $posting_log = array();

        if (!empty($sap_gmb_sess_data)) {

            $sap_networks_meta = $this->quick_posts->get_post_meta($post_id, 'sap_networks');

            if (!empty($sap_networks_meta['gmb_locations'])) {

                $gmb_user_locations = $sap_networks_meta['gmb_locations'];
            } else {

                $gmb_user_locations = !empty($sap_gmb_options['google_business_post_users']) ? $sap_gmb_options['google_business_post_users'] : '';
            }

            /* Code for checking the button type - from the main settings */
            $link_button_text = '';
            if (!empty($sap_networks_meta['gmb_button_type'])) {

                $link_button_text = $sap_networks_meta['gmb_button_type'];
            } else {

                $link_button_text = $sap_gmb_options['google_business_button_type'];
            }

            $title = !empty($quick_post->message) ? html_entity_decode(strip_tags($quick_post->message),ENT_QUOTES) : '';
            
            $postlink = !empty($quick_post->share_link) ? $quick_post->share_link : '';

            if(!empty($postlink)) {
                $postlink = $postlink."".$link_timestamp;
            }

            $postimage = !empty($quick_post->image) ? $quick_post->image : '';

            $customlink = !empty($postlink) ? 'true' : 'false';
            $postlink = $this->common->sap_script_short_post_link($postlink, $customlink, 'gmb', 'google_business', $user_id);

            if ($postimage == '' || empty($postimage)) {
                if (!empty($sap_gmb_options['gmb_image'])) {
                    $postimage = $sap_gmb_options['gmb_image'];
                }
            }

            if (empty($postlink) && $link_button_text != 'CALL' ) {
                $this->sap_common->sap_script_logs('Google My Business - link is requires for posting.', $user_id);
                $this->flash->setFlash('Google My Business - link is requires for posting.', 'error','',true);
                $status_meta_array[] = array(
                    "status" => 'error',
                    "message" => 'Google My Business - link is requires for posting.'
                );
                $this->quick_posts->update_post_meta($post_id, "sap_gmb_posting_error", $status_meta_array);
                return false;
            }

            if (empty($postimage)) {
                $this->sap_common->sap_script_logs('Google My Business - Image for posting is not selected.',$user_id);
                $this->flash->setFlash('Google My Business - Image for posting is not selected.', 'error','',true);
                $status_meta_array[] = array(
                    "status" => 'error',
                    "message" => 'Google My Business - Image for posting is not selected.'
                );
                $this->quick_posts->update_post_meta($post_id, "sap_gmb_posting_error", $status_meta_array);
                return false;
            }

            if (empty($link_button_text)) {

                $this->sap_common->sap_script_logs('Google My Business - Button Type for posting is not selected.',$user_id);
                
                $this->flash->setFlash('Google My Business - Button Type for posting is not selected.', 'error','',true);

                $status_meta_array[] = array(
                    "status" => 'error',
                    "message" => 'Google My Business - Button Type for posting is not selected.'
                );
                $this->quick_posts->update_post_meta($post_id, "sap_gmb_posting_error", $status_meta_array);
                return false;
            }

            // GMB post limit 1500 character per post
            if (!empty($title))
                $title = $this->posts->sap_limit_character($title, 1500);

            $content = array(
                'title' => $title,
                'submitted-url' => $postlink,
                'submitted-image-url' => SAP_IMG_URL . $postimage,
            );
            
            try {


                if (!empty($gmb_user_locations)) {

                    foreach ($gmb_user_locations as $gmb_user_location) {

                        $proxy = '';
                        $gmb_users_id = $gmb_user_location;
                        $gmb_users_id_array = explode("/", $gmb_user_location);                        

                        $allLocations = $sap_gmb_sess_data[$gmb_users_id_array[2]]['sap_gmb_user_accounts'][$gmb_users_id_array[2]];
                        $locIDs = array_column( $allLocations, 'id' );

                        $gmb_array_key = array_search( $gmb_users_id_array[1], $locIDs );
                        $gmb_array = $allLocations[$gmb_array_key];

                        $posting_logs_user_details['display_name'] = $gmb_array['name'];
                        $posting_logs_user_details['id'] = $gmb_array['id'];
                        $refresh_token = $gmb_array['refresh_token'];
                        $access_token = $this->mybusiness->get_exchange_token($refresh_token);

                        //if (!empty($gmb_array)) {
                            //foreach ($gmb_array as $send_gmb) {
                              

                                if (!empty($access_token) && $access_token['access_token'] != '') {
                                    if (isset($gmb_users_id) && !empty($gmb_users_id)) {
                                        $post_data = array(
                                            'topicType' => "STANDARD",
                                            'languageCode' => "en_US",
                                            'summary' => $content['title'],
                                            'callToAction' => array(
                                                'actionType' => $link_button_text,
                                                'url' => $content['submitted-url'],
                                            ),
                                            'media' => array(
                                                'mediaFormat' => 'PHOTO',
                                                'sourceUrl' => $content['submitted-image-url'],
                                            ),
                                            'name' => $content['title'],
                                        );

                                       
                                        
                                        $response = $this->mybusiness->post_local_post('accounts/'.$gmb_users_id_array[2].'/locations/'.$gmb_array['id'] . '/localPosts', $access_token['access_token'], $post_data);
                                        
                                         
                                        $state = isset( $response['state'] ) ? $response['state'] : '';

                                        if (!empty($response) && ($state == 'LIVE' || $state == 'PROCESSING' ) ) {
                                            $postflg = true;
                                            $gmb_posting['success'] = 1;
                                            $this->flash->setFlash('Google My Business : Post successfully posted on - ' . $gmb_array['name'], 'success','',true);
                                            $this->sap_common->sap_script_logs('Google My Business : Post successfully posted on - ' . $gmb_array['name'], $user_id);
                                        } else {
                                            if ($response['error']['details'][0]['errorDetails'][0]['field'] == 'photos.additional_photo_urls') {

                                                $postflg = false;
                                                $gmb_posting['fail'] = 0;
                                                $this->sap_common->sap_script_logs('Google My Business error : ' . $response['error']['details'][0]['errorDetails'][0]['message'],$user_id);
                                                $this->flash->setFlash('Something was wrong while posting on Google My Business - ' . $response['error']['details'][0]['errorDetails'][0]['message'], 'error' ,'',true);
                                            } else if($response['error']['details'][0]['errorDetails'][0]['field'] == 'media[0].source_url'){
                                                $postflg = false;
                                                $gmb_posting['fail'] = 0;
                                                $this->sap_common->sap_script_logs('Google My Business error : The image link you have entered is invalid', $user_id);
                                                $this->flash->setFlash('Google My Business error : The image link you have entered is invalid ', 'error' ,'',true);
                                            } else {

                                                $postflg = false;
                                                $gmb_posting['fail'] = 0;
                                                $this->sap_common->sap_script_logs('Google My Business error : Something was wrong while posting on Google My Business',$user_id);
                                                $this->flash->setFlash('Google My Business error : Something was wrong while posting on Google My Business', 'error','',true);
                                            }
                                        }
                                    }
                                //}
                            //}
                        }

                        if ($postflg) {
                            $posting_log = array(
                                'link' => $content['submitted-url'],
                                'image' => $content['submitted-image-url'],
                                'message' => $content['title'],
                                'account name' => $sap_gmb_sess_data[$gmb_users_id_array[1]]['sap_gmb_user_accounts'][$gmb_users_id_array[1]]['display_name'],
                                'link to post' => 'https://business.google.com/posts/l/' . $gmb_users_id_array[1]
                            );
                            $this->sap_common->sap_script_logs('Google My Business post data : ' . var_export($posting_log, true),$user_id);
                            $this->logs->add_log('Google My Business', $posting_log, 1, $user_id);
                        }

                        
                         
                    }
                }
            } catch (Exception $e) {
                $this->sap_common->sap_script_logs('Google My Business error : ' .$e->getMessage(),$user_id);
                // display error notice on post page  
                $this->flash->setFlash('Google My Business error - ' . $e->getMessage(), 'error','',true);

                $status_meta_array[] = array(
                    "status" => 'error',
                    "message" => $e->getMessage()
                );

                $this->quick_posts->update_post_meta($post_id, "sap_gmb_posting_error", $status_meta_array);
                return false;
            }
        } else {
            $this->sap_common->sap_script_logs('Google My Business : Please select location before posting to the Google My Business.', $user_id);
            //record logs when grant extended permission not set
            $this->flash->setFlash('Google My Business : Please select location before posting to the Google My Business.', 'error','',true);

            $status_meta_array[] = array(
                "status" => 'error',
                "message" => 'Please select location before posting to the Google My Business.'
            );
            $this->quick_posts->update_post_meta($post_id, "sap_gmb_posting_error", $status_meta_array);
        }

        return $gmb_posting;
    }

}
