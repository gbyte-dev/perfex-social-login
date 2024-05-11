<?php

/* Check the absolute path to the Social Auto Poster directory. */
if ( !defined( 'SAP_APP_PATH' ) ) {
    // If SAP_APP_PATH constant is not defined, perform some action, show an error, or exit the script
    // Or exit the script if required
    exit();
}

/**
 * SAP Shorte Class
 * 
 * @package Social Auto Poster
 * @since 1.0.0
 */
class SAP_Shorte_Url {

    public $name;
    public $url;

    function __construct() {
        $this->name = 'shortest';
    }

    /**
     * Shorten API 
     * 
     * @package Social Auto Poster
     * @since 1.0.0
     */
    function shorten($api_token, $pageurl) {

        $curl_url = "https://api.shorte.st/s/" . $api_token . "/" . $pageurl;
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $curl_url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $result = curl_exec($ch);
        curl_close($ch);
        $shortest_url_data = json_decode($result);

        if (isset($shortest_url_data->shortenedUrl) && $shortest_url_data->status == "ok") {

            return $shortest_url_data->shortenedUrl;
        }

        return $pageurl;
    }

}
