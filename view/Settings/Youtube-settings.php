<?php

/* Check the absolute path to the Social Auto Poster directory. */
if ( !defined( 'SAP_APP_PATH' ) ) {
    // If SAP_APP_PATH constant is not defined, perform some action, show an error, or exit the script
    // Or exit the script if required
    exit();
}

?>
<!-- End Tab 2 /.tab-pane -->
<div class="tab-pane <?php echo ( $active_tab == "youtube") ? "active" : '' ?>" id="youtube">
	<form id="youtube-settings" class="form-horizontal" method="POST" action="<?php echo SAP_SITE_URL . '/settings/save/'; ?>" enctype="multipart/form-data"> 
		<?php
		global $sap_common,$settings;
		//Get SAP options which stored
		$sap_youtube_options 		  = $this->get_user_setting('sap_youtube_options');
		$sap_youtube_accounts_details = $this->get_user_setting('sap_youtube_accounts_details');
		$sap_youtube_sess_data = $this->get_user_setting('sap_yt_sess_data');

		//Url shortner options
		$shortner_options = $common->sap_get_all_url_shortners();

		// $yt_profile_data = $youtube->sap_yt_get_profiles_data();
        
        // if Linkedin user id is not empty reset session data
        if (isset($_GET['yt_reset_user']) && $_GET['yt_reset_user'] == '1') {
            $youtube->sap_yt_reset_session();
        }

        $youtube_auth_options = !empty($sap_youtube_options['youtube_auth_options']) ? $sap_youtube_options['youtube_auth_options'] : '';
		?>
		<div class="box box-primary border-b">
			<div class="box-header sap-settings-box-header"><?php echo $sap_common->lang('youtube_general_settings'); ?> </div>
			<div class="box-body">
				<div class="sap-box-inner">
					<div class="form-group mb-0">
						<label for="" class="col-sm-3 control-label"><?php echo $sap_common->lang('en_autopost_youtube'); ?></label>
						<div class="tg-list-item col-sm-5">
							<input class="tgl tgl-ios" name="sap_youtube_options[enable_youtube]" id="enable_youtube" <?php echo!empty($sap_youtube_options['enable_youtube']) ? 'checked="checked"' : ''; ?> type="checkbox" value="1">
							<label class="tgl-btn float-right-cs-init" for="enable_youtube"></label>
						</div>
						<div class="col-sm-12 pt-40">	
							<button type="submit" name="sap_youtube_submit" class="btn btn-primary sap-youtube-submit"><i class="fa fa-inbox"></i> <?php echo $sap_common->lang('save'); ?></button>
						</div>
					</div>
				</div>
			</div>
			<!-- <div class="box-footer">
				<div class="pull-right">
					<button type="submit" name="sap_youtube_submit" class="btn btn-primary sap-youtube-submit"><i class="fa fa-inbox"></i> <?php echo $sap_common->lang('save'); ?></button>
				</div>
			</div> -->
		</div>

		<div class="box box-primary border-b">
			<div class="box-header sap-settings-box-header"><?php echo $sap_common->lang('youtube_api_settings'); ?> </div>
			<div class="box-body">
				<div class="sap-box-inner sap-api-youtube-settings">
					<div class="form-group">
						<label for="app-setting" class="col-sm-3 control-label"><?php echo $sap_common->lang('youtube_application'); ?></label>
						<div class="col-sm-12  documentation-text ">
							<?php echo sprintf($sap_common->lang('youtube_application_help_text'),'<span>','<a href="https://docs.wpwebelite.com/social-network-integration/youtube/" target="_blank">','</a>','</span>'); ?>
						</div> 
					</div>
					
					<?php
					$sap_youtube_keys = empty($sap_youtube_options['youtube_keys']) ? array(0 => array('consumer_key' => '', 'consumer_secret' => '', 'oauth_token' => '', 'oauth_secret' => '')) : $sap_youtube_options['youtube_keys'];
					// print_r($sap_youtube_keys);exit;
					if (!empty($sap_youtube_keys)) {
						$i = 0;
						foreach ($sap_youtube_keys as $key => $value) { ?>
							
                            <div class="form-group sap-youtube-account-details" data-row-id="<?php echo $key; ?>">
                                <div class="col-sm-3">
                                    <label class="col-sm-12"><?php echo $sap_common->lang('api_key'); ?></label>
                                    <input class="form-control sap-youtube-consumer-key" name="sap_youtube_options[youtube_keys][<?php echo $key; ?>][consumer_key]" value="<?php echo $value['consumer_key']; ?>" placeholder="<?php echo $sap_common->lang('api_key_youtube_text'); ?>" type="text">
                                </div>
                                <div class="col-sm-3">
                                    <label class="col-sm-12"><?php echo $sap_common->lang('api_secret'); ?></label>
                                    <input class="form-control sap-youtube-consumer-secret" name="sap_youtube_options[youtube_keys][<?php echo $key; ?>][consumer_secret]" value="<?php echo $value['consumer_secret']; ?>" placeholder="<?php echo $sap_common->lang('api_secret_youtube_text'); ?>" type="text">
                                </div>
                                <?php
                                if (!empty($value['consumer_key'])) {
                                    $valid_auto_redirect_url = SAP_SITE_URL.'/settings/' . '?sap=youtube&yt_app_id=' . $value['consumer_key'];
                                    ?>
                                    <div class="col-sm-3">
                                        <label class="col-sm-12"><?php echo $sap_common->lang('youtube_oauth_uri'); ?></label>
                                        <input class="form-control sap-oauth-url youtube-oauth-token" id="youtube-oauth-token-<?php print $value['consumer_secret']; ?>" type="text" value="<?php echo $valid_auto_redirect_url; ?>" size="30" readonly/>
                                        <button type="button" data-inputID="#youtube-oauth-token-" data-appid="<?php print $value['consumer_secret']; ?>" class="btn btn-primary copy-clipboard"><?php echo $sap_common->lang('copy'); ?></button>
                                    </div>
                                <?php } ?>
                                <div class="col-sm-3">
                                    <label class="col-sm-12"><?php echo $sap_common->lang('allowing_permissinons'); ?></label>
                                    <div class="sap-grant-reset-data">
                                        <?php
										$parts = explode('.', $value['consumer_key']);
										$yt_user_id = $parts[0];
										// if ( ( !empty($value['consumer_key']) && !empty($value['consumer_secret']) ) ) {
										if (!empty($value['consumer_key']) && !empty($value['consumer_secret']) && !empty($sap_youtube_sess_data[$yt_user_id])) {
                                            echo '<p  class="sap-grant-msg">'.$sap_common->lang('allowing_permissinons_help_text').'</p>';
                                            ?>
                                            <a href="<?php echo SAP_SITE_URL.'/settings/?yt_reset_user=1&yt_app_id='. $value['consumer_key']; ?>"><?php echo $sap_common->lang('reset_user_session'); ?></a>
                                            <?php
                                        } elseif (!empty($value['consumer_key']) && !empty($value['consumer_secret'])) {
                                            echo '<p><a href="' . $youtube->sap_get_yt_login_url($value['consumer_key']) . '">'.$sap_common->lang('grant_permission').'</a></p>';
                                        }
                                        ?>
                                    </div>
                                </div>
                                <div class="col-md-12 remove-icon-youtube">
                                    <div class="pull-right <?php echo ( $i == 0 ) ? 'sap-youtube-main' : ''; ?>">
                                        <a href="javascript:void(0)" class="sap-youtube-remove remove-tx-init"><i class="fa fa-close"></i></a>
                                    </div>
                                </div>
                            </div>
                            <?php
                            $i++;
                        }
					}  ?>

					<div class="">
						<div class="pull-right add-more">
							<button type="button" class="btn btn-primary sap-add-more-youtube-account"><i class="fa fa-plus"></i> <?php echo $sap_common->lang('add_more'); ?></button>
						</div>
					</div>
				</div>
			</div>
			<div class="box-footer">
				<div class="">
					<button type="submit" name="sap_youtube_submit" class="btn btn-primary sap-youtube-submit"><i class="fa fa-inbox"></i> <?php echo $sap_common->lang('save'); ?></button>
				</div>
			</div>

		</div>

		<div class="box box-primary ">
			<div class="box-header sap-settings-box-header"><?php echo $sap_common->lang('autopost_to_youtube'); ?></div>
			<div class="box-body">

				<div class="sap-box-inner sap-api-youtube-autopost">
					<div class="form-group">
						<label for="tw-post-users" class="col-sm-3 control-label"><?php echo $sap_common->lang('autopost_to_youtube_users'); ?></label>
						<div class="tg-list-item col-sm-6">
							<select class="form-control sap_select" multiple="multiple" name="sap_youtube_options[yt_type_post_user][]">
							<?php
							$yt_type_post_user = !empty($sap_youtube_options['yt_type_post_user']) ? ($sap_youtube_options['yt_type_post_user']) : array();

							if (!empty($sap_youtube_options['youtube_keys'])) {
								foreach ($sap_youtube_options['youtube_keys'] as $youtube_keys) {
									foreach ($youtube_keys as $profile_id => $profile_name) {
										$parts = explode('.', $youtube_keys['consumer_key']);
										$yt_user_id = $parts[0];
										if($profile_id == 'consumer_key' && !empty($sap_youtube_sess_data[$yt_user_id])){
											?>                                       
											<option value="<?php echo $profile_name; ?>" <?php echo in_array($profile_name, $yt_type_post_user) ? 'selected=selected' : ''; ?>><?php echo $profile_name; ?></option><?php
										}
									}
								}
							}
							?>
							</select>
						</div>
					</div>
					
					<div class="form-group">
						<label for="" class="col-sm-3 control-label"> <?php echo $sap_common->lang('youtube_post_video'); ?></label>
							<div class="col-sm-6 sap-yt-img-wrap <?php echo (!empty($sap_youtube_options['sap_yt_video'])) ? 'tw-hide-uploader' : '';?>">
							<?php 
							if( !empty( $sap_youtube_options['sap_yt_video'] ) ) { 
							?>
								<div class="yt-video-preview sap-img-preview">									
									<?php //echo '<video width="100%" height="100%" controls><source src="'. SAP_IMG_URL.$sap_youtube_options['sap_yt_video'] .'" type="video/mp4"></video>'; ?>
									<div class="sap-quick-post-privew-video">
										<video width="auto" height="100%" controls>
											<source src="<?php echo SAP_IMG_URL.$sap_youtube_options['sap_yt_video']; ?>" type="video/mp4">
										</video>
									</div>	
									<div class="cross-arrow">
										<a href="javascript:void(0)" data-upload_img=".sap-yt-img-wrap .file-input" data-preview=".yt-video-preview" title="Remove Youtube Video" class="sap-setting-remove-img remove-tx-init"><i class="fa fa-close"></i></a>
									</div> 
								</div>
							<?php 
							} ?>
								<?php 
								$preview_name = !empty($sap_youtube_options['sap_yt_video']) ? $sap_youtube_options['sap_yt_video'] : '';
								$preview_video = !empty($sap_youtube_options['sap_yt_video']) ? SAP_SITE_URL.'/uploads/'. $preview_name : '';
								?>
								<input id="sap_yt_video" tabindex="3" name="sap_yt_video" value="<?php echo $preview_video; ?>" type="file" class="file file-loading" data-show-upload="false" data-show-caption="true" data-max-file-size="<?php echo MINGLE_MAX_FILE_UPLOAD_SIZE; ?>" />
								<input type="hidden" id="uploaded_video" name="sap_youtube_options[sap_yt_video]" value="<?php echo !empty($sap_youtube_options['sap_yt_video']) ? $sap_youtube_options['sap_yt_video'] : ''; ?>" >
							</div>
						</div>
				  </div>
				  <div class="form-group">
					  <label for="" class="col-sm-3 control-label"><?php echo $sap_common->lang('url_shortener'); ?></label>  
					  <div class="col-sm-6">
							 <select class="sap_select sap-url-shortener-select" name="sap_youtube_options[yt_type_shortner_opt]">
										<?php 
											$selected_url_type = !empty($sap_youtube_options['yt_type_shortner_opt']) ? $sap_youtube_options['yt_type_shortner_opt'] : '';  
										   foreach($shortner_options as $key => $value) { 
											$selected = "";
											if (!empty($selected_url_type) && $selected_url_type == $key) {
												$selected = ' selected="selected"';
											}
										?>
											<option value="<?php echo $key;  ?>"<?php echo $selected; ?>><?php echo $value;  ?></option>
										<?php } ?>
							</select>
					  </div>   
				  </div>
				  <div class="form-group">
					  <label for="" class="col-sm-3 control-label"><?php echo $sap_common->lang('bit_access_token'); ?></label>                      
					  <div class="col-sm-6">
						  <input type="text" class="form-control bitly-token" name="sap_youtube_options[yt_bitly_access_token]" value="<?php echo!empty($sap_youtube_options['yt_bitly_access_token']) ? $sap_youtube_options['yt_bitly_access_token'] : ''; ?>" >     
					  </div>
					</div>
					<div class="form-group">
					  <label for="" class="col-sm-3 control-label"><?php echo $sap_common->lang('shorte_api_token'); ?></label>                      
					  <div class="col-sm-6">
						  <input type="text" class="form-control shorte-token" name="sap_youtube_options[yt_shortest_api_token]" value="<?php echo!empty($sap_youtube_options['yt_shortest_api_token']) ? $sap_youtube_options['yt_shortest_api_token'] : ''; ?>" >     
					  </div>
					</div>
			</div>
			<div class="box-footer">
				<div class="">
					<button type="submit" name="sap_youtube_submit" class="btn btn-primary sap-youtube-submit"><i class="fa fa-inbox"></i> <?php echo $sap_common->lang('save'); ?></button>
				</div>
			</div>
		</div>
	</form>
</div>