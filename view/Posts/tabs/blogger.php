<?php 

/* Check the absolute path to the Social Auto Poster directory. */
if ( !defined( 'SAP_APP_PATH' ) ) {
    // If SAP_APP_PATH constant is not defined, perform some action, show an error, or exit the script
    // Or exit the script if required
    exit();
}

global $sap_common;
$blogger_accounts = $blogger->sap_get_blogger_accounts();
$blogger_urls = $blogger->sap_get_blogger_urls();

if ( !empty( $post_id ) ) {

    $status = $this->get_post_meta($post_id, '_sap_blogger_status');
    $sap_blogger_custom_msg = $this->get_post_meta($post_id, '_sap_blogger_post_title');
    $sap_blogger_post_image = $this->get_post_meta($post_id, '_sap_blogger_post_img');
    $sap_blogger_post_accounts = $this->get_post_meta($post_id, '_sap_blogger_post_accounts');
    $sap_blogger_post_urls = $this->get_post_meta($post_id, '_sap_blogger_post_url');
    $sap_schedule_time_blogger = $this->get_post_meta($post_id, 'sap_schedule_time_blogger');

}

$blogger_post_status = array('Unpublished', 'Published', 'Scheduled');
$sap_blogger_options = $this->settings->get_user_setting('sap_blogger_options');
$sap_blogger_grant_data = $this->settings->get_user_setting('sap_blogger_sess_data'); 

?>

<div class="row">
    <div class="col-sm-12 margin-bottom">

        <?php if ( empty( $blogger_accounts ) ){?>
            <div class="col-sm-12">
                <div class="alert alert-danger sap-warning">
                    <i class="fa fa-info-circle" aria-hidden="true"></i>
                    <?php echo $sap_common->lang('quick_post_blogger_cnofig_msg'); ?>   
                </div>
            </div>
        <?php }?>

        <div class="form-group">
            <label class="col-sm-4 col-xs-5"><?php echo $sap_common->lang('status'); ?>:
                <i class="fa fa-question-circle" data-trigger="hover" data-container="body" data-toggle="popover" data-placement="right" data-content="<?php echo $sap_common->lang('blogger_post_status_note'); ?>"></i>
            </label>

            <div class="col-sm-8 col-xs-7">
                <?php
                  
                if ( isset( $status ) && array_key_exists( $status, $blogger_post_status ) ) {

                   echo '<label class="_sap_blogger_status_lbl status-text">'.$blogger_post_status[$status].'</label>';
                   echo '<button class="btn btn-primary reset_post_status btn-xs" aria-data-id="'.$post_id.'" aria-type="blogger" aria-label="_sap_blogger_status" type="button" ><i class="fa fa-refresh" aria-hidden="true"></i> '.$sap_common->lang('reset_status').'</button>';

                }else{
                    echo '<label class="_sap_blogger_status_lbl status-Unpublished">'.$sap_common->lang('unpublished').'</label>&nbsp;&nbsp;';
                }?>  
            </div>
        </div>
    </div>

    <div class="col-sm-12 margin-bottom">
        <div class="form-group">
            <label class="col-sm-4" for="sap_blogger_user_id">
                <?php echo $sap_common->lang('post_to_blogger'); ?>:
                <i class="fa fa-question-circle" data-trigger="hover" data-container="body" data-toggle="popover" data-placement="right" data-content="<?php echo $sap_common->lang('post_to_account_note'); ?>"></i>
            </label>
            <div class="col-sm-8">
                <select class="form-control sap_select" tabindex="14" name="sap_blogger_user_id[]" multiple="multiple" id="sap_blogger_user_id" data-placeholder="Select User">
                    <?php
                            
                    if ( !empty( $blogger_accounts ) ) {
                        foreach ( $blogger_accounts as $uid => $uname ) { 
                            $selected = "";
                            if( !empty( $sap_blogger_post_accounts ) && in_array( $uid, $sap_blogger_post_accounts ) ){
                                $selected = ' selected="selected"';
                            } ?>
                            <option value="<?php echo $uid; ?>"<?php print $selected;?>><?php echo $uname; ?></option>
                        <?php } 
                    } ?>
                </select>
            </div>
        </div>
    </div>

    <div class="col-sm-12 margin-bottom">
        <div class="form-group">
            <label for="sap_blogger_url" class="col-sm-4 control-label"><?php echo $sap_common->lang('blogger_post_url'); ?>:
                <i class="fa fa-question-circle" data-container="body" data-toggle="popover" data-trigger="hover" data-placement="right" data-content="<?php echo $sap_common->lang('blogger_url_note'); ?>" data-html="true"></i>
            </label>
            <div class="col-sm-8 sap-msg-wrap">
                <select class="form-control sap_select sap-blogger-url" tabindex="14" name="sap_blogger_url[]" multiple="multiple" id="sap_blogger_url" data-placeholder="Select User">
                    <?php
                            
                    if ( !empty( $blogger_urls ) ) {
                        foreach ( $blogger_urls as $key => $value ) { 
                            $selected = "";
                            if( !empty( $sap_blogger_post_urls ) && in_array( $value, $sap_blogger_post_urls ) ){
                                $selected = ' selected="selected"';
                            } ?>
                            <option value="<?php echo $value; ?>"<?php print $selected;?>><?php echo $value; ?></option>
                        <?php } 
                    } ?>
                </select>
            </div>
        </div>
    </div>

    <div class="col-sm-12 margin-bottom">
        <?php if( empty( $sap_blogger_options['disable_image_blogger'] ) ) { ?>
            <div class="form-group">
                <label for="sap_blogger_post_img" class="col-sm-4 control-label">
                    <?php echo $sap_common->lang('post_image'); ?>:
                    <i class="fa fa-question-circle" data-trigger="hover" data-container="body" data-toggle="popover" data-placement="right" data-content="<?php echo $sap_common->lang('blogger_post_image_note'); ?>"></i>
                </label>
                <div class="col-sm-8">
                    <?php if( !empty( $sap_blogger_post_image ) ) { ?>
                        <input id="sap_blogger_post_img" name="sap_blogger_post_img" type="file" class="file file-loading" data-show-upload="false" data-show-caption="true" data-allowed-file-extensions='["png", "jpg","jpeg", "gif"]' tabindex="20" data-initial-preview="<img src='<?php echo SAP_IMG_URL.$sap_blogger_post_image;?>' class='uploaded-img'/>">
                    <?php } else { ?>
                        <input id="sap_blogger_post_img" name="sap_blogger_post_img" type="file" class="file file-loading" data-show-upload="false" data-show-caption="true" data-allowed-file-extensions='["png", "jpg","jpeg", "gif"]' tabindex="20">
                    <?php } ?>
                    <input type="hidden" name="sap_blogger_post_img" class="sap-default-img" value="<?php echo !empty( $sap_blogger_post_image ) ? $sap_blogger_post_image : '' ;  ?>">
                </div>
            </div>
        <?php } ?>
    </div>


    <div class="col-sm-12 margin-bottom">
        <div class="form-group">
            <label for="sap_blogger_title" class="col-sm-4 control-label"><?php echo $sap_common->lang('blogger_custom_message'); ?><span class="astric">*</span>:
                <i class="fa fa-question-circle" data-container="body" data-toggle="popover" data-trigger="hover" data-placement="right" data-content="<?php echo $sap_common->lang('blogger_title_note'); ?>" data-html="true"></i>
            </label>
            <div class="col-sm-8 sap-msg-wrap">
                <input type="text" name="sap_blogger_title" class="form-control sap-blogger-title" id="sap_blogger_title" tabindex="5" value="<?php echo ( !empty( $sap_blogger_custom_msg ) ? $sap_blogger_custom_msg : '' ); ?>" />
            </div>
        </div>
    </div>
    <div class="col-sm-12">
        <div class="form-group">
            <label for="sap-schedule-time-blogger" class="col-sm-4 control-label">
                <?php echo $sap_common->lang('individual_schedule'); ?>
                <i class="fa fa-question-circle" data-container="body" data-toggle="popover" data-trigger="hover" data-placement="right" data-content="This setting modifies the schedule global setting and overrides scheduled time. Keep it blank to use the global schedule settings."></i>
            </label>
            <div class="col-sm-2">
                <input type="text" name="sap-schedule-time-blogger" id="sap-schedule-time-blogger" placeholder="YYYY-MM-DD hh:mm" <?php echo !empty($sap_schedule_time_blogger) ? 'value="' . date('Y-m-d H:i', $sap_schedule_time_blogger) . '"' : ''; ?> readonly="" class="form-control sap-datetime fb-schedule-input">
            </div>
        </div>
    </div>
    <input type="hidden" name="networks[blogger]" id="enable_blogger" value="1">
</div>