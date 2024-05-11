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
?>
<link rel="stylesheet" href="<?php echo SAP_SITE_URL . '/assets/css/jquery-ui.css' ?>" >
<?php
include SAP_APP_PATH . 'header.php';

include SAP_APP_PATH . 'sidebar.php';


?>


<!-- Content Wrapper. Contains page content -->
<div class="content-wrapper">
	<section class="content-header">
		<h1><div class="plus-icon edit-icon"></div><p><?php echo $sap_common->lang('edit_coupon'); ?><small></small></p></h1>
	</section>

	<section class="content">

		<?php echo $this->flash->renderFlash(); ?>

		<form class="edir-payment-form" name="edit-coupon" id="edit-coupon" method="POST" enctype="multipart/form-data" action="<?php echo SAP_SITE_URL . '/coupons/update/'; ?>">
			<div class="box box-primary margin-bottom-30">
				<div class="box-header with-border">
					<h3 class="box-title"><?php echo $sap_common->lang('coupon_details'); ?></h3>
				</div>

				<div class="box-body">
					<div class="row ">
						<div class="col-md-6 form-group">
							<label class=""><?php echo $sap_common->lang('coupon_code'); ?><span class="astric">*</span></label>
							<div class="">								

								<input type="text" name="coupon_code" class="form-control" value="<?php echo $coupon_details->coupon_code; ?>">
							</div>
						</div>
						<div class="col-md-6 form-group">
							
								<label class=""><?php echo $sap_common->lang('coupon_type'); ?><span class="astric">*</span></label>
								<div class="">

									<select name="coupon_type" id="coupon_type" class="form-control" onchange="return checkPercentageAmountLength('coupon_amount', 'coupon_type', 'percentage_discount')">
										<option value=""><?php echo $sap_common->lang('select_coupon_type'); ?></option>
										<?php
											foreach($coupon_type as $key => $value) {
										?>
											<option value="<?php echo $key; ?>" <?php if( $key == $coupon_details->coupon_type ) { echo "selected"; } ?>><?php echo $value; ?></option>
										<?php
											}
										?>
									</select>
								</div>
						</div>
					</div>

					<div class="row">
						<div class="col-md-6 form-group">
								<label class=""><?php echo $sap_common->lang('coupon_amount'); ?><span class="astric">*</span></label>
								<div class="">
									<input type="number" name="coupon_amount" id="coupon_amount" onkeyup="return checkPercentageAmountLength('coupon_amount', 'coupon_type', 'percentage_discount')" class="form-control" min="1" value="<?php echo $coupon_details->coupon_amount; ?>" step=".01">
								</div>
						</div>
						<div class="col-md-6 form-group">
								<label class=""><?php echo $sap_common->lang('coupon_expiry_date'); ?></label>
								<div class="">
									<input type="text" name="coupon_expiry_date" id="coupon_expiry_date" class="form-control datepicker" value="<?php echo date('Y-m-d',strtotime($coupon_details->coupon_expiry_date)) ?>">
								</div>
						</div>
					</div>

					<div class="row">
						<div class="col-md-6 form-group">
								<label class=""><?php echo $sap_common->lang('coupon_description'); ?></label>
								<div class="">
									<textarea rows="3" name="coupon_description" class="form-control"><?php echo $coupon_details->coupon_description; ?></textarea>
								</div>
						</div>
						<div class="col-md-6 form-group">
							<label class=""><?php echo $sap_common->lang('coupon_status'); ?></label>
							<div class="">
								<select name="coupon_status" id="coupon_status" class="form-control">
									<?php
										foreach($coupon_status as $key => $value) {
									?>
										<option value="<?php echo $key; ?>" <?php if( $key == $coupon_details->coupon_status ) { echo "selected"; } ?>><?php echo $value; ?></option>
									<?php
										}
									?>
								</select>
							</div>
						</div>
					</div>
					<div class="row">
						<div class="sap-mt-1 col-md-12 form-group">
							<input type="hidden" name="coupon_id" value="<?php echo $coupon_details->id ?>">
							<button type="submit" name="coupon_submit" class="btn btn-primary"><?php echo $sap_common->lang('save'); ?></button>
						</div>
					</div>
				</div>
			</div>
		</form>
	</section>
</div>

<script src="<?php echo SAP_SITE_URL . '/assets/js/jquery.min.js' ?>" type="text/javascript"></script>

<?php
include'footer.php';
?>
<script src="<?php echo SAP_SITE_URL . '/assets/js/jquery-ui.js' ?>"></script>
<script src="<?php echo SAP_SITE_URL . '/assets/js/custom.js'; ?>"></script>

