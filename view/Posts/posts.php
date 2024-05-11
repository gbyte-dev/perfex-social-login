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
    <!-- Content Header (Page header) -->
    <section class="content-header">
        <h1>
            <span class="d-flex flex-wrap align-items-center">
                <div class="page-title-icon multi-post-title-icon"></div>
                <?php echo $sap_common->lang('multi-post'); ?>
            </span>
            <div class='sap-delete'>
                <a href="<?php echo $router->generate('addpost'); ?>" class="btn btn-primary"><?php echo $sap_common->lang('add_new'); ?></a>
            </div>    
        </h1>
    </section>
    <!-- Main content -->
    <?php $all_posts = $this->get_posts(); ?>
    <section class="content">
        <div class="row">
            <div class="col-xs-12">
                <?php echo $this->flash->renderFlash(); ?>
                <div class="box bg-white ptb-40">                    
                    <div class="box-body post-filter-content sap-custom-drop-down-wrap">
                        <?php if (count($all_posts) > 0) { ?>
                            <div class="filter-wrap">
                                <div class="delete-dropdown">
                                    <select id='bulk_action'>
                                        <option value=''><?php echo $sap_common->lang('bulk_action'); ?></option>
                                        <option value='delete'><?php echo $sap_common->lang('delete'); ?></option>
                                    </select>
                                    <button class="delete_bulk_multi_post btn btn-primary"><?php echo $sap_common->lang('apply'); ?></button>
                                </div>
                                <!-- DataTables Search Filter outside DataTables Wrapper -->
                                <div id="customSearch" class="customSearch">
                                    <input type="text" id="searchInputcoupons" class="custom-search-input" placeholder="Type to search">
                                </div>
                            </div>
                        <?php } ?>
                        
                        <table id="list-post" class="display table table-bordered table-striped multipost">
                            <thead>
                                <tr>
                                    <th data-sortable="false" data-width="10px"><input type="checkbox" class="multipost-select-all" /></th>
                                    <th data-sortable="true"><?php echo $sap_common->lang('content'); ?></th>
                                    <th data-sortable="false"><?php echo $sap_common->lang('image'); ?></th>
                                    <th data-sortable="false"><?php echo $sap_common->lang('status'); ?></th>
                                    <th data-sortable="true"><?php echo $sap_common->lang('date'); ?></th>
                                    <th data-sortable="false"><?php echo $sap_common->lang('action'); ?></th>
                                </tr>
                            </thead>
                            <tfoot>
                                <tr>
                                    <th data-sortable="false" data-width="10px"><input type="checkbox" class="multipost-select-all" /></th>
                                    <th data-sortable="true"><?php echo $sap_common->lang('content'); ?></th>
                                    <th data-sortable="false"><?php echo $sap_common->lang('image'); ?></th>
                                    <th data-sortable="false"><?php echo $sap_common->lang('status'); ?></th>
                                    <th data-sortable="true"><?php echo $sap_common->lang('date'); ?></th>
                                    <th data-sortable="false"><?php echo $sap_common->lang('action'); ?></th>
                                </tr>
                            </tfoot>
                            <tbody>
                                <?php
                                if (count($all_posts) > 0) {
                                    foreach ($all_posts as $post) 
                                    {  
                                        if (!empty($post->img) && $post->img != '0') {
                                            $post_img = '<img class="post_img" src="' .SAP_IMG_URL.$post->img . '"/>';
                                        } else {
                                            $post_img = '';
                                        }
                                        ?>
                                        <tr id="post_<?php echo $post->post_id; ?>">
                                            <td><input type="checkbox" name="post_id[]" value="<?php echo $post->post_id; ?>" /></td>
                                            <td><a href="<?php echo SAP_SITE_URL . '/posts/view/' . $post->post_id; ?>"><?php echo !empty($post->body) ? $this->common->sap_content_excerpt($post->body, 65) : ''; ?></a></td>
                                            <td><?php echo $post_img; ?></td>
                                            <?php
                                            $shedule = $this->get_post_meta($post->post_id, 'sap_schedule_time');   
                                            ?>
                                            <td class="quick-status" <?php echo !empty($shedule) && $post->status == 2 ?  'data-toggle="tooltip" title="'.date('Y-m-d H:i', $shedule).'" ' : '' ?> data-placement="left" ><?php echo $post->status == 1 ? 'Published' : 'Scheduled'; ?></td>

                                            <td data-order="<?php echo $post->created_date; ?>"><?php echo date("M j, Y g:i a", strtotime($post->created_date)); ?></td>
                                            <td class="action_icons">
                                                <a href="<?php echo SAP_SITE_URL . '/posts/view/' . $post->post_id; ?>"><i class="fa fa-pencil-square-o" aria-hidden="true"></i></a>
                                                <a class="delete_post" aria-data-id="<?php echo $post->post_id; ?>"><i class="fa fa-trash" aria-hidden="true"></i></a>
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
            </div>
        </div>
    </section>
    <!-- /.content -->
</div>
<!-- /.content-wrapper -->  
<?php
include SAP_APP_PATH . 'footer.php';
?>

<script type="text/javascript" class="init">
    'use strict';
    
    $(document).ready(function () {
        $('#list-post').DataTable({
            "oLanguage": {
                "sEmptyTable": "No post found."
            },
            "aLengthMenu": [[15,25, 50, 100], [15,25, 50, 100]],
            "pageLength": 15,
             "bLengthChange": false,
            "order": [[4, "desc"]],
            "dom": 'lrtip',
            "responsive": true,
            initComplete: function () {
               this.api().columns().every( function (colIdx) {
                   if( colIdx == 3 ){
                     var column = this;
                     var select = $('<select><option value=""><?php echo $sap_common->lang("status"); ?></option></select>')
                     .appendTo( $(column.header()).empty() )
                     .on( 'change', function () {
                      var val = $.fn.dataTable.util.escapeRegex(
                          $(this).val()
                          );

                      column
                      .search( val ? '^'+val+'$' : '', true, false )
                      .draw();
                  } );

                     column.data().unique().sort().each( function ( d, j ) {
                      select.append( '<option value="'+d+'">'+d+'</option>' )
                  } );
                 }
             } );
           }
       });
    });

    $(document).on('click', '.delete_post', function () {
       
        var obj = $(this);

        var post_id = $(this).attr('aria-data-id');

        if (confirm("<?php echo $sap_common->lang('delete_record_conform_msg'); ?>")) {
            $.ajax({
                type: 'POST',
                url: '../post/delete/',
                data: {post_id: post_id},
                success: function (result) {
                    var result = jQuery.parseJSON(result);
                    if (result.status)
                    {
                        $('#post_' + post_id).remove();
                        if ($("#list-post tbody tr").length == 0) {
                            $("#list-post").find('tbody').append('<tr class="odd"><td valign="top" colspan="5" class="dataTables_empty">No data available in table</td></tr>');
                        }
                    }
                }
            });
        }
    });

    // Attach DataTables search to custom input
    $('#searchInputcoupons').on('keyup', function() {
        dtListUsers.search(this.value).draw();
    });

    $(document).on('click','.delete_bulk_multi_post', function(){

        var selected_val = $('#bulk_action option:selected').val();

        if(selected_val == 'delete'){

            var id = [];

            $("input[name='post_id[]']:checked").each(function (i) {
                id[i] = $(this).val();
            });

            if (id.length === 0) {
                alert("<?php echo $sap_common->lang('select_checkbox_alert'); ?>");

            } else if(confirm("<?php echo $sap_common->lang('delete_selected_records_conform_msg'); ?>")) {

                $.ajax({
                    url: '../post/delete_multiple/',
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
</script>