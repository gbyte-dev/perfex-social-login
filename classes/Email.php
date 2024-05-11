<?php

/* Check the absolute path to the Social Auto Poster directory. */
if ( !defined( 'SAP_APP_PATH' ) ) {
    // If SAP_APP_PATH constant is not defined, perform some action, show an error, or exit the script
    // Or exit the script if required
    exit();
}

// Import PHPMailer classes into the global namespace
// These must be at the top of your script, not inside a function
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

/**
 * Email class
 */
class Sap_Email{

	public $mailer;

	// Constructor
	public function __construct() {

		// Get class
		$this->settings = new SAP_Settings();

		// Init SMTP
		$this->init_smtp();
	}

	/**
	 * Init SMTP
	 */
	public function init_smtp() {

		// Get SAP options which stored
		$smtp_setting = $this->settings->get_options('sap_smtp_setting');

		$this->smtp = isset( $smtp_setting['enable'] ) ? $smtp_setting['enable'] : 'no';
		
		if ( 'yes' == $this->smtp ) {

			$from_email	= isset( $smtp_setting['from_email'] ) ? $smtp_setting['from_email'] : '';
			$from_name	= isset( $smtp_setting['from_name'] ) ? $smtp_setting['from_name'] : '';
			$host		= isset( $smtp_setting['host'] ) ? $smtp_setting['host'] : '';
			$port		= isset( $smtp_setting['port'] ) ? $smtp_setting['port'] : '';
			$username	= isset( $smtp_setting['username'] ) ? $smtp_setting['username'] : '';
			$password	= isset( $smtp_setting['password'] ) ? $smtp_setting['password'] : '';
			// $enc_type	= isset( $smtp_setting['enc_type'] ) ? $smtp_setting['enc_type'] : 'None';

			// Include mailer tasks
			require_once( LIB_PATH . '/PHPMailer/autoload.php' );

			// Instantiation and passing `true` enables exceptions
			$this->mailer = new PHPMailer(true);
			$this->mailer->isSMTP();

			//Server settings
			$this->mailer->SMTPDebug	= SMTP::DEBUG_OFF;		// SMTP::DEBUG_SERVER Enable verbose debug output

			$this->mailer->SMTPAuth		= true;						// Enable SMTP authentication
			$this->mailer->Host			= $host;					// Set the SMTP server to send through
			$this->mailer->Username		= $username;				// SMTP username
			$this->mailer->Password		= $password;				// SMTP password
			$this->mailer->Port			= $port; 					// SMTP port
			$this->mailer->SMTPSecure	= PHPMailer::ENCRYPTION_SMTPS; // Enable TLS encryption;

			// Set HTML content type true
			$this->mailer->isHTML(true);

			// Set from email address
			$this->mailer->setFrom( $from_email, $from_name );
		}
	}

	/**
	 * Get headers
	 */
	public function get_headers() {
		$headers = '';
		
		// Set content-type when sending HTML email
		$headers .= "MIME-Version: 1.0" . "\r\n";
		$headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";

		return $headers;
	}

	/**
	 * send mail
	 * use php standard mail function here,
	 * so all mail will be pass from here
	 */
	public function send( $to, $subject, $msg, $attach = array(), $args = array() ) {

		// check if nothing pass to TO email
		if( empty($to) ) return false;

		if ( 'yes' == $this->smtp ) {
			$mail = $this->smtp_send( $to, $subject, $msg, $attach, $args );
		} else {
			$mail = $this->default_send( $to, $subject, $msg, $attach, $args );
		}

		return $mail;
	}

	/**
	 * Send mail with default mail function 
	 */
	public function default_send( $to, $subject, $msg, $attach = array(), $args = array() ) {

		try {

			$headers = $this->get_headers();

			$mail = mail( $to, $subject, $msg, $headers, $attach );
			$this->__destruct();

			return $mail;

		} catch (Exception $e) {
			//echo "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
			return false;
		}

		return false;
	}

	/**
	 * Send mail with smtp settings
	 */
	public function smtp_send( $to, $subject, $msg, $attach = array(), $args = array() ) {

		try{
			// Set the variables
			$this->mailer->CharSet = 'utf-8';
			$this->mailer->addAddress( $to );
			$this->mailer->Subject = $subject;
			$this->mailer->Body    = $msg;

			// Send mail
			$this->mailer->send();
			$this->__destruct();

			return true;
		} catch (Exception $e) {
			//echo "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
			return false;
		}

		return false;
	}

	/**
	 * Send the mailer and destroy the object
	 */
	public function mail() {
		
		
	}

	/**
	 * Destructor function
	 */
	public function __destruct() {
		// destroy class object
	}
}