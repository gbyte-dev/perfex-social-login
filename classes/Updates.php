<?php

/* Check the absolute path to the Social Auto Poster directory. */
if ( !defined( 'SAP_APP_PATH' ) ) {
    // If SAP_APP_PATH constant is not defined, perform some action, show an error, or exit the script
    // Or exit the script if required
    exit();
}

/**
 * Logs Class
 * 
 * Responsible for all function related to posts
 *
 * @package Social Auto Poster
 * @since 1.0.0
 */
class SAP_Updates{

	private $db;
	private $settings;
	private $dbvarsion;

	public function __construct() {

		global $sap_db_connect;
        
        // Set Database
        $this->db = $sap_db_connect;

        // Create settings object
		$this->settings = new SAP_Settings();

		// Get database version settings
		$dbvarsion 		= $this->settings->get_options( 'sap_set_db_version' );
		$sap_version 	= $this->settings->get_options( 'sap_version' );
		$sap_new_sass 	= $this->settings->get_options( 'sap_new_sass' );
		
		
		// Check if version is empty
		if( empty($dbvarsion) && ( empty( $sap_version ) || empty( $sap_new_sass ) ) ) {

			// Alter user table, add role column
			$query = "ALTER TABLE `sap_users`
						ADD role varchar(255) NOT NULL AFTER password,
						ADD plan bigint(20) NOT NULL AFTER role,
						ADD expiration varchar(255) NULL AFTER plan,
						ADD email_verification_tokan longtext NULL  AFTER plan,
						ADD status tinyint(2) NULL COMMENT ' 1 active / 0 inactive';";
			$this->db->query( $query );

			// Update table to update default roles of users
			$query = "UPDATE `sap_users` SET role = 'superadmin' WHERE role = '';";
			$this->db->query( $query );

			$query = "UPDATE `sap_users` SET status = '1' WHERE role = 'superadmin';";
			$this->db->query( $query );

			// Alter sap_logs table and add a user_id column
			$query = "ALTER TABLE `sap_logs`
						ADD user_id bigint(20) NOT NULL AFTER id;";
			$this->db->query( $query );

			$insert_email_sub = "INSERT INTO `sap_options` (`option_name`, `option_value`, `autoload`) 
          VALUES('renewal_email_subject', 'Subscription Renewal', 'yes')";
			$this->db->query( $insert_email_sub );

			$payment_gateway = "INSERT INTO `sap_options` (`option_name`, `option_value`, `autoload`) 
          VALUES('payment_gateway', 'manual', 'yes')";
			$this->db->query( $payment_gateway );

			$default_payment_method = "INSERT INTO `sap_options` (`option_name`, `option_value`, `autoload`) 
          VALUES('default_payment_method', 'manual', 'yes')";
			$this->db->query( $default_payment_method );

			$insert_email_content = "INSERT INTO `sap_options` (`option_name`, `option_value`, `autoload`) 
          VALUES('renewal_email_content', '<h3>Hello {user_name},</h3>
                      <p>
						Your current subscription {membership_level}  has been renewed successfully for the subscription id: {subscription_id}. Your {membership_level} plan will be expire on {expiration_date}
						</p>	


                      	<p>Thanks,
                        <br>The Team</p>', 'yes')";
			$this->db->query( $insert_email_content );


			$sql = "INSERT INTO `sap_options` (`option_name`, `option_value`, `autoload`) 
          VALUES('cancelled_membership_email_subject', 'Your membership has been cancelled', 'yes')";
			$this->db->query( $sql );


			$sql = "INSERT INTO `sap_options` (`option_name`, `option_value`, `autoload`) 
          VALUES('cancelled_membership_email_content', '<h3>Hello {user_name},</h3>
                      <p>
            Your current subscription {membership_level}  has been cancelled. You will retain access until {expiration_date}
            </p>
                        <p>Thanks</p>', 'yes')";
			$this->db->query( $sql );

			$sql = "INSERT INTO `sap_options` (`option_name`, `option_value`, `autoload`) 
          VALUES('expired_membership_email_subject', 'Your membership has expired', 'yes')";
			$this->db->query( $sql );

			$sql = "INSERT INTO `sap_options` (`option_name`, `option_value`, `autoload`) 
          VALUES('expired_membership_email_content', '<h3>Hello {user_name},</h3>

Your current subscription {membership_level} has expired. 

To renew or upgrade the membership login to your profile and follow the suggested actions. 

Thanks', 'yes')";
			$this->db->query( $sql );

			// Create user settings table
			$query = "CREATE TABLE IF NOT EXISTS `sap_user_settings` (
					`setting_id` bigint(20) NOT NULL AUTO_INCREMENT,
					`user_id` bigint(20) NOT NULL,
					`setting_name` varchar(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci DEFAULT NULL,
					`setting_value` longtext CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
					`autoload` varchar(255) NOT NULL DEFAULT 'yes',
					PRIMARY KEY  (`setting_id`)
				) ENGINE=InnoDB DEFAULT CHARSET=latin1;";
			$this->db->query( $query );

			// Create plan table
			$query = "CREATE TABLE IF NOT EXISTS `sap_plans` (
					`id` bigint(20) NOT NULL AUTO_INCREMENT,
					`name` varchar(255) NOT NULL,
					`stripe_subscription_id` varchar(255) DEFAULT '',
					`stripe_product_id` varchar(255) DEFAULT '',
					`status` tinyint(2) NULL COMMENT ' 1 active / 0 inactive',
					`subscription_expiration_days` int(21)  NULL,
					`description` longtext NULL,
					`price` DOUBLE NOT NULL,
					`networks` longtext NOT NULL,
					`created` datetime NOT NULL,
					`modified_date` datetime NOT NULL,
					PRIMARY KEY  (`id`)
				) ENGINE=InnoDB DEFAULT CHARSET=latin1;";
			$this->db->query( $query );			

			$query = "ALTER TABLE `sap_users` DROP `expiration`;";		
			$query = "ALTER TABLE `sap_users` DROP `plan`;";		
			$query = "ALTER TABLE `sap_users` DROP `picture`;";		
			$this->db->query( $query );

			$query = "ALTER TABLE `sap_users` DROP `plan`;";
			$this->db->query( $query );


			$query = "CREATE TABLE IF NOT EXISTS `sap_membership` (
				`id` int(11) NOT NULL AUTO_INCREMENT,
				`user_id` int(11) NOT NULL,
				`plan_id` int(11) NOT NULL,
				`membership_duration_days` int(11) DEFAULT NULL,
				`customer_id` varchar(255) NOT NULL,
				`customer_name` varchar(255) NOT NULL,
				`membership_status` tinyint(2) NOT NULL DEFAULT '0' COMMENT ' 0 pending / 1 active / 2 expired /3 cancelled',
				`recurring` tinyint(2) NOT NULL  DEFAULT '0' COMMENT '1 yes / 0 no',
				`gateway` varchar(255) DEFAULT NULL,
				`subscription_id` varchar(255) DEFAULT NULL,
				`expiration_date` varchar(255)  NULL,
				`renew_date` datetime DEFAULT NULL,
				`upgrade_date` datetime DEFAULT NULL,
				`cancellation_date` datetime DEFAULT NULL,
				`previous_plan` varchar(255) DEFAULT NULL,
				`membership_created_date` datetime DEFAULT NULL,
				`created_date` datetime NOT NULL,
				`modified_date` datetime NOT NULL,
				PRIMARY KEY  (`id`)
			) ENGINE=InnoDB DEFAULT CHARSET=latin1;";
			$this->db->query( $query );


			$query = "ALTER TABLE `sap_membership` ADD INDEX( `user_id`); 
			ALTER TABLE `sap_membership` ADD INDEX( `customer_name`); 
			ALTER TABLE `sap_membership` ADD INDEX( `plan_id`);"; 
			$this->db->query( $query );

			$query = "CREATE TABLE IF NOT EXISTS `sap_payment_history` (
				`id` int(20) NOT NULL AUTO_INCREMENT,
				`user_id` int(11) NOT NULL,
				`membership_id` int(11) NOT NULL,
				`plan_id` int(11) NOT NULL,
				`customer_id` varchar(255) DEFAULT NULL,
				`customer_name` varchar(255) DEFAULT NULL,
				`customer_email` varchar(255) DEFAULT NULL,
				`payment_date` datetime DEFAULT NULL,
				`amount` double DEFAULT NULL,
				`type` tinyint(2) NOT NULL  DEFAULT '0' COMMENT ' 0 new /  1 renew / 2 upgrade',
				`gateway` varchar(255) DEFAULT NULL COMMENT 'stripe / paypal  / manual',
				`payment_status` tinyint(2) NOT NULL DEFAULT '0'  COMMENT '0 Pending / 1 completed / 2 fail /3 Refunded',
				`transaction_id` varchar(255) DEFAULT NULL,
				`transaction_data` longtext DEFAULT NULL,
				`created_date` datetime NOT NULL,
				`modified_date` datetime NOT NULL,
				PRIMARY KEY  (`id`)
				) ENGINE=InnoDB DEFAULT CHARSET=latin1;";
			$this->db->query( $query );


			$query = "ALTER TABLE `sap_payment_history` ADD INDEX( `membership_id`); 
			ALTER TABLE `sap_payment_history` ADD INDEX( `plan_id`);";
			$this->db->query( $query );

			$query = " ALTER TABLE zone RENAME TO sap_zone";
			$this->db->query( $query );
			
			// Update db version option
			$dbvarsion = '1.0.1';
			$this->settings->update_options( 'sap_set_db_version', $dbvarsion );

		}

		// Check dbversion is 1.0.1
		if( '1.0.0' == $dbvarsion ) {
			//Sap quick posts edit
			$qp_query = "ALTER TABLE `sap_quick_posts` ADD `video` varchar(255)  CHARACTER SET  utf8 COLLATE utf8_unicode_ci DEFAULT NULL AFTER `image`";
			$this->db->query( $qp_query );

			$dbvarsion = '1.0.1';
			$this->settings->update_options( 'sap_set_db_version', $dbvarsion );
		} elseif( empty( $dbvarsion ) ) {
			$dbvarsion = '1.0.1';
			$this->settings->update_options( 'sap_set_db_version', $dbvarsion );
		}

		// Check dbversion is 1.0.1
		if( '1.0.1' == $dbvarsion ) {
			//create table for coupons and chnages in payment history table regarding coupons
			$qp_query = "CREATE TABLE IF NOT EXISTS `sap_coupons` (
				`id` INT NOT NULL AUTO_INCREMENT ,
				`coupon_code` VARCHAR(100) NOT NULL ,
				`coupon_type` ENUM('fixed_discount','percentage_discount') NOT NULL ,
				`coupon_amount` INT NOT NULL ,
				`coupon_description` TEXT NOT NULL ,
				`coupon_expiry_date` DATETIME NOT NULL ,
				`coupon_status` ENUM('draft','publish','used') NOT NULL ,
				`created_date` DATETIME NOT NULL ,
				`modified_date` DATETIME NOT NULL , PRIMARY KEY (`id`)) ENGINE = InnoDB";
			$this->db->query( $qp_query );
			  
			$qp_query = "ALTER TABLE `sap_coupons` CHANGE `modified_date` `modified_date` DATETIME NULL DEFAULT NULL";
			$this->db->query( $qp_query );
			$qp_query = "ALTER TABLE `sap_coupons` CHANGE `coupon_expiry_date` `coupon_expiry_date` DATETIME NULL DEFAULT NULL";
			$this->db->query( $qp_query );
			$qp_query = "ALTER TABLE `sap_coupons` CHANGE `coupon_description` `coupon_description` TEXT CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL";
			$this->db->query( $qp_query );
			$qp_query = "ALTER TABLE `sap_coupons` CHANGE `coupon_amount` `coupon_amount` DOUBLE NOT NULL";
			$this->db->query( $qp_query );
			
			$db_query = "SHOW COLUMNS from `sap_payment_history` LIKE 'coupon_id'";
			$result = $this->db->get_results( $db_query );
			if(empty($result)) {
				$qp_query = "ALTER TABLE `sap_payment_history` ADD `coupon_id` INT NULL AFTER `payment_date`";
				$this->db->query( $qp_query );
			}
			$db_query = "SHOW COLUMNS from `sap_payment_history` LIKE 'coupon_name'";
			$result = $this->db->get_results( $db_query );
			if(empty($result)) {
				$qp_query = "ALTER TABLE `sap_payment_history` ADD `coupon_name` VARCHAR(100) NULL AFTER `amount`, ADD `coupon_discount_amount` DOUBLE NULL AFTER `coupon_name`";
				$this->db->query( $qp_query );
			}

			$dbvarsion = '1.0.2';
			$this->settings->update_options( 'sap_set_db_version', $dbvarsion );
		}
		
		// Check dbversion is 1.0.2
		if( '1.0.2' == $dbvarsion ) {
			$qp_query = "UPDATE `sap_user_settings` set setting_value = '' WHERE setting_name = 'sap_fb_sess_data'";
			$this->db->query( $qp_query );		

			$dbvarsion = '1.0.3';
			$this->settings->update_options( 'sap_set_db_version', $dbvarsion );		
		}

		if( '1.0.3' == $dbvarsion ) {
			// future update code			
		}
	}
}

return new SAP_Updates();
