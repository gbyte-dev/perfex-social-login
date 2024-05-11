<?php

/* Check the absolute path to the Social Auto Poster directory. */
if ( !defined( 'SAP_APP_PATH' ) ) {
    // If SAP_APP_PATH constant is not defined, perform some action, show an error, or exit the script
    // Or exit the script if required
    exit();
}

/**
 * Blogger posting
 *
 * @package Social auto poster
 * @since 1.0.0
 */


class SAP_Blogger {

    public $settings, $sap_common, $flash, $posts, $common, $logs, $quick_posts;

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
        $this->sap_blogger_initialize($user_id);
        //$this->sap_blogger_get_code();
    }

    /**
     * Assign Blogger User's all Data to session
     *
     * Handles to assign user's Blogger data
     * to sessoin & save to database
     *
     * @package Social Auto Poster
     * @since 1.0.0
     */
    
    public function sap_blogger_initialize($user_id='') {
        
        // Get global SAP Blogger options
        $sap_blogger_options = $this->settings->get_user_setting('sap_blogger_sess_data', $user_id);

        global $sap_options, $sap_message_stack;
      
        $blogger_sess_data = array();
          
        if( isset( $_GET['code'] ) && isset( $_GET['sap_blogger_verification'] ) && $_GET['sap_blogger_verification'] == 'true' ) {
          
            $code = $_GET["code"];
            $redirect_url = SAP_BLOGGER_REDIRECT_URL;
            $auth_token_url = SAP_BLOGGER_ACCESS_TOKEN_URL;
            $postvals = sprintf( "code=%s&redirect_uri=%s&grant_type=authorization_code", $code, $redirect_url );

            $token = $this->runCurl( $auth_token_url, $postvals, null, true, false, '' );
            
            $access_token = '';  
            if ( isset( $token->access_token ) && $token->access_token != '' ) {
                $access_token = "{$token->token_type}:{$token->access_token}";
            }

            $user_details = $this->getUser( $access_token );

            if ( empty( $user_details ) || empty( $user_details->id ) || empty( $user_details->name ) ) {
                $this->flash->setFlash( $this->sap_common->lang('try_again_later'), 'error');
                $_SESSION['sap_active_tab'] = 'blogger';
                header("Location:" . SAP_SITE_URL . "/settings/");
                exit;
            }

            $blogger_sess_data[$user_details->id] = array(
                'name'          => $user_details->name,
                'display_name'  => $user_details->given_name,
            );

            $blogger_sess_data[$user_details->id]['token_details'] = array(
                'authorized_timestamp' => time(),
                'access_token' => $token->access_token,
                'token_type' => $token->token_type,
                'expire_timestamp' => $token->expires_in,
                'refresh_token' => $token->refresh_token,
            );

            if ( !empty( $sap_blogger_options ) ) {
                foreach ( $sap_blogger_options as $key => $value ) {
                   $blogger_sess_data[$key] = $value;
                }
            }else{
                $blogger_sess_data = $blogger_sess_data;
            }


            if( !empty( $blogger_sess_data ) && is_array( $blogger_sess_data ) ) {
                $this->settings->update_user_setting( 'sap_blogger_sess_data', $blogger_sess_data );
            }

            $_SESSION['sap_active_tab'] = 'blogger';
            header("Location:" . SAP_SITE_URL . "/settings/");
            exit;
        }
     
    }
    

    public function sap_auto_poster_get_blogger_login_url() {
        
        $params = array(
            'duration'     => 'permanent',
            'response_type'=> 'code',
            'access_type'  => 'offline',
            'prompt'       => 'consent',
            'client_id'    => SAP_BLOGGER_CLIENT_ID,
            'redirect_uri' => SAP_BLOGGER_REDIRECT_URL,
            'scope'        => SAP_BLOGGER_SCOPE,
            'state'        => SAP_SITE_URL,
        );

        $http_query = http_build_query($params);
            
        return SAP_BLOGGER_AUTH_URL . $http_query;
    }


    /**
     * cURL request
     *
     * General cURL request function for GET and POST
     * @link URL
     * @param string $url URL to be requested
     * @param string $postVals NVP string to be send with POST request
     */
    public function runCurl( $url, $postVals = null, $headers = null, $auth = false, $posting = false , $access_token = '', $get = false , $contentJson = false ) {
    
       $ch = curl_init($url);


        $options = array(
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_TIMEOUT => 10
        );

        // Create common user agent 
        // Get different user agent string from the reference https://developers.whatismybrowser.com/useragents/explore/software_type_specific/web-browser/2
        $user_agents = [
            "Mozilla/5.0 (Windows NT 5.1; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/60.0.3112.90 Safari/537.36",
            "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/79.0.3945.130 Safari/537.36",
            "Mozilla/5.0 (Windows NT 6.3; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/60.0.3112.113 Safari/537.36",
            "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/81.0.4044.138 Safari/537.36",
            "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/80.0.3987.132 Safari/537.36",
        ];

        $useragent = $user_agents[array_rand($user_agents)]; // get random user agent to fix bad request issue

        $options[CURLOPT_USERAGENT] = $useragent;


        if ($postVals != null && $auth === true ) {
            $options[CURLOPT_POST] = true;
            $options[CURLOPT_POSTFIELDS] = $postVals;
        } elseif ( $postVals != null ) {
            $options[CURLOPT_POSTFIELDS] = $postVals;
            $options[CURLOPT_CUSTOMREQUEST] = "POST";
        }

        if ( $get === true ) {
            $options[CURLOPT_HTTPGET] = 1;
        }

        if ( $this->auth_mode == 'oauth' ) {

            if( $posting ) {
                $access_token = '';
                $access_token = $this->access_token;
            } else {
                if( isset( $access_token ) && $access_token != '' ){
                    $access_token = $access_token;
                }
            }
                   
            if ( isset( $access_token ) && $access_token != '' ) {
                $token = explode( ":", $access_token );
                $headers = array("Authorization: {$token[0]} {$token[1]}");

                if ( $contentJson == true ) {
                    $headers[] = "Content-Type: application/json";
                }

            } else {
                $headers = array( "Authorization: Basic " . base64_encode( SAP_BLOGGER_CLIENT_ID.":".SAP_BLOGGER_CLIENT_SECRET ) );
            }

            $options[CURLOPT_HEADER] = false;
            $options[CURLINFO_HEADER_OUT] = false;
            $options[CURLOPT_HTTPHEADER] = $headers;
        }
                
        if ( $auth === true ) {

            $header = array ( 'Authorization' => 'Basic '. base64_encode( SAP_BLOGGER_CLIENT_ID.":".SAP_BLOGGER_CLIENT_SECRET ) );
            
            $header_array = array();
            foreach( $header as $k => $v )
            {
                $header_array[] = $k.': '.$v;
            }
           
            $options[CURLOPT_HTTPHEADER] = $header_array;
            $options[CURLOPT_SSL_VERIFYPEER] = true;
            $options[CURLOPT_SSL_VERIFYHOST] = 2;
        }
        
        curl_setopt_array($ch, $options);
                  
        $apiResponse = curl_exec($ch);
       
        $response = json_decode($apiResponse);

        //check if non-valid JSON is returned
        if ( $error = json_last_error() ) {
            $response = $apiResponse;
        }
        curl_close($ch);

        return $response;
    }


    public function sap_quick_post_on_blogger_post( $post_id ){

        $postflg = false;

        $quick_posts = $this->quick_posts->get_post( $post_id, true );
            
        $user_id = isset( $quick_posts->user_id ) ? $quick_posts->user_id : '';
        $sap_networks_meta = $this->quick_posts->get_post_meta( $post_id, 'sap_networks' );
        $accounts = !empty( $sap_networks_meta['blogger_accounts'] ) ? $sap_networks_meta['blogger_accounts'] : array();

        //Getting Blogger Options
        $blogger_options = $this->settings->get_user_setting( 'sap_blogger_options', $user_id );
        

        //Getting stored Blogger app data
        $sap_blogger_custom_msg = !empty( $sap_networks_meta['blogger_title'] ) ? $sap_networks_meta['blogger_title'] : '' ;
        $sap_blogger_post_url = !empty( $sap_networks_meta['blogger_urls'] ) ? $sap_networks_meta['blogger_urls'] : '' ;

        if ( empty( $sap_blogger_post_url ) ) {
            $sap_blogger_post_url = $blogger_options['blogger_url']; 
        }

        $sap_blogger_sess_data = $this->settings->get_user_setting( 'sap_blogger_sess_data', $user_id );

        if( !empty( $quick_posts ) ) {

            $blogger_image = !empty( $blogger_options['blogger_image'] ) ? $blogger_options['blogger_image'] : '';
            $default_accounts = !empty( $blogger_options['posts_users'] )? $blogger_options['posts_users'] : '';

            $post_body  = !empty($quick_posts->message) ? html_entity_decode(strip_tags($quick_posts->message),ENT_QUOTES): '';

            $description  = '';
            $description .= ( !empty( $post_body ) ) ? $post_body . "\r\n" : '';

            if( !empty( $quick_posts->image ) ){
                $postimage = $quick_posts->image;
            }else{
                $postimage = $blogger_image;
            }

            if ( !empty( $postimage ) ) {
                $postimage = SAP_IMG_URL.$postimage;
            }else{
                $postimage = '';
            }
            
            $posting_logs_data = array();
          
            $accounts = !empty( $accounts ) ? $accounts : $default_accounts;

            //Check Accounts exist
            if ( empty( $accounts ) ) {
                $this->flash->setFlash( $this->sap_common->lang('blogger_account_not_select'), 'error','',true);
                $this->sap_common->sap_script_logs( $this->sap_common->lang('blogger_account_not_select'), $user_id );
                $status_meta_array[] = array(
                    "status" => 'error',
                    "message" => $this->sap_common->lang('blogger_account_not_select'),
                );
                $this->quick_posts->update_post_meta( $post_id, "sap_blogger_posting_error", $status_meta_array );
                return false;
            }
                       
            if ( !empty( $accounts ) ) { 
                foreach ( $accounts as $blogger_user_id ) {
                    if( array_key_exists( $blogger_user_id, $sap_blogger_sess_data ) ) {

                        $posting_logs_data['display_name'] = $sap_blogger_sess_data[$blogger_user_id]['name'];
                        $blogger_post_profile = $sap_blogger_sess_data[$blogger_user_id]['display_name'];
                        $posting_logs_data['id'] = $blogger_user_id;
                        $posting_logs_data['body'] = $description;
                            
                        $access_token = $sap_blogger_sess_data[$blogger_user_id]['token_details']['access_token'];
                        $token_type = $sap_blogger_sess_data[$blogger_user_id]['token_details']['token_type'];

                        $refresh_token = $sap_blogger_sess_data[$blogger_user_id]['token_details']['refresh_token'];

                        $newTokenData = $this->get_exchange_token( $refresh_token );
                        $access_token = $newTokenData['access_token'];
                        $token_type   = $newTokenData['token_type'];

                        foreach ( $sap_blogger_post_url as $key => $value ) {

                            $bloggerUrlData = $this->getBloggerData( $value, $token_type.':'.$access_token );
                                   
                            if ( empty( $bloggerUrlData->id ) && empty( $bloggerUrlData->selfLink ) ) {

                                $this->flash->setFlash( $this->sap_common->lang('something_went_wrong_debug'), 'error', '', true );
                                        
                                $this->logs->add_log( 'blogger', $bloggerUrlData, '', $user_id );

                                header("Location:" . SAP_SITE_URL . "/quick-post/");
                                exit;

                            }

                            $posting_logs_data['message'] = $sap_blogger_custom_msg;
                            $content = array(
                                'title'               => $sap_blogger_custom_msg,
                                'description'         => $description,
                                'submitted-image-url' => $postimage,
                                'kind'                => $bloggerUrlData->kind,
                                'blogID'              => $bloggerUrlData->id,
                                'selfLink'            => $bloggerUrlData->selfLink,
                            );

                            if ( !empty( $access_token ) ) {
                                if( isset( $blogger_user_id ) && !empty( $blogger_user_id ) ) {
                                    $post_data = array(
                                        'title'               => $content['title'],
                                        'submitted-url'       => $content['submitted-url'],
                                        'submitted-image-url' => $content['submitted-image-url'],
                                        'description'         => $content['description'],
                                        'kind'                => $content['kind'],
                                        'blogID'              => $content['blogID'],
                                        'selfLink'            => $content['selfLink'],
                                        'access_token'        => $token_type." ".$access_token
                                    );
       
                                    $response = $this->createStory( $post_data );
                                       
                                    if( !empty( $response->id ) && $response->status == 'LIVE' ){
                                                
                                        $errorFind = false;
                                        $this->sap_common->sap_script_logs( 'Blogger post data : ' . var_export( $content, true ), $user_id );
                                      
                                        $this->logs->add_log( 'blogger', $posting_logs_data, '',$user_id );

                                        $postflg = true;

                                        $blogger_posting['success'] = 1;

                                    } else {

                                        $errorFind = true;

                                    }
                                        
                                }
                            } 
                        }

                        if ( $errorFind == true ) {

                            $this->flash->setFlash( 'Blogger : Post not posted on - ' . $blogger_post_profile, 'error', '', true );
                            $_SESSION['sap_active_tab'] = 'blogger';
                            header("Location:" . SAP_SITE_URL . "/quick-post/");
                            exit;

                        }else{

                            $this->flash->setFlash( 'Blogger : Post sucessfully posted on - ' . $blogger_post_profile, 'success', '', true );
                        }
                    }
                }
            }
           
        }
      
        return $postflg;
    }


    public function sap_blogger_post_to_userwall( $post_id ){
        $postflg = false;

        $post = $this->posts->get_post( $post_id, true );
        $user_id = isset( $post->user_id ) ? $post->user_id : '';

        //Getting Blogger Options
        $blogger_options = $this->settings->get_user_setting( 'sap_blogger_options', $user_id );
               
        //Getting stored Blogger app data
        $sap_blogger_sess_data = $this->settings->get_user_setting( 'sap_blogger_sess_data', $user_id );
   
        if( !empty( $sap_blogger_sess_data ) ) {
            
            $sap_blogger_custom_msg = $this->posts->get_post_meta( $post_id, '_sap_blogger_post_title' );
            $sap_blogger_custom_accounts = $this->posts->get_post_meta( $post_id, '_sap_blogger_post_accounts' );
            $sap_blogger_custom_image = $this->posts->get_post_meta( $post_id, '_sap_blogger_post_img' );

            $sap_blogger_post_url = $this->posts->get_post_meta( $post_id, '_sap_blogger_post_url' );

            if ( empty( $sap_blogger_post_url ) ) {
                $sap_blogger_post_url = $blogger_options['blogger_url']; 
            }

            $blogger_image = !empty( $blogger_options['blogger_image'] ) ? $blogger_options['blogger_image'] : '';


            if ( !empty( $sap_blogger_custom_image ) ) {
                $sap_blogger_custom_image = $sap_blogger_custom_image;
            }
            elseif( isset($post->img) && $post->img != '0' && $post->img != '' ) {
                $sap_blogger_custom_image = $post->img;
            }
            else {
                $sap_blogger_custom_image = $blogger_image;
            }
            
            $sap_blogger_custom_image = SAP_IMG_URL.$sap_blogger_custom_image;

            $default_accounts = !empty( $blogger_options['posts_users'] ) ? $blogger_options['posts_users'] : '';
        
            
            $post_body  = !empty( $post->body ) ? html_entity_decode( strip_tags( $post->body ), ENT_QUOTES) : '' ;
            $description  = '';
            $description .= ( !empty( $post_body ) ) ? $post_body . "\r\n" : '';
           
            $posting_logs_data = array();
            $accounts = !empty( $sap_blogger_custom_accounts ) ? $sap_blogger_custom_accounts : $default_accounts;
             
            //Check Accounts exist
            if ( empty( $accounts ) ) {
                $this->flash->setFlash( $this->sap_common->lang('blogger_account_not_select'), 'error', '', true );
                
                $this->sap_common->sap_script_logs( $this->sap_common->lang('blogger_account_not_select'), $user_id );

                $status_meta_array[] = array(
                    "status" => 'error',
                    "message" => $this->sap_common->lang('blogger_account_not_select')
                );
                $this->quick_posts->update_post_meta( $post_id, "sap_blogger_posting_error", $status_meta_array );
                return false;
            }
                        
            if ( !empty( $accounts ) ) {

                foreach ( $accounts as $blogger_user_id ) {
                    $posting_access_token = ''; 
                    if( array_key_exists( $blogger_user_id, $sap_blogger_sess_data ) ) {

                        $posting_logs_data['display_name'] = $sap_blogger_sess_data[$blogger_user_id]['name'];
                        $blogger_post_profile = $sap_blogger_sess_data[$blogger_user_id]['display_name'];
                        $posting_logs_data['id'] = $blogger_user_id;
                        $posting_logs_data['body'] = $description;
                            
                        $access_token = $sap_blogger_sess_data[$blogger_user_id]['token_details']['access_token'];
                        $token_type = $sap_blogger_sess_data[$blogger_user_id]['token_details']['token_type'];

                        $refresh_token = $sap_blogger_sess_data[$blogger_user_id]['token_details']['refresh_token'];

                        $newTokenData = $this->get_exchange_token( $refresh_token );
                        $access_token = $newTokenData['access_token'];
                        $token_type   = $newTokenData['token_type'];


                        foreach ( $sap_blogger_post_url as $key => $value ) {

                            $bloggerUrlData = $this->getBloggerData( $value, $token_type.':'.$access_token );
                            
                            if ( empty( $bloggerUrlData->id ) && empty( $bloggerUrlData->selfLink ) ) {

                                $this->flash->setFlash( $this->sap_common->lang('something_went_wrong_debug'), 'error', '', true );
                                $bloggerUrlData = (array) $bloggerUrlData;
                                $this->logs->add_log( 'blogger', $bloggerUrlData, '', $user_id );

                                header("Location:" . SAP_SITE_URL . "/posts/");
                                exit;

                            }

                            $posting_logs_data['message'] = $sap_blogger_custom_msg;
                            $content = array(
                                'title'               => $sap_blogger_custom_msg,
                                'description'         => $description,
                                'submitted-image-url' => $sap_blogger_custom_image,
                                'kind'                => $bloggerUrlData->kind,
                                'blogID'              => $bloggerUrlData->id,
                                'selfLink'            => $bloggerUrlData->selfLink,
                            );  

                            if ( !empty( $access_token ) ) {
                                if( isset( $blogger_user_id ) && !empty( $blogger_user_id ) ) {
                                    $post_data = array(
                                        'title'               => $content['title'],
                                        'submitted-url'       => $content['submitted-url'],
                                        'submitted-image-url' => $content['submitted-image-url'],
                                        'description'         => $content['description'],
                                        'kind'                => $content['kind'],
                                        'blogID'              => $content['blogID'],
                                        'selfLink'            => $content['selfLink'],
                                        'access_token'        => $token_type." ".$access_token
                                    );

                                    $response = $this->createStory( $post_data );
                                           
                                    if( !empty( $response->id ) && $response->status == 'LIVE' ){

                                        $errorFind = false;

                                        $this->sap_common->sap_script_logs( 'Blogger post data : ' . var_export( $content, true ), $user_id );
                                                                        
                                        $this->logs->add_log('blogger', $posting_logs_data, '', $user_id );

                                        $postflg = true;

                                        $blogger_posting['success'] = 1;

                                    } else {

                                        $errorFind = true ;                                        
                                    }
                                }
                            }
                            
                        }
                        if ( $errorFind == true ) {
                            
                            $this->flash->setFlash( 'Blogger : Post not posted on - ' . $blogger_post_profile, 'error', '', true );

                            $_SESSION['sap_active_tab'] = 'blogger';
                            header("Location:" . SAP_SITE_URL . "/posts/");
                            exit;

                        }else{

                            $this->flash->setFlash( 'Blogger : Post sucessfully posted on - ' . $blogger_post_profile, 'success', '', true );

                        }
                    }
                }
            }
        }
      return  $postflg;
    }


    public function sap_get_blogger_accounts($user_id=''){
        // Taking some defaults
        $blogger_data = array();

        $sap_blogger_sess_data = $this->settings->get_user_setting('sap_blogger_sess_data', $user_id);
        if ( is_array( $sap_blogger_sess_data ) && !empty( $sap_blogger_sess_data ) ) {

            foreach ( $sap_blogger_sess_data as $blogger_sess_key => $blogger_sess_data ) {
                if( array_key_exists( 'name', $blogger_sess_data ) ){
                    $blogger_data[$blogger_sess_key] = $blogger_sess_data['name'];
                }
            }
        }

        return $blogger_data;
    }

    public function sap_get_blogger_urls($user_id=''){
        // Taking some defaults
        $blogger_data = array();

        $sap_blogger_options = $this->settings->get_user_setting('sap_blogger_options', $user_id);
                
        if ( is_array( $sap_blogger_options['blogger_url'] ) && !empty( $sap_blogger_options['blogger_url'] ) ) {

            $blogger_data = $sap_blogger_options['blogger_url'];
        }

        return $blogger_data;
    }


    /**
     * Get user
     *
     * Get data for the current user
     */
    public function getUser( $access_token ) {
        $this->auth_mode = 'oauth';
        return $this->runCurl( SAP_BLOGGER_USERINFO_URL, '', '', $this->auth_mode, false, $access_token );
    }


    /**
     * Reset Sessions
     *
     * Resetting the Blogger sessions when the admin clicks on
     * its link within the settings page.
     *
     * @package Social Auto Poster
     * @since 1.0.0
     */
    public function sap_blogger_reset_session() {
        
        if ( !empty( $_GET['sap_blogger_userid'] ) ) {
            $blogger_app_id = $_GET['sap_blogger_userid'];
    
            $sap_blogger_sess_data = $this->settings->get_user_setting('sap_blogger_sess_data');
                
            if( is_array( $sap_blogger_sess_data ) && !empty( $sap_blogger_sess_data ) ) {
                foreach ( $sap_blogger_sess_data as $uid => $uidData ) {
                    if ( $blogger_app_id == $uid ) {
                        unset( $sap_blogger_sess_data[$uid] );
                    }
                }
            }
           
            $update_setting = $this->settings->update_user_setting( 'sap_blogger_sess_data', $sap_blogger_sess_data );
            $this->sap_common->sap_script_logs( 'Blogger ' . $blogger_app_id . ' Account Reset Successfully.' );
            
            //Check response for DB Update
            if ( !empty( $update_setting ) ) {
                $this->flash->setFlash( $this->sap_common->lang('blogger_settings_update_msg'), 'success');
            } else {
                $this->flash->setFlash( $this->sap_common->lang('setting_saving_blogger_data_error_msg'), 'error');
            }
            $_SESSION['sap_active_tab'] = 'blogger';
            header("Location:" . SAP_SITE_URL . "/settings/");
            exit;
        }
    }
    
    public function getBloggerData( $url, $access_token ){

        $urlSubmit = "https://www.googleapis.com/blogger/v3/blogs/byurl?url=".$url;
               
        $this->auth_mode = 'oauth';

        $response = $this->runCurl( $urlSubmit , '', '', $this->auth_mode, '', $access_token, true );

        return $response;
    }

    /**
     * Create new story
     *
     * Creates a new story on a particular subblogger
     * @param string $title The title of the story
     */
    public function createStory( $post_data ) {

        $urlSubmit    = $post_data['selfLink'].'/posts/';
        $kind         = $post_data['kind'];
        $blogID       = $post_data['blogID'];
        $title        = $post_data['title'];
        $link         = $post_data['submitted-url'];
        $content      = $post_data['description'];
        $image        = $post_data['submitted-image-url'];


        if ( !empty( $image ) ) {
            $image = '<img src="'.$image.'"><br/>';
        }

        //data checks and pre-setup
        if ( $title == null || $content == null ) {
            return null;
        }

        $postData = array(
                        'kind' => $kind,
                        'blog' => array( 'id' => $blogID ),
                        'title' => $title,
                        'content' => $image.$content,
                        );

        //$this->auth_mode    = 'oauth';
        $this->access_token = $post_data['access_token'];
        $headers = array("Content-Type: application/json");
               
        $response = $this->runCurl( $urlSubmit , json_encode( $postData ), $headers, 'oauth', true, $this->access_token, false, true );

        return $response;
    }

    public function get_exchange_token( $refresh_token )    {
        
        if ( empty( $refresh_token ) ){
            $this->_show_error("Refresh token is missing");
        }

        $client_secret = SAP_BLOGGER_CLIENT_SECRET;
        $timestamp = time();
        $auth_token_url = 'https://www.googleapis.com/oauth2/v4/token';
        $postvals = array(  
                            'client_id'     => SAP_BLOGGER_CLIENT_ID,
                            'client_secret' => SAP_BLOGGER_CLIENT_SECRET,
                            'grant_type'    => 'refresh_token',
                            'refresh_token' => $refresh_token,

                        );

        $token = $this->runCurl( $auth_token_url, $postvals, null, true, false, '' );

        $token = array(
            'access_token' => $token->access_token,
            'token_type' => $token->token_type,
        );
        return $token;
    }

}
