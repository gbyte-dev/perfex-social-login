<?php

/* Check the absolute path to the Social Auto Poster directory. */
if ( !defined( 'SAP_APP_PATH' ) ) {
    // If SAP_APP_PATH constant is not defined, perform some action, show an error, or exit the script
    // Or exit the script if required
    exit();
}

/**
 * Twitter posting
 *
 * Handles all the functions to tweet on twitter
 * 
 * @package Social auto poster
 * @since 1.0.0
 */
class SAP_Twitter {

    private $db, $common, $flash, $twitter, $settings, $user_id;

    public function __construct($from_user_id='') {
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
        $this->user_id = $from_user_id;
    }

    /**
     * Include Twitter Class
     * 
     * Handles to load twitter class
     * 
     * @package Social Auto Poster
     * @since 1.0.0
     */
    public function sap_load_twitter($consumer_key, $consumer_secret, $oauth_token, $oauth_secret) {

        //check twitter application id and application secret is not empty or not
        if (!empty($consumer_key) && !empty($consumer_secret) && !empty($oauth_token) && !empty($oauth_secret)) {

            if (!class_exists('Codebird')) {
                require_once( LIB_PATH . 'Social' . DS . 'Twitter' . DS . 'twitter.php' );
            }

            // Twitter Object
            \Codebird\Codebird::setConsumerKey($consumer_key, $consumer_secret);

            $this->twitter = \Codebird\Codebird::getInstance();

            $this->twitter->setToken($oauth_token, $oauth_secret);

            return true;
        } else {
            return false;
        }
    }

    /**
     * Get Twitter User Data
     * 
     * Handles to get twitter user data
     * 
     * @package Social Auto Poster
     * @since 1.4.0
     */
    public function sap_get_user_data($consumer_key, $consumer_secret, $oauth_token, $oauth_secret) {

        //load twitter class
        $twitter = $this->sap_load_twitter($consumer_key, $consumer_secret, $oauth_token, $oauth_secret);

        //check twitter class is loaded or not
        if (!$twitter)
            return false;

        //getting user data from twitter
        $response = $this->twitter->account_verifyCredentials();
        
        // Double check if response is in json then again decode it
        if (is_string($response)) {
            $response = json_decode($response);
        }
        
        $user_id = $this->user_id;
        //if user data get successfully
        if (isset($response->id_str) && $response->id_str) {
            //record logs for grant extended permission
            $this->sap_common->sap_script_logs('Twitter Grant Extended Permission', $user_id);

            //record logs for get parameters set properly
            $this->sap_common->sap_script_logs('Get Parameters Set Properly.', $user_id);
            
            
            $this->sap_common->sap_script_logs('Twitter Session Data Updated to Options', $user_id);
            return $response;
        }

        return false;
    }

    /**
     * Post To Twitter
     * 
     * Handles to Post on Twitter account
     * 
     * @package Social Auto Poster
     * @since 1.0.0
     */
    public function sap_post_to_twitter($post_id) {
        
        $postflg = false;

        //Get Post details
        $post = $this->posts->get_post($post_id, true);
        $user_id = isset( $post->user_id ) ? $post->user_id : '';

        //Get general options;
        $sap_twitter_options = $this->settings->get_user_setting('sap_twitter_options', $user_id);
        $default_accounts = !empty($sap_twitter_options['posts_users']) ? $sap_twitter_options['posts_users'] : '';
        $sap_twitter_accounts_details = $this->settings->get_user_setting('sap_twitter_accounts_details', $user_id);

        // General setting
        $sap_general_options = $this->settings->get_user_setting('sap_general_options',$user_id);

        $link_timestamp = isset($sap_general_options['timestamp_link']) ? "?".time() : '';

        $twitter_keys = !empty($sap_twitter_options['twitter_keys']) ? $sap_twitter_options['twitter_keys'] : array();
        $disable_image_tweet = !empty($sap_twitter_options['disable_image_tweet']) ? $sap_twitter_options['disable_image_tweet'] : '';
        $tweet_image = !empty($sap_twitter_options['tweet_image']) ? $sap_twitter_options['tweet_image'] : '';
        $message = $this->posts->get_post_meta($post_id, '_sap_tw_msg');
        $posting_type = $this->posts->get_post_meta($post_id, '_sap_tw_status');

        $post_link = strip_tags($post->share_link);
        
        if(!empty($post_link)) {
            $post_link = $post_link."".$link_timestamp;
        }
        $customlink  = !empty($post_link) ? 'true' : 'false';
        $post_link   = $this->common->sap_script_short_post_link($post_link,$customlink,'tw','twitter', $user_id);

        $post_body = !empty($message) ? $message : $post->body;
        $post_body = !empty($post_body) ? html_entity_decode(strip_tags($post_body),ENT_QUOTES) : '';

        $post_desc = $image = '';
        $post_desc .= (!empty($post_link) ) ? $post_link . "\r\n" : '';
        $post_desc .= (!empty($post_body) ) ? $post_body . "\r\n" : '';

        // Twitter limit 280 character for tweet
        if (!empty($post_desc))
            $post_desc = $this->posts->sap_limit_character($post_desc, 280);

        $accounts = $this->posts->get_post_meta($post_id, '_sap_tw_accounts');
        $accounts = !empty($accounts) ? $accounts : $default_accounts;

        //Check Accounts exist
        if (empty($accounts)) {
            $this->sap_common->sap_script_logs('Twitter user not selected.',$user_id);
            $this->flash->setFlash('Twitter user not selected.', 'error','',true);
            return false;
        }

        //Check general settings allow image uploading
        if (empty($disable_image_tweet)) {
            //Twitters Post meta
            $image = $this->posts->get_post_meta($post_id, '_sap_tw_image');

            /** ************
             * Image Priority
             * If metabox image set then take from metabox
             * If metabox image is not set then take from content image
             * If content image is not set then take from settings page
             * ************ */
            $image = empty($image) ? $post->img : $image;
            $image = empty($image) ? $tweet_image : $image;

        }

        //posting logs data
        $posting_logs_data = array();

        if (!empty($accounts)) { // Check all user ids
            foreach ($accounts as $key => $value) {

                //load twitter class
                $tw_consumer_key = isset($twitter_keys[$value]['consumer_key']) ? $twitter_keys[$value]['consumer_key'] : '';
                $tw_consumer_secret = isset($twitter_keys[$value]['consumer_secret']) ? $twitter_keys[$value]['consumer_secret'] : '';
                $tw_auth_token = isset($twitter_keys[$value]['oauth_token']) ? $twitter_keys[$value]['oauth_token'] : '';
                $tw_auth_token_secret = isset($twitter_keys[$value]['oauth_secret']) ? $twitter_keys[$value]['oauth_secret'] : '';

                $twitter = $this->sap_load_twitter($tw_consumer_key, $tw_consumer_secret, $tw_auth_token, $tw_auth_token_secret);

                //check twitter class is loaded or not
                if (!$twitter)
                    return false;

                try {
                    
                    if ($post_link != '') {
                        $posting_logs_data['link'] = $post_link;
                    }

                    //do posting to twitter
                    if (!empty($image)) {

                        $enable_misc_relative_path = $this->settings->get_options('enable_misc_relative_path');

                        if ( $enable_misc_relative_path == 'yes' ) {
                            $image = SAP_APP_PATH.'uploads/' . $image;
                        }else{
                            $image = SAP_IMG_URL . $image;
                        }
                           

                        // build an array of images to send to twitter
                        $upload = $this->twitter->media_upload(array(
                            'media' => $image
                        ));

                        // check if media upload function successfully run
                        if ($upload->httpstatus == 200) {

                            //upload the file to your twitter account
                            $media_ids = $upload->media_id_string;

                            $params = array(
                                'text' => $post_desc,
                                'media' => array( // modifiy code to fix issue with new API since 2023
                                    'media_ids' => (array)$media_ids
                                )
                            );
                        } else {
                            $params = array(
                                'text' => $post_desc
                            );
                        }
                    } else {
                        $params = array(
                            'text' => $post_desc
                        );
                    }
                    $posting_logs_data['image'] = !empty($image) ? $image : '';
                    $posting_logs_data['message'] = $post_body;
                    
                    $result = $this->twitter->tweets($params);
            
                    //check id is set in result data and not empty
                    if( ( isset( $result->id ) && !empty( $result->id ) ) || isset( $result->data->id ) && !empty( $result->data->id ) ) { // modifiy code to fix issue with new API since 2023

                        //User details
                        $posting_logs_user_details = array(
                            'account_id' => isset($result->user->id) ? $result->user->id : '',
                            'display_name' => isset($result->user->name) ? $result->user->name : '',
                            'user_name' => isset($result->user->screen_name) ? $result->user->screen_name : '',
                            'twitter_consumer_key' => $tw_consumer_key,
                            'twitter_consumer_secret' => $tw_consumer_secret,
                            'twitter_oauth_token' => $tw_auth_token,
                            'twitter_oauth_secret' => $tw_auth_token_secret,
                        );
                        
                        $posting_logs_data['account_id'] = isset($result->user->id) ? $result->user->id : '';
                        $posting_logs_data['display_name'] = isset($result->user->name) ? $sap_twitter_accounts_details[0]['name']  : '';
                        
                        //record logs for post posted to twitter
                        $this->flash->setFlash( 'Twitter : Post sucessfully posted on - '.$sap_twitter_accounts_details[0]['name'] , 'success','',true );
                        $this->sap_common->sap_script_logs('Twitter : Post sucessfully posted on - ' . $sap_twitter_accounts_details[0]['name'] , $user_id );
                        $this->sap_common->sap_script_logs('Twitter post data : ' . var_export($posting_logs_data,true),$user_id);
                        //posting flag that posting successfully
                        $postflg = true;
                    }

                    //check error is set
                    if (isset($result->errors) && !empty($result->errors)) {

                        //record logs for twitter posting exception
                        $this->flash->setFlash('Twitter posting exception : ' . $result->errors[0]->code . ' | ' . $result->errors[0]->message, 'error','',true);
                        $this->sap_common->sap_script_logs('Twitter error : ' . $result->errors[0]->code . ' | ' . $result->errors[0]->message, $user_id );
                    }

                    //Store log into DB
                    if ($postflg) {

                        $posting_logs_data['link to post'] = 'https://twitter.com/'.$posting_logs_user_details['user_name'];
                        $this->logs->add_log('twitter', $posting_logs_data, $posting_type, $user_id);
                    }

                    //return $result;
                } catch (Exception $e) {

                    //record logs exception generated
                    $this->flash->setFlash('Twitter posting time out, Please try again', 'error','',true);
                    $this->sap_common->sap_script_logs('Twitter posting time out, Please try again', $user_id );
                    //return false;
                }
            }

            
        }

        //returning post flag
        return $postflg;
    }

    /**
     * Quick Post To Twitter
     * 
     * Handles to Quick Post on Twitter account
     * 
     * @package Social Auto Poster
     * @since 1.0.0
     */
    public function sap_quick_post_to_twitter($post_id) {
        $postflg = false;
        //Get Post details
        $status_meta_array = array();

        $quick_post = $this->quick_posts->get_post($post_id, true);

        $user_id = isset( $quick_post->user_id ) ? $quick_post->user_id : '';
        
        $sap_networks_meta = $this->quick_posts->get_post_meta($post_id, 'sap_networks');

        $sap_twitter_accounts_details = $this->settings->get_user_setting('sap_twitter_accounts_details', $user_id);
        
        $accounts = !empty($sap_networks_meta['tw_accounts']) ? $sap_networks_meta['tw_accounts'] : array();

        // General setting
        $sap_general_options = $this->settings->get_user_setting('sap_general_options',$user_id);

        $link_timestamp = isset($sap_general_options['timestamp_link']) ? "?".time() : '';

        //Get general options;
        $sap_twitter_options = $this->settings->get_user_setting('sap_twitter_options', $user_id);
        $default_accounts = !empty($sap_twitter_options['posts_users']) ? $sap_twitter_options['posts_users'] : '';

        $twitter_keys = !empty($sap_twitter_options['twitter_keys']) ? $sap_twitter_options['twitter_keys'] : array();

        $post_link = strip_tags($quick_post->share_link);

        if(!empty($post_link)) {
            $post_link = $post_link."".$link_timestamp;
        }
        $customlink  = !empty($post_link) ? 'true' : 'false';
        $post_link     = $this->common->sap_script_short_post_link($post_link,$customlink,'tw','twitter', $user_id);

        $post_body = !empty($quick_post->message) ? htmlentities(strip_tags($quick_post->message)) : '';
        $post_body = !empty($post_body) ? html_entity_decode(strip_tags($post_body),ENT_QUOTES) : '';

        $post_desc = $image = '';
        $post_desc .= (!empty($post_link) ) ? $post_link . "\r\n" : '';
        $post_desc .= (!empty($post_body) ) ? $post_body . "\r\n" : '';

        $posting_type = $this->quick_posts->get_post_meta($post_id, '_sap_tw_status');
        // Twitter limit 140 character for tweet
        if (!empty($post_desc))
            $post_desc = $this->posts->sap_limit_character($post_desc, 280);

        $accounts = !empty($accounts) ? $accounts : $default_accounts;
        
        //Check Accounts exist
        if (empty($accounts)) {
            $this->flash->setFlash('Twitter posting users are not selected.', 'error','',true);
            $this->sap_common->sap_script_logs('Twitter posting users are not selected.', $user_id );
            $status_meta_array[] = array(
                "status" => 'error',
                "message" => 'Twitter posting users are not selected.'
            );
            $this->quick_posts->update_post_meta($post_id,"sap_tw_posting_error", $status_meta_array);
            return false;
        }

        if (isset($sap_twitter_options['tweet_image'])) {
            $general_tweet_image = $sap_twitter_options['tweet_image'];
        }

        $image = !empty($quick_post->image) ? $quick_post->image : $general_tweet_image;
        $video = !empty($quick_post->video) ? $quick_post->video : "";

        //posting logs data
        $posting_logs_data = array();

        if (!empty($accounts)) { // Check all user ids
            foreach ($accounts as $key => $value) {

                //load twitter class
                $tw_consumer_key = isset($twitter_keys[$value]['consumer_key']) ? $twitter_keys[$value]['consumer_key'] : '';
                $tw_consumer_secret = isset($twitter_keys[$value]['consumer_secret']) ? $twitter_keys[$value]['consumer_secret'] : '';
                $tw_auth_token = isset($twitter_keys[$value]['oauth_token']) ? $twitter_keys[$value]['oauth_token'] : '';
                $tw_auth_token_secret = isset($twitter_keys[$value]['oauth_secret']) ? $twitter_keys[$value]['oauth_secret'] : '';

                $twitter = $this->sap_load_twitter($tw_consumer_key, $tw_consumer_secret, $tw_auth_token, $tw_auth_token_secret);

                $unknown_key = $tw_consumer_key."|".$tw_consumer_secret."|".$tw_auth_token."|".$tw_auth_token_secret;

                $status_meta_key = ( isset( $sap_twitter_accounts_details[$value] ) && isset( $sap_twitter_accounts_details[$value]['name'] ) ) ? $sap_twitter_accounts_details[$value]['name'] : $unknown_key;

                //check twitter class is loaded or not
                if (!$twitter)
                    return false;

                try {
                    if ($post_link != '') {
                        $posting_logs_data['link'] = $post_link;
                    }
                    //do posting to twitter
                    if (!empty($image) || !empty($video)) {

                        $media_source = "";

                        $enable_misc_relative_path = $this->settings->get_options('enable_misc_relative_path');

                        if( !empty( $video ) ){
                            if ( $enable_misc_relative_path == 'yes' ) {
                                $media_source = SAP_APP_PATH.'uploads/' . $video;
                            }else{
                                $media_source = SAP_IMG_URL . $video;
                            }
                        } else {
                            if ( $enable_misc_relative_path == 'yes' ) {
                                $media_source = SAP_APP_PATH.'uploads/' . $image;
                            }else{
                                $media_source = SAP_IMG_URL . $image;
                            }
                        }

                        $file_data = pathinfo($media_source);
                        if($file_data['extension'] == 'mp4' || $file_data['extension'] == 'mov' || $file_data['extension'] == 'mkv'){
                            
                            $video_path = SAP_APP_PATH.'uploads/'.$video; 
                            $upload = $this->twitter->media_upload(array(
                                "command" => "INIT",
                                "total_bytes" => (int)filesize($video_path),
                                'media_type' => 'video/'.$file_data['extension'],
                            ));

                            $media_id = $upload->media_id_string;
                            if(!empty($media_id)){

                                $fp = fopen($video_path, 'r'); 
                                $segment_id = 0; 
                                while (!feof($fp)) {

                                    $chunk = fread($fp, 1048576); // 1MB per chunk for this sample
                                    $upload = $this->twitter->media_upload(array(
                                        "command"       => "APPEND",
                                        "media_id"      => $media_id,
                                        'media_data'    => base64_encode($chunk),
                                        "segment_index" => $segment_id,
                                    ));
                                    $segment_id++;
                                }                              

                            }

                            $upload = $this->twitter->media_upload(array(
                                "command" => "FINALIZE",
                                "media_id" => $media_id
                            ));

                            if ($upload->httpstatus == 400 ) {
                                $this->flash->setFlash( 'Twitter Video Error : '.$upload->error, 'error','',true );
                                return;
                            }    

                       

                        } else {
                            // build an array of images to send to twitter
                            $upload = $this->twitter->media_upload(array(
                                'media' => $media_source
                            ));
                        }
                        
                  

                        

                        // check if media upload function successfully run
                        if ($upload->httpstatus == 200 || $upload->httpstatus == 201) {

                            //upload the file to your twitter account
                            $media_ids = $upload->media_id_string;

                            $params = array(
                                'text' => $post_desc,
                                'media' => array( // modifiy code to fix issue with new API since 2023
                                    'media_ids' => (array)$media_ids
                                )
                            );
                        } else {
                            $params = array(
                                'text' => $post_desc
                            );
                        }
                    } else {
                        $params = array(
                            'text' => $post_desc
                        );
                    }
                    $posting_logs_data['image'] = !empty($image) ? $image : '';
                    $posting_logs_data['message'] = $post_body;

                    $result = $this->twitter->tweets($params);
                   
                    
          
                    //check id is set in result data and not empty
                    if( ( isset( $result->id ) && !empty( $result->id ) ) || isset( $result->data->id ) && !empty( $result->data->id ) ) { // modifiy code to fix issue with new API since 2023

                        //User details
                        $posting_logs_user_details = array(
                            'account_id' => isset($result->user->id) ? $result->user->id : '',
                            'display_name' => isset($result->user->name) ? $result->user->name : '',
                            'user_name' => isset($result->user->screen_name) ? $result->user->screen_name : '',
                            'twitter_consumer_key' => $tw_consumer_key,
                            'twitter_consumer_secret' => $tw_consumer_secret,
                            'twitter_oauth_token' => $tw_auth_token,
                            'twitter_oauth_secret' => $tw_auth_token_secret,
                        );
                        
                        $posting_logs_data['account_id'] = isset($result->user->id) ? $result->user->id : '';
                        $posting_logs_data['display_name'] = isset($result->user->name) ? $result->user->name : '';
                        
                        //record logs for post posted to twitter
                        $this->flash->setFlash( 'Twitter : Post sucessfully posted on - '.$sap_twitter_accounts_details[0]['name'], 'success','',true );
                        $this->sap_common->sap_script_logs('Twitter : Post sucessfully posted on - ' . $sap_twitter_accounts_details[0]['name'], $user_id );
                        $this->sap_common->sap_script_logs('Twitter post data : ' . var_export($posting_logs_data,true), $user_id);

                        $status_meta_array[$status_meta_key] = array(
                            "status" => 'success'
                        );
                        //posting flag that posting successfully
                        $postflg = true;
                    }

                    //check error is set
                    if (isset($result->errors) && !empty($result->errors)) {
                        //record logs for twitter posting exception

                        $error_message = $result->errors[0]->message;
                        if($result->errors[0]->code == "324"){
                            $error_message = "Twitter Media Upload API does not allow video more than 30 seconds.";
                        }

                        $this->flash->setFlash('Twitter posting exception : ' . $result->errors[0]->code . ' | ' . $error_message, 'error','',true);
                        $this->sap_common->sap_script_logs('Twitter error : ' . $result->errors[0]->code . ' | ' . $error_message, $user_id );
                        $status_meta_array[$status_meta_key] = array(
                            "status" => 'error',
                            "message" => $result->errors[0]->code . ' | ' . $error_message
                        );
                    }

                    if (isset($result->error) && !empty($result->error)) {
                        //record logs for twitter posting exception
                        $this->flash->setFlash('Twitter posting exception : ' . $result->error, 'error','',true);
                        $this->sap_common->sap_script_logs('Twitter posting error : ' . $result->error, $user_id );
                        $status_meta_array[$status_meta_key] = array(
                            "status" => 'error',
                            "message" => $result->error
                        );
                    }

                    

                    //Store log into DB
                    if ($postflg) {

                        $posting_logs_data['link to post'] = 'https://twitter.com/'.$posting_logs_user_details['user_name'];
                        $this->logs->add_log('twitter', $posting_logs_data, $posting_type, $user_id);
                        $this->quick_posts->update_post_meta($post_id,"sap_tw_link_to_post", $posting_logs_data['link to post']);
                    }

                    //return $result;
                } catch (Exception $e) {

                    //record logs exception generated
                    $this->flash->setFlash('Twitter posting time out, Please try again', 'error','',true);
                    $this->sap_common->sap_script_logs('Twitter posting time out, Please try again.', $user_id );
                    $status_meta_array[$status_meta_key] = array(
                        "status" => 'error',
                        "message" => 'Twitter posting time out, Please try again'
                    );
                   
                    //return false;
                }
            }

            $this->quick_posts->update_post_meta($post_id,"sap_tw_posting_error", $status_meta_array);

        }

        //returning post flag
        return $postflg;
    }

}
