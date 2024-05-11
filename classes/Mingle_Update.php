<?php

/* Check the absolute path to the Social Auto Poster directory. */
if ( !defined( 'SAP_APP_PATH' ) ) {
    // If SAP_APP_PATH constant is not defined, perform some action, show an error, or exit the script
    // Or exit the script if required
    exit();
}

/**
 * Script Update Class
 * 
 * Responsible for all updates and license check
 *
 * @package Social Auto Poster
 * @since 1.0.0
 */
require_once ( CLASS_PATH . 'Settings.php');


class SAP_Mingle_Update {

    //Set Database variable
    private $db;
    private $settings;
    public $flash;
    public $common;
    public $sap_common, $tmp_dir, $root_dir;
    public $updated_file_name;

    public function __construct() {

        global $sap_db_connect, $sap_common;
        
        $this->db = $sap_db_connect;
        $this->flash = new Flash();
        $this->common = new Common();
        $this->settings = new SAP_Settings();
        $this->tmp_dir = dirname(dirname(__FILE__)) . DS . 'tmp-sap-upgrade';
        $this->root_dir = dirname(dirname(__FILE__));
        $this->sap_common = $sap_common;
    }

    /**
     * Listing page of Posts
     * 
     * @package Social Auto Poster
     * @since 1.0.0
     */
    public function index() {

        //Includes Html files for Posts list
        if ( !sap_current_user_can('mingle-update') ) {

            if (isset($_SESSION['user_details']) && !empty($_SESSION['user_details'])) {

                $template_path = $this->common->get_template_path('Update' . DS . 'index.php' );
                include_once( $template_path );
            } 
        }
        else {
            $this->common->redirect('login');
        }
    }

    /**
     * Check Update Available
     * 
     * @package Social Auto Poster
     * @since 1.0.0
     */
    public function check_update() {

        $ch = curl_init();
        $headers = array(
            'Accept: application/json',
            'Content-Type: application/json',
        );
        $get_options = $this->settings->get_options( 'sap_license_data' );


        $URL = SAP_UPDATER_LICENSE_URL . '?type=check_update&access_token='.$get_options['access_token'];

        curl_setopt($ch, CURLOPT_URL, $URL);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);

        //$response = curl_exec($ch);
        $response = json_decode( curl_exec( $ch ), true );
        $pattern = '/(?<=-)\d+\.\d+\.\d+(?=.zip)/';
        preg_match($pattern, $response['download_url'], $matches);

        $version = $matches[0];

        //Return the latest version data
        return $version;
    }



    /**
     * Version Updating
     * 
     * @package Social Auto Poster
     * @since 1.0.0
     */
    public function version_updating() {

        $response = array();

        $license_data = $this->get_license_data();

        if (empty($license_data['license_key']) && empty($license_data['license_email'])) {
            $response['error'] = $this->sap_common->lang('update_email_key_error');
            echo json_encode($response);
            exit;
        }

        if (empty($license_data['license_email'])) {
            $response['error'] = $this->sap_common->lang('update_email_address_error');
        }

        if (empty($license_data['license_key'])) {
            $response['error'] = $this->sap_common->lang('update_key_error');
        }

        if (empty($license_data['access_token'])) {
            $response['error'] = $this->sap_common->lang('update_token_error');
        }

        //If error return it here
        if (!empty($response['error'])) {
            echo json_encode($response);
            exit;
        }

        $license_data = $this->get_license_data();


        $response = $this->updating_process($license_data);      

        if (!empty($response['download_url'])) {
            //Temp Close
            $newUpdate = file_get_contents($response['download_url']);

            if (!is_dir($this->tmp_dir))
                mkdir($this->tmp_dir);
            
            //Temp Close
            $download_url = $response['download_url'];
            $filename = urldecode(parse_url($download_url, PHP_URL_QUERY));
            preg_match('/filename=([^&]+)/', $filename, $matches);
            $filename = $matches[1];

            //Temp Close
            $this->updated_file_name = $filename;
            
            //Temp Close
            $dlHandler = fopen($this->tmp_dir . '/' . $this->updated_file_name, 'w');

            if (!fwrite($dlHandler, $newUpdate)) {
                echo json_encode(array('error' => $this->sap_common->lang('update_not_save')));
                exit;
            }
            
            fclose($dlHandler);
            
            echo json_encode(array('success' => $this->sap_common->lang('update_version_success'), 'filename' => $this->updated_file_name ));
            exit;
        } elseif ( ! empty($response['error'])) {
            echo json_encode(array('error' => $response['description']));
            exit;
        } elseif (!empty($response->error)) {
            echo json_encode($response);
            exit;
        } else {
            echo json_encode(array('error' => $this->sap_common->lang('update_fail')));
            exit;
        }
    }

    /**
     * Compress Version
     * 
     * @package Social Auto Poster
     * @since 1.0.0
     */
    public function version_compress() {

        //Temp Close
        $backup = $this->tmp_dir .'/'. $_POST['filename'];

        $zip1 = new ZipArchive();
        $result = $zip1->open($backup);

        if ($zip1->open($backup) === TRUE) {
            for ($i = 0; $i < $zip1->numFiles; $i++) {
                $filename = $zip1->getNameIndex($i);
                $get_latest_version = $_SESSION['Update_version'];
                $temp_inner_zip ='mingle-sap-php-script/mingle-v' . $get_latest_version . '.zip';
                // Check if the second zip file exists in the first zip file
                if ($zip1->locateName($temp_inner_zip) !== FALSE) {

                    // Extract the second zip file to the parent folder
                    $zip1->extractTo($this->tmp_dir);
                    $zip1->close();

                    $zip2 = new ZipArchive();
                    $results = $zip2->open($this->tmp_dir.'mingle-sap-php-script/mingle-v' . $get_latest_version . '.zip');
                   
                    if ($zip2->open($this->tmp_dir.'/mingle-sap-php-script/mingle-v' . $get_latest_version . '.zip') === TRUE) {
                        $entries = array();
                        for ($idx = 0; $idx < $zip2->numFiles; $idx++) {
                            $filename = $zip2->getNameIndex($idx);
                            if ( strpos($filename, 'mingle/install/') === 0  || strpos($filename, 'mingle/mingle-config.php') === 0 || strpos($filename, 'mingle/uploads/') === 0 || strpos($filename, 'mingle/mingle-script-logs/') === 0 || strpos($filename, 'mingle/.htaccess') === 0 ) {
                                continue;
                            }
                            $entries[] = $filename;
                        }
                        $zip2->extractTo($this->tmp_dir, $entries);
                        
                    } else {
                        echo json_encode(array('error' => 'failed_to_find_inner_main_source_file'));
                        exit;  
                    }
                } else{
                    echo json_encode(array('error' => 'failed_to_find_the_main_source_file_from_source_zip'));
                    exit;
                }
            }
        } else {
            echo json_encode(array('error' => 'failed_to_open_the_zip_file'));
            exit;
        }

        // Usage example
        $sourceFolder = $this->tmp_dir.'/mingle';
        $destinationFolder = $this->root_dir;
        $get_copy_folder_success = $this->copyFolder($sourceFolder, $destinationFolder);
        echo json_encode(array('success' => $this->sap_common->lang('update_script_up_to_date')));
        if (isset($_SESSION['Update_version']) && !empty($_SESSION['Update_version'])) {
            $this->settings->update_options('sap_version', $_SESSION['Update_version']);
            unset($_SESSION['Update_version']);
        }
    }

    public function copyFolder($source, $destination) {
        if (is_dir($source)) {
            // Create the destination directory if it doesn't exist
            if (!is_dir($destination)) {
                mkdir($destination, 0777, true);
                touch($destination);
            }
    
            // Get a list of all the files and directories in the source directory
            $files = glob($source . '/*');
    
            // Loop through the files and directories
            foreach ($files as $file) {
                // Get the full path to the file or directory
                $srcFile = $file;
                $destFile = $destination . '/' . basename($file);
    
                // Recursively copy sub-directories
                if (is_dir($srcFile)) {
                    $this->copyFolder($srcFile, $destFile);
                } else {
                    // Copy individual files
                    $srcFile = fopen($srcFile, 'r');
                    $destFile = fopen($destFile, 'w');
                    stream_copy_to_stream($srcFile, $destFile);
                    fclose($srcFile);
                    fclose($destFile);
                }
            }
        } else {
            return false;
        }
    
        return true;
    }

    /**
     * Version Compress
     * 
     * @package Social Auto Poster
     * @since 1.0.0
     */
    public function version_compress1() {

        $zipHandle = zip_open($this->tmp_dir . '/mingle.zip');

        while ($file = zip_read($zipHandle)) {

            $thisFileName = zip_entry_name($file);
            $thisFileDir = dirname($thisFileName);

            if ($thisFileName == 'mingle/mingle-config.php' || $thisFileDir == 'mingle/uploads') {
                continue;
            }

            //Continue if its not a file
            if (substr($thisFileName, -1, 1) == '/')
                continue;

            $dir_root = "$this->root_dir/$thisFileDir";

            //Make the directory if we need to...
            if (!is_dir($dir_root) && !is_file($dir_root)) {
                @mkdir($dir_root);
            }

            //Overwrite the file
            if (!is_dir($this->root_dir . '/' . $thisFileName)) {

                $contents = zip_entry_read($file, zip_entry_filesize($file));
                $contents = str_replace("\r\n", "\n", $contents);
                $updateThis = '';

                //If we need to run commands, then do it.
                if ($thisFileName == 'upgrade.php') {

                    $upgradeExec = fopen('upgrade.php', 'w');
                    fwrite($upgradeExec, $contents);
                    fclose($upgradeExec);
                    include ('upgrade.php');
                    unlink('upgrade.php');
                    echo' EXECUTED</li>';
                } else {

                    $updateThis = @fopen($this->root_dir . '/' . $thisFileName, 'w');
                    @fwrite($updateThis, $contents);
                    @fclose($updateThis);
                    unset($contents);
                }
            }
        }
        echo json_encode(array('success' => $this->sap_common->lang('update_script_up_to_date')));
    }


    /**
     * Update Process
     * 
     * @package Social Auto Poster
     * @since 1.0.0
     */
    protected function updating_process($license_data) {

        $ch = curl_init();
        $headers = array(
            'Accept: application/json',
            'Content-Type: application/json',
        );
        $get_options = $this->settings->get_options( 'sap_license_data' );


        $URL = SAP_UPDATER_LICENSE_URL . '?type=download_update&access_token='.$get_options['access_token'];

        curl_setopt($ch, CURLOPT_URL, $URL);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);

        //$response = json_decode( curl_exec($ch) );
        $response = json_decode( curl_exec( $ch ), true );
        return $response;
    }

    /**
     * Save posts to database
     * 
     * @todo : Set error message
     * @since v1.0.0
     */
    public function save_process() {

        $get_options = array();
        
        //Check form submit request
        if( ( isset( $_POST['action']) && ! empty( $_POST['sap_license_key'] ) && ! empty( $_POST['sap_license_email'] ) ) || ( isset( $_POST['sap_register_access_token'] ) && ! empty( $_POST['sap_access_token'] ) ) || ( isset( $_POST['sap_deregister_access_token'] ) ) ) {

            $sap_license_key = $_POST['sap_license_key'];
            $sap_license_email = $_POST['sap_license_email'];
            $sap_license_activation = $_POST['action'];

            if(isset($_POST['sap_register_license'])){
                
                $status = false;

                if ( $sap_license_activation == 'Activate License' ) {
                    $data = $this->wpw_auto_poster_render_activation_settings( $sap_license_key, $sap_license_email, $sap_license_activation );
                    
                    if ( isset( $data['status'] ) && true == $data['status'] ) {
                        $status = true;
                        $update_options = $this->settings->update_options('sap_license_data', array('license_key' => $sap_license_key, 'license_email' => $sap_license_email, 'status' => $status));
                        $final_activation_code =  base64_encode( $sap_license_key. '%' . $sap_license_email );
                        $this->settings->update_options('sap_license_activated', $final_activation_code);
                    }
                }

                $status = $data["status"];
                $status_msg = $data["msg"];
                if ( $status == 'true' ) {
                    $this->flash->setFlash( $status_msg = $data["msg"], 'success');
                } else {
                    $this->flash->setFlash( $status_msg = $data["msg"], 'error');
                }
            } else if(isset($_POST['sap_deregister_license'])) {
                $sap_license_activation = $_POST['action'];

                if ( $sap_license_activation == 'Deactivate License' ) {

                    $data = $this->wpw_auto_poster_render_activation_settings( $sap_license_key, $sap_license_email, $sap_license_activation );
                    $delete_options = $this->settings->delete_options('sap_license_data');
                    $this->settings->delete_options('sap_license_activated');
                    $this->settings->delete_options('sap_license_activated', $final_activation_code);
                }
                $status = $data["status"];
                $status_msg = $data["msg"];
                if ( $status == 'true' ) {
                    $this->flash->setFlash( $status_msg = $data["msg"], 'success');
                }
                
            } else if( isset( $_POST['sap_register_access_token'] ) && ! empty( $_POST['sap_access_token'] ) && $_POST['access_token_action'] == 'Activate Access Token' ) {

                $sap_access_token = $_POST['sap_access_token'];
                $access_token_action = $_POST['access_token_action'];
                $get_license_data = $this->settings->get_options( 'sap_license_data' );
                $get_license_key = "";
                $get_license_email = "";
                if( ! empty( $get_license_data ) ) {
                    $get_license_key = $get_license_data['license_key'];
                    $get_license_email = $get_license_data['license_email'];
                }

                $get_license_data['access_token'] = $sap_access_token;
                $get_license_data['access_token_action'] = $access_token_action;
                $data = $this->wpw_auto_poster_render_activation_settings( $get_license_key, $get_license_email, $sap_license_activation = true, $sap_access_token, $access_token_action );
                
                if( ! empty( $data['download_url'] ) ){
                    $update_options = $this->settings->update_options('sap_license_data', $get_license_data );
                } else if( empty( $data['download_url'] ) ) {
                    $this->flash->setFlash($this->sap_common->lang('invalid_access_token'), 'error');
                }
                

            } else if( isset( $_POST['sap_deregister_access_token'] ) && $_POST['access_token_action'] == 'Deactivate Access Token' ) {

                $get_options = $this->settings->get_options( 'sap_license_data' );
                unset( $get_options['access_token'] );
                unset( $get_options['access_token_action'] );
                $update_options = $this->settings->update_options('sap_license_data', $get_options );

            }
        }

        $this->common->redirect('mingle_update');
        exit();
    }
	public function wpw_auto_poster_render_activation_settings( $sap_license_key, $sap_license_email, $sap_license_activation = null, $sap_access_token = null, $access_token_action = null ) {
		$activation_code = $sap_license_key;
		$email_address   = $sap_license_email;
		$url             = SAP_UPDATER_LICENSE_URL;
		$curl            = curl_init();
        if( ! empty( $sap_access_token ) ) {
            $fields          = array(
                'email'           => $email_address,
                'site_url'        => SAP_SITE_URL,
                'activation_code' => $activation_code,
                'activation'      => $sap_license_activation,
                'access_token'    => $sap_access_token,
                'access_token_action'    => $access_token_action,
                'item_id'         => 29531150,
            );
        } else {
            $fields          = array(
                'email'           => $email_address,
                'site_url'        => SAP_SITE_URL,
                'activation_code' => $activation_code,
                'activation'      => $sap_license_activation,
                'item_id'         => 29531150,
            );
        }
		$fields_string   = http_build_query( $fields );
		curl_setopt( $curl, CURLOPT_URL, $url );
		curl_setopt( $curl, CURLOPT_RETURNTRANSFER, true );
		curl_setopt( $curl, CURLOPT_HEADER, false );
		curl_setopt( $curl, CURLOPT_POST, true );
		curl_setopt( $curl, CURLOPT_POSTFIELDS, $fields_string );
		curl_setopt( $curl, CURLOPT_RETURNTRANSFER, 1 );
		
        $data = json_decode( curl_exec( $curl ), true );

        return $data ;

	}


    /**
     * License Data
     * 
     * @package Social Auto Poster
     * @since 1.0.0
     */
    public function get_license_data() {
        $license_options = $this->settings->get_options('sap_license_data');
        if (!empty($license_options)) {
            return $license_options;
        }
        return array();
    }

    /**
     * Delete Files
     * 
     * @package Social Auto Poster
     * @since 1.0.0
     */
    public function delete_files($target) {
        if (is_dir($target)) {

            $files = glob($target . '*', GLOB_MARK); //GLOB_MARK adds a slash to directories returned

            foreach ($files as $file) {
                $this->delete_files($file);
            }

            if (is_dir($target)) {
                rmdir($target);
            }
        } elseif (is_file($target)) {
            unlink($target);
        }
    }
}