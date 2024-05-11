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

include SAP_APP_PATH . 'header.php';

include SAP_APP_PATH . 'sidebar.php';

?>
<!-- Content Wrapper. Contains page content -->
<div class="content-wrapper">
	<section class="content-header">
		<h1><div class="plus-icon edit-icon"></div><p><?php echo $sap_common->lang('edit_membership_level'); ?><small></small></p></h1>
	</section>

	<section class="content">
		<?php
		echo $this->flash->renderFlash(); ?>
		<form class="edit-plan-form" id="edit-plan" method="POST" enctype="multipart/form-data" action="<?php echo SAP_SITE_URL . '/plan/update/'; ?>">
			<div class="box box-primary">
				<div class="box-header with-border">
					<div class="row d-flex flex-wrap align-items-center">
						<div class="col-md-6 form-group"><h3 class="box-title"><?php echo $sap_common->lang('membership_level_details'); ?></h3></div>
						<div class="col-md-6 form-group" bis_skin_checked="1">
							<div style="display: inline-block;float: right;">
								<div class="d-flex align-items-center justify-content-end status-text" bis_skin_checked="1">
									<label class="control-label"><?php echo $sap_common->lang('status'); ?>:</label>
									<div class="" bis_skin_checked="1">
										<input type="checkbox" class="tgl tgl-ios" name="status" id="status" <?php echo ($plan_data->status == '1') ? "checked='checked'" : ''; ?> value="1">
										<label class="tgl-btn float-right-cs-init" for="status"></label>
									</div>
								</div>
							</div>
						</div>
					</div>
				</div>

				<?php
	            $plan_id = $match['params']['id'];
	            $plan_data = $this->get_plan( $plan_id, true );
	           
	            if ( empty($plan_data) ) {
	                header("Location:" . SAP_SITE_URL . "/plans/");
	                exit;
	            } ?>

				<div class="box-body">
					<div class="row edit-plan-inner d-flex flex-wrap">
						<div class="col-md-6 col-xs-12">
							<div class=" form-group">
								<label class=""><?php echo $sap_common->lang('membership_level'); ?><span class="astric">*</span></label>
								<input type="text" class="form-control" name="sap_name" id="sap_name" value="<?php echo ( !empty($plan_data->name) ? $plan_data->name : '' ); ?>" placeholder="<?php echo $sap_common->lang('name'); ?>" />
								<p class="description"><?php echo $sap_common->lang('name_of_the_membership_level'); ?></p>
							</div>
							<div class=" form-group">
								<label><?php echo $sap_common->lang('price'); ?><span class="astric">*</span></label>
								<input type="number" class="form-control" name="sap_price" id="sap_price" value="<?php echo ( !empty($plan_data->price) ? $plan_data->price : '' ); ?>" min="0" step="0.5" placeholder="<?php echo $sap_common->lang('price'); ?>" />
								<p class="description"><?php echo $sap_common->lang('price_of_membership_msg'); ?> </p>
							</div>					
						
							<div class="form-group">
								<label><?php echo $sap_common->lang('duration'); ?></label>
								<div class="d-flex row">
									<div class="col-md-10">
										<input type="number"  step="1" class="form-control" name="subscription_expiration_days" id="subscription_expiration_days" value="<?php echo  $plan_data->subscription_expiration_days; ?>"  />
										<p class="description"><?php echo $sap_common->lang('Length_of_time'); ?></p>
									</div>
									<div class="col-md-2">
										<input readonly="readonly"  type="text" class="form-control expiration-day-label" name="" id="" value="Days" />
									</div>
								</div>
							</div>
							

						</div>
						<div class="col-md-6 col-xs-12">
							<div class="description-textarea">
								<div class="form-group">
									<label><?php echo $sap_common->lang('description'); ?></label>
									<textarea class="form-control" rows="7" name="sap_description" id="sap_description" placeholder="<?php echo $sap_common->lang('description'); ?>"><?php echo ( !empty($plan_data->description) ? $plan_data->description : '' ); ?></textarea>
									<p class="description"><?php echo $sap_common->lang('membership_level_description'); ?></p>
								</div>
							</div>
						</div>
					</div>
					
					<div class=" border-top">
						<div class="row">
							<div class="col-md-12 form-group">
								<h3><strong><?php echo $sap_common->lang('networks'); ?></strong></h3>
								<?php
								$networks = !empty($plan_data->networks) ? unserialize($plan_data->networks) : array(); ?>

								<div class="d-flex  plan-network-wrap">
									<div class="sap-plan-network sap-mb-1">
										<div class="d-flex align-items-center justify-content-between">
											<label for="sap_network_fb"><?php echo $sap_common->lang('network_label_fb'); ?></label>
											<input id="sap_network_fb" type="checkbox" class="tgl tgl-ios" name="sap_network[]" value="facebook" <?php if( in_array('facebook', $networks) ) echo 'checked'; ?> />
											<label class="tgl-btn float-right-cs-init" for="sap_network_fb"></label>
										</div>
									</div>
									<div class="sap-plan-network sap-mb-1">
										<div class="d-flex align-items-center justify-content-between">
											<label for="sap_network_tw"><?php echo $sap_common->lang('network_label_twitter'); ?></label>
											<input id="sap_network_tw" type="checkbox" class="tgl tgl-ios" name="sap_network[]" value="twitter" <?php if( in_array('twitter', $networks) ) echo 'checked'; ?> />
											<label class="tgl-btn float-right-cs-init" for="sap_network_tw"></label>
										</div>
									</div>
									<div class="sap-plan-network sap-mb-1">
										<div class="d-flex align-items-center justify-content-between">
											<label for="sap_network_linkedin"><?php echo $sap_common->lang('network_label_li'); ?></label>
											<input id="sap_network_linkedin" type="checkbox" class="tgl tgl-ios" name="sap_network[]" value="linkedin" <?php if( in_array('linkedin', $networks) ) echo 'checked'; ?> />
											<label class="tgl-btn float-right-cs-init" for="sap_network_linkedin"></label>
										</div>
									</div>
									<div class="sap-plan-network sap-mb-1">
										<div class="d-flex align-items-center justify-content-between">
											<label for="sap_network_tumblr"><?php echo $sap_common->lang('network_label_tumblr'); ?></label>
											<input id="sap_network_tumblr" type="checkbox" class="tgl tgl-ios" name="sap_network[]" value="tumblr" <?php if( in_array('tumblr', $networks) ) echo 'checked'; ?> />
											<label class="tgl-btn float-right-cs-init" for="sap_network_tumblr"></label>
										</div>
									</div>
									<div class="sap-plan-network sap-mb-1">
										<div class="d-flex align-items-center justify-content-between">
											<label for="sap_network_pin"><?php echo $sap_common->lang('network_label_pinterest'); ?></label>
											<input id="sap_network_pin" type="checkbox" class="tgl tgl-ios" name="sap_network[]" value="pinterest" <?php if( in_array('pinterest', $networks) ) echo 'checked'; ?> />
											<label class="tgl-btn float-right-cs-init" for="sap_network_pin"></label>
										</div>
									</div>
									<div class="sap-plan-network sap-mb-1">
										<div class="d-flex align-items-center justify-content-between">
											<label for="sap_network_gmb"><?php echo $sap_common->lang('network_label_gmb'); ?></label>
											<input id="sap_network_gmb" type="checkbox" class="tgl tgl-ios" name="sap_network[]" value="gmb" <?php if( in_array('gmb', $networks) ) echo 'checked'; ?> />
											<label class="tgl-btn float-right-cs-init" for="sap_network_gmb"></label>
										</div>
									</div>

									<div class="sap-plan-network sap-mb-1">
										<div class="d-flex align-items-center justify-content-between">
											<label for="sap_network_reddit">
												<?php echo $sap_common->lang('network_label_reddit'); ?></label>
											<input id="sap_network_reddit" type="checkbox" class="tgl tgl-ios" name="sap_network[]" value="reddit" <?php if( in_array('reddit', $networks) ) echo 'checked'; ?> />
											<label class="tgl-btn float-right-cs-init" for="sap_network_reddit"></label>
										</div>
									</div>
									
									<div class="sap-plan-network sap-mb-1">
										<div class="d-flex align-items-center justify-content-between">
											<label for="sap_network_insta"><?php echo $sap_common->lang('network_label_insta'); ?></label>
											<input id="sap_network_insta" type="checkbox" class="tgl tgl-ios" name="sap_network[]" value="instagram" <?php if( in_array('instagram', $networks) ) echo 'checked'; ?> />
											<label class="tgl-btn float-right-cs-init" for="sap_network_insta"></label>
										</div>
									</div>
									
									<div class="sap-plan-network sap-mb-1">
										<div class="d-flex align-items-center justify-content-between">
											<label for="network_label_youtube"><?php echo $sap_common->lang('network_label_youtube'); ?></label>
											<input id="network_label_youtube" type="checkbox" class="tgl tgl-ios" name="sap_network[]" value="youtube" <?php if( in_array('youtube', $networks) ) echo 'checked'; ?> />
											<label class="tgl-btn float-right-cs-init" for="network_label_youtube"></label>
										</div>
									</div>

									<div class="sap-plan-network sap-mb-1">
										<div class="d-flex align-items-center justify-content-between">
											<label for="sap_network_blogger"><?php echo $sap_common->lang('network_label_blogger'); ?></label>
											<input id="sap_network_blogger" type="checkbox" class="tgl tgl-ios" name="sap_network[]" value="blogger" <?php if( in_array('blogger', $networks) ) echo 'checked'; ?> />
											<label class="tgl-btn float-right-cs-init" for="sap_network_blogger"></label>
										</div>
									</div>

								</div>
							</div>
						</div>
					</div>

					<div class="row">
						<div class="sap-mt-1 col-md-12 form-group">
							<input type="hidden" name="form-updated" value="1" />
							<input type="hidden" value="<?php echo ( !empty($plan_id) ? $plan_id : 0 ); ?>" name="id" />
							<button type="submit" name="sap_update_plan_submit" class="btn btn-primary"><?php echo $sap_common->lang('updat_membership_level'); ?></button>
						</div>
					</div>
				</div>
			</div>
		</form>
	</section>
</div>
<?php
include'footer.php';
?>