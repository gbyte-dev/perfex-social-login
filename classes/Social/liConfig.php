<?php
 
/* Check the absolute path to the Social Auto Poster directory. */
if ( !defined( 'SAP_APP_PATH' ) ) {
    // If SAP_APP_PATH constant is not defined, perform some action, show an error, or exit the script
    // Or exit the script if required
    exit();
}
 
/**
 * Linkedin posting
 *
 * @package Social auto poster
 * @since 1.0.0
 */
class SAP_Linkedin {

    public $linkedin, $settings, $common, $posts, $linkedinconfig, $grantaccessToken, $helperli,$linkedinAppMethod, $flash, $logs, $sap_common, $quick_posts;
    private $db;

    public function __construct($user_id='') {
        global $sap_common,$sap_db_connect;

        if (!class_exists('SAP_Quick_Posts')) {
            require_once( CLASS_PATH . 'Quick_Posts.php' );
        }

        if (!class_exists('SAP_Posts')) {
            require_once( CLASS_PATH . 'Posts.php' );
        }

        $this->settings = new SAP_Settings();
        $this->common = new Common();
        $this->db = $sap_db_connect;
        $this->flash = new Flash();
        $this->logs = new SAP_Logs();
        $this->sap_common = $sap_common;
        $this->quick_posts = new SAP_Quick_Posts();

        $sap_linkedin_options = $this->settings->get_user_setting('sap_linkedin_options', $user_id);

        if (isset($sap_linkedin_options['linkedin_keys']) && !empty($sap_linkedin_options['linkedin_keys'])) {
            if (!defined('LI_APP_ID')) {
                define('LI_APP_ID', $sap_linkedin_options['linkedin_keys'][0]['app_id']);
            }
            if (!defined('LI_APP_SECRET')) {
                define('LI_APP_SECRET', $sap_linkedin_options['linkedin_keys'][0]['app_secret']);
            }
        }
     
        $this->sap_li_user_logged_in($user_id);
    }

     /**
     * Linkedin Login URL Using APP method
     *
     * Getting the login URL from Linkedin.
     * Linkedin App method
     *
     * @package Social Auto Poster
     * @since 1.0.0
     */
    public function sap_auto_poster_get_li_app_method_login_url() {
        //load facebook class
        $sap_linkedin_options = $this->settings->get_user_setting('sap_linkedin_options');

        $scope = array('w_member_social', 'r_liteprofile', 'w_member_social', 'r_emailaddress');

        if (isset($sap_linkedin_options['enable_company_pages']) && $sap_linkedin_options['enable_company_pages'] == 'on') {

            $scope[] = 'rw_organization_admin';
            $scope[] = 'w_organization_social';

        }

        //load linkedin class
        
        if (!class_exists('LinkedInOAuth2')) {
            include LIB_PATH . 'Social/Linkedin/LinkedIn.OAuth2.class.php';
        }
        //check linkedin loaded or not
        $this->helperli = new LinkedInOAuth2();

        $redirect_URL = SAP_NEW_LI_APP_REDIRECT_URL;
        
        try {
            $preparedurl = $this->helperli->getAuthorizeUrl(SAP_NEW_LI_APP_METHOD_ID, $redirect_URL, $scope, SAP_SITE_URL );
            
            
        } catch (Exception $e) {
            $preparedurl = '';
        }

        return $preparedurl;
    }


    /**
     * Include LinkedIn Class
     * 
     * Handles to load Linkedin class
     * 
     * @package Social Auto Poster
     * @since 1.0.0
     */
    public function sap_load_linkedin($app_id = false, $user_id='') {
 
        //Getting linkedin apps
        $li_apps = $this->sap_get_li_apps($user_id);

        // If app id is not passed then take first li app data
        if (empty($app_id)) {
            $li_apps_keys = array_keys($li_apps);
            $app_id = reset($li_apps_keys);
        }

        $linkedin_auth_options = !empty($sap_linkedin_options['linkedin_auth_options']) ? $sap_linkedin_options['linkedin_auth_options'] : 'appmethod';

      
        //linkedin declaration
        if (!empty($app_id) ) {
             if (!class_exists('LinkedInOAuth2')) {
                include LIB_PATH . 'Social/Linkedin/LinkedIn.OAuth2.class.php';
            }
           
            if($linkedin_auth_options == 'graph' && !empty($li_apps[$app_id]) ){

                $callbackUrl = SAP_SITE_URL.'/settings/' . '?grant_li=true&li_app_id=' . $app_id;

                //linkedin api configuration
                $this->linkedinconfig = array(
                    'appKey'      => $app_id,
                    'appSecret'   => $li_apps[$app_id],
                    'callbackUrl' => $callbackUrl
                );

                //Get access token
                $access_token = $this->sap_li_get_access_token($app_id,$user_id);

                //Load linkedin outh2 class
              

            }else{
               
                $access_token = $this->sap_li_get_access_token(SAP_NEW_LI_APP_METHOD_ID,$user_id);
    
                //Load linkedin outh2 class
               
            }
            $this->linkedin = new LinkedInOAuth2($access_token);
             return true;

           
        } else {

            return false;
        }
    }

    /**
     * Assign Linkedin User's all Data to session
     * 
     * Handles to assign user's facebook data
     * to sessoin & save to database
     * 
     * @package Social Auto Poster
     * @since 1.0.0
     */
    public function sap_li_initialize($user_id='') {

        $sap_linkedin_options = $this->settings->get_user_setting('sap_linkedin_options',$user_id);

        //check user data is not empty and linkedin app id and secret are not empty
        if (!empty($sap_linkedin_options['linkedin_keys'][0]['app_id']) && !empty($sap_linkedin_options['linkedin_keys'][0]['app_secret'])) {
            
            $this->sap_common->sap_script_logs('Linkedin Grant Extended Permission', $user_id);
            
            $this->sap_common->sap_script_logs('Linkedin Get Parameters Set Properly.', $user_id);
            //Set Session From Options Value            
            $sap_li_sess_data = $this->settings->get_user_setting('sap_li_sess_data', $user_id);

            if (!empty($sap_li_sess_data) && !isset($_SESSION['sap_li_user_id'])) { //check user data is not empty
                $_SESSION['sap_li_user_id'] = $sap_li_sess_data['sap_li_user_id'];
                $_SESSION['sap_li_cache'] = $sap_li_sess_data['sap_li_cache'];
                $_SESSION['sap_li_oauth'] = $sap_li_sess_data['sap_li_oauth'];
                $_SESSION['sap_linkedin_oauth'] = $sap_li_sess_data['sap_li_oauth']; //assign stored oauth token to database
                $_SESSION['sap_li_companies'] = $sap_li_sess_data['sap_li_companies']; //assign stored companies to database
                $_SESSION['sap_li_groups'] = $sap_li_sess_data['sap_li_groups']; //assign stored groups to database
            }
        }
    }

    /**
     * LinekedIn Get Access Tocken
     * 
     * @package Social Auto Poster
     * @since 1.0.0
     */
    public function sap_li_get_access_token($app_id,$user_id='') {

        $access_tocken = '';

        $sap_linkedin_options = $this->settings->get_user_setting('sap_linkedin_options', $user_id);

        $linkedin_auth_options = !empty($sap_linkedin_options['linkedin_auth_options']) ? $sap_linkedin_options['linkedin_auth_options'] : 'appmethod';
        $sap_li_sess_data = $this->settings->get_user_setting('sap_li_sess_data',$user_id);

           
        if ($linkedin_auth_options == 'appmethod') {


            $user_li_id = explode(':|:', $app_id);

            if ($user_li_id[0] == 'user') {
            
                $data_key =  $user_li_id[1]; 

            } elseif($user_li_id[0] == 'company') {

                $data_key =  $user_li_id[2]; 
            }

            if (!empty($sap_li_sess_data) && isset($sap_li_sess_data[$data_key]['sap_li_oauth']['linkedin']['access'])) {
              $access_tocken = $sap_li_sess_data[$data_key]['sap_li_oauth']['linkedin']['access'];
            }

        }else{

             if (!empty($sap_li_sess_data) && isset($sap_li_sess_data[$app_id]['sap_li_oauth']['linkedin']['access'])) {

                $li_access_data = $sap_li_sess_data[$app_id]['sap_li_oauth']['linkedin']['access'];

                $access_tocken = isset($li_access_data['access_token']) ? $li_access_data['access_token'] : '';
                
            } elseif (isset($_SESSION['sap_linkedin_oauth']['linkedin']['access'])) {

                $li_access_data = $_SESSION['sap_linkedin_oauth']['linkedin']['access'];

                $access_tocken = isset($li_access_data['access_token']) ? $li_access_data['access_token'] : '';
            }    
        }
    
        return $access_tocken;
    }

    public function sap_li_get_access_token_for_publishing($app_id,$user_id='') {

        $sap_li_sess_data = $this->settings->get_user_setting('sap_li_sess_data',$user_id);

        if (!empty($sap_li_sess_data) && isset($sap_li_sess_data[$app_id]['sap_li_oauth']['linkedin']['access'])) {

            $li_access_data = $sap_li_sess_data[$app_id]['sap_li_oauth']['linkedin']['access'];

            $access_tocken = isset($li_access_data['access_token']) ? $li_access_data['access_token'] : '';
        }
        return $access_tocken;
    }

    /**
     * Make Logged In User to LinekedIn
     * 
     * @package Social Auto Poster
     * @since 1.0.0
     */
    public function sap_li_user_logged_in($user_id='') {

        $sap_linkedin_options = $this->settings->get_user_setting('sap_linkedin_options', $user_id);

        $linkedin_keys = isset($sap_linkedin_options['linkedin_keys']) ? $sap_linkedin_options['linkedin_keys'] : array();

        //check if user is logged in to linkedin
        if (isset($_GET['grant_li']) && $_GET['grant_li'] == 'true' && isset($_GET['code']) && isset($_REQUEST['state']) && isset($_GET['li_app_id'])) {
           
            //record logs for grant extended permission
            $this->sap_common->sap_script_logs('Linkedin Grant Extended Permission', $user_id);

            //record logs for get parameters set properly
            $this->sap_common->sap_script_logs('Get Parameters Set Properly.', $user_id);

            // Get linkedin app key/Id
            $li_app_id = $_GET['li_app_id'];

            $li_app_secret = '';

            foreach ($linkedin_keys as $linkedin_key => $linkedin_value) {

                if (in_array($li_app_id, $linkedin_value)) {

                    $li_app_secret = $linkedin_value['app_secret'];
                }
            }

            $callbackUrl = SAP_SITE_URL.'/settings/' . '?grant_li=true&li_app_id=' . $li_app_id;

            //load linkedin class
            $linkedin = $this->sap_load_linkedin($li_app_id, $user_id);

            //check linkedin loaded or not
            if (!$linkedin)
                return false;

            //Get Access token
            $arr_access_token = $this->linkedin->getAccessToken($li_app_id, $li_app_secret, $callbackUrl);

            // code will excute when user does connect with linked in
            if (!empty($arr_access_token['access_token'])) { // if user allows access to linkedin
                // the request went through without an error, gather user's 'access' tokens
                $_SESSION['sap_linkedin_oauth']['linkedin']['access'] = $arr_access_token;

                // set the user as authorized for future quick reference
                $_SESSION['sap_linkedin_oauth']['linkedin']['authorized'] = TRUE;

                //Get User Profiles
                $resultdata = $this->linkedin->getProfile();

                //set user data to sesssion for further use
                $_SESSION['sap_li_cache'] = $resultdata;
                $_SESSION['sap_li_user_id'] = isset($resultdata['id']) ? $resultdata['id'] : '';

                //Get company data
                $company_data = $this->sap_li_get_company_data($li_app_id,$user_id);
  
                //update company data in session
                $_SESSION['sap_li_companies'] = $company_data;

                //Get group data
                $group_data = $this->sap_li_get_group_data($li_app_id, $resultdata['id'],$user_id);
                //Update group data in session
                $_SESSION['sap_li_groups'] = $group_data;

                //set user data  to session
                $this->sap_set_li_data_to_session($li_app_id,$user_id);

                // unset session data so there will be no probelm to grant extend another account
                unset($_SESSION['sap_linkedin_oauth']);
                unset($_SESSION['sap_li_oauth']);

                $_SESSION['sap_active_tab'] = 'linkedin';
                header("Location:" . SAP_SITE_URL . "/settings/");
                exit;
            } else {
                
            }
        }
        elseif (isset($_GET['wpw_auto_poster_li_app_method']) && $_GET['wpw_auto_poster_li_app_method'] == 'appmethod') {
             if (isset($_GET['access_token']) && $_GET['access_token'] != '' && $_GET['wpw_li_grant'] == 'true') {
                
                    if (!empty($_GET['access_token'])) { 
                    $this->grantaccessToken = $_GET['access_token'];
                  
                    $_SESSION['sap_linkedin_oauth']['linkedin']['access'] = $this->grantaccessToken;

                    // set the user as authorized for future quick reference
                    $_SESSION['sap_linkedin_oauth']['linkedin']['authorized'] = TRUE;

                     if (!class_exists('LinkedInOAuth2')) {
                        include LIB_PATH . 'Social/Linkedin/LinkedIn.OAuth2.class.php';
                    }

                    //Get User Profiles.
                    $this->linkedinAppMethod = new LinkedInOAuth2($this->grantaccessToken);

                    $resultdata = $this->linkedinAppMethod->getProfile();
             
                    $_SESSION['sap_li_cache']   = $resultdata;
                    $_SESSION['sap_li_user_id'] = isset($resultdata['id']) ? $resultdata['id'] : '';

                    $company_data = $this->sap_li_get_company_data($li_app_id,$user_id);

                    $_SESSION['sap_li_companies'] = $company_data;

                    $group_data = $this->sap_li_get_group_data(SAP_NEW_LI_APP_METHOD_ID, $resultdata['id'], $$user_id);
                    
                    $_SESSION['sap_li_groups'] = $group_data;
          
                    $this->sap_set_li_appmethod_to_session($resultdata['id'],$user_id);

                    // unset session data so there will be no probelm to grant extend another account
                    unset($_SESSION['sap_linkedin_oauth']);
                    unset($_SESSION['sap_li_oauth']);

                    $_SESSION['sap_active_tab'] = 'linkedin';
                    header("Location:" . SAP_SITE_URL . "/settings/");
                    exit;
                } else {
                    
                }
             }

        }
         elseif (isset($_GET['grant_li']) && $_GET['grant_li'] == 'true' && !empty($_GET['error_description'])) {
            $this->flash->setFlash($_GET['error_description'], 'error');
            $_SESSION['sap_active_tab'] = 'linkedin';
            header("Location:" . SAP_SITE_URL . "/settings/");
            exit;
        }
    }

    /**
     * Get LinkedIn Login URL
     * 
     * Handles to Return LinkedIn URL
     * 
     * @package Social Auto Poster
     * @since 1.0.0
     */
    public function sap_get_li_login_url($app_id = false, $user_id='') {

        $sap_linkedin_options = $this->settings->get_user_setting('sap_linkedin_options',$user_id);


        $scope = array('w_member_social', 'r_liteprofile', 'w_member_social', 'r_emailaddress');

        if ($sap_linkedin_options['enable_company_pages'] == 'on') {

            $scope[] = 'rw_organization_admin';
            $scope[] = 'w_organization_social';
        }

        //load linkedin class
        $linkedin = $this->sap_load_linkedin($app_id,$user_id);

        //check linkedin loaded or not
        if (!$linkedin)
            return false;


        $portvalue = $this->common->is_ssl() ? 'https://' : 'http://';
        $redirect_URL = $portvalue . $_SERVER['SERVER_NAME'] . $_SERVER['REQUEST_URI'];
        if ( isset( $_SERVER['SERVER_PORT'] ) && !empty( $_SERVER['SERVER_PORT'] ) ) {
            if ( strpos( $_SERVER['HTTP_HOST'] , $_SERVER['SERVER_PORT'] ) && $_SERVER['SERVER_NAME'] !== $_SERVER['HTTP_HOST'] ) {
                $redirect_URL = $portvalue . $_SERVER['SERVER_NAME'] . ':' . $_SERVER['SERVER_PORT'] . $_SERVER['REQUEST_URI'];
            }
        }
        
        $url_args = '?grant_li=true&li_app_id=' . $app_id;
        $callbackUrl = $redirect_URL . $url_args;

        try {//Prepare login URL
            $preparedurl = $this->linkedin->getAuthorizeUrl($app_id, $callbackUrl, $scope);
        } catch (Exception $e) {
            $preparedurl = '';
        }



        return $preparedurl;
    }

    /**
     * Linkedin Get Company Data
     * 
     * @package Social Auto Poster
     * @since 1.5.0
     */
    public function sap_li_get_company_data($app_id, $user_id='') {

        $sap_linkedin_options = $this->settings->get_user_setting('sap_linkedin_options', $user_id);

        $linkedin_auth_options = !empty($sap_linkedin_options['linkedin_auth_options']) ? $sap_linkedin_options['linkedin_auth_options'] : 'appmethod';
        
        $company_data = array();

        // Get stored li app grant data
        $sap_li_sess_data = $this->settings->get_user_setting('sap_li_sess_data',$user_id);

        if (isset($sap_li_sess_data[$app_id]['sap_li_companies'])) {

            $company_data = $sap_li_sess_data[$app_id]['sap_li_companies'];

        } else {

            //Load linkedin class
            $this->sap_load_linkedin($app_id,$user_id);

            if (!empty($this->linkedin) || !empty($this->linkedinAppMethod)) { 

                if ($linkedin_auth_options == 'appmethod') {
                    $results = $this->linkedinAppMethod->getAdminCompanies();
               
                }else{
                    $results = $this->linkedin->getAdminCompanies();
                }

                //Companies data
                $companies = isset($results['elements']) ? $results['elements'] : array();
            
                if( !empty( $companies ) ) {//If company data is not empty
                    foreach ( $companies as $company ) {
                        
                        //Get company Id
                        $company_array_id   = isset( $company['organizationalTarget~']['id'] ) ? $company['organizationalTarget~']['id'] : '';
                        //Get company name
                        $company_array_name = isset( $company['organizationalTarget~']['localizedName'] ) ? $company['organizationalTarget~']['localizedName'] : '';
                        
                        //If company Id not found
                        if( !empty( $company_array_id ) ) {
                            $company_data[$company_array_id]    = $company_array_name;
                        }
                    }
                }
                
            }
        }

        return $company_data;
    }

    /**
     * Get LinkedIn User Data
     *
     * Function to get LinkedIn User Data
     *
     * @package Social Auto Poster
     * @since 1.0.0
     */
    public function sap_get_li_user_data($user_id='') {

        $sap_li_sess_data = $this->settings->get_user_setting('sap_li_sess_data',$user_id);

        $user_profile_data = '';

        if (isset($_SESSION['sap_li_cache']) && !empty($_SESSION['sap_li_cache'])) {

            $user_profile_data = $_SESSION['sap_li_cache'];
        }

        return $user_profile_data;
    }

    /**
     * Set Session Data of linkedin to session
     * 
     * Handles to set user data to session
     * 
     * @package Social Auto Poster
     * @since 1.0.0
     */
    public function sap_set_li_data_to_session($li_app_id = false,$user_id='') {

        //fetch user data who is grant the premission
        $liuserdata = $this->sap_get_li_user_data($user_id);

        if (isset($liuserdata['id']) && !empty($liuserdata['id'])) {

            try {

                $_SESSION['sap_li_user_id'] = isset($_SESSION['sap_li_user_id']) ? $_SESSION['sap_li_user_id'] : $liuserdata['id'];

                $_SESSION['sap_li_cache'] = isset($_SESSION['sap_li_cache']) ? $_SESSION['sap_li_cache'] : $liuserdata;

                $_SESSION['sap_li_oauth'] = isset($_SESSION['sap_li_oauth']) ? $_SESSION['sap_li_oauth'] : $_SESSION['sap_linkedin_oauth'];

                $_SESSION['sap_li_companies'] = isset($_SESSION['sap_li_companies']) ? $_SESSION['sap_li_companies'] : '';

                $_SESSION['sap_li_groups'] = isset($_SESSION['sap_li_groups']) ? $_SESSION['sap_li_groups'] : '';

                // start code to manage session from database 			
                $sap_li_sess_data = $this->settings->get_user_setting('sap_li_sess_data',$user_id);

                if (empty($sap_li_sess_data)) {

                    $sap_li_sess_data = array();
                    $sap_li_sess_data[$li_app_id] = array(
                        'sap_li_user_id' => $_SESSION['sap_li_user_id'],
                        'sap_li_cache' => $liuserdata,
                        'sap_li_oauth' => $_SESSION['sap_linkedin_oauth'],
                        'sap_li_companies' => $_SESSION['sap_li_companies'],
                        'sap_li_groups' => $_SESSION['sap_li_groups']
                    );
                    $this->settings->update_user_setting('sap_li_sess_data', $sap_li_sess_data);
                    
                    $this->sap_common->sap_script_logs('Linkedin Session Data Updated to Options', $user_id);
                }

                if (!isset($sap_li_sess_data[$li_app_id])) {

                    $sess_data = array(
                        'sap_li_user_id' => $_SESSION['sap_li_user_id'],
                        'sap_li_cache' => $liuserdata,
                        'sap_li_oauth' => $_SESSION['sap_linkedin_oauth'],
                        'sap_li_companies' => $_SESSION['sap_li_companies'],
                        'sap_li_groups' => $_SESSION['sap_li_groups']
                    );

                    $sap_li_sess_data[$li_app_id] = $sess_data;
                    $orignal_result = $this->settings->get_user_setting('sap_li_sess_data',$user_id);
                    if (!empty($orignal_result) && $li_app_id) {

                        $final_data = array_merge($orignal_result, $sap_li_sess_data);
                        $this->settings->update_user_setting('sap_li_sess_data', $final_data);
                        $this->sap_common->sap_script_logs('Linkedin Session Data Updated to Options', $user_id);
                    } else {

                        $this->settings->update_user_setting('sap_li_sess_data', $sap_li_sess_data);
                        $this->sap_common->sap_script_logs('Linkedin Session Data Updated to Options', $user_id);
                    }
                }
            } catch (Exception $e) {

                $liuserdata = null;
            }
        }
    }

     /**
     * Set Session Data of linkedin to session
     * 
     * Handles to set user data to session
     * 
     * @package Social Auto Poster
     * @since 1.0.0
     */
    public function sap_set_li_appmethod_to_session($liuserid = false,$user_id='') {

        //fetch user data who is grant the premission
        $liuserdata = $this->sap_get_li_user_data($user_id);

        if (isset($liuserdata['id']) && !empty($liuserdata['id'])) {

            try {

                $_SESSION['sap_li_user_id'] = isset($_SESSION['sap_li_user_id']) ? $_SESSION['sap_li_user_id'] : $liuserdata['id'];

                $_SESSION['sap_li_cache'] = isset($_SESSION['sap_li_cache']) ? $_SESSION['sap_li_cache'] : $liuserdata;

                $_SESSION['sap_li_oauth'] = isset($_SESSION['sap_li_oauth']) ? $_SESSION['sap_li_oauth'] : $_SESSION['sap_linkedin_oauth'];

                $_SESSION['sap_li_companies'] = isset($_SESSION['sap_li_companies']) ? $_SESSION['sap_li_companies'] : '';

                $_SESSION['sap_li_groups'] = isset($_SESSION['sap_li_groups']) ? $_SESSION['sap_li_groups'] : '';

                // start code to manage session from database           
                $sap_li_sess_data = $this->settings->get_user_setting('sap_li_sess_data',$user_id);

                if (empty($sap_li_sess_data)) {

                    $sap_li_sess_data = array();
                    $sap_li_sess_data[$liuserid] = array(
                        'sap_li_user_id' => $_SESSION['sap_li_user_id'],
                        'sap_li_cache' => $liuserdata,
                        'sap_li_oauth' => $_SESSION['sap_linkedin_oauth'],
                        'sap_li_companies' => $_SESSION['sap_li_companies'],
                        'sap_li_groups' => $_SESSION['sap_li_groups']
                    );
                    $this->settings->update_user_setting('sap_li_sess_data', $sap_li_sess_data);
                    
                    $this->sap_common->sap_script_logs('Linkedin Session Data Updated to Options', $user_id);
                }

                if (!isset($sap_li_sess_data[$liuserid])) {

                    $sess_data = array(
                        'sap_li_user_id' => $_SESSION['sap_li_user_id'],
                        'sap_li_cache' => $liuserdata,
                        'sap_li_oauth' => $_SESSION['sap_linkedin_oauth'],
                        'sap_li_companies' => $_SESSION['sap_li_companies'],
                        'sap_li_groups' => $_SESSION['sap_li_groups']
                    );

                    $sap_li_sess_data[$liuserid] = $sess_data;
                    $orignal_result = $this->settings->get_user_setting('sap_li_sess_data',$user_id);
                    if (!empty($orignal_result) && $liuserid) {

                        $final_data = array_merge($orignal_result, $sap_li_sess_data);
                        $this->settings->update_user_setting('sap_li_sess_data', $final_data);
                        $this->sap_common->sap_script_logs('Linkedin Session Data Updated to Options', $user_id);
                    } else {

                        $this->settings->update_user_setting('sap_li_sess_data', $sap_li_sess_data);
                        $this->sap_common->sap_script_logs('Linkedin Session Data Updated to Options', $user_id);
                    }
                }
            } catch (Exception $e) {

                $liuserdata = null;
            }
        }
    }

    /**
     * Linkedin Get Group Data
     * 
     * @package Social Auto Poster
     * @since 1.5.0
     */
    public function sap_li_get_group_data($app_id, $profile_id,$user_id='') {

        $sap_linkedin_options = $this->settings->get_user_setting('sap_linkedin_options', $user_id);

        $linkedin_auth_options = !empty($sap_linkedin_options['linkedin_auth_options']) ? $sap_linkedin_options['linkedin_auth_options'] : 'appmethod';

        //Initilize group array
        $group_data = array();

        // Get stored li app grant data
        $sap_li_sess_data = $this->settings->get_user_setting('sap_li_sess_data',$user_id);

        if (isset($sap_li_sess_data[$app_id]['sap_li_groups'])) {

            $group_data = $sap_li_sess_data[$app_id]['sap_li_groups'];
        } else {

            //Load linkedin class
            $this->sap_load_linkedin($app_id,$user_id);

              if (!empty($this->linkedin) || !empty($this->linkedinAppMethod)) { 

                if ($linkedin_auth_options == 'appmethod') {
                    $results = $this->linkedinAppMethod->getGroups($profile_id);
                }else{
                    $results = $this->linkedin->getGroups($profile_id); 
                }

                //Get groups data    
                $groups = isset($results['elements']) ? $results['elements'] : array();

                if (!empty($groups)) {

                    foreach ($groups as $group) {

                        //Get code is owner/member
                        $membershipState = isset($group['membershipState']['code']) ? $group['membershipState']['code'] : '';

                        if ($membershipState == 'owner' && !empty($group['group'])) {//If group owner
                            $group_details = $this->linkedin->getGroup($group['group']);
                            if (!empty($group_details) && !empty($group_details['id'])) {
                                //Get group Id
                                $group_id = $group_details['id'];

                                //Get group name
                                $group_name = isset($group_details['title']) ? $group['title']['value'] : '';

                                if (!empty($group_id)) {//Group id is not empty
                                    $group_data[$group_id] = $group_name;
                                }
                            }
                        }
                    }
                }
            }
        }

        return $group_data;
    }

    /**
     * Get LinkedIn Profiles
     * 
     * Function to get LinkedIn profiles
     * UserWall/Company/Groups
     * 
     * @package Social Auto Poster
     * @since 1.0.0
     */
    public function sap_li_get_profiles_data($user_id='') {

        $profiles = array();

        //Get Users Data
        $users = $this->sap_get_li_users($user_id);

        //Get Company Data
        $companies = $this->sap_get_li_companies($user_id);

        //Get Groups Data
        $groups = $this->sap_get_li_groups($user_id);

        if (!empty($users)) {//If User Data is not empty
            foreach ($users as $app_id => $user_value) {

                $user_id = isset($user_value['id']) ? $user_value['id'] : '';
                $first_name = isset($user_value['localizedFirstName']) ? $user_value['localizedFirstName'] : '';
                $last_name = isset($user_value['localizedLastName']) ? $user_value['localizedLastName'] : '';

                if (!empty($user_id)) {

                    $profiles['user:|:' . $user_id . ':|:' . $app_id] = $first_name . ' ' . $last_name . ' ' . '( ' . $user_id . ' )';
                }
            }
        }

        if (!empty($companies)) {//If Company Data is not empty
            foreach ($companies as $app_id => $company_details) {

                foreach ($company_details as $company_id => $company_name) {
                    $profiles['company:|:' . $company_id . ':|:' . $app_id] = $company_name;
                }
            }
        }

        if (!empty($groups)) {//If Group Data is not empty
            foreach ($groups as $app_id => $group_details) {

                foreach ($group_details as $group_id => $group_name) {
                    $profiles['group:|:' . $group_id . ':|:' . $app_id] = $group_name;
                }
            }
        }

        return $profiles;
    }

    /**
     * Linkedin Get All User Data
     * 
     * @package Social Auto Poster
     * @since 1.0.0
     */
    public function sap_get_li_users($user_id='') {

        // Get stored li app grant data
        $sap_li_sess_data = $this->settings->get_user_setting('sap_li_sess_data',$user_id);

        //Initilize users array
        $user_profile_data = array();

        if (!empty($sap_li_sess_data)) {

            foreach ($sap_li_sess_data as $sess_key => $sess_data) {

                if (isset($sess_data['sap_li_cache']) && !empty($sess_data['sap_li_cache'])) {

                    $user_profile_data[$sess_key] = $sess_data['sap_li_cache'];
                }
            }
        }
        return $user_profile_data;
    }

    /**
     * Linkedin Get All Company Data
     * 
     * @package Social Auto Poster
     * @since 1.0.0
     */
    public function sap_get_li_companies($user_id='') {

        $sap_li_sess_data = $this->settings->get_user_setting('sap_li_sess_data', $user_id);

        //Initilize company array
        $company_data = array();

        if (!empty($sap_li_sess_data)) {

            foreach ($sap_li_sess_data as $sess_key => $sess_data) {

                if (isset($sess_data['sap_li_companies']) && !empty($sess_data['sap_li_companies'])) {

                    $company_data[$sess_key] = $sess_data['sap_li_companies'];
                }
            }
        }

        return $company_data;
    }

    /**
     * Linkedin Get All Group Data
     * 
     * @package Social Auto Poster
     * @since 1.0.0
     */
    public function sap_get_li_groups($user_id='') {

        $sap_li_sess_data = $this->settings->get_user_setting('sap_li_sess_data',$user_id);

        //Initilize group array
        $group_data = array();

        if (!empty($sap_li_sess_data)) {

            foreach ($sap_li_sess_data as $sess_key => $sess_data) {

                if (isset($sess_data['sap_li_groups']) && !empty($sess_data['sap_li_groups'])) {

                    $group_data[$sess_key] = $sess_data['sap_li_groups'];
                }
            }
        }

        return $group_data;
    }

    /**
     * Post To LinkedIn
     * 
     * Handles to Posting to Linkedin User Wall,
     * Company Page / Group Posting
     * 
     * @package Social Auto Poster
     * @since 1.0.0
     */
    public function sap_post_to_linkedin($post_id) {
        if ($post_id) {

            $this->posts = new SAP_Posts();

            $post = $this->posts->get_post($post_id, true);
            $user_id = isset( $post->user_id ) ? $post->user_id : '';

            $sap_linkedin_options = $this->settings->get_user_setting('sap_linkedin_options', $user_id);

            $sap_li_sess_data = $this->settings->get_user_setting('sap_li_sess_data', $user_id);

            // General setting
            $sap_general_options = $this->settings->get_user_setting('sap_general_options',$user_id);

            $link_timestamp = isset($sap_general_options['timestamp_link']) ? "?".time() : '';

            //Getting linkedin apps
            $li_apps = $this->sap_get_li_apps($user_id);

            //meta prefix
            $prefix = '_sap_';
            $linkedin_auth_options = !empty($sap_linkedin_options['linkedin_auth_options']) ? $sap_linkedin_options['linkedin_auth_options'] : 'appmethod';


            //Initilize linkedin posting
            $li_posting = $posting_log = array();

            //check linkedin authorized session is true or not  //need to do for linkedin posting code
            if (!empty($sap_li_sess_data) && isset($post_id)) {

                $unique = 'false';

                $ispublished = $this->posts->get_post_meta($post_id, $prefix . 'li_status');

                //custom title from metabox
                $customtitle = $this->posts->get_post_meta($post_id, $prefix . 'li_post_title');

                //custom title set use it otherwise user posttiel
                $title = !empty($customtitle) ? $customtitle : '';

                /*                 * ************
                 * Image Priority
                 * If metabox image set then take from metabox
                 * If metabox image is not set then take from content image
                 * If content image is not set then take from settings page
                 * ************ */
                $postimage = $this->posts->get_post_meta($post_id, $prefix . 'li_post_image');

                $global_img = $sap_linkedin_options['linkedin_image'];
                $featured_img = $post->img;
                $post_img = !empty($postimage) ? ($postimage) : $featured_img;
                $post_img = !empty($post_img) ? ($post_img) : $global_img;

                //post link
                $postlink = $this->posts->get_post_meta($post_id, $prefix . 'li_post_link');
                $postlink = !empty($postlink) ? $postlink : $post->share_link;

                if(!empty($postlink)) {
                    $postlink = $postlink."".$link_timestamp;
                }

                $customlink  = !empty($postlink) ? 'true' : 'false';
                $postlink     = $this->common->sap_script_short_post_link($postlink,$customlink,'li','linkedin', $user_id);

                //if post is published on linkedin once then change url to prevent duplication
                if (isset($ispublished) && $ispublished == '1') {
                    $unique = 'true';
                }

                //get linkedin posting description
                $description = $this->posts->get_post_meta($post_id, $prefix . 'li_post_desc');
                $description = !empty($description) ? $description : $post->body;

                //Get description html_entity_decode($str)
                $description = html_entity_decode($description, ENT_QUOTES);
               
                $posting_type = $this->posts->get_post_meta($post_id, '_sap_li_status');

                // Post limit 3000 character pre post
                if (!empty($description))
                    $description = $this->posts->sap_limit_character($description, 3000);

              
                //Get titlehtml_entity_decode($str)
                $title = !empty($title) ? html_entity_decode($title, ENT_QUOTES) : '';

                $li_post_profiles = $this->posts->get_post_meta($post_id, $prefix . 'li_post_profile');
                $li_post_profiles = !empty($li_post_profiles) ? (explode(',', $li_post_profiles)) : '';

                $li_post_profiles = ( empty($li_post_profiles) && !empty($sap_linkedin_options['li_type_post_user']) ) ? $sap_linkedin_options['li_type_post_user'] : $li_post_profiles;
           
                if (empty($li_post_profiles)) {
                    $this->sap_common->sap_script_logs('LinkedIn user not selected.', $user_id);
                    $this->flash->setFlash('LinkedIn user not selected', 'error','',true);
                    //return false
                    return false;
                }

                $content = array(
                    'title' => $title,
                    'comment' => $description,
                    'description' => $description
                );

                $posting_log['link'] = $postlink;
                
                unset($posting_log['description']);
                

                if (!empty($post_img)) {
                    $content['submitted-image-url'] = SAP_IMG_URL . $post_img;
                    $posting_log['image'] = $content['submitted-image-url'];
                }
                $posting_log['message'] = $content['description'];
                if (!empty($postlink)) {
                    $content['submitted-url'] = $postlink;
                    
                } else {
                    $this->flash->setFlash('Post Share link or Custom Link required for Linkedin.', 'error','',true);
                    $this->sap_common->sap_script_logs('Post Share link or Custom Link required for Linkedin.', $user_id);
                    return false;
                }

                //Get all Profiles
                $profile_datas = $this->sap_li_get_profiles_data($user_id);

                //get user profile data
                $user_profile_data = $this->sap_get_li_user_data($user_id);

                //Initilize all user/company/group data
                $company_data = $group_data = $userwall_data = $display_name_data = $display_id_data = array();

                //initial value of posting flag
                $postflg = false;

                try {
                    if (!empty($li_post_profiles)) {

                        foreach ($li_post_profiles as $li_post_profile) {

                            $split_profile = explode(':|:', $li_post_profile);

                            $profile_type = isset($split_profile[0]) ? $split_profile[0] : '';
                            $profile_id = isset($split_profile[1]) ? $split_profile[1] : '';

                            // Linkedin App Id
                            $li_post_app_id = isset($split_profile[2]) ? $split_profile[2] : '';
                            
                            if($linkedin_auth_options == 'appmethod'){

                                 $access_token = $this->sap_li_get_access_token($li_post_profile, $user_id);

                            }else{

                                $access_token = $this->sap_li_get_access_token($li_post_app_id, $user_id);

                            }

                            $li_stored_app_data = isset($sap_li_sess_data[$li_post_app_id]) ? $sap_li_sess_data[$li_post_app_id] : array();
                            
                            $user_cache_data = isset($li_stored_app_data['sap_li_cache']) ? $li_stored_app_data['sap_li_cache'] : array();
                            
                            $li_post_app_sec = isset($li_apps[$li_post_app_id]) ? $li_apps[$li_post_app_id] : '';

                            if($linkedin_auth_options == 'appmethod'){

                                $linkedin = $this->sap_load_linkedin(SAP_NEW_LI_APP_METHOD_ID,$user_id);
                   
                            }else{
                                $linkedin = $this->sap_load_linkedin($li_post_app_id,$user_id);    
                            }
                         
                            // Check linkedin class is exis or not
                            if (!$linkedin) {
                                $this->flash->setFlash('Linkedin not initialized with ' . $li_post_app_id . ' App.', 'error','',true);
                                return false;
                            }

                            if ($profile_type == 'user' && $user_cache_data['id'] == $profile_id) {
                                $user_first_name = isset($user_cache_data['localizedFirstName']) ? $user_cache_data['localizedFirstName'] : '';
                                $user_last_name = isset($user_cache_data['localizedLastName']) ? $user_cache_data['localizedLastName'] : '';
                                $user_email = isset($user_cache_data['email-address']) ? $user_cache_data['email-address'] : '';
                                $profile_url = isset($user_cache_data['publicProfileUrl']) ? $user_cache_data['publicProfileUrl'] : '';
                                $display_name = $user_first_name . ' ' . $user_last_name;
                            }

                           
                            switch ($profile_type) {

                                case 'user':
                                    if (!empty($profile_id)) {
                                        //Filter content
                                        $response = $this->linkedin->shareStatus($content, 'urn:li:person:' . $profile_id, $access_token);
                                        if( !empty( $response['id'] ) ) {
                                            $this->flash->setFlash('Linkedin : Post sucessfully posted on - ' . $display_name, 'success','',true);
                                            $this->sap_common->sap_script_logs('Linkedin : Post sucessfully posted on - ' . $display_name, $user_id );
                                            $this->sap_common->sap_script_logs('Linkedin post data : ' . var_export($posting_log,true), $user_id);
                                            $postflg	= true;
                                        }
                                    }
                                    break;

                                case 'group':

                                    $title = !empty($title) ? $title : $description;
                                    $response = $this->linkedin->postToGroup($profile_id, $title, $description, $content);
                                    $this->flash->setFlash('Linkedin : Post sucessfully posted on - ' . $display_name, 'success','',true);
                                    $this->sap_common->sap_script_logs('Linkedin : Post sucessfully posted on - ' . $display_name, $user_id );
                                    $this->sap_common->sap_script_logs('Linkedin post data : ' . var_export($posting_log,true), $user_id);
                                    $postflg = true;
                                    break;

                                case 'company':
                                    $title = !empty($title) ? $title : $description;
                                    //Filter content and title
                                    $response = $this->linkedin->shareStatus($content, 'urn:li:organization:' . $profile_id, $access_token);
                                    if (!empty($response['id'])) {
                                        $this->flash->setFlash('Linkedin : Post sucessfully posted on - ' . $display_name, 'success','',true);
                                        $this->sap_common->sap_script_logs('Linkedin : Post sucessfully posted on - ' . $display_name, $user_id );
                                        $this->sap_common->sap_script_logs('Linkedin post data : ' . var_export($posting_log,true), $user_id);
                                        $postflg = true;
                                    }
                                    break;
                            }


                            if ($postflg) {
                                $posting_log['account name'] = $display_name;
                                $posting_log['link to post'] = 'https://www.linkedin.com/';
                                $this->logs->add_log('linkedIn', $posting_log, $posting_type, $user_id);
                                $li_posting['success'] = 1;
                            } else {
                                if ( isset( $response['serviceErrorCode'] ) || isset( $response['message'] ) ) {
                                    $errorMess = isset( $response['message'] ) ? $response['message'] : '';
                                }
                                $this->flash->setFlash('Linkedin error: '.$errorMess, 'error','',true);
                                $li_posting['fail'] = 1;
                            }
                        }
                    }
                } catch (Exception $e) {
                   
                    //record logs exception generated
                    return false;
                }

            } else {
                $this->flash->setFlash('Linkedin grant extended permissions not set.', 'error','',true);
                $this->sap_common->sap_script_logs('Linkedin grant extended permissions not set.',$user_id);
            }
        }

        return $li_posting;
    }

    /**
     * Fetching Linkedin
     *
     * Fetching all the Linkedin app and secret from database
     * Facebook user (site admin).
     *
     * @since 1.0.0
     */
    public function sap_get_li_apps($user_id='') {

        //Get linkedin options from linkedin
        $sap_linkedin_options = $this->settings->get_user_setting('sap_linkedin_options',$user_id);

        $li_apps = array();
        $li_keys = !empty($sap_linkedin_options['linkedin_keys']) ? $sap_linkedin_options['linkedin_keys'] : array();

        if (!empty($li_keys)) {

            foreach ($li_keys as $li_key_id => $li_key_data) {

                if (!empty($li_key_data['app_id']) && !empty($li_key_data['app_secret'])) {
                    $li_apps[$li_key_data['app_id']] = $li_key_data['app_secret'];
                }
            } // End of for each
        } // End of main if
        return $li_apps;
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
    public function sap_li_reset_session() {

        if (isset($_GET['li_reset_user']) && $_GET['li_reset_user'] == '1' && !empty($_GET['sap_li_app'])) {

            $li_app_id = $_GET['sap_li_app'];

            // Getting stored li app data
            $sap_li_sess_data = $this->settings->get_user_setting('sap_li_sess_data');

            // Unset particular app value data and update the option
            if (isset($sap_li_sess_data[$li_app_id])) {
                unset($sap_li_sess_data[$li_app_id]);
                $this->settings->update_user_setting('sap_li_sess_data', $sap_li_sess_data);
                
                $this->sap_common->sap_script_logs('Linkedin ' . $li_app_id . ' Account Reset Successfully.',$user_id);
            }

            /*             * ***** Code for selected category Linkdin account ***** */

            // unset selected Linkdin account option for category 
            $cat_selected_social_acc = array();
            $cat_selected_acc = $this->settings->get_user_setting('sap_category_posting_acct');
            $cat_selected_social_acc = (!empty($cat_selected_acc) ) ? $cat_selected_acc : $cat_selected_social_acc;

            if (!empty($cat_selected_social_acc)) {
                foreach ($cat_selected_social_acc as $cat_id => $cat_social_acc) {
                    if (isset($cat_social_acc['li'])) {
                        unset($cat_selected_acc[$cat_id]['li']);
                    }
                }

                // Update autoposter category FB posting account options
                $this->settings->update_user_setting('sap_category_posting_acct', $cat_selected_acc);
            }

            if (isset($_SESSION['sap_li_user_id'])) {//destroy userId session
                unset($_SESSION['sap_li_user_id']);
            }
            if (isset($_SESSION['sap_li_cache'])) {//destroy cache
                unset($_SESSION['sap_li_cache']);
            }
            if (isset($_SESSION['sap_li_oauth'])) {//destroy oauth
                unset($_SESSION['sap_li_oauth']);
            }
            if (isset($_SESSION['sap_li_companies'])) {//destroy company session
                unset($_SESSION['sap_li_companies']);
            }
            if (isset($_SESSION['sap_li_groups'])) {//destroy group session
                unset($_SESSION['sap_li_groups']);
            }
            if (isset($_SESSION['sap_linkedin_oauth'])) {//destroy linkedin session
                unset($_SESSION['sap_linkedin_oauth']);
            }

            $_SESSION['sap_active_tab'] = 'linkedin';
            header("Location:" . SAP_SITE_URL . "/settings/");
            exit;
        }
    }

    /**
     * Quick Post To LinkedIn
     * 
     * Handles to Posting to Linkedin User Wall,
     * Company Page / Group Posting
     * 
     * @package Social Auto Poster
     * @since 1.0.0
     */
    public function sap_quick_post_to_linkedin($post_id) {

        if ($post_id) {

            if (!class_exists('SAP_Quick_Posts')) {
                require_once( CLASS_PATH . 'Quick_Posts.php' );
            }

            $this->quick_posts = new SAP_Quick_Posts();
            
            $quick_post = $this->quick_posts->get_post($post_id, true);
            $user_id = isset( $quick_post->user_id ) ? $quick_post->user_id : '';

            $status_meta_array = array();
            $this->quick_posts = new SAP_Quick_Posts();
            $sap_networks_meta = $this->quick_posts->get_post_meta($post_id, 'sap_networks');
            $sap_networks_accounts = !empty($sap_networks_meta['li_accounts']) ? $sap_networks_meta['li_accounts'] : array();

            $sap_linkedin_options = $this->settings->get_user_setting('sap_linkedin_options', $user_id);

            $sap_li_sess_data = $this->settings->get_user_setting('sap_li_sess_data', $user_id);

            // General setting
            $sap_general_options = $this->settings->get_user_setting('sap_general_options',$user_id);

            $link_timestamp = isset($sap_general_options['timestamp_link']) ? "?".time() : '';

            $linkedin_auth_options = !empty($sap_linkedin_options['linkedin_auth_options']) ? $sap_linkedin_options['linkedin_auth_options'] : 'appmethod';
            //Initilize linkedin posting
            $li_posting = $posting_log = array();

            //check linkedin authorized session is true or not  //need to do for linkedin posting code
            if (!empty($sap_li_sess_data) && isset($post_id)) {

          
                $unique = 'false';

                //custom title set use it otherwise user posttiel
                $title = '';
                if (isset($sap_linkedin_options['linkedin_image'])) {

                    $linkedin_settings_image = $sap_linkedin_options['linkedin_image'];
                }

                $post_img = !empty($quick_post->image) ? $quick_post->image : $linkedin_settings_image;
                $postlink = !empty($quick_post->share_link) ? $quick_post->share_link : '';

                if(!empty($postlink)) {
                    $postlink = $postlink."".$link_timestamp;
                }

                $customlink  = !empty($postlink) ? 'true' : 'false';
                $postlink     = $this->common->sap_script_short_post_link($postlink,$customlink,'li','linkedin', $user_id);
     
                //get linkedin posting description
                $description = !empty($quick_post->message) ? html_entity_decode($quick_post->message, ENT_QUOTES) : '';

                //Get titlehtml_entity_decode($str)
                $title = !empty($title) ? html_entity_decode($title, ENT_QUOTES) : '';

                //Get comment
                $li_post_profiles = !empty($sap_networks_accounts) ? $sap_networks_accounts : array();

                 // Post limit 3000 character pre post
                if (!empty($description))
                    $description = $this->posts->sap_limit_character($description, 3000);

                $content = array(
                    'title' => $description,
                    'comment' => $description,
                    'description' => $description
                );

                $posting_log['link'] = $postlink;
                unset($posting_log['description']);
                

                if (!empty($post_img)) {
                    $content['submitted-image-url'] = SAP_IMG_URL . $post_img;
                    $posting_log['image'] = $content['submitted-image-url'];
                }

                if (!empty($postlink)) {
                    $content['submitted-url'] = $postlink;
                    
                } else {
                    $this->flash->setFlash('Post Share link required for Linkedin.', 'error','',true);
                    $this->sap_common->sap_script_logs('Post Share link required for Linkedin.',$user_id);
                    $status_meta_array[] = array(
                        "status" => 'error',
                        "message" => 'Post Share link required for Linkedin.'
                    );
                    $this->quick_posts->update_post_meta($post_id,"sap_li_posting_error", $status_meta_array);
                    return false;
                }
                
                
                
                $posting_log['message'] = $content['description'];

                //Get all Profiles
                $profile_datas = $this->sap_li_get_profiles_data($user_id);


                //get user profile data
                $user_profile_data = $this->sap_get_li_user_data($user_id);

                //Initilize all user/company/group data
                $company_data = $group_data = $userwall_data = $display_name_data = $display_id_data = array();

                //initial value of posting flag
                $postflg = false;

                try {
                    
                    if (!empty($li_post_profiles)) {

                        foreach ($li_post_profiles as $li_post_profile) {

                           $status_meta_key = isset( $profile_datas[$li_post_profile] ) ? $profile_datas[$li_post_profile] : $li_post_profile;

                            $split_profile = explode(':|:', $li_post_profile);
                            
                            $profile_type = isset($split_profile[0]) ? $split_profile[0] : '';
                            $profile_id = isset($split_profile[1]) ? $split_profile[1] : '';
                            $app_id = isset($split_profile[2]) ? $split_profile[2] : '';

                            if($linkedin_auth_options == 'appmethod'){

                                 $access_token = $this->sap_li_get_access_token($li_post_profile, $user_id);

                            }else{

                                $access_token = $this->sap_li_get_access_token($app_id, $user_id);

                            }
                            // Linkedin App Id
                            $li_post_app_id = isset($split_profile[2]) ? $split_profile[2] : '';
                            
                            
                            $li_stored_app_data = isset($sap_li_sess_data[$li_post_app_id]) ? $sap_li_sess_data[$li_post_app_id] : array();
                            
                            $user_cache_data = isset($li_stored_app_data['sap_li_cache']) ? $li_stored_app_data['sap_li_cache'] : array();
                            
                            
                            // Linkedin App Sec
                            $li_post_app_sec = isset($li_apps[$li_post_app_id]) ? $li_apps[$li_post_app_id] : '';

                            //load linkedin class
                            if($linkedin_auth_options == 'appmethod'){

                                $linkedin = $this->sap_load_linkedin(SAP_NEW_LI_APP_METHOD_ID,$user_id);
                   
                            }else{
                                $linkedin = $this->sap_load_linkedin($li_post_app_id,$user_id);    
                            }

                            if ($profile_type == 'user' && $user_cache_data['id'] == $profile_id ) {
                                $user_first_name = isset($user_cache_data['localizedFirstName']) ? $user_cache_data['localizedFirstName'] : '';
                                $user_last_name = isset($user_cache_data['localizedLastName']) ? $user_cache_data['localizedLastName'] : '';
                                $user_email = isset($user_cache_data['email-address']) ? $user_cache_data['email-address'] : '';
                                $profile_url = isset($user_cache_data['publicProfileUrl']) ? $user_cache_data['publicProfileUrl'] : '';
                                $display_name = $user_first_name . ' ' . $user_last_name;
                            }

                            switch ($profile_type) {

                                case 'user':

                                    if (!empty($profile_id)) {
                                        //Filter content
                                        $response = $this->linkedin->shareStatus($content, 'urn:li:person:' . $profile_id, $access_token);
                                        if (!empty($response['id'])) {
                                            $postflg = true;
                                            $this->sap_common->sap_script_logs('Linkedin : Post sucessfully posted on - ' . $display_name, $user_id );
                                            $this->sap_common->sap_script_logs('Linkedin post data : '. var_export($posting_log,true),$user_id);
                                            $this->flash->setFlash('Linkedin : Post sucessfully posted on - ' . $display_name, 'success','',true);
                                        }
                                    }
                                    break;
                                case 'group':

                                    $response = $this->linkedin->postToGroup($profile_id, $description, $description, $content);
                                    $this->flash->setFlash('Linkedin : Post sucessfully posted on - ' . $display_name, 'success','',true);
                                    $this->sap_common->sap_script_logs('Linkedin : Post sucessfully posted on - ' . $display_name, $user_id );
                                    $this->sap_common->sap_script_logs('Linkedin post data : ' . var_export($posting_log,true),$user_id);
                                    $postflg = true;
                                    break;

                                case 'company':

                                    //Filter content and title
                                    $response = $this->linkedin->shareStatus($content, 'urn:li:organization:' . $profile_id, $access_token);
                                    if (!empty($response['id'])) {
                                        $postflg = true;
                                        $this->flash->setFlash('Linkedin : Post sucessfully posted on - ' . $display_name, 'success','',true);
                                        $this->sap_common->sap_script_logs('Linkedin : Post sucessfully posted on - ' . $display_name,$user_id );
                                        $this->sap_common->sap_script_logs('Linkedin post data : '. var_export($posting_log,true),$user_id);
                                    }
                                    break;
                            }

                            if ($postflg) {
                                $posting_log['account name'] = $display_name; 
                                $posting_log['link to post'] = 'https://www.linkedin.com/';
                                $this->quick_posts->update_post_meta($post_id,"sap_li_link_to_post", 'https://www.linkedin.com/');
                                $this->logs->add_log('linkedIn', $posting_log, 1, $user_id);
                                $status_meta_array[$status_meta_key] = array(
                                    "status" => 'success'
                                );
                                $li_posting['success'] = 1;
                            } else {
                                $errorMess = '';
                                if ( isset( $response['serviceErrorCode'] ) || isset( $response['message'] ) ) {
                                    $errorMess = isset( $response['message'] ) ? $response['message'] : '';
                                }
                                $this->flash->setFlash('Linkedin error: '.$errorMess, 'error','',true);
                                $status_meta_array[$status_meta_key] = array(
                                    "status" => 'error',
                                    "message" => $errorMess
                                );
                                $li_posting['fail'] = 1;
                            }
                        }

                        $this->quick_posts->update_post_meta($post_id,"sap_li_posting_error", $status_meta_array);

                    }
                } catch (Exception $e) {

                    //record logs exception generated
                    return false;
                }
            } else {
                $this->flash->setFlash('Linkedin grant extended permissions not set.', 'error','',true);
                $this->sap_common->sap_script_logs('Linkedin grant extended permissions not set.',$user_id);
                $status_meta_array[] = array(
                    "status" => 'error',
                    "message" => 'Linkedin grant extended permissions not set.'
                );
                $this->quick_posts->update_post_meta($post_id,"sap_li_posting_error", $status_meta_array);
            }
        }


        return $li_posting;
    }

}
