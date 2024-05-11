<?php

/* Check the absolute path to the Social Auto Poster directory. */
if ( !defined( 'SAP_APP_PATH' ) ) {
    // If SAP_APP_PATH constant is not defined, perform some action, show an error, or exit the script
    // Or exit the script if required
    exit();
}

global $sap_common;
$selected = '';
$gmb_user_locations = $google_business->sap_add_gmb_locations();

if (!empty($post_id)) {

    $status = $this->get_post_meta($post_id, '_sap_gmb_status');
    $sap_gmb_custom_msg = $this->get_post_meta($post_id, '_sap_gmb_post_msg');
    $sap_gmb_custom_img = $this->get_post_meta($post_id, '_sap_gmb_post_image');
    $sap_gmb_post_accounts = $this->get_post_meta($post_id, '_sap_gmb_post_accounts');
    $sap_gmb_button_type = $this->get_post_meta($post_id, '_sap_gmb_post_button_type');
    $sap_schedule_time_gmb = $this->get_post_meta($post_id, 'sap_schedule_time_gmb');

}
$sap_gmb_post_status = array('Unpublished', 'Published', 'Scheduled');
$sap_gmb_sess_data = $this->settings->get_user_setting('sap_google_business_sess_data'); 

?>
<div class="row">
    <div class="col-sm-12 margin-bottom">
        <?php if(empty($sap_gmb_sess_data)) { ?>
            <div class="col-sm-12">
                <div class="alert alert-danger sap-warning">
                    <i class="fa fa-info-circle" aria-hidden="true"></i>
                    <?php echo $sap_common->lang('set_gmb_msg'); ?>
                </div>
            </div> 
        <?php } ?>
        <div class="form-group">
            <label class="col-sm-4 col-xs-5"><?php echo $sap_common->lang('status'); ?>:
                <i class="fa fa-question-circle" data-container="body" data-toggle="popover" data-trigger="hover" data-placement="right" data-content="Status of Google My Business post i.e. published/unpublished/scheduled."></i>
            </label>
            <div class="col-sm-8 col-xs-7">
                <?php
                if (isset($status) && array_key_exists($status, $sap_gmb_post_status)) {
                   echo '<label class="_sap_gmb_status_lbl status-text">'.$sap_gmb_post_status[$status].'</label>';
                   echo '<button class="btn btn-primary reset_post_status btn-xs" aria-data-id="'.$post_id.'" aria-type="gmb" aria-label="_sap_gmb_status" type="button"><i class="fa fa-refresh" aria-hidden="true"></i>'.$sap_common->lang('reset_status').' </button>';
               } else {
                echo '<label class="_sap_gmb_status_lbl status-Unpublished">'.$sap_common->lang('unpublished').'</label>';
            }
            ?>
        </div>
        </div>
    </div>


    <div class="col-sm-12 margin-bottom">
        <div class="form-group">
            <label for="sap_gmb_location_id" class="col-sm-4 control-label"><?php echo $sap_common->lang('post_to_gmb'); ?>:
                <i class="fa fa-question-circle" data-container="body" data-trigger="hover" data-toggle="popover" data-placement="right" data-content="Select locations to which you want to publish a post. This setting overrides the default settings. Leave it empty to use the general default settings."></i>
            </label>
            <div class="col-sm-8">
                <select class="form-control sap_select" tabindex="6" name="sap_gmb[accounts][]" multiple="multiple" id="sap_gmb_user_id" data-placeholder="Select Locations">
                    <?php
                    if (!empty($gmb_user_locations) && is_array($gmb_user_locations)) {
                        $gmb_type_location = (!empty($sap_gmb_post_accounts)) ? $sap_gmb_post_accounts : array();
                        foreach ($gmb_user_locations as $aid => $aval) {
                            ?>
                            <option <?php echo in_array($aid, $gmb_type_location) ? 'selected="selected"' : ''; ?> value="<?php echo $aid; ?>" ><?php echo $aval; ?></option>
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
        <label for="sap_gmb_button_type" class="col-sm-4 control-label"><?php echo $sap_common->lang('select_button_type'); ?>:
            <i class="fa fa-question-circle" data-container="body" data-trigger="hover" data-toggle="popover" data-placement="right" data-content="Select type of button. This setting overrides the general settings. Leave it empty to use the general default settings."></i>
        </label>
        <div class="col-sm-8">
            <?php   
            $button_type_options = array(

                "BOOK"       => $sap_common->lang('book'),
                "ORDER"      => $sap_common->lang('order_online'),
                "SHOP"       => $sap_common->lang('buy'),
                "LEARN_MORE" => $sap_common->lang('learn_more'),
                "SIGN_UP"    => $sap_common->lang('sign_up'),
                "CALL"       => $sap_common->lang('call')    
            );
            ?>
            <select class="form-control sap_select" tabindex="6" name="sap_gmb[gmb_button_type]" id="sap_gmb_button_type" data-placeholder="Select Button Type">
                <?php    
                if(!empty($button_type_options)) {
                    $sap_gmb_button_type = (!empty($sap_gmb_button_type)) ? $sap_gmb_button_type : 'LEARN_MORE'; 
                    foreach ($button_type_options as $button_id => $button_label) { ?>
                        <option value="<?php echo $button_id ?>" <?php  echo ($button_id == $sap_gmb_button_type) ?  'selected="selected"'  : "" ; ?>><?php echo $button_label ?></option> 
                        <?php
                    }
                }     
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
            <input type="text" tabindex="21" class="form-control sap-valid-url" name="sap_gmb_custom_link" id="sap_gmb_custom_link" value="<?php echo (!empty($sap_gmb_custom_link) ? $sap_gmb_custom_link :'');?>" placeholder="<?php echo $sap_common->lang('custom_link'); ?>" />
            <div class="alert alert-info linkedin-multi-post-note"><i><?php echo sprintf($sap_common->lang('provide_link_gmb'),'<a href="#sap-valid-url">','</a>'); ?></i></div>
        </div>
    </div>
</div>

<div class="col-sm-12 margin-bottom">
    <div class="form-group">
        <label for="sap_gmb_post_img" class="col-sm-4 control-label">
            <?php echo $sap_common->lang('post_image'); ?>:
            <i class="fa fa-question-circle" data-container="body" data-trigger="hover" data-toggle="popover" data-placement="right" data-content="Here you can upload an image which will be used for the Google My Business posting. Leave it empty to use the general default settings." data-html="true"></i>
        </label>
        <div class="col-sm-8">
            <?php if(!empty($sap_gmb_custom_img)) { ?>
                <input id="sap_gmb_post_img" name="sap_gmb_post_img" type="file" class="file file-loading" data-show-upload="false" data-show-caption="true" data-allowed-file-extensions='["png", "jpg","jpeg", "gif"]' tabindex="8" data-initial-preview="<img src='<?php echo SAP_IMG_URL.$sap_gmb_custom_img;?>'/>">
            <?php } else { ?>
                <input id="sap_gmb_post_img" name="sap_gmb_post_img" type="file" class="file file-loading" data-show-upload="false" data-show-caption="true" data-allowed-file-extensions='["png", "jpg","jpeg", "gif"]' tabindex="8">
            <?php } ?>
            <input type="hidden" name="sap_gmb_post_img" class="sap-default-img" value="<?php echo !empty( $sap_gmb_custom_img )? $sap_gmb_custom_img :'';  ?>">
        </div>
    </div>
</div>

<div class="col-sm-12 margin-bottom">
    <div class="form-group">
        <label for="sap_gmb_post_custom_message" class="col-sm-4 control-label"><?php echo $sap_common->lang('custom_message'); ?>:
            <i class="fa fa-question-circle" data-container="body" data-toggle="popover" data-trigger="hover" data-placement="right" data-content="Here you can enter a custom content which will be used for the Google My Business post. Leave it empty to use the general default settings." data-html="true"></i>
        </label>
        <div class="col-sm-8">                
            <textarea class="form-control" name="sap_gmb[message]" id="sap_gmb_post_custom_message" tabindex="5"><?php echo (!empty($sap_gmb_custom_msg) ? $sap_gmb_custom_msg : '');?></textarea>
        </div>
    </div>
</div>
<div class="col-sm-12">
        <div class="form-group">
            <label for="sap-schedule-time-gmb" class="col-sm-4 control-label">
                <?php echo $sap_common->lang('individual_schedule'); ?>
                <i class="fa fa-question-circle" data-container="body" data-toggle="popover" data-trigger="hover" data-placement="right" data-content="This setting modifies the schedule global setting and overrides scheduled time. Keep it blank to use the global schedule settings."></i>
            </label>
            <div class="col-sm-2">
                <input type="text" name="sap-schedule-time-gmb" id="sap-schedule-time-gmb" placeholder="YYYY-MM-DD hh:mm" <?php echo !empty($sap_schedule_time_gmb) ? 'value="' . date('Y-m-d H:i', $sap_schedule_time_gmb) . '"' : ''; ?> readonly="" class="form-control sap-datetime fb-schedule-input">
            </div>
        </div>
    </div>
    <input type="hidden" name="networks[gmb]" id="enable_gmb" value="1">
</div>




