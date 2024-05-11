<?php
if( file_exists(dirname(__FILE__). DIRECTORY_SEPARATOR .'mingle-config.php')){
	include_once dirname(__FILE__). DIRECTORY_SEPARATOR .'mingle-config.php';
}
else{
	header('Location:'. $_SERVER['SCRIPT_URI'].'install');
	exit;
}
