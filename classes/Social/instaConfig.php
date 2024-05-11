<?php

/* Check the absolute path to the Social Auto Poster directory. */
if ( !defined( 'SAP_APP_PATH' ) ) {
    // If SAP_APP_PATH constant is not defined, perform some action, show an error, or exit the script
    // Or exit the script if required
    exit();
}

/**
 * Instagram posting
 *
 * @package Social auto poster
 * @since 1.0.0
 */
require_once LIB_PATH . "Social/facebook/autoload.php";

// Include required libraries
use Facebook\Facebook;
use Facebook\Exceptions\FacebookResponseException;
use Facebook\Exceptions\FacebookSDKException;

class SAP_Instagram {


    public $fbPermissions = ['pages_manage_posts','pages_show_list','instagram_basic','instagram_content_publish','business_management'];
    public $facebook, $settings, $flash, $posts, $common,$instagram, $logs, $quick_posts, $sap_common;
    public $grantaccessToken, $helper;

    public function __construct($user_id='') {
        global $sap_common;
        if (!class_exists('SAP_Quick_Posts')) {
            require_once( CLASS_PATH . 'Quick_Posts.php' );
        }

        if (!class_exists('SAP_Posts')) {
            require_once( CLASS_PATH . 'Posts.php' );
        }

        $this->settings = new SAP_Settings();
        $this->flash = new Flash();
        $this->posts = new SAP_Posts();
        $this->common = new Common();
        $this->logs = new SAP_Logs();
        $this->quick_posts = new SAP_Quick_Posts();
        $this->sap_common = $sap_common;

        /* Initialize the function */
        $this->sap_fb_initialize_for_insta($user_id);
        
    }

     /**
     * Get Social Auto poster Screen ID
     *
     * Handles to get social auto poster screen id
     *
     * @package Social Auto Poster
     * @since 1.0.0
     */

    public function sap_get_fb_instagram_accounts($data_type = false, $user_id='') {

        // Taking some defaults
        $res_data = array();

        // Get stored fb app grant data
        $sap_insta_sess_data = $this->settings->get_user_setting('sap_fb_sess_data_for_insta',$user_id);
       
        if (is_array($sap_insta_sess_data) && !empty($sap_insta_sess_data)) {


            foreach ($sap_insta_sess_data as $fb_sess_key => $fb_sess_data) {

                $sap_instagram_accounts = $sap_insta_sess_data[$fb_sess_key]['sap_instagram_accounts'];
                $inta_accounts = array();

                if(!empty($sap_instagram_accounts) && is_array($sap_instagram_accounts)){

                    foreach(  $sap_instagram_accounts as $key => $insta_accounts ) {

                        $inta_accounts[$key."|".$fb_sess_key] = $insta_accounts;
                       
                    } 

                    $res_data[$fb_sess_key] = $inta_accounts;
                }
            }
        }
        return $res_data;

    }    

    /**
     * Include Facebook Class
     *
     * Handles to load facebook class
     *
     * @package Social Auto Poster
     * @since 1.0.0
     */
    public function sap_load_facebook($app_id = false, $user_id='') {
        //Getting facebook apps
        $fb_apps = $this->sap_get_fb_apps($user_id);
        /// If app id is not passed then take first fb app data
        if (empty($app_id)) {
            $fb_apps_keys = array_keys($fb_apps);
            $app_id = reset($fb_apps_keys);
        }
        //// Check facebook application id and application secret is not empty or not
        if (!empty($app_id) && !empty($fb_apps[$app_id])) {
            $this->facebook = new Facebook(array(
                'app_id' => $app_id,
                'app_secret' => $fb_apps[$app_id],
                'cookie' => true,
                'default_graph_version' => SAP_NEW_FB_APP_VERSION,
            ));
            $this->helper = $this->facebook->getRedirectLoginHelper();
            return true;
        } else {
            return false;
        }
    }

    /**
     * Fetch Instagram User name
     *
     * Handles to load facebook class
     *
     * @package Social Auto Poster
     * @since 1.0.0
    */

    public function sap_fetch_instagram_account_usernames( $all_user_ids , $fb_access_token ){

        $insta_user_data = array();
        if( !empty( $all_user_ids ) ) {

            $curl = curl_init();
            foreach( $all_user_ids as $key => $user_data ) {

                    $get_api = 'https://graph.facebook.com/v14.0/'.$user_data.'?fields=name,username&access_token='.$_SESSION['sap_insta_user_accounts']['auth_tokens'][$user_data];

                    curl_setopt_array($curl, array(
                    CURLOPT_URL => $get_api,
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_ENCODING => '',
                    CURLOPT_MAXREDIRS => 10,
                    CURLOPT_TIMEOUT => 0,
                    CURLOPT_FOLLOWLOCATION => true,
                    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                    CURLOPT_CUSTOMREQUEST => 'GET',
                    CURLOPT_HTTPHEADER => array(
                        'Cookie: PHPSESSID=477cf3cd663b806d142219499d7bfa20'
                    ),
                    ));
                    $response = curl_exec($curl);
                    $response = json_decode($response);
                    $result = (array) $response;
                    if(!empty( $result['username'] )){
                        $insta_user_data[$user_data] = $result['username'];
                    }
            }
            curl_close($curl);
        }
        return $insta_user_data;
    }


    /**
     * Fetch Instagram User Data
     *
     * Handles to load facebook class
     *
     * @package Social Auto Poster
     * @since 1.0.0
    */

    public function sap_fb_fetch_instagram_accounts( $facebook_app_id  ){

        

        $all_instagram_details = array();
        $fb_access_token       = $_SESSION['long_access_token'];
        $all_intagram_user_ids = array();
        $insta_user_data = array();

        if(!empty($_SESSION['sap_insta_user_accounts']['auth_accounts']) && is_array($_SESSION['sap_insta_user_accounts']['auth_accounts'])){
         
            $curl = curl_init();

            foreach( $_SESSION['sap_insta_user_accounts']['auth_accounts'] as $page_id => $page_data ){
                    if( $page_id == '0') {
                        continue;
                    }
                   $get_api = 'https://graph.facebook.com/v14.0/'.$page_id.'?fields=instagram_business_account&access_token='.$_SESSION['sap_insta_user_accounts']['auth_tokens'][$page_id];

                    curl_setopt_array($curl, array(
                    CURLOPT_URL => $get_api,
                    CURLOPT_RETURNTRANSFER => true,
                    // CURLOPT_ENCODING => '',
                    // CURLOPT_MAXREDIRS => 10,
                    CURLOPT_TIMEOUT => 0,
                    // CURLOPT_FOLLOWLOCATION => true,
                    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                    // CURLOPT_CUSTOMREQUEST => 'GET',
                    // CURLOPT_HTTPHEADER => array(
                    //     'Cookie: PHPSESSID=477cf3cd663b806d142219499d7bfa20'
                    // ),
                    ));
                    $response = curl_exec($curl);
                    $response = json_decode($response);

                    $result = (array) $response;

                    $ig_user_id = '';
                    if( !array_key_exists('error',$result) ){
                        if (array_key_exists('instagram_business_account', $result)) {

                            $ig_user_id = $result['instagram_business_account']->id;
                        } 
                        
                        $all_intagram_user_ids[] = $ig_user_id;
                        if( !empty( $ig_user_id ) ) {
                            $get_api = 'https://graph.facebook.com/v14.0/'.$ig_user_id.'?fields=name,username&access_token='.$_SESSION['sap_insta_user_accounts']['auth_tokens'][$page_id];

                            curl_setopt_array($curl, array(
                            CURLOPT_URL => $get_api,
                            CURLOPT_RETURNTRANSFER => true,
                            CURLOPT_ENCODING => '',
                            CURLOPT_MAXREDIRS => 10,
                            CURLOPT_TIMEOUT => 0,
                            CURLOPT_FOLLOWLOCATION => true,
                            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                            CURLOPT_CUSTOMREQUEST => 'GET',
                            CURLOPT_HTTPHEADER => array(
                                'Cookie: PHPSESSID=477cf3cd663b806d142219499d7bfa20'
                            ),
                            ));
                            $response = curl_exec($curl);
                            $response = json_decode($response);
                            
                            $result = (array) $response;
                            if(!empty( $result['username'] )){
                                $insta_user_data[$ig_user_id] = $result['username'];
                            }
                        }
                    }
            }
            
            
        }

        curl_close($curl);
        $all_instagram_details = $insta_user_data;
        return $all_instagram_details; 
    }

    /**
     * Assign Facebook User's all Data to session
     *
     * Handles to assign user's facebook data
     * to sessoin & save to database
     *
     * @package Social Auto Poster
     * @since 1.0.0
     */
    public function sap_fb_initialize_for_insta($user_id='') {
        
        // Get global SAP facebook options
        $sap_facebook_options = $this->settings->get_user_setting('sap_facebook_options',$user_id);

        if (isset($_GET['wpw_auto_poster_insta_app_method']) && $_GET['wpw_auto_poster_insta_app_method'] == 'appmethod') {

            if (isset($_GET['access_token']) && $_GET['access_token'] != '' && $_GET['wpw_insta_grant'] == 'true') {

                $this->grantaccessToken = $_GET['access_token'];
                try {

                    $this->facebook = new Facebook(array(
                        'app_id' => SAP_NEW_FB_APP_METHOD_ID_FOR_INSTA,
                        'app_secret' => SAP_NEW_FB_APP_METHOD_SECRET_FOR_INSTA,
                        'cookie' => true,
                        'default_graph_version' => SAP_NEW_FB_APP_VERSION,
                    ));

                    $profileRequest = $this->facebook->get('/me?fields=name,first_name,last_name', $this->grantaccessToken);
                    $user = $profileRequest->getGraphNode()->asArray();



                } catch (FacebookResponseException $e) {
                    echo $this->sap_common->lang('social_fbconfig_graph_error').''. $e->getMessage();
                    $this->sap_common->sap_script_logs('Instagram error : ' . $e->getMessage(), $user_id);
                    exit;
                } catch (FacebookSDKException $e) {
                    echo $this->sap_common->lang('social_fbconfig_sdk_error').''. $e->getMessage();
                    $this->sap_common->sap_script_logs('Instagram error : ' . $e->getMessage(), $user_id);
                    exit;
                }
              
                if (!empty($user)) {
                    $this->sap_common->sap_script_logs('Facebook User ID : ' . $user['id'], $user_id);

                    try {
                        

                        $client =  $this->facebook->getOAuth2Client();
                        $accessTokenLong = $client->getLongLivedAccessToken($this->grantaccessToken);

                        $_SESSION['sap_insta_user_cache'] = $user;
                        $this->_user_cache = $_SESSION['sap_insta_user_cache'];
                        $_SESSION['sap_insta_user_id'] = $user['id'];
                        $_SESSION['sap_insta_user_accounts'] = $this->sap_fetch_accounts($user_id);

                        $_SESSION['long_access_token'] = $accessTokenLong->getValue();
                        $instagram_accounts_list = $this->sap_fb_fetch_instagram_accounts( $_SESSION['sap_insta_user_id']  );
                        $_SESSION['instagram_accounts'] = $instagram_accounts_list;
                        $sap_insta_sess_data = $this->settings->get_user_setting('sap_fb_sess_data_for_insta',$user_id);
                        
                        if (empty($sap_insta_sess_data)) {
                            $sap_insta_sess_data = array();
                        }

                        if (!isset($sap_insta_sess_data[$user['id']])) {

                            $sess_data = array(
                                'sap_insta_user_cache' => $_SESSION['sap_insta_user_cache'],
                                'sap_insta_user_id' => $_SESSION['sap_insta_user_id'],
                                'sap_insta_user_accounts' => $_SESSION['sap_insta_user_accounts'],
                                'sap_instagram_accounts' => $_SESSION['instagram_accounts'],
                                'insta_' . $user['id'] . '_long_access_token' => $_SESSION['long_access_token'],
                                'insta_' . $user['id'] . '_code' => $_GET['code'],
                                'insta_' . $user['id'] . '_access_token' => $_GET['access_token'],
                                'insta_' . $user['id'] . '_user_id' => $user['id'],
                              
                            );


                            $key_user_id = strval($user['id']);
                            $sap_insta_sess_data[$key_user_id] = $sess_data;

                            // Update session data to options
                            $this->settings->update_user_setting('sap_fb_sess_data_for_insta', $sap_insta_sess_data);
                            $this->sap_common->sap_script_logs('Facebook Session Data Updated to Options',$user_id);
                        }
                        $_SESSION['display_insta_post_msg'] = 'Grant Extended Permission Successfully.';
                        $this->sap_common->sap_script_logs('Facebook Grant Extended Permission Successfully.',$user_id);
                    } catch (FacebookApiException $e) {
                        //record logs exception generated
                        $this->sap_common->sap_script_logs('Facebook Exception : ' . $e->__toString(), $user_id);
                        //user is null
                        $user = null;
                    }
                }
                $_SESSION['sap_active_tab'] = 'instagram';
                header("Location:" . SAP_SITE_URL . "/settings/");
                exit;
            } else if(isset($_GET['wpw_insta_grant']) && $_GET['wpw_insta_grant'] == 'false' && isset($_GET['error']) && $_GET['error'] != ''){
                $this->flash->setFlash('Facebook error : ' . $_GET['error'], 'error' ,'',true);
                $this->sap_common->sap_script_logs('Facebook Exception : '.$_GET['error'], $user_id);
                
                $_SESSION['sap_active_tab'] = 'instagram';
                header("Location:" . SAP_SITE_URL . "/settings/");
                exit;
            }
        }    
        
    }

    /**
     * Facebook Login URL Using APP method
     *
     * Getting the login URL from Facebook.
     * Facebook App method
     *
     * @package Social Auto Poster
     * @since 1.0.0
     */
    public function sap_auto_poster_get_fb_app_method_login_url($user_id='') {


        //load facebook class
        $facebook = $this->sap_auto_poster_load_facebook_app_method(SAP_NEW_FB_APP_METHOD_ID_FOR_INSTA, $user_id);
        //check facebook class is exis or not
        if (!$facebook)
            return false;

        $redirect_URL = SAP_NEW_FB_APP_REDIRECT_URL_FOR_INSTA;

        $loginUrl = $this->helper->getLoginUrl($redirect_URL, $this->fbPermissions);
        $loginUrl = $loginUrl . '&state=' . SAP_SITE_URL;

        return $loginUrl;
    }

    /**
     * Include Facebook Class
     *
     * Handles to load facebook class with the use of fix app id and secret
     * Facebook App method
     *
     * @package Social Auto Poster
     * @since 1.0.0
     */
    public function sap_auto_poster_load_facebook_app_method($app_id = false, $user_id='') {

        $sap_fb_options = $this->settings->get_user_setting('sap_facebook_options', $user_id);
        // Check facebook application id and application secret is not empty or not
        if (!empty(SAP_NEW_FB_APP_METHOD_ID_FOR_INSTA) && !empty(SAP_NEW_FB_APP_METHOD_SECRET_FOR_INSTA)) {

            $this->facebook = new Facebook(array(
                'app_id' => SAP_NEW_FB_APP_METHOD_ID_FOR_INSTA,
                'app_secret' => SAP_NEW_FB_APP_METHOD_SECRET_FOR_INSTA,
                'cookie' => true,
                'default_graph_version' => SAP_NEW_FB_APP_VERSION,
            ));

            // Get redirect login helper
            $this->helper = $this->facebook->getRedirectLoginHelper();
            return true;
        } else {
            return false;
        }
        // Check facebook application id and application secret is not empty or not
    }

    /**
     * Facebook Login URL
     *
     * Getting the login URL from Facebook.
     *
     * @package Social Auto Poster
     * @since 1.0.0
     */
    public function sap_get_fb_login_url($app_id = false,$user_id='') {
//load facebook class
        $facebook = $this->sap_load_facebook($app_id, $user_id);

        if (!$facebook)
            return false;

        $portvalue = $this->common->is_ssl() ? 'https://' : 'http://';
        $redirect_URL = $portvalue . $_SERVER['SERVER_NAME'] . $_SERVER['REQUEST_URI'];
        $url_args = '?grant_fb=true&fb_app_id=' . $app_id;
        $redirect_URL = $redirect_URL . $url_args;

        $loginUrl = $this->helper->getLoginUrl($redirect_URL, $this->fbPermissions);
        return $loginUrl;
    }

    /**
     * User Data
     *
     * Getting the cached user data from the connected
     * Facebook user (back end).
     *
     * @package Social Auto Poster
     * @since 1.0.0
     */
    public function sap_get_fb_user_data() {

        if (!empty($this->_user_cache)) {
            return $this->_user_cache;
        }
    }

    /**
     * Fetching Accounts
     *
     * Fetching all the associated accounts from the connected
     * Facebook user (site admin).
     *
     * @package Social auto poster
     * @since 1.0.0
     */
    public function sap_fetch_accounts($user_id='') {
        $api = array();
        $page_tokens = array();
        $page_tokens = $this->sap_get_pages_tokens($user_id);

        $page_tokens = isset($page_tokens->data) ? $page_tokens->data : array();

        $api['auth_accounts'][0] = $this->_user_cache['name'];

        if (isset($_GET['wpw_auto_poster_insta_app_method']) && $_GET['wpw_auto_poster_insta_app_method'] == 'appmethod') {
            $api['auth_tokens'][0] = $this->grantaccessToken;
        } else {
            $api['auth_tokens'][$_SESSION['sap_insta_user_id']] = $this->grantaccessToken->getValue();
        }

        foreach ($page_tokens as $ptk) {
            if (!isset($ptk->id) || !isset($ptk->access_token))
                continue;

            $api['auth_tokens'][$ptk->id] = $ptk->access_token;
            $api['auth_accounts'][$ptk->id] = $ptk->name;
        }

        return $api;
    }


    
    /**
     * Pages Tokens
     *
     * Getting the the tokens from all pages/accounts which
     * are associated with the connected Facebook account
     * so that the admin chan choose to which page/account
     * he wants to post the submitted and approved reviews to.
     *
     * @package Social auto poster
     * @since 1.0.0
     */
    public function sap_get_pages_tokens($user_id='') {
        $ret = array();
        try {

            if (isset($_GET['wpw_insta_grant']) && $_GET['wpw_insta_grant'] == 'true' && $_GET['wpw_auto_poster_insta_app_method'] == 'appmethod') {

                $get_api = 'https://graph.facebook.com/v14.0/me/accounts?access_token='.$this->grantaccessToken.'&limit=1000&offset=0';
                $curl = curl_init();
                curl_setopt_array($curl, array(
                CURLOPT_URL => $get_api,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'GET',
                CURLOPT_HTTPHEADER => array(
                    'Cookie: PHPSESSID=477cf3cd663b806d142219499d7bfa20'
                ),
                ));
                $response = curl_exec($curl);
                $response = json_decode($response);
                $ret = $response;

                
            } else {

                //check facebook class is exist or not
                $facebook = $this->sap_load_facebook($_SESSION['sap_insta_user_id'], $user_id);
                $ret = $this->facebook->get('/me/accounts/', $this->grantaccessToken->getValue());
                $ret = $ret->getDecodedBody();
            }
        } catch (Exception $e) {

            $this->sap_common->sap_script_logs('Instagram error : ' . $e->getMessage(), $user_id);
            return false;
        }

        return $ret;
    }

    /**
     * Fetching Facebook
     *
     * Fetching all the Facebook app and secret from database
     * Facebook user (site admin).
     *
     * @package Social auto poster
     * @since 1.0.0
     */
    public function sap_get_fb_apps($user_id='') {
        //Get facebook options from facebook
        $sap_facebook_options = $this->settings->get_user_setting('sap_facebook_options', $user_id);

        $fb_apps = array();
        $fb_keys = !empty($sap_facebook_options['facebook_keys']) ? $sap_facebook_options['facebook_keys'] : array();

        if (!empty($fb_keys)) {

            foreach ($fb_keys as $fb_key_id => $fb_key_data) {

                if (!empty($fb_key_data['app_id']) && !empty($fb_key_data['app_secret'])) {
                    $fb_apps[$fb_key_data['app_id']] = $fb_key_data['app_secret'];
                }
            } // End of for each
        } // End of main if
        return $fb_apps;
    }

    /**
     * Get Social Auto poster Screen ID
     *
     * Handles to get social auto poster screen id
     *
     * @package Social Auto Poster
     * @since 1.0.0
     */
    public function sap_get_fb_accounts($data_type = false, $user_id='') {

        // Taking some defaults
        $res_data = array();

        // Get stored fb app grant data
        $sap_fb_sess_data = $this->settings->get_user_setting('sap_fb_sess_data',$user_id);

        if (is_array($sap_fb_sess_data) && !empty($sap_fb_sess_data)) {

            foreach ($sap_fb_sess_data as $fb_sess_key => $fb_sess_data) {


                $fb_sess_acc = isset($fb_sess_data['sap_insta_user_accounts']['auth_accounts']) ? $fb_sess_data['sap_insta_user_accounts']['auth_accounts'] : array();
                $fb_sess_token = isset($fb_sess_data['sap_insta_user_accounts']['auth_tokens']) ? $fb_sess_data['sap_insta_user_accounts']['auth_tokens'] : array();

                // Retrives only App Users
                if ($data_type == 'all_app_users_with_name  ') {

                    // Loop of account and merging with page id and app key
                    foreach ($fb_sess_keyacc as $fb_page_id => $fb_page_name) {
                        $res_data[$fb_sess_key][] = $fb_page_id . '|' . $fb_sess_key;
                    }
                } elseif ($data_type == 'all_app_users_with_name') {

                    // Loop of account and merging with page id and app key
                    foreach ($fb_sess_acc as $fb_page_id => $fb_page_name) {

                        if ($fb_page_id != $fb_sess_key) {

                            $res_data[$fb_sess_key][$fb_page_id . '|' . $fb_sess_key] = $fb_page_name;
                        }
                    }
                } elseif ($data_type == 'app_users') {

                    $res_data[$fb_sess_key] = (!empty($fb_sess_acc) && is_array($fb_sess_acc) ) ? array_keys($fb_sess_acc) : array();
                } elseif ($data_type == 'all_auth_tokens') {

                    // Loop of tokens and merging with page id and app key
                    foreach ($fb_sess_token as $fb_sess_token_id => $fb_sess_token_data) {
                        $res_data[$fb_sess_token_id . '|' . $fb_sess_key] = $fb_sess_token_data;
                    }
                } elseif ($data_type == 'auth_tokens') {

                    // Merging the array
                    $res_data = $res_data + $fb_sess_token;
                } elseif ($data_type == 'all_accounts') {

                    // Loop of account and merging with page id and app key
                    foreach ($fb_sess_acc as $fb_page_id => $fb_page_name) {
                        $res_data[$fb_page_id . '|' . $fb_sess_key] = $fb_page_name;
                    }
                } else {

                    // Merging the array
                    $res_data = $res_data + $fb_sess_acc;
                }
            }
        }

        return $res_data;
    }

    /**
     * Reset Sessions
     *
     * Resetting the Facebook sessions when the admin clicks on
     * its link within the settings page.
     *
     * @package Social Auto Poster
     * @since 1.0.0
     */
    public function sap_fb_reset_session_for_insta() {

        // Check if facebook reset user link is clicked and insta_reset_user is set to 1 and facebook app id is there
        if (isset($_GET['insta_reset_user']) && $_GET['insta_reset_user'] == '1' && !empty($_GET['sap_insta_userid'])) {
            $fb_app_id = $_GET['sap_insta_userid'];

            unset($_SESSION['sap_insta_user_id']);
            unset($_SESSION['sap_insta_user_cache']);
            unset($_SESSION['sap_insta_user_accounts']);

            unset($_SESSION['fb_' . $fb_app_id . '_code']);
            unset($_SESSION['fb_' . $fb_app_id . '_access_token']);
            unset($_SESSION['fb_' . $fb_app_id . '_user_id']);
            unset($_SESSION['fb_' . $fb_app_id . '_state']);

            //Getting stored fb app data
            $sap_insta_sess_data = $this->settings->get_user_setting('sap_fb_sess_data_for_insta');

            // Getting facebook app users
            $app_users = $this->sap_get_fb_accounts('all_app_users');

            // Users need to flush from stored data
            $reset_app_users = !empty($app_users[$fb_app_id]) ? $app_users[$fb_app_id] : array();

            // Unset perticular app value data and update the option
            if (isset($sap_insta_sess_data[$fb_app_id])) {

                unset($sap_insta_sess_data[$fb_app_id]);
                $this->settings->update_user_setting('sap_fb_sess_data_for_insta', $sap_insta_sess_data);
                $this->sap_common->sap_script_logs('Facebook ' . $fb_app_id . ' Account Reset Successfully.', $user_id);
                $_SESSION['sap_active_tab'] = 'instagram';
                header("Location:" . SAP_SITE_URL . "/settings/");
                exit;
            }
        }
    }

    /**
     * Post to User Wall on Facebook
     *
     * Handles to post user wall on facebook
     *
     * @package Social Auto Poster
     * @since 1.0.0
     */
    public function sap_instagram_post_to_userwall($post_id) {

        $postflg = false;

        $post = $this->posts->get_post($post_id, true);
        $user_id = isset( $post->user_id ) ? $post->user_id : '';

        //Getting Instagram Options
        $instagram_options = $this->settings->get_user_setting('sap_instagram_options', $user_id);

       //Getting stored fb app data
        $sap_instagram_sess_data = $this->settings->get_user_setting('sap_fb_sess_data_for_insta', $user_id);

        // General setting
        $sap_general_options = $this->settings->get_user_setting('sap_general_options',$user_id);

        $link_timestamp = isset($sap_general_options['timestamp_link']) ? "?".time() : '';
        
        if( !empty( $sap_instagram_sess_data ) ) {

            $sap_instagram_custom_msg = $this->posts->get_post_meta($post_id, '_sap_instagram_post_msg');
            $sap_instagram_custom_accounts = $this->posts->get_post_meta($post_id, '_sap_instagram_post_accounts');
            $sap_instagram_custom_image = $this->posts->get_post_meta($post_id, '_sap_instagram_post_image');
            $instagram_image = !empty($instagram_options['insta_image']) ? $instagram_options['insta_image'] : '';
            $default_accounts = !empty($instagram_options['posts_users']) ? $instagram_options['posts_users'] : '';

            $message = !empty($sap_instagram_custom_msg) ? $sap_instagram_custom_msg : $post->body;
            $message = html_entity_decode(strip_tags($message), ENT_QUOTES);

            if( !empty( $post->share_link ) ) {
                $message .= $post->share_link."".$link_timestamp;
            }

            if( !empty( $sap_instagram_custom_image ) ) {
                $postimage = $sap_instagram_custom_image;
            } 
            elseif( isset($post->img) && $post->img != '0' && $post->img != '' ) {
                $postimage = $post->img;
            } 
            else {
                $postimage = $instagram_image;
            }

            // Post limit 2200 character per post
            if (!empty($message))
                $message = $this->posts->sap_limit_character($message, 2200);

            //posting logs data
            $posting_logs_data = array();
            $accounts = !empty($sap_instagram_custom_accounts) ? $sap_instagram_custom_accounts : $default_accounts;


             //Check Accounts exist
            if (empty($accounts)) {
                $this->flash->setFlash('Instagram posting users are not selected.', 'error','',true);
                $this->sap_common->sap_script_logs('Instagram posting users are not selected.', $user_id );
                $status_meta_array[] = array(
                    "status" => 'error',
                    "message" => 'Instagram posting users are not selected.'
                );
                $this->quick_posts->update_post_meta($post_id,"sap_instgram_posting_error", $status_meta_array);
                return false;
            }

            if (!empty($accounts)) { // Check all user ids
                foreach ($accounts as $key => $value) {
                    try {
                        if ($post->share_link != '') {
                            $posting_logs_data['link'] = $post->share_link."".$link_timestamp;
                        }
                                    
                        $posting_image = SAP_IMG_URL . $postimage;
                        $posting_logs_data['image'] = $posting_image;
                        $posting_logs_data['message'] = $message;

                       
    
                        //$result = $this->twitter->statuses_update($params);
                        $account_data = explode("|",$value);
                        $long_access_token  = $sap_instagram_sess_data[$account_data[1]]["insta_".$account_data[1].'_long_access_token'];
                        $instagram_accounts =  $sap_instagram_sess_data[$account_data[1]]['sap_instagram_accounts'];
                        
                        if(!empty($account_data[0])){
    
                            $base_caption   = $message;
                            $base_image_url = $posting_image;
    
                            $container_api  = 'https://graph.facebook.com/v3.2/' . $account_data[0] . '/media?image_url=' . $base_image_url . '&caption=' . urlencode($base_caption) . '&access_token=' . $long_access_token;
                            $container_curl = curl_init();
                            
                            curl_setopt($container_curl, CURLOPT_URL,$container_api);
                            curl_setopt($container_curl, CURLOPT_POST, 1);  
                            curl_setopt($container_curl, CURLOPT_POSTFIELDS,$container_api);
    
                            // Receive server response ...
                            curl_setopt($container_curl, CURLOPT_RETURNTRANSFER, true);
                            $server_output = curl_exec($container_curl);
                            $server_response = json_decode($server_output);
                            
    
                            curl_close($container_curl);
    
                            if (! empty($server_response)) {
                            
                                $container_id    =  $server_response->id;

                                if( !empty( $container_id ) ) {

                                    $api_post        =  'https://graph.facebook.com/v3.2/'.$account_data[0].'/media_publish?creation_id=' . $container_id . '&access_token=' . $long_access_token;
                                    $curl = curl_init();
                                    curl_setopt_array($curl, array(
                                    CURLOPT_URL => $api_post,
                                    CURLOPT_RETURNTRANSFER => true,
                                    CURLOPT_ENCODING => '',
                                    CURLOPT_MAXREDIRS => 10,
                                    CURLOPT_TIMEOUT => 0,
                                    CURLOPT_FOLLOWLOCATION => true,
                                    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                                    CURLOPT_CUSTOMREQUEST => 'POST',
                                    CURLOPT_HTTPHEADER => array(
                                        'Cookie: PHPSESSID=477cf3cd663b806d142219499d7bfa20'
                                    ),
                                    ));
        
                                    $response = curl_exec($curl);
                                    $response = json_decode($response);
                                    $result = (array) $response;
                                    
                                    curl_close($curl);
                                   
                                    if(!empty($result['id'])){
        
                                        $posting_logs_user_details = array(
                                            'account_id' => $account_data[0],
                                            'display_name' => $instagram_accounts[$account_data[0]],
                                       
                                        );     
        
                                        $posting_logs_data['account_id'] = $account_data[0];
                                        $posting_logs_data['display_name'] = $instagram_accounts[$account_data[0]];
                                      
                                        //record logs for post posted to twitter
                                        $this->flash->setFlash( 'Instagram : Post sucessfully posted on - '.$instagram_accounts[$account_data[0]], 'success','',true );
                                        $this->sap_common->sap_script_logs('Instagram : Post sucessfully posted on - ' . $instagram_accounts[$account_data[0]], $user_id );
                                        $this->sap_common->sap_script_logs('Instagram post data : ' . var_export($posting_logs_data,true), $user_id);
        
                                        $status_meta_array[$instagram_accounts[$account_data[0]]] = array(
                                            "status" => 'success'
                                        );
                                        //posting flag that posting successfully
                                        $postflg = true;
        
                                    }
    
                                    if((isset($server_response->error) && !empty($server_response->error))){
    
                                        $error_message = $server_response->error->error_user_msg;
                                        if( $server_response->error->code == "36003" ) {
                                            $error_message = "The image's aspect ratio does not fall within our acceptable range. Advise the app user to try again with an image that falls withing a 4:5 to 1.91:1 range.";
                                        }
                                        $this->flash->setFlash('Instagram posting exception : ' . $server_response->error->code . ' | ' . $error_message, 'error','',true);
                                        $this->sap_common->sap_script_logs('Instagram error : ' . $server_response->error->code . ' | ' . $error_message, $user_id );
                                        $status_meta_array[$instagram_accounts[$account_data[0]]] = array(
                                            "status" => 'success',
                                            "message" => $server_response->error->code . ' | ' . $error_message
                                        );
    
                                    }
                                   
        
                                    //check error is set
                                    if ((isset($result->error) && !empty($result->error))) {
                                        //record logs for twitter posting exception  
                                        $error_message = $result->error->error_user_msg;
                                        $this->flash->setFlash('Instagram posting exception : ' . $result->error->code . ' | ' . $error_message, 'error','',true);
                                        $this->sap_common->sap_script_logs('Instagram error : ' . $result->error->code . ' | ' . $error_message, $user_id );
                                        $status_meta_array[$instagram_accounts[$account_data[0]]] = array(
                                            "status" => 'success',
                                            "message" => $result->error->code . ' | ' . $error_message
                                        );
                                    }
        
                                    if ($postflg) {
                                        $this->logs->add_log('instagram', $posting_logs_data,'default', $user_id);
                                    }

                                } else {
                                    $this->sap_common->sap_script_logs('Instagram error : '.$server_response->error->message, $user_id);
                                    $this->flash->setFlash('Instagram posting exception : ' . $server_response->error->message, 'error','',true);
                                }

                            } 
    
                        }    
                        
                    } catch (Exception $e) {
    
                        //record logs exception generated
                        $this->flash->setFlash('Instagram posting time out, Please try again', 'error','',true);
                        $this->sap_common->sap_script_logs('Instagram posting time out, Please try again.', $user_id );
                        $status_meta_array[$instagram_accounts[$account_data[0]]] = array(
                            "status" => 'error',
                            "message" => 'Instagram posting time out, Please try again'
                        );
                       
                        return false;
                    }
                }   
    
            }

        }
        
        //returning post flag
        return $postflg;
       
    }

    /**
     * Quick Post On facebook
     *
     * @package Social Auto Poster
     * @since 1.0.0
     */
    public function sap_quick_post_on_insta_post($post_id) {
        
    
        $postflg = false;
        //Get Post details
        $status_meta_array = array();

        $quick_post = $this->quick_posts->get_post($post_id, true);
        $user_id = isset( $quick_post->user_id ) ? $quick_post->user_id : '';
        
        $sap_networks_meta = $this->quick_posts->get_post_meta($post_id, 'sap_networks');
        
        $accounts = !empty($sap_networks_meta['instagram_accounts']) ? $sap_networks_meta['instagram_accounts'] : array();

        // Global General setting
        $sap_general_options = $this->settings->get_user_setting('sap_general_options',$user_id);

        $link_timestamp = isset($sap_general_options['timestamp_link']) ? "?".time() : '';

        //Get general options;
        $sap_instagram_options = $this->settings->get_user_setting('sap_instagram_options', $user_id);
        $sap_instagram_sess_data = $this->settings->get_user_setting('sap_fb_sess_data_for_insta', $user_id);

        $default_accounts = !empty($sap_instagram_options['posts_users']) ? $sap_instagram_options['posts_users'] : '';

        $post_link = strip_tags($quick_post->share_link);

        if(!empty($post_link)) {
            $post_link = $post_link."".$link_timestamp;
        }

        $customlink  = !empty($post_link) ? 'true' : 'false';
      
        $post_body = !empty($quick_post->message) ? html_entity_decode(strip_tags($quick_post->message),ENT_QUOTES) : '';

        $post_desc = $image = '';
        $post_body .= (!empty($post_link) ) ? "\r\n".$post_link . "\r\n" : '';
        $post_desc .= (!empty($post_body) ) ? $post_body . "\r\n" : '';

        $accounts = !empty($accounts) ? $accounts : $default_accounts;
        
        // Post limit 2200 character per post
        if (!empty($post_body))
            $post_body = $this->posts->sap_limit_character($post_body, 2200);

        //Check Accounts exist
        if (empty($accounts)) {
            $this->flash->setFlash('Instagram posting users are not selected.', 'error','',true);
            $this->sap_common->sap_script_logs('Instagram posting users are not selected.',$user_id );
            $status_meta_array[] = array(
                "status" => 'error',
                "message" => 'Instagram posting users are not selected.'
            );
            $this->quick_posts->update_post_meta($post_id,"sap_instgram_posting_error", $status_meta_array);
            return false;
        }

        if (isset($sap_instagram_options['insta_image'])) {
            $general_instagram_image = $sap_instagram_options['insta_image'];
        }

        $image = !empty($quick_post->image) ? $quick_post->image : $general_instagram_image;
        

        //posting logs data
        $posting_logs_data = array();


        if (!empty($accounts)) { // Check all user ids
            foreach ($accounts as $key => $value) {
                try {
                    if ($post_link != '') {
                        $posting_logs_data['link'] = $post_link."".$link_timestamp;
                    }
                                
                    $posting_image = SAP_IMG_URL . $image;
                    $posting_logs_data['image'] = $posting_image;
                    $posting_logs_data['message'] = $post_body;

                    //$result = $this->twitter->statuses_update($params);
                    $account_data = explode("|",$value);
                    $long_access_token  = $sap_instagram_sess_data[$account_data[1]]["insta_".$account_data[1].'_long_access_token'];
                    $instagram_accounts =  $sap_instagram_sess_data[$account_data[1]]['sap_instagram_accounts'];
                    
                    if(!empty($account_data[0])){

                        $base_caption   = $post_body;
						$base_image_url = $posting_image;

                        $container_api  = 'https://graph.facebook.com/v3.2/' . $account_data[0] . '/media?image_url=' . $base_image_url . '&caption=' . urlencode($base_caption) . '&access_token=' . $long_access_token;
						$container_curl = curl_init();
						
						curl_setopt($container_curl, CURLOPT_URL,$container_api);
						curl_setopt($container_curl, CURLOPT_POST, 1);  
						curl_setopt($container_curl, CURLOPT_POSTFIELDS,$container_api);

						// Receive server response ...
						curl_setopt($container_curl, CURLOPT_RETURNTRANSFER, true);
						$server_output = curl_exec($container_curl);
						$server_response = json_decode($server_output);

						

                        if (! empty($server_response)) {
						
							$container_id    =  $server_response->id;

                            if( !empty( $container_id ) ) {

                                $api_post        =  'https://graph.facebook.com/v3.2/'.$account_data[0].'/media_publish?creation_id=' . $container_id . '&access_token=' . $long_access_token;
                                $curl = curl_init();
                                curl_setopt_array($curl, array(
                                CURLOPT_URL => $api_post,
                                CURLOPT_RETURNTRANSFER => true,
                                CURLOPT_ENCODING => '',
                                CURLOPT_MAXREDIRS => 10,
                                CURLOPT_TIMEOUT => 0,
                                CURLOPT_FOLLOWLOCATION => true,
                                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                                CURLOPT_CUSTOMREQUEST => 'POST',
                                CURLOPT_HTTPHEADER => array(
                                    'Cookie: PHPSESSID=477cf3cd663b806d142219499d7bfa20'
                                ),
                                ));

                                $response = curl_exec($curl);
                                $response = json_decode($response);
                                $result = (array) $response;
                                curl_close($curl);
                                if(!empty($result['id'])){

                                    $posting_logs_user_details = array(
                                        'account_id' => $account_data[0],
                                        'display_name' => $instagram_accounts[$account_data[0]],
                                
                                    );     

                                    $posting_logs_data['account_id'] = $account_data[0];
                                    $posting_logs_data['display_name'] = $instagram_accounts[$account_data[0]];
                                
                                    //record logs for post posted to twitter
                                    $this->flash->setFlash( 'Instagram : Post sucessfully posted on - '.$instagram_accounts[$account_data[0]], 'success','',true );
                                    $this->sap_common->sap_script_logs('Instagram : Post sucessfully posted on - ' . $instagram_accounts[$account_data[0]], $user_id );
                                    $this->sap_common->sap_script_logs('Instagram post data : ' . var_export($posting_logs_data,true), $user_id);

                                    $status_meta_array[$instagram_accounts[$account_data[0]]] = array(
                                        "status" => 'success'
                                    );
                                    //posting flag that posting successfully
                                    $postflg = true;

                                }

                                
                                if((isset($server_response->error) && !empty($server_response->error))){

                                        $error_message = $server_response->error->error_user_msg;
                                        if( $server_response->error->code == "36003" ) {
                                            $error_message = "The image's aspect ratio does not fall within our acceptable range. Advise the app user to try again with an image that falls withing a 4:5 to 1.91:1 range.";
                                        }
                                        $this->flash->setFlash('Instagram posting exception : ' . $server_response->error->code . ' | ' . $error_message, 'error','',true);
                                        $this->sap_common->sap_script_logs('Instagram error : ' . $server_response->error->code . ' | ' . $error_message,$user_id );
                                        $status_meta_array[$instagram_accounts[$account_data[0]]] = array(
                                            "status" => 'success',
                                            "message" => $server_response->error->code . ' | ' . $error_message
                                        );

                                    }

                                //check error is set
                                if (isset($result->error) && !empty($result->error)) {
                                    //record logs for twitter posting exception
                                    $error_message = $result->error->error_user_msg;
                                    $this->flash->setFlash('Instagram posting exception : ' . $result->error->code . ' | ' . $error_message, 'error','',true);
                                    $this->sap_common->sap_script_logs('Instagram error : ' . $result->error->code . ' | ' . $error_message, $user_id );
                                    $status_meta_array[$instagram_accounts[$account_data[0]]] = array(
                                        "status" => 'success',
                                        "message" => $result->error->code . ' | ' . $error_message
                                    );
                                }

                                if ($postflg) {
                                    $this->logs->add_log('instagram', $posting_logs_data,'default', $user_id);
                                }

                            } else {
                                $this->sap_common->sap_script_logs('Instagram error : '.$server_response->error->message, $user_id);
                                $this->flash->setFlash('Instagram posting exception : ' . $server_response->error->message, 'error','',true);
                            }
							
						} 

                    }    
                    
                } catch (Exception $e) {

                    //record logs exception generated
                    $this->flash->setFlash('Instagram posting time out, Please try again', 'error','',true);
                    
                    $this->sap_common->sap_script_logs('Twitter posting time out, Please try 
                        again.', $user_id);

                    $status_meta_array[$instagram_accounts[$account_data[0]]] = array(
                        "status"  => 'success',
                        "message" => 'Instagram posting time out, Please try again'
                    );
                   
                    return false;
                }
            }

            $this->quick_posts->update_post_meta($post_id,"sap_instagram_posting_error", $status_meta_array);

        }

        //returning post flag
        return $postflg;
        
    }


    /**
     * FB get json response
     *
     * @package Social Auto Poster
     * @since 1.0.0
     */
    public function SAP_getFbJsonResponse($rawResponse) {

        if ($rawResponse === FALSE) {
            $this->error = $this->facebook->getError();
            return FALSE;
        }

        $res = json_decode($rawResponse->getBody());

        if (isset($res->error)) {

            $this->error = $res->error->message;
            if (isset($res->error->error_user_title)) {
                $this->error .= "\nError Details : " . $res->error->error_user_title;
            }
            if (isset($res->error->error_user_msg)) {
                $this->error .= " : " . $res->error->error_user_msg;
            }
            return false;
        }

        return $res;
    }

}
