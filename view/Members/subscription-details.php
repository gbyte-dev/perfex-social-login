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
   //Update user profile
   $email = $user_details['email'];
   $id = $user_details['id'];

   include 'header.php';
   include 'sidebar.php';

   $payment_gateway  = explode(',',$this->settings->get_options('payment_gateway'));
   ?>
<!-- Content Wrapper. Contains page content -->
<div class="content-wrapper">
   <!-- Content Header (Page header) -->
   <section class="content-header">
      <h1>
         <span class="d-flex flex-wrap align-items-center">
            <div class="page-title-icon your_subscription_icon"></div>
            <?php echo $sap_common->lang('your_subscription'); ?>
         </span>
      </h1>
   </section>
   <!-- Main content -->
   <section class="content sap-my-account subscription-table">
      <?php echo $this->flash->renderFlash(); ?>


      <?php 
         if( $subscription_details->membership_status  == '3'){
             $exp_date =  sap_format_date($subscription_details->expiration_date);
             ?>
      <div class="alert alert-error" role="alert">
         <?php echo sprintf($sap_common->lang('cancelled_membership_notice_message'),$subscription_details->name,$exp_date); ?>
      </div>
      <?php            
         }
         ?>


           <?php if($user_details['role'] != 'superadmin'){ ?>               
      <!-- Info boxes -->
      <div class="box box-primary">
         <div class="box-header ">
            <h3 class="box-title"><?php echo $sap_common->lang('your_subscription'); ?></h3>
         </div>
         <div class="box-body">
            <div class="row">
               <div class="col-md-12"> 
                     <table class="table table-striped" style="width: 70%">
                        <tbody>
                           <tr>
                              <th scope="row"><?php echo $sap_common->lang('customer_name'); ?></th>
                              <td><?php echo $subscription_details->customer_name ?></td>
                           </tr>
                           <tr>
                              <th scope="row"><?php echo $sap_common->lang('membership_level'); ?></th>
                              <td><?php echo $subscription_details->name ?></td>
                           </tr>
                           <tr>
                              <th scope="row"><?php echo $sap_common->lang('allowed_network'); ?></th>
                              <td>
                                 <?php    
                                    $li_content  = '';                                      
                                    $networks = unserialize($subscription_details->networks);
                                    if( !empty( $networks ) ){
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
                                    echo get_membership_status_label( $subscription_details->membership_status );
                                    
                                     ?>
                                     
                              </td>
                           </tr>
                           <tr>
                              <th scope="row"><?php echo $sap_common->lang('recurring'); ?></th>
                              <td>
                                 <?php 
                                    if( $subscription_details->recurring == '1'){
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
                                    echo sap_get_membership_expiration_date( $subscription_details->expiration_date );  ?>
                              </td>
                           </tr>
                           <tr>
                              <td></td>
                              <td>
                                 <?php 
                                 if( $subscription_details->membership_status != '2' && $subscription_details->membership_status != '3' ){

                                    if(in_array('stripe',$payment_gateway) &&  $subscription_details->recurring == '1' && !empty( $subscription_details->subscription_id ) ){ ?>
                                    <a href="javascript:void(0);" class="btn btn-primary cancel-membership"><?php echo $sap_common->lang('cancel'); ?></a>
                                    <?php } ?>
                                 <?php 


                                 if( in_array('stripe',$payment_gateway) && !empty( $max_plan ) ){ ?>
                                 <a href="<?php echo SAP_SITE_URL ?>/payment/upgrade/" class="btn btn-primary"><?php echo $sap_common->lang('upgrade'); ?></a>
                                 <?php } } ?>
                              </td>
                           </tr>
                        </tbody>
                     </table>
               </div>
            </div>
         </div>
      </div>


      <div class="box box-primary bg-white ptb-30">
         <div class="box-header d-flex flex-wrap p-0">
            <div class="col-md-12">
               <div class="row">
                  <div class="col-md-6">
                     <div class="row">
                        <h3 class="box-title"><?php echo $sap_common->lang('payment_history'); ?></h3>
                     </div>
                  </div>
                  <div class="col-md-6 text-right">
                     <div class="row">
                        <!-- DataTables Search Filter outside DataTables Wrapper -->
                        <div id="customSearch" class="customSearch">
                           <input type="text" id="searchInputsubscription" class="custom-search-input" placeholder="Type to search">
                        </div>
                     </div>
                  </div>
               </div>
            </div>
         </div>
         <div class="box-body">

            <div class="row">
               <div class="col-md-12">
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

       <?php } ?>
   </section>
</div>
<?php 

   
   include'footer.php'; ?>
<script type="text/javascript" class="init">
   'use strict';
   $(document).ready(function () {     
   
       var dtListUsers =  $('#user_payment_histrory').DataTable({
           "oLanguage": {
               "sEmptyTable": "No payments found."
           },
           "aLengthMenu": [[15,25, 50, 100], [15,25, 50, 100]],
           "pageLength": 15,
           "bLengthChange":false,
           "responsive": true,
           "processing": true,
           "dom": 'lrtip',
           "serverSide": true,
           'ajax': {
              'url':'../user-payments-ajax/',
              'data': function(data){                  
               
               }
            },            
       } );
   
       $(document).on('click','.cancel-membership',function(){
                   
           var msg = '<?php echo sprintf($sap_common->lang('cancel_membership_alert'),$subscription_details->name); ?>';
   
           if( confirm(msg)){
               window.location.href = '<?php echo SAP_SITE_URL ?>/cancel-user-membership/<?php echo $id; ?>';
           }
       });
       // Attach DataTables search to custom input
        $('#searchInputsubscription').on('keyup', function() {
            dtListUsers.search(this.value).draw();
        });
   });
</script>