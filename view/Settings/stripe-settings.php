<?php

/* Check the absolute path to the Social Auto Poster directory. */
if ( !defined( 'SAP_APP_PATH' ) ) {
    // If SAP_APP_PATH constant is not defined, perform some action, show an error, or exit the script
    // Or exit the script if required
    exit();
}

include 'header.php';
include 'sidebar.php';

global $sap_common;
$payment_gateway = (isset($payment_gateway) && !empty($payment_gateway)) ? explode(',', $payment_gateway) : array();
$SAP_Mingle_Update = new SAP_Mingle_Update();
$license_data = $SAP_Mingle_Update->get_license_data();
if( empty( $license_data['license_key'] ) ){
	$redirection_url = '/mingle-update/';
	header('Location: ' . SAP_SITE_URL . $redirection_url );
	die();
}

$common = new Common();

//get chapGPT api key
$sap_chatgpt_api_key = $this->get_options('sap_chatgpt_api_key');

//get site name
$mingle_site_name = $this->get_options('mingle_site_name');

//get meta title
$mingle_meta_title = $this->get_options('mingle_meta_title');

//get meta description
$mingle_meta_description = $this->get_options('mingle_meta_description');

// Get SAP options which stored
$footer_content = $this->get_options('footer_content');
$mingle_logo = $this->get_options('mingle_logo');
$mingle_favicon = $this->get_options('mingle_favicon');

$smtp_setting = $this->get_options('sap_smtp_setting');
$enable_email_verification = $this->get_options('enable_email_verification');
$cancelled_membership_email_subject = $this->get_options('cancelled_membership_email_subject');
$cancelled_membership_email_content = $this->get_options('cancelled_membership_email_content');

$expired_membership_email_content = $this->get_options('expired_membership_email_content');
$expired_membership_email_subject = $this->get_options('expired_membership_email_subject');

$enable		= isset($smtp_setting['enable']) ? $smtp_setting['enable'] : '';
$from_email	= isset($smtp_setting['from_email']) ? $smtp_setting['from_email'] : '';
$from_name	= isset($smtp_setting['from_name']) ? $smtp_setting['from_name'] : '';
$host		= isset($smtp_setting['host']) ? $smtp_setting['host'] : '';
$enc_type	= isset($smtp_setting['enc_type']) ? $smtp_setting['enc_type'] : 'None';
$port		= isset($smtp_setting['port']) ? $smtp_setting['port'] : '';
$username	= isset($smtp_setting['username']) ? $smtp_setting['username'] : '';
$password	= isset($smtp_setting['password']) ? $smtp_setting['password'] : '';
$sap_active_tab = !empty($_SESSION['sap_active_tab']) ? $_SESSION['sap_active_tab'] : 'payment_gateway';


$enable_misc_relative_path = $this->get_options('enable_misc_relative_path');

?>

<!-- Content Wrapper. Contains page content -->
<div class="content-wrapper">
	<!-- Content Header (Page header) -->
	<section class="content-header">
		<h1>
			<span class="d-flex flex-wrap align-items-center">
				<div class="page-title-icon general_settings_icon"></div>
				<?php echo $sap_common->lang('general_settings'); ?>
			</span>
			</h1>
	</section>
	<!-- Main content -->
	<section class="content">

		<!-- Info boxes -->
		<div class="row">
			<div class="col-md-12">
				<?php
				$smtp_settings 		= '';
				$email_settings 	= '';
				$misc_settings 	    = '';
				$payment_gateways 	= '';
				$general_settings	= 'active';

				if ($sap_active_tab == 'smtp_settings') {

					$smtp_settings 		= 'active';
					$email_settings 	= '';
					$payment_gateways 	= '';
					$misc_settings 	= '';
					$general_settings = '';
				} elseif ($sap_active_tab == 'email_settings') {
					$smtp_settings 		= '';
					$email_settings 	= 'active';
					$payment_gateways 	= '';
					$misc_settings 	= '';
					$general_settings = '';
				} elseif ($sap_active_tab == 'misc_settings') {
					$smtp_settings 		= '';
					$email_settings 	= '';
					$payment_gateways 	= '';
					$general_settings = '';
					$misc_settings 	    = 'active';
				}

				echo $this->flash->renderFlash();
				?>

				<div class="nav-tabs-custom sap-general-setting-tab">
					<ul class="nav nav-tabs">

						<li class="<?php echo $general_settings ?>"><a href="#general_settings_tab" data-tab="payment_gateways" data-toggle="tab"><?php echo $sap_common->lang('general_settings_tab'); ?>
							</a></li>

						<li class="<?php echo $payment_gateways ?>"><a href="#payment_gateways" data-tab="payment_gateways" data-toggle="tab"><?php echo $sap_common->lang('payment_gateway_settings'); ?>
							</a></li>

						<li class="<?php echo $email_settings ?>"><a href="#email_settings" data-tab="email_settings" data-toggle="tab"><?php echo $sap_common->lang('email_settings'); ?></a></li>

						<li class="<?php echo $smtp_settings ?>"><a href="#smtp_settings" data-tab="smtp_settings" data-toggle="tab"><?php echo $sap_common->lang('smtp_settings'); ?></a></li>

						<li class="<?php echo $misc_settings ?>"><a href="#misc_settings" data-tab="misc_settings" data-toggle="tab"><?php echo $sap_common->lang('misc_settings'); ?>
							</a></li>

					</ul>

					<form class="stripe-setting" name="stripe-setting" id="stripe-setting" method="POST" action="<?php echo SAP_SITE_URL . '/save_stripe_settings/'; ?>" novalidate="novalidate" enctype="multipart/form-data">
						<input type="hidden" name="sap_active_tab" id="sap_active_tab" value="<?php echo $sap_active_tab ?>">

						<div class="tab-content">
							<div class="tab-pane <?php echo $general_settings ?>" id="general_settings_tab">
								<div class="box-primary">
									<div class="box-header"></div>
									<div class="box-header sap-settings-box-header"><?php echo $sap_common->lang('general_settings_logo_htitle'); ?></div>
									<div class="row">
										<div class="col-lg-6">
											<div class="row">
												<div class="col-md-6 form-group ">
													<div class="form-group logo-img-edit">
														<div class="row">															
															<div class="col-sm-12">
																<label for="mingle_logo" class="control-label"><?php echo $sap_common->lang('upload_logo'); ?></label>
																<?php if (!empty($mingle_logo)) { ?> 
																	<input id="mingle-logo-image" tabindex="3" value="<?php echo $mingle_logo; ?>" name="mingle_logo" type="file" class="file file-loading" data-show-upload="false" data-show-caption="false" data-allowed-file-extensions='["png", "jpg","jpeg", "svg"]' data-max-file-size="<?php echo MINGLE_MAX_FILE_UPLOAD_SIZE; ?>" data-initial-preview="<img src='<?php echo SAP_IMG_URL . $mingle_logo; ?>' class='uploaded-img'/>">
																	<input type="hidden" id="mingle_logo_file" name="mingle_logo_file" value="<?php echo $mingle_logo ?>">

																<?php } else { ?>

																	<input id="mingle-logo-image" tabindex="3" value="" name="mingle_logo" id="mingle_logo" type="file" class="file file-loading" data-show-upload="false" data-show-caption="true" data-max-file-size="<?php echo MINGLE_MAX_FILE_UPLOAD_SIZE; ?>" data-allowed-file-extensions='["png", "jpg","jpeg","svg"]' />

																<?php } ?>


																<p class="description"><?php echo $sap_common->lang('upload_logo_image'); ?></p>
															</div>
														</div>
													</div>
												</div> 
												<div class="col-md-6 form-group ">
													<div class="form-group favicon-img-edit">
														<div class="row">
															<div class="col-sm-12">
																<label for="mingle_favicon" class="control-label"><?php echo $sap_common->lang('upload_favicon'); ?></label>
																<?php if (!empty($mingle_favicon)) { ?>

																	<input id="mingle-favicon-image" tabindex="3" value="<?php echo $mingle_favicon; ?>" name="mingle_favicon" type="file" class="file file-loading" data-show-upload="false" data-show-caption="false" data-allowed-file-extensions='["png", "jpg","jpeg", "svg"]' data-max-file-size="<?php echo MINGLE_MAX_FILE_UPLOAD_SIZE; ?>" data-initial-preview="<img src='<?php echo SAP_IMG_URL . $mingle_favicon; ?>' class='uploaded-img'/>">

																	<input type="hidden" id="mingle_favicon_file" name="mingle_favicon_file" value="<?php echo $mingle_favicon ?>">

																<?php } else { ?>

																	<input id="mingle-favicon-image" tabindex="3" value="" name="mingle_favicon" id="mingle_favicon" type="file" class="file file-loading" data-show-upload="false" data-show-caption="true" data-max-file-size="<?php echo MINGLE_MAX_FILE_UPLOAD_SIZE; ?>" data-allowed-file-extensions='["png", "jpg","jpeg","svg"]' />

																<?php } ?>



																<p class="description"><?php echo $sap_common->lang('upload_favicon'); ?></p>
															</div>
														</div>
													</div>
												</div> 
											</div>
										</div>		
										<div class="col-md-12 col-lg-6 form-group"> 
													<div class="row">														
														<div class="col-sm-12">
															<label for="mingle_site_name" class="control-label"><?php echo $sap_common->lang('site_name'); ?></label>
															<input type="text" id="mingle_site_name" name="mingle_site_name" value="<?php echo !empty($mingle_site_name) ? $mingle_site_name : ''; ?>" class="form-control">
															<p class="description"><?php echo $sap_common->lang('description_for_site_name_input_field'); ?></p>
														</div>
													</div> 
												</div>
												<div class="col-md-12 col-lg-6 form-group">
														<div class="row">															
															<div class="col-sm-12">
																<label for="mingle_meta_title" class="control-label"><?php echo $sap_common->lang('meta_title'); ?></label>
																<input type="text" id="mingle_meta_title" name="mingle_meta_title" value="<?php echo !empty($mingle_meta_title) ? $mingle_meta_title : ''; ?>" class="form-control">
																<p class="description"><?php echo $sap_common->lang('description_for_meta_title_input_field'); ?></p>
															</div>
														</div>
												</div>		
											<div class="col-lg-6">					  
													<div class="row"> 
														<div class="col-sm-12">
															<label for="mingle_meta_description" class="control-label"><?php echo $sap_common->lang('meta_description'); ?></label>
															<input type="text" id="mingle_meta_description" name="mingle_meta_description" value="<?php echo !empty($mingle_meta_description) ? $mingle_meta_description : ''; ?>" class="form-control">
															<p class="description"><?php echo $sap_common->lang('description_for_meta_desc_input_field'); ?></p>
														</div> 
												</div>
											</div>
										</div>

									<div class="box-header"></div>
									<div class="box-header sap-settings-box-header border-top"><?php echo $sap_common->lang('footer_setting_label'); ?></div>
									<div class="row">
										<div class="col-md-12 col-lg-6 form-group ">
											<div class="row">
												<div class="col-sm-12">
													<label for="footer_content" class="control-label"><?php echo $sap_common->lang('content'); ?></label>
													<textarea rows="5" class="form-control" name="footer_content" id="footer_content"><?php echo $footer_content ?></textarea>
												</div>
											</div>
										</div>
									</div>							
									<div class="box-header"></div>
									<div class="box-header sap-settings-box-header border-top"><?php echo $sap_common->lang('api_key_setting_heading'); ?></div>
									<div class="row">
										<div class="col-md-12 col-lg-6 form-group"> 
											<div class="row">
												<div class="col-sm-12">
													<label for="sap_chatgpt_api_key" class="control-label"><?php echo $sap_common->lang('enter_api_key'); ?></label>
													<input type="text" name="sap_chatgpt_api_key" id="sap_chatgpt_api_key" class="form-control" value="<?php echo !empty($sap_chatgpt_api_key) ? $sap_chatgpt_api_key : ''; ?>">
													<p class="description"><?php echo $sap_common->lang('desction_for_api_key_input_field'); ?></p>
												</div>
											</div> 
										</div>
										<div class="sap-mt-1 col-md-12">
											<div class="sap-mb-1">
												<button type="submit" name="sap_save_stripe_setting" class="btn btn-primary"><?php echo $sap_common->lang('save'); ?></button>
											</div>
										</div>

									</div>
								</div>
							</div>

							<div class="tab-pane <?php echo $payment_gateways ?>" id="payment_gateways">
								<div class="box-primary">

									<div class="nav-tabs-custom">
										<ul class="nav nav-tabs">
											<li class="active"><a href="#general" data-toggle="tab"><?php echo $sap_common->lang('general'); ?></a></li>
											<li><a href="#stripe" data-toggle="tab"><?php echo $sap_common->lang('stripe'); ?></a></li>
										</ul>

										<div class="tab-content">
											<div class="tab-pane active" id="general">
												<div class="row">
													<div class="col-md-12 form-group"> 
														<div class="row">
															<div class="col-sm-10 available-gateways" bis_skin_checked="1">
																<div class="row">
																	<label class="col-lg-3 col-md-4" class="control-label"><?php echo $sap_common->lang('payment_gateway'); ?></label>
																	<div class="col-lg-9 col-md-8">
																		<div class="checbox-list">
																			<div class="checbox-item">																		
																				<input type="checkbox" class="" name="payment_gateway[]" id="payment_gateway_manual" value="manual" <?php echo (in_array('manual', $payment_gateway)) ? 'checked' : ''; ?>>
																				<label class="" for="payment_gateway_manual"><?php echo $sap_common->lang('manual'); ?></label>

																			</div>

																			<div class="checbox-item">
																				<input type="checkbox" class="" name="payment_gateway[]" id="payment_gateway_stripe" value="stripe" <?php echo (in_array('stripe', $payment_gateway)) ? 'checked' : ''; ?>>
																				<label class="" for="payment_gateway_stripe"><?php echo $sap_common->lang('stripe'); ?></label>																			
																			</div>
																		</div>
																		<p class="description"><?php echo $sap_common->lang('stripe_help_text'); ?></p>
																		</div> 
																</div>
															</div>
														</div>
													</div>
												</div>

												<div class="row">
													<div class=" form-group">
														
														<div class="col-sm-8 col-md-6" bis_skin_checked="1">
														<label for="default_payment_method" class="control-label"><?php echo $sap_common->lang('default_payment'); ?></label>		
															<select name="default_payment_method" class="form-control">
																<option value=''><?php echo $sap_common->lang('select_payment'); ?></option>
																<?php if (in_array('manual', $payment_gateway)) { ?>
																	<option value='manual' <?php echo ($default_payment_method == 'manual') ? 'selected' : '' ?>><?php echo $sap_common->lang('manual'); ?></option>
																<?php } ?>
																<?php if (in_array('stripe', $payment_gateway)) { ?>
																	<option value='stripe' <?php echo ($default_payment_method == 'stripe') ? 'selected' : '' ?>><?php echo $sap_common->lang('stripe'); ?></option>
																<?php } ?>

															</select>
															<p class="description"><?php echo $sap_common->lang('default_payment_help_text'); ?></p>
														</div>
													</div>
												</div>
												<div class="row">
													<div class="sap-mt-1 col-md-12 form-group">
														<button type="submit" name="sap_save_stripe_setting" class="btn btn-primary"><?php echo $sap_common->lang('save'); ?></button>
													</div>
												</div>

											</div>
											<div class="tab-pane" id="stripe">
												<div class="row sap_plan">
													<div class="col-md-12 form-group">
														<div class="col-md-12 form-group">
															<div class="row">
																<label class="col-sm-4 col-md-2 control-label"><?php echo $sap_common->lang('en_test_mode'); ?></label>
																<div class="col-sm-8 col-md-9">
																	<input type="checkbox" class="tgl tgl-ios" name="stripe_test_mode" <?php echo ($stripe_test_mode == 'yes') ? 'checked="checked"' : ''; ?> id="stripe_test_mode" value="1">
																	<label class="tgl-btn float-right-cs-init" for="stripe_test_mode"></label>
																	<p class="description"><?php echo $sap_common->lang('en_test_help_text'); ?></p>
																</div>
															</div>
														</div>
													</div>

													<div class="col-md-6 form-group">
														<div class="col-md-12 form-group">
															<div class="row">
																<div class="col-sm-12">
																	<label class="control-label"><?php echo $sap_common->lang('stripe_title'); ?></label>
																	<input type="text" class="form-control" name="stripe_label" id="stripe_label" value="<?php echo $stripe_label; ?>">
																	<p class="description"><?php echo $sap_common->lang('stripe_title_help_text'); ?></p>
																</div>
															</div>
														</div>
													</div>

													<div class="col-md-6 form-group stripe-test">
														<div class="col-md-12 form-group">
															<div class="row">
																<div class="col-sm-12">
																	<label class="control-label"><?php echo $sap_common->lang('test_publisher_key'); ?></label>
																	<input type="text" value="<?php echo $test_publishable_key ?>" class="form-control" name="test_publishable_key" id="test_publishable_key">
																	<p class="description"><?php echo $sap_common->lang('test_publisher_help_text'); ?></p>
																</div>
															</div>
														</div>
													</div>

													<div class="col-md-6 form-group stripe-test">
														<div class="col-md-12 form-group">
															<div class="row">
																<div class="col-sm-12">
																	<label class="control-label"><?php echo $sap_common->lang('test_secret_key'); ?></label>
																	<input type="text" class="form-control" name="test_secret_key" value="<?php echo $test_secret_key ?>" id="test_secret_key">
																	<p class="description"><?php echo $sap_common->lang('enter_test_secret_key'); ?></p>
																</div>
															</div>

														</div>
													</div>


													<div class="col-md-6 form-group stripe-live">
														<div class="col-md-12 form-group">
															<div class="row">
																<div class="col-sm-12">
																	<label class="control-label"><?php echo $sap_common->lang('live_publisher_key'); ?></label>
																	<input type="text" value="<?php echo $live_publishable_key ?>" class="form-control" name="live_publishable_key" id="live_publishable_key">
																	<p class="description"><?php echo $sap_common->lang('enter_live_publisher_key'); ?></p>
																</div>

															</div>
														</div>
													</div>

													<div class="col-md-6 form-group stripe-live">
														<div class="col-md-12 form-group">
															<div class="row">
																<div class="col-sm-12">
																	<label class="control-label"><?php echo $sap_common->lang('live_secret_key'); ?></label>
																	<input type="text" class="form-control" name="live_secret_key" value="<?php echo $live_secret_key ?>" id="live_secret_key">
																	<p class="description"><?php echo $sap_common->lang('enter_live_secret_key'); ?></p>
																</div>

															</div>
														</div>
													</div>

													<div class="col-md-12 form-group  checbox-list">
														<div class="col-md-12 form-group">
															<div class="row">
																<label class="col-sm-4 col-md-2 control-label"><?php echo $sap_common->lang('billing_detail_settings'); ?> </label>
																<div class="col-sm-8 col-md-10">
																	<input type="checkbox"  name="enable_billing_details" value="enable_billing_details" id="enable_billing_details" <?php if( $enable_billing_details == 'enable_billing_details' ) { echo 'checked'; }  ?> >
																	<label for="enable_billing_details">Enable Address Information</label>
																	<p class="description">
																		<?php echo sprintf($sap_common->lang('enable_billing_details_help_text'), '<b>', '</b>'); ?></p>
																</div>
															</div>
														</div>
													</div>

													<div class="col-md-12 form-group">
														<div class="col-md-12 form-group">
															<div class="row">
																<label class="col-sm-4 col-md-2 control-label"><?php echo $sap_common->lang('strip_webhook_url'); ?> </label>
																<div class="col-sm-8 col-md-10">
																	<i><?php echo SAP_SITE_URL . '/subscription-stripe/' ?></i>
																	<p class="description">
																		<?php echo sprintf($sap_common->lang('strip_webhook_url_help_text'), '<b>', '</b>'); ?></p>
																</div>
															</div>
														</div>
													</div>

													<div class="sap-mt-1 col-md-12 form-group">
														<button type="submit" name="sap_save_stripe_setting" class="btn btn-primary"><?php echo $sap_common->lang('save'); ?></button>
													</div>
												</div>
											</div>
										</div>
									</div>
								</div>
							</div>

							<div class="tab-pane <?php echo $email_settings ?>" id="email_settings">
								<div class="box-primary ">

									<div class="box-header"></div>
									<div class="row">
										<div class="col-md-12 form-group">
											<div class="row">
												<div class="col-md-2">
													<label for="enable_email_verification" class="control-label"><?php echo $sap_common->lang('en_email_verif'); ?> </label>
												</div>
												<div class="col-sm-9">
													<input type="checkbox" <?php echo ($enable_email_verification == 'yes') ? 'checked="checked"' : '' ?> class="tgl tgl-ios" name="enable_email_verification" id="enable_email_verification" value="1">

													<label class="tgl-btn float-right-cs-init" for="enable_email_verification"></label>
													<p class="description"><?php echo $sap_common->lang('en_email_verif_help_text'); ?> </p>
												</div>
											</div>
										</div>
									</div>

									<div class="box-header sap-settings-box-header renewal_notif"><?php echo $sap_common->lang('subscription_renewal_notif_email'); ?></div>
									<div class="row">

										<div class="col-md-12 form-group ">
											<div class="col-md-6 form-group">
												<div class="row">
													<label for="renewal_email_subject" class="control-label"><?php echo $sap_common->lang('subject'); ?></label>
													<div>
														<input type="text" class="form-control" name="renewal_email_subject" value="<?php echo $renewal_email_subject ?>" id="renewal_email_subject">
														<p class="description"><?php echo $sap_common->lang('subject_help_text'); ?></p>
													</div>
												</div>
											</div>
										</div>

										<div class="col-md-12 form-group ">
											<div class="col-md-12 form-group">
												<div class="row">
													<label for="renewal_email_content" class="control-label"><?php echo $sap_common->lang('content'); ?></label>
													<div class="d-flex row renewal_email_content">
														<div class="col-md-8 col-sm-12">
															<textarea rows="15" class="form-control" name="renewal_email_content" id="renewal_email_content"><?php echo $renewal_email_content ?></textarea>
														</div>
														<div class="col-md-4 col-sm-12">
															<span class="description">
																<?php echo sprintf($sap_common->lang('content_help_test'), '<br>', '<code>', '</code>', '<br>', '<code>', '</code>', '<br>', '<code>', '</code>', '<br>', '<code>', '</code>'); ?>
															</span>
														</div>
													</div>
												</div>
											</div>
										</div>

										<div class="col-md-12 form-group ">
											<div class="box-header sap-settings-box-header"><?php echo $sap_common->lang('cancelled_membership_email_notif'); ?></div>
											<div class="col-md-6 form-group">
												<div class="row">
													<label for="cancelled_membership_email_subject" class="control-label"><?php echo $sap_common->lang('subject'); ?></label>
													<div>
														<input type="text" class="form-control" name="cancelled_membership_email_subject" value="<?php echo $cancelled_membership_email_subject ?>" id="cancelled_membership_email_subject">
														<p class="description"><?php echo $sap_common->lang('cancelled_membership_email_notif_help_text'); ?></p>
													</div>
												</div>
											</div>
										</div>

										<div class="col-md-12 form-group ">
											<div class="col-md-12 form-group">
												<div class="row">
													<label for="cancelled_membership_email_content" class="control-label"><?php echo $sap_common->lang('content'); ?></label>
													<div class="d-flex row renewal_email_content">
														<div class="col-md-8 col-sm-12">
															<textarea rows="15" class="form-control" name="cancelled_membership_email_content" id="cancelled_membership_email_content"><?php echo $cancelled_membership_email_content ?></textarea>
														</div>
														<div class="col-md-4 col-sm-12">
															<span class="description">
																<?php echo sprintf($sap_common->lang('cancelled_membership_email_notif_content_help_text'), '<br>', '<code>', '</code>', '<br>', '<code>', '</code>', '<br>', '<code>', '</code>'); ?>
															</span>
														</div>
													</div>
												</div>
											</div>
										</div>


										<div class="col-md-12 form-group ">
											<div class="box-header sap-settings-box-header"><?php echo $sap_common->lang('expired_membership_email_notif'); ?></div>
											<div class="col-md-6 form-group">
												<div class="row">
													<label for="expired_membership_email_subject" class="control-label"><?php echo $sap_common->lang('subject'); ?></label>
													<div >
														<input type="text" class="form-control" name="expired_membership_email_subject" value="<?php echo $expired_membership_email_subject ?>" id="expired_membership_email_subject">
														<p class="description"><?php echo $sap_common->lang('expired_membership_email_notif_help_text'); ?></p>
													</div>
												</div>
											</div>
										</div>

										<div class="col-md-12 form-group ">
											<div class="col-md-12 form-group">
												<div class="row">
													<label for="expired_membership_email_content" class="control-label"><?php echo $sap_common->lang('content'); ?></label>
													<div class="d-flex row renewal_email_content">
														<div class="col-md-8 col-sm-12">
														<textarea rows="15" class="form-control" name="expired_membership_email_content" id="expired_membership_email_content"><?php echo $expired_membership_email_content ?></textarea>
														</div>
														<div class="col-md-4 col-sm-12">
															<span class="description">
																<?php echo sprintf($sap_common->lang('expired_membership_email_notif_content_help_text'), '<br>', '<code>', '</code>', '<br>', '<code>', '</code>'); ?>
															</span>
														</div>
													</div>
												</div>
											</div>
										</div>

										<div class="row">
											<div class="sap-mt-1 col-md-12">
												<div class="col-md-5 sap-mb-1">
													<button type="submit" name="sap_save_stripe_setting" class="btn btn-primary"><?php echo $sap_common->lang('save'); ?></button>
												</div>
											</div>
										</div>
									</div>
								</div>
							</div>

							<div class="tab-pane <?php echo $smtp_settings ?>" id="smtp_settings">
								<div class="box-primary">
									<div class="box-header" bis_skin_checked="1"></div>

									<div class="row">
										<div class="col-md-12 form-group">
											<div class="row">
												<label for="" class="col-sm-3 col-md-3 control-label"><?php echo $sap_common->lang('enable_disable'); ?></label>
												<div class="col-sm-9 col-md-8 col-lg-6">
													<div class="checkbox-inline">
														<label class="auth-option checbox-list ">
															<input name="sap_smtp[enable]" value="no" type="hidden" />
															<input name="sap_smtp[enable]" value="yes" id="enable"  type="checkbox" class="" <?php if ('yes' == $enable) echo "checked"; ?> />
															<label for="enable"><?php echo $sap_common->lang('smtp_en_checkbox'); ?></label>
															
														</label>
													</div>
												</div>
											</div>
										</div>

										<div class="col-md-6 form-group ">
											
												<label for="" class="control-label"><?php echo $sap_common->lang('from_email'); ?></label>
												<div >
													<input name="sap_smtp[from_email]" value="<?php echo $from_email; ?>" type="text" class="form-control" />
												</div>
											
										</div>

										<div class="col-md-6 form-group ">
											
													<label for="" class="control-label"><?php echo $sap_common->lang('from_name'); ?></label>
													<div>
														<input name="sap_smtp[from_name]" value="<?php echo $from_name; ?>" type="text" class="form-control" />
													</div>
												
										</div>

										<div class="col-md-6 form-group ">
											
													<label for="" class="control-label"><?php echo $sap_common->lang('smtp_host'); ?></label>
													<div>
														<input name="sap_smtp[host]" value="<?php echo $host; ?>" type="text" class="form-control" />
													</div>
											
										</div>

										<div class="col-md-6 form-group ">
											
													<label for="" class="control-label"><?php echo $sap_common->lang('smtp_port'); ?></label>
													<div>
														<input name="sap_smtp[port]" value="<?php echo $port; ?>" type="text" class="form-control" />
													</div>
											
										</div>

										<div class="col-md-6 form-group ">
											
													<label for="" class="control-label"><?php echo $sap_common->lang('smtp_username'); ?></label>
													<div>
														<input name="sap_smtp[username]" value="<?php echo $username; ?>" type="text" class="form-control" />
													</div>
											
										</div>

										<div class="col-md-6 form-group ">
											
													<label for="" class="control-label"><?php echo $sap_common->lang('smtp_pass'); ?></label>
													<div>
														<input name="sap_smtp[password]" value="<?php echo $password; ?>" type="password" class="form-control" />
													</div>
												

											
										</div>
										<div class="col-md-6 form-group ">
											<div class="row">
												<div class="sap-mt-1 col-md-12 form-group">
														<button type="submit" name="sap_save_stripe_setting" class="btn btn-primary"><?php echo $sap_common->lang('save'); ?></button>
													</div>
												</div>
											</div>
										</div>
								</div>
							</div>

							<div class="tab-pane <?php echo $misc_settings ?>" id="misc_settings">
								<div class="box-primary">
									<div class="box-header" bis_skin_checked="1"></div>
									<div class="row">
										<div class="col-md-12 form-group">
											<div class="form-group">
												<label for="" class="col-sm-4 col-md-2 control-label"><?php echo $sap_common->lang('enable_relative_path'); ?></label>
												<div class="col-sm-9 col-md-8 col-lg-6">
													<div class="checkbox-inline">
														<label class="auth-option checbox-list ">
															<input name="enable_misc_relative_path" value="yes" type="checkbox" id="enable_misc" class="" <?php if ('yes' == $enable_misc_relative_path) echo "checked"; ?> />
															<label for="enable_misc"><?php echo $sap_common->lang('misc_en_checkbox'); ?>
														</label>
													</div>
												</div>
											</div>
										</div>

										<div class="col-md-12 form-group ">
											<div class="sap-mt-1 col-md-12 form-group">
												<button type="submit" name="sap_save_stripe_setting" class="btn btn-primary"><?php echo $sap_common->lang('save'); ?></button>
											</div>
										</div>
									</div>
								</div>
							</div>

						</div>
					</form>
				</div>
			</div>
		</div>
		<!-- /.row -->
	</section>
</div>

<?php
unset($_SESSION['sap_active_tab']);
include 'footer.php';
?>