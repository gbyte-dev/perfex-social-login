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

	//include SAP_APP_PATH.'sidebar.php';

	$payment_gateway_array = !empty($payment_gateway) ? $payment_gateway : array();
?>
<script src="https://js.stripe.com/v3/"></script>
<?php $enable_billing_details = $this->settings->get_options('enable_billing_details'); ?>
<!-- Content Wrapper. Contains page content -->
<div class="content-wrapper payment-page">
	<section class="content">
		<div class="container">
			<div class="row">
				<?php

				/**********
				 * check if plan not exit or not active plan 
				 * 
				*/
				if( empty( $plan_exits )){

					?>
					<div class="col-md-6 col-md-offset-3">
						<div class="panel panel-default">
							<div class="panel-heading">
			                    <h3 class="panel-title"><?php echo $sap_common->lang('choose_your_membershipas'); ?></h3>
			                </div>

                  			<div class="panel-body"> <p><b><?php echo $sap_common->lang('no_mebership_level'); ?></b></p> </div>
						</div>
					</div>
					<?php
				}
				else{ ?>
				<div class="col-md-6 col-md-offset-3">

					<?php 					

					// If Membership status is pending.
					if( !empty( $membership_data ) && $membership_data->membership_status == '0' ){ ?>
						<div class="alert alert-info" role="alert">
						  	<?php echo $sap_common->lang('payment_process_msg'); ?>
						</div>
					<?php } ?>

					<?php echo $this->flash->renderFlash(); ?>

					<div class="payment-errors alert-error"></div>					
					<?php

					if( !empty( $login_user->payment_status ) && ( $login_user->payment_status == 'active' )  && ( date('Y-m-d') > date('Y-m-d',strtotime( $login_user->expiration ) ) )){
						?>
						<div class="payment-errors alert-error alert"><?php echo $sap_common->lang('subscription_expired_msg'); ?></div>						 
						<?php
					}
					else{

					 ?>
					

					 <form role="form" action="<?php echo SAP_SITE_URL . '/re-payment/'; ?>" method="POST" id="user-payment">
					 	
					 	<input type="hidden" name="is_upgrade" value="<?php echo $upgrade; ?>">
					 	<div class="row">
					 		<div class="col-xs-12 col-md-12">
					 			<div class="panel panel-default">				 			
								
								<?php if( $upgrade == 'yes' ){ 
									$current_plan_details = $this->plan->get_plan($user['plan'],true);
								?>							
	                                
	                                <div class="col-sm-12 col-md-12">
	                                	<div class="row panel panel-default">
		                                	<div class="panel-heading">
							                    <h3 class="panel-title"><?php echo $sap_common->lang('choose_your_membershipas'); ?></h3>
							                </div>

                                  			<div class="panel-body">  

                                  				<p><b><?php echo $sap_common->lang('you_current_plan_is'); ?> <?php echo $current_plan_details->name. ' - ' . $current_plan_details->price . ' - '.$current_plan_details->subscription_expiration_days .' Days'?> </b></p>

	                                	<?php

	                                	 if($plan_data){	                                	 	
	                                		
	                                		foreach($plan_data as $plan){       			

	                                			if($plan->price == 0 || $plan->price == ''){
	                                                $class = 'price_zero_cls';
	                                            }
	                                            else{
	                                                $class = 'price_not_zero_cls';
	                                            } 

	                                            $unlimited_class  = '';
                                                if( $plan->subscription_expiration_days == '' || $plan->subscription_expiration_days == '0'  ){
                                                    $unlimited_class = 'unlimited_plan';    
                                                }
	                                			?>
	                                			<div class="form-check upgrade">
			                                          <input class="form-check-input plan <?php echo $class .' ' .$unlimited_class; ?>" type="radio" name="sap_plan" value="<?php echo $plan->id; ?>" id="<?php echo $plan->id; ?>" checked="checked">
			                                          <label class="form-check-label" for="<?php echo $plan->id; ?>">
			                                            
			                                          	<?php 
			                                            
			                                            $plan_price = 'Free';
                                                    
	                                                    if( !empty( $plan->price)){
	                                                        $plan_price = "$".round($plan->price,2);
	                                                    }

	                                                    $subscription_expiration_days = 'Never';
	                                                    if( !empty( $plan->subscription_expiration_days)){
	                                                        $subscription_expiration_days = $plan->subscription_expiration_days .' Days';
	                                                    }

	                                                    echo $plan->name .' - ' . $plan_price .' - '. $subscription_expiration_days
	                                                    ?>                                          
			                                          </label>
			                                    </div>
	                                			<?php
	                                		}
	                                	}
	                                	?>
	                                    
	                                	<div class="discount-fees" style="display:none;">
	                                    	<hr>
	                                     	<h5><b><?php echo $sap_common->lang('discounts_and_fees'); ?></b></h5>
	                                     	<p><?php echo sprintf($sap_common->lang('proration_credit'),'<span class="discount-amt">','','<span>') ?></p>
	                                 	</div>
	                                </div>
	                                </div>
	                                </div>	                           
							<?php }
							else{
							 ?>
								
                            <div class="col-md-12 form-group membership-details">
                                <div class="row panel panel-default">
                                	<div class="panel-heading">
					                    <h3 class="panel-title"><?php echo $sap_common->lang('choose_your_membershipas'); ?></h3>
					                </div>

                                    <div class=" panel-body">  
                                    	<?php 
                                    	if( $plan_data ){

	                                		foreach( $plan_data as $plan ){
	                                			
	                                			if($plan->price == 0 || $plan->price == ''){
	                                                $class = 'price_zero_cls';
	                                            }
	                                            else{
	                                                $class = 'price_not_zero_cls';
	                                            }

	                                            $unlimited_class  = '';
                                                if( $plan->subscription_expiration_days == '' || $plan->subscription_expiration_days == '0'  ){
                                                    $unlimited_class = 'unlimited_plan';    
                                                }
	                                			?>
	                                			<div class="form-check">
			                                          <input class="form-check-input plan <?php echo $class .' '. $unlimited_class ?>" type="radio" name="sap_plan" value="<?php echo $plan->id; ?>" id="<?php echo $plan->id; ?>">
			                                          <label class="form-check-label" for="<?php echo $plan->id; ?>">
			                                            <?php 

			                                            $plan_price = 'Free';
                                                    
	                                                    if( !empty( $plan->price)){
	                                                        $plan_price = "$".round($plan->price,2);
	                                                    }

	                                                    $subscription_expiration_days = 'Never';
	                                                    if( !empty( $plan->subscription_expiration_days)){
	                                                        $subscription_expiration_days = $plan->subscription_expiration_days .' Days';
	                                                    }

	                                                    echo $plan->name .' - ' . $plan_price .' - '. $subscription_expiration_days
	                                                    ?>

			                                          </label>
			                                    </div>
	                                			<?php
	                                		}
	                                	}?>

                                    </div>
                                </div>
                            </div>							
					    <?php } ?>
					    	
					    	</div>    
					    	</div>
			        	</div>	
			        	
				        	<div class="payment_method_cls">
	                            <div class="col-md-12 form-group">
	                                <div class="row panel panel-default">
	                                	<div class="panel-heading payment-detail-wrap">
						                    <h3 class="panel-title"><?php echo $sap_common->lang('choose_payment'); ?></h3>
						                </div>

	                                    <div class=" panel-body payment-detail-wrap  gateway_checkbox">
	                                        <?php
	                                         $payment_gateway = array(); 

	                                        $payment_gateway 	= $this->settings->get_options('payment_gateway');
	                                       $stripe_label 		= $this->settings->get_options('stripe_label');
	                                       $default_payment_method = $this->settings->get_options('default_payment_method');

	                                       $stripe_test_mode = $this->settings->get_options('stripe_test_mode');

	                                        $stripe_label = !empty($stripe_label) ? $stripe_label : 'Stripe';

	                                        if(!empty($payment_gateway)){

	                                            $payment_gateway = explode(',',$payment_gateway);
	                                            
	                                            foreach($payment_gateway as $data){
	                                            ?>
	                                            <div class="form-check">
	                                                  <input class="form-check-input payment-gateway" type="radio" name="gateway_type" value="<?php echo $data ?>" id="payment_<?php echo $data ?>" <?php if($data == $default_payment_method){ echo 'checked'; } ?>>
	                                                  <label class="form-check-label" for="payment_<?php echo $data ?>">
	                                                    <?php if($data == 'stripe'){ echo $stripe_label; }else{ echo ucfirst($data); } ?>
	                                                  </label>
	                                            </div>
	                                            <?php 
	                                            }
	                                        }else{
	                                        	?>
	                                        	<div class="alert payment-method-error alert-danger" role="alert">
	                                                  <?php echo $sap_common->lang('signup_payment_help_text'); ?>
	                                                </div>
	                                        	<?php
	                                        }
	                                        ?>
	                                        
	                                    </div>
	                                </div>
	                            </div>
	                        </div>                       
                        <?php }	
                    	$payment_gateway_array = !empty($payment_gateway) ? $payment_gateway : array();
                           
                            if( in_array( 'stripe', $payment_gateway_array)){

                            

                            if( $stripe_test_mode == 'yes'){  
                                ?>

                                <div class="stripe-payment-fields" style="display:<?php if('stripe' == $default_payment_method){ echo 'block'; }else{ echo 'none'; } ?>;" >
                                      <div class="row">
                                            <div class="col-xs-12 col-md-12">
                                                <div class="panel panel-default">
                                                    <div class="panel-heading">
                                                        <?php echo sprintf($sap_common->lang('signup_test_help_text'),'<h3 class="panel-title">','</h3>','<span>','</span>'); ?>
                                                    </div>

                                                <div class="panel-body">
                                                    <?php echo sprintf($sap_common->lang('signup_card_details'),'<p>','<b>','</b>','</p>','<p>','<b>','</b>','</p>','<p>','<b>','</b>','</p>','<p>','<a href="https://stripe.com/docs/testing#cards" target="_blank">','</a>','</p>'); ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <?php } ?>

                        <div class="stripe-payment-fields" style="display:<?php if('stripe' == $default_payment_method){ echo 'block'; }else{ echo 'none'; } ?>;" >
                            <input type="hidden" name="user_id" value="<?php echo $login_user->id ?>">
                                <div class="row">
                                    <div class="col-xs-12 col-md-12">
                                        <div class="panel panel-default">
                                            <div class="panel-heading">
                                                <h3 class="panel-title"><?php echo $sap_common->lang('signup_payment_details'); ?></h3>
                                            </div>

                                        <div class="panel-body">
                                            
                                            <div class="form-group">
												<div id="stripe-card-element-user"></div>
                                               
                                            </div>
                                            <div class="row">
												<div id="response-message-user"></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-12 form-group auto-renew-opt" style="display:<?php if('stripe' == $default_payment_method){ echo 'block'; }else{ echo 'none'; } ?>;">
                            <div class="row panel panel-default">
                            	<div class="panel-heading payment-detail-wrap">
				                    <h3 class="panel-title"><?php echo $sap_common->lang('signup_auto_renew'); ?></h3>
				                </div>

                                <div class=" panel-body payment-detail-wrap  gateway_checkbox">
                                    <input type="checkbox" class="tgl tgl-ios" name="auto_renew" id="auto_renew" value="1">
	                                <label class="tgl-btn float-right-cs-init" for="auto_renew"></label>
                                </div>
                            </div>
                        </div>

						<?php if( ! empty( $enable_billing_details ) ) { ?>
							<div class="stripe-payment-fields stripe-billing-details" style="display:<?php if('stripe' == $default_payment_method){ echo 'block'; }else{ echo 'none'; } ?>;" >
									<div class="row">
										<div class="col-xs-12 col-md-12">
											<div class="panel panel-default">
												<div class="panel-heading">
														<h3 class="panel-title"><?php echo $sap_common->lang('signup_address_details'); ?></h3>
												</div>
													<ul>
														<li>
															<label class="col-sm-4 col-md-3"><?php echo $sap_common->lang('line1'); ?></label>
															<input type="text" class="form-control" name="line1" id="line1"  placeholder="<?php echo $sap_common->lang('line1'); ?>">
														</li>
														<li>
															<label class="col-sm-4 col-md-3 "><?php echo $sap_common->lang('line2'); ?></label>
															<input type="text" class="form-control" name="line2" id="line2"  placeholder="<?php echo $sap_common->lang('line2'); ?>">
														</li>
														<li>
															<label class="col-sm-4 col-md-3 "><?php echo $sap_common->lang('city'); ?></label>
															<input type="text" class="form-control" name="city" id="city"  placeholder="<?php echo $sap_common->lang('city'); ?>">
														</li>
														<li>
															<label class="col-sm-4 col-md-3 "><?php echo $sap_common->lang('postal_code'); ?></label>
															<input type="text" class="form-control" name="postal_code" id="postal_code"  placeholder="<?php echo $sap_common->lang('postal_code'); ?>">
														</li>
														<li>
															<label class="col-sm-4 col-md-3 "><?php echo $sap_common->lang('state'); ?></label>
															<input type="text" class="form-control" name="state" id="state"  placeholder="<?php echo $sap_common->lang('state'); ?>">
														</li>
														<li>
															<label class="col-sm-4 col-md-3 "><?php echo $sap_common->lang('country'); ?></label>
															<input type="text" class="form-control" name="country" id="country"  placeholder="<?php echo $sap_common->lang('country'); ?>">
														</li>
													</ul>
											</div>
										</div>
									</div>
								</div>
						<?php } ?>

                    <?php } 


                    if( !empty( $payment_gateway ) ){   ?>
					            
				        <button type="submit" id="stripe-submit" name="sap_add_member_user_submit" class="btn btn-primary"><?php echo $sap_common->lang('make_payment'); ?></button>
			    	 </form>
			    	<?php } ?>
					
				</div>
				<?php } ?>
			</div>
		</div>
	</section>
</div>
<?php
	$payment_gateways = array();
	$payment_gateways = $this->settings->get_options('payment_gateway');
	
	$payment_gateway_arr = !is_array( $payment_gateways ) ? (array) $payment_gateways : $payment_gateways;
    $stripe_test_mode       = $this->settings->get_options('stripe_test_mode');
    // IF send box enabled
    if( $stripe_test_mode == 'yes' ) {          
        $publish_key    = $this->settings->get_options('test_publishable_key');
    }
    else {          
        $publish_key    = $this->settings->get_options('live_publishable_key');
    }
?>

<script type="text/javascript">
    var stripe_publishable_key = "<?php echo $publish_key;?>";
    var disc_amount = "<?php echo $sap_common->lang('discount_amount'); ?>";
    var payable_amount = "<?php echo $sap_common->lang('payable_amount'); ?>";
    var coupon_error_message = "<?php echo $sap_common->lang('enter_coupon_code'); ?>";
</script>

<?php include SAP_APP_PATH . 'footer.php'; ?>
<?php 
$implode_payment_gateway_arr = implode( ",", $payment_gateway_arr);
$explode_payment_gateway_arr = explode( ",", $implode_payment_gateway_arr );
if( !empty( $explode_payment_gateway_arr ) && in_array( 'stripe', $explode_payment_gateway_arr ) ) { ?>
	<script type="text/javascript" src="<?php echo SAP_SITE_URL .'/assets/js/stripe-processing-user.js' ?>"></script>
<?php } ?>