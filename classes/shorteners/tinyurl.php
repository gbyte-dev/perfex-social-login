<?php

/* Check the absolute path to the Social Auto Poster directory. */
if ( !defined( 'SAP_APP_PATH' ) ) {
    // If SAP_APP_PATH constant is not defined, perform some action, show an error, or exit the script
    // Or exit the script if required
    exit();
}

/**
 * SAP Tiny URL Class
 * 
 * @package Social Auto Poster
 * @since 1.0.0
 */

class SAP_Tiny_Url {

    public $name;
    public $url;

    public function __construct() {
        $this->name = 'tinyurl';
    }

    /**
     * Tiny URL API
     * 
     * @package Social Auto Poster
     * @since 1.0.0
     */
    public function shorten($pageurl) {

        $new_tiny_url = '';
        $ch = curl_init();
        $timeout = 5;
        curl_setopt($ch, CURLOPT_URL, 'http://tinyurl.com/api-create.php?url=' . $pageurl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
        $new_tiny_url = curl_exec($ch);
        curl_close($ch);
        if ($new_tiny_url) {
            return $new_tiny_url;
        } else {
            return $pageurl;
        }
    }

}
