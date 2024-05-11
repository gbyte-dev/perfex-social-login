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
$all_logs = $this->get_logs();

?>

<!-- Content Wrapper. Contains page content -->
<div class="content-wrapper">

    <section class="content-header">
        <h1>
            <span class="d-flex flex-wrap align-items-center">
                <div class="page-title-icon posting_logs-icon"></div>
                <?php echo $sap_common->lang('posting_logs'); ?></h1>
            </span>
    </section>
    <!-- Main content -->
    <section class="content">
        <!-- Info boxes -->
        <div class="row">
            <div class="col-md-12">
                <?php echo $this->flash->renderFlash(); ?>
                <div class="box">    
                    <div class="box-body post-filter-content sap-custom-drop-down-wrap">
                        
                        <?php if (count($all_logs) > 0) { ?>
                            <div class="filter-wrap">
                                <div class="delete-dropdown">
                                    <select id='filter_logs'>
                                        <option value=''><?php echo $sap_common->lang('bulk_action'); ?></option>
                                        <option value='delete'><?php echo $sap_common->lang('delete'); ?></option>
                                    </select>
                                    <button class="delete_bulk_logs btn btn-primary"><?php echo $sap_common->lang('apply'); ?></button>
                                </div>
                                <!-- DataTables Search Filter outside DataTables Wrapper -->
                                <div id="customSearch" class="customSearch">
                                    <input type="text" id="searchInputPostingLogs" class="custom-search-input" placeholder="Type to search">
                                </div>
                            </div>
                        <?php } ?>
                    </div>
                        <table id="logs-listing" class="display table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th data-sortable="false" data-width="10px"><input type="checkbox" class="logs-select-all"></th>
                                    <th data-sortable="true"><?php echo $sap_common->lang('logs_content'); ?></th>
                                    <th data-sortable="false"><?php echo $sap_common->lang('social_type'); ?></th>
                                    <th data-sortable="true"><?php echo $sap_common->lang('date'); ?></th>
                                    <th data-sortable="false"><?php echo $sap_common->lang('action'); ?></th>
                                </tr>
                            </thead>
                            <tfoot>
                                <tr>
                                    <th data-sortable="false" data-width="10px"><input type="checkbox" class="logs-select-all"></th>
                                    <th data-sortable="true"><?php echo $sap_common->lang('logs_content'); ?></th>
                                    <th data-sortable="false"><?php echo $sap_common->lang('social_type'); ?></th>
                                    <th data-sortable="true"><?php echo $sap_common->lang('date'); ?></th>
                                    <th data-sortable="false"><?php echo $sap_common->lang('action'); ?></th>
                                </tr>
                            </tfoot>
                            <tbody>
                                <?php
                                if (count($all_logs) > 0) {
                                    foreach ($all_logs as $log) {
                                             
                                        if ($this->common->is_serialized($log->social_source)) {
                                            $social_source = unserialize($log->social_source);
                                        } else {
                                            $social_source = $log->social_source;
                                        }
                                   
                                            
                                        ?>
                                        <tr aria-data-id-table="<?php echo $log->id; ?>" id="log_<?php echo $log->id; ?>">
                                            <td><input type="checkbox" name="log_id[]" value="<?php echo $log->id; ?>" /></td>
                                            <td>
                                                <?php
                                                if (!empty($social_source['message'])) {
                                                    echo!empty($social_source['message']) ? $this->common->sap_content_excerpt($social_source['message'], 80) : '';
                                                } elseif (!empty($social_source['status'])) {
                                                    echo!empty($social_source['status']) ? $this->common->sap_content_excerpt($social_source['status'], 80) : '';
                                                } elseif (!empty($social_source['caption'])) {
                                                    echo!empty($social_source['caption']) ? $this->common->sap_content_excerpt($social_source['caption'], 80) : '';
                                                } elseif (!empty($social_source['body'])) {
                                                    echo!empty($social_source['body']) ? $this->common->sap_content_excerpt($social_source['body'], 80) : '';
                                                } elseif (!empty($social_source['notes'])) {
                                                    echo!empty($social_source['notes']) ? $this->common->sap_content_excerpt($social_source['notes'], 80) : '';
                                                }
                                                ?></td>
                                                <td><?php echo ucfirst($log->social_type); ?></td>
                                                <td data-order="<?php echo $log->created; ?>"><?php echo date("M j, Y g:i a", strtotime($log->created)); ?></td>
                                                <td class="action_icons">
                                                    <a href="javascript:void(0)" aria-data-id="<?php echo $log->id; ?>" class="log_view_details" data-toggle="modal" ><i class="fa fa-eye" aria-hidden="true"></i></a>
                                                    <a class="delete_post" aria-data-id="<?php echo $log->id; ?>"><i class="fa fa-trash" aria-hidden="true"></i></a>
                                                </td>
                                            </tr>
                                            <?php
                                        }
                                    }
                                    ?>
                                </tbody>
                            </table>   
                        </div>
                    </div>

                    <div class="modal fade" id="myModal">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <button type="button" class="close" data-dismiss="modal" aria-hidden="true"><i class="fa fa-times" aria-hidden="true"></i></button>
                                    <h3 class="modal-title"><?php echo $sap_common->lang('social_posting_logs'); ?></h3>
                                </div>
                                <div class="modal-body">
                                    <div class="social_logs_view"></div>
                                    <table class="table table-striped" id="tblGrid">
                                        <tbody>
                                        </tbody>
                                    </table>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-default " data-dismiss="modal"><?php echo $sap_common->lang('close'); ?></button>
                                </div>
                            </div><!-- /.modal-content -->
                        </div><!-- /.modal-dialog -->
                    </div><!-- /.modal -->

                </div>
            </div>
        </div>

        <?php 
        $users_networks =  sap_get_users_networks();

        $options = '';

        foreach( $users_networks as $network ){
            $options .= '<option value="'.$network.'">'.sap_get_networks_label($network).'</option>';
        }

        ?>

        <!-- /.content-wrapper -->  
        <?php
                      
        include SAP_APP_PATH . 'footer.php';
        ?>
        <script src="<?php echo SAP_SITE_URL . '/assets/js/loader.js' ?>"></script>
        <script src="<?php echo SAP_SITE_URL . '/assets/js/bootstrap-datepicker.min.js'; ?>"></script>

        <script type="text/javascript" class="init">
            'use strict';
            
            var success_mess_wrap = '<div class="alert alert-success alert-dismissible" role="alert"><button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">×</span></button>{success_message}</div>';


            var networks = '<?php echo $options ?>';

            $(document).on('click', '.log_view_details', function () {

                var obj = $(this);
                var log_id = $(this).attr('aria-data-id');
                $.ajax({
                    type: 'POST',
                    url: '../log/log_view_details/',
                    data: {log_id: log_id},
                    success: function (result) {
                        var result = jQuery.parseJSON(result);
                        if (result.html) {
                            $('#myModal').find('.modal-body table tbody').html(result.html);
                        }
                    }
                });
                $('#myModal').modal('show');
            });



            $('#logs-listing tbody').on('click', 'tr td:nth-child(2),tr td:nth-child(3),tr td:nth-child(4)', function () {
                var obj = $(this);
                console.log($(this));
                var log_id = $(this).parent('tr').attr('aria-data-id-table');
                $.ajax({
                    type: 'POST',
                    url: '../log/log_view_details/',
                    data: {log_id: log_id},
                    success: function (result) {
                        var result = jQuery.parseJSON(result);
                        if (result.html) {
                            $('#myModal').find('.modal-body table tbody').html(result.html);
                        }
                    }
                });
                $('#myModal').modal('show');
            });

            $(document).ready(function () {
                $('#logs-listing').DataTable({
                    "oLanguage": {
                        "sEmptyTable": "No post found."
                    },
                    "bLengthChange": false,
                    "aLengthMenu": [[15,25, 50, 100], [15,25, 50, 100]],
                    "pageLength": 15,
                    "dom": 'lrtip',
                    "order": [[3, "desc"]],
                    initComplete: function () {


                        this.api().columns().every(function (colIdx) {

                            if (colIdx == 2) {
                                var column = this;

                                var select = $('<select><option value=""><?php echo $sap_common->lang("social_type")?></option></select>')
                                .appendTo($(column.header()).empty())
                                .on('change', function () {
                                    var val = $.fn.dataTable.util.escapeRegex(
                                        $(this).val()
                                        );

                                    column
                                    .search(val ? '^' + val + '$' : '', true, false)
                                    .draw();
                                });
                                select.append(networks)

                            }
                        });
                    }
                });
            });



            $(document).on('click', '.delete_post', function () {
                var obj = $(this);
                var log_id = $(this).attr('aria-data-id');
                if (confirm("<?php echo $sap_common->lang('delete_record_conform_msg'); ?>")) {
                    $.ajax({
                        type: 'POST',
                        url: '../log/delete/',
                        data: {log_id: log_id},
                        success: function (result) {
                            var result = jQuery.parseJSON(result);
                            var success_message = 'Log has been deleted successfully.';
                            var success_html = success_mess_wrap.replace("{success_message}", success_message);
                            if (result.status) {
                                $('#log_' + log_id).remove();
                                $(success_html).insertBefore(".content .row .col-md-12 .box");
                                if ($("#logs-listing tbody tr").length == 0) {
                                    $("#logs-listing").find('tbody').append('<tr class="odd"><td valign="top" colspan="5" class="dataTables_empty"><?php echo $sap_common->lang("no_data_available_in_table"); ?></td></tr>');
                                }
                            }
                        }
                    });
                }
            });

            $(document).on('click', '.delete_bulk_logs', function(){

                var selected_val = $('#filter_logs option:selected').val();
                if(selected_val == 'delete'){
                    var id = [];

                    $(':checkbox:checked').each(function (i) {
                        id[i] = $(this).val();
                    });

            //tell you if the array is empty
            if (id.length === 0) {
                alert("<?php echo $sap_common->lang('select_checkbox_alert'); ?>");

            } else if (confirm("<?php echo $sap_common->lang('delete_selected_records_conform_msg'); ?>")) {
                $.ajax({
                    url: '../log/delete_multiple/',
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

        // Attach DataTables search to custom input
        $('#searchInputPostingLogs').on('keyup', function() {
            dtListUsers.search(this.value).draw();
        });	
    });
</script>