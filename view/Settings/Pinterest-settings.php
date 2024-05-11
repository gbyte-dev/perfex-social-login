<?php

/* Check the absolute path to the Social Auto Poster directory. */
if ( !defined( 'SAP_APP_PATH' ) ) {
    // If SAP_APP_PATH constant is not defined, perform some action, show an error, or exit the script
    // Or exit the script if required
    exit();
}

?>
<div class="tab-pane <?php echo ( $active_tab == "pinterest") ? "active" : "" ?>" id="pinterest">
    <form id="pinterest-settings" class="form-horizontal" method="POST" action="<?php echo SAP_SITE_URL . '/settings/save/'; ?>" enctype="multipart/form-data"> 
        <?php
        global $sap_common;
        // if Pinterest app id is not empty reset session data

      
        

        if (isset($_GET['pin_reset_user']) && $_GET['pin_reset_user'] == '1' && !empty($_GET['sap_pinterest_username'])) {
           
           
            $pinterest->sap_pin_reset_session();
        }

        if (isset($_GET['pin_reset_user']) && $_GET['pin_reset_user'] == '1' && !empty($_GET['sap_pinterest_app_id'])) {
            $pinterest->sap_pin_reset_session_from_apps();
        }

        //Get SAP options which stored
        $sap_pinterest_options = $this->get_user_setting('sap_pinterest_options');

        //Get Pinterest Authentication options
        $pinterest_auth_options = !empty($sap_pinterest_options['pin_auth_options']) ? $sap_pinterest_options['pin_auth_options'] : 'app';

        $pinterest_proxy_options = !empty($sap_pinterest_options['enable_proxy']) ? $sap_pinterest_options['enable_proxy'] : '';

        $proxy_style =  "display:none";
        $cookie_style = "";
        $app_style = ""; 
        if ($pinterest_auth_options == 'app') {
            $cookie_style = "display:none";
        } else if ($pinterest_auth_options == 'cookie') {
            $app_style = "display:none";
        }

        if($pinterest_proxy_options == 1) {
            $proxy_style = "display:block";
        }

        // Getting pinterest app grant data
        $sap_pin_sess_data = $this->get_user_setting('sap_pin_sess_data');
        $pinterest_accounts = $pinterest->sap_get_pin_apps_with_boards();
        $shortner_options = $common->sap_get_all_url_shortners();
        ?>
        <div class="box box-primary border-b">
            <div class="box-header sap-settings-box-header"><?php echo $sap_common->lang('pit_general_settings'); ?> </div>
            <div class="box-body">
                <div class="sap-box-inner">
                    <div class="form-group mb-0">
                        <label for="" class="col-sm-3 control-label"><?php echo $sap_common->lang('en_autopost_pit'); ?></label>
                        <div class="tg-list-item col-sm-5">
                            <input class="tgl tgl-ios" name="sap_pinterest_options[enable_pinterest]" id="enable_pinterest" <?php echo!empty($sap_pinterest_options['enable_pinterest']) ? 'checked="checked"' : ''; ?> type="checkbox" value="1">
                            <label class="tgl-btn float-right-cs-init" for="enable_pinterest"></label>
                        </div>
                        <div class="col-sm-12 pt-40">
                            <button type="submit" name="sap_pinterest_submit" class="btn btn-primary sap-pinterest-submit"><i class="fa fa-inbox"></i> <?php echo $sap_common->lang('save'); ?></button>
                        </div>
                    </div>
                </div>
            </div>
            <!-- <div class="box-footer">
                <div class="pull-right">
                    <button type="submit" name="sap_pinterest_submit" class="btn btn-primary sap-pinterest-submit"><i class="fa fa-inbox"></i> <?php echo $sap_common->lang('save'); ?></button>
                </div>
            </div> -->
        </div>

        <div class="box box-primary border-b">
            <div class="box-header sap-settings-box-header"><?php echo $sap_common->lang('pit_proxy_title'); ?></div>
            <div class="box-body">
                <div class="sap-box-inner">
                    <div class="form-group">
                        <label for="" class="col-sm-3 control-label"><?php echo $sap_common->lang('pit_proxy_enable'); ?></label>
                        <div class="tg-list-item col-sm-9">
                            <input class="tgl tgl-ios" name="sap_pinterest_options[enable_proxy]" id="enable_pinterest_proxy" <?php echo!empty($sap_pinterest_options['enable_proxy']) ? 'checked="checked"' : ''; ?> type="checkbox" value="1">
                            <label class="tgl-btn float-right-cs-init" for="enable_pinterest_proxy"></label>
                        </div>
                    </div>
                    <div id="pinterest-proxy" style="<?php print $proxy_style; ?>">
                        <div class="form-group">
                            <label for="pit_proxy_url" class="col-sm-3 control-label padding-top-0"><?php echo $sap_common->lang('pit_proxy_url'); ?></label>
                             <div class="col-sm-6">
                                <input type="url" class="form-control bitly-token" name="sap_pinterest_options[proxy_url]" value="<?php echo!empty($sap_pinterest_options['proxy_url']) ? $sap_pinterest_options['proxy_url'] : ''; ?>" >
                            </div>  
                        </div>
                        <div class="form-group">
                            <label for="pit_proxy_username" class="col-sm-3 control-label padding-top-0"><?php echo $sap_common->lang('pit_proxy_username'); ?></label>
                             <div class="col-sm-6">
                                <input type="text" class="form-control bitly-token" name="sap_pinterest_options[proxy_username]" value="<?php echo!empty($sap_pinterest_options['proxy_username']) ? $sap_pinterest_options['proxy_username'] : ''; ?>" >
                            </div>  
                        </div>
                        <div class="form-group">
                            <label for="pit_proxy_password" class="col-sm-3 control-label padding-top-0"><?php echo $sap_common->lang('pit_proxy_password'); ?></label>
                             <div class="col-sm-6">
                                <input type="text" class="form-control bitly-token" name="sap_pinterest_options[proxy_password]" value="<?php echo!empty($sap_pinterest_options['proxy_password']) ? $sap_pinterest_options['proxy_password'] : ''; ?>" >
                            </div>  
                        </div>
                    </div>
                </div>
            </div>
            <div class="box-footer">
                <div class="">
                    <button type="submit" name="sap_pinterest_submit" class="btn btn-primary sap-pinterest-submit"><i class="fa fa-inbox"></i> <?php echo $sap_common->lang('save'); ?></button>
                </div>
            </div>
        </div>

        <div class="box box-primary border-b">
            <div class="box-header sap-settings-box-header"><?php echo $sap_common->lang('pit_api_settings'); ?> </div>
            <div class="box-body">
                <div class="sap-box-inner sap-api-pinterest-settings">
                    <div class="form-group">
                            <label for="app-setting" class="col-sm-3 control-label padding-top-0"><?php echo $sap_common->lang('pit_authentication'); ?></label>
                            <div class="col-sm-3">
                                <input id="app_api" type="radio" name="sap_pinterest_options[pin_auth_options]" <?php echo($pinterest_auth_options == 'app') ? 'checked="checked"' : ''; ?> value="app">
                                <label class="auth-option" for="app_api"><?php echo $sap_common->lang('pit_app_method'); ?></label>
                            </div>
                            <div class="col-sm-3">
                                <input id="cookie_api" type="radio" name="sap_pinterest_options[pin_auth_options]" <?php echo($pinterest_auth_options == 'cookie') ? 'checked="checked"' : ''; ?> value="cookie">
                                <label class="auth-option" for="cookie_api"><?php echo $sap_common->lang('pit_cookie_method'); ?></label>
                            </div>
                    </div>
                    <div style="<?php print $app_style; ?>" id="app-method-wrap">
                        <div class="form-group">
                            <label for="app-setting" class="col-sm-3 control-label"><?php echo $sap_common->lang('pit_application'); ?></label>
                            <div class="col-sm-9 documentation-text">
                                <?php echo sprintf($sap_common->lang('pit_app_method_help_text'),'<span>','<a href="https://docs.wpwebelite.com/social-network-integration/pinterest/" target="_blank">','</a>','</span>'); ?>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="app-permission-setting" class="col-sm-3 control-label"><?php echo $sap_common->lang('allowing_permissinons'); ?></label>
                            <div class="col-sm-9">
                                <span><?php echo $sap_common->lang('pit_allowing_permissinons_hlp_text'); ?></span>
                            </div>
                        </div>
                        <div class="alert alert-info linkedin-multi-post-note">
                            <i>
                                <?php echo sprintf($sap_common->lang('pit_notice_help_text'),'<strong>','</strong>'); ?>
                            </i><br/>
                            <?php echo SAP_SITE_URL.'/settings/'; ?>
                        </div>
                        <div class="form-group display_desktop pinterest-app-section">
                            <label class="col-sm-3"><?php echo $sap_common->lang('pit_app_id_key'); ?></label>
                            <label class="col-sm-3"><?php echo $sap_common->lang('pit_app_secret'); ?></label>
                            <label class="col-sm-3"><?php echo $sap_common->lang('allowing_permissinons'); ?></label>
                        </div>

                        <?php
                             $sap_pin_keys = empty($sap_pinterest_options['pinterest_keys']) ? array(0 => array('app_id' => '', 'app_secret' => '')) : $sap_pinterest_options['pinterest_keys'];
                             if (!empty($sap_pin_keys)) {
                                $i = 0;
                                foreach ($sap_pin_keys as $key => $value) {
                             ?>
                             <div class="form-group display_mobile sap-pinterest-account-details" data-row-id="<?php echo $key; ?>">
                                 <div class="col-md-12 remove-icon-pinterest">
                                    <div class="pull-right <?php echo ( $i == 0 ) ? 'sap-pinterest-main' : ''; ?>">
                                        <a href="javascript:void(0)" class="sap-pinterest-remove remove-tx-init"><i class="fa fa-close"></i></a>
                                    </div>
                                </div>    
                                <div class="col-sm-3">
                                    <label class="heading-label"><?php echo $sap_common->lang('pit_app_id_key'); ?></label>
                                    <input class="form-control sap-pinterest-app-id" name="sap_pinterest_options[pinterest_keys][<?php echo $key; ?>][app_id]" value="<?php echo $value['app_id']; ?>" placeholder="<?php echo $sap_common->lang('pin_app_id_key_plh_text'); ?>" type="text">
                                </div>
                                <div class="col-sm-3">
                                    <label class="heading-label"><?php echo $sap_common->lang('pit_app_secret'); ?></label>
                                    <input class="form-control sap-pinterest-app-secret" name="sap_pinterest_options[pinterest_keys][<?php echo $key; ?>][app_secret]" value="<?php echo $value['app_secret']; ?>" placeholder="<?php echo $sap_common->lang('pin_app_secret_plh_text'); ?>" type="text">
                                </div>
                                <?php
                                if (!empty($value['app_id']) && empty($sap_pin_sess_data[ $value['app_id'] ])) {
                                    ?>
                                    <div class="col-sm-3 pinterest-grant-permission">  
                                       <a href='<?php echo $pinterest->sap_pinterest_login_url( $value['app_id'] ); ?>'>Grant Extended Permission</a>                                         
                                    </div>
                                <?php } if(!empty($value['app_id']) && !empty($sap_pin_sess_data[ $value['app_id'] ])) { ?>    
                                    <div class="col-sm-3 pinterest-reset-permission">
                                        <a href='<?php echo SAP_SITE_URL.'/settings/?pin_reset_user=1&sap_pinterest_app_id='.$value['app_id']; ?>'>Reset User Session</a>
                                    </div>     
                                <?php } ?>    
                                  
                             </div>
                             <?php  
                                  $i++;
                                }
                            }                      
                        ?>
                        <div class="form-group">
                            <div class="pull-right add-more">
                                <button type="button" class="btn btn-primary sap-pinterest-more-account"><i class="fa fa-plus"></i> <?php echo $sap_common->lang('add_more'); ?></button>
                            </div>
                        </div>
                    </div>
                    <div style="<?php print $cookie_style; ?>" id="cookie-method-wrap">                        
                        <div class="form-group">
                            <label for="app-setting" class="col-sm-3 control-label"><?php echo $sap_common->lang('pit_cookie'); ?></label>
                            <div class="col-sm-9 documentation-text">
                                <?php echo sprintf($sap_common->lang('pit_cookie_help_text'),'<span>','<a href="https://docs.wpwebelite.com/mingle-saas/social-network-configuration#Pinterest_Settings" target="_blank">','</a>','</span>'); ?>
                            </div>
                        </div>
                        <div class="form-group control-pinterest-wrap">
                            <label for="app-setting" class="col-sm-3 col-lg-3 control-label"><?php echo $sap_common->lang('pit_session_id'); ?></label>
                            <div class="col-sm-6 col-lg-6">
                                <input id="sap-pinterest-cookie-data" class="form-control sap-pinterest-cookie" name="sap-pinterest-cookie" value=""  type="text">                  
                                <div class='pinterest-status-msg'>        
                                    <div id='pinterest-result'></div>     
                                </div>            
                            
                                <div class="col-sm-6 col-lg-12">
                                    <div class="row">
                                        <div class="add-pin-account-pint">                            
                                            <button type="button" class="btn btn-primary add-pin-account sap-api-btn"><?php echo $sap_common->lang('pit_add_account'); ?></button>
                                            <div class="update_loader_pinterest"><img src="<?php echo SAP_SITE_URL . '/assets/images/ajax-loader.gif'; ?>" alt="Update"></div>
                                        </div>
                                    </div>
                                </div>
                        </div>
                    </div> 
                    </div>   
                    <div id='facebook-app-method' class="pinterest-listing">
                        <?php 
                        if (!empty($sap_pin_sess_data)) { 
                            
                            ?>

                            <div class="form-group form-head">
                                <label class="col-md-3"><?php echo $sap_common->lang('user_id'); ?></label>
                                <label class="col-md-3"><?php echo $sap_common->lang('account_name'); ?></label>
                                <label class="col-md-3 delete-account"><?php echo $sap_common->lang('action'); ?></label>
                            </div>    

                             <?php
                            $i = 0;
                            foreach ($sap_pin_sess_data as $pin_uid => $pin_app_details) {

                                $pin_app_reset_url = '?pin_reset_user=1&sap_pinterest_username=' . $pin_app_details['username'];
                                $hide_app = $hide_cookie =  '';
                                if ( $pinterest_auth_options == 'app' ) {
                                    $hide_cookie = "display:none";
                                } 
                                if ( $pinterest_auth_options == 'cookie' ) {
                                    $hide_app = "display:none";
                                } 


                                if( $pin_app_details['auth_type'] == 'cookie' ) {  
                                ?>
                                <div style="<?php print $hide_cookie; ?>" class="cookie-data"> 
                                    <div  class="form-group form-deta">  
                                        <div class="col-md-3"><?php print $pin_app_details['id'] ?></div>
                                        <div class="col-md-3"><?php print $pin_app_details['username']; ?></div>  
                                        <div class="col-md-3 delete-account">
                                            <a href="<?php print $pin_app_reset_url; ?>"><?php echo $sap_common->lang('delete_account'); ?></a>
                                        </div>  
                                    </div>
                                </div>    
                                <?php } if( $pin_app_details['auth_type'] == 'app' ) { ?>
                                    <div style='<?php print $hide_app; ?>' class="app-data"> 
                                    <div class="form-group form-deta">  
                                        <div class="col-md-3"><?php print $pin_app_details['id'] ?></div>
                                        <div class="col-md-3"><?php print $pin_app_details['username']; ?></div>  
                                        <div class="col-md-3 delete-account">
                                            <a href="<?php print $pin_app_reset_url; ?>"><?php echo $sap_common->lang('delete_account'); ?></a>
                                        </div>  
                                    </div>
                                </div>   

                                <?php }    
                            }
                        }
                        ?> 
                    </div>
                </div>             
            </div>
            <div class="box-footer">
                <div class="">
                    <button type="submit" name="sap_pinterest_submit" class="btn btn-primary sap-pinterest-submit"><i class="fa fa-inbox"></i> <?php echo $sap_common->lang('save'); ?></button>
                </div>
            </div>
        </div>

        <div class="box box-primary">
            <div class="box-header sap-settings-box-header"><?php echo $sap_common->lang('autopost_to_pit'); ?> </div>
            <div class="box-body">
                <div class="sap-box-inner sap-api-pinterest-settings">
                    <div class="form-group">
                        <label for="app-setting" class="col-sm-3 control-label"><?php echo $sap_common->lang('autopost_to_pit_users'); ?></label>
                        <div class="tg-list-item col-sm-6">

                            <select class="sap_select" multiple="multiple" name="sap_pinterest_options[pin_type_post_user][]">
                                <?php
                                $sap_selected_boards = !empty($sap_pinterest_options['pin_type_post_user']) ? ($sap_pinterest_options['pin_type_post_user']) : array();
                                if (!empty($pinterest_accounts)) {
                                    foreach ($pinterest_accounts as $board_id => $board_label) {
                                        ?>
                                        <option value="<?php echo $board_id ?>" <?php echo in_array($board_id, $sap_selected_boards) ? 'selected=selected' : ''; ?>><?php echo $board_label; ?></option> 
                                        <?php
                                    }
                                }
                                ?>
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="app-setting" class="col-sm-3 control-label"><?php echo $sap_common->lang('pit_post_img'); ?></label>
                        <div class="col-sm-6 sap-pin-img-wrap <?php echo (!empty($sap_pinterest_options['pin_image'])) ? 'pin-hide-uploader' : '';?>">
                            <?php
                            if (!empty($sap_pinterest_options['pin_image'])) {
                                ?>
                                <div class="pin-img-preview sap-img-preview">
                                    <img src="<?php echo SAP_IMG_URL . $sap_pinterest_options['pin_image']; ?>">
                                    <div class="cross-arrow">
                                        <a href="javascript:void(0)" data-upload_img=".sap-pin-img-wrap .file-input" data-preview=".pin-img-preview" title="Remove Pinterest Image" class="sap-setting-remove-img remove-tx-init"><i class="fa fa-close"></i></a>
                                    </div> 
                                </div>
                            <?php } ?>

                            <input id="sap_pin_img" name="pin_image" type="file" class="file file-loading <?php echo!empty($sap_pinterest_options['pin_image']) ? 'sap-hide' : ''; ?>" data-show-upload="false" data-show-caption="true" data-allowed-file-extensions='["png", "jpg","jpeg", "gif"]' tabindex="15">
                            <input type="hidden" class="uploaded_img" name="sap_pinterest_options[pin_image]" value="<?php echo!empty($sap_pinterest_options['pin_image']) ? $sap_pinterest_options['pin_image'] : ''; ?>" >

                            <div class="alert alert-info linkedin-multi-post-note">
                                <i><?php echo $sap_common->lang('pit_post_img_help_text'); ?></i>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="" class="col-sm-3 control-label"><?php echo $sap_common->lang('url_shortener'); ?></label>  
                        <div class="col-sm-6">
                            <select class="sap_select sap-url-shortener-select" name="sap_pinterest_options[pin_type_shortner_opt]">
                                <?php
                                $selected_url_type = !empty($sap_pinterest_options['pin_type_shortner_opt']) ? $sap_pinterest_options['pin_type_shortner_opt'] : '';
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
                            <input type="text" class="form-control bitly-token" name="sap_pinterest_options[pin_bitly_access_token]" value="<?php echo!empty($sap_pinterest_options['pin_bitly_access_token']) ? $sap_pinterest_options['pin_bitly_access_token'] : ''; ?>" >     
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="" class="col-sm-3 control-label"><?php echo $sap_common->lang('shorte_api_token'); ?></label>                      
                        <div class="col-sm-6">
                            <input type="text" class="form-control shorte-token" name="sap_pinterest_options[pin_shortest_api_token]" value="<?php echo!empty($sap_pinterest_options['pin_shortest_api_token']) ? $sap_pinterest_options['pin_shortest_api_token'] : ''; ?>" >     
                        </div>
                    </div> 

                </div>
            </div>
            <div class="box-footer">
                <div class="">
                    <button type="submit" name="sap_pinterest_submit" class="btn btn-primary sap-pinterest-submit"><i class="fa fa-inbox"></i> <?php echo $sap_common->lang('save'); ?></button>
                </div>
            </div>
        </div>
    </form>
</div>