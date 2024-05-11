<?php  

/* Check the absolute path to the Social Auto Poster directory. */
if ( !defined( 'SAP_APP_PATH' ) ) {
    // If SAP_APP_PATH constant is not defined, perform some action, show an error, or exit the script
    // Or exit the script if required
    exit();
}

include SAP_APP_PATH . 'header.php';
include SAP_APP_PATH . 'sidebar.php';

global $sap_common;
$common = new Common();

// Get SAP options which stored
$smtp_setting = $this->get_options('sap_smtp_setting');

$enable		= isset( $smtp_setting['enable'] ) ? $smtp_setting['enable'] : '';
$from_email	= isset( $smtp_setting['from_email'] ) ? $smtp_setting['from_email'] : '';
$from_name	= isset( $smtp_setting['from_name'] ) ? $smtp_setting['from_name'] : '';
$host		= isset( $smtp_setting['host'] ) ? $smtp_setting['host'] : '';
$enc_type	= isset( $smtp_setting['enc_type'] ) ? $smtp_setting['enc_type'] : 'None';
$port		= isset( $smtp_setting['port'] ) ? $smtp_setting['port'] : '';
$username	= isset( $smtp_setting['username'] ) ? $smtp_setting['username'] : '';
$password	= isset( $smtp_setting['password'] ) ? $smtp_setting['password'] : '';
?>

<!-- Content Wrapper. Contains page content -->
<div class="content-wrapper">
	<!-- Content Header (Page header) -->
	<section class="content-header">
		<h1><?php echo $sap_common->lang('smtp_settings'); ?></h1>
	</section>
	 <!-- Main content -->
	<section class="content">
		<?php
		echo $this->flash->renderFlash(); ?>

		<div class="tab-content">
			<div class="box box-primary">
				<div class="box-header"></div>
				<!-- /.box-header -->
				<!-- form start -->
				<form class="form-horizontal" action="<?php echo SAP_SITE_URL . '/settings/save/'; ?>" method="POST" id="sap-smtp-settings-form">
					<div class="box-body">
						<div class="sap-box-inner">
							<div class="form-group">
								<label for="" class="col-sm-3 col-md-3 control-label"><?php echo $sap_common->lang('enable_disable'); ?></label>
								<div class="col-sm-9 col-md-8 col-lg-6">
									<div class="checkbox-inline">
										<label class="auth-option">
											<input name="sap_smtp[enable]" value="no" type="hidden" />
											<input name="sap_smtp[enable]" value="yes" type="checkbox" class="" <?php if( 'yes' == $enable ) echo "checked"; ?> />
											 <?php echo $sap_common->lang('smtp_en_checkbox'); ?>
										</label>
									</div>
								</div>
							</div>

							<div class="form-group">
								<label for="" class="col-sm-3 col-md-2 control-label"><?php echo $sap_common->lang('from_email'); ?></label>
								<div class="col-sm-9 col-md-8 col-lg-6">
									<input name="sap_smtp[from_email]" value="<?php echo $from_email; ?>" type="text" class="form-control" />
								</div>
							</div>
							<div class="form-group">
								<label for="" class="col-sm-3 col-md-2 control-label"><?php echo $sap_common->lang('from_name'); ?></label>
								<div class="col-sm-9 col-md-8 col-lg-6">
									<input name="sap_smtp[from_name]" value="<?php echo $from_name; ?>" type="text" class="form-control" />
								</div>
							</div>
							<div class="form-group">
								<label for="" class="col-sm-3 col-md-2 control-label"><?php echo $sap_common->lang('smtp_host'); ?></label>
								<div class="col-sm-9 col-md-8 col-lg-6">
									<input name="sap_smtp[host]" value="<?php echo $host; ?>" type="text" class="form-control" />
								</div>
							</div>							
							
							<div class="form-group">
								<label for="" class="col-sm-3 col-md-2 control-label"><?php echo $sap_common->lang('smtp_port'); ?></label>
								<div class="col-sm-9 col-md-8 col-lg-6">
									<input name="sap_smtp[port]" value="<?php echo $port; ?>" type="text" class="form-control" />
								</div>
							</div>
							<div class="form-group">
								<label for="" class="col-sm-3 col-md-2 control-label"><?php echo $sap_common->lang('smtp_username'); ?></label>
								<div class="col-sm-9 col-md-8 col-lg-6">
									<input name="sap_smtp[username]" value="<?php echo $username; ?>" type="text" class="form-control" />
								</div>
							</div>
							<div class="form-group">
								<label for="" class="col-sm-3 col-md-2 control-label"><?php echo $sap_common->lang('smtp_pass'); ?></label>
								<div class="col-sm-9 col-md-8 col-lg-6">
									<input name="sap_smtp[password]" value="<?php echo $password; ?>" type="password" class="form-control" />
								</div>
							</div>
							<div class="form-group">
								<label class="col-sm-3 col-md-2"></label>
								<div class="col-sm-9 col-md-6">
									<button type="submit" name="sap_smtp_submit" class="btn btn-primary sap-smtp-submit"><?php echo $sap_common->lang('save_settings'); ?></button>
								</div>
							</div>
						</div>
					</div>
				</form>
			</div>
		</div>
	</section>
</div>
<?php
include'footer.php';
?>