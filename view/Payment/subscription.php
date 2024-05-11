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

	$user 	= sap_get_current_user();

	

	$subscription_details 	= $this->user->get_user_subscription_details($user['user_id']);
	
?>
<!-- Content Wrapper. Contains page content -->
<div class="content-wrapper payment-page">
	<section class="content">
		<div class="container">

			<div class="row">
				
                <?php 
                    // If Membership status is pending.
                    if( isset( $subscription_details->membership_status) && $subscription_details->membership_status == '0' ) { ?>
                        <div class="alert alert-info" role="alert">
                            <?php echo $sap_common->lang('payment_process_msg'); ?>
                        </div>
                    <?php } ?>

				<div class="row panel panel-default">

                	<div class="panel-heading payment-detail-wrap">
	                    <h3 class="panel-title"><?php echo $sap_common->lang('your_subscription_details'); ?></h3>
	                </div>

                    <div class=" panel-body payment-detail-wrap">
                        <div id="plan_result">
                            <table class="table table-striped">  
                                  <tbody>
                                    <tr>
                                      <th scope="row"><?php echo $sap_common->lang('customer_name'); ?></th>
                                      <td><?php echo $subscription_details->customer_name ?? ''; ?></td> 
                                    </tr>

                                    <tr>
                                      <th scope="row"><?php echo $sap_common->lang('membership_level'); ?></th>
                                      <td><?php echo $subscription_details->name ?? ''; ?></td>
                                    </tr>
                                    <tr>
                                      <th scope="row"><?php echo $sap_common->lang('allowed_network'); ?></th>
                                      <td>
                                        <?php                                              
                                          
                                            $networks = isset($subscription_details->networks) ? unserialize($subscription_details->networks) : '';
                                            if( !empty( $networks ) ) {
                                                $li_content = '';
                                                foreach ($networks as $key => $network) {
                                                    $li_content .= sap_get_networks_label($network).', ';
                                                }
                                                echo rtrim($li_content,", ");  
                                            }
                                        ?>                                                
                                         </td>
                                    </tr>

                                    <tr>
                                      <th scope="row"><?php echo $sap_common->lang('membership_status'); ?></th>
                                      <td>
                                      <?php

                                      echo get_membership_status_label($subscription_details->membership_status ?? '');
                                     
                                       ?></td>
                                    </tr>
                                    <tr>
                                        <th scope="row"><?php echo $sap_common->lang('recurring'); ?></th>
                                        <td>
                                            <?php 
                                            if( isset($subscription_details->recurring) && $subscription_details->recurring == '1') {
                                                echo $sap_common->lang('yes');
                                            }
                                            else{
                                                echo $sap_common->lang('no');
                                            }
                                            ?>                                                
                                        </td>          
                                    </tr>
                                    <tr>
                                        <th scope="row"><?php echo $sap_common->lang('expiration_and_renewal_date'); ?></th>
                                        <td>
                                        <?php
                                        echo sap_get_membership_expiration_date( $subscription_details->expiration_date ?? '');  ?></td>
                                    </tr>
                                    <tr>
                                        <td></td>
                                        <td>                                           
                                            <?php if( !empty( $max_plan ) ){ ?>
                                            <a href="<?php echo SAP_SITE_URL ?>/payment/upgrade/" class="btn btn-primary"><?php echo $sap_common->lang('upgrade'); ?></a>
                                            <?php } ?>
                                        </td>
                                    </tr>
                                    
                                  </tbody>
                                </table>
                            </div>
                            <div class="payment-back-link">
                            	<a href="<?php echo SAP_SITE_URL ?>/payment-page/"><?php echo $sap_common->lang('back_to_payment_page'); ?></a>
                            </div>
                        </div>
                    </div>
                </div>			

			<div class="row">
				<div class="row panel panel-default">
                	<div class="panel-heading payment-detail-wrap">
	                    <h3 class="panel-title"><?php echo $sap_common->lang('payment_history'); ?></h3>
	                </div>

                    <div class=" panel-body payment-detail-wrap">
                        <div id="plan_result">
                             <table id="user_payment_histrory" class="display table table-bordered table-striped member-list" width="100%">
                                <thead>
                                    <tr>
                                        <th data-sortable="false"><?php echo $sap_common->lang('number'); ?></th>
                                        <th data-sortable="false"><?php echo $sap_common->lang('membership_level'); ?></th>
                                        <th data-sortable="false"><?php echo $sap_common->lang('payment_gateway'); ?></th>
                                        <th data-sortable="false"><?php echo $sap_common->lang('transaction_id'); ?></th>
                                        <th data-sortable="false"><?php echo $sap_common->lang('payment_status'); ?></th>
                                        <th data-sortable="false"><?php echo $sap_common->lang('amount'); ?></th>
                                        <th data-sortable="false"><?php echo $sap_common->lang('payment_date'); ?></th>
                                        <th data-sortable="false"><?php echo $sap_common->lang('invoice'); ?></th>
                                    </tr>
                                </thead>
                                <tfoot>
                                    <tr>
                                        <th data-sortable="false"><?php echo $sap_common->lang('number'); ?></th>
                                        <th data-sortable="false"><?php echo $sap_common->lang('membership_level'); ?></th>
                                        <th data-sortable="false"><?php echo $sap_common->lang('payment_gateway'); ?></th>
                                        <th data-sortable="false"><?php echo $sap_common->lang('transaction_id'); ?></th>
                                        <th data-sortable="false"><?php echo $sap_common->lang('payment_status'); ?></th>
                                        <th data-sortable="false"><?php echo $sap_common->lang('amount'); ?></th>
                                        <th data-sortable="false"><?php echo $sap_common->lang('payment_date'); ?></th>
                                        <th data-sortable="false"><?php echo $sap_common->lang('invoice'); ?></th>
                                    </tr>
                                </tfoot>
                                <tbody></tbody>
                            </table>
                        </div>
                    </div>
                </div>
                </div>
			</div>
		</div>
	</section>
</div>


<?php include SAP_APP_PATH . 'footer.php'; ?>

<script type="text/javascript" class="init">
    'use strict';
    $(document).ready(function () {     

        var dtListUsers =  $('#user_payment_histrory').DataTable({
            "oLanguage": {
                "sEmptyTable": "No payments found."
            },
            "aLengthMenu": [[15,25, 50, 100], [15,25, 50, 100]],
            "pageLength": 15,                        
            "responsive": true,
            "processing": true,
            "serverSide": true,
           "bLengthChange": false,
           "searching": false,

            'ajax': {
               'url':'<?php echo SAP_SITE_URL ?>/user-payments-ajax/',
               'data': function(data){                  
               }
            },            
        } );

        $(document).on('click','.cancel-membership',function(){
                    
            var msg = '<?php echo sprintf($sap_common->lang('cancel_membership_alert'),$subscription_details->name); ?>';

            if( confirm(msg)){
                window.location.href = '<?php echo SAP_SITE_URL ?>/cancel-user-membership/<?php echo $subscription_details->id; ?>';
            }
        });
    });
</script>
