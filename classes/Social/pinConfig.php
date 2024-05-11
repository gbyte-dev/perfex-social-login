<?php

/* Check the absolute path to the Social Auto Poster directory. */
if ( !defined( 'SAP_APP_PATH' ) ) {
    // If SAP_APP_PATH constant is not defined, perform some action, show an error, or exit the script
    // Or exit the script if required
    exit();
}

/**
 * Pinterest posting
 *
 * @package Social auto poster
 * @since 1.0.0
 */
class SAP_Pinterest {

    public $pinterest, $settings, $flash, $posts, $common, $logs, $sap_common, $quick_posts;

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

        $this->sap_pin_data_user_initialize($user_id);

        
    }

    /**
     * Assign Pinterest User's all Data to session
     *
     * Handles to assign user's pinterest data
     * to sessoin & save to database
     *
     * @package Mingle
     * @since 1.0.0
     */

    public function sap_pin_data_user_initialize($user_id=''){

        
        if (( isset($_GET['code']) && isset($_REQUEST['state']) && strpos($_REQUEST['state'], 'pinterestapp') !== false )) {

            $this->sap_common->sap_script_logs('Pinterest Grant Extended Permission', $user_id);
            //record logs for get parameters set properly
            
            $this->sap_common->sap_script_logs('Pinterest Grant Extended Permission', $user_id);
            $code       = $_GET['code'];
            $state      = $_GET['state'];
            $app        = explode('#', $state);
            $pin_app_id = $app[1];
            
            try {
                //load pinterest class
                $pinterest = $this->sap_load_pinterest($pin_app_id);
            } catch (Exception $e) {
                //record logs exception generated
                $this->sap_common->sap_script_logs('Pinterest error: ' . $e->getMessage(), $user_id);
                $pinterest = null;
            }

            //check pinterest class is exis or not
            if (!$pinterest) {
                return false;
            }

            // Pinterest
            try {
                $token = $this->pinterest->auth->getOAuthToken($code);
            } catch (Exception $e) {

                //record logs exception generated
                $this->sap_common->sap_script_logs('Pinterest error: ' . $e->getMessage(), $user_id);
            }
            
            $me = false;
            if (!empty($token->access_token)) {

                $pin_access_token = $token->access_token;
                try {
                    $grant = $this->pinterest->auth->setOAuthToken($token->access_token);
                    $me = $this->pinterest->users->me(
                        array(
                            'fields' => 'username,first_name,last_name'
                        )
                    );

                    //record logs for user id
                    $this->sap_common->sap_script_logs('Pinterest User ID : ' . $me->id, $user_id);
                } catch (Exception $e) {
                    //record logs exception generated
                    $this->sap_common->sap_script_logs('Pinterest error: ' . $e->getMessage(), $user_id);
                }
           }     

            //check user is logged in pinterest or not
       
            if ($me) {

                try {
                // Proceed knowing you have a logged in user who's authenticated.
                   $sap_pin_user_id = $me->username;
                   $sap_pin_user_name = $me->username;
                   $sap_pin_user_url = $me->website_url;  

                    $boards = $this->pinterest->users->getMeBoards(array(
                     'fields' => 'name,url'
                    ));
                     $i = 0;

                     $boardList = $selectBoard = array();
                     foreach ($boards->items as $boardu) {
                         
                         $board_user = $boardu['owner']['username'];
                         $boardList[$boardu['id']] = array(
                            'id'   => $boardu['id'],
                            'name' => $boardu['name']
                         );
                         $selectBoard[$boardu['id']] = array(
                            'id'   => $boardu['id'],
                            'name' => $boardu['name']
                         );
                     }
 
                     $available_boards = $boardList;
                     // For record
                     $selectedBoards = $selectBoard;
                     $sap_pinterest_sess_data = $this->settings->get_user_setting('sap_pin_sess_data', $user_id);     
                     

                     if (!isset($sap_pinterest_sess_data[$pin_app_id])) {
                     
                        $sess_data = array(
                            'id' => $sap_pin_user_id,
                            'username' => $sap_pin_user_name,
                            'boards' => $available_boards,
                            'auth_type'  => 'app',
                            'pin_access_token' => $pin_access_token,
                        );  

                        
                        if ($pin_app_id) {
                         
                            // Save Multiple Accounts
                            $pinterest_sess = array();
                            $pinterest_sess[$pin_app_id] = $sess_data;

                            if(!empty($sap_pinterest_sess_data)){
                               
                                $sap_pinterest_sess_data = array_replace($sap_pinterest_sess_data, $pinterest_sess);
                                $this->settings->update_user_setting('sap_pin_sess_data', $sap_pinterest_sess_data);
                            } else {
                                // Update session data to options
                                $this->settings->update_user_setting('sap_pin_sess_data', $pinterest_sess);
                            }

                        

                            // Record logs for session data updated to options
                            $this->sap_common->sap_script_logs('Session Data Updated to Options', $user_id);
                        } else {
                            // Record logs when app id is not found
                            $this->sap_common->sap_script_logs("Pinterest error: The App Id {$pin_app_id} does not exist.", $user_id);
                        }
                     }   
                     $this->sap_common->sap_script_logs('Grant Extended Permission Successfully.', $user_id);
                } catch (Exception $e) {
                    //record logs exception generated
                    $this->logs->wpw_auto_poster_add('Pinterest error: ' . $e->__toString());

                    //user is null
                    $me = null;
                } //end catch
                $_SESSION['sap_active_tab'] = 'pinterest';
                header("Location:" . SAP_SITE_URL . "/settings/");
                exit;
            }    
        }    
    } 

    /**
     * Fetching Pinterest
     *
     * Fetching all the Pinterest app and secret from database
     * Pinterest user (site admin).
     *
     * @package Social Auto Poster
     * @since 1.0.0
     */
    public function sap_get_pin_apps_with_boards($user_id='') {

        //Get pinterest options from pinterest
        $sap_pinterest_sess_data = $this->settings->get_user_setting('sap_pin_sess_data', $user_id);
        $pin_apps = array();
        if (is_array($sap_pinterest_sess_data) && !empty($sap_pinterest_sess_data)) {
            foreach ($sap_pinterest_sess_data as $username => $pinSite) {

                if (empty($pinSite['boards']))
                    continue;
                foreach ($pinSite['boards'] as $key => $board) {
                    $key = $username . '|' . $board['id'];
                    $value = $username . ' - ' . $board['name'];

                    $pin_apps[$key] = $value;
                }
            }
        }

        return $pin_apps;
    }

    /**
     * Fetching Facebook
     *
     * Fetching all the Pinterest app and secret from database
     * Pinterest user (site admin).
     *
     * @package Social auto poster
     * @since 1.0.0
    */
    public function sap_get_pinterest_apps($user_id='') {

        //Get facebook options from facebook
        $sap_pinterest_options = $this->settings->get_user_setting('sap_pinterest_options',$user_id);

        $pin_apps = array();
        $pin_keys = !empty($sap_pinterest_options['pinterest_keys']) ? $sap_pinterest_options['pinterest_keys'] : array();

        if (!empty($pin_keys)) {

            foreach ($pin_keys as $pin_key_id => $pin_key_data) {

                if (!empty($pin_key_data['app_id']) && !empty($pin_key_data['app_secret'])) {
                    $pin_apps[$pin_key_data['app_id']] = $pin_key_data['app_secret'];
                }
            } // End of for each
        } // End of main if
        return $pin_apps;
    }

    /**
     * Load Pinterest App method
     * 
     * Load Pinterest
     * its link within the settings page.
     * 
     * @package Social Auto Poster
     * @since 1.0.0
     */

    public function sap_load_pinterest( $app_id = false, $user_id = '' ) {


        $pin_apps = $this->sap_get_pinterest_apps($user_id);
        // If app id is not passed then take first pinterest app data
        if (empty($app_id)) {
            $pin_apps_keys = array_keys($pin_apps);
            $app_id = reset($pin_apps_keys);
        }

        // Check whether application id and application secret is set or not
        if (!empty($app_id) && !empty($pin_apps[$app_id])) {
            require_once( LIB_PATH . 'Social' . DS . 'pinterest' . DS . 'autoload.php' );
            $this->pinterest = new DirkGroenen\Pinterest\Pinterest($app_id, $pin_apps[$app_id]);
            return true;
        } else {
            return false;
        }
    }

     /**
     * Get Login Url for pinterest app method
     * 
     * Login Url - Pinterest
     * 
     * @package Social Auto Poster
     * @since 1.0.0
     */

    public function sap_pinterest_login_url( $app_id = false, $user_id='' ){

        $pinterest = $this->sap_load_pinterest( $app_id, $user_id );
        if (!$pinterest) {
            return false;
        }

        $redirect_URL = SAP_SITE_URL.'/settings/';
        $this->pinterest->auth->setState('pinterestapp#'.$app_id);
        $loginUrl = $this->pinterest->auth->getLoginUrl($redirect_URL, array('pins:write
','pins:read', 'boards:read','boards:write','user_accounts:read'));

        return $loginUrl;

    }

    /**
     * Reset Sessions
     * 
     * Resetting the Pinterest sessions from the listing of apps
     * its link within the settings page.
     * 
     * @package Social Auto Poster
     * @since 1.0.0
     */

    public function sap_pin_reset_session_from_apps(){

        if (isset($_GET['pin_reset_user']) && $_GET['pin_reset_user'] == '1' && !empty($_GET['sap_pinterest_app_id'])) {

            $sap_pin_app_username = $_GET['sap_pinterest_app_id'];

            // Getting stored pin app data
            $sap_pin_sess_data = $this->settings->get_user_setting('sap_pin_sess_data');
           

            // Unset perticular app value data and update the option
            if (isset($sap_pin_sess_data[$sap_pin_app_username])) {
                unset($sap_pin_sess_data[$sap_pin_app_username]);
                $this->settings->update_user_setting('sap_pin_sess_data', $sap_pin_sess_data);
                $this->sap_common->sap_script_logs('Pinterest ' . $sap_pin_app_username . ' Account Reset Successfully.');
                $_SESSION['sap_active_tab'] = 'pinterest';
                header("Location:" . SAP_SITE_URL . "/settings/");
                exit;
            }
        }

    }

    /**
     * Reset Sessions
     * 
     * Resetting the Pinterest sessions when the admin clicks on
     * its link within the settings page.
     * 
     * @package Social Auto Poster
     * @since 1.0.0
     */
    public function sap_pin_reset_session() {

        // Check if pinterest reset user link is clicked and pin_reset_user is set to 1 and pinterest app id is there
        if (isset($_GET['pin_reset_user']) && $_GET['pin_reset_user'] == '1' && !empty($_GET['sap_pinterest_username'])) {

            $sap_pin_app_username = $_GET['sap_pinterest_username'];

            // Getting stored pin app data
            $sap_pin_sess_data    = $this->settings->get_user_setting('sap_pin_sess_data');
          
            // Unset perticular app value data and update the option
            if (isset($sap_pin_sess_data[$sap_pin_app_username])) {

                unset($sap_pin_sess_data[$sap_pin_app_username]);

                $this->settings->update_user_setting('sap_pin_sess_data', $sap_pin_sess_data);
                $this->sap_common->sap_script_logs('Pinterest ' . $sap_pin_app_username . ' Account Reset Successfully.', $user_id);
                $_SESSION['sap_active_tab'] = 'pinterest';
                header("Location:" . SAP_SITE_URL . "/settings/");
                exit;
            } else if ( empty( $sap_pin_sess_data[$sap_pin_app_username] ) ) {

                $app_id = '';
                if( !empty( $sap_pin_sess_data ) && is_array( $sap_pin_sess_data ) ) {

                    foreach( $sap_pin_sess_data as $key => $pin_sess_data ) {

                        if(  $pin_sess_data['id'] ==  $sap_pin_app_username ) {

                            $app_id = $key;
                            break;
                        }

                    }
                } 
                

                if( isset(  $app_id ) ) {

                    unset($sap_pin_sess_data[$app_id]);
                    $this->settings->update_user_setting('sap_pin_sess_data', $sap_pin_sess_data);
                    $this->sap_common->sap_script_logs('Pinterest ' . $app_id . ' Account Reset Successfully.', $user_id);
                    $_SESSION['sap_active_tab'] = 'pinterest';
                    header("Location:" . SAP_SITE_URL . "/settings/");
                    exit;
               
                } 
            }

        }
    }

    /**
     * Post to User Board on Pinterest
     * 
     * Handles to post user wall on pinterest
     * 
     * @package Social Auto Poster
     * @since 1.0.0
     */
    public function sap_pin_post_to_userwall($post_id) {

        global $proxy_url, $proxy_pupw;

        $post = $this->posts->get_post($post_id, true);

        $user_id = isset( $post->user_id ) ? $post->user_id : '';

        //Getting pinterest options
        $sap_pin_options = $this->settings->get_user_setting('sap_pinterest_options', $user_id);
        $sap_pin_auth_method = !empty( $sap_pin_options['pin_auth_options'] ) ? $sap_pin_options['pin_auth_options'] : 'cookie';

        //Getting stored pinterest app data
        $sap_pin_sess_data = $this->settings->get_user_setting('sap_pin_sess_data', $user_id);

        // General setting
        $sap_general_options = $this->settings->get_user_setting('sap_general_options',$user_id);

        $link_timestamp = isset($sap_general_options['timestamp_link']) ? "?".time() : '';

        //Pinterest proxy setting
        $proxy = array();
        if (!empty($sap_pin_options['enable_proxy'])) {
            
            if(!empty($sap_pin_options['proxy_url'])) {
                
                $proxy_url = $sap_pin_options['proxy_url'];
            }
            if(!empty($sap_pin_options['proxy_username']) && !empty($sap_pin_options['proxy_password'])) {
                $proxy_pupw = $sap_pin_options['proxy_username'].":".$sap_pin_options['proxy_password'];
            }

        }

        $post_to_users = array();
        if (!empty($post_id)) {

            $sap_pin_custom_msg = $this->posts->get_post_meta($post_id, '_sap_pin_post_msg');
            $sap_pin_custom_accounts = $this->posts->get_post_meta($post_id, '_sap_pin_post_accounts');
            $sap_pin_custom_image = $this->posts->get_post_meta($post_id, '_sap_pin_post_image');
            $posting_type = $this->posts->get_post_meta($post_id, '_sap_pin_status');
            $sap_post_link = $this->posts->get_post_meta($post_id, 'sap_pinterest_custom_link');


            $global_img = $sap_pin_options['pin_image'];
        }

        // Check pinterest grant extended permission is set ot not
        if (!empty($sap_pin_sess_data)) {

            //convert user ids to single array
            $posting_logs_data = array();

            if (!empty($sap_pin_custom_msg)) {

                $notes = $sap_pin_custom_msg;
            } else {
                $notes = $post->body;
            }

            // Post limit 500 character per post
            if (!empty($notes))
                $notes = $this->posts->sap_limit_character($notes, 500);

            $postlink = !empty($post->share_link) ? $post->share_link : $sap_post_link;

            if(!empty($postlink)) {
                $postlink = $postlink."".$link_timestamp;
            }

            $customlink = !empty($postlink) ? 'true' : 'false';
            $postlink = $this->common->sap_script_short_post_link($postlink, $customlink, 'pin', 'pinterest', $user_id);
            
            //check post image is not empty then pass to pinterest
            if (isset($sap_pin_custom_image) && !empty($sap_pin_custom_image)) {
                $img_src = SAP_IMG_URL . $sap_pin_custom_image;
                $img_path = SAP_APP_PATH . '/uploads/' . $sap_pin_custom_image;
            } elseif (!empty($post->img)) {
                $img_src = SAP_IMG_URL . $post->img;
                $img_path = SAP_APP_PATH . 'uploads/' . $post->img;
            } elseif (!empty($global_img)) {
                $img_src = SAP_IMG_URL . $global_img;
                $img_path = SAP_APP_PATH . 'uploads/' . $global_img;
            } else {
                $this->flash->setFlash('Post Image required for Pinterest.', 'error','',true);
                $this->sap_common->sap_script_logs('Post Image required for Pinterest.', $user_id);
                return false;
            }

            //posting logs data
            $posting_logs_data = array(
                'notes' => $notes,
                'image' => $img_src,
            );

            if (!empty($postlink)) {
                $posting_logs_data['link'] = $postlink;
            }

            if( $sap_pin_auth_method == 'cookie' ) {

                $send = array(
                    'note' => mb_substr($notes, 0, 499),
                    'link' => $postlink
                );
    
                if (isset($img_path) && !empty($img_path)) {
                    $send['image'] = $img_path;
                }

            }    

            if( $sap_pin_auth_method == 'app' ) {

                $send['media_source'] = array(
                    'source_type' => 'image_url',
                    'url' => $img_src
                );
                $send['title'] = mb_substr($notes, 0, 499);

            }    

            
            //initial value of posting flag
            $postflg = false;
            $post_to_users = array();
            if (!empty($sap_pin_custom_accounts)) {
                $post_to_users = $sap_pin_custom_accounts;
            } else {
                $post_to_users = $sap_pin_options['pin_type_post_user'];
            }

            if (!empty($post_to_users)) {

                $posting_logs_user_details = array();
                foreach ($post_to_users as $post_to) {

                    if( $sap_pin_auth_method == 'cookie' ) {

                        $allPinData = $this->settings->get_user_setting('sap_pin_sess_data',$user_id);
                        $pinData = explode('|', $post_to);

                        $username = isset($pinData[0]) ? $pinData[0] : '';
                        $boardID = isset($pinData[1]) ? $pinData[1] : '';

                        $sessID = !empty($allPinData[$username]['sessid']) ? $allPinData[$username]['sessid'] : '';
                        if (!empty($username) && !empty($boardID) && !empty($sessID)) {

                            $posted = $this->sap_send_post_to_pin($sessID, $boardID, $send);

                            $boardName = isset($allPinData[$username]['boards'][$boardID]['name']) ? $allPinData[$username]['boards'][$boardID]['name'] : '';

                            if (!empty($boardName)) {
                                $posting_logs_data['display_name'] = $username . " - " . $boardName;
                            }

                            $postflg = false;
                            try {
                                if (isset($posted['status']) && $posted['status'] == 'success') {

                                    $posting_logs_data['link to post'] = 'https://pinterest.com/' . $username . "/" . str_replace(' ', '-', strtolower($boardName));

                                    $postflg = true;
                                    $this->logs->add_log('pinterest', $posting_logs_data, 1, $user_id);
                                    $this->flash->setFlash('Pinterest : Post successfully posted on - ' . $username . " - " . $boardName, 'success','',true);
                                    $this->sap_common->sap_script_logs('Pinterest : Post successfully posted on - ' . $username . " - " . $boardName, $user_id);
                                    $this->sap_common->sap_script_logs('Pinterest post data : ' . var_export($posting_logs_data, true), $user_id);
                                }
                            } catch (Exception $e) {
                                $this->sap_common->sap_script_logs('Pinterest error : ' . $e->getMessage(), $user_id);
                                $this->flash->setFlash($e->getMessage(), 'error','',true);
                                $errorMessage = $posted['message'];
                                $postflg = false;
                            }
                        }
                        if (!$postflg) {
                            $this->sap_common->sap_script_logs('Pinterest error : ' . $posted['message'], $user_id);
                            $this->flash->setFlash('Pinterest Exception : ' . $posted['message'], 'error','',true);
                        }

                    }    
                    if( $sap_pin_auth_method == 'app' ) {

                        $pinData = explode('|', $post_to);
                        $app_id = isset($pinData[0]) ? $pinData[0] : '';
                        $username = $sap_pin_sess_data[$app_id]['username'];
                        $boardID = isset($pinData[1]) ? $pinData[1] : '';
    
                        //check there is auth token is set for pinterest user
                        if (isset($sap_pin_sess_data[$app_id])) {
                            $auth_token = $sap_pin_sess_data[$app_id]['pin_access_token'];
                        }
    
                        if (!empty($sap_pin_sess_data[$app_id])) {
    
                            $account_name = $sap_pin_sess_data[$app_id]['username'];
                            if (!empty($sap_pin_sess_data[$app_id]['boards'])) {
                                $board_name = $sap_pin_sess_data[$app_id]['boards'][$boardID]['name'];
                            }
                        }                  
    
                        if (!empty($app_id) && !empty($boardID)) {
    
                                $send['board_id'] = $boardID;
                                $pinterest = $this->sap_load_pinterest($app_id, $user_id);
                                if (!$pinterest) {
                                    return false;
                                }
                               
                                $this->pinterest->auth->setOAuthToken($auth_token);
                                $pub = $this->pinterest->pins->create($send);
                                if (!empty($board_name)) {
        
                                    $posting_logs_data['display_name'] = $username . " - " . $board_name;
                                }
    
                                $postflg = false;
                                try {
                                    if (isset($pub) && !empty($pub->id)) {
        
                                        $posting_logs_data['link to post'] = 'https://pinterest.com/' . $username . "/" . str_replace(' ', '-', strtolower($board_name));
                                        $postflg = true;
                                        $this->logs->add_log('pinterest', $posting_logs_data, 1, $user_id);
                                        $this->flash->setFlash('Pinterest : Post successfully posted on - ' . $username . " - " . $board_name, 'success','',true);
                                        $this->sap_common->sap_script_logs('Pinterest : Post successfully posted on - ' . $username . " - " . $board_name, $user_id);
                                        $this->sap_common->sap_script_logs('Pinterest post data : ' . var_export($posting_logs_data, true), $user_id);
                                        $this->quick_posts->update_post_meta($post_id, "sap_pin_link_to_post", $posting_logs_data['link to post']);
                                    }
                                } catch (Exception $e) {
                                    $this->sap_common->sap_script_logs('Pinterest error : ' . $e->getMessage(), $user_id);
                                    $this->flash->setFlash($e->getMessage(), 'error','',true);
                                    $errorMessage = $posted['message'];
                                    $postflg = false;
                                }
                        }    
                        if (!$postflg) {
                            $this->sap_common->sap_script_logs('Pinterest error : ' . $posted['message'], $user_id);
                            $this->flash->setFlash('Pinterest Exception : ' . $posted['message'], 'error','',true);
                        }
    
                    } 
                    
                }
            }

            return $postflg;
        } else {
            $this->flash->setFlash('Pinterest grant extended permissions not set.', 'error','',true);
            $this->sap_common->sap_script_logs('Pinterest grant extended permissions not set.', $user_id);
        }
    }

    /**
     * Handles posting on Pinterest method
     * 
     * This method handles to send posting on pinterest portal
     * 
     * @package Social Auto Poster
     * @since 1.0.0
     */
    public function sap_send_post_to_pin($sessID, $boardId, $data = array()) {

        global $proxy_url, $proxy_pupw;

        $apiURL = 'https://www.pinterest.com/resource/PinResource/create/';

        $imageURL = isset($data['image']) ? $data['image'] : '';
        $image_file_name = basename($imageURL);

        $imageURL = SAP_IMG_URL . $image_file_name;

        $pinData = array(
            "options" => array(
                "board_id" => $boardId,
                "title" => '',
                "description" => isset($data['note']) ? $data['note'] : '',
                "link" => isset($data['link']) ? $data['link'] : '',
                "image_url" => $imageURL,
                "method" => "uploaded",
            ),
            "context" => array()
        );

        $postField = array(
            'data' => json_encode($pinData)
        );

        $fields = http_build_query($postField);

        // generated csrf token dynamically
        $csrftoken = bin2hex(random_bytes(32));

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $apiURL);

        curl_setopt($ch, CURLOPT_HTTPHEADER, array("X-Requested-With: XMLHttpRequest", "X-CSRFToken: {$csrftoken}"));

        if($proxy_url !== ''){
            curl_setopt($ch, CURLOPT_PROXY, $proxy_url);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
            if($proxy_pupw !== ''){
                curl_setopt($ch, CURLOPT_PROXYUSERPWD, $proxy_pupw);
            }
        }

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_ENCODING, '');
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $fields);

        curl_setopt($ch, CURLOPT_COOKIE, 'csrftoken=' . $csrftoken . '; _pinterest_sess="' . $sessID . '"; c_dpr=1');

        $response = curl_exec($ch);


        // Get response code
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        curl_close($ch);

        $data = json_decode($response, true);

        $status = isset($data['resource_response']['status']) ? $data['resource_response']['status'] : '';
        if ($httpCode == '200' && $status == 'success') {
            $pinData = isset($data['resource_response']['data']) ? $data['resource_response']['data'] : '';
            $respose['status'] = 'success';
            $respose['pindata'] = $pinData;
        } else {
            $respose['status'] = 'error';
            $respose['message'] = isset($data['resource_response']['error']['message']) ? $data['resource_response']['error']['message'] : 'Something goes wrong, please try later.';
        }
        
        return $respose;
    }

    /**
     * Qucik Post On pinterest
     * 
     * Handles to post user wall on pinterest
     * 
     * @package Social Auto Poster
     * @since 1.0.0
     */
    public function sap_quick_post_on_pin_post($post_id) {

        global $proxy_url, $proxy_pupw;

        $status_meta_array = array();
        
        $quick_post = $this->quick_posts->get_post($post_id, true);
        $user_id = isset( $quick_post->user_id ) ? $quick_post->user_id : '';

        //Getting pinterest options
        $sap_pin_options = $this->settings->get_user_setting('sap_pinterest_options', $user_id);
        $sap_pin_auth_method = !empty( $sap_pin_options['pin_auth_options'] ) ? $sap_pin_options['pin_auth_options'] : 'cookie';
       
       //Pinterest proxy setting
        $proxy = array();
        if (!empty($sap_pin_options['enable_proxy'])) {
            
            if(!empty($sap_pin_options['proxy_url'])) {
                
                $proxy_url = $sap_pin_options['proxy_url'];
            }
            if(!empty($sap_pin_options['proxy_username']) && !empty($sap_pin_options['proxy_password'])) {
                $proxy_pupw = $sap_pin_options['proxy_username'].":".$sap_pin_options['proxy_password'];
            }

        }

        // General setting
        $sap_general_options = $this->settings->get_user_setting('sap_general_options',$user_id);

        $link_timestamp = isset($sap_general_options['timestamp_link']) ? "?".time() : '';

        //Getting stored pin app data
        $sap_pin_sess_data = $this->settings->get_user_setting('sap_pin_sess_data', $user_id);

        if (!empty($sap_pin_sess_data)) {

            $sap_networks_meta = $this->quick_posts->get_post_meta($post_id, 'sap_networks');
            $sap_networks_accounts = !empty($sap_networks_meta['pin_accounts']) ? $sap_networks_meta['pin_accounts'] : array();

            // Pinterest user id on whose wall the post will be posted

            $notes = html_entity_decode(strip_tags($quick_post->message),ENT_QUOTES);

            // Post limit 500 character per post
            if (!empty($notes))
                $notes = $this->posts->sap_limit_character($notes, 500);

            $postlink = !empty($quick_post->share_link) ? $quick_post->share_link : '';

            if(!empty($postlink)) {
                $postlink = $postlink."".$link_timestamp;
            }

            $customlink = !empty($postlink) ? 'true' : 'false';
            $postlink = $this->common->sap_script_short_post_link($postlink, $customlink, 'pin', 'pinterest', $user_id);
            
            //check post image is not empty then pass to pinterest
            if (isset($sap_pin_options['pin_image'])) {

                $pin_general_image = $sap_pin_options['pin_image'];
            }
            $post_img = !empty($quick_post->image) ? $quick_post->image : $pin_general_image;

            if (!empty($post_img)) {
                $img_src = SAP_IMG_URL . $post_img;
                $img_path = SAP_APP_PATH . '/uploads/' . $post_img;
            } else {
                $this->flash->setFlash('Post Image required for Pinterest.', 'error','',true);
                $this->sap_common->sap_script_logs('Pinterest Post Image required for Pinterest.', $user_id);
                $status_meta_array[] = array(
                    "status" => 'error',
                    "message" => 'Pinterest Post Image required for Pinterest.'
                );
                $this->quick_posts->update_post_meta($post_id, "sap_pin_posting_error", $status_meta_array);
                return false;
            }

            $posting_logs_data = array();

            //posting logs data
            $posting_logs_data = array(
                'notes' => $notes,
                'image' => $img_src,
            );

            if (!empty($postlink)) {
                $posting_logs_data['link'] = $postlink;
            }

            if( $sap_pin_auth_method == 'cookie' ) {

                $send = array(
                    'note' => mb_substr($notes, 0, 499),
                    'link' => $postlink
                );
    
                if (isset($img_path) && !empty($img_path)) {
                    $send['image'] = $img_path;
                }

            }    

            if( $sap_pin_auth_method == 'app' ) {

                $send['media_source'] = array(
                    'source_type' => 'image_url',
                    'url' => $img_src
                );
                $send['title'] = mb_substr($notes, 0, 499);

            }    



            //initial value of posting flag
            $postflg = false;
            $post_to_users = array();
            if (!empty($sap_networks_accounts)) {

                $post_to_users = $sap_networks_accounts;
            } else {

                if (isset($sap_pin_options['pin_type_post_user'])) {
                    $post_to_users = $sap_pin_options['pin_type_post_user'];
                }
            }

            if (!empty($post_to_users)) {

                $posting_logs_user_details = array();
                foreach ($post_to_users as $post_to) {

                if( $sap_pin_auth_method == 'cookie' ) {

                        $allPinData = $this->settings->get_user_setting('sap_pin_sess_data', $user_id);
                        $pinData = explode('|', $post_to);

                        $username = isset($pinData[0]) ? $pinData[0] : '';
                        $boardID = isset($pinData[1]) ? $pinData[1] : '';

                        $status_meta_key = (!empty($username) ) ? $username : $post_to;

                        $sessID = !empty($allPinData[$username]['sessid']) ? $allPinData[$username]['sessid'] : '';

                        if (!empty($username) && !empty($boardID) && !empty($sessID)) {

                            $posted = $this->sap_send_post_to_pin($sessID, $boardID, $send);
    
                            $boardName = isset($allPinData[$username]['boards'][$boardID]['name']) ? $allPinData[$username]['boards'][$boardID]['name'] : '';
    
                            if (!empty($boardName)) {
    
                                $posting_logs_data['display_name'] = $username . " - " . $boardName;
                            }
    
                            $postflg = false;
                            try {
                                if (isset($posted['status']) && $posted['status'] == 'success') {
    
                                    $posting_logs_data['link to post'] = 'https://pinterest.com/' . $username . "/" . str_replace(' ', '-', strtolower($boardName));
    
                                    $postflg = true;
                                    $this->logs->add_log('pinterest', $posting_logs_data, 1, $user_id);
                                    $this->flash->setFlash('Pinterest : Post successfully posted on - ' . $username . " - " . $boardName, 'success','',true);
                                    $this->sap_common->sap_script_logs('Pinterest : Post successfully posted on - ' . $username . " - " . $boardName, $user_id);
                                    $this->sap_common->sap_script_logs('Pinterest post data : ' . var_export($posting_logs_data, true), $user_id);
                                    $status_meta_array[$status_meta_key] = array(
                                        "status" => 'success'
                                    );
                                    $this->quick_posts->update_post_meta($post_id, "sap_pin_link_to_post", $posting_logs_data['link to post']);
                                }
                            } catch (Exception $e) {
                                $this->sap_common->sap_script_logs('Pinterest error : ' . $e->getMessage(), $user_id);
                                $this->flash->setFlash($e->getMessage(), 'error','',true);
                                $errorMessage = $posted['message'];
                                $postflg = false;
                                $status_meta_array[$status_meta_key] = array(
                                    "status" => 'error',
                                    "message" => $e->getMessage()
                                );
                            }
                        }
                        if (!$postflg) {
                            $this->sap_common->sap_script_logs('Pinterest Exception : ' . $posted['message'], $user_id);
                            $this->flash->setFlash('Pinterest Exception: ' . $posted['message'], 'error','',true);
                            $status_meta_array[$status_meta_key] = array(
                                "status" => 'error',
                                "message" => $posted['message']
                            );
                        }

                    }
   
                }
                if( $sap_pin_auth_method == 'app' ) {

                    $pinData = explode('|', $post_to);
                    $app_id = isset($pinData[0]) ? $pinData[0] : '';
                    $username = $sap_pin_sess_data[$app_id]['username'];
                    $boardID = isset($pinData[1]) ? $pinData[1] : '';

                    //check there is auth token is set for pinterest user
                    if (isset($sap_pin_sess_data[$app_id])) {
                        $auth_token = $sap_pin_sess_data[$app_id]['pin_access_token'];
                    }

                    if (!empty($sap_pin_sess_data[$app_id])) {

                        $account_name = $sap_pin_sess_data[$app_id]['username'];
                        if (!empty($sap_pin_sess_data[$app_id]['boards'])) {
                            $board_name = $sap_pin_sess_data[$app_id]['boards'][$boardID]['name'];
                        }
                    }                  

                    if (!empty($app_id) && !empty($boardID)) {

                            $send['board_id'] = $boardID;
                            $pinterest = $this->sap_load_pinterest($app_id, $user_id);
                            if (!$pinterest) {
                                return false;
                            }
                           
                            $this->pinterest->auth->setOAuthToken($auth_token);
                                                      try {
                                
                            $pub = $this->pinterest->pins->create($send);   

                            } catch (Exception $e) {

                                 $status_meta_array[$status_meta_key] = array(
                                    "status" => 'error',
                                    "message" => $e->getMessage()
                                );
                                $this->sap_common->sap_script_logs('Pinterest error : ' . $e->getMessage(), $user_id);
                                $this->flash->setFlash($e->getMessage(), 'error','',true);
                            }
                            
                            if (!empty($board_name)) {
    
                                $posting_logs_data['display_name'] = $username . " - " . $board_name;
                            }

                            $postflg = false;
                            try {
                                if (isset($pub) && !empty($pub->id)) {
    
                                    $posting_logs_data['link to post'] = 'https://pinterest.com/' . $username . "/" . str_replace(' ', '-', strtolower($board_name));
                                    $postflg = true;
                                    $this->logs->add_log('pinterest', $posting_logs_data, 1, $user_id);
                                    $this->flash->setFlash('Pinterest : Post successfully posted on - ' . $username . " - " . $board_name, 'success','',true);
                                    $this->sap_common->sap_script_logs('Pinterest : Post successfully posted on - ' . $username . " - " . $board_name, $user_id);
                                    $this->sap_common->sap_script_logs('Pinterest post data : ' . var_export($posting_logs_data, true), $user_id);
                                    $status_meta_array[$status_meta_key] = array(
                                        "status" => 'success'
                                    );
                                    $this->quick_posts->update_post_meta($post_id, "sap_pin_link_to_post", $posting_logs_data['link to post']);
                                }
                            } catch (Exception $e) {
                                $this->sap_common->sap_script_logs('Pinterest error : ' . $e->getMessage(), $user_id);
                                $this->flash->setFlash($e->getMessage(), 'error','',true);
                                $errorMessage = $posted['message'];
                                $postflg = false;
                                $status_meta_array[$status_meta_key] = array(
                                    "status" => 'error',
                                    "message" => $e->getMessage()
                                );
                            }
                    }    
                    if (!$postflg) {
                        $this->sap_common->sap_script_logs('Pinterest Exception : ' . $posted['message'], $user_id);
                        $this->flash->setFlash('Pinterest Exception: ' . $posted['message'], 'error','',true);
                        $status_meta_array[$status_meta_key] = array(
                            "status" => 'error',
                            "message" => $posted['message']
                        );
                    }

                }    
                $this->quick_posts->update_post_meta($post_id, "sap_pin_posting_error", $status_meta_array);
            }
            return $postflg;
        } else {
            $this->flash->setFlash('Pinterest grant extended permissions not set.', 'error','',true);
            $this->sap_common->sap_script_logs('Pinterest grant extended permissions not set.', $user_id);
            $status_meta_array[] = array(
                "status" => 'error',
                "message" => 'Pinterest grant extended permissions not set.'
            );
            $this->quick_posts->update_post_meta($post_id, "sap_pin_posting_error", $status_meta_array);
        }
    }

}
