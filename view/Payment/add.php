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
		<h1><div class="plus-icon"></div><p><?php echo $sap_common->lang('add_payment'); ?><small></small></p></h1>
	</section>

	<section class="content">
		<?php
		echo $this->flash->renderFlash(); ?>

		<form class="add-payment-form" name="new-member" id="add-payment" method="POST" enctype="multipart/form-data" action="<?php echo SAP_SITE_URL . '/payments/save/'; ?>">

			<div class="box box-primary">
				<div class="box-header with-border">
					<h3 class="box-title"><?php echo $sap_common->lang('payment_details'); ?></h3>
				</div>

				<div class="box-body margin-bottom-30">
					<div class=" row">
							<div class="col-md-6 form-group">
								<label class=""><?php echo $sap_common->lang('select_customer'); ?><span class="astric">*</span></label>
								<div class="">								

									<select name="user_id" id="user_id" class="form-control">
										<option value=""><?php echo $sap_common->lang('select_customer'); ?></option>
										<?php 
										if( !empty($customers) ) {
											
											foreach ( $customers as $key => $customer ) { ?>
												<option value="<?php echo $customer['id'] ?>"><?php echo $customer['first_name'].' '.$customer['last_name'] ?> </option>
										<?php } } ?>
									</select>
								</div>
							</div>
						<div class="col-md-6 form-group">
							<label class=""><?php echo $sap_common->lang('membership_level'); ?></label>
							<div class="">

								<select readonly="readonly" name="plan_id" id="plan_id" class="form-control">
									<option value=""><?php echo $sap_common->lang('select_membership_level'); ?></option>
								
								</select>
							</div>
						</div>
					</div>

					<div class="row">
						<div class="col-md-6 form-group">
							<label class=""><?php echo $sap_common->lang('amount'); ?><span class="astric">*</span></label>
							<div class="">
								<input type="number" name="amount" class="form-control" min="1">
							</div>
						</div>
						<div class="col-md-6 form-group">
							<label class=""><?php echo $sap_common->lang('payment_date'); ?><span class="astric">*</span></label>
							<div class="">
								<input type="text" name="payment_date" id="payment_date" class="form-control datepicker" value="<?php echo date('Y-m-d') ?>">
							</div>
						</div>
					</div>

					<div class=" row">
						<div class="col-md-6 form-group">
							
								<label class=""><?php echo $sap_common->lang('transaction_id'); ?></label>
								<div class="">
									<input type="text" name="transaction_id" class="form-control">
								</div>
							
						</div>
						<div class="col-md-6 form-group">
							<label class=""><?php echo $sap_common->lang('payment_status'); ?><span class="astric">*</span></label>
							<div class="">
								<select name="status" id="status" class="form-control">
									<option value=""><?php echo $sap_common->lang('payment_status'); ?></option>
									<option value="1"><?php echo $sap_common->lang('completed'); ?></option>
									<option value="0"><?php echo $sap_common->lang('pending'); ?></option>
									<option value="2"><?php echo $sap_common->lang('failed'); ?></option>
									<option value="3"><?php echo $sap_common->lang('refunded'); ?></option>
								</select>
							</div>
						</div>
					</div>
					<div class="row">
						<div class="sap-mt-1 col-md-12 form-group">
							<input type="hidden" name="form-submitted" value="1">
							<button type="submit" name="sap_add_member_submit" class="btn btn-primary"><?php echo $sap_common->lang('add_payment'); ?></button>
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

