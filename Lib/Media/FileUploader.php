<?php

/*
 * file-uploader-class.php
 * description: uploads a file in the specified directory
 * @since v1.0.0
 * @todo : Change upload dir permission
 */

if (!defined('APACHE_MIME_TYPES_URL')) {
 define('APACHE_MIME_TYPES_URL', 'http://svn.apache.org/repos/asf/httpd/httpd/trunk/docs/conf/mime.types');
}

class FileUploader {

    public $_config = array(
        'max_filesize' => 20000000, // 20MB,
        'max_file_width' => 1000, // 1000 pixels
        'max_file_height' => 1000, // 1000 pixels
        'min_file_width' => 0, //0 pixels,
        'min_file_height' => 0, // 0 pixels
        //Directory name to upload file
        // FTP requires full directory name (e.g. home/path/to/project/directory/upload/directory)
        'file_upload_dir' => 'uploads',
        'file_change_name' => 'yes',
        'file_upload_protocol' => 'http', // OR FTP
        'ftp_username' => '',
        'ftp_password' => '',
        'ftp_domain' => '',
        'file_allowed_file_extension' => array('.png', '.jpg', '.jpeg', '.gif', '.jpe','.mp4','.mkv','.mov'),
        'file_allowed_mime_types' => array('image/jpeg', 'image/pjpeg', 'image/jpeg', 'image/pjpeg', 'image/jpeg', 'image/pjpeg', 'image/png', 'image/gif','video/mp4','video/mkv','video/quicktime','video/x-matroska'),
        'return_type' => 'name',
    );

    /**
     * Uploaded file name
     */
    private $_file_name;

    /**
     * File Original name
     */
    private $_file_original_name = "";

    /**
     * File temporary name
     */
    private $_file_temp_name = "";

    /**
     * Form field name for file
     */
    private $_file_filed_name = 'img';

    /**
     * File Type
     */
    private $_file_type = '';

    /**
     * File extension
     */
    private $_file_extension = '';

    /**
     * Uploaded file size
     */
    private $_file_size;

    /**
     * Uploaded file size in kilobyte
     */
    private $_file_size_in_kbs;

    /**
     * Uploaded files contains errors?
     */
    private $_file_error = 0;

    /**
     * Uploaded file's mime type
     */
    private $_file_mime_type;

    /**
     * Uploaded file's type
     * Determine if file is image,video,pdf or docuement etc
     */
    private $_file_content_type;

    /**
     * Naturally generated file name
     */
    private $_file_natural_name = "";

    /**
     * File upload status
     */
    private $_file_upload_successful = 1;

    /**
     * File upload path
     */
    private $_file_upload_path;
    private $_upload_dir_permission = 0777;

    /**
     * No Files were uploaded
     */
    private $_UPLOAD_NO_FILES = 0;
    /*
     * Total folders created
     */
    private $_UPLOAD_NO_FOLDER = 1;

    /**
     * If File type was invalid
     */
    private $_UPLOAD_FILETYPE_INVALID = 2;
    /*
     * If File mime was invalid
     */
    private $_UPLOAD_MIMETYPE_INVALID = 3;

    /**
     * If file already exists with similar name
     * which will be highly likely since we are not using same file name its original name
     * 
     */
    private $_UPLOAD_FILENAME_EXISTS = 4;
    private $_UPLOAD_FILENAME_INVALID = 5;
    private $_UPLOAD_FILESIZE_LARGE = 6;
    private $_UPLOAD_FILEDIMENSIONS_LARGE = 7;
    private $_UPLOAD_FILEDIMENSIONS_SMALL = 8;
    private $_UPLOAD_FILESIZE_LARGE_INI = 9;
    private $_UPLOAD_FILESIZE_LARGE_FORM = 10;
    private $_UPLOAD_PARTIAL_ONLY = 11;
    private $_UPLOAD_NO_TEMPDIR = 12;
    private $_UPLOAD_NO_WRITE = 13;
    private $_UPLOAD_EXTENSION_STOP = 14;
    private $_UPLOAD_FILEINFO_ERROR = 15;
    private $_UPLOAD_CURL_ERROR = 16;
    private $_UPLOAD_ERROR = 17;
    private $_UPLOAD_SUCCESS = 18;
    private $_UPLOAD_SUCCESS_OVERWRITTEN = 19;

    public function __construct(array $config) {
        if (!empty($config)) {
            foreach ($config as $key => $val) {
                $this->_config[$key] = $val;
            }
        }
    }

    public function uploadFile($_file_filed_name = FALSE) {
    	
        if(!empty($_FILES['video']['name'])){
            $this->_file_filed_name = 'video';
        } else {
            $this->_file_filed_name = !empty( $_file_filed_name ) ? $_file_filed_name : $this->_file_filed_name ;
        }

        

        $this->_file_name = strip_tags($_FILES[$this->_file_filed_name]['name']);
        $this->_file_temp_name = $_FILES[$this->_file_filed_name]['tmp_name'];
        $this->_file_size = $_FILES[$this->_file_filed_name]['size'];
        $this->_file_error = $_FILES[$this->_file_filed_name]['error'];
        $this->_file_type = $_FILES[$this->_file_filed_name]['type'];

    

        // If files is not uploaded
        if ($this->_file_name == '' || $this->_file_temp_name == '' || empty($this->_file_name) && $this->_file_upload_successful == 1) {
           
            $this->_file_upload_successful = 0;
            return $this->_UPLOAD_NO_FILES;
        }

        //Keep original file name just in case
        $this->_file_original_name = $this->_file_name;

        //Convert file name to lower case
        $this->_file_name = strtolower($this->_file_name);
        // Get file extension
        $this->_file_extension = $this->_determine_file_extension();

       

        // Check if uploaded file's extension is valid
        if (!in_array($this->_file_extension, $this->_config['file_allowed_file_extension']) && $this->_file_upload_successful == 1) {
          
            $this->_file_upload_successful = 0;
            echo $this->_UPLOAD_FILETYPE_INVALID;
            return $this->_UPLOAD_FILETYPE_INVALID;
        }
        // Get file size in kilobytes
        $this->_file_size_in_kbs = $this->_determine_file_size();

        // Check if uploaded file's size does not cross maximum upload size
        if ($this->_file_size_in_kbs > $this->_config['max_filesize'] && $this->_file_upload_successful == 1) {
            
          
 
            $this->_file_upload_successful = 0;
            return $this->_UPLOAD_FILESIZE_LARGE;
        }
        //Get File mime type
        $this->_file_mime_type = $this->_file_type;
        //Determine file type
        $this->_determine_file_type();

     
        // Check if uploaded file's mime types are valid
        if (!in_array($this->_file_mime_type, $this->_config['file_allowed_mime_types']) && $this->_file_upload_successful == 1) {
           
           
            $this->_file_upload_successful = 0;
            return $this->_UPLOAD_MIMETYPE_INVALID;
        }
 
        //Begin upload process
        if ($this->_config['file_upload_protocol'] == 'http') {
            return $this->_upload_via_http();
        }
    }

    private function _upload_via_http() {
        
       

        if ($this->_file_upload_successful == 1) {
            $this->_file_upload_path = $this->_determine_fileupload_path();
            //Move uploaded file to our storage
            if (move_uploaded_file($this->_file_temp_name, $this->_file_upload_path) && $this->_file_error == 0) {
                
                //check real filetype by using finfo
                if (function_exists('finfo_open') && !empty($this->_config['file_allowed_mime_types'])) {
                    //start finfo connection
                    $finfo = finfo_open(FILEINFO_MIME);
                    if (!$finfo) {
                        //there was an error with finfo so we will detele the file as it may be harmfull to system
                        unlink($this->_file_upload_path);
                        return $this->_UPLOAD_FILEINFO_ERROR;
                    }
                    //Get real abolute path to check the file
                    $abolute_upload_dir = realpath($this->_config['file_upload_dir']);
                    $get_mimeType = finfo_file($finfo, $abolute_upload_dir . DIRECTORY_SEPARATOR . $this->_file_natural_name);
                    $get_mimeType = explode(';', $get_mimeType);

                    
                    
                    //close fileinfo connection
                    finfo_close($finfo);
                    if (!in_array($get_mimeType[0], $this->_config['file_allowed_mime_types'])) {
                        //suspicious file. Delete from server
                        unlink($this->_file_upload_path);
                        return $this->_UPLOAD_MIMETYPE_INVALID;
                    } else {
                    	//Need Url
                    	if( $this->_config['return_type'] == 'url' ){
                    		return SAP_SITE_URL.'/'.$this->_file_upload_path;
                    		
                    	}elseif ( $this->_config['return_type'] == 'path'){
                    		return $this->_file_upload_path;	
                    	}
                        
                    	return $this->_file_natural_name;
                    }
                }
            } else {
                //return file upload error
              
                return $this->_return_file_upload_error();
            }
        } else {
            return $this->_UPLOAD_ERROR;
        }
    }

    /**
     * Determine uploaded file extension
     * 
     * @return string
     * @since v1.0.0
     */
    private function _determine_file_extension() {
        return '.' . strtolower(pathinfo($this->_file_name, PATHINFO_EXTENSION));
    }

    /**
     * Determine uploaded file size
     * @return int
     * @since v1.0.0
     */
    private function _determine_file_size() {
        return $this->_file_size / 1000;
    }

    private function _determine_fileupload_path() {
        if ($this->_config['file_upload_protocol'] == 'http') {
            if (!is_dir($this->_config['file_upload_dir'])) {
                mkdir($this->_config['file_upload_dir'], 0777);
            }
            $this->_file_natural_name = $this->_generate_natural_file_name();
            return $this->_config['file_upload_dir'] . DIRECTORY_SEPARATOR . $this->_file_natural_name;
        }
    }

    private function _generate_natural_file_name() {
        $current_month = date('m');
        $filename = str_replace(' ', '-', pathinfo($this->_file_name, PATHINFO_FILENAME));
        $name = $filename . '-' . rand(1, 2000) . '-' . $current_month . '-' . $this->_determine_file_width() . 'X' . $this->_determine_file_height() . $this->_determine_file_extension();
        return $name;
    }

    private function _determine_file_mimetype() {
        return mime_content_type($this->_file_temp_name);
    }

    private function _determine_file_type() {
        if (substr($this->_file_mime_type, 0, 5) == 'image') {
            $this->_file_content_type = 'image';
        }
    }

    private function _determine_file_height() {
        if ($this->_file_content_type == 'image' && is_uploaded_file($this->_file_temp_name)) {
            return getimagesize($this->_file_temp_name)[1];
        }
    }

    private function _determine_file_width() {
        if ($this->_file_content_type == 'image' && is_uploaded_file($this->_file_temp_name)) {
            return getimagesize($this->_file_temp_name)[0];
        }
    }

    private function _get_all_mimetypes() {
        $s = array();
        foreach (@explode("\n", @file_get_contents(APACHE_MIME_TYPES_URL))as $x) {
            if (isset($x[0]) && $x[0] !== '#' && preg_match_all('#([^\s]+)#', $x, $out) && isset($out[1]) && ($c = count($out[1])) > 1) {
                for ($i = 1; $i < $c; $i++) {
                    $s[] = '&nbsp;&nbsp;&nbsp;\'' . $out[1][$i] . '\' => \'' . $out[1][0] . '\'';
                }
            }
        }
        return @sort($s) ? '$mime_types = array(<br />' . implode($s, ',<br />') . '<br />);' : false;
    }

    private function _return_file_upload_error() {
        switch ($this->_file_error) {
            case 1:
                return $this->_UPLOAD_FILESIZE_LARGE_INI;
                break;
            case 2:
                return $this->_UPLOAD_FILESIZE_LARGE_FORM;
                break;
            case 3:
                return $this->_UPLOAD_PARTIAL_ONLY;
                break;
            case 4:
                return $this->_UPLOAD_NO_FILES;
                break;
            case 5:
                return $this->_UPLOAD_NO_TEMPDIR;
                break;
            case 6:
                return $this->_UPLOAD_NO_WRITE;
                break;
            case 7:
                return $this->_UPLOAD_EXTENSION_STOP;
                break;
            default :
                return $this->_UPLOAD_ERROR;
                break;
        }
    }

}
