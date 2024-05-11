<?php 

/* Check the absolute path to the Social Auto Poster directory. */
if ( !defined( 'SAP_APP_PATH' ) ) {
    // If SAP_APP_PATH constant is not defined, perform some action, show an error, or exit the script
    // Or exit the script if required
    exit();
}

global $sap_common;
$reddit_accounts = $reddit->sap_get_reddit_accounts();

if (!empty($post_id)) {

    $status = $this->get_post_meta($post_id, '_sap_reddit_status');

    $sap_reddit_post_type = $this->get_post_meta($post_id, '_sap_reddit_post_type');

    $sap_reddit_custom_msg = $this->get_post_meta($post_id, '_sap_reddit_post_msg');
 
    $sap_reddit_post_image = $this->get_post_meta($post_id, '_sap_reddit_post_img');

    $sap_reddit_post_accounts = $this->get_post_meta($post_id, '_sap_reddit_post_accounts');

    $sap_schedule_time_reddit = $this->get_post_meta($post_id, 'sap_schedule_time_reddit');
       
}

$reddit_post_status = array('Unpublished', 'Published', 'Scheduled');
$sap_reddit_options = $this->settings->get_user_setting('sap_reddit_options');

$sap_reddit_grant_data = $this->settings->get_user_setting('sap_reddit_sess_data'); 


?>

<div class="row">
    <div class="col-sm-12 margin-bottom">

        <?php if ( empty( $reddit_accounts ) ){?>
            <div class="col-sm-12">
                <div class="alert alert-danger sap-warning">
                    <i class="fa fa-info-circle" aria-hidden="true"></i>
                    <?php echo $sap_common->lang('quick_post_reddit_cnofig_msg'); ?>   
                </div>
            </div>
        <?php }?>
        <div class="form-group">
           
            <label class="col-sm-4 col-xs-5"><?php echo $sap_common->lang('status'); ?>:
                <i class="fa fa-question-circle" data-trigger="hover" data-container="body" data-toggle="popover" data-placement="right" data-content="Status of Reddit post i.e. published/unpublished/scheduled."></i>
            </label>

            <div class="col-sm-8 col-xs-7">
                <?php
                  
                if (isset($status) && array_key_exists($status, $reddit_post_status)) {

                   echo '<label class="_sap_reddit_status_lbl status-text">'.$reddit_post_status[$status].'</label>';
                   echo '<button class="btn btn-primary reset_post_status btn-xs" aria-data-id="'.$post_id.'" aria-type="reddit" aria-label="_sap_reddit_status" type="button" ><i class="fa fa-refresh" aria-hidden="true"></i> '.$sap_common->lang('reset_status').'</button>';

               }else{
                echo '<label class="_sap_reddit_status_lbl status-Unpublished">'.$sap_common->lang('unpublished').'</label>';
            }?>  
            
        </div>
    </div>

</div>

<div class="col-sm-12 margin-bottom">
    <div class="form-group">
        <label class="col-sm-4" for="sap_reddit_user_id">
            <?php echo $sap_common->lang('post_to_reddit'); ?>:
            <i class="fa fa-question-circle" data-trigger="hover" data-container="body" data-toggle="popover" data-placement="right" data-content="Select an account to which you want to Post. This setting overrides the general settings. Leave it empty to use the general default settings."></i>
        </label>
        <div class="col-sm-8">
            <select class="form-control sap_select" tabindex="14" name="sap_reddit_user_id[]" multiple="multiple" id="sap_reddit_user_id" data-placeholder="Select User">
                <?php
                        
                    
                if (!empty($reddit_accounts)) {
                    foreach ($reddit_accounts as $uid => $uname) { 
                        $selected = "";
                         if( !empty( $sap_reddit_post_accounts ) && in_array($uid, $sap_reddit_post_accounts) ){
                            $selected = ' selected="selected"';
                         } 
                        ?>
                        <option value="<?php echo $uid; ?>"<?php print $selected;?>><?php echo $uname; ?></option>
                    <?php } 
                } ?>
            </select>
        </div>
    </div>
</div>

<div class="col-sm-12 margin-bottom">
    <?php if(empty($sap_reddit_options['disable_image_reddit'])) { ?>
        <div class="form-group">
            <label for="sap_reddit_post_img" class="col-sm-4 control-label">
                <?php echo $sap_common->lang('post_image'); ?>:
                <i class="fa fa-question-circle" data-trigger="hover" data-container="body" data-toggle="popover" data-placement="right" data-content="Here you can upload an image which will be used as a Reddit Image. Leave it empty to use the general default settings."></i>
            </label>
            <div class="col-sm-8">
                <?php if(!empty($sap_reddit_post_image)) { ?>
                    <input id="sap_reddit_post_img" name="sap_reddit_post_img" type="file" class="file file-loading" data-show-upload="false" data-show-caption="true" data-allowed-file-extensions='["png", "jpg","jpeg", "gif"]' tabindex="20" data-initial-preview="<img src='<?php echo SAP_IMG_URL.$sap_reddit_post_image;?>' class='uploaded-img'/>">
                <?php } else { ?>
                    <input id="sap_reddit_post_img" name="sap_reddit_post_img" type="file" class="file file-loading" data-show-upload="false" data-show-caption="true" data-allowed-file-extensions='["png", "jpg","jpeg", "gif"]' tabindex="20">
                <?php } ?>
                <input type="hidden" name="sap_reddit_post_img" class="sap-default-img" value="<?php echo !empty( $sap_reddit_post_image )? $sap_reddit_post_image :'';  ?>">
            </div>
        </div>
    <?php } ?>
</div>


<div class="col-sm-12 margin-bottom">
    <div class="form-group">
            <label for="sap_reddit_post_type" class="col-sm-4 control-label">
                <?php echo $sap_common->lang('posting_type'); ?>:
                <i class="fa fa-question-circle" data-container="body" data-toggle="popover" data-trigger="hover" data-placement="right" data-content="Choose posting type which you want to use. Leave it empty to use the default one from the general settings."></i>
            </label>
       
        <div class="col-sm-8">
            <select class="form-control" name="sap_reddit_post_type" id="sap_reddit_post_type" tabindex="25">
                <option <?php echo (!empty( $sap_reddit_post_type ) && $sap_reddit_post_type == 'self') ? ' selected="selected" ' : '';  ?> value="self"><?php echo $sap_common->lang('text'); ?></option>
                <option <?php echo (!empty( $sap_reddit_post_type ) && $sap_reddit_post_type == 'link') ? ' selected="selected" ' : '';  ?> value="link"><?php echo $sap_common->lang('link'); ?></option>
                <option <?php echo (!empty( $sap_reddit_post_type ) && $sap_reddit_post_type == 'image') ? ' selected="selected" ' : '';  ?> value="image"><?php echo $sap_common->lang('photo'); ?></option>
            </select>
        </div>
     </div>    
</div>
<div class="col-sm-12 margin-bottom">     
    <div class="form-group">
        <label for="sap_reddit_msg" class="col-sm-4 control-label"><?php echo $sap_common->lang('reddit_custom_message'); ?><span class="astric">*</span>:
            <i class="fa fa-question-circle" data-container="body" data-toggle="popover" data-trigger="hover" data-placement="right" data-content="Here you can enter a title  which will be used for the reddit." data-html="true"></i>
        </label>
        <div class="col-sm-8 sap-msg-wrap">                
            <input type="text" name="sap_reddit_msg" class="form-control" id="sap_reddit_msg" tabindex="5" value="<?php echo (!empty($sap_reddit_custom_msg) ? $sap_reddit_custom_msg : '');?>" />
        </div>
    </div>
</div>
    <div class="col-sm-12">
        <div class="form-group">
            <label for="sap-schedule-time-reddit" class="col-sm-4 control-label">
                <?php echo $sap_common->lang('individual_schedule'); ?>
                <i class="fa fa-question-circle" data-container="body" data-toggle="popover" data-trigger="hover" data-placement="right" data-content="This setting modifies the schedule global setting and overrides scheduled time. Keep it blank to use the global schedule settings."></i>
            </label>
            <div class="col-sm-2">
                <input type="text" name="sap-schedule-time-reddit" id="sap-schedule-time-reddit" placeholder="YYYY-MM-DD hh:mm" <?php echo !empty($sap_schedule_time_reddit) ? 'value="' . date('Y-m-d H:i', $sap_schedule_time_reddit) . '"' : ''; ?> readonly="" class="form-control sap-datetime fb-schedule-input">
            </div>
        </div>
    </div>
    <input type="hidden" name="networks[reddit]" id="enable_reddit" value="1">
</div>