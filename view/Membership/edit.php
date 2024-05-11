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


$membership_id   = $match['params']['id'];
$membership_data = $this->get_membership_by_id( $membership_id, true );

$payment_data  = $this->payments->user_payments_history($membership_data->user_id);
$membership_status  = array(
	'1' => $sap_common->lang('active'),
	'0' => $sap_common->lang('pending'),	
);

$payment_gateway  = explode(',',$this->settings->get_options('payment_gateway'));


?>
<link rel="stylesheet" href="<?php echo SAP_SITE_URL . '/assets/css/jquery-ui.css' ?>" >
<!-- Content Wrapper. Contains page content -->
<div class="content-wrapper">
	<section class="content-header">
		<h1><div class="plus-icon edit-icon"></div><p><?php echo $sap_common->lang('edit_membership'); ?><small></small></p></h1>
	</section>

	<section class="content">
		<?php
		echo $this->flash->renderFlash(); ?>

		<form class="edit-membership-form" name="edit-membership" id="edit-membership" method="POST" enctype="multipart/form-data" action="<?php echo SAP_SITE_URL . '/membership/update/'; ?>">

			<div class="box box-primary margin-bottom-30">
				<div class="box-header with-border">
					<h3 class="box-title"><?php echo $sap_common->lang('membership_details'); ?></h3>
				</div>
				<?php

                if ( empty($membership_data) ) {
                    header("Location:" . SAP_SITE_URL . "/membership/");
                    exit;
                } ?>

				<div class="box-body">
					<input type="hidden" class="tgl tgl-ios" name="hidden_expiration_dt" id="hidden_expiration_dt" value="<?php echo $membership_data->expiration_date; ?>" />

					<div class="row">
						<div class="col-md-6 form-group">
								<label><?php echo $sap_common->lang('name'); ?></label>
								<div>
									<input type="text" class="form-control " name="name" id="name" value="<?php echo $membership_data->customer_name; ?>" disabled='disabled' />
								</div>
						</div>
						<div class="col-md-6 form-group">
								<label><?php echo $sap_common->lang('membership_level'); ?></label>
								<div>
									<input type="text" class="form-control " name="plan_name" id="plan_name" value="<?php echo $membership_data->name ?>" disabled='disabled' />
								</div>
						</div>
					</div>

					<div class="row">
						<div class="col-md-6 form-group">
								<label><?php echo $sap_common->lang('price'); ?></label>
								<div>
									<input type="text" class="form-control " name="price" id="price" value="<?php echo $membership_data->price; ?>" disabled='disabled' />
								</div>
						</div>
						<div class="col-md-6 form-group">
								<label><?php echo $sap_common->lang('duration'); ?></label>
								<div>
									<input type="text" class="form-control " name="subscription_expiration_days" id="subscription_expiration_days" value="<?php echo $membership_data->subscription_expiration_days ?>" disabled='disabled' />
								</div>
						</div>
					</div>

					<div class="row">
						<div class="col-md-6 form-group">
							<label for="membership_status"><?php echo $sap_common->lang('membership_status'); ?></label>
							<div>
								<div class="membership-status-inputs">

									<select class="form-control" name="membership_status" id="membership_status">
										<option><?php echo $sap_common->lang('select_membership_status'); ?></option>
										<?php
										foreach( $membership_status as $key => $stutus ){
											
											$selected = '';
											
											if($membership_data->membership_status == $key ){
												$selected = 'selected="selected"';
											}

											echo '<option value="'.$key.'" '.$selected.'> '.$stutus.' </option>';
										} 

										if( $membership_data->membership_status == '3' ){
											$selected = 'selected="selected"';
											echo '<option value="'.$membership_data->membership_status .'" '.$selected.'> '.$sap_common->lang('cancelled').' </option>';	
										}

										if( $membership_data->membership_status == '2' ){
											$selected = 'selected="selected"';
											echo '<option value="'.$membership_data->membership_status .'" '.$selected.'> '.$sap_common->lang('expired').' </option>';	
										}
										?>
									</select>

									

									<span class="tt_large" data-toggle="tooltip" data-placement="top" title="<?php echo $sap_common->lang('membership_status_tooltrip'); ?>"> <i class="fa fa-question-circle"></i></span>

									<?php 
									
									if( in_array('stripe',$payment_gateway) && $membership_data->membership_status != '3' && $membership_data->membership_status != '2') { ?>
									<?php if(  $membership_data->recurring == '1' && !empty( $membership_data->subscription_id )  ){ ?> 
									<a class="btn btn-default cencel-membership" href="<?php echo SAP_SITE_URL  ?>/cancel-user-membership/<?php echo $membership_data->user_id ?>">
										<?php echo $sap_common->lang('cancel'); ?></a>
									<?php } 

									if( $membership_data->recurring == '1'&&  $membership_data->membership_status != '2' && !empty( $membership_data->subscription_id )  ){ ?>
									<a class="btn btn-default" href="<?php echo SAP_SITE_URL  ?>/expire-user-membership/<?php echo $membership_data->user_id ?>">
										<?php echo $sap_common->lang('expired'); ?></a>
									<?php }  } ?>
								</div>
							</div>
						</div>

						<div class="col-md-6 form-group">
							
								<label class="control-label"><?php echo $sap_common->lang('never_expire'); ?></label>
								<div>
									<input type="checkbox" class="tgl tgl-ios" name="no_expiration" id="no_expiration" <?php echo ($membership_data->expiration_date == '') ? 'checked = checked' : '' ?> value="1" />
									<label class="tgl-btn float-right-cs-init" for="no_expiration"></label>
								</div>
							
						</div>
					</div>

					<div class="row">
						<div class="col-md-6 form-group">
								<label class="control-label"><?php echo $sap_common->lang('start_date'); ?></label>
									<div>
										
										<input type="text" class="form-control membership-created-date-input" value="<?php echo  date('Y-m-d',strtotime($membership_data->membership_created_date)) ?>" style="display: none;" id="membership_start_date" name="membership_created_date"/>

										<p class="membership-created-date-text"><?php echo  sap_format_date($membership_data->membership_created_date,false) ?><a> - <span class="created-edit-link"><?php echo $sap_common->lang('edit'); ?></span></a></p>
									</div>
						</div>
						<div class="col-md-6 form-group">
							<div class="row" style="margin: 0;">
								<label><?php echo $sap_common->lang('expiration_date'); ?></label>
								<div>
									<input type="text" class="form-control datepicker" name="expiration_date" id="expiration_date" value="<?php echo ($membership_data->expiration_date != '') ? date('Y-m-d',strtotime($membership_data->expiration_date)) : ''; ?>" placeholder="YYYY-MM-DD"  />  
								</div>
							</div>
						</div>
					</div>

					<div class="row">
						<div class="col-md-6 form-group">
								<label><?php echo $sap_common->lang('customer_id'); ?></label>
								<div>
									<input type="text" class="form-control" name="customer_id" id="customer_id" value="<?php echo $membership_data->customer_id ?>"  />
								</div>
						</div>
						<div class="col-md-6 form-group">
							
								<label><?php echo $sap_common->lang('subscription_id'); ?></label>
								<div>
									<input type="text" class="form-control" name="subscription_id" id="subscription_id" value="<?php echo  $membership_data->subscription_id ?>"  />
								</div>
						
						</div>
					</div>
					<div class="row ">
						<div class="col-md-6 form-group">
							
								<label class="col-sm-3 control-label"><?php echo $sap_common->lang('signup_auto_renew'); ?></label>
								<div class="col-sm-8">
									<input type="checkbox" class="tgl tgl-ios" name="auto_renew" id="auto_renew" value="1" <?php echo ($membership_data->recurring == '1') ? 'checked="checked"' : '' ?>/>
									<label class="tgl-btn float-right-cs-init" for="auto_renew"></label>
								</div>
							
						</div>
					</div>
					<div class="row">
						<div class="col-md-12">
							<div class="alert alert-info schedule-the-content auto-renew-note linkedin-multi-post-note">								
								<i><?php echo $sap_common->lang('auto_renew_note'); ?></i>
							</div>
						</div>
					</div>

					<?php if($membership_data->previous_plan != ''){ ?>
						
					<div class="row sap-mt-1">
						<div class="col-md-6 form-group">
							<div class="row">
								<label><?php echo $sap_common->lang('upgrade_from'); ?></label>
								<div>
									<input type="text" class="form-control " name="previous_plan" id="previous_plan" value="<?php echo $membership_data->previous_plan ?>" disabled='disabled' />
								</div>
							</div>
						</div>
					</div>
					<?php } ?>	

									
					<div class="row">
						<div class="sap-mt-1 col-md-12 form-group">
							<input type="hidden" value="<?php echo ( !empty($membership_id) ? $membership_id : 0 ); ?>" name="id" />
							<input type="hidden" name="form-updated" value="1">
							<button type="submit" name="sap_update_membership_submit" class="btn btn-primary"><?php echo $sap_common->lang('update_membership'); ?></button>
						</div>
					</div>

					<div class="row">
					<div class="col-sm-12">
						<div class="alert alert-info schedule-the-content linkedin-multi-post-note">
							<i><?php echo $sap_common->lang('membership_auto_renew_note'); ?></i>
						</div>  
					</div>
					</div>
					
				</div>
			</div>
		</form>



		<div class="box box-primary membership_details-table">
			<div class="box-header">
				<h3 class="box-title"><?php echo $sap_common->lang('recent_payments'); ?></h3>
			</div>
			<div class="box-body">
				<div class="payment_histrory">
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
									<td><?php echo "$".round($payment->amount,2)  ?></td>
									<td><?php echo isset($payment->coupon_discount_amount) != '' || isset($payment->coupon_discount_amount) != null ? "$".round($payment->coupon_discount_amount,2) : "$0"; ?></td>
									<td><?php echo round($payment->amount,2) > round($payment->coupon_discount_amount,2) ? "$".round($payment->amount,2) - round($payment->coupon_discount_amount,2) : "$0"; ?></td>
	                    			<td><?php echo sap_format_date($payment->payment_date,true) ?></td>
	                    			<td>
	                    			<?php
	                    			echo '<a target="_blank" href="'.SAP_SITE_URL.'/payment-invoice/'.$payment->id.'" class="view-Status">View</a>';
	                    			?>
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
	            </div>
				<?php }
				else{
					echo '<p><b>'.$sap_common->lang('Payments_not_found').'</b></p>';
				} ?>
			</div>
	</section>
</div>

<script src="<?php echo SAP_SITE_URL . '/assets/js/jquery.min.js' ?>" type="text/javascript"></script>


<?php
include'footer.php';
?>
<script src="<?php echo SAP_SITE_URL . '/assets/js/jquery-ui.js' ?>"></script>
<script src="<?php echo SAP_SITE_URL . '/assets/js/custom.js'; ?>"></script>

<script type="text/javascript">
	<?php 

		$timestamp = strtotime($membership_data->membership_created_date);

		$day = date('d', $timestamp);
		$month = date('m', $timestamp);
		$year = date('Y', $timestamp);
	?>
	var day 	= '<?php echo $day ?>';
	var month 	= '<?php echo $month ?>';
	var year 	= '<?php echo $year ?>';
	
	if($('#membership_start_date').length > 0){
		$( "#membership_start_date" ).datepicker({
			dateFormat: 'yy-mm-dd',
		  	changeMonth: true,
		  	changeYear: true,
		  	onSelect: function (selected) {
                var dt = new Date(selected);
                dt.setDate(dt.getDate() + 1);
                $("#expiration_date").datepicker("option", "minDate", dt);
            }
		});	
	}

	if($('#expiration_date').length > 0){

		$( "#expiration_date" ).datepicker({
			dateFormat: 'yy-mm-dd',
		  	changeMonth: true,
		  	changeYear: true,
		  	 minDate: new Date(year, month - 1, day),		  	
		  	onSelect: function (selected) {
                var dt = new Date(selected);
                dt.setDate(dt.getDate() - 1);
                $("#membership_start_date").datepicker("option", "maxDate", dt);
            }
		});	
	}

</script>