<?php

/* Check the absolute path to the Social Auto Poster directory. */
if ( !defined( 'SAP_APP_PATH' ) ) {
    // If SAP_APP_PATH constant is not defined, perform some action, show an error, or exit the script
    // Or exit the script if required
    exit();
}

?>
<div class="tab-pane <?php echo ( $active_tab == "instagram") ? "active" : "" ?>" id="instagram">
    <form id="instagram-settings" class="form-horizontal" method="POST" action="<?php echo SAP_SITE_URL . '/settings/save/'; ?>" enctype="multipart/form-data"> 
        
        <?php 
        global $sap_common;
        // if FB app id is not empty reset session data
        if (isset($_GET['insta_reset_user']) && $_GET['insta_reset_user'] == '1' && !empty($_GET['sap_insta_userid'])) {
            $instagram->sap_fb_reset_session_for_insta();
        }

        //getting facebook App Method account
        $inta_fb_app_accounts = $this->sap_get_insta_fb_app_accounts();
        
        $sap_instagram_options  = $this->get_user_setting('sap_instagram_options');
          
        // Getting facebook app grant data
        $sap_fb_sess_data = $this->get_user_setting('sap_fb_sess_data_for_insta');

        ?>


        <div class="box box-primary border-b">
            <div class="box-header sap-settings-box-header"><?php echo $sap_common->lang('instagram_general_title'); ?></div>
            <div class="box-body">
                <div class="sap-box-inner">
                    <div class="form-group mb-0">
                        <label for="" class="col-sm-3 control-label"><?php echo $sap_common->lang('instagram_autoposting'); ?></label>
                        <div class="tg-list-item col-sm-5">
                            <input class="tgl tgl-ios" name="sap_instagram_options[enable_instagram]" id="enable_instagram" <?php echo!empty($sap_instagram_options['enable_instagram']) ? 'checked="checked"' : ''; ?> type="checkbox" value="1">
                            <label class="tgl-btn float-right-cs-init" for="enable_instagram"></label>
                        </div>
                        <div class="col-sm-12 pt-40">
                            <button type="submit" name="sap_instagram_submit" class="btn btn-primary sap-instagram-submit"><i class="fa fa-inbox"></i> <?php echo $sap_common->lang('save'); ?></button>
                        </div>
                    </div>
                </div>
            </div>
            <!-- <div class="box-footer">
                <div class="pull-right">
                    <button type="submit" name="sap_instagram_submit" class="btn btn-primary sap-instagram-submit"><i class="fa fa-inbox"></i> <?php echo $sap_common->lang('save'); ?></button>
                </div>
            </div> -->
        </div>


        <div class="box box-primary">
            <div class="box-header sap-settings-box-header"><?php echo $sap_common->lang('instagram_api_setting'); ?></div>

            <div id="facebook-app-method" class="sap-box-inner">
                <?php
                if (!empty($inta_fb_app_accounts)) {
                    echo '<div class="fb-btn" style="margin-bottom:20px">';
                }
                echo '<p><a class="sap-grant-fb-android btn btn-primary sap-api-btn" href="' . $instagram->sap_auto_poster_get_fb_app_method_login_url() . '"> '.$sap_common->lang("facebook_add_account").' </a></p>';
                if (!empty($inta_fb_app_accounts)) {
                    echo '</div>';
                }
                if ( !empty($inta_fb_app_accounts) ) {
                    ?>

                    <div class="form-group form-head">
                        <label class="col-md-3 "><?php echo $sap_common->lang('user_id'); ?></label>
                        <label class="col-md-3 "><?php echo $sap_common->lang('account_name'); ?></label>
                        <label class="col-md-3 "><?php echo $sap_common->lang('action'); ?></label>
                    </div>  
                    <?php
                    $i = 0;
                    foreach ($inta_fb_app_accounts as $facebook_app_key => $facebook_app_value) {
                        if (is_array($facebook_app_value)) {
                            $fb_user_data = $facebook_app_value;
                            $app_reset_url = '?insta_reset_user=1&sap_insta_userid=' . $facebook_app_key;
                            ?>
                            <div class="form-group form-deta">
                                <div class="col-md-3 "><?php print $facebook_app_key; ?></div>
                                <div class="col-md-3 "><?php print $fb_user_data['name']; ?></div>
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
        <div class="box box-primary" style="margin-top: 30px;">
			<div class="box-header sap-settings-box-header">
                <?php echo $sap_common->lang('autopost_to_instagram'); ?>
            </div>
			<div class="box-body">
				<div class="sap-box-inner sap-api-instagram-autopost">
					<div class="form-group">
						<label for="insta-post-users" class="col-sm-4 control-label"><?php echo $sap_common->lang('autopost_to_insta_users'); ?></label>
						<div class="tg-list-item col-sm-6">
                            <?php $fb_accounts = $instagram->sap_get_fb_instagram_accounts('all_app_users_with_name'); ?>
							<select class="form-control sap_select" multiple="multiple" name="sap_instagram_options[posts_users][]">
							    <?php
                                    
                                    if (!empty($fb_accounts) && is_array($fb_accounts)) {
                                        $fb_type_post_user = (!empty($sap_instagram_options['posts_users'])) ? $sap_instagram_options['posts_users'] : array();
                                        
                                        foreach ($fb_accounts as $aid => $aval) {

                                            if (is_array($aval)) {

                                                $fb_app_data = isset($sap_fb_sess_data[$aid]) ? $sap_fb_sess_data[$aid] : array();
                                                $fb_user_data = isset($fb_app_data['sap_insta_user_cache']) ? $fb_app_data['sap_insta_user_cache'] : array();
                                                $fb_opt_label = !empty($fb_user_data['name']) ? $fb_user_data['name'] . ' - ' : '';
                                                $fb_opt_label = $fb_opt_label . $aid;
                                                ?>
                                                <optgroup label="<?php echo $fb_opt_label; ?>">

                                                    <?php foreach ($aval as $aval_key => $aval_data) { ?>
                                                        <option <?php echo in_array($aval_key, $fb_type_post_user) ? 'selected="selected"' : ''; ?> value="<?php echo $aval_key; ?>" ><?php echo $aval_data; ?></option>
                                                    <?php } ?>

                                                </optgroup>

                                            <?php } else { ?>
                                                <option <?php echo in_array($aid, $fb_type_post_user) ? 'selected="selected"' : ''; ?> value="<?php echo $aid; ?>" ><?php echo $aval; ?></option>
                                                <?php
                                            }
                                        } // End of foreach
                                    } // End of main if
                                    ?>
							</select>
						</div>
					</div>
					<div class="form-group mb-0">
						<label for="" class="col-sm-4 control-label"> <?php echo $sap_common->lang('inst_post_img'); ?></label>
						<div class="col-sm-6 sap-insta-img-wrap <?php echo (!empty($sap_instagram_options['insta_image'])) ? 'tw-hide-uploader' : '';?>">
							<?php if( !empty( $sap_instagram_options['insta_image'] ) ) { ?>
								<div class="insta-img-preview sap-img-preview">
									<img src="<?php echo SAP_IMG_URL.$sap_instagram_options['insta_image']; ?>">
									<div class="cross-arrow">
										<a href="javascript:void(0)" data-upload_img=".sap-insta-img-wrap .file-input" data-preview=".insta-img-preview" title="Remove Insta Image" class="sap-setting-remove-img remove-tx-init"><i class="fa fa-close"></i></a>
									</div> 
								</div>
						    <?php } ?>
							<input id="sap_insta_img" name="insta_image" type="file" class="file file-loading <?php echo !empty( $sap_instagram_options['insta_image'] )? 'sap-hide' : ''; ?>" data-show-upload="false" data-show-caption="true" data-allowed-file-extensions='["png", "jpg","jpeg", "gif"]' tabindex="15">
							<input type="hidden" class="uploaded_img" name="sap_instagram_options[insta_image]" value="<?php echo !empty( $sap_instagram_options['insta_image'] )? $sap_instagram_options['insta_image'] : ''; ?>" >
						</div>
					</div>
			    </div>
            </div>
			<div class="box-footer">
				<div class="">
					<button type="submit" name="sap_instagram_submit" class="btn btn-primary sap-insta-submit"><i class="fa fa-inbox"></i> <?php echo $sap_common->lang('save'); ?></button>
				</div>
			</div>
	    </div>        
    </form>
</div>