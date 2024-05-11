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
<div class="content-wrapper">
	<!-- Content Header (Page header) -->
	<section class="content-header">
		<h1>
			<span class="d-flex flex-wrap align-items-center">
				<div class="page-title-icon membership_level_icon"></div>
				<?php echo $sap_common->lang('membership_level'); ?>
			</span>
			<div class='sap-delete'>

				<a href="<?php echo $router->generate('add_plan'); ?>" class="btn btn-primary"><?php echo $sap_common->lang('add_new'); ?> </a>

			</div>
		</h1>
	</section>

	<!-- Main content -->
	<?php
	$all_plans = $this->get_plans();
	?>

	<section class="content">
		<div class="row  mobile-row">
			<div class="col-xs-12">
				<?php echo $this->flash->renderFlash(); ?>
				<div class="box">
					<div class="box-body sap-custom-drop-down-wrap">
						<div class="filter-wrap">
							<div class="d-flex">
							<?php if (count($all_plans) > 0) { ?>
								<div class="delete-dropdown">
									<select id='searchByGender'>
										<option value=''><?php echo $sap_common->lang('bulk_action'); ?></option>
										<option value='delete'><?php echo $sap_common->lang('delete'); ?></option>
									</select>
									<button class="delete_bulk_plan btn btn-primary"><?php echo $sap_common->lang('apply'); ?></button>
								</div>
							<?php } ?>
							<div class="filter-dropdown">
								<select id='searchByStatus' name="searchByStatus">
									<option value=''><?php echo $sap_common->lang('select_status'); ?></option>
									<option value="1"><?php echo $sap_common->lang('active'); ?></option>
									<option value="0"><?php echo $sap_common->lang('in-active'); ?></option>
								</select>
								<button class="apply_filters btn btn-primary"><?php echo $sap_common->lang('filter'); ?></button>
							</div>
						</div>
						<!-- DataTables Search Filter outside DataTables Wrapper -->
						<div id="customSearch" class="customSearch">
							<input type="text" id="searchInputplans" class="custom-search-input" placeholder="Type to search">
						</div>
					</div>
						<table id="list-plans" class="display table table-bordered table-striped member-list">
							<thead>
								<tr>
									<th data-sortable="false" data-width="10px"><input type="checkbox" class="multipost-select-all" /></th>
									<th data-sortable="false"><?php echo $sap_common->lang('number'); ?></th>
									<th data-sortable="true"><?php echo $sap_common->lang('name'); ?></th>
									<th data-sortable="true"><?php echo $sap_common->lang('description'); ?></th>
									<th data-sortable="true"><?php echo $sap_common->lang('price'); ?></th>
									<th data-sortable="true"><?php echo $sap_common->lang('networks'); ?></th>
									<th data-sortable="true"><?php echo $sap_common->lang('status'); ?></th>
									<th data-sortable="false"><?php echo $sap_common->lang('action'); ?></th>
								</tr>
							</thead>
							<tfoot>
								<tr>
									<th data-sortable="false" data-width="10px"><input type="checkbox" class="multipost-select-all" /></th>
									<th data-sortable="false"><?php echo $sap_common->lang('number'); ?></th>
									<th data-sortable="true"><?php echo $sap_common->lang('name'); ?></th>
									<th data-sortable="true"><?php echo $sap_common->lang('description'); ?></th>
									<th data-sortable="true"><?php echo $sap_common->lang('price'); ?></th>
									<th data-sortable="true"><?php echo $sap_common->lang('networks'); ?></th>
									<th data-sortable="true"><?php echo $sap_common->lang('status'); ?></th>
									<th data-sortable="false"><?php echo $sap_common->lang('action'); ?></th>
								</tr>
							</tfoot>

							<tbody>								
							</tbody>
						</table>
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
		var dtListUsers =  $('#list-plans').DataTable({
			"oLanguage": {
				"sEmptyTable": "No membership level found."
			},
			"aLengthMenu": [[15,25, 50, 100], [15,25, 50, 100]],
			"pageLength": 15,
			"bLengthChange" : false,
			"order": [[7, "desc"]],
			"responsive": true,
			"processing": true,
        	"serverSide": true,
			"dom": 'lrtip',        	
		    "columnDefs": [
	            {"targets": 0, "name": "checkbox", 'searchable': false, 'orderable': false},
	            {"targets": 1, "name": "number", 'searchable': false, 'orderable': false},
	            {"targets": 2, "name": "name", 'searchable': true, 'orderable': true},
	            {"targets": 3, "name": "description", 'searchable': true, 'orderable': true},
	            {"targets": 4, "name": "price", 'searchable': true, 'orderable': true},
	            {"targets": 5, "name": "networks", 'searchable': true, 'orderable': true},
	            {"targets": 6, "name": "status", 'searchable': true, 'orderable': true},
	            {"targets": 7, "name": "action", 'searchable': false, 'orderable': false},
	        ],
	      
        	'ajax': {
		       'url':'../plan-ajax/',
		       'data': function(data){
		          // Read values
		          var searchByStatus = $('#searchByStatus').val();
		          data.searchByStatus = searchByStatus;

		          var searchByPlan = $('#searchByPlan').val();
		          data.searchByPlan = searchByPlan;
		       }
		    },		    
		});

		$('body').on('click','.apply_filters',function(){
		    dtListUsers.draw();
		});

		$(document).on('click', '.delete_plan', function () {
			
			var obj = $(this);
			var plan_id = $(this).attr('aria-data-id');

			if ( confirm("<?php echo $sap_common->lang('delete_record_conform_msg'); ?>") ) {
				$.ajax({
					type: 'POST',
					url: '../plan/delete/',
					data: {plan_id: plan_id},
					success: function (result) {
						
						var result = $.parseJSON(result);

						if ( result.status == '1' ) {

							$('#plan_' + plan_id).parent('td').parent('tr').remove();
							if ($("#list-plans tbody tr").length == 0) {

								$("#list-plans").find('tbody').append('<tr class="odd"><td valign="top" colspan="5" class="dataTables_empty"><?php echo $sap_common->lang("no_plan_available");?></td></tr>');
							}
						} else if (result.msg) {
							alert( result.msg );
						}


					}
				});
			}
		});

		// Attach DataTables search to custom input
        $('#searchInputplans').on('keyup', function() {
            dtListUsers.search(this.value).draw();
        });

		$(document).on('click','.delete_bulk_plan', function(){

			var selected_val = $('#searchByGender').val();
			if(selected_val == '' ){
				alert("<?php echo $sap_common->lang('select_bulk_action'); ?>");
			}
			if(selected_val == 'delete'){
				var id = [];

				$("input[name='plan_id[]']:checked").each(function (i) {
					id[i] = $(this).val();
				});

				if (id.length === 0) {
					alert("<?php echo $sap_common->lang('select_checkbox_alert'); ?>");

				} else if(confirm("<?php echo $sap_common->lang('delete_selected_records_conform_msg'); ?>")) {

					$.ajax({
						url: '../plan/delete_multiple/',
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

	} );
</script> 