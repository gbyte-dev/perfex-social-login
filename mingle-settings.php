<?php

/* Check the absolute path to the Social Auto Poster directory. */
if ( !defined( 'SAP_APP_PATH' ) ) {
    // If SAP_APP_PATH constant is not defined, perform some action, show an error, or exit the script
    // Or exit the script if required
    exit();
}

// Include Message Class file
include ( SAP_APP_PATH . 'Lib' . DS . 'Message' . DS . 'Flash.php' );

//Include Database class
include( dirname(__FILE__) . DS . 'classes' . DS . 'database.php');
global $sap_db_connect;
$sap_db_connect = new Sap_Database();

// Include Common Class file
include ( SAP_APP_PATH . DS . 'classes' . DS . 'Common.php' );
global $sap_common;
$sap_common = new Common();


// Include User functions file
include ( SAP_APP_PATH . DS . 'functions' . DS . 'UserFunctions.php' );

// Include Common Class file
include ( SAP_APP_PATH . DS . 'classes' . DS . 'Settings.php' );
global $sap_global_settings;
$sap_global_settings = new SAP_Settings();

// Include Common Class file
include ( SAP_APP_PATH . DS . 'classes' . DS . 'Email.php' );

// Include Updates class
include( dirname(__FILE__) . DS . 'classes' . DS . 'Updates.php');

// Include Common Class file
require_once ( CLASS_PATH . 'Logs.php');

require_once ( SAP_APP_PATH . 'cron.php');

// Include all constant
include ( SAP_APP_PATH . 'config' . DS . 'constant.php' );
        

// Check if we're in SAP_DEBUG mode.
if ( SAP_DEBUG ) {
    error_reporting( E_ALL );

    if ( SAP_DEBUG_DISPLAY )
        ini_set( 'display_errors', 1 );
    elseif ( null !== SAP_DEBUG_DISPLAY )
        ini_set( 'display_errors', 0 );   
} else {
    error_reporting( E_CORE_ERROR | E_CORE_WARNING | E_COMPILE_ERROR | E_ERROR | E_WARNING | E_PARSE | E_USER_ERROR | E_USER_WARNING | E_RECOVERABLE_ERROR );
}

//Include Routes file
include ( SAP_APP_PATH . 'config' . DS . 'routes.php' );

define('PRORATION_CREDITS', FALSE);