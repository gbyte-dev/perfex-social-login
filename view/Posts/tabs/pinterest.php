<?php

/* Check the absolute path to the Social Auto Poster directory. */
if ( !defined( 'SAP_APP_PATH' ) ) {
    // If SAP_APP_PATH constant is not defined, perform some action, show an error, or exit the script
    // Or exit the script if required
    exit();
}

global $sap_common;
// Getting pinterest all accounts
$pin_accounts = $pinterest->sap_get_pin_apps_with_boards();

if (!empty($post_id)) {

    $status = $this->get_post_meta($post_id, '_sap_pin_status');
    $sap_pin_custom_msg = $this->get_post_meta($post_id, '_sap_pin_post_msg');
    $sap_pin_custom_img = $this->get_post_meta($post_id, '_sap_pin_post_image');
    $sap_pin_post_accounts = $this->get_post_meta($post_id, '_sap_pin_post_accounts');
    $sap_schedule_time_pin = $this->get_post_meta($post_id, 'sap_schedule_time_pin');

}
$pin_post_status = array('Unpublished', 'Published', 'Scheduled');

$sap_pinterest_grant_data = $this->settings->get_user_setting('sap_pin_sess_data'); // Getting pinterest app grant data
?>
<div class="row">
    <div class="col-sm-12 margin-bottom">


        <?php if (empty($sap_pinterest_grant_data)) { ?>

            <div class="col-sm-12">
                <div class="alert alert-danger sap-warning">
                    <i class="fa fa-info-circle" aria-hidden="true"></i>
                    <?php echo $sap_common->lang('quick_post_pit_help_msg'); ?>
                      
                </div>
            </div> 
        <?php } ?>

        <div class="form-group">
            <label class="col-sm-4 col-xs-5"><?php echo $sap_common->lang('status'); ?>:
                <i class="fa fa-question-circle" data-container="body" data-toggle="popover" data-trigger="hover" data-placement="right" data-content="Status of Pinterest post i.e. published/unpublished/scheduled."></i>
            </label>
            <div class="col-sm-8 col-xs-7">
                <?php
                if (isset($status) && array_key_exists($status, $pin_post_status)) {
                    echo '<label class="_sap_pin_status_lbl status-text">' . $pin_post_status[$status] . '</label>';
                    echo '<button class="btn btn-primary reset_post_status btn-xs" aria-data-id="' . $post_id . '" aria-type="pinterest" aria-label="_sap_pin_status" type="button" ><i class="fa fa-refresh" aria-hidden="true"></i>'.$sap_common->lang('reset_status').' </button>';
                } else {
                    echo '<label class="_sap_pin_status_lbl status-Unpublished">'.$sap_common->lang('unpublished').'</label>';
                }
                ?>
            </div>
        </div>

    </div>
    <div class="col-sm-12 margin-bottom">
        <div class="form-group">
            <label for="sap_pin_user_id" class="col-sm-4 control-label"><?php echo $sap_common->lang('post_to_pit'); ?>:
                <i class="fa fa-question-circle" data-container="body" data-toggle="popover" data-trigger="hover" data-placement="right" data-content="Select an account to which you want to post. This setting overrides the general settings. Leave it empty to use the general settings."></i>
            </label>
            <div class="col-sm-8">
                <select class="form-control sap_select" tabindex="6" name="sap_pinterest[accounts][]" multiple="multiple" id="sap_pin_user_id">
                    <?php
                    if (!empty($pin_accounts) && is_array($pin_accounts)) {
                        $pin_type_post_user = (!empty($sap_pin_post_accounts)) ? $sap_pin_post_accounts : array();
                        foreach ($pin_accounts as $aid => $aval) {
                            ?>
                            <option <?php echo in_array($aid, $pin_type_post_user) ? 'selected="selected"' : ''; ?> value="<?php echo $aid; ?>" ><?php echo $aval; ?></option>
                            <?php
                        } // End of foreach
                    } // End of main if
                    ?>
                </select>
            </div>
        </div>
    </div>
    <div class="col-sm-12 margin-bottom">
        <div class="form-group">
            <label for="sap_linkedin_custom_link" class="col-sm-4 control-label"><?php echo $sap_common->lang('custom_link'); ?>:
                <i class="fa fa-question-circle" data-container="body" data-toggle="popover" data-trigger="hover" data-placement="right" data-content="Here you can enter the custom link which will be used for the wall post. The link must start with http://"></i>
            </label>
            <div class="col-sm-8">
                <input type="text" tabindex="21" class="form-control sap-valid-url" name="sap_pinterest_custom_link" id="sap_pinterest_custom_link" value="<?php echo (!empty($sap_pinterest_custom_link) ? $sap_pinterest_custom_link :'');?>" placeholder="<?php echo $sap_common->lang('custom_link'); ?>" />
                 
            </div>
        </div>
    </div>
    <div class="col-sm-12 margin-bottom">
        <div class="form-group">
            <label for="sap_pin_post_img" class="col-sm-4 control-label">
                <?php echo $sap_common->lang('post_image'); ?>:
                <i class="fa fa-question-circle" data-container="body" data-trigger="hover" data-toggle="popover" data-placement="right" data-content="Here you can upload an image which will be used for the Pinterest post. Leave it empty to use the general default settings." data-html="true"></i>
            </label>
            <div class="col-sm-8">
                <?php if (!empty($sap_pin_custom_img)) { ?>
                    <input id="sap_pin_post_img" name="sap_pinterest_post_img" type="file" class="file file-loading" data-show-upload="false" data-show-caption="true" data-allowed-file-extensions='["png", "jpg","jpeg", "gif"]' data-max-file-size="<?php echo MINGLE_MAX_FILE_UPLOAD_SIZE; ?>" tabindex="8" data-initial-preview="<img src='<?php echo SAP_IMG_URL . $sap_pin_custom_img; ?>' class='uploaded-img'/>">
                <?php } else { ?>
                    <input id="sap_pin_post_img" name="sap_pinterest_post_img" type="file" class="file file-loading" data-show-upload="false" data-show-caption="true" data-max-file-size="<?php echo MINGLE_MAX_FILE_UPLOAD_SIZE; ?>" data-allowed-file-extensions='["png", "jpg","jpeg", "gif"]' tabindex="8">
                <?php } ?>

                <div class="alert alert-info linkedin-multi-post-note">
                    <i><?php echo $sap_common->lang('pinterest_desc'); ?></i>
                </div>

                <input type="hidden" name="sap_pinterest_post_img" class="sap-default-img" value="<?php echo!empty($sap_pin_custom_img) ? $sap_pin_custom_img : ''; ?>">
            </div>
        </div>
    </div>
    <div class="col-sm-12 margin-bottom">
        <div class="form-group">
            <label for="sap_pin_post_custom_message" class="col-sm-4 control-label"><?php echo $sap_common->lang('custom_message'); ?>:
                <i class="fa fa-question-circle" data-container="body" data-toggle="popover" data-trigger="hover" data-placement="right" data-content="Here you can enter a custom content which will be used for the Pinterest post. Leave it empty to use content of the current post." data-html="true"></i>
            </label>
            <div class="col-sm-8">                
                <textarea class="form-control" name="sap_pinterest[message]" id="sap_pin_post_custom_message" tabindex="5"><?php echo (!empty($sap_pin_custom_msg) ? $sap_pin_custom_msg : ''); ?></textarea>
            </div>
        </div>
    </div>
    <div class="col-sm-12">
        <div class="form-group">
            <label for="sap-schedule-time-pin" class="col-sm-4 control-label">
                <?php echo $sap_common->lang('individual_schedule'); ?>
                <i class="fa fa-question-circle" data-container="body" data-toggle="popover" data-trigger="hover" data-placement="right" data-content="This setting modifies the schedule global setting and overrides scheduled time. Keep it blank to use the global schedule settings."></i>
            </label>
            <div class="col-sm-2">
                <input type="text" name="sap-schedule-time-pin" id="sap-schedule-time-pin" placeholder="YYYY-MM-DD hh:mm" <?php echo !empty($sap_schedule_time_pin) ? 'value="' . date('Y-m-d H:i', $sap_schedule_time_pin) . '"' : ''; ?> readonly="" class="form-control sap-datetime fb-schedule-input">
            </div>
        </div>
    </div>
    <input type="hidden" name="networks[pinterest]" id="enable_pinterest" value="1">
</div>