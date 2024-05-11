<?php 

/* Check the absolute path to the Social Auto Poster directory. */
if ( !defined( 'SAP_APP_PATH' ) ) {
    // If SAP_APP_PATH constant is not defined, perform some action, show an error, or exit the script
    // Or exit the script if required
    exit();
}

global $sap_common;
$sap_twitter_accounts_details = $this->settings->get_user_setting('sap_twitter_accounts_details');
$sap_twitter_options 		  = $this->settings->get_user_setting('sap_twitter_options');
$sap_tweet_accounts = array();
if (!empty($post_id)) {

    $status = $this->get_post_meta($post_id, '_sap_tw_status');
    $sap_tweet_custom_img = $this->get_post_meta($post_id, '_sap_tw_image');
    $sap_tweet_template   = $this->get_post_meta($post_id, '_sap_tw_template');
    $sap_tweet_accounts   = $this->get_post_meta($post_id, '_sap_tw_accounts');
    $sap_twitter_msg      = $this->get_post_meta($post_id, '_sap_tw_msg');
    $sap_schedule_time_tw = $this->get_post_meta($post_id, 'sap_schedule_time_tw');

}

$twitter_post_status = array('Unpublished', 'Published', 'Scheduled');


?>

<div class="row">
    <div class="col-sm-12 margin-bottom">

        <?php if ( empty( $sap_twitter_accounts_details ) ){?>
            <div class="col-sm-12">
                <div class="alert alert-danger sap-warning">
                    <i class="fa fa-info-circle" aria-hidden="true"></i>
                    <?php echo $sap_common->lang('quick_post_twi_cnofig_msg'); ?>   
                </div>
            </div>
        <?php }?>
        <div class="form-group">
            <label class="col-sm-4 col-xs-5"><?php echo $sap_common->lang('status'); ?>:
                <i class="fa fa-question-circle" data-trigger="hover" data-container="body" data-toggle="popover" data-placement="right" data-content="Status of Twitter post i.e. published/unpublished/scheduled."></i>
            </label>
            <div class="col-sm-8 col-xs-7">
                <?php
                if (isset($status) && array_key_exists($status, $twitter_post_status)) {
                   echo '<label class="_sap_tw_status_lbl">'.$twitter_post_status[$status].'</label>&nbsp;&nbsp;';
                   echo '<button class="btn btn-primary reset_post_status btn-xs" aria-data-id="'.$post_id.'" aria-type="facebook" aria-label="_sap_tw_status" type="button" ><i class="fa fa-refresh" aria-hidden="true"></i> '.$sap_common->lang('reset_status').'</button>';
               }else{
                echo '<label class="_sap_tw_status_lbl status-Unpublished">'.$sap_common->lang('unpublished').'</label>';
            }?>  
            
        </div>
    </div>

</div>

<div class="col-sm-12 margin-bottom">
    <div class="form-group">
        <label class="col-sm-4" for="sap_twitter_user_id">
            <?php echo $sap_common->lang('post_to_twi'); ?>:
            <i class="fa fa-question-circle" data-trigger="hover" data-container="body" data-toggle="popover" data-placement="right" data-content="Select an account to which you want to Tweet. This setting overrides the general settings. Leave it empty to use the general default settings."></i>
        </label>
        <div class="col-sm-8">
            <select class="form-control sap_select" tabindex="14" name="sap_twitter_user_id[]" multiple="multiple" id="sap_twitter_user_id" data-placeholder="Select User">
                <?php
                if (!empty($sap_twitter_accounts_details)) {
                    foreach ($sap_twitter_accounts_details as $key => $profile_details) { 
                        $selected = "";
                        if( !empty( $sap_tweet_accounts ) && in_array($key, $sap_tweet_accounts) ){
                            $selected = ' selected="selected"';
                        } 
                        ?>
                        <option value="<?php echo $key; ?>"<?php print $selected;?>><?php echo $profile_details['name']; ?></option>
                    <?php } 
                } ?>
            </select>
        </div>
    </div>
</div>

<div class="col-sm-12 margin-bottom">
    <?php if(empty($sap_twitter_options['disable_image_tweet'])) { ?>
        <div class="form-group">
            <label for="sap_tweet_img" class="col-sm-4 control-label">
                <?php echo $sap_common->lang('post_image'); ?>:
                <i class="fa fa-question-circle" data-trigger="hover" data-container="body" data-toggle="popover" data-placement="right" data-content="Here you can upload an image which will be used as a Tweet Image. Leave it empty to use the general default settings."></i>
            </label>
            <div class="col-sm-8">
                <?php if(!empty($sap_tweet_custom_img)) { ?>
                    <input id="sap_tweet_img" name="sap_tweet_img" type="file" class="file file-loading" data-show-upload="false" data-show-caption="true" data-allowed-file-extensions='["png", "jpg","jpeg", "gif"]' tabindex="20" data-initial-preview="<img src='<?php echo SAP_IMG_URL.$sap_tweet_custom_img;?>' class='uploaded-img'/>">
                <?php } else { ?>
                    <input id="sap_tweet_img" name="sap_tweet_img" type="file" class="file file-loading" data-show-upload="false" data-show-caption="true" data-allowed-file-extensions='["png", "jpg","jpeg", "gif"]' tabindex="20">
                <?php } ?>
                <input type="hidden" name="sap_tweet_img" class="sap-default-img" value="<?php echo !empty( $sap_tweet_custom_img )? $sap_tweet_custom_img :'';  ?>">
            </div>
        </div>
    <?php } ?>
</div>


<div class="col-sm-12 margin-bottom">
    <div class="form-group">
        <label for="sap_tw_post_custom_message" class="col-sm-4 control-label"><?php echo $sap_common->lang('custom_message'); ?>:
            <i class="fa fa-question-circle" data-container="body" data-toggle="popover" data-trigger="hover" data-placement="right" data-content="Here you can enter a custom content which will be used for the tweet. Leave it empty to use the content." data-html="true"></i>
        </label>
        <div class="col-sm-8">                
            <textarea class="form-control" name="sap_twitter_msg" id="sap_twitter_msg" tabindex="5"><?php echo (!empty($sap_twitter_msg) ? $sap_twitter_msg : '');?></textarea>
        </div>
    </div>
</div>
<div class="col-sm-12">
        <div class="form-group">
            <label for="sap-schedule-time-tw" class="col-sm-4 control-label">
                <?php echo $sap_common->lang('individual_schedule'); ?>
                <i class="fa fa-question-circle" data-container="body" data-toggle="popover" data-trigger="hover" data-placement="right" data-content="This setting modifies the schedule global setting and overrides scheduled time. Keep it blank to use the global schedule settings."></i>
            </label>
            <div class="col-sm-2">
                <input type="text" name="sap-schedule-time-tw" id="sap-schedule-time-tw" placeholder="YYYY-MM-DD hh:mm" <?php echo !empty($sap_schedule_time_tw) ? 'value="' . date('Y-m-d H:i', $sap_schedule_time_tw) . '"' : ''; ?> readonly="" class="form-control sap-datetime fb-schedule-input">
            </div>
        </div>
    </div>
    <input type="hidden" name="networks[twitter]" id="enable_twitter" value="1">
</div>