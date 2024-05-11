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
$user_id = sap_get_current_user_id();

$log_filename = SAP_LOG_DIR;
if ( file_exists($log_filename) ) {
    $log_file_data = $log_filename . '/mingle_log_' . md5('123456789ABCDEFGHI') . '--' . $user_id . '.txt';
}
?>

<!-- Content Wrapper. Contains page content -->
<div class="content-wrapper">

    <section class="content-header content-header-updated-log">
         <h1>
        <span class="d-flex flex-wrap align-items-center">
           
                <div class="page-title-icon   posting-debug-logs"></div>
                <?php echo sprintf($sap_common->lang('debug_logs_title'),'','<small class="debug_remove" style="margin-left:5px">','</small>',''); ?>
        </span>
            </h1>
        <form action="<?php echo SAP_SITE_URL . '/debug/clear/'; ?>" method="POST">
            <input type="hidden" name="sap_clear_debug_log" value="clear-log">
            <input type="submit" id="clear_debug_log_id" class="btn btn-primary sap-facebbok-submit" name="wpw_auto_poster_log_submit" value="Clear Log">
        </form>
    </section>

    <!-- Main content -->
    <section class="content">
        <!-- Info boxes -->
        <div class="row">
            
            <div class="col-md-12">
                <div class="box">                  
                    <div class="box-body">
                        <div class="debug_log_file-wrap">
                            <div class="debug_log_file">
                                <?php
                                if (file_exists($log_filename) && file_exists($log_file_data)) {
                                    $myfile = @fopen($log_file_data, "r");
                                    while (!feof($myfile)) {
                                        echo fgets($myfile) . "<br>";
                                    }
                                    fclose($myfile);
                                }
                                ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
</div>

<!-- /.content-wrapper -->  
<?php
include SAP_APP_PATH . 'footer.php';
?>
<script>
    'use strict';
    $(document).on('click', '#clear_debug_log_id', function () {
          var r = confirm("<?php echo $sap_common->lang('debug_logs_alert_msg'); ?>");
          if(r == true){
            return true;
          }else{
            return false;
          }
    });
</script>