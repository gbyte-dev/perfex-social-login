<?php 

/* Check the absolute path to the Social Auto Poster directory. */
if ( !defined( 'SAP_APP_PATH' ) ) {
    // If SAP_APP_PATH constant is not defined, perform some action, show an error, or exit the script
    // Or exit the script if required
    exit();
}

global $sap_common;
$SAP_Mingle_Update = new SAP_Mingle_Update();
$license_data = $SAP_Mingle_Update->get_license_data();
if( !$sap_common->sap_is_license_activated() ){
    $redirection_url = '/mingle-update/';
    header('Location: ' . SAP_SITE_URL . $redirection_url );
    die();
}

include SAP_APP_PATH . 'header.php';

include SAP_APP_PATH . 'sidebar.php';

// Get user's active networks
$networks = sap_get_users_networks();

//Get Facebook options
$sap_facebook_options = array();
if ( in_array('facebook', $networks) ) {
    $sap_facebook_options = $this->settings->get_user_setting('sap_facebook_options');
    $sap_facebook_options = !empty($sap_facebook_options) ? $sap_facebook_options : array();
}

//Get Linkdin options
$sap_linkedin_options = array();
if ( in_array('linkedin', $networks) ) {
    $sap_linkedin_options = $this->settings->get_user_setting('sap_linkedin_options');
    $sap_linkedin_options = !empty($sap_linkedin_options) ? $sap_linkedin_options : array();
}

//Get Twitter options
$sap_twitter_options = array();
if ( in_array('twitter', $networks) ) {
    $sap_twitter_options = $this->settings->get_user_setting('sap_twitter_options');
    $sap_twitter_options = !empty($sap_twitter_options) ? $sap_twitter_options : array();
}

//Get Tumblr options
$sap_tumblr_options = array();
if ( in_array('tumblr', $networks) ) {
    $sap_tumblr_options = $this->settings->get_user_setting('sap_tumblr_options');
    $sap_tumblr_options = !empty($sap_tumblr_options) ? $sap_tumblr_options : array();
}

//Get Pinterest options
$sap_pinterest_options = array();
if ( in_array('pinterest', $networks) ) {
    $sap_pinterest_options = $this->settings->get_user_setting('sap_pinterest_options');
    $sap_pinterest_options = !empty($sap_pinterest_options) ? $sap_pinterest_options : array();
}

//Get GMB options
$sap_gmb_options = array();
if ( in_array('gmb', $networks) ) {
    $sap_gmb_options = $this->settings->get_user_setting('sap_google_business_options');
    $sap_gmb_options = !empty($sap_gmb_options) ? $sap_gmb_options : array();
}

//Get Instagram options
$sap_insta_options = array();
if ( in_array('instagram', $networks) ) {
	$sap_insta_options = $this->settings->get_user_setting('sap_instagram_options');
	$sap_insta_options = !empty($sap_insta_options) ? $sap_insta_options : array();
}

// Reddit
$sap_reddit_options = array();
if ( in_array('reddit', $networks) ) {
    $sap_reddit_options = $this->settings->get_user_setting('sap_reddit_options');
    $sap_reddit_options =!empty($sap_reddit_options)? $sap_reddit_options: array();
}

// Blogger
$sap_blogger_options = array();
if ( in_array('blogger', $networks) ) {
    $sap_blogger_options = $this->settings->get_user_setting('sap_blogger_options');
    $sap_blogger_options =!empty($sap_blogger_options)? $sap_blogger_options: array();
}

if (!class_exists('SAP_Linkedin')) {
    include ( CLASS_PATH . 'Social' . DS . 'liConfig.php' );
}
$linkedin = new SAP_Linkedin();

if (!class_exists('SAP_Facebook')) {
    include ( CLASS_PATH . 'Social' . DS . 'fbConfig.php' );
}
$facebook = new SAP_Facebook();

if (!class_exists('SAP_Pinterest')) {
    include ( CLASS_PATH . 'Social' . DS . 'pinConfig.php' );
}
$pinterest = new SAP_Pinterest();

if (!class_exists('SAP_Gmb')) {
    include ( CLASS_PATH . 'Social' . DS . 'gmbConfig.php' );
}
$google_business = new SAP_Gmb();

if (!class_exists('SAP_Instagram')) {
	include ( CLASS_PATH . 'Social' . DS . 'instaConfig.php' );
}
$instagram = new SAP_Instagram();

if (!class_exists('SAP_Reddit')) {
    include ( CLASS_PATH . 'Social' . DS . 'redditConfig.php' );
}
$reddit = new SAP_Reddit();

if (!class_exists('SAP_Youtube')) {
    include ( CLASS_PATH . 'Social' . DS . 'youtubeConfig.php' );
}
$youtube = new SAP_Youtube();

if (!class_exists('SAP_Blogger')) {
    include ( CLASS_PATH . 'Social' . DS . 'bloggerConfig.php' );
}
$blogger = new SAP_Blogger();
?>

<!-- Content Wrapper. Contains page content -->
<div class="content-wrapper">
    <!-- Content Header (Page header) -->
    <section class="content-header">
        <h1><?php echo $sap_common->lang('edit_multi_post_content'); ?><small></small></h1>
    </section>

    <!-- Main content -->
    <section class="content">
        <div class="row">
            <div class="col-md-12">
                <?php
                echo $this->flash->renderFlash();
                ?>
                <div class="box box-primary bg-white ptb-30">
                    <div class=" with-border add-new-post-title">
                        <h3 class="box-title"><?php echo $sap_common->lang('edit_content'); ?></h3>
                    </div>
                    <?php
                    $post_id = $match['params']['id'];
                    $post_data = $this->get_post($post_id, true);
                    if (empty($post_data)) {
                        header("Location:" . SAP_SITE_URL . "/posts/");
                        exit;
                    }
                    $post_shedule = $this->get_post_meta($post_id, 'sap_schedule_time', true);
                    $is_display_schedule = $this->is_display_schedule($post_id);
                    ?>
                    <form class="add-post-form" id="updatepost" method="POST" enctype="multipart/form-data" action="<?php echo SAP_SITE_URL . '/post/update/'; ?>">

                        <div class="box-body pt-0-form">
                            <div class="row">
                                <div class="d-flex flex-wrap col-md-8">
                                    <div class="row" style="width: 100%;">
                                        <div class="form-group  col-md-12">
                                            <div class="row">
                                                <input type="hidden" value="<?php echo (!empty($post_id) ? $post_id : 0); ?>" name="id">
                                            </div>
                                        </div>
                                        <div class="form-group sap-msg-wrap col-md-12">
                                            <textarea tabindex="2" class="multi-post-message form-control height-200" name="body" ><?php echo $post_data->body; ?></textarea>
                                        </div>
                                        
                                        <div class="form-group   col-md-8">
                                            <label class="control-label" id="sap-valid-url"><?php echo $sap_common->lang('link'); ?></label>
                                            <input class="form-control  sap-valid-url" tabindex="4" placeholder="Content share link" value="<?php echo (!empty($post_data->share_link) ? $post_data->share_link : ""); ?>" name="share_link">
                                        </div>
                                    </div>
                                </div>
                                <div class="form-group post-img-edit-pre col-md-4">
                                    <label class="control-label"><?php echo $sap_common->lang('content_image'); ?>
                                    </label>

                                    <?php if (!empty($post_data->img)) { ?>
                                        <input id="post-image" tabindex="3" value="" name="img" type="file" class="file file-loading" data-show-upload="false" data-show-caption="false" data-allowed-file-extensions='["png", "jpg","jpeg", "gif"]' data-max-file-size="<?php echo MINGLE_MAX_FILE_UPLOAD_SIZE; ?>" data-initial-preview="<img src='<?php echo SAP_IMG_URL . $post_data->img; ?>' class='uploaded-img'/>" >
                                    <?php } else { ?>
                                        <input id="post-image" tabindex="3" value="" name="img" type="file" class="file file-loading" data-show-upload="false" data-show-caption="false" data-max-file-size="<?php echo MINGLE_MAX_FILE_UPLOAD_SIZE; ?>" data-allowed-file-extensions='["png", "jpg","jpeg", "gif"]'/>
                                    <?php } ?>

                                    <input type="hidden" id="featured-img" name="edit_image" value="<?php echo (!empty($post_data->img) ? $post_data->img : 0); ?>">

                                    <?php if (!empty($sap_facebook_options['fb_app_version']) && $sap_facebook_options['fb_app_version'] >= 2.9) { ?>
                                        <div class="alert alert-warning sap-warning"><i class="fa fa-info-circle" aria-hidden="true"></i> <?php echo $sap_common->lang('quick_post_image_help_text'); ?></div>
                                    <?php } ?>
                                </div>  
                            </div>
                            <div class="nav-tabs-custom sap-post-social-list sap-post-social-list-wrap">
                                <ul class="nav nav-tabs">
                                    <?php
                                    if (!empty($sap_facebook_options['enable_facebook'])) {
                                        echo '<li><a href="#facebook_tab" data-toggle="tab">'.$sap_common->lang('network_label_fb').'</a></li>';
                                    }

                                    if (!empty($sap_twitter_options['enable_twitter'])) {
                                        echo '<li><a href="#twitter_tab" data-toggle="tab">'.$sap_common->lang('network_label_twitter').'</a></li>';
                                    }

                                    if (!empty($sap_linkedin_options['enable_linkedin'])) {
                                        echo '<li><a href="#linkedin_tab" data-toggle="tab">'.$sap_common->lang('network_label_li').'</a></li>';
                                    }

                                    if (!empty($sap_tumblr_options['enable_tumblr'])) {
                                        echo '<li><a href="#tumblr_tab" data-toggle="tab">'.$sap_common->lang('network_label_tumblr').'</a></li>';
                                    }

                                    if (!empty($sap_pinterest_options['enable_pinterest'])) {
                                        echo '<li><a href="#pinterest_tab" data-toggle="tab">'.$sap_common->lang('network_label_pinterest').'</a></li>';
                                    }
                                    if (!empty($sap_gmb_options['enable_google_business'])) {
                                        echo '<li><a href="#gmb_tab" data-toggle="tab">'.$sap_common->lang('network_label_gmb').'</a></li>';
                                    }
                                    if (!empty($sap_reddit_options['enable_reddit'])) {
                                        echo '<li><a href="#reddit_tab" data-toggle="tab">'.$sap_common->lang('network_label_reddit').'</a></li>';
                                    }
                                    if (!empty($sap_blogger_options['enable_blogger'])) {
                                        echo '<li><a href="#blogger_tab" data-toggle="tab">'.$sap_common->lang('network_label_blogger').'</a></li>';
                                    }  
                                    if (!empty($sap_insta_options['enable_instagram'])) {
										echo '<li><a href="#insta_tab" data-toggle="tab">'.$sap_common->lang('network_label_insta').'</a></li>';
									}    

                                   
                                    
                                    ?>
                                </ul>
                                <div class="tab-content sap-post-social-list-content">
                                    <?php
                                    if (!empty($sap_facebook_options['enable_facebook'])) {

                                        echo'<div class="tab-pane" id="facebook_tab">';
                                        include SAP_APP_PATH . 'view' . DS . 'Posts' . DS . 'tabs' . DS . 'facebook.php';
                                        echo '</div>';
                                    }

                                    if (!empty($sap_twitter_options['enable_twitter'])) {

                                        echo '<div class="tab-pane" id="twitter_tab">';
                                        include SAP_APP_PATH . 'view' . DS . 'Posts' . DS . 'tabs' . DS . 'twitter.php';
                                        echo '</div>';
                                    }

                                    if (!empty($sap_linkedin_options['enable_linkedin'])) {

                                        echo '<div class="tab-pane" id="linkedin_tab">';
                                        include SAP_APP_PATH . 'view' . DS . 'Posts' . DS . 'tabs' . DS . 'linkedin.php';
                                        echo '</div>';
                                    }

                                    if (!empty($sap_tumblr_options['enable_tumblr'])) {

                                        echo '<div class="tab-pane" id="tumblr_tab">';
                                        include SAP_APP_PATH . 'view' . DS . 'Posts' . DS . 'tabs' . DS . 'tumblr.php';
                                        echo '</div>';
                                    }

                                    if (!empty($sap_pinterest_options['enable_pinterest'])) {
                                        echo '<div class="tab-pane" id="pinterest_tab">';
                                        include SAP_APP_PATH . 'view' . DS . 'Posts' . DS . 'tabs' . DS . 'pinterest.php';
                                        echo '</div>';
                                    }
                                    if (!empty($sap_gmb_options['enable_google_business'])) {
                                        echo '<div class="tab-pane" id="gmb_tab">';
                                        include SAP_APP_PATH . 'view' . DS . 'Posts' . DS . 'tabs' . DS . 'gmb.php';
                                        echo '</div>';
                                    }

                                    if (!empty($sap_blogger_options['enable_blogger'])) {
                                        echo '<div class="tab-pane" id="blogger_tab">';
                                        include SAP_APP_PATH . 'view' . DS . 'Posts' . DS . 'tabs' . DS . 'blogger.php';
                                        echo '</div>';
                                    }
                                    
                                    if (!empty($sap_reddit_options['enable_reddit'])) {
                                        echo '<div class="tab-pane" id="reddit_tab">';
                                        include SAP_APP_PATH . 'view' . DS . 'Posts' . DS . 'tabs' . DS . 'reddit.php';
                                        echo '</div>';
                                    }

                                    if (!empty($sap_insta_options['enable_instagram'])) {
                                        echo '<div class="tab-pane" id="insta_tab">';
                                        include SAP_APP_PATH . 'view' . DS . 'Posts' . DS . 'tabs' . DS . 'instagram.php';
                                        echo '</div>';
                                    }
                                    ?>
                                </div>
                                <!-- /.tab-content -->
                            </div>
                            <div class="col-sm-12 edit-multi-post-schedule 123">
                                <div class="form-group row">
                                    <label for="sap-schedule-time" class="col-sm-4 control-label"><?php echo $sap_common->lang('schedule_global'); ?><i class="fa fa-question-circle" data-container="body" data-toggle="popover" data-trigger="hover" data-placement="right" data-content="Enter schedule time to publish post on social media. This setting applies the schedule time for all the social media and it gets over-written if Schedule Individually is applied for any particular social media."></i></label>
                                    <div class="col-sm-4">                
                                        <i class="fa fa-clock-o sap-schedule-icon <?php echo!empty($post_shedule) ? 'obj-hide' : ''; ?>" aria-hidden="true"></i>
                                        
                                        <input type="text" name="sap-schedule-time" id="sap-schedule-time" readonly="" class="sap-datetime form-control <?php echo!empty($post_shedule) ? 'obj-inline' : ''; ?>" value=" <?php echo!empty($post_shedule) ? date('Y-m-d H:i', $post_shedule) : '' ?> " >
                                    </div>
                                </div>
                            </div>
                            <input type="hidden" name="sap_is_display_schedule" id="sap_is_display_schedule" value="<?php echo $is_display_schedule; ?>">
                        </div>
                        <!-- /.box-body -->
                        <div class="box-footer">
                            <div class="">
                                <!-- pull-right -->
                                <input type="hidden" class="tgl tgl-ios" id="status" value="1" name="status">
                                <input type="hidden" name="form-updated" value="1">
                                <button type="submit" class="add-new-post btn btn-primary"><i class="fa fa-inbox"></i> <?php echo $sap_common->lang('update'); ?></button>
                            </div>              
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </section>
</div>
<!-- /.content-wrapper -->
<?php
include SAP_APP_PATH . 'footer.php';
?>

<script type="text/javascript">
    'use strict';
    $(function () {
        //Initialize Select2 Elements
        $('.select2').select2();
    });

</script>