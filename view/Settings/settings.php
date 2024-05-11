<?php 

/* Check the absolute path to the Social Auto Poster directory. */
if ( !defined( 'SAP_APP_PATH' ) ) {
    // If SAP_APP_PATH constant is not defined, perform some action, show an error, or exit the script
    // Or exit the script if required
    exit();
}

global $sap_common;
$SAP_Mingle_Update = new SAP_Mingle_Update();
$license_data = $SAP_Mingle_Update->get_license_data();
if( !$sap_common->sap_is_license_activated() ){
	$redirection_url = '/mingle-update/';
	header('Location: ' . SAP_SITE_URL . $redirection_url );
	die();
}

include 'header.php';

include 'sidebar.php';

require_once ( CLASS_PATH . 'Posts.php');
require_once ( CLASS_PATH . 'Common.php');


// Get user's active networks
$networks = sap_get_users_networks();
	
//Check Social class exist or not then load class
if ( !class_exists('SAP_Facebook') ) {
	include ( CLASS_PATH . 'Social' . DS . 'fbConfig.php' );
}
if ( !class_exists('SAP_Linkedin') ) {
	include ( CLASS_PATH . 'Social' . DS . 'liConfig.php' );
}
if ( !class_exists('SAP_Tumblr') ) {
	include ( CLASS_PATH . 'Social' . DS . 'tumblrConfig.php' );
}
if ( !class_exists('SAP_Gmb') ) {
	include ( CLASS_PATH . 'Social' . DS . 'gmbConfig.php' );
}
if ( !class_exists('SAP_Pinterest') ) {
	include ( CLASS_PATH . 'Social' . DS . 'pinConfig.php' );
}
if ( !class_exists('SAP_Instagram') ) {
	include ( CLASS_PATH . 'Social' . DS . 'instaConfig.php' );
}
if ( !class_exists('SAP_Reddit') ) {
	include ( CLASS_PATH . 'Social' . DS . 'redditConfig.php' );
}
if ( !class_exists('SAP_Youtube') ) {
	include ( CLASS_PATH . 'Social' . DS . 'youtubeConfig.php' );
}
if ( !class_exists('SAP_Blogger') ) {
	include ( CLASS_PATH . 'Social' . DS . 'bloggerConfig.php' );
}

//Object of social classed
$facebook = new SAP_Facebook();
$linkedin = new SAP_Linkedin();
$tumblr = new SAP_Tumblr();
$google_business = new SAP_Gmb();
$pinterest = new SAP_Pinterest();
$instagram = new SAP_Instagram();
$reddit = new SAP_Reddit();
$youtube = new SAP_Youtube();
$blogger = new SAP_Blogger();
$common = new Common();
?>

<!-- Content Wrapper. Contains page content -->
<div class="content-wrapper">
	<!-- Content Header (Page header) -->
	<section class="content-header">
		<h1>
			<span class="d-flex flex-wrap align-items-center">
				<div class="page-title-icon settings-title-iocn"></div>
				Settings
			</span>
		</h1>
	</section>
	<!-- Main content -->
	<section class="content">
		<!-- Info boxes -->
		<div class="row">
			<div class="col-md-12">
				<?php
				echo $this->flash->renderFlash();

				//Active tab check
				$active_tab = !empty($_SESSION['sap_active_tab']) ? $_SESSION['sap_active_tab'] : '';


				if( !in_array($active_tab, $networks) ) {
					$active_tab = '';
				} ?>
				<!-- Custom Tabs -->
				<div class="nav-tabs-custom settings--tabs-custom">
					<ul class="nav nav-tabs">
						<li class="<?php echo ( empty($active_tab) || $active_tab == "general" ) ? "active" : "" ?>"><a href="#general" data-toggle="tab">General</a></li>
						
						<?php
								
						foreach ( $networks as $key => $network ) {
							switch ($network) {
								case 'facebook':
									$label = $sap_common->lang('network_label_fb');
									break;
								case 'twitter':
									$label = $sap_common->lang('network_label_twitter');
									break;
								case 'linkedin':
									$label = $sap_common->lang('network_label_li');
									break;
								case 'tumblr':
									$label = $sap_common->lang('network_label_tumblr');
									break;
								case 'pinterest':
									$label = $sap_common->lang('network_label_pinterest');
									break;
								case 'gmb':
									$label = $sap_common->lang('network_label_gmb');
									break;
								case 'reddit':
									$label = $sap_common->lang('network_label_reddit');
									break;	
								case 'blogger':
									$label = $sap_common->lang('network_label_blogger');
									break;
								case 'youtube':
									$label = $sap_common->lang('network_label_youtube');
									break;
								case 'instagram':
									$label = $sap_common->lang('network_label_insta');
									break;
							}

							$class = ( $active_tab == $network ) ? "active" : "";
							echo '<li class="' . $class . '"><a href="#' . $network . '" data-toggle="tab">' . $label . '</a></li>';
						} ?>
						
					</ul>

					<div class="tab-content tab-content-settings">
						<div class="tab-pane <?php echo ( empty($active_tab) || $active_tab == "general" ) ? "active" : "" ?>" id="general">
							<div class="box box-primary">

								<!-- <div class="box-header sap-settings-box-header">General Settings</div> -->
								<!-- /.box-header -->
								<!-- form start -->

								<form class="form-horizontal" action="<?php echo SAP_SITE_URL . '/settings/save/'; ?>" method="POST" id="sap-general-settings-form">

									<?php
									//Get SAP options which stored
									$sap_general_options = $this->get_user_setting('sap_general_options');
									?>
									<div class="box-body">
										<div class="sap-box-inner">

											<div class="form-group">
												<label for="schedule_wallpost_option" class="col-sm-3 control-label">Timezone</label>
												<div class="col-sm-6 general-timezone-wrap">
													<select name="sap_general_options[timezone]" id="schedule_wallpost_option" class="form-control sap_select">
														<option value=''>Select your TIMEZONE</option>
														<?php
														//Get all schedule time
														$timezones_options = $this->get_timezones();

														foreach ($timezones_options as $key => $option) {
															echo '<option value="' . $option['zone_name'] . '" ' . (!empty($sap_general_options['timezone']) && $sap_general_options['timezone'] == $option['zone_name'] ? 'selected="selected"' : '' ) . ' >' . $option['zone_name'] . '</option>';
														}
														?>
													</select>
												</div>
											</div>
											<div class="form-group google-analytics-campaign-tracking-wrap">
												<label class="col-sm-3 control-label">Google Analytics Campaign Tracking</label>
												<div class="col-sm-9 general-timezone-wrap">
													<input id="google_campaign_tracking" type="checkbox" class="tgl tgl-ios" name="sap_general_options[google_campaign_tracking]" value="1" <?php echo (!empty($sap_general_options['google_campaign_tracking']) ) ? 'checked' : ''; ?>>
													<label class="tgl-btn float-right-cs-init" for="google_campaign_tracking"></label>
													<p>Enable campaign tracking if you want to see how much traffic is generated by Social Auto Poster.</p>
												</div>
											</div>

											<div class="form-group">
												<label class="col-sm-3 control-label">Enable Social Posting Logs</label>
												<div class="col-sm-9">
													<input id="social_posting_logs" type="checkbox" class="tgl tgl-ios" name="sap_general_options[social_posting_logs]" value="1" <?php echo (!empty($sap_general_options['social_posting_logs']) ) ? 'checked' : ''; ?>>
													<label class="tgl-btn float-right-cs-init" for="social_posting_logs"></label>
													<p>Enable this to store your social posting activities into the database which can be viewed from "Logs" section.</p>
												</div>
											</div>		

											<div class="form-group">
												<label class="col-sm-3 control-label">Enable Timestamp Link</label>
												<div class="col-sm-9">
													<input id="timestamp_link" type="checkbox" class="tgl tgl-ios" name="sap_general_options[timestamp_link]" value="1" <?php echo (!empty($sap_general_options['timestamp_link']) ) ? 'checked' : ''; ?>>
													<label class="tgl-btn float-right-cs-init" for="timestamp_link"></label>
													<p>Enable this to send timestamp with Social Link post.</p>
												</div>
											</div>									
										</div>

									</div>

									<div class="box-footer">
										<div class="">
											<button type="submit" name="sap_general_submit" class="btn btn-primary sap-general-submit"><i class="fa fa-inbox"></i> Save</button>
										</div>
									</div>
								</form>
							</div>
						</div>

						<?php
					
						foreach ( $networks as $key => $network ) {
				
							include_once( SAP_APP_PATH . 'view/Settings/' . ucwords($network) . '-settings.php' );

						} ?>
						
						<!-- /.tab-pane -->
						<span class="sap-loader">
							<div class="sap-loader-sub">
								<div class="sap-loader-img"></div>
							</div>
						</span>
					</div>
					<!-- /.tab-content -->
				</div>
				<!-- nav-tabs-custom -->
			</div>
		</div>
		<!-- /.row -->
	</section>
</div>

<?php
unset($_SESSION['sap_active_tab']);
include'footer.php';
?>
