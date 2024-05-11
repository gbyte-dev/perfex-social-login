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

$SAP_Mingle_Update = new SAP_Mingle_Update();
$license_data = $SAP_Mingle_Update->get_license_data();
if( !$sap_common->sap_is_license_activated() ){
	$redirection_url = '/mingle-update/';
	header('Location: ' . SAP_SITE_URL . $redirection_url );
	die();
}

$payment_gateway = (isset($payment_gateway) && !empty($payment_gateway)) ? explode(',',$payment_gateway) : array();
?>
<div class="content-wrapper">
	<!-- Main content -->
	<section class="content-header">
		<h1>
			<span class="d-flex flex-wrap align-items-center">
				<div class="page-title-icon coupons_icon"></div>
				<?php echo $sap_common->lang('coupons'); ?>
			</span>
			<div class='sap-delete'>
				<a href="<?php echo $router->generate('add-coupon'); ?>" class="btn btn-primary"><?php echo $sap_common->lang('add_new'); ?></a>
			</div>
		</h1>
	</section>
	<?php
	//$all_members = $this->get_members();
	?>

	<section class="content">
		<div class="row">
			<div class="col-xs-12">
				<?php echo $this->flash->renderFlash(); ?>
				<div class="box">	
					<div class="box-body">
						<div class="sap-custom-drop-down-wrap  membership-drop-down-wrap ">
							<div class="filter-wrap flex-wrap">
								<div class="d-flex">
									<div class="delete-dropdown">
										<select id='searchByGender'>
											<option value=''><?php echo $sap_common->lang('bulk_action'); ?></option>
											<option value='delete'><?php echo $sap_common->lang('delete'); ?></option>
										</select>
										<button class="delete_bulk_plan btn btn-primary"><?php echo $sap_common->lang('apply'); ?></button>
									</div>
									<div class="filter-dropdown">
										<select id='searchByCouponType' name="searchByCouponType" style="width: 270px;">
											<option value=''><?php echo $sap_common->lang('select_coupon_type'); ?></option>
											<?php
												foreach($coupon_type as $key => $value) {
											?>
												<option value="<?php echo $key; ?>" <?php if( $key == $coupon_details->coupon_type ) { echo "selected"; } ?>><?php echo $value; ?></option>
											<?php
												}
											?>
										</select>
										<select id="search_coupon_status" name="search_coupon_status" style="width: 250px;">
											<option value=""><?php echo $sap_common->lang('select_coupon_status'); ?></option>
											<?php
												foreach($coupon_status as $key => $value) {
											?>
												<option value="<?php echo $key; ?>" <?php if( $key == $coupon_details->coupon_status ) { echo "selected"; } ?>><?php echo $value; ?></option>
											<?php
												}
											?>
										</select>
										<button class="apply_filters btn btn-primary zindex-dropdown"><?php echo $sap_common->lang('filter'); ?></button>
									</div>
								</div>
								<!-- DataTables Search Filter outside DataTables Wrapper -->
								<div id="customSearch" class="customSearch">
									<input type="text" id="searchInputcoupons" class="custom-search-input" placeholder="Type to search">
								</div>
							</div>

							<div class="datatable-overly search-top-bar"> 
								<table id="list-coupon" class="display table table-bordered table-striped member-list">
									<thead>
										<tr>
											<th data-sortable="false" data-width="10px"><input type="checkbox" class="multipost-select-all" /></th>
											<th data-sortable="false"><?php echo $sap_common->lang('number'); ?></th>	
											<th data-sortable="true"><?php echo $sap_common->lang('coupon_code'); ?></th>
											<th data-sortable="true"><?php echo $sap_common->lang('coupon_type'); ?></th>
											<th data-sortable="true"><?php echo $sap_common->lang('coupon_amount'); ?></th>
											<th data-sortable="true"><?php echo $sap_common->lang('coupon_description'); ?></th>
											<th data-sortable="true"><?php echo $sap_common->lang('coupon_expiry_date'); ?></th>
											<th data-sortable="true"><?php echo $sap_common->lang('coupon_status'); ?></th>
											<th data-sortable="false"><?php echo $sap_common->lang('action'); ?></th>
										</tr>
									</thead>
									<tfoot>
										<tr>
											<th data-sortable="false" data-width="10px"><input type="checkbox" class="multipost-select-all" /></th>
											<th data-sortable="false"><?php echo $sap_common->lang('number'); ?></th>	
											<th data-sortable="true"><?php echo $sap_common->lang('coupon_code'); ?></th>
											<th data-sortable="true"><?php echo $sap_common->lang('coupon_type'); ?></th>
											<th data-sortable="true"><?php echo $sap_common->lang('coupon_amount'); ?></th>
											<th data-sortable="true"><?php echo $sap_common->lang('coupon_description'); ?></th>
											<th data-sortable="true"><?php echo $sap_common->lang('coupon_expiry_date'); ?></th>
											<th data-sortable="true"><?php echo $sap_common->lang('coupon_status'); ?></th>
											<th data-sortable="false" ><?php echo $sap_common->lang('action'); ?></th>
										</tr>
									</tfoot>
									<tbody>
									</tbody>
								</table>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	</section>
	<!-- /.content -->

</div><!-- /.content-wrapper -->

<?php
include SAP_APP_PATH . 'footer.php'; ?>

<script type="text/javascript" class="init">
	'use strict';

	$(document).ready(function () {		

		var dtPaymnentHistroy =  $('#list-coupon').DataTable({
			"oLanguage": {
				"sEmptyTable": "No coupons found."
			},
			"aLengthMenu": [[15,25, 50, 100], [15,25, 50, 100]],
			"pageLength": 15,
			"order": [[8, "desc"]],
			"bLengthChange" : false,
			"responsive": true,
			"processing": true,
			"dom": 'lrtip',
        	"serverSide": true,
        	'ajax': {
		       'url':'../coupons-ajax/',
		       'data': function(data){
					console.log(data);
		       		var searchByCouponType 	= $('#searchByCouponType').val();
		          	data.searchByCouponType = searchByCouponType;
		          			          	
		          	var search_coupon_status  = $('#search_coupon_status').val();
		          	data.search_coupon_status = search_coupon_status;

		          	/* var searchByCouponExpiryDate  = $('#searchByCouponExpiryDate').val();
		          	data.searchByCouponExpiryDate = searchByCouponExpiryDate; */
		       }
		    },
		    
		});

		$('body').on('click','.apply_filters',function(){
		    dtPaymnentHistroy.draw();
		});


		$(document).on('click', '.delete_coupon', function () {
			
			var obj = $(this);
			var coupon_id = $(this).attr('aria-data-id');


			if ( confirm("<?php echo $sap_common->lang('delete_record_conform_msg'); ?>") ) {

				$.ajax({

					type: 'POST',
					url: '../coupons/coupon_delete/',
					data: {coupon_id: coupon_id},
					success: function (result) {
						var result = $.parseJSON(result);
						if ( result.status ) {
							$('#member_' + coupon_id).parent('td').parent('tr').remove();
							$('<div class="alert alert-success alert-dismissible" role="alert" bis_skin_checked="1">'+
								'<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">×</span></button>Coupon has been deleted successfully!</div>').insertBefore('.box');
						}
					}
				});
			}
		});

		// Attach DataTables search to custom input
        $('#searchInputcoupons').on('keyup', function() {
            dtListUsers.search(this.value).draw();
        });

		$(document).on('click','.delete_bulk_plan', function(){
	        var selected_val = $('#searchByGender').val();
	        if(selected_val == '' ){
				alert("<?php echo $sap_common->lang('select_bulk_action'); ?>");
			}
	        if(selected_val == 'delete') {
	            var id = [];

	            $("input[name='coupon_id[]']:checked").each(function (i) {
	                id[i] = $(this).val();
	            });

	            if (id.length === 0) {
	                alert("You must select at least one checkbox!");

	            } else if(confirm("<?php echo $sap_common->lang('delete_selected_records_conform_msg'); ?>")) {

	                $.ajax({
	                    url: '../coupons/delete_multiple/',
	                    method: 'POST',
	                    data: {id: id},
	                    success: function (result)
	                    {
	                     var result = jQuery.parseJSON(result);
	                     if (result.status) {
	                        location.reload();
	                    }
	                }
	            });

	            } else {
	                return false;
	            }
	        }
	    });

	    $("#list-payment_filter").parent().addClass('col-md-3').css('float','right');
	});
</script>
