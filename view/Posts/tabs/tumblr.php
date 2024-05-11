<?php

/* Check the absolute path to the Social Auto Poster directory. */
if ( !defined( 'SAP_APP_PATH' ) ) {
    // If SAP_APP_PATH constant is not defined, perform some action, show an error, or exit the script
    // Or exit the script if required
    exit();
}

global $sap_common;
$selected = '';
$tumbl_user_accounts = $tumblr->sap_fetch_tumblr_accounts();

if (!empty($post_id)) {

    $status = $this->get_post_meta($post_id, '_sap_tumblr_status');
    $sap_tumblr_custom_type = $this->get_post_meta($post_id, '_sap_tumblr_post_type');
    $sap_tumblr_custom_link = $this->get_post_meta($post_id, '_sap_tumblr_post_link');
    $sap_tumblr_custom_desc = $this->get_post_meta($post_id, '_sap_tumblr_post_desc');
    $sap_tumblr_custom_img = $this->get_post_meta($post_id, '_sap_tumblr_post_img');
    $sap_tumblr_posts_profile = explode(",",$this->get_post_meta($post_id, '_sap_tumblr_post_profile'));
    $sap_schedule_time_tumblr = $this->get_post_meta($post_id, 'sap_schedule_time_tumblr');
   
}

$tumblr_post_status = array('Unpublished', 'Published', 'Scheduled');
?>
<div class="row">
    <div class="col-sm-12 margin-bottom">
        <?php $sap_tumblr_sess_data = $this->settings->get_user_setting( 'sap_tumblr_sess_data' );  if( !isset($sap_tumblr_sess_data) || empty($sap_tumblr_sess_data)) {?>
        <div class="col-sm-12">
            <div class="alert alert-danger sap-warning">
                <i class="fa fa-info-circle" aria-hidden="true"></i>
                <?php echo $sap_common->lang('quick_post_tumb_help_msg'); ?>
                
            </div>
        </div> 
        <?php } ?>
        <div class="form-group">
            <label for="sap_tumblr_post_status" class="col-sm-4 col-xs-5 control-label"><?php echo $sap_common->lang('status'); ?>:
                <i class="fa fa-question-circle" data-container="body" data-toggle="popover" data-trigger="hover" data-placement="right" data-content="Status of Tumblr post i.e. published/unpublished/scheduled."></i>
            </label>
            <div class="col-sm-8 col-xs-7">
            <?php
            if (isset($status) && array_key_exists($status, $tumblr_post_status)) {
               echo '<label class="_sap_tumblr_status_lbl status-text">'.$tumblr_post_status[$status].'</label>';
               echo '<button class="btn btn-primary reset_post_status btn-xs" aria-data-id="'.$post_id.'" aria-type="tumblr" aria-label="_sap_tumblr_status" type="button" ><i class="fa fa-refresh" aria-hidden="true"></i> '.$sap_common->lang('reset_status').'</button>';
            }else{
                echo '<label class="_sap_tumblr_status_lbl status-Unpublished">'.$sap_common->lang('unpublished').'</label>';
            }?>  
        </div>
        </div>
        
    </div>
    <div class="col-md-12 margin-bottom">
        <div class="form-group">
            <label for="sap_tumblr_user_id" class="col-sm-4 control-label"><?php echo $sap_common->lang('post_to_tumb'); ?>:
                <i class="fa fa-question-circle" data-container="body" data-toggle="popover" data-trigger="hover" data-placement="right" data-content="Select an account to which you want to publish a post. This setting overrides the general settings. Leave it empty to use the default one from the general settings."></i>
            </label>
            <div class="col-md-8">
                <select class="form-control select2 width-100" id="sap_tumblr_user_id" name="sap_tumblr_user_id[]" multiple="multiple">
                     <?php
                       if (!empty($tumbl_user_accounts)) {
                        foreach ($tumbl_user_accounts as $profile_id => $profile_name) {
                            if(!empty($sap_tumblr_posts_profile)){

                              $selected = in_array($profile_id,$sap_tumblr_posts_profile) ? 'selected=selected' : '';

                            }?> 
                           <option value="<?php echo $profile_id; ?>" <?php echo $selected;?>><?php echo $profile_name; ?></option>
                        <?php } 
                       }     
                     ?>
                </select>
            </div>    
        </div>
    </div>
    <div class="col-sm-12 margin-bottom">
        <div class="form-group">
            <label for="sap_tumblr_posting_type" class="col-sm-4 control-label"><?php echo $sap_common->lang('posting_type'); ?>:
                <i class="fa fa-question-circle" data-container="body" data-toggle="popover" data-trigger="hover" data-placement="right" data-content="Choose posting type which you want to use. Leave it empty to use the default one from the general settings."></i>
            </label>
        </div>
        <div class="col-sm-8">
            <select class="form-control" name="sap_tumblr_posting_type" id="sap_tumblr_posting_type" tabindex="25">
                <option <?php echo (!empty( $sap_tumblr_custom_type ) && $sap_tumblr_custom_type == 'text') ? ' selected="selected" ' : '';  ?> value="text"><?php echo $sap_common->lang('text'); ?></option>
                <option <?php echo (!empty( $sap_tumblr_custom_type ) && $sap_tumblr_custom_type == 'link') ? ' selected="selected" ' : '';  ?> value="link"><?php echo $sap_common->lang('link'); ?></option>
                <option <?php echo (!empty( $sap_tumblr_custom_type ) && $sap_tumblr_custom_type == 'photo') ? ' selected="selected" ' : '';  ?> value="photo"><?php echo $sap_common->lang('photo'); ?></option>
            </select>
        </div>
    </div>

    <div class="col-sm-12 margin-bottom hide-tumblr-post-link">
        <div class="form-group">
            <label for="sap_linkedin_custom_link" class="col-sm-4 control-label"><?php echo $sap_common->lang('custom_link'); ?>:
                <i class="fa fa-question-circle" data-container="body" data-toggle="popover" data-trigger="hover" data-placement="right" data-content="Here you can enter the custom link which will be used for the wall post. The link must start with http://"></i>
            </label>
            <div class="col-sm-8">
                <input type="text" tabindex="21" class="form-control sap-valid-url" name="sap_tumblr_custom_link" id="sap_tumblr_custom_link" value="<?php echo (!empty($sap_tumblr_custom_link) ? $sap_tumblr_custom_link :'');?>" placeholder="<?php echo $sap_common->lang('custom_link'); ?>" />
            </div>
        </div>
    </div>

    <div class="col-sm-12 margin-bottom hide-tumblr-post-img">
        <div class="form-group">
            <label for="sap_tumblr_post_img" class="col-sm-4 control-label"> <?php echo $sap_common->lang('post_image'); ?>:
                <i class="fa fa-question-circle" data-container="body" data-toggle="popover" data-trigger="hover" data-placement="right" data-content="Here you can upload a image which will be used for the Tumblr post. Leave it empty to use the content image. if content image is also blank, then it will take default image from the settings page."></i>
            </label>
        </div>
        <div class="col-sm-8">
            <?php if(!empty($sap_tumblr_custom_img)) { ?>
                <input id="sap_tumblr_post_img" name="sap_tumblr_post_img" type="file" class="file file-loading" data-show-upload="false" data-show-caption="true" data-allowed-file-extensions='["png", "jpg","jpeg", "gif"]' data-max-file-size="<?php echo MINGLE_MAX_FILE_UPLOAD_SIZE; ?>" tabindex="8" data-initial-preview="<img src='<?php echo SAP_IMG_URL.$sap_tumblr_custom_img;?>' class='uploaded-img'/>">
            <?php } else { ?>
                <input id="sap_tumblr_post_img" name="sap_tumblr_post_img" type="file" class="file file-loading" data-show-upload="false" data-show-caption="true" data-allowed-file-extensions='["png", "jpg","jpeg", "gif"]' data-max-file-size="<?php echo MINGLE_MAX_FILE_UPLOAD_SIZE; ?>" tabindex="8">
            <?php } ?>
            <input type="hidden" name="sap_tumblr_post_img" class="sap-default-img" value="<?php echo !empty( $sap_tumblr_custom_img )? $sap_tumblr_custom_img :'';  ?>">
        </div>
    </div>
    <div class="col-sm-12 margin-bottom">
        <div class="form-group">
            <label for="sap_tumblr_custom_description" class="col-sm-4 control-label"><?php echo $sap_common->lang('custom_message'); ?>:
                <i class="fa fa-question-circle" data-container="body" data-toggle="popover" data-trigger="hover" data-placement="right" data-content="Here you can enter custom content which will appear underneath the post title in Tumblr. Leave it empty to use content of the current post."></i>
            </label>
        </div>
        <div class="col-sm-8">
            <textarea class="form-control" name="sap_tumblr_custom_description" id="sap_tumblr_custom_description" tabindex="28"><?php echo (!empty($sap_tumblr_custom_desc) ? $sap_tumblr_custom_desc : '');?></textarea>
        </div>
    </div>    
    <div class="col-sm-12">
        <div class="form-group">
            <label for="sap-schedule-time-tumblr" class="col-sm-4 control-label">
                <?php echo $sap_common->lang('individual_schedule'); ?>
                <i class="fa fa-question-circle" data-container="body" data-toggle="popover" data-trigger="hover" data-placement="right" data-content="This setting modifies the schedule global setting and overrides scheduled time. Keep it blank to use the global schedule settings."></i>
            </label>
            <div class="col-sm-2">
                <input type="text" name="sap-schedule-time-tumblr" id="sap-schedule-time-tumblr" placeholder="YYYY-MM-DD hh:mm" <?php echo !empty($sap_schedule_time_tumblr) ? 'value="' . date('Y-m-d H:i', $sap_schedule_time_tumblr) . '"' : ''; ?> readonly="" class="form-control sap-datetime fb-schedule-input">
            </div>
        </div>
    </div>
    <input type="hidden" name="networks[tumblr]" id="enable_tumblr" value="1">
</div>