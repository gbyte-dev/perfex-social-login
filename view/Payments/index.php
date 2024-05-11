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


$payment_gateway = (isset($payment_gateway) && !empty($payment_gateway)) ? explode(',',$payment_gateway) : array();
?>
<div class="content-wrapper">
	<!-- Main content -->
	<section class="content-header">
		<h1>
			<span class="d-flex flex-wrap align-items-center">
				<div class="page-title-icon payments_icon"></div>
				<?php echo $sap_common->lang('payments'); ?>
			</span>
			<div class='sap-delete'>
				<a href="<?php echo $router->generate('add-payment'); ?>" class="btn btn-primary"><?php echo $sap_common->lang('add_new'); ?></a>
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
						<div class="sap-custom-drop-down-wrap">
							<div class="filter-wrap">
								<div class="d-flex" style=" align-items: flex-start;">
								<div class="delete-dropdown">
									<select id='searchByGender'>
										<option value=''><?php echo $sap_common->lang('bulk_action'); ?></option>
										<option value='delete'><?php echo $sap_common->lang('delete'); ?></option>
									</select>
									<button class="delete_bulk_plan btn btn-primary"><?php echo $sap_common->lang('apply'); ?></button>
								</div>
								<div class="filter-dropdown filter-dropdown-payments">
									<?php if(!empty($allplan)){ ?>
									<select id='searchByMembershipLevel' name="searchByMembershipLevel" style="width: 200px;">
										<option value=''><?php echo $sap_common->lang('select_membership_level'); ?></option>
										<?php 
										foreach($allplan as $plan){
											echo '<option value="'.$plan->name.'">'.$plan->name.'</option>';
										}
										?>							
									</select>
									<?php } ?>								
									<?php if(!empty($payment_gateway)){ ?>
									<select id="searchByGateway" name="searchByGateway">
										<option value=""><?php echo $sap_common->lang('select_gateway'); ?></option>
										<?php  
										foreach($payment_gateway as $gateway){
											echo '<option value="'.$gateway.'">'.ucfirst($gateway).'</option>';
										}
										?>
									</select>

									<select id="search_payment_status" name="search_payment_status">
										<option value=""><?php echo $sap_common->lang('payment_status'); ?></option>
										<option value="1"><?php echo $sap_common->lang('completed'); ?></option>
										<option value="0"><?php echo $sap_common->lang('pending'); ?></option>
										<option value="2"><?php echo $sap_common->lang('failed'); ?></option>
										<option value="3"><?php echo $sap_common->lang('refunded'); ?></option>
									</select>
									<?php } ?>

									<button class="apply_filters btn btn-primary"><?php echo $sap_common->lang('filter'); ?></button>
								</div>
									
								</div>
									<!-- DataTables Search Filter outside DataTables Wrapper -->
									<div id="customSearch" class="customSearch">
										<input type="text" id="searchInputpayment" class="custom-search-input" placeholder="Type to search">
									</div>
							</div>

						<div class="datatable-overly search-top-bar"> 
						<table id="list-payment" class="display table table-bordered table-striped member-list">
							<thead>
								<tr>
									<th data-sortable="false" data-width="10px"><input type="checkbox" class="multipost-select-all" /></th>
									<th data-sortable="false"><?php echo $sap_common->lang('number'); ?></th>	
									<th data-sortable="true"><?php echo $sap_common->lang('name'); ?></th>
									<th data-sortable="true"><?php echo $sap_common->lang('email'); ?></th>
									<th data-sortable="true"><?php echo $sap_common->lang('membership_level'); ?></th>
									<th data-sortable="true"><?php echo $sap_common->lang('gateway'); ?></th>
									<th data-sortable="true"><?php echo $sap_common->lang('transaction_id'); ?></th>
									<th data-sortable="true"><?php echo $sap_common->lang('payment_status'); ?></th>
									<th data-sortable="true"><?php echo $sap_common->lang('amount'); ?></th>
									<th data-sortable="true"><?php echo $sap_common->lang('payment_date'); ?></th>
									<th data-sortable="false"><?php echo $sap_common->lang('invoice'); ?></th>
									<th data-sortable="false"><?php echo $sap_common->lang('action'); ?></th>
								</tr>
							</thead>
							<tfoot>
								<tr>
									<th data-sortable="false" data-width="10px"><input type="checkbox" class="multipost-select-all" /></th>
									<th data-sortable="false"><?php echo $sap_common->lang('number'); ?></th>	
									<th data-sortable="true"><?php echo $sap_common->lang('name'); ?></th>
									<th data-sortable="true"><?php echo $sap_common->lang('email'); ?></th>
									<th data-sortable="true"><?php echo $sap_common->lang('membership_level'); ?></th>
									<th data-sortable="true"><?php echo $sap_common->lang('gateway'); ?></th>
									<th data-sortable="true"><?php echo $sap_common->lang('transaction_id'); ?></th>
									<th data-sortable="true"><?php echo $sap_common->lang('payment_status'); ?></th>
									<th data-sortable="true"><?php echo $sap_common->lang('amount'); ?></th>
									<th data-sortable="true"><?php echo $sap_common->lang('payment_date'); ?></th>
									<th data-sortable="false"><?php echo $sap_common->lang('invoice'); ?></th>
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
	</section>
	<!-- /.content -->

</div><!-- /.content-wrapper -->

<?php
include SAP_APP_PATH . 'footer.php'; ?>

<script type="text/javascript" class="init">
	'use strict';

	$(document).ready(function () {		

		var dtPaymnentHistroy =  $('#list-payment').DataTable({
			"oLanguage": {
				"sEmptyTable": "No payments found."
			},
			"aLengthMenu": [[15,25, 50, 100], [15,25, 50, 100]],
			"pageLength": 15,
			"order": [[11, "desc"]],
			"bLengthChange" : false,
			"responsive": true,
			"processing": true,
			"dom": 'lrtip',      
        	"serverSide": true,
        	'ajax': {
		       'url':'../payments-ajax/',
		       'data': function(data){

		       		var searchByMembershipLevel 	= $('#searchByMembershipLevel').val();
		          	data.searchByMembershipLevel = searchByMembershipLevel;
		          			          	
		          	var searchByGateway  = $('#searchByGateway').val();
		          	data.searchByGateway = searchByGateway;

		          	var search_payment_status  = $('#search_payment_status').val();
		          	data.search_payment_status = search_payment_status;
		       }
		    },
		    
		});

		$('body').on('click','.apply_filters',function(){
		    dtPaymnentHistroy.draw();
		});

		// Attach DataTables search to custom input
        $('#searchInputpayment').on('keyup', function() {
            dtListUsers.search(this.value).draw();
        });

		$(document).on('click', '.delete_payment_histroy', function () {
			
			var obj = $(this);
			var payment_id = $(this).attr('aria-data-id');


			if ( confirm("<?php echo $sap_common->lang('delete_record_conform_msg'); ?>") ) {

				$.ajax({

					type: 'POST',
					url: '../payments/payment_delete/',
					data: {payment_id: payment_id},
					success: function (result) {
						var result = $.parseJSON(result);
						if ( result.status ) {
							$('#member_' + payment_id).parent('td').parent('tr').remove();
							$('<div class="alert alert-success alert-dismissible" role="alert" bis_skin_checked="1">'+
								'<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">×</span></button>Payment has been deleted successfully!</div>').insertBefore('.box');
						}
					}
				});
			}
		});


		$(document).on('click','.delete_bulk_plan', function(){
	        var selected_val = $('#searchByGender').val();
	        if(selected_val == '' ){
				alert("<?php echo $sap_common->lang('select_bulk_action'); ?>");
			}
	        if(selected_val == 'delete') {
	            var id = [];

	            $("input[name='payment_id[]']:checked").each(function (i) {
	                id[i] = $(this).val();
	            });

	            if (id.length === 0) {
	                alert("You must select at least one checkbox!");

	            } else if(confirm("<?php echo $sap_common->lang('delete_selected_records_conform_msg'); ?>")) {

	                $.ajax({
	                    url: '../payments/delete_multiple/',
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
