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


$member_id = $match['params']['id'];

$membership_data = $this->user->get_user_subscription_details($member_id);	
$payment_data 	 = $this->payments->user_payments_history($member_id);	


?>

<!-- Content Wrapper. Contains page content -->
<div class="content-wrapper">
	<section class="content-header">
		<h1><div class="plus-icon edit-icon"></div><p><?php echo $sap_common->lang('edit_customer'); ?><small></small></p></h1>
	</section>

	<section class="content" style=" padding-top: 0;">
		<?php
		echo $this->flash->renderFlash(); ?>

		<form class="edit-member-form" name="edit-member" id="edit-member" method="POST" enctype="multipart/form-data" action="<?php echo SAP_SITE_URL . '/member/update/'; ?>">

			<div class="box box-primary">
				<div class="box-header with-border">
					<h3 class="box-title"><?php echo $sap_common->lang('customer_details'); ?></h3>
				</div>

				<?php

				$member_data = $this->get_member( $member_id, true );
				if( !empty( $member_data ) ){              

					if ( empty($member_data) ) {
						header("Location:" . SAP_SITE_URL . "/members/");
						exit;
					} ?>

					<div class="box-body">
						<div class="row ">
							<div class="col-md-6 form-group">
								
								<label><?php echo $sap_common->lang('first_name'); ?><span class="astric">*</span></label>
								<div>
									<input type="text" class="form-control" name="sap_firstname" id="sap_firstname" value="<?php echo !empty( $member_data->first_name ) ? $member_data->first_name : ''; ?>" placeholder="<?php echo $sap_common->lang('first_name'); ?>" />
								</div>
								
							</div>
							<div class="col-md-6 form-group">
								<label><?php echo $sap_common->lang('last_name'); ?></label>
								<div class="">
									<input type="text" class="form-control" name="sap_lastname" id="sap_lastname" value="<?php echo !empty( $member_data->last_name ) ? $member_data->last_name : ''; ?>" placeholder="<?php echo $sap_common->lang('last_name'); ?>" />
								</div>
							</div>
						</div>

						<div class="row">
							<div class="col-md-6 form-group">
								<label><?php echo $sap_common->lang('email'); ?><span class="astric">*</span></label>
								<div class="">
									<input type="text" class="form-control" name="sap_email" id="sap_email" value="<?php echo !empty( $member_data->email ) ? $member_data->email : ''; ?>" placeholder="<?php echo $sap_common->lang('email'); ?>" />
								</div>
							</div>
							<div class="col-md-6 form-group">
								<label><?php echo $sap_common->lang('role'); ?></label>
								<div class="">
									<?php
									$role = isset( $member_data->role ) ? $member_data->role : ''; ?>

									<select name="sap_role" class="form-control sap_role">
										<option value="user" <?php if( 'user' == $role ) echo 'selected="selected"'; ?>><?php echo $sap_common->lang('user'); ?></option>
										<option value="superadmin" <?php if( 'superadmin' == $role ) echo 'selected="selected"'; ?>><?php echo $sap_common->lang('admin'); ?></option>
									</select>
								</div>
							</div>
						</div>

						<div class="row">
							<div class="col-md-6 form-group">
								<label><?php echo $sap_common->lang('password'); ?></label>
								<div class="">
									<input type="password" class="form-control" name="sap_password" id="sap_password" value="" placeholder="<?php echo $sap_common->lang('password'); ?>" />
								</div>
							</div>
							<div class="col-md-6 form-group">
								<label><?php echo $sap_common->lang('re_password'); ?></label>
								<div class="">
									<input type="password" class="form-control" name="sap_repassword" id="sap_repassword" value="" placeholder="<?php echo $sap_common->lang('re_password_plh'); ?>" />
								</div>
							</div>
						</div>

						
						<div class="row sap_plan">
							<div class="col-md-6 form-group d-flex">
								<label style="margin: 0 50px 0 0"><?php echo $sap_common->lang('status'); ?></label>
								<div class="">
									<input type="checkbox" class="tgl tgl-ios" name="sap_status" id="sap_status" <?php echo ($member_data->status == '1') ? "checked='checked'" : ''; ?> value="1" />
									<label class="tgl-btn float-right-cs-init" for="sap_status"></label>
								</div>
							</div>
						</div>

						<div class="sap-mt-1 form-group">
							<input type="hidden" value="<?php echo ( !empty($member_id) ? $member_id : 0 ); ?>" name="id" />
							<input type="hidden" name="form-updated" value="1">
							<button type="submit" name="sap_update_member_submit" class="btn btn-primary"><?php echo $sap_common->lang('update_customer'); ?></button>
						</div>
					</div>
				<?php }else{ ?>
					<div class="box-body">
						<p><b><?php echo $sap_common->lang('user_no_exist_msg'); ?> </b></p>
					</div>
				<?php } ?>
			

		</form>
	</section>


	<?php if( !empty( $member_data ) ){ ?>
		<section class="content" style=" padding-top: 0;">	
			<div class="box box-primary membership_details-table">
				<div class="box-header ">
					<h3 class="box-title"><?php echo $sap_common->lang('membership_details'); ?></h3>
				</div>
				<div class="box-body">

					<?php 
					if( !empty( $membership_data )){ 
						?>
						<table class="table table-striped table-bordered">
							<thead>
								<tr>
									<th><?php echo $sap_common->lang('membership_level'); ?></th>
									<th><?php echo $sap_common->lang('allowed_network'); ?></th>
									<th><?php echo $sap_common->lang('membership_status'); ?></th>		
									<th><?php echo $sap_common->lang('recurring'); ?></th>
									<th><?php echo $sap_common->lang('expiration_date'); ?></th>
								</tr>
							</thead>
							<tbody>
								<td><?php echo $membership_data->name ?></td>
								<td>
									<?php 

									$li_content = '';
									$networks = unserialize($membership_data->networks);
									if( !empty( $networks ) ){
										foreach ($networks as $key => $network) {
											// Convert to lowercase
											$lowercaseString = strtolower($network);

											// Replace spaces with hyphens
											$finalkey = str_replace(' ', '-', $lowercaseString);
											$li_content .= '<div class="'. $finalkey .' finalnetwork">'. $network .'</div>';
										}
										echo rtrim($li_content);  
									}
									?>							
								</td>
								<td>
									<?php 
										$planstatus = get_membership_status_label( $membership_data->membership_status);
										if($membership_data->membership_status == '1'){
											echo '<div class="plan-active">'. $sap_common->lang('active') .'</div>';
										}else{
											echo '<div class="plan-inactive">'. $sap_common->lang('in-active') .'</div>';
										} 
									?>
								</td>
								<td><?php echo get_recuring_status_label( $membership_data->recurring) ?></td>
								<td><?php echo sap_get_membership_expiration_date( $membership_data->expiration_date) ?></td>

							</tbody>

							<tfoot>
								<tr>
									<th><?php echo $sap_common->lang('membership_level'); ?></th>
									<th><?php echo $sap_common->lang('allowed_network'); ?></th>
									<th><?php echo $sap_common->lang('membership_status'); ?></th>	
									<th><?php echo $sap_common->lang('recurring'); ?></th>
									<th><?php echo $sap_common->lang('expiration_date'); ?></th>
								</tr>
							</tfoot>
						</table>
					<?php }
					else{
						echo '<p><b>'.$sap_common->lang('customer_membership_purchased').'</b></p>';
					} ?>
				</div>	
			</div>




			<div class="box box-primary membership_details-table">	

				<div class="box-header ">
					<h3 class="box-title"><?php echo $sap_common->lang('recent_payments'); ?></h3>
				</div>
				<div class="box-body">
					<?php if( !empty( $payment_data ) ){ ?>
						<table id="user_payment_histrory" class="display table table-bordered table-striped member-list" width="100%">
							<thead>
								<tr>
									<th><?php echo $sap_common->lang('number'); ?></th>
									<th><?php echo $sap_common->lang('membership_level'); ?></th>
									<th><?php echo $sap_common->lang('payment_gateway'); ?></th>
									<th><?php echo $sap_common->lang('transaction_id'); ?></th>
									<th><?php echo $sap_common->lang('payment_status'); ?></th>
									<th><?php echo $sap_common->lang('coupon_name'); ?></th>
									<th><?php echo $sap_common->lang('amount'); ?></th>
									<th><?php echo $sap_common->lang('discount_amount'); ?></th>
									<th><?php echo $sap_common->lang('total_amount'); ?></th>
									<th><?php echo $sap_common->lang('payment_date'); ?></th>
									<th><?php echo $sap_common->lang('invoice'); ?></th>
								</tr>
							</thead>
							<tbody>
								<?php 
								$number = 1;
								foreach ($payment_data as $key => $payment) { 

									$test_mode = $this->settings->get_options('stripe_test_mode');
									$stripe_endpoint = ( $test_mode == 'yes' ) ? 'https://dashboard.stripe.com/test/' : 'https://dashboard.stripe.com/';

									$transaction_id = $payment->transaction_id;

									if( strpos($transaction_id, 'sub_') !== false ){
										$transaction_id = '<a target="__blank" href="'.$stripe_endpoint.'subscriptions/'.$payment->transaction_id.'">'.$payment->transaction_id.'</a>';
									}
									elseif( strpos($transaction_id, 'ch_') !== false  ){
										$transaction_id = '<a href="'.$stripe_endpoint.'payments/'.$payment->transaction_id.'">'.$payment->transaction_id.'</a>';
									}								
									?>
									<tr>
										<td><?php echo $number ?></td>
										<td><?php echo $payment->plan_name ?></td>
										<td><?php echo ucfirst($payment->gateway) ?></td>
										<td><?php echo $transaction_id ?></td>
										<td><?php echo get_payment_status_label($payment->payment_status) ?></td>
										<td><?php echo isset($payment->coupon_name) != '' || isset($payment->coupon_name) != null ? $payment->coupon_name : ""; ?></td>
										<td><?php echo "$".round($payment->amount,2) ?></td>
										<td><?php echo isset($payment->coupon_discount_amount) != '' || isset($payment->coupon_discount_amount) != null ? "$".round($payment->coupon_discount_amount,2) : "$0"; ?></td>
										<td><?php echo round($payment->amount,2) > round($payment->coupon_discount_amount,2) ? "$".round($payment->amount,2) - round($payment->coupon_discount_amount,2) : "$0"; ?></td>
										<td><?php echo sap_format_date($payment->payment_date,true) ?></td>
										<td><div>
											<?php
											echo '<a target="_blank"  class="view-Status" href="'.SAP_SITE_URL.'/payment-invoice/'.$payment->id.'">View</a>';
											?>
										</div>
										</td>
									</tr>
									<?php   
									$number++;
								}
								?>
							</tbody>
							<tfoot>
								<tr>
									<th><?php echo $sap_common->lang('number'); ?></th>
									<th><?php echo $sap_common->lang('membership_level'); ?></th>
									<th><?php echo $sap_common->lang('payment_gateway'); ?></th>
									<th><?php echo $sap_common->lang('transaction_id'); ?></th>
									<th><?php echo $sap_common->lang('payment_status'); ?></th>
									<th><?php echo $sap_common->lang('coupon_name'); ?></th>
									<th><?php echo $sap_common->lang('amount'); ?></th>
									<th><?php echo $sap_common->lang('discount_amount'); ?></th>
									<th><?php echo $sap_common->lang('total_amount'); ?></th>
									<th><?php echo $sap_common->lang('payment_date'); ?></th>
									<th><?php echo $sap_common->lang('invoice'); ?></th>
								</tr>
							</tfoot>
							<tbody></tbody>
						</table>
					<?php }
					else{
						echo '<p><b>'.$sap_common->lang('customer_not_made_payment').'</b></p>';
					} ?>
				</div>	
			</div>
		</section>	
	<?php } ?>

</div></div>


<script src="<?php echo SAP_SITE_URL . '/assets/js/jquery.min.js' ?>" type="text/javascript"></script>
<script src="<?php echo SAP_SITE_URL . '/assets/js/custom.js'; ?>"></script>
<?php
include'footer.php';
?>
