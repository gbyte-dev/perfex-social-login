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

    <section class="content-header">
        <h1>
            <span class="d-flex flex-wrap align-items-center">
                <div class="page-title-icon report-title-icon"></div>
                <?php echo $sap_common->lang('report'); ?></h1>
            </span>
    </section>

    <!-- Main content -->
    <section class="content">
        <!-- Info boxes -->
        <div class="row">
            <div class="col-md-12">
                <?php echo $this->flash->renderFlash(); ?>
                <div class="sap-logs-graph-wrap">
                    <div class="sap-logs-graph-filter-wrap">
                        <ul class="sap-filter-btn-wrap">
                            <li class="sap-graph-social-type">
                                <?php
                                $social_types = sap_get_users_networks();
                                ?>
                                <select name="sap_graph_social_type" class="form-control" id="sap_graph_social_type" data-placeholder="Show all social type">
                                    <option value=""><?php echo $sap_common->lang('show_all_st'); ?></option>
                                    <?php
                                    if (!empty($social_types)) { // Check social types are not empty
                                        foreach ($social_types as $social_key => $social_name) {

                                            $value = $social_name;
                                            $label = $social_name;

                                            switch ($social_name) {
                                                case 'facebook':
                                                    $label = sap_get_networks_label('facebook');
                                                    break;
                                                 case 'twitter':
                                                    $label =  sap_get_networks_label('twitter');
                                                    break;
                                                 case 'linkedIn':
                                                    $label = sap_get_networks_label('linkedIn');
                                                    break;
                                                 case 'tumblr':
                                                    $label = sap_get_networks_label('tumblr');
                                                    break;
                                                 case 'pinterest':
                                                    $label = sap_get_networks_label('pinterest');
                                                    break;
                                                 case 'instagram':
                                                    $label = sap_get_networks_label('instagram');
                                                    break;
                                                 case 'gmb':
                                                    $value = 'googlemybusiness';
                                                    $label = sap_get_networks_label('gmb');
                                                    break;
                                            }

                                            echo '<option value="' . $value . '">' . $label . '</option>';
                                        }
                                    }
                                    ?>
                                </select>
                            </li>
                            <li>
                                <input type="radio" class="sap_filter_type form-control" id="current_year" name="sap_filter_type" value="current_year">
                                <label for="current_year"><?php echo $sap_common->lang('year'); ?></label>
                            </li>
                            <li>
                                <input type="radio" checked="checked" class="sap_filter_type form-control" id="current_month" name="sap_filter_type" value="current_month">
                                <label for="current_month"><?php echo $sap_common->lang('this_month'); ?></label>
                            </li>
                            <li>
                                <input type="radio" class="sap_filter_type form-control" id="last_7days" name="sap_filter_type" value="last_7days">
                                <label for="last_7days"><?php echo $sap_common->lang('last_days'); ?></label>
                            </li>
                            <li>
                                <input type="radio" class="sap_filter_type form-control" id="custom" name="sap_filter_type" value="custom">
                                <label for="custom"><?php echo $sap_common->lang('custom'); ?></label>
                            </li>
                        </ul>
                        <div class="sap-custom-wrap-main">
                            <div class="sap-custom-wrap row">
                                <div class="col-md-4">
                                    <input type="text" name="sap_graph_start_date" id="sap_graph_start_date" class="sap-datepicker form-control" placeholder="<?php echo $sap_common->lang('from_date'); ?>">
                                </div>
                                <div class="col-md-4">
                                    <input type="text" name="sap_graph_end_date" id="sap_graph_end_date" class="sap-datepicker form-control" placeholder="<?php echo $sap_common->lang('to_date'); ?>">
                                </div>
                                <div class="col-md-3">
                                    <button type="button" class="button sap_graph_filter btn btn-primary" name="sap_graph_filter"><?php echo $sap_common->lang('filter'); ?></button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div id="sap-logs-graph">

                    </div>
                    <span class="sap-loader-wrap">
                        <div class="sap-loader-sub">
                            <div class="sap-loader-img"></div>
                        </div>
                    </span>
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
<script src="<?php echo SAP_SITE_URL . '/assets/js/loader.js' ?>"></script>
<script src="<?php echo SAP_SITE_URL . '/assets/js/bootstrap-datepicker.min.js'; ?>"></script>
