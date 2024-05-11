<?php

/* Check the absolute path to the Social Auto Poster directory. */
if ( !defined( 'SAP_APP_PATH' ) ) {
    // If SAP_APP_PATH constant is not defined, perform some action, show an error, or exit the script
    // Or exit the script if required
    exit();
}

?>
<div class="tab-pane <?php echo ( $active_tab == "blogger") ? "active" : '' ?>" id="blogger">
	<form id="blogger-settings" class="form-horizontal" method="POST" action="<?php echo SAP_SITE_URL . '/settings/save/'; ?>" enctype="multipart/form-data"> 

        <?php 
        global $sap_common;
        // if Blogger app id is not empty reset session data
        if ( isset( $_GET['blogger_reset_user'] ) && $_GET['blogger_reset_user'] == '1' && !empty( $_GET['sap_blogger_userid'] ) ) {
            $blogger->sap_blogger_reset_session();
        }
        
        $sap_blogger_options = $this->get_user_setting('sap_blogger_options');
        $sap_blogger_sess_data = $this->get_user_setting('sap_blogger_sess_data');

        //getting blogger App Method account
        $blogger_app_accounts = $blogger->sap_get_blogger_accounts();

        ?>

        <div class="box box-primary border-b">
            <div class="box-header sap-settings-box-header"><?php echo $sap_common->lang('blogger_general_title'); ?></div>
            <div class="box-body">
                <div class="sap-box-inner">
                    <div class="form-group mb-0">
                        <label for="" class="col-sm-3 control-label"><?php echo $sap_common->lang('blogger_autoposting'); ?></label>
                        <div class="tg-list-item col-sm-5">
                            <input class="tgl tgl-ios" name="sap_blogger_options[enable_blogger]" id="enable_blogger" <?php echo!empty($sap_blogger_options['enable_blogger']) ? 'checked="checked"' : ''; ?> type="checkbox" value="1">
                            <label class="tgl-btn float-right-cs-init" for="enable_blogger"></label>
                        </div>
                        <div class="col-sm-12 pt-40">
                            <button type="submit" name="sap_blogger_submit" class="btn btn-primary sap-blogger-submit"><i class="fa fa-inbox"></i> <?php echo $sap_common->lang('save'); ?></button>
                        </div>
                    </div>
                </div>
            </div>
            <!-- <div class="box-footer">
                <div class="pull-right">
                    <button type="submit" name="sap_blogger_submit" class="btn btn-primary sap-blogger-submit"><i class="fa fa-inbox"></i> <?php echo $sap_common->lang('save'); ?></button>
                </div>
            </div> -->
        </div>

        <div class="box box-primary d-flex flex-wrap border-b " style=" padding-bottom: 30px !important;;">
            <div class="box-header sap-settings-box-header col-md-12"><?php echo $sap_common->lang('blogger_api_setting'); ?></div>

            <div id="facebook-app-method" class="col-md-12 sap-box-inner ">
                <?php
                if ( !empty( $blogger_app_accounts ) ) {
                    echo '<div class="fb-btn">';
                }
                
                if ( empty( $blogger_app_accounts ) ) {
                    echo '<a class="sap-grant-fb-android btn btn-primary sap-api-btn" href="'. $blogger->sap_auto_poster_get_blogger_login_url() .'"> '.$sap_common->lang("blogger_add_account").' </a>';
                }

                if ( !empty( $blogger_app_accounts ) ) {
                    echo '</div>';
                }
                if ( !empty( $blogger_app_accounts ) ) {
                    ?>

                    <div class="form-group form-head">
                        <label class="col-md-3 "><?php echo $sap_common->lang('user_id'); ?></label>
                        <label class="col-md-3 "><?php echo $sap_common->lang('account_name'); ?></label>
                        <label class="col-md-3 "><?php echo $sap_common->lang('action'); ?></label>
                    </div>  
                    <?php
                    $i = 0;
                    foreach ( $blogger_app_accounts as $blogger_app_key => $blogger_app_value ) {
                        if( !empty( $blogger_app_key ) ) {

                            $blogger_user_data = $blogger_app_value;
                            $app_reset_url = '?blogger_reset_user=1&sap_blogger_userid=' . $blogger_app_key;
                            ?>
                            <div class="form-group form-deta d-flex flex-wrap">
                                <div class="col-md-3 "><?php print $blogger_app_key; ?></div>
                                <div class="col-md-3 "><?php print $blogger_user_data; ?></div>
                                <div class="col-md-3 delete-account">
                                    <a href="<?php print $app_reset_url; ?>"><?php echo $sap_common->lang('delete_account'); ?></a>
                                </div>
                            </div>
                            <?php
                        }
                    }
                }
                ?>
            </div>
        </div>

        <div class="box box-primary">
            <div class="box-header sap-settings-box-header"><?php echo $sap_common->lang('autopost_to_blogger'); ?></div>
            <div class="box-body">
                <div class="sap-box-inner sap-api-blogger-autopost">
                    <?php $sap_blogger_urls = empty( $sap_blogger_options['blogger_url'] ) ? array( 0 => '' ) : $sap_blogger_options['blogger_url']; ?>

                    <?php if ( !empty( $sap_blogger_urls ) ) { ?>
                        <?php foreach ( $sap_blogger_urls as $key => $value ) { ?>
                            <div class="form-group sap-blogger-url-details" data-row-id="<?php echo $key; ?>">
                                <label for="" class="col-sm-3 control-label">
                                    <?php if ( $key == 0 ) { ?>
                                        <?php echo $sap_common->lang('blogger_url'); ?><span class="astric">*</span>
                                    <?php } ?>
                                </label>
                                <div class="col-sm-6">
                                    <input type="url" id="sap-blogger-url" class="form-control sap-blogger-url" name="sap_blogger_options[blogger_url][]" value="<?php echo !empty( $value ) ? $value : ''; ?>" required>     
                                </div>
                                <div class="col-sm-2 remove-icon-blogger">
                                    <div class="<?php echo ( $key == 0 ) ? 'sap-blogger-main' : ''; ?>">
                                        <a href="javascript:void(0)" class="sap-blogger-remove remove-tx-init"><i class="fa fa-close"></i></a>
                                    </div>
                                </div>
                            </div>
                        <?php } ?>
                    <?php } ?>
                    <div class="form-group">
                        <div class="col-md-3"></div>
                        <div class=" add-more col-md-6">
                            <button type="button" class="btn btn-primary sap-add-more-blogger-account"><i class="fa fa-plus"></i> <?php echo $sap_common->lang('add_more'); ?></button>
                        </div>
                    </div>
                   
                    <div class="form-group">
                        <label for="tw-post-users" class="col-sm-3 control-label"><?php echo $sap_common->lang('autopost_to_blogger_users'); ?><span class="astric">*</span></label>
                        <div class="tg-list-item col-sm-6">
                            <select class="form-control sap_select" multiple="multiple" name="sap_blogger_options[posts_users][]">
                                <?php
                                $accounts_details = !empty( $sap_blogger_options['posts_users'] )? $sap_blogger_options['posts_users'] : array();

                                if ( !empty( $blogger_app_accounts ) ) {

                                    foreach ( $blogger_app_accounts as $uid => $uname ){
                                        echo '<option '.( in_array( $uid, $accounts_details )? 'selected="selected"' : '' ).' value="'.$uid.'">'.$uname.'</option>';
                                    }
                                }
                                ?>
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="" class="col-sm-3 control-label"> <?php echo $sap_common->lang('blogger_post_img'); ?></label>
                        <div class="col-sm-6 sap-blogger-img-wrap <?php echo ( !empty( $sap_blogger_options['blogger_image'] ) ) ? 'tw-hide-uploader' : '';?>">
                            <?php if( !empty( $sap_blogger_options['blogger_image'] ) ) { ?>
                                <div class="blogger-img-preview sap-img-preview">
                                    <img src="<?php echo SAP_IMG_URL.$sap_blogger_options['blogger_image']; ?>">
                                    <div class="cross-arrow">
                                        <a href="javascript:void(0)" data-upload_img=".sap-blogger-img-wrap .file-input" data-preview=".blogger-img-preview" title="<?php echo $sap_common->lang('blogger_post_img_remove'); ?>" class="sap-setting-remove-img remove-tx-init"><i class="fa fa-close"></i></a>
                                    </div> 
                                </div>
                            <?php } ?>

                            <input id="sap_blogger_img" name="blogger_image" type="file" class="file file-loading <?php echo !empty( $sap_blogger_options['blogger_image'] )? 'sap-hide' : ''; ?>" data-show-upload="false" data-show-caption="true" data-allowed-file-extensions='["png", "jpg","jpeg", "gif"]' tabindex="15">

                            <input type="hidden" class="uploaded_img" name="sap_blogger_options[blogger_image]" value="<?php echo !empty( $sap_blogger_options['blogger_image'] )? $sap_blogger_options['blogger_image'] :''; ?>" >
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="box-footer">
                <div class="">
                    <button type="submit" name="sap_blogger_submit" class="btn btn-primary sap-blogger-submit"><i class="fa fa-inbox"></i> <?php echo $sap_common->lang('save'); ?></button>
                </div>
            </div>

        </div>       
    </form>
</div>