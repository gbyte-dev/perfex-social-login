<?php

/* Check the absolute path to the Social Auto Poster directory. */
if ( !defined( 'SAP_APP_PATH' ) ) {
    // If SAP_APP_PATH constant is not defined, perform some action, show an error, or exit the script
    // Or exit the script if required
    exit();
}

/**
 * Reddit posting
 *
 * Handles all the functions to post on reddit
 * 
 * @package Social auto poster
 * @since 1.0.0
 */

// Include required libraries

class SAP_Reddit {
  
    private $client_id;
    private $redirect_uri;
    private $access_token;
    private $token_type;
    private $auth_uri = 'https://www.reddit.com/api/v1/authorize/?';
    private $scopes = array('save','modposts','identity','edit','flair','history','modconfig','modflair','modlog','modposts','modwiki','mysubreddits','privatemessages','read','report','submit','subscribe','vote','wikiedit','wikiread');
    private $auth_mode = 'basic';
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
       $this->sap_reddit_initialize($user_id);
        
    }

    /**
     * Assign Reddit User's all Data to session
     *
     * Handles to assign user's reddit data
     * to sessoin & save to database
     *
     * @package Social Auto Poster
     * @since 1.0.0
     */
    public function sap_reddit_initialize($user_id='') {
      
            
    // Get global SAP reddit options
    $sap_reddit_options= $this->settings->get_user_setting('sap_reddit_sess_data', $user_id);

             
    global $sap_options, $sap_message_stack;
  
    $user_accounts    = array();
    $reddit_sess_data = array();
          
    if( isset($_GET['code']) && isset( $_GET['sap_reddit_verification']) && $_GET['sap_reddit_verification'] == 'true' ) {
          
        $code = $_GET["code"];
        $redirect_url = SAP_REDDIT_REDIRECT_URL;
        $auth_token_url = 'https://www.reddit.com/api/v1/access_token';
        $postvals = sprintf("code=%s&redirect_uri=%s&grant_type=authorization_code", $code, $redirect_url);

        $token = $this->runCurl($auth_token_url, $postvals, null,true,false,'');
    
                $access_token = '';  
                if (isset($token->access_token) && $token->access_token != '') {
                    $access_token = "{$token->token_type}:{$token->access_token}";
                }

                $subreddit_data          = array();
                $user_details            = $this->getUser( $access_token );     
                $subscribed_subreddits   = $this->get_subscribed_subreddits( $access_token );
                $contribution_subreddits = $this->get_contributor_subreddits( $access_token );
                $moderation_subreddits   = $this->get_moderator_subreddits( $access_token );
                $stream_subreddit        = $this->get_streams_subreddits( $access_token );  

                
                if (!empty($subscribed_subreddits) && !empty($subscribed_subreddits->data->children) && is_array($subscribed_subreddits->data->children)) {
                   $subreddit_data = $subscribed_subreddits->data->children;  

                } if (!empty($subreddit_data) && !empty($contribution_subreddits) && !empty($contribution_subreddits->data->children) && is_array($contribution_subreddits->data->children)) {
                    $subreddit_data = array_merge( $subreddit_data , $contribution_subreddits->data->children );

                } if (!empty($subreddit_data) && !empty($moderation_subreddits) && !empty($moderation_subreddits->data->children) && is_array($moderation_subreddits->data->children)) {
                    $subreddit_data = array_merge( $subreddit_data , $moderation_subreddits->data->children );

                } if (!empty($subreddit_data) && !empty($stream_subreddit) && !empty($stream_subreddit->data->children) &&  is_array($stream_subreddit->data->children)) {
                    $subreddit_data = array_merge( $subreddit_data , $stream_subreddits->data->children );
                }
                
               foreach ( $subreddit_data as $key => $subreddit_acc ) {
                    if( $subreddit_acc->data->display_name == $user_details->subreddit->display_name ){
                        unset($subreddit_data[$key]);
                    }
                }

                
                $reddit_sess_data[$user_details->id] = array(
                    'name'          => $user_details->name,
                    'display_name'  => $user_details->subreddit->display_name,
                );

                $reddit_sess_data[$user_details->id]['token_details'] = array(
                    'authorized_timestamp' => time(),
                    'access_token' => $token->access_token,
                    'token_type' => $token->token_type,
                    'expire_timestamp' => $token->expires_in,
                    'refresh_token' => $token->refresh_token,
                );

                // $reddit_sess_data[$user_details->id]['subreddit_details'] = $subreddit_data; // Data not needed to store also this creating issue
                  
            if (!empty($sap_reddit_options)) {
                 // $reddit_sess_data = array_merge($sap_reddit_options, $reddit_sess_data);
                foreach ($sap_reddit_options as $key => $value) {
                   $reddit_sess_data[$key] = $value;
                }

             } else {
                 $reddit_sess_data = $reddit_sess_data;
             }
   
                         
             if(!empty($reddit_sess_data) && is_array($reddit_sess_data)) {
                
                $this->settings->update_user_setting('sap_reddit_sess_data', $reddit_sess_data);
             }

            $_SESSION['sap_active_tab'] = 'reddit';
            header("Location:" . SAP_SITE_URL . "/settings/");
            exit;
      }   
     
 
    }
    /**
     * Reddit Login URL link
     * 
     * @package Social Auto Poster
     * @since 3.5.2
    */

    public function sap_auto_poster_get_reddit_login_url() {
        
        $params = array(
            'duration'     => 'permanent',
            'response_type'=> 'code',
            'client_id'    => SAP_REDDIT_CLIENT_ID,
            'redirect_uri' => SAP_REDDIT_REDIRECT_URL,
            'scope'        => SAP_REDDIT_APP_SCOPE,
            'state'        => SAP_SITE_URL
        );

        $http_query = http_build_query($params);
            
        $auth_uri = 'https://www.reddit.com/api/v1/authorize/?';
   
        return $auth_uri . $http_query;

        //return $this->myreddit->reddit_login($state);
    }


     /**
     * Reset Sessions
     *
     * Resetting the Reddit sessions when the admin clicks on
     * its link within the settings page.
     *
     * @package Social Auto Poster
     * @since 1.0.0
     */
    public function sap_reddit_reset_session() {

        
        if ( !empty($_GET['sap_reddit_userid'])) {
            $reddit_app_id = $_GET['sap_reddit_userid'];
    
            $sap_reddit_sess_data = $this->settings->get_user_setting('sap_reddit_sess_data');
                
                if( is_array($sap_reddit_sess_data) && !empty($sap_reddit_sess_data)) {
                    foreach ($sap_reddit_sess_data as $uid => $uidData) {
                          if($uid == $reddit_app_id){
                               unset($sap_reddit_sess_data[$uid]);
                           }
                    }
                }
               
                $this->settings->update_user_setting('sap_reddit_sess_data', $sap_reddit_sess_data);
                $this->sap_common->sap_script_logs('Reddit ' . $reddit_app_id . ' Account Reset Successfully.', $user_id);
                $_SESSION['sap_active_tab'] = 'reddit';
                header("Location:" . SAP_SITE_URL . "/settings/");
                exit;
            }
        }
    

    /**
     * get all reddit account
     * 
     * @package Social Auto Poster
     * @since 1.0.0
     */
    public function sap_get_reddit_app_accounts($user_id='') {

        // Taking some defaults
        $res_data = array();

        $sap_reddit_sess_data = $this->settings->get_user_setting('sap_reddit_sess_data',$user_id);

        if (is_array($sap_reddit_sess_data) && !empty($sap_reddit_sess_data)) {

            foreach ($sap_reddit_sess_data as $reddit_sess_key => $reddit_sess_data) {
                if ($reddit_sess_key == $reddit_sess_data['sap_reddit_user_id']) {
                    $res_data[$reddit_sess_key] = $reddit_sess_data['sap_reddit_user_cache'];
                }
            }
        }

        return $res_data;
    }


     /**
     * Get Reddit Acccounts Lists
     *
     *
     * @package Social Auto Poster
     * @since 3.5.2
     */
    function sap_get_reddit_accounts($user_id=''){

        $res_data = array();

        $sap_reddit_sess_data =$this->settings->get_user_setting('sap_reddit_sess_data', $user_id);

        if( !empty($sap_reddit_sess_data) && is_array($sap_reddit_sess_data) ) {
            foreach($sap_reddit_sess_data as $key => $accounts){
                if(array_key_exists('name',$accounts)){
                    
                    $res_data[$key] = $accounts['name'];    
                }
                        
                
            }
        }
   
        return $res_data;
    }
    
   

     /**
     * cURL request
     *
     * General cURL request function for GET and POST
     * @link URL
     * @param string $url URL to be requested
     * @param string $postVals NVP string to be send with POST request
     */
    
    public function runCurl($url, $postVals = null, $headers = null, $auth = false,$posting = false , $access_token = '') {
    
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

        if ($this->auth_mode == 'oauth') {

            
            if($posting) {
                $access_token = '';
                $access_token = $this->access_token;

            } else {

                if(isset($access_token) && $access_token != ''){
                    $access_token = $access_token;
                }

            }

            if (isset($access_token) && $access_token != '') {
                $token = explode(":",$access_token);
                $headers = array("Authorization: {$token[0]} {$token[1]}");

            } else {
                $headers = array("Authorization: Basic " . base64_encode( SAP_REDDIT_CLIENT_ID.":".SAP_REDDIT_SECRET_KEY));
            }
     
            $options[CURLOPT_HEADER] = false;
            $options[CURLINFO_HEADER_OUT] = false;
            $options[CURLOPT_HTTPHEADER] = $headers;
        }

        if ( $auth === true ) {

            $header = array ( 'Authorization' => 'Basic '. base64_encode(SAP_REDDIT_CLIENT_ID.":".SAP_REDDIT_SECRET_KEY) );
            $header_array = array();
            foreach( $header as $k => $v )
            {
                $header_array[] = $k.': '.$v;
            }
           

            $options[CURLOPT_HTTPHEADER] = $header_array;
            //$options[CURLOPT_SSLVERSION] = 4;
            $options[CURLOPT_SSL_VERIFYPEER] = true;
            $options[CURLOPT_SSL_VERIFYHOST] = 2;
        }
   
    
        curl_setopt_array($ch, $options);
                  
        $apiResponse = curl_exec($ch);
       
        $response = json_decode($apiResponse);

        //check if non-valid JSON is returned
        if ($error = json_last_error()) {
            $response = $apiResponse;
        }
        curl_close($ch);

        return $response;
    }

     /**
     * Get user
     *
     * Get data for the current user
     * @link http://www.reddit.com/dev/api#GET_api_v1_me
     */
    public function getUser($access_token) {
        $this->auth_mode = 'oauth';
        $urlUser = "https://oauth.reddit.com/api/v1/me";
        return $this->runCurl($urlUser, '', '', $this->auth_mode,false,$access_token);
    }


     /**
     * Get Sub-Reddits from the current account which are subscribed
     *
     * Get data for the sub-reddits
     * http://www.reddit.com/dev/api#GET_api_v1_me
    */

    public function get_subscribed_subreddits( $access_token ) {
        
        $get_subscribed_subreddits_url = 'https://oauth.reddit.com/subreddits/mine/subscriber';
        return $this->runCurl($get_subscribed_subreddits_url, '', '','','', $access_token);
    }

    /**
     * Get Sub-Reddits from the current account in which user is approved user
     *
     * Get data for the sub-reddits
     * http://www.reddit.com/dev/api#GET_api_v1_me
    */

    public function get_contributor_subreddits( $access_token ) {

        $get_contributor_subreddits_url = 'https://oauth.reddit.com/subreddits/mine/contributor';
        return $this->runCurl($get_contributor_subreddits_url, '', '','','', $access_token);

    }   

    /**
     * Get Sub-Reddits from the current account in which user is moderator of that subreddit
     *
     * Get data for the sub-reddits
     * http://www.reddit.com/dev/api#GET_api_v1_me
    */

    public function get_moderator_subreddits( $access_token ) {

        $get_moderator_subreddits_url = 'https://oauth.reddit.com/subreddits/mine/moderator';
        return $this->runCurl($get_moderator_subreddits_url, '', '','','', $access_token);

    }

     /**
     * Get Sub-Reddits from the current account in which subreddits contains hosted video link streams
     *
     * Get data for the sub-reddits
     * http://www.reddit.com/dev/api#GET_api_v1_me
    */

    public function get_streams_subreddits( $access_token ) {

        $get_streams_subreddits_url = 'https://oauth.reddit.com/subreddits/mine/streams';
        return $this->runCurl($get_streams_subreddits_url, '', '','','', $access_token);

    }

      /**
     * Post to User Wall on Reddit
     *
     * Handles to post user wall on reddit
     *
     * @package Social Auto Poster
     * @since 1.0.0
     */
    public function sap_reddit_post_to_userwall($post_id) {

        $postflg = false;

        $post = $this->posts->get_post($post_id, true);
        $user_id = isset( $post->user_id ) ? $post->user_id : '';

        //Getting Reddit Options
        $reddit_options = $this->settings->get_user_setting('sap_reddit_options', $user_id);

       //Getting stored reddit app data
        $sap_reddit_sess_data = $this->settings->get_user_setting('sap_reddit_sess_data', $user_id);

        // General setting
        $sap_general_options = $this->settings->get_user_setting('sap_general_options',$user_id);

        $link_timestamp = isset($sap_general_options['timestamp_link']) ? "?".time() : '';
   
      if( !empty( $sap_reddit_sess_data ) ) {
        $sap_reddit_post_type = $this->posts->get_post_meta($post_id, '_sap_reddit_post_type');
        $sap_reddit_custom_msg = $this->posts->get_post_meta($post_id, '_sap_reddit_post_msg');


            $sap_reddit_custom_accounts = $this->posts->get_post_meta($post_id, '_sap_reddit_post_accounts');
            $sap_reddit_custom_image = $this->posts->get_post_meta($post_id, '_sap_reddit_post_img');
            $reddit_image = !empty($reddit_options['reddit_image']) ? $reddit_options['reddit_image'] : '';
            $default_accounts = !empty($reddit_options['posts_users']) ? $reddit_options['posts_users'] : '';
        
            //If disable default image
            $disable_image_reddit='';
            if( isset($reddit_options['disable_image_reddit']) && !empty($reddit_options['disable_image_reddit']) ){

                $disable_image_reddit =  $reddit_options['disable_image_reddit'];

            }
   
           


        if( !empty( $post->share_link ) ) {

            $post_link  = strip_tags($post->share_link);

            if(!empty($post_link)) {     
                $post_link = $post_link."".$link_timestamp;
            }

            $customlink = !empty($post_link) ? 'true' : 'false';

            $post_link  = $this->common->sap_script_short_post_link($post_link,$customlink,'reddit','reddit',$user_id);
        }

        $post_body  = !empty($post->body) ? html_entity_decode(strip_tags($post->body),ENT_QUOTES): '';
        $description  = '';
        $description .= (!empty($post_link) ) ? $post_link . "\r\n" : '';
        $description .= (!empty($post_body) ) ? $post_body . "\r\n" : '';

        // Reddit limit 40000 character per post
        if (!empty($description))
            $description = $this->posts->sap_limit_character($description, 40000);
           
        if(!empty($sap_reddit_custom_image) ){
            $postimage = $sap_reddit_custom_image;
        }elseif (isset($post->img) && $post->img != '0' && $post->img != '') {
           $postimage = $post->img;
        }else{
            if ( empty($disable_image_reddit)) {
                $postimage = $reddit_image;
            }
            
        }
           

            // if( isset($reddit_image) && !empty($reddit_image) ){
            //     $postimage = $reddit_image;
            // }else if( isset($reddit_image) && empty($reddit_image) ){
            //     if( isset($post->img) && $post->img != '0' && $post->img != '' ){
            //         $postimage = $post->img;

            //     }else{
            //         $postimage = $sap_reddit_custom_image;
            //     }
            // }
                    
                
            $posting_logs_data = array();
            $accounts = !empty($sap_reddit_custom_accounts) ? $sap_reddit_custom_accounts : $default_accounts;

             
             //Check Accounts exist
            if (empty($accounts)) {
                $this->flash->setFlash('Reddit posting users are not selected.', 'error','',true);
                $this->sap_common->sap_script_logs('Reddit posting users are not selected.', $user_id );
                $status_meta_array[] = array(
                    "status" => 'error',
                    "message" => 'Reddit posting users are not selected.'
                );
                $this->quick_posts->update_post_meta($post_id,"sap_reddit_posting_error", $status_meta_array);
                return false;
            }
             
                        
            if (!empty($accounts)) {

                   foreach ($accounts as $reddit_user_id) {
                      $posting_access_token = ''; 
                       if(array_key_exists($reddit_user_id, $sap_reddit_sess_data)) {

                            $posting_logs_data['display_name'] = $sap_reddit_sess_data[$reddit_user_id]['name'];

                            $reddit_post_profile=$sap_reddit_sess_data[$reddit_user_id]['display_name'];

                            $posting_logs_data['id'] = $reddit_user_id;
                            $posting_logs_data['body'] = $description;

                            if ( !empty($reddit_post_profile && $sap_reddit_sess_data[$reddit_user_id]['name'] != $reddit_post_profile)) {
                                $posting_logs_data['subreddit_name'] = !empty($reddit_user_id) ? $reddit_user_id : '';    
                            }
                            
                            $refresh_token = $sap_reddit_sess_data[$reddit_user_id]['token_details']['refresh_token'];

                            $old_token_time = $sap_reddit_sess_data[$reddit_user_id]['token_details']['authorized_timestamp'];

                            $old_time = strtotime(date('H:i:s',$old_token_time));
                            $current_time = strtotime(date('H:i:s'),time());

                            $difference = round(abs($current_time - $old_time) / 60);


                            $newTokenData = $this->get_exchange_token($refresh_token);
                            $access_token = $newTokenData['access_token'];
                            $token_type   = $newTokenData['token_type'];
                            $posting_logs_data['message'] =$sap_reddit_custom_msg;
                            $content = array(
                                'title'         => $sap_reddit_custom_msg,
                                'submitted-url' => $post_link,
                                'comment'       => '',
                                'description'   => $description,
                                'submitted-image-url' => SAP_IMG_URL.$postimage,
                                'user_name'     => $reddit_post_profile 
                            );  

                            if (!empty($access_token)) {
                                 if(isset($reddit_user_id) && !empty( $reddit_user_id)) {
                                     $post_data = array(
                                        'title'               => $content['title'],
                                        'submitted-url'       => $content['submitted-url'],
                                        'comment'             => $content['comment'],
                                        'submitted-image-url' => $content['submitted-image-url'],
                                        'description'         => $content['description'],
                                        'post_type'           => $sap_reddit_post_type,
                                        'subreddit_name'      => $reddit_post_profile,
                                        'access_token'        => $token_type.":".$access_token
                                    );

                                     $response = $this->createStory($post_data);
                                         
                                    if(!empty($response) && $response->success == '1'){
                                        
                                        $this->sap_common->sap_script_logs('Reddit post data : ' . var_export($content, true), $user_id );
                                        
                                        $this->flash->setFlash('Reddit : Post sucessfully posted on - ' . $reddit_post_profile, 'success','',true);
                                        
                                        $this->logs->add_log('reddit', $posting_logs_data, $sap_reddit_post_type, $user_id);

                                        $postflg = true;

                                        $reddit_posting['success'] = 1;

                                    } else {
                                              
                                        $msg = isset( $response->jquery[22][3][0] ) ? $response->jquery[22][3][0] : '';
                                        $msg_subreddit_unable  = isset( $response->jquery[14][3][0] ) ? $response->jquery[14][3][0] : '';
                                        $allowed_only_text_post = isset( $response->jquery[20][3][0] ) ? $response->jquery[20][3][0] : '';
                                        $account_name = !empty( $reddit_post_profile ) ? $reddit_post_profile : '';

                                        if(!empty($response) && $response->success == '' && $msg == 'that link has already been submitted'){
                                            $this->sap_common->sap_script_logs('Reddit: That link has already been submitted.', $user_id);

                                            // if( $post_type == 'wpwsapquickshare'){
                                            //     update_post_meta($post->ID, $prefix . 'reddit_post_status','error');
                                            //     update_post_meta($post->ID, $prefix . 'reddit_error', esc_html__('The link has already been submitted.', 'wpwautoposter' ) );
                                            // }
                                            
                                            $this->flash->setFlash('Reddit: That link has already been submitted.', 'error','',true);
                                            $postflg = false;
                                            $reddit_posting['fail'] = 0;

                                        } else if(!empty($response) && $response->success == '' && $msg == 'This community doesn\'t allow links to be posted more than once, and this link has already been shared') {
                                             $this->sap_common->sap_script_logs('Reddit: This community doesn\'t allow links to be posted more than once, and this link has already been shared', $user_id);
                                            
                                            // if( $post_type == 'wpwsapquickshare'){
                                            //     update_post_meta($post->ID, $prefix . 'reddit_post_status','error');
                                            //     update_post_meta($post->ID, $prefix . 'reddit_error', esc_html__('The link has already been submitted.', 'wpwautoposter' ) );
                                            // }
                                            $this->flash->setFlash('Reddit: This community doesn\'t allow links to be posted more than once, and this link has already been shared', 'error','',true);
                                            $postflg = false;
                                            $reddit_posting['fail'] = 0;

                                        } else if ( !empty($response) && $response->success == '' && $msg_subreddit_unable == 'you aren\'t allowed to post there.' ) {

                                            
                                            $this->sap_common->sap_script_logs('Reddit : You aren\'t allowed to post on '.$account_name, $user_id);

                                            // if( $post_type == 'wpwsapquickshare'){
                                            //     update_post_meta($post->ID, $prefix . 'reddit_post_status','error');
                                            //     update_post_meta($post->ID, $prefix . 'reddit_error', esc_html__('Reddit : You aren\'t allowed to post on ' . $account_name, 'wpwautoposter' ) );
                                            // }
                                             $this->flash->setFlash('Reddit : You aren\'t allowed to post on ' . $account_name, 'error','',true);
                                            
                                            $postflg = false;
                                            $reddit_posting['fail'] = 0;

                                        } else if ( !empty($response) && $response->success == '' && $msg_subreddit_unable == 'that subreddit does not allow image posts' ) {

                                            $this->sap_common->sap_script_logs('Reddit : photo posting is not allowed on '.$account_name, $user_id);

                                            // if( $post_type == 'wpwsapquickshare'){
                                            //     update_post_meta($post->ID, $prefix . 'reddit_post_status','error');
                                            //     update_post_meta($post->ID, $prefix . 'reddit_error', esc_html__('Reddit : photo posting is not allowed on ' . $account_name, 'wpwautoposter' ) );
                                            // }
                                            $this->flash->setFlash('Reddit : photo posting is not allowed on ' . $account_name, 'error','',true);
                                            $postflg = false;
                                            $reddit_posting['fail'] = 0;

                                        } else if ( !empty($response) && $response->success == '' && $msg_subreddit_unable == 'Reddit doesn\'t allow links from tinyurl.com: link shorteners are smelly' ) {

                                            $this->sap_common->sap_script_logs('Reddit doesn\'t allow links from tinyurl.com are not supported for '.$account_name, $user_id);

                                            // if( $post_type == 'wpwsapquickshare'){
                                            //     update_post_meta($post->ID, $prefix . 'reddit_post_status','error');
                                            //     update_post_meta($post->ID, $prefix . 'reddit_error', esc_html__('Reddit doesn\'t allow links from tinyurl.com are not supported for ' . $account_name, 'wpwautoposter' ) );
                                            // }

                                            $this->flash->setFlash('Reddit doesn\'t allow links from tinyurl.com are not supported for ' . $account_name, 'error','',true);
                                            
                                            $postflg = false;
                                            $reddit_posting['fail'] = 0;

                                        } else if ( !empty($response) && $response->success == '' && $allowed_only_text_post == 'that subreddit only allows text posts' ) {

                                            $this->sap_common->sap_script_logs('Reddit : only text posting is allowed for  ' . $account_name, $user_id);

                                            // if( $post_type == 'wpwsapquickshare'){
                                            //     update_post_meta($post->ID, $prefix . 'reddit_post_status','error');
                                            //     update_post_meta($post->ID, $prefix . 'reddit_error', esc_html__('Reddit : only text posting is allowed for ' . $account_name, 'wpwautoposter' ) );
                                            // }
                                             $this->flash->setFlash('Reddit : only text posting is allowed for ' . $account_name, 'error','',true);
                                            
                                            $postflg = false;
                                            $reddit_posting['fail'] = 0;


                                        } else {

                                            $this->flash->setFlash('Reddit: Something went wrong. ' . $account_name, 'error','',true);
                                            $this->sap_common->sap_script_logs('Reddit: Something went wrong.', $user_id);
                                            // if( $post_type == 'wpwsapquickshare'){
                                            //     update_post_meta($post->ID, $prefix . 'reddit_post_status','error');
                                            //     update_post_meta($post->ID, $prefix . 'reddit_error', esc_html__('Post not published, please try again.', 'wpwautoposter' ) );
                                            // }
                                            $postflg = false;
                                            $reddit_posting['fail'] = 0;
                                        }

                                    }
                                 }
                            }
                       }
                   }
            }

      }
      return  $postflg;
            
    }


     /**
     * Post to User Wall on Reddit
     *
     * Handles to post user wall on reddit
     *
     * @package Social Auto Poster
     * @since 1.0.0
     */
    public function sap_quick_post_on_reddit_post($post_id) {

        $postflg = false;

        $quick_posts = $this->quick_posts->get_post($post_id, true);
      
            
        $user_id = isset( $quick_posts->user_id ) ? $quick_posts->user_id : '';
        $sap_networks_meta = $this->quick_posts->get_post_meta($post_id, 'sap_networks');
        $accounts = !empty($sap_networks_meta['reddit_accounts']) ? $sap_networks_meta['reddit_accounts'] : array();
        //Getting Reddit Options
        $reddit_options = $this->settings->get_user_setting('sap_reddit_options', $user_id);

       //Getting stored reddit app data
        $sap_reddit_sess_data = $this->settings->get_user_setting('sap_reddit_sess_data', $user_id);

        // General setting
        $sap_general_options = $this->settings->get_user_setting('sap_general_options',$user_id);

        $link_timestamp = isset($sap_general_options['timestamp_link']) ? "?".time() : '';

        if( !empty( $quick_posts ) ) {

        
          $reddit_image = !empty($reddit_options['reddit_image']) ? $reddit_options['reddit_image'] : '';
          $default_accounts = !empty($reddit_options['posts_users'])? $reddit_options['posts_users']: '';

         // $description = $quick_posts->message;
            

            if( !empty( $quick_posts->share_link ) ) {

                $post_link  = $quick_posts->share_link;

                if(!empty($post_link)) {
                    $post_link = $post_link."".$link_timestamp;
                }

                $post_link  = strip_tags($post_link);
                $customlink = !empty($post_link) ? 'true' : 'false';
                $post_link  = $this->common->sap_script_short_post_link($post_link,$customlink,'reddit','reddit', $user_id);
            }

            $post_body  = !empty($quick_posts->message) ? html_entity_decode(strip_tags($quick_posts->message),ENT_QUOTES): '';


         

            $description  = '';
            $description .= (!empty($post_link) ) ? $post_link . "\r\n" : '';
            $description .= (!empty($post_body) ) ? $post_body . "\r\n" : '';

            // Reddit limit 40000 character per post
            if (!empty($description))
                $description = $this->posts->sap_limit_character($description, 40000);

            if(!empty($quick_posts->image) ){
                $postimage = $quick_posts->image;
           
            }else{
                $postimage = $reddit_image;
            }
             
            // if( isset($reddit_image) && !empty($reddit_image) ){
            //     $postimage = $reddit_image;
            // }else if( isset($reddit_image) && empty($reddit_image) ){
            //     if( isset($quick_posts->image) && $quick_posts->image != '0' && $quick_posts->image != '' ){
            //         $postimage = $quick_posts->image;
            //     }else{
            //         $postimage = $sap_reddit_custom_image;
            //     }
            // }
            if(!empty($postimage)){
                $sap_reddit_post_type = 'image';
            }elseif(!empty($post_link)){
                $sap_reddit_post_type = 'link';
            }else{
                $sap_reddit_post_type = 'self';
            }
            $posting_logs_data = array();
          
            $accounts = !empty($accounts) ? $accounts : $default_accounts;

             //Check Accounts exist
            if (empty($accounts)) {
                $this->flash->setFlash('Reddit posting users are not selected.', 'error','',true);
                $this->sap_common->sap_script_logs('Reddit posting users are not selected.', $user_id );
                $status_meta_array[] = array(
                    "status" => 'error',
                    "message" => 'Reddit posting users are not selected.'
                );
                $this->quick_posts->update_post_meta($post_id,"sap_reddit_posting_error", $status_meta_array);
                return false;
            }
                       
            if (!empty($accounts)) { 
                foreach ($accounts as $reddit_user_id) {
                    if(array_key_exists($reddit_user_id, $sap_reddit_sess_data)) {

                         $posting_logs_data['display_name'] = $sap_reddit_sess_data[$reddit_user_id]['name'];

                            $reddit_post_profile=$sap_reddit_sess_data[$reddit_user_id]['display_name'];

                            $posting_logs_data['id'] = $reddit_user_id;

                            if ( !empty($reddit_post_profile && $sap_reddit_sess_data[$reddit_user_id]['name'] != $reddit_post_profile) ) {
                                $posting_logs_data['subreddit_name'] = !empty($reddit_user_id) ? $reddit_user_id : '';    
                            }
                            
                            $refresh_token = $sap_reddit_sess_data[$reddit_user_id]['token_details']['refresh_token'];

                            $old_token_time = $sap_reddit_sess_data[$reddit_user_id]['token_details']['authorized_timestamp'];

                            $old_time = strtotime(date('H:i:s',$old_token_time));
                            $current_time = strtotime(date('H:i:s'),time());

                            $difference = round(abs($current_time - $old_time) / 60);


                            $newTokenData = $this->get_exchange_token($refresh_token);
                            $access_token = $newTokenData['access_token'];
                            $token_type   = $newTokenData['token_type'];
                            $posting_logs_data['body'] = $description;
                            $content = array(
                                'title'         => $description,
                                'submitted-url' => $post_link,
                                'comment'       => $description,
                                'description'   => $description,
                                'submitted-image-url' => SAP_IMG_URL.$postimage,
                                'user_name'     => $reddit_post_profile 
                            ); 

                            if (!empty($access_token)) {

                                 if(isset($reddit_user_id) && !empty( $reddit_user_id)) {
                                     $post_data = array(
                                        'title'               => $content['title'],
                                        'submitted-url'       => $content['submitted-url'],
                                        'comment'             => $content['comment'],
                                        'submitted-image-url' => $content['submitted-image-url'],
                                        'description'         => $content['description'],
                                        'post_type'           => $sap_reddit_post_type,
                                        'subreddit_name'      => $reddit_post_profile,
                                        'access_token'        => $token_type.":".$access_token
                                    );
   
                
                                    $response = $this->createStory($post_data);
                                   
                                     if(!empty($response) && $response->success == '1'){
                                        
                                        $this->sap_common->sap_script_logs( 'Reddit Quick post data : ' . var_export($content, true), $user_id );
                                      

                                        $this->flash->setFlash('Reddit : Quick Post sucessfully posted on - ' . $reddit_post_profile, 'success','',true);

                                        $this->logs->add_log('reddit', $posting_logs_data, $sap_reddit_post_type, $user_id);

                                        $postflg = true;

                                        $reddit_posting['success'] = 1;

                                    } else {
                                              
                                        $msg = isset( $response->jquery[22][3][0] ) ? $response->jquery[22][3][0] : '';
                                        $msg_subreddit_unable  = isset( $response->jquery[14][3][0] ) ? $response->jquery[14][3][0] : '';
                                        $allowed_only_text_post = isset( $response->jquery[20][3][0] ) ? $response->jquery[20][3][0] : '';
                                        $account_name = !empty( $reddit_post_profile ) ? $reddit_post_profile : '';

                                        if(!empty($response) && $response->success == '' && $msg == 'that link has already been submitted') {
                                            $this->sap_common->sap_script_logs('Reddit: That link has already been submitted.', $user_id);

                                            
                                            $this->flash->setFlash('Reddit: That link has already been submitted.', 'error','',true);
                                            $postflg = false;
                                            $reddit_posting['fail'] = 0;

                                        } else if(!empty($response) && $response->success == '' && $msg == 'This community doesn\'t allow links to be posted more than once, and this link has already been shared') {
                                             $this->sap_common->sap_script_logs('Reddit: This community doesn\'t allow links to be posted more than once, and this link has already been shared', $user_id);
                                          
                                            $this->flash->setFlash('Reddit: This community doesn\'t allow links to be posted more than once, and this link has already been shared', 'error','',true);
                                            $postflg = false;
                                            $reddit_posting['fail'] = 0;

                                        } else if ( !empty($response) && $response->success == '' && $msg_subreddit_unable == 'you aren\'t allowed to post there.' ) {

                                            
                                            $this->sap_common->sap_script_logs('Reddit : You aren\'t allowed to post on '.$account_name, $user_id);

                                          
                                             $this->flash->setFlash('Reddit : You aren\'t allowed to post on ' . $account_name, 'error','',true);
                                            
                                            $postflg = false;
                                            $reddit_posting['fail'] = 0;

                                        } else if ( !empty($response) && $response->success == '' && $msg_subreddit_unable == 'that subreddit does not allow image posts' ) {

                                            $this->sap_common->sap_script_logs('Reddit : photo posting is not allowed on '.$account_name, $user_id);

                                            $this->flash->setFlash('Reddit : photo posting is not allowed on ' . $account_name, 'error','',true);
                                            $postflg = false;
                                            $reddit_posting['fail'] = 0;

                                        } else if ( !empty($response) && $response->success == '' && $msg_subreddit_unable == 'Reddit doesn\'t allow links from tinyurl.com: link shorteners are smelly' ) {

                                            $this->sap_common->sap_script_logs('Reddit doesn\'t allow links from tinyurl.com are not supported for '.$account_name, $user_id);

                                            $this->flash->setFlash('Reddit doesn\'t allow links from tinyurl.com are not supported for ' . $account_name, 'error','',true);
                                            
                                            $postflg = false;
                                            $reddit_posting['fail'] = 0;

                                        } else if ( !empty($response) && $response->success == '' && $allowed_only_text_post == 'that subreddit only allows text posts' ) {

                                            $this->sap_common->sap_script_logs('Reddit : only text posting is allowed for  ' . $account_name, $user_id);

                                             $this->flash->setFlash('Reddit : only text posting is allowed for ' . $account_name, 'error','',true);
                                            
                                            $postflg = false;
                                            $reddit_posting['fail'] = 0;


                                        } else {

                                            $this->flash->setFlash('Reddit: Something went wrong. ' . $account_name, 'error','',true);
                                            $this->sap_common->sap_script_logs('Reddit: Something went wrong.', $user_id);
                                          
                                            $postflg = false;
                                            $reddit_posting['fail'] = 0;
                                        }     
                                       
                                    }
                                    
                                 }
                            } 
                        
                    }
                }
            }
           

        }
          return $postflg;
            
    }

    public function get_exchange_token($refresh_token)    {
        
        if (empty($refresh_token))
        {
            $this->_show_error("Refresh token is missing");
        }


        $redirect_url  = SAP_REDDIT_REDIRECT_URL;
        $client_id     = SAP_REDDIT_CLIENT_ID;
        $client_secret = SAP_REDDIT_SECRET_KEY;
        $timestamp = time();
        $auth_token_url = 'https://www.reddit.com/api/v1/access_token';
        $postvals = array('grant_type' => 'refresh_token',
                          'refresh_token' => $refresh_token
                        );

        $token = $this->runCurl($auth_token_url, $postvals, null, true,false,'');

        $token = array(
            'access_token' => $token->access_token,
            'token_type' => $token->token_type,
        );
        return $token;
    }

    /**
     * Create new story
     *
     * Creates a new story on a particular subreddit
     * @link http://www.reddit.com/dev/api/oauth#POST_api_submit
     * @param string $title The title of the story
     * @param string $link The link that the story should forward to
     * @param string $subreddit The subreddit where the story should be added
     */
    public function createStory($post_data) {
        $urlSubmit = "https://oauth.reddit.com/api/submit";

        $title   = $post_data['title'];
        $link    = $post_data['submitted-url'];
        $content = $post_data['description'];
        $image   = $post_data['submitted-image-url'];
        $subreddit = $post_data['subreddit_name'];
        $posting_type = $post_data['post_type'];


        //data checks and pre-setup
        if ($title == null || $subreddit == null) {
            return null;
        }

        $title = isset($title) ? $title : $content;

        $kind = ($posting_type == null) ? "self" : $posting_type;

        if (isset($posting_type) && $posting_type != '') {
            if ($posting_type == 'image') {
                $postData = sprintf("kind=image&url=%s&sr=%s&title=%s&r=%s", $image, $subreddit, $title, $subreddit
                );
            } else if ($posting_type == 'link') {
                $postData = sprintf("kind=link&url=%s&sr=%s&title=%s&r=%s", $link, $subreddit, $title, $subreddit
                );
            } else if ($posting_type == 'self') {
                $postData = sprintf("kind=self&sr=%s&title=%s&r=%s&text=%s", $subreddit, $title, $subreddit, $content
                );
            } else {
                $postData = sprintf("kind=self&sr=%s&title=%s&r=%s&text=%s", $subreddit, $title, $subreddit, $content
                );
            }
        }
        $this->auth_mode    = 'oauth';
        $this->access_token = $post_data['access_token'];

        $response = $this->runCurl('https://oauth.reddit.com/api/submit', $postData, '', $this->auth_mode,true,'');

        return $response;
    }



}
