<?php

/* Check the absolute path to the Social Auto Poster directory. */
if ( !defined( 'SAP_APP_PATH' ) ) {
    // If SAP_APP_PATH constant is not defined, perform some action, show an error, or exit the script
    // Or exit the script if required
    exit();
}

?>
<div class="tab-pane <?php echo ( $active_tab == "gmb") ? "active" : "" ?>" id="gmb">
    <form id="google-business-settings" class="form-horizontal" method="POST" action="<?php echo SAP_SITE_URL . '/settings/save/'; ?>" enctype="multipart/form-data"> 
        <?php
        global $sap_common;
        if (isset($_GET['gmb_reset_user']) && $_GET['gmb_reset_user'] == 1 && !empty(($_GET['sap_gmb_userid']))) {

            $google_business->sap_gmb_reset_user_session();
        }

        //Get SAP options which stored
        $sap_google_business_options = $this->get_user_setting('sap_google_business_options');

        // Getting pinterest app grant data
        $sap_google_business_sess_data = $this->get_user_setting('sap_google_business_sess_data');
        $gmb_locations = $google_business->sap_add_gmb_locations();
        $gmb_user_user_accounts = $google_business->sap_get_gmb_accounts();
        //Url shortner options
        $shortner_options = $common->sap_get_all_url_shortners();
        ?>
        <div class="box box-primary border-b">
            <div class="box-header sap-settings-box-header"><?php echo $sap_common->lang('gmb_general_settings'); ?></div>
            <div class="box-body">            
                <div class="sap-box-inner">
                    <div class="form-group mb-0">
                        <label for="" class="col-sm-3 control-label"><?php echo $sap_common->lang('en_autopost_gmb'); ?></label>
                        <div class="tg-list-item col-sm-5">
                            <input class="tgl tgl-ios" name="sap_google_business_options[enable_google_business]" id="enable_google_business" <?php echo!empty($sap_google_business_options['enable_google_business']) ? 'checked="checked"' : ''; ?> type="checkbox" value="1">
                            <label class="tgl-btn float-right-cs-init" for="enable_google_business"></label>
                        </div>
                        <div class="col-md-12  pt-40">
                            <button type="submit" name="sap_google_business_submit" class="btn btn-primary sap_google_business_submit"><i class="fa fa-inbox"></i> <?php echo $sap_common->lang('save'); ?></button>
                        </div>
                    </div>
                </div>
            </div>
            <!-- <div class="box-footer">
                <div class="pull-right">
                    <button type="submit" name="sap_google_business_submit" class="btn btn-primary sap_google_business_submit"><i class="fa fa-inbox"></i> <?php echo $sap_common->lang('save'); ?></button>
                </div>
            </div> -->
        </div>

        <div class="box box-primary border-b">
            <div class="box-header sap-settings-box-header"  style="margin-bottom: 0"><?php echo $sap_common->lang('gmb_api_setting'); ?> </div>
            <div class="box-body">
                <div class="sap-box-inner sap-api-facebook-settings sap-api-google-business-settings">
                    <div id="facebook-app-method"   >
                        <?php
                        if (!empty($gmb_user_user_accounts)) {
                            echo '<div class="gmb-btn">';
                        }

                        echo '<p style="margin-bottom:30px"><a class="sap-grant-fb-android btn btn-primary sap-api-btn" href="' . $google_business->sap_get_gmb_app_method_login_url() . '"> '.$sap_common->lang('add_gmb_account').'</a></p>';

                        if (!empty($gmb_user_user_accounts)) {
                            echo '</div>';
                        }
                        if (!empty($gmb_user_user_accounts)) {
                            ?>
                            <div class="form-group form-head">
                                <label class="col-md-3"><?php echo $sap_common->lang('user_id'); ?></label>
                                <label class="col-md-3"><?php echo $sap_common->lang('account_name'); ?></label>
                                <label class="col-md-3 delete-account"><?php echo $sap_common->lang('action'); ?></label>
                            </div>  
                            <?php
                            $i = 0;
                            foreach ($gmb_user_user_accounts as $gmb_uid => $gmb_app_value) {
                                if (is_array($gmb_app_value)) {
                                    $gmb_user_data = $gmb_app_value;
                                    $gmb_app_reset_url = '?gmb_reset_user=1&sap_gmb_userid=' . $gmb_uid;
                                    ?>
                                    <div class="form-group form-deta">  
                                        <div class="col-md-3"><?php print $gmb_uid ?></div>
                                        <div class="col-md-3"><?php print $gmb_app_value['display_name']; ?></div>  
                                        <div class="col-md-3 delete-account">
                                            <a href="<?php print $gmb_app_reset_url; ?>"><?php echo $sap_common->lang('delete_account'); ?></a>
                                        </div>  
                                    </div>
                                    <?php
                                }
                            }
                        }
                        ?>
                    </div>
                </div>
            </div>

            <div class="box-footer">
                <div class=" ">
                    <button type="submit" name="sap_google_business_submit" class="btn btn-primary sap_google_business_submit"><i class="fa fa-inbox"></i> <?php echo $sap_common->lang('save'); ?></button>
                </div>
            </div>
        </div>

        <div class="box box-primary">
            <div class="box-header sap-settings-box-header"><?php echo $sap_common->lang('autopost_to_gmb'); ?></div>
            <div class="box-body">
                <div class="sap-box-inner sap-api-google-business-settings">

                    <div class="form-group">

                        <label for="app-setting" class="col-sm-3 control-label"><?php echo $sap_common->lang('autopost_to_gmb_users'); ?></label>

                        <div class='col-sm-6'>
                            <select class="sap_select" multiple="multiple"  name="sap_google_business_options[google_business_post_users][]">
                                <?php
                                $sap_selected_locations = !empty($sap_google_business_options['google_business_post_users']) ? ($sap_google_business_options['google_business_post_users']) : array();
                                if (!empty($gmb_locations)) {
                                    foreach ($gmb_locations as $location_id => $location_label) {
                                        ?>
                                        <option value="<?php echo $location_id ?>" <?php echo in_array($location_id, $sap_selected_locations) ? 'selected=selected' : ''; ?>><?php echo $location_label ?></option> 
                                        <?php
                                    }
                                }
                                ?>  
                            </select>
                        </div>
                    </div>


                    <div class="form-group"> 

                        <label for="app-setting" class="col-sm-3 control-label"><?php echo $sap_common->lang('gmb_btn_type'); ?></label>

                        <div class='col-sm-6'>
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
                            <select class="sap_select" id="sap_button_type"  name="sap_google_business_options[google_business_button_type]">          
                                <?php
                                $selected_button_type = !empty($sap_google_business_options['google_business_button_type']) ? ($sap_google_business_options['google_business_button_type']) : 'LEARN_MORE';
                                if (!empty($button_type_options)) {
                                    foreach ($button_type_options as $button_id => $button_label) {
                                        ?>
                                        <option value="<?php echo $button_id ?>" <?php
                                        if ($button_id == $selected_button_type) {
                                            echo 'selected=selected';
                                        } else {
                                            echo '';
                                        }
                                        ?>><?php echo $button_label ?></option> 
                                                <?php
                                            }
                                        }
                                        ?>
                            </select>
                        </div>
                    </div>

                    <div class='form-group'>                  

                        <label for="app-setting" class="col-sm-3 control-label"><?php echo $sap_common->lang('gmb_post_img'); ?></label>

                        <div class='col-sm-6 sap-gmb-img-wrap <?php echo (!empty($sap_google_business_options['gmb_image'])) ? 'gmb-hide-uploader' : '';?>'>
                            <?php
                            if (!empty($sap_google_business_options['gmb_image'])) {
                                ?>   
                                <div class="gmb-img-preview sap-img-preview">
                                    <img src="<?php echo SAP_IMG_URL . $sap_google_business_options['gmb_image']; ?>">
                                    <div class="cross-arrow">
                                        <a href="javascript:void(0)" data-upload_img=".sap-gmb-img-wrap .file-input" data-preview=".gmb-img-preview" title="Remove Google My Business Post Image" class="sap-setting-remove-img remove-tx-init"><i class="fa fa-close"></i></a>
                                    </div>
                                </div>
                            <?php } ?>
                            <input id="sap_gmb_img" name="gmb_image" type="file" class="file file-loading <?php echo!empty($sap_google_business_options['gmb_image']) ? 'sap-hide' : ''; ?>" data-show-upload="false" data-show-caption="true" data-allowed-file-extensions='["png", "jpg","jpeg", "gif"]' tabindex="15">
                            <input type="hidden" class="uploaded_img" name="sap_google_business_options[gmb_image]" value="<?php echo!empty($sap_google_business_options['gmb_image']) ? $sap_google_business_options['gmb_image'] : ''; ?>" >
                        </div>
                    </div>
                    <div class='form-group'>
                        <label for="app-setting" class="col-sm-3 control-label"><?php echo $sap_common->lang('url_shortener'); ?></label>
                        <div class='col-sm-6'>
                            <select class="sap_select sap-url-shortener-select" name="sap_google_business_options[gmb_type_shortner_opt]">
                                <?php
                                $selected_url_type = !empty($sap_google_business_options['gmb_type_shortner_opt']) ? $sap_google_business_options['gmb_type_shortner_opt'] : '';
                                foreach ($shortner_options as $key => $value) {
                                    $selected = "";
                                    if (!empty($selected_url_type) && $selected_url_type == $key) {
                                        $selected = ' selected="selected"';
                                    }
                                    ?>
                                    <option value="<?php echo $key; ?>"<?php echo $selected; ?>><?php echo $value; ?></option>
                                <?php } ?>
                            </select>                
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="" class="col-sm-3 control-label"><?php echo $sap_common->lang('bit_access_token'); ?></label>                      
                        <div class="col-sm-6">
                            <input type="text" class="form-control bitly-token" name="sap_google_business_options[gmb_bitly_access_token]" value="<?php echo!empty($sap_google_business_options['gmb_bitly_access_token']) ? $sap_google_business_options['gmb_bitly_access_token'] : ''; ?>" >     
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="" class="col-sm-3 control-label"><?php echo $sap_common->lang('shorte_api_token'); ?></label>                      
                        <div class="col-sm-6">
                            <input type="text" class="form-control shorte-token" name="sap_google_business_options[gmb_shortest_api_token]" value="<?php echo!empty($sap_google_business_options['gmb_shortest_api_token']) ? $sap_google_business_options['gmb_shortest_api_token'] : ''; ?>" >     
                        </div>
                    </div>
                </div>
            </div>
            <div class="box-footer">
                <div class="">
                    <button type="submit" name="sap_google_business_submit" class="btn btn-primary sap_google_business_submit"><i class="fa fa-inbox"></i> <?php echo $sap_common->lang('save'); ?></button>
                </div>
            </div>
        </div>
    </form>
</div>