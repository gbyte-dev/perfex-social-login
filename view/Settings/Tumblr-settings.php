<?php

/* Check the absolute path to the Social Auto Poster directory. */
if ( !defined( 'SAP_APP_PATH' ) ) {
    // If SAP_APP_PATH constant is not defined, perform some action, show an error, or exit the script
    // Or exit the script if required
    exit();
}

global $sap_common;
//Get Tumblr SAP options which stored
$sap_tumblr_options = $this->get_user_setting('sap_tumblr_options');

//Url shortner options
$shortner_options = $common->sap_get_all_url_shortners();

//Set Grant extend permission
if (!empty($_GET['authtumb']) && !empty($_GET['sap']) && $_GET['sap'] == 'tumblr') {

    //load tumblr class
    $tumblr->sap_grant_extended_permissions();
}

if (isset($_GET['auth']) && $_GET['auth'] == 'tumblr_auth' && isset($_GET['sap']) && $_GET['sap'] == 'tumblr') {

    //load tumblr class
    $tumblr->sap_tumblr_connect_data_store();
}

//Rset User
if (!empty($_GET['tumblr_reset_user']) && $_GET['tumblr_reset_user'] == 1) {

    //load tumblr class
    $tumblr->sap_reset_session();
}
?>

<div class="tab-pane <?php echo ( $active_tab == "tumblr") ? "active" : '' ?>" id="tumblr">
    <!-- form start -->
    <form id="tumblr-settings" class="form-horizontal" method="POST" action="<?php echo SAP_SITE_URL . '/settings/save/'; ?>" enctype="multipart/form-data"> 
        <?php
        $sap_tumblr_sess_data = $this->get_user_setting('sap_tumblr_sess_data');
        $get_tumblr_account_details = $tumblr->sap_fetch_tumblr_accounts();
        ?>
        <div class="box box-primary border-b">
            <div class="box-header sap-settings-box-header"><?php echo $sap_common->lang('tumb_general_settings'); ?> </div>
            <div class="box-body">
                <div class="sap-box-inner">
                    <div class="form-group">
                        <label for="" class="col-sm-3 control-label"><?php echo $sap_common->lang('en_autopost_tumb'); ?></label>
                        <div class="tg-list-item col-sm-9">
                            <input class="tgl tgl-ios" name="sap_tumblr_options[enable_tumblr]" id="enable_tumblr" <?php echo!empty($sap_tumblr_options['enable_tumblr']) ? 'checked="checked"' : ''; ?> type="checkbox" value="1">
                            <label class="tgl-btn float-right-cs-init" for="enable_tumblr"></label>
                        </div>
                    </div>
                </div>
                <div class="form-group">
                    <label for="" class="col-sm-3 control-label"><?php echo $sap_common->lang('post_content'); ?></label>
                    <div class="tg-list-item col-sm-9">
                        <div class="sap-radio-wrap col-md-6">
                            <div class="row">
                                <div class="col-md-5">
                                    <div class="row">
                                        <input name="sap_tumblr_options[post_content_size]" id="post_content_snippets" <?php echo ( empty($sap_tumblr_options['post_content_size']) || (!empty($sap_tumblr_options['post_content_size']) && $sap_tumblr_options['post_content_size'] == 'snippets' ) ) ? 'checked="checked"' : ''; ?> type="radio" value="snippets">
                                        <label for="post_content_snippets"><?php echo $sap_common->lang('snippets'); ?></label>
                                    </div>
                                </div>
                                <div class="col-md-5">
                                    <div class="row">
                                        <input name="sap_tumblr_options[post_content_size]" id="post_content_full" <?php echo (!empty($sap_tumblr_options['post_content_size']) && $sap_tumblr_options['post_content_size'] == 'full') ? 'checked="checked"' : ''; ?> type="radio" value="full">
                                        <label for="post_content_full"><?php echo $sap_common->lang('full'); ?></label>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <span class="sap-desc"> 
                            <?php echo $sap_common->lang('post_content_help_text'); ?> 
                        </span>
                    </div>
                </div>
            </div>
            <div class="box-footer">
                <div class="">
                    <button type="submit" name="sap_tumblr_submit" class="btn btn-primary sap-tumblr-submit"><i class="fa fa-inbox"></i> <?php echo $sap_common->lang('save'); ?></button>
                </div>
            </div>
        </div>
        <div class="box box-primary border-b">
            <div class="box-header sap-settings-box-header"><?php echo $sap_common->lang('tumb_api_settings'); ?> </div>
            <div class="box-body">
                <div class="sap-box-inner sap-api-tumblr-settings">
                    <div class="form-group">
                        <label for="app-setting" class="col-sm-3 control-label"><?php echo $sap_common->lang('tumb_application'); ?></label>
                        <div class="col-sm-12 documentation-text">
                            <?php echo sprintf($sap_common->lang('tumb_application_help_text'),'<span>','<a href="https://docs.wpwebelite.com/social-network-integration/tumblr/" target="_blank">','</a>','</span>'); ?>
                        </div>
                    </div>

                    <div class="form-group display_desktop">
                        <label class="col-sm-4 col-xs-12"><?php echo $sap_common->lang('tumb_oauth_consumer_key'); ?><span class="astric">*</span></label>
                        <label class="col-sm-4 col-xs-12"><?php echo $sap_common->lang('tumb_secret_key'); ?><span class="astric">*</span></label>
                        <label class="col-sm-4 col-xs-12"><?php echo $sap_common->lang('allowing_permissinons'); ?></label>
                    </div>
                    <?php
                    $sap_tumblr_keys = empty($sap_tumblr_options['tumblr_keys']) ? array(0 => array('tumblr_consumer_key' => '', 'tumblr_consumer_secret' => '')) : $sap_tumblr_options['tumblr_keys'];
                    if (!empty($sap_tumblr_keys)) {
                        $i = 0;
                        foreach ($sap_tumblr_keys as $key => $value) {
                            ?> 
                            <div class="form-group sap-tumblr-account-details" data-row-id="<?php echo $key; ?>">
                                <div class="col-md-12 remove-icon-tumblr tumblr-remove-image-icon">
                                    <div class="pull-right <?php echo ( $i == 0 ) ? 'sap-tumblr-main' : ''; ?>">
                                        <a href="javascript:void(0)" class="sap-tumblr-remove remove-tx-init"><i class="fa fa-close"></i></a>
                                    </div>
                                </div>
                                <div class="col-sm-4 display_mobile">
                                    <label class="heading-label"><?php echo $sap_common->lang('tumb_oauth_consumer_key'); ?></label>
                                    <input class="form-control sap-tumblr-consumer-key" name="sap_tumblr_options[tumblr_keys][<?php echo $key; ?>][tumblr_consumer_key]" value="<?php echo $value['tumblr_consumer_key']; ?>" placeholder="<?php echo $sap_common->lang('enter_customer_key'); ?>" type="text">
                                </div>
                                <div class="col-sm-4 display_mobile">
                                    <label class="heading-label"><?php echo $sap_common->lang('tumb_secret_key'); ?></label>
                                    <input class="form-control sap-tumblr-secret-key" name="sap_tumblr_options[tumblr_keys][<?php echo $key; ?>][tumblr_consumer_secret]" value="<?php echo $value['tumblr_consumer_secret']; ?>" placeholder="<?php echo $sap_common->lang('enter_tumblr_key'); ?>" type="text">
                                </div>
                                <?php
                                if (!empty($value['tumblr_consumer_key']) && !empty($value['tumblr_consumer_secret']) && !empty($sap_tumblr_sess_data[$value['tumblr_consumer_key']])) {

                                    $returnurl = SAP_SITE_URL . '/settings/';
                                    ?>

                                    <div class="col-sm-4 display_mobile custom-tumbler-text"><div class="sap-grant-reset-data">
                                            <label class="heading-label"><?php echo $sap_common->lang('allowing_permissions'); ?></label>
                                            <p  class="sap-grant-msg"><?php echo $sap_common->lang(''); ?></p>
                                            <a href="<?php echo $returnurl . '?tumblr_reset_user=1&app-id=' . $value['tumblr_consumer_key']; ?>"><?php echo $sap_common->lang('reset_user_session'); ?></a>
                                        </div></div>
                                <?php } else if (!empty($value['tumblr_consumer_key']) && !empty($value['tumblr_consumer_secret'])) {
                                    ?>
                                    <div class="col-sm-4 tumblr-grant-permission  tumblr-grant-permission-new ">
                                        <a href='<?php echo $tumblr->sap_get_login_url() . '&app-id=' . $value['tumblr_consumer_key']; ?>'><?php echo $sap_common->lang('grant_permission'); ?></a>
                                    </div>
                                <?php } ?>

                               
                            </div>
                            <?php
                            $i++;
                        }
                    }
                    ?>  
                    <div class="">
                        <div class="pull-right add-more">
                            <button type="button" class="btn btn-primary sap-add-more-tumblr-account"><i class="fa fa-plus"></i> <?php echo $sap_common->lang('add_more'); ?></button>
                        </div>
                    </div> 
                </div>
            </div>
            <div class="box-footer">
                <div class="">
                    <button type="submit" name="sap_tumblr_submit" class="btn btn-primary sap-tumblr-submit"><i class="fa fa-inbox"></i> <?php echo $sap_common->lang('save'); ?></button>
                </div>
            </div>
        </div>

        <div class="box box-primary">
            <div class="box-header sap-settings-box-header"><?php echo $sap_common->lang('autopost_to_tumb'); ?></div>
            <div class="box-body">
                <div class="sap-box-inner sap-api-tumblr-settings">

                    <div class="form-group">
                        <label for="tumblr-select-accounts" class="col-sm-3 control-label"><?php echo $sap_common->lang('autopost_to_tumb_users'); ?>)</label>
                        <div class="tg-list-item col-sm-6">
                            <select class="form-control sap_select" multiple="multiple" name="sap_tumblr_options[tumblr_type_post_accounts][]">
                                <?php
                                $tumblr_selected_user = !empty($sap_tumblr_options['tumblr_type_post_accounts']) ? ($sap_tumblr_options['tumblr_type_post_accounts']) : array();
                                if (!empty($get_tumblr_account_details) && is_array($get_tumblr_account_details) ) {
                                    foreach ($get_tumblr_account_details as $profile_id => $profile_name) {
                                        ?>
                                        <option value="<?php echo $profile_id; ?>" <?php echo in_array($profile_id, $tumblr_selected_user) ? 'selected=selected' : ''; ?>><?php echo $profile_name; ?></option>      
                                    <?php }
                                }
                                ?>
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="tumblr-post-users" class="col-sm-3 control-label"><?php echo $sap_common->lang('posting_type'); ?></label>
                        <div class="tg-list-item col-sm-6">
                            <?php
                            $tumblr_post_type = !empty($sap_tumblr_options['posting_type']) ? $sap_tumblr_options['posting_type'] : '';
                           
                            ?>
                            <select class="form-control sap_select sap-tumblr-post-type" name="sap_tumblr_options[posting_type]">
                                <option <?php echo $tumblr_post_type == 'text' ? 'selected="selected"' : ''; ?> value="text"><?php echo $sap_common->lang('text'); ?></option>
                                <option <?php echo $tumblr_post_type == 'link' ? 'selected="selected"' : ''; ?> value="link"><?php echo $sap_common->lang('link'); ?></option>
                                <option <?php echo $tumblr_post_type == 'photo' ? 'selected="selected"' : ''; ?> value="photo"><?php echo $sap_common->lang('photo'); ?></option>
                            </select>
                        </div>
                    </div>

                    <div class="form-group sap-tumblr-post-link">
                        <label for="sap_tumblr_link" class="col-sm-3 control-label"><?php echo $sap_common->lang('link'); ?></label>
                        <div class="tg-list-item col-sm-6">
                            <input type="link" tabindex="21" class="form-control sap-valid-url" name="sap_tumblr_options[tumblr_link]" id="sap_tumblr_link" placeholder="Link" value="<?php echo !empty($sap_tumblr_options['tumblr_link']) ? $sap_tumblr_options['tumblr_link'] : ''; ?>">
                        </div>
                    </div>

                    <div class="form-group sap-tumblr-post-image">
                        <label for="" class="col-sm-3 control-label"> <?php echo $sap_common->lang('tumb_post_img'); ?></label>
                        <div class="col-sm-6 sap-tumblr-img-wrap <?php echo (!empty($sap_tumblr_options['tumblr_image'])) ? 'tb-hide-uploader' : '';?>">
                            <?php
                            if (!empty($sap_tumblr_options['tumblr_image'])) {
                                ?>
                                <div class="tumblr-img-preview sap-img-preview">
                                    <img src="<?php echo SAP_IMG_URL . $sap_tumblr_options['tumblr_image']; ?>">
                                    <div class="cross-arrow">
                                        <a href="javascript:void(0)" data-upload_img=".sap-tumblr-img-wrap .file-input" data-preview=".tumblr-img-preview" title="Remove Tweet Image" class="sap-setting-remove-img remove-tx-init"><i class="fa fa-close"></i></a>
                                    </div> 
                                </div>
<?php } ?>
                            <input id="sap_tumblr_img" name="tumblr_image" type="file" class="file file-loading <?php echo!empty($sap_tumblr_options['tumblr_image']) ? 'sap-hide' : ''; ?>" data-show-upload="false" data-max-file-size="<?php echo MINGLE_MAX_FILE_UPLOAD_SIZE; ?>" data-show-caption="true" data-allowed-file-extensions='["png", "jpg","jpeg", "gif"]' tabindex="15">
                            <input type="hidden" class="uploaded_img" name="sap_tumblr_options[tumblr_image]" value="<?php echo!empty($sap_tumblr_options['tumblr_image']) ? $sap_tumblr_options['tumblr_image'] : ''; ?>" >
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="" class="col-sm-3 control-label"><?php echo $sap_common->lang('url_shortener'); ?></label>
                        <div class="col-sm-6">
                            <select class="sap_select sap-url-shortener-select" name="sap_tumblr_options[tu_type_shortner_opt]">
                                <?php
                                $selected_url_type = !empty($sap_tumblr_options['tu_type_shortner_opt']) ? $sap_tumblr_options['tu_type_shortner_opt'] : '';
                                foreach ($shortner_options as $key => $value) {
                                    $selected = "";
                                    if (!empty($selected_url_type) && $selected_url_type == $key) {
                                        $selected = ' selected="selected"';
                                    }
                                    ?>

                                    <?php if ( $key != 'tinyurl' ) { ?>
                                        <option value="<?php echo $key; ?>"<?php echo $selected; ?>><?php echo $value; ?></option>
                                    <?php } ?>
<?php } ?>
                            </select>
                        </div> 
                    </div>
                    <div class="form-group">
                        <label for="" class="col-sm-3 control-label"><?php echo $sap_common->lang('bit_access_token'); ?></label>                      
                        <div class="col-sm-6">
                            <input type="text" class="form-control bitly-token" name="sap_tumblr_options[tu_bitly_access_token]" value="<?php echo!empty($sap_tumblr_options['tu_bitly_access_token']) ? $sap_tumblr_options['tu_bitly_access_token'] : ''; ?>" >     
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="" class="col-sm-3 control-label"><?php echo $sap_common->lang('shorte_api_token'); ?></label>                      
                        <div class="col-sm-6">
                            <input type="text" class="form-control shorte-token" name="sap_tumblr_options[tu_shortest_api_token]" value="<?php echo!empty($sap_tumblr_options['tu_shortest_api_token']) ? $sap_tumblr_options['tu_shortest_api_token'] : ''; ?>" >     
                        </div>
                    </div>                         
                    <div class="box-footer">
                        <div class="">
                            <button type="submit" name="sap_tumblr_submit" class="btn btn-primary sap-tumblr-submit"><i class="fa fa-inbox"></i> <?php echo $sap_common->lang('save'); ?></button>
                        </div>
                    </div>

                </div>
            </div>


        </div>
    </form>
</div>