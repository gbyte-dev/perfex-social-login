<?php

/* Check the absolute path to the Social Auto Poster directory. */
if ( !defined( 'SAP_APP_PATH' ) ) {
    // If SAP_APP_PATH constant is not defined, perform some action, show an error, or exit the script
    // Or exit the script if required
    exit();
}

global $sap_common;
$selected = '';
$li_profile_data = $linkedin->sap_li_get_profiles_data();
if (!empty($post_id)) {
    $status = $this->get_post_meta($post_id, '_sap_li_status');
    $sap_linkedin_custom_title = $this->get_post_meta($post_id, '_sap_li_post_title');
    $sap_linkedin_custom_img = $this->get_post_meta($post_id, '_sap_li_post_image');
    $sap_linkedin_custom_link = $this->get_post_meta($post_id, '_sap_li_post_link');
    $sap_linkedin_custom_desc = $this->get_post_meta($post_id, '_sap_li_post_desc');
    $sap_linkedin_post_details = explode(",",$this->get_post_meta($post_id, '_sap_li_post_profile'));
    $sap_schedule_time_li = $this->get_post_meta($post_id, 'sap_schedule_time_li');

}
$linkedin_post_status = array('Unpublished', 'Published', 'Scheduled');
$sap_linkedin_sess_data = $this->settings->get_user_setting('sap_li_sess_data');

?>
<div class="row">
    <div class="col-sm-12 margin-bottom">
        <?php if( empty( $sap_linkedin_sess_data ) ) {?>
        <div class="col-sm-12">
            <div class="alert alert-danger sap-warning">
                <i class="fa fa-info-circle" aria-hidden="true"></i>
                <?php echo $sap_common->lang('quick_post_li_help_msg'); ?>
                   
            </div>
        </div> 
        <?php } ?>

        <div class="form-group">
            <label for="status" class="col-sm-4 col-xs-5 control-label"><?php echo $sap_common->lang('status'); ?>:
                <i class="fa fa-question-circle" data-container="body" data-toggle="popover" data-trigger="hover" data-placement="right" data-content="Status of LinkedIn post i.e. published/unpublished/scheduled."></i>
            </label>
            <div class="col-sm-8 col-xs-7">
                <?php
                if (isset($status) && array_key_exists($status, $linkedin_post_status)) {
                   echo '<label class="_sap_li_status_lbl status-text">'.$linkedin_post_status[$status].'</label>';
                   echo '<button class="btn btn-primary reset_post_status btn-xs" aria-data-id="'.$post_id.'" aria-type="linkedin" aria-label="_sap_li_status" type="button" ><i class="fa fa-refresh" aria-hidden="true"></i> '.$sap_common->lang('reset_status').'</button>';
                }else{
                    echo '<label class="_sap_li_status_lbl status-Unpublished">'.$sap_common->lang('unpublished').'</label> ';
                }?>              
            </div>
        </div>
       
    </div>

    <div class="col-md-12 margin-bottom">
        <div class="form-group">
            <label for="sap_linkedin_user_id" class="col-sm-4 control-label"><?php echo $sap_common->lang('post_to_li'); ?>:
                <i class="fa fa-question-circle" data-container="body" data-toggle="popover" data-trigger="hover" data-placement="right" data-content="Select an account to which you want to publish a post. This setting overrides the general settings. Leave it empty to use the general default settings."></i>
            </label>
            <div class="col-md-8">
                <select class="form-control select2 width-100" id="sap_linkedin_user_id" name="sap_linkedin_user_id[]" multiple="multiple" data-placeholder="Select User">
                    <?php
                    if (!empty($li_profile_data)) {
                        foreach ($li_profile_data as $profile_id => $profile_name) {

                            if(!empty($sap_linkedin_post_details)){

                              $selected = in_array($profile_id, $sap_linkedin_post_details) ? 'selected=selected' : '';

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
            <label for="sap_linkedin_custom_link" class="col-sm-4 control-label"><?php echo $sap_common->lang('custom_link'); ?>:
                <i class="fa fa-question-circle" data-container="body" data-toggle="popover" data-trigger="hover" data-placement="right" data-content="Here you can enter the custom link which will be used for the wall post. Leave it empty to use the link of the current content. The link must start with http://"></i>
            </label>
            <div class="col-sm-8">
                <input type="text" tabindex="21" class="form-control sap-valid-url" name="sap_linkedin_custom_link" id="sap_linkedin_custom_link" value="<?php echo (!empty($sap_linkedin_custom_link) ? $sap_linkedin_custom_link :'');?>" placeholder="<?php echo $sap_common->lang('custom_link'); ?>" />
                <div class="alert alert-info linkedin-multi-post-note"><i><?php echo sprintf($sap_common->lang('provide_link_li'),'<a href="#sap-valid-url">','</a>'); ?></i></div>
            </div>
        </div>
    </div>

    <div class="col-sm-12 margin-bottom">
        <div class="form-group">
            <label for="sap_linkedin_post_img" class="col-sm-4 control-label"><?php echo $sap_common->lang('post_image'); ?>:
                <i class="fa fa-question-circle" data-container="body" data-toggle="popover" data-trigger="hover" data-placement="right" data-content="Here you can upload a image which will be used for the LinkedIn wall post. Leave it empty to use the content image. if content image is also blank, then it will take default image from the settings page."></i>
            </label>
        </div>
        <div class="col-sm-8">

        <?php if(!empty($sap_linkedin_custom_img)) { ?>
            <input id="sap_linkedin_post_img" value="<?php echo!empty($_FILES['sap_linkedin_post_img']) ? $_FILES['sap_linkedin_post_img']['tmp_name'] : '' ?>" name="sap_linkedin_post_img" type="file" class="file file-loading" data-show-upload="false" data-show-caption="true" data-allowed-file-extensions='["png", "jpg","jpeg", "gif"]' tabindex="20" data-initial-preview="<img src='<?php echo SAP_IMG_URL.$sap_linkedin_custom_img;?>' class='uploaded-img'/>">
        <?php } else { ?>
             <input id="sap_linkedin_post_img" value="<?php echo!empty($_FILES['sap_linkedin_post_img']) ? $_FILES['sap_linkedin_post_img']['tmp_name'] : '' ?>" name="sap_linkedin_post_img" type="file" class="file file-loading" data-show-upload="false" data-show-caption="true" data-allowed-file-extensions='["png", "jpg","jpeg", "gif"]' tabindex="20">
        <?php } ?>
            <input type="hidden" name="sap_linkedin_post_img" class="sap-default-img" value="<?php echo !empty( $sap_linkedin_custom_img )? $sap_linkedin_custom_img :'';  ?>">
        </div>
    </div>

    <div class="col-sm-12 margin-bottom">
        <div class="form-group">
            <label for="sap_linkedin_custom_title" class="col-sm-4 control-label"><?php echo $sap_common->lang('custom_title'); ?>:
                <i class="fa fa-question-circle" data-container="body" data-toggle="popover" data-trigger="hover" data-placement="right" data-content="Here you can enter a custom title which will be used as a title of the LinkedIn post." data-html="true"></i>
            </label>
            <div class="col-sm-8">                
                <textarea class="form-control" name="sap_linkedin_custom_title" id="sap_linkedin_custom_title" tabindex="5"><?php echo (!empty($sap_linkedin_custom_title) ? $sap_linkedin_custom_title : '');?></textarea>
            </div>
        </div>
    </div>

    <div class="col-sm-12 margin-bottom">
        <div class="form-group">
            <label for="sap_linkedin_custom_description" class="col-sm-4 control-label"><?php echo $sap_common->lang('custom_message'); ?>:
                <i class="fa fa-question-circle" data-container="body" data-toggle="popover" data-trigger="hover" data-placement="right" data-content="Here you can enter a custom content which will be used by LinkedIn for the link description on the wall post. Leave it empty to use content of the current post."></i>
            </label>
	        <div class="col-sm-8">
	            <textarea class="form-control" name="sap_linkedin_custom_description" id="sap_linkedin_custom_description" tabindex="23"><?php echo !empty($sap_linkedin_custom_desc) ? $sap_linkedin_custom_desc :'';?></textarea>
	        </div>
         </div>
    </div>
    <div class="col-sm-12">
        <div class="form-group">
            <label for="sap-schedule-time-li" class="col-sm-4 control-label">
                <?php echo $sap_common->lang('individual_schedule'); ?>
                <i class="fa fa-question-circle" data-container="body" data-toggle="popover" data-trigger="hover" data-placement="right" data-content="This setting modifies the schedule global setting and overrides scheduled time. Keep it blank to use the global schedule settings."></i>
            </label>
            <div class="col-sm-2">
                <input type="text" name="sap-schedule-time-li" id="sap-schedule-time-li" placeholder="YYYY-MM-DD hh:mm" <?php echo !empty($sap_schedule_time_li) ? 'value="' . date('Y-m-d H:i', $sap_schedule_time_li) . '"' : ''; ?> readonly="" class="form-control sap-datetime fb-schedule-input">
            </div>
        </div>
    </div>
    <input type="hidden" name="networks[linkedin]" id="enable_linkedin" value="1">
</div>