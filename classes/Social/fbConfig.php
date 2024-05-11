<?php

/* Check the absolute path to the Social Auto Poster directory. */
if ( !defined( 'SAP_APP_PATH' ) ) {
    // If SAP_APP_PATH constant is not defined, perform some action, show an error, or exit the script
    // Or exit the script if required
    exit();
}

/**
 * Facebook posting
 *
 * @package Social auto poster
 * @since 1.0.0
 */
require_once LIB_PATH . "Social/facebook/autoload.php";

// Include required libraries
use Facebook\Facebook;
use Facebook\Exceptions\FacebookResponseException;
use Facebook\Exceptions\FacebookSDKException;

class SAP_Facebook {

    public $fbPermissions = array('email', 'public_profile', 'publish_pages', 'pages_manage_posts', 'publish_to_groups','business_management');  //Optional permissions
    public $facebook, $settings, $flash, $posts, $common, $logs, $quick_posts, $sap_common;
    public $grantaccessToken, $helper;

    public function __construct($user_id = '') {
        global $sap_common;
        global $proxy_url, $proxy_pupw;

        if (!class_exists('SAP_Quick_Posts')) {
            require_once( CLASS_PATH . 'Quick_Posts.php' );
        }

        if (!class_exists('SAP_Posts')) {
            require_once( CLASS_PATH . 'Posts.php' );
        }

        $this->settings    = new SAP_Settings();
        $this->flash       = new Flash();
        $this->posts       = new SAP_Posts();
        $this->common      = new Common();
        $this->logs        = new SAP_Logs();
        $this->quick_posts = new SAP_Quick_Posts();
        $this->sap_common  = $sap_common;
       
        /* Initialize the function */
        $this->sap_fb_initialize($user_id);

    }

    /**
     * Include Facebook Class
     *
     * Handles to load facebook class
     *
     * @package Social Auto Poster
     * @since 1.0.0
     */
    public function sap_load_facebook($app_id = false, $user_id = '') {
        //Getting facebook apps
        $fb_apps = $this->sap_get_fb_apps( $user_id );
        /// If app id is not passed then take first fb app data
        if (empty($app_id)) {
            $fb_apps_keys = array_keys($fb_apps);
            $app_id = reset($fb_apps_keys);
        }
        //// Check facebook application id and application secret is not empty or not
        if (!empty($app_id) && !empty($fb_apps[$app_id])) {
            $this->facebook = new Facebook(array(
                'app_id'     => $app_id,
                'app_secret' => $fb_apps[$app_id],
                'cookie'     => true,
                'default_graph_version' => SAP_NEW_FB_APP_VERSION,
            ));
            $this->helper = $this->facebook->getRedirectLoginHelper();
            return true;
        } else {
            return false;
        }
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
    public function sap_fb_initialize($user_id='') {

        //Get global SAP facebook options
        $sap_facebook_options = $this->settings->get_user_setting('sap_facebook_options', $user_id);

        if (isset($_GET['grant_fb']) && $_GET['grant_fb'] == 'true' && isset($_GET['code']) && isset($_GET['state']) && isset($_GET['fb_app_id'])) {

            //record logs for grant extended permission
           $this->sap_common->sap_script_logs('Facebook Grant Extended Permission',$user_id);

            //record logs for get parameters set properly
            $this->sap_common->sap_script_logs('Get Parameters Set Properly.',$user_id);

            $fb_app_id = $_GET['fb_app_id'];
            $facebook_auth_options = !empty($sap_facebook_options['facebook_auth_options']) ? $sap_facebook_options['facebook_auth_options'] : 'graph';

            try {
                //load facebook class
                $facebook = $this->sap_load_facebook($fb_app_id, $user_id);
            } catch (Exception $e) {
                //catch exception generated
                $error = $e->getMessage();
                $this->sap_common->sap_script_logs('Facebook Exception : ' . $error, $user_id);
                $facebook = null;
            }
            if (!$facebook)
                return false;
            $this->grantaccessToken = $this->helper->getAccessToken();


            $oAuth2Client = $this->facebook->getOAuth2Client();

            $oAuth2Client->getLongLivedAccessToken($this->grantaccessToken);

            $this->facebook->setDefaultAccessToken($this->grantaccessToken);
            $user = array();


            // Getting user facebook profile info
            try {

                $profileRequest = $this->facebook->get('/me?fields=name,first_name,last_name');
                $user = $profileRequest->getGraphNode()->asArray();
            } catch (FacebookResponseException $e) {

                echo $this->sap_common->lang('social_fbconfig_graph_error') .''. $e->getMessage();

                exit;
            } catch (FacebookSDKException $e) {
                echo $this->sap_common->lang('social_fbconfig_sdk_error') .''. $e->getMessage();
                exit;
            }
            //check user is logged in facebook or not
            if (!empty($user)) {
                $this->sap_common->sap_script_logs('Facebook User ID : ' . $user['id'], $user_id);
                try {

                    $_SESSION['sap_fb_user_cache'] = $user;
                    $this->_user_cache = $_SESSION['sap_fb_user_cache'];
                    $_SESSION['sap_fb_user_id'] = $user['id'];
                    $_SESSION['sap_fb_user_accounts'] = $this->sap_fetch_accounts();

                    // Start code to manage session from database
                    $sap_fb_sess_data = $this->settings->get_user_setting('sap_fb_sess_data');

                    // Checking if the grant extend is already done or not
                    if (!isset($sap_fb_sess_data[$fb_app_id])) {

                        $sess_data = array(
                            'sap_fb_user_cache' => $_SESSION['sap_fb_user_cache'],
                            'sap_fb_user_id' => $_SESSION['sap_fb_user_id'],
                            'sap_fb_user_accounts' => $_SESSION['sap_fb_user_accounts'],
                            'fb_' . $fb_app_id . '_code' => $_GET['code'],
                            'fb_' . $fb_app_id . '_access_token' => $this->grantaccessToken->getValue(),
                            'fb_' . $fb_app_id . '_user_id' => $fb_app_id,
                            'fb_' . $fb_app_id . '_state' => isset($_SESSION['fb_' . $fb_app_id . '_state']) ? $_SESSION['fb_' . $fb_app_id . '_state'] : '',
                        );


                        if ($fb_app_id) {
                            if (!empty($sap_fb_sess_data)) { // if rest options selected and give graph access then remove rest data
                                foreach ($sap_fb_sess_data as $k_app_id => $v_sess_data) {
                                    if ($k_app_id == $v_sess_data['sap_fb_user_id']) {
                                        unset($sap_fb_sess_data[$k_app_id]);
                                    }
                                }
                            }
                            $sap_fb_sess_data[$fb_app_id] = $sess_data;
                            // Update session data to options
                            $this->settings->update_user_setting('sap_fb_sess_data', $sap_fb_sess_data);

                            $this->sap_common->sap_script_logs('Facebook Session Data Updated to Options',$user_id);
                        }
                    }

                    $_SESSION['display_fb_post_msg'] = 'Grant Extended Permission Successfully.';
                    $this->sap_common->sap_script_logs('Facebook Grant Extended Permission Successfully', $user_id);
                } catch (FacebookApiException $e) {

                    $user = null;
                    $_SESSION['display_fb_post_msg'] = 'Facebook Exception : ' . $e->__toString();
                    $this->sap_common->sap_script_logs('Facebook Exception :' . $e->__toString(), $user_id);
                }
            }

            $_SESSION['sap_active_tab'] = 'facebook';
            header("Location:" . SAP_SITE_URL . "/settings/");
            exit;
        } else if (isset($_GET['wpw_auto_poster_fb_app_method']) && $_GET['wpw_auto_poster_fb_app_method'] == 'appmethod') {

            if (isset($_GET['access_token']) && $_GET['access_token'] != '' && $_GET['wpw_fb_grant'] == 'true') {

                $this->grantaccessToken = $_GET['access_token'];
                try {

                    $this->facebook = new Facebook(array(
                        'app_id' => SAP_NEW_FB_APP_METHOD_ID,
                        'app_secret' => SAP_NEW_FB_APP_METHOD_SECRET,
                        'cookie' => true,
                        'default_graph_version' => SAP_NEW_FB_APP_VERSION,
                    ));

                    $profileRequest = $this->facebook->get('/me?fields=name,first_name,last_name', $this->grantaccessToken);
                    $user = $profileRequest->getGraphNode()->asArray();
                } catch (FacebookResponseException $e) {
                    echo $this->sap_common->lang('social_fbconfig_graph_error').''. $e->getMessage();
                    exit;
                } catch (FacebookSDKException $e) {
                    echo $this->sap_common->lang('social_fbconfig_sdk_error').''. $e->getMessage();
                    exit;
                }

                if (!empty($user)) {
                    $this->sap_common->sap_script_logs('Facebook User ID : ' . $user['id'], $user_id);

                    try {

                        $_SESSION['sap_fb_user_cache'] = $user;
                        $this->_user_cache = $_SESSION['sap_fb_user_cache'];
                        $_SESSION['sap_fb_user_id'] = $user['id'];
                        $_SESSION['sap_fb_user_accounts'] = $this->sap_fetch_accounts();

                        $sap_fb_sess_data = $this->settings->get_user_setting('sap_fb_sess_data');

                        if (empty($sap_fb_sess_data)) {
                            $sap_fb_sess_data = array();
                        }

                        if (!isset($sap_fb_sess_data[$user['id']])) {

                            $sess_data = array(
                                'sap_fb_user_cache' => $_SESSION['sap_fb_user_cache'],
                                'sap_fb_user_id' => $_SESSION['sap_fb_user_id'],
                                'sap_fb_user_accounts' => $_SESSION['sap_fb_user_accounts'],
                                'fb_' . $user['id'] . '_code' => $_GET['code'],
                                'fb_' . $user['id'] . '_access_token' => $_GET['access_token'],
                                'fb_' . $user['id'] . '_user_id' => $user['id']
                            );


                            $key_user_id = strval($user['id']);
                            $sap_fb_sess_data[$key_user_id] = $sess_data;


                            // Update session data to options
                            $this->settings->update_user_setting('sap_fb_sess_data', $sap_fb_sess_data);
                            $this->sap_common->sap_script_logs('Facebook Session Data Updated to Options',$user_id);
                        }
                        $_SESSION['display_fb_post_msg'] = 'Grant Extended Permission Successfully.';
                        $this->sap_common->sap_script_logs('Facebook Grant Extended Permission Successfully.',$user_id);
                    } catch (FacebookApiException $e) {
                        //record logs exception generated
                        $this->sap_common->sap_script_logs('Facebook Exception : ' . $e->__toString(), $user_id);
                        //user is null
                        $user = null;
                    }
                }
                $_SESSION['sap_active_tab'] = 'facebook';
                header("Location:" . SAP_SITE_URL . "/settings/");
                exit;
            } else if(isset($_GET['wpw_fb_grant']) && $_GET['wpw_fb_grant'] == 'false' && isset($_GET['error']) && $_GET['error'] != ''){
                $this->flash->setFlash('Facebook error : ' . $_GET['error'], 'error' ,'',true);
                $this->sap_common->sap_script_logs('Facebook Exception : '.$_GET['error'], $user_id);
                
                $_SESSION['sap_active_tab'] = 'facebook';
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
    public function sap_auto_poster_get_fb_app_method_login_url() {
        //load facebook class
        $facebook = $this->sap_auto_poster_load_facebook_app_method(SAP_NEW_FB_APP_METHOD_ID);
        //check facebook class is exis or not
        if (!$facebook)
            return false;

        $redirect_URL = SAP_NEW_FB_APP_REDIRECT_URL;

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
    public function sap_auto_poster_load_facebook_app_method($app_id = false) {

        $sap_fb_options = $this->settings->get_user_setting('sap_facebook_options');
        // Check facebook application id and application secret is not empty or not
        if (!empty(SAP_NEW_FB_APP_METHOD_ID) && !empty(SAP_NEW_FB_APP_METHOD_SECRET)) {

            $this->facebook = new Facebook(array(
                'app_id' => SAP_NEW_FB_APP_METHOD_ID,
                'app_secret' => SAP_NEW_FB_APP_METHOD_SECRET,
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
    public function sap_get_fb_login_url($app_id = false) {
//load facebook class
        $facebook = $this->sap_load_facebook($app_id);

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
    public function sap_fetch_accounts() {
        $api = array();
        $page_tokens = array();
        $page_tokens = $this->sap_get_pages_tokens();
        $page_tokens = isset($page_tokens['data']) ? $page_tokens['data'] : array();

        $group_tokens = array();
        $group_tokens = $this->sap_get_groups_tokens();
        $group_tokens = !empty($group_tokens) ? $group_tokens : array();


// Taking user auth tokens
        if (isset($_GET['wpw_auto_poster_fb_app_method']) && $_GET['wpw_auto_poster_fb_app_method'] == 'appmethod') {
            $user_auth_tokens = $_SESSION['sap_fb_user_id'];
        } else {
            $user_auth_tokens = isset($_GET['fb_app_id']) ? $_GET['fb_app_id'] : '';
        }

        $api['auth_accounts'][$_SESSION['sap_fb_user_id']] = $this->_user_cache['name'] . " (" . $_SESSION['sap_fb_user_id'] . ")";

        if (isset($_GET['wpw_auto_poster_fb_app_method']) && $_GET['wpw_auto_poster_fb_app_method'] == 'appmethod') {
            $api['auth_tokens'][$_SESSION['sap_fb_user_id']] = $this->grantaccessToken;
        } else {
            $api['auth_tokens'][$_SESSION['sap_fb_user_id']] = $this->grantaccessToken->getValue();
        }

        foreach ($page_tokens as $ptk) {
            if (!isset($ptk['id']) || !isset($ptk['access_token']))
                continue;

            $api['auth_tokens'][$ptk['id']] = $ptk['access_token'];
            $api['auth_accounts'][$ptk['id']] = $ptk['name'];
        }


        //Remove this code due to group posting is not working from fb api 2.4.0 ( SAP V-1.8.0 )
        // Creating user group data if user is administrator of that group
        if (!empty($group_tokens)) {
            foreach ($group_tokens as $gtk) {


                if (isset($_GET['wpw_auto_poster_fb_app_method']) && $_GET['wpw_auto_poster_fb_app_method'] == 'appmethod') {
                    $api['auth_tokens'][$gtk['id']] = $this->grantaccessToken;
                } else {
                    $api['auth_tokens'][$gtk['id']] = $this->grantaccessToken->getValue();
                }

                $api['auth_accounts'][$gtk['id']] = $gtk['name'];
            }
        }

        return $api;
    }


    /**
     * Get Group Tokens
     *
     * Fetching all the associated accounts from the connected
     * Facebook user (site admin).
     *
     * @package Social auto poster
     * @since 1.0.0
     */
    public function sap_get_groups_tokens() {
        try {
            if (isset($_GET['wpw_fb_grant']) && $_GET['wpw_fb_grant'] == 'true' && $_GET['wpw_auto_poster_fb_app_method'] == 'appmethod') {
                $this->facebook = new Facebook(array(
                    'app_id' => SAP_NEW_FB_APP_METHOD_ID,
                    'app_secret' => SAP_NEW_FB_APP_METHOD_SECRET,
                    'cookie' => true,
                    'default_graph_version' => SAP_NEW_FB_APP_VERSION,
                ));
                $ret = $this->facebook->get('/me/groups/?admin_only=true', $this->grantaccessToken);

                $ret = $ret->getDecodedBody();
            } else {
                //check facebook class is exist or not
                $facebook = $this->sap_load_facebook($_SESSION['sap_fb_user_id']);
                $ret = $this->facebook->get('/me/groups/?admin_only=true', $this->grantaccessToken->getValue());
                $ret = $ret->getDecodedBody();
            }
        } catch (Exception $e) {
            return false;
        }

        return $ret['data'];
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
    public function sap_get_pages_tokens() {

        try {
            if (isset($_GET['wpw_fb_grant']) && $_GET['wpw_fb_grant'] == 'true' && $_GET['wpw_auto_poster_fb_app_method'] == 'appmethod') {
                $this->facebook = new Facebook(array(
                    'app_id' => SAP_NEW_FB_APP_METHOD_ID,
                    'app_secret' => SAP_NEW_FB_APP_METHOD_SECRET,
                    'cookie' => true,
                    'default_graph_version' => SAP_NEW_FB_APP_VERSION,
                ));
                $ret = $this->facebook->get('/me/accounts/?limit=100', $this->grantaccessToken);
                $retOrg = $ret;
                          
                $ret = $ret->getDecodedBody();

                if( !empty( $ret ) && isset($ret['data']) && !empty($ret['paging']['next'])  ) {
                    $paging_flag = true;
                    $fbEdgeData = $retOrg->getGraphEdge();

                    while( $paging_flag ) {
                        $responseData = $this->facebook->next($fbEdgeData);

                        if( !empty( $responseData ) ) {
                            $pagesArray = $responseData->asArray();
                            if( !empty( $pagesArray ) ){
                                $ret['data'] = array_merge($ret['data'], $pagesArray);
                            }
                            $metadata = $responseData->getMetaData();

                            if( empty( $metadata['paging']['next'] ) ) {
                                $paging_flag = false;
                            }
                        }
                    }

                }
            } else {

                //check facebook class is exist or not
                $facebook = $this->sap_load_facebook($_SESSION['sap_fb_user_id']);
                $ret = $this->facebook->get('/me/accounts/', $this->grantaccessToken->getValue());
                $retOrg = $ret;
                $ret = $ret->getDecodedBody();
                
                if( !empty( $ret ) && isset($ret['data']) && !empty($ret['paging']['next'])  ) {
                    $paging_flag = true;
                    $fbEdgeData = $retOrg->getGraphEdge();

                    while( $paging_flag ) {
                        $responseData = $this->facebook->next($fbEdgeData);

                        if( !empty( $responseData ) ) {
                            $pagesArray = $responseData->asArray();
                            if( !empty( $pagesArray ) ){
                                $ret['data'] = array_merge($ret['data'], $pagesArray);
                            }
                            $metadata = $responseData->getMetaData();

                            if( empty( $metadata['paging']['next'] ) ) {
                                $paging_flag = false;
                            }
                        }
                    }

                }
            }
        } catch (Exception $e) {
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
    public function sap_get_fb_apps( $user_id = '' ) {
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
    public function sap_get_fb_accounts($data_type = false, $user_id = '' ) {

        // Taking some defaults
        $res_data = array();

        // Get stored fb app grant data
        $sap_fb_sess_data = $this->settings->get_user_setting('sap_fb_sess_data', $user_id);

        if (is_array($sap_fb_sess_data) && !empty($sap_fb_sess_data)) {

            foreach ($sap_fb_sess_data as $fb_sess_key => $fb_sess_data) {


                $fb_sess_acc = isset($fb_sess_data['sap_fb_user_accounts']['auth_accounts']) ? $fb_sess_data['sap_fb_user_accounts']['auth_accounts'] : array();
                $fb_sess_token = isset($fb_sess_data['sap_fb_user_accounts']['auth_tokens']) ? $fb_sess_data['sap_fb_user_accounts']['auth_tokens'] : array();

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
    public function sap_fb_reset_session() {

        // Check if facebook reset user link is clicked and fb_reset_user is set to 1 and facebook app id is there
        if (isset($_GET['fb_reset_user']) && $_GET['fb_reset_user'] == '1' && !empty($_GET['sap_fb_userid'])) {
            $fb_app_id = $_GET['sap_fb_userid'];

            unset($_SESSION['sap_fb_user_id']);
            unset($_SESSION['sap_fb_user_cache']);
            unset($_SESSION['sap_fb_user_accounts']);

            unset($_SESSION['fb_' . $fb_app_id . '_code']);
            unset($_SESSION['fb_' . $fb_app_id . '_access_token']);
            unset($_SESSION['fb_' . $fb_app_id . '_user_id']);
            unset($_SESSION['fb_' . $fb_app_id . '_state']);

            //Getting stored fb app data
            $sap_fb_sess_data = $this->settings->get_user_setting('sap_fb_sess_data');

            // Getting facebook app users
            $app_users = $this->sap_get_fb_accounts('all_app_users');

            // Users need to flush from stored data
            $reset_app_users = !empty($app_users[$fb_app_id]) ? $app_users[$fb_app_id] : array();

            // Unset perticular app value data and update the option
            if (isset($sap_fb_sess_data[$fb_app_id])) {

                unset($sap_fb_sess_data[$fb_app_id]);
                $this->settings->update_user_setting('sap_fb_sess_data', $sap_fb_sess_data);
                $this->sap_common->sap_script_logs('Facebook ' . $fb_app_id . ' Account Reset Successfully.');
                $_SESSION['sap_active_tab'] = 'facebook';
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
    public function sap_fb_post_to_userwall($post_id) {

        global $proxy_url, $proxy_pupw;
        $postflg = false;
        $post = $this->posts->get_post($post_id, true);
        $user_id = isset( $post->user_id ) ? $post->user_id : '';

        // General setting
        $sap_general_options = $this->settings->get_user_setting('sap_general_options',$user_id);

        $link_timestamp = isset($sap_general_options['timestamp_link']) ? "?".time() : '';

        //Getting facebook options
        $sap_fb_options = $this->settings->get_user_setting('sap_facebook_options',$user_id);

        //Getting stored fb app data
        $sap_fb_sess_data = $this->settings->get_user_setting('sap_fb_sess_data', $user_id);

        $global_share_post_type = (!empty($sap_fb_options['share_posting_type']) ) ? $sap_fb_options['share_posting_type'] : 'link_posting';
        $sap_fb_posting_type = $global_share_post_type;

        // check facebook method for posting
        $facebook_auth_options = !empty($sap_fb_options['facebook_auth_options']) ? $sap_fb_options['facebook_auth_options'] : 'graph';

        //Facebbok proxy setting
        $proxy = array();
        if (!empty($sap_fb_options['enable_proxy'])) {
            
            if(!empty($sap_fb_options['proxy_url'])) {
                
                $proxy_url = $sap_fb_options['proxy_url'];
            }
            if(!empty($sap_fb_options['proxy_username']) && !empty($sap_fb_options['proxy_password'])) {
                $proxy_pupw = $sap_fb_options['proxy_username'].":".$sap_fb_options['proxy_password'];
            }

        }
        

        if (!empty($sap_fb_sess_data)) {

            $sap_fb_custom_msg = $this->posts->get_post_meta($post_id, '_sap_fb_post_msg');
            $sap_fb_custom_accounts = $this->posts->get_post_meta($post_id, '_sap_fb_post_accounts');
            $sap_fb_custom_image = $this->posts->get_post_meta($post_id, '_sap_fb_post_image');
            $posting_type = $this->posts->get_post_meta($post_id, '_sap_fb_status');
            $fb_image = !empty($sap_fb_options['fb_image']) ? $sap_fb_options['fb_image'] : '';
            $sap_facebook_custom_link = $this->posts->get_post_meta($post_id, 'sap_facebook_custom_link');
            $post_sap_fb_posting_type = $this->posts->get_post_meta($post_id, '_sap_fb_post_type');
            if ( !empty( $post_sap_fb_posting_type ) ) {
                $sap_fb_posting_type = $post_sap_fb_posting_type;
            }

            $sap_fb_user_id = !empty($sap_fb_options['fb_type_post_user']) ? $sap_fb_options['fb_type_post_user'] : '';

            // Getting all facebook apps
            $fb_apps = $this->sap_get_fb_apps( $user_id );

            // Getting all stored facebook access token
            $fb_access_token = $this->sap_get_fb_accounts('all_auth_tokens', $user_id);

            // Facebook user id on whose wall the post will be posted
            $fb_user_ids = '';

            //check there is facebook user ids are set and not empty in metabox
            if (!empty($sap_fb_custom_accounts)) {
                //users from metabox
                $fb_user_ids = $sap_fb_custom_accounts;

                /**** Backward Compatibility Code Starts **** */
                // If user account is selected in meta so creating data accoring to new method ( Will be helpfull when scheduling is done )
                if (!empty($fb_user_ids)) {

                    $fb_first_app_key = !empty($sap_fb_options['facebook_keys'][0]['app_id']) ? $sap_fb_options['facebook_keys'][0]['app_id'] : '';

                    if (!empty($fb_first_app_key)) {
                        foreach ($fb_user_ids as $fb_user_key => $fb_user_data) {
                            if (strpos($fb_user_data, '|') === false) {
                                $fb_user_ids[$fb_user_key] = $fb_user_data . '|' . $fb_first_app_key;
                            }
                        }
                    }
                }
                /*                 * *** Backward Compatibility Code Ends **** */
            }

            //check facebook user ids are empty in metabox and set in settings page
            if (empty($fb_user_ids) && !empty($sap_fb_user_id)) {
                //users from settings
                $fb_user_ids = $sap_fb_user_id;
            } //end if
            //convert user ids to single array
            $post_to_users = (array) $fb_user_ids;

            if (empty($fb_user_ids)) {
                $this->flash->setFlash('Facebook user not selected', 'error' ,'',true);
                $this->sap_common->sap_script_logs('Facebook error: User not selected for posting.', $user_id);
                //return false
                return false;
            }

            $message = !empty($sap_fb_custom_msg) ? $sap_fb_custom_msg : $post->body;
            $postlink = !empty($post->share_link) ? $post->share_link : $sap_facebook_custom_link;

            if(!empty($postlink)) {
                $postlink = $postlink."".$link_timestamp;
            }

            $customlink  = !empty($postlink) ? 'true' : 'false';
            $postlink   = $this->common->sap_script_short_post_link($postlink,$customlink,'fb','facebook', $user_id);


            $message = html_entity_decode(strip_tags($message), ENT_QUOTES);

            $posting_log = array();

            //check post image is not empty then pass to facebook
            if( !empty( $sap_fb_custom_image ) ){
                $postimage = $sap_fb_custom_image;
            }
            elseif( isset($post->img) && $post->img != '0' && $post->img != '' ){
                $postimage = $post->img;
            } 
            else {
                $postimage = $fb_image;
            }

            $posting_log['link'] = $postlink;
            if (!empty($postimage)) {
                $posting_log['image'] = SAP_IMG_URL . $postimage;
            }

            // facebook post character length limit 63,206 per post
            if (!empty($message))
                $message = $this->posts->sap_limit_character($message, 63206);

            //Post feed
            $posting_log['message'] = $message;

            //Facebbok app version check and based on send data
            if (!empty($sap_fb_options['fb_app_version']) && $sap_fb_options['fb_app_version'] >= 2.9) {
                $posting_log = array(
                    'message' => $message,
                    'link' => $postlink,
                );
            }

            // if Post Reviews to this Fan Page/Account option is set
            if (!empty($post_to_users)) {

                // Get facebook account details
                $fb_accounts = $this->sap_get_fb_accounts(false, $user_id);

                foreach ($post_to_users as $post_to) {

                    $send = $posting_log;

                    if (isset($send['name'])) {
                        unset($send['name']);
                    }
                    if ($facebook_auth_options == 'rest') {
                        if (isset($send['link']) && !empty($send['link'])) {
                            $send['link'] = urlencode($send['link']);
                        }
                        if (!empty($send['message'])) {
                            $send['message'] = urlencode($send['message']);
                        }
                        if (!empty($send['description'])) {
                            $send['description'] = urlencode($send['description']);
                        }
                        if (!empty($send['url'])) {
                            $send['url'] = urlencode($send['url']);
                        }
                    }

                    $fb_post_app_arr = explode('|', $post_to);
                    $fb_post_to_id = isset($fb_post_app_arr[0]) ? $fb_post_app_arr[0] : ''; // Facebook Posting account Id
                    $fb_post_app_id = isset($fb_post_app_arr[1]) ? $fb_post_app_arr[1] : ''; // Facebook App Id
                    $fb_post_app_sec = isset($fb_apps[$fb_post_app_id]) ? $fb_apps[$fb_post_app_id] : ''; // Facebook App Sec
                    // Load facebook class


                    if ($facebook_auth_options == 'graph') {
                        // Load facebook class
                        $facebook = $this->sap_load_facebook($fb_post_app_id, $user_id);
                    } else { // load facebook rest API class
                        $this->facebook = new Facebook(array(
                            'app_id' => SAP_NEW_FB_APP_METHOD_ID,
                            'app_secret' => SAP_NEW_FB_APP_METHOD_SECRET,
                            'cookie' => true,
                            'default_graph_version' => SAP_NEW_FB_APP_VERSION,
                        ));
                    }

                    // Getting stored facebook app data
                    $fb_stored_app_data = isset($sap_fb_sess_data[$fb_post_app_id]) ? $sap_fb_sess_data[$fb_post_app_id] : array();

                    // Get user cache data
                    $user_cache_data = isset($fb_stored_app_data['sap_fb_user_cache']) ? $fb_stored_app_data['sap_fb_user_cache'] : array();
                    $posting_account_name = isset($fb_stored_app_data['sap_fb_user_accounts']['auth_accounts'][$fb_post_to_id]) ? $fb_stored_app_data['sap_fb_user_accounts']['auth_accounts'][$fb_post_to_id] : '';

                    $send['access_token'] = '';

                    if (isset($fb_access_token[$post_to])) {//check there is access token is set
                        $send['access_token'] = $fb_access_token[$post_to]; // most imp line
                    } //end if
                    //check accesstoken is not empty
                    if (!empty($send['access_token']) && !empty( $this->facebook ) ) {

                        $post_method = 'feed';
                        if ( $sap_fb_posting_type == 'image_posting' && !empty($postimage) && !empty($send['access_token'])) {
                            if (isset($send['link']))
                                unset($send['link']);
                            if (isset($send['actions']))
                                unset($send['actions']);
                            if (isset($send['description']))
                                unset($send['description']);
                            if (isset($send['name']))
                                unset($send['name']);
                            $post_method = 'photos';
                            $send['url'] = $send['image'];
                        }

                        try {

                            if ($facebook_auth_options == 'graph' || $facebook_auth_options == 'appmethod') {
                                $this->sap_common->sap_script_logs('Facebook posting begins with ' . $post_method . ' method.', $user_id);
                                //post to facebook user wall
                                $ret = $this->facebook->post('/' . $fb_post_to_id . '/' . $post_method . '/', $send, $send['access_token'], SAP_NEW_FB_APP_VERSION);
                                $response = $ret->getDecodedBody();

                                //check id is set in response and not empty
                                if (isset($response['id']) && !empty($response['id'])) {
                                    $postflg = true;
                                    $send['posted'] = 'success';
                                } else {
                                    $send['posted'] = 'fail';
                                }
                                if ($postflg) {

                                    $posting_log['account name'] = $posting_account_name;
                                    $posting_log['link to post'] = 'https://www.facebook.com/' . $fb_post_to_id;

                                    $this->logs->add_log('facebook', $posting_log, $posting_type, $user_id);
                                }
                                $this->flash->setFlash('Facebook : Post sucessfully posted on - ' . $posting_account_name, 'success','',true);
                                $this->sap_common->sap_script_logs('Facebook : Post sucessfully posted on - ' . $posting_account_name, $user_id);
                                //record logs for facebook data
                                $this->sap_common->sap_script_logs('Facebook post data : ' . var_export($send, true), $user_id);
                            }
                        } catch (Exception $e) {

                            $message = 'Facebook ' . $posting_account_name . ': ';
                            $postflg = false;
                            $this->sap_common->sap_script_logs('Facebook error : ' . $e->getMessage(), $user_id);
                            $this->flash->setFlash($message . $e->getMessage(), 'error' ,'',true);
                        } //end catch
                    } //end if to check accesstoken is not empty
                } //end foreach
            } //end if to check post_to is not empty
            return $postflg;
        } else {
            $this->flash->setFlash('Facebook grant extended permissions not set.', 'error' ,'',true);
            $this->sap_common->sap_script_logs('Facebook grant extended permissions not set.', $user_id);
        }
    }

    /**
     * Quick Post On facebook
     *
     * @package Social Auto Poster
     * @since 1.0.0
     */
    public function sap_quick_post_on_fb_post($post_id) {

        global $global_user_id, $proxy_url, $proxy_pupw;
        
        $postflg = false;
        $status_meta_array = array();
        $all_fb_users_with_name = array();
        
        $quick_post     = $this->quick_posts->get_post($post_id, true);
        $video_uploaded = $quick_post->video;

      

        
        $user_id = isset( $quick_post->user_id ) ? $quick_post->user_id : '';
        
        //Getting facebook options
        $sap_fb_options = $this->settings->get_user_setting('sap_facebook_options', $user_id);

        //Getting stored fb app data
        $sap_fb_sess_data = $this->settings->get_user_setting('sap_fb_sess_data', $user_id);

        $global_share_post_type = (!empty($sap_fb_options['share_posting_type']) ) ? $sap_fb_options['share_posting_type'] : 'link_posting';

        // check facebook method for posting
        $facebook_auth_options = !empty($sap_fb_options['facebook_auth_options']) ? $sap_fb_options['facebook_auth_options'] : 'graph';

         // General setting
        $sap_general_options = $this->settings->get_user_setting('sap_general_options',$user_id);

        $link_timestamp = isset($sap_general_options['timestamp_link']) ? "?".time() : '';

        //Facebbok proxy setting
        $proxy = array();
        if (!empty($sap_fb_options['enable_proxy'])) {
            
            if(!empty($sap_fb_options['proxy_url'])) {
                
                $proxy_url = $sap_fb_options['proxy_url'];
            }
            if(!empty($sap_fb_options['proxy_username']) && !empty($sap_fb_options['proxy_password'])) {
                $proxy_pupw = $sap_fb_options['proxy_username'].":".$sap_fb_options['proxy_password'];
            }

        }

        if (!empty($sap_fb_sess_data)) {

            $sap_networks_meta = $this->quick_posts->get_post_meta($post_id, 'sap_networks');
            $sap_networks_accounts = !empty($sap_networks_meta['fb_accounts']) ? $sap_networks_meta['fb_accounts'] : array();

            $posting_type = $this->posts->get_post_meta($post_id, '_sap_fb_status');

            // Getting all facebook apps
            $fb_apps = $this->sap_get_fb_apps($user_id);

            // Getting all stored facebook access token
            $fb_access_token = $this->sap_get_fb_accounts('all_auth_tokens',$user_id);

            // Facebook user id on whose wall the post will be posted
            $fb_user_ids = '';

            //check there is facebook user ids are set and not empty in metabox
            if (!empty($sap_networks_accounts)) {

                //users from metabox
                $fb_user_ids = $sap_networks_accounts;

                /*                 * *** Backward Compatibility Code Starts **** */
                // If user account is selected in meta so creating data accoring to new method ( Will be helpfull when scheduling is done )
                if (!empty($fb_user_ids)) {

                    $fb_first_app_key = !empty($sap_fb_options['facebook_keys'][0]['app_id']) ? $sap_fb_options['facebook_keys'][0]['app_id'] : '';

                    if (!empty($fb_first_app_key)) {
                        foreach ($fb_user_ids as $fb_user_key => $fb_user_data) {
                            if (strpos($fb_user_data, '|') === false) {
                                $fb_user_ids[$fb_user_key] = $fb_user_data . '|' . $fb_first_app_key;
                            }
                        }
                    }
                }
                /*                 * *** Backward Compatibility Code Ends **** */
            } //end if
            //convert user ids to single array
            $post_to_users = (array) $fb_user_ids;

            $message = html_entity_decode(strip_tags($quick_post->message), ENT_QUOTES);

            // facebook post character length limit 63,206 per post
            if (!empty($message))
                $message = $this->posts->sap_limit_character($message, 63206);

            $postlink = !empty($quick_post->share_link) ? $quick_post->share_link: '';

            if(!empty($postlink)) {
                $postlink = $postlink."".$link_timestamp;
            }

            $customlink  = !empty($postlink) ? 'true' : 'false';
            $postlink   = $this->common->sap_script_short_post_link($postlink,$customlink,'fb','facebook', $user_id);

            $posting_log = array();

            //check post image is not empty then pass to facebook
            $post_img = !empty($quick_post->image) ? $quick_post->image : $sap_fb_options['fb_image'];

            $posting_log['link'] = $postlink;
            if (!empty($post_img)) {
                $posting_log['image'] = SAP_IMG_URL . $post_img;
            }


            //Post feed
            $posting_log['message'] = $message;
            //Facebbok app version check and based on send data
            if (!empty($sap_fb_options['fb_app_version']) && $sap_fb_options['fb_app_version'] >= 2.9) {
                $posting_log = array(
                    'message' => $message,
                    'link' => $postlink,
                );
            }


            $fb_get_users_with_name = $this->sap_get_fb_accounts('all_app_users_with_name',$user_id);

            if (!empty($fb_get_users_with_name)) {
                foreach ($fb_get_users_with_name as $key => $value) {
                    if (is_array($value) && !empty($value)) {
                        foreach ($value as $app_id_key => $user_name_value) {
                            $all_fb_users_with_name[$app_id_key] = $user_name_value;
                        }
                    } else {
                        $all_fb_users_with_name[$key] = $value;
                    }
                }
            }

            // if Post Reviews to this Fan Page/Account option is set
            if (!empty($post_to_users)) {

                // Get facebook account details
                $fb_accounts = $this->sap_get_fb_accounts(false, $user_id);

                foreach ($post_to_users as $post_to) {

                    $status_meta_key = isset($all_fb_users_with_name[$post_to]) ? $all_fb_users_with_name[$post_to] : $post_to;
                    $send = $posting_log;

                    if (isset($send['name'])) {
                        unset($send['name']);
                    }

                    if ($facebook_auth_options == 'rest') {
                        if (isset($send['link']) && !empty($send['link'])) {
                            $send['link'] = urlencode($send['link']);
                        }
                        if (!empty($send['message'])) {
                            $send['message'] = urlencode($send['message']);
                        }
                        if (!empty($send['description'])) {
                            $send['description'] = urlencode($send['description']);
                        }
                    }

                    $fb_post_app_arr = explode('|', $post_to);
                    $fb_post_to_id = isset($fb_post_app_arr[0]) ? $fb_post_app_arr[0] : ''; // Facebook Posting account Id
                    $fb_post_app_id = isset($fb_post_app_arr[1]) ? $fb_post_app_arr[1] : ''; // Facebook App Id
                    $fb_post_app_sec = isset($fb_apps[$fb_post_app_id]) ? $fb_apps[$fb_post_app_id] : ''; // Facebook App Sec


                    if ($facebook_auth_options == 'graph') {
                        // Load facebook class
                        $facebook = $this->sap_load_facebook($fb_post_app_id,$user_id);
                    } else { // load facebook class while Appmethod
                        $this->facebook = new Facebook(array(
                            'app_id' => SAP_NEW_FB_APP_METHOD_ID,
                            'app_secret' => SAP_NEW_FB_APP_METHOD_SECRET,
                            'cookie' => true,
                            'default_graph_version' => SAP_NEW_FB_APP_VERSION,
                        ));
                    }

                    // Getting stored facebook app data
                    $fb_stored_app_data = isset($sap_fb_sess_data[$fb_post_app_id]) ? $sap_fb_sess_data[$fb_post_app_id] : array();

                    // Get user cache data
                    $user_cache_data = isset($fb_stored_app_data['sap_fb_user_cache']) ? $fb_stored_app_data['sap_fb_user_cache'] : array();
                    $posting_account_name = isset($fb_stored_app_data['sap_fb_user_accounts']['auth_accounts'][$fb_post_to_id]) ? $fb_stored_app_data['sap_fb_user_accounts']['auth_accounts'][$fb_post_to_id] : '';

                    $send['access_token'] = '';

                    if (isset($fb_access_token[$post_to])) {//check there is access token is set
                        $send['access_token'] = $fb_access_token[$post_to]; // most imp line
                    } //end if
                    //check accesstoken is not empty
                    if (!empty($send['access_token'])) {
                        $post_method = 'feed';
                        if ($global_share_post_type == 'image_posting' && !empty($post_img) && !empty($send['access_token'])) {
                            if (isset($send['link']))
                                unset($send['link']);
                            if (isset($send['actions']))
                                unset($send['actions']);
                            if (isset($send['description']))
                                unset($send['description']);
                            if (isset($send['name']))
                                unset($send['name']);
                            $post_method = 'photos';
                            $send['url'] = $send['image'];
                        }

                        $video_path = SAP_APP_PATH.'uploads/'.$video_uploaded;
                        if(!empty($video_uploaded) && file_exists($video_path)){
                                
                            $post_method = 'videos';
                            $send['description'] = $send['message'];
                            $send['source'] = $this->facebook->videoToUpload($video_path);
                            unset($send['message']);
                        }

                       


                        try {
                            if ($facebook_auth_options == 'graph' || $facebook_auth_options == 'appmethod') {

                                $this->sap_common->sap_script_logs('Facebook posting begins with ' . $post_method . ' method.', $user_id);

                                //post to facebook user wall
                                $ret = $this->facebook->post('/' . $fb_post_to_id . '/' . $post_method . '/', $send, $send['access_token'], SAP_NEW_FB_APP_VERSION);
                                $response = $ret->getDecodedBody();
                                //check id is set in response and not empty
                                if (isset($response['id']) && !empty($response['id'])) {
                                    $postflg = true;
                                    $send['posted'] = 'success';
                                } else {
                                    $send['posted'] = 'fail';
                                }
                                if ($postflg) {

                                    $posting_log['account name'] = $posting_account_name;
                                    $posting_log['link to post'] = 'https://www.facebook.com/' . $fb_post_to_id;
                                    $this->logs->add_log('facebook', $posting_log, $posting_type, $user_id);
                                    $this->quick_posts->update_post_meta($post_id, "sap_fb_link_to_post", $posting_log['link to post'] );
                                }
                                $this->flash->setFlash('Facebook : Post sucessfully posted on - ' . $posting_account_name, 'success','',true);
                                $this->sap_common->sap_script_logs('Facebook : Post sucessfully posted on - ' . $posting_account_name, $user_id);
                                $this->sap_common->sap_script_logs('Facebook post data : ' . var_export($send, true), $user_id);
                                $status_meta_array[$status_meta_key] = array(
                                    "status" => 'success'
                                );
                            }
                        } catch (Exception $e) {
                            $message = 'Facebook ' . $posting_account_name . ': ';
                            $this->sap_common->sap_script_logs('Facebook error : ' . $e->getMessage(), $user_id);
                            $postflg = false;
                            $this->flash->setFlash($message . $e->getMessage(), 'error','',true);
                            $status_meta_array[$status_meta_key] = array(
                                "status" => 'error',
                                "message" => $e->getMessage()
                            );
                        } //end catch
                    } //end if to check accesstoken is not empty
                } //end foreach
                $this->quick_posts->update_post_meta($post_id, "sap_fb_posting_error", $status_meta_array);
            } //end if to check post_to is not empty

            return $postflg;
        } else {
            $this->flash->setFlash('Facebook grant extended permissions not set.', 'error','',true);
            $this->sap_common->sap_script_logs('Facebook grant extended permissions not set.', $user_id);
            $status_meta_array[] = array(
                "status" => 'error',
                "message" => 'Facebook grant extended permissions not set.'
            );
            $this->quick_posts->update_post_meta($post_id, "sap_fb_posting_error", $status_meta_array);
        }
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
