<?php 

/* Check the absolute path to the Social Auto Poster directory. */
if ( !defined( 'SAP_APP_PATH' ) ) {
    // If SAP_APP_PATH constant is not defined, perform some action, show an error, or exit the script
    // Or exit the script if required
    exit();
}

global $router, $match,$sap_common;
$settings_object      = new SAP_Settings();
?>

<!DOCTYPE html>
<html>
    <head>
        <meta charset="utf-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <title><?php echo empty( $settings_object->get_options('mingle_site_name') ) ? SAP_NAME : $settings_object->get_options('mingle_site_name'); ?></title>
        <!-- Tell the browser to be responsive to screen width -->
        <meta content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no" name="viewport">
        <!-- Bootstrap 3.3.7 -->
        <link rel="stylesheet" href="<?php echo SAP_SITE_URL . '/assets/css/bootstrap.min.css'; ?>">
        
        <?php if(!empty($settings_object->get_options('mingle_favicon') )) {?>
        
        <link rel="icon" href="<?php echo SAP_IMG_URL . $settings_object->get_options('mingle_favicon'); ?>" type="image/png" sizes="32x32">
    
        <?php }else{?>

         <link rel="icon" href="<?php echo SAP_SITE_URL . '/assets/images/favicon.png'; ?>" type="image/png" sizes="32x32">
    <?php } ?>
        <!-- Font Awesome -->
        <link rel="stylesheet" href="<?php echo SAP_SITE_URL . '/assets/css/font-awesome.min.css'; ?>">
        <!-- Theme style -->
        <link rel="stylesheet" href="<?php echo SAP_SITE_URL . '/assets/css/mingle-social-auto-poster.min.css'; ?>">
        <!-- AdminLTE Skins. Choose a skin from the css/skins
             folder instead of downloading all of them to reduce the load. -->
        <link rel="stylesheet" href="<?php echo SAP_SITE_URL . '/assets/css/_all-skins.min.css'; ?>">
        <!-- Login Page CSS -->
        <link rel="stylesheet" href="<?php echo SAP_SITE_URL . '/assets/css/mingle-login.css'; ?>">
        <!-- Google Font -->
        <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,600,700,300italic,400italic,600italic">
    </head>
    
    <body class="hold-transition login-page">
        <!-- login --> 
         <div class="row h-100 no-gutters">
                <div class="d-lg-block col-lg-4 h-100 bg-plum-plate">
                    <div class="login-logo-inner">
                        <div class="login-logo">
                            <?php
                            
                            if(!empty($settings_object->get_options('mingle_logo')) ){ ?>
                                
                                <img src="<?php echo SAP_IMG_URL .$settings_object->get_options('mingle_logo'); ?>" class="mingle-logo" />

                            <?php }else{?>

                                <img src="<?php echo SAP_SITE_URL .'/assets/images/Mingle-Logo.svg'; ?>" class="mingle-logo" />

                           <?php } ?>
                            
                        </div>
                    </div>
                </div>
                <div class="h-100 d-flex bg-white justify-content-center align-items-center col-md-12 col-lg-8  login-box-wrap">
                    <div class="login-box">
                        
                        <!-- /.login-logo -->
                        <div class="login-box-body forgot-password">
                            <?php
                            if (isset($_SESSION['flash']['messageStack']) && !empty($_SESSION['flash']['messageStack'])) {
                                foreach ($_SESSION['flash']['messageStack'] as $key => $value) {
                                    if (isset($value['unique']) && !empty($value['unique'])) {
                                        unset($_SESSION['flash']['messageStack'][$key]);
                                    }
                                }
                            }
                            echo $this->flash->renderFlash();
                            ?>
                            <h4 class="login-text"><?php $sap_common->e_lang('reset_your_password');?></h4>
                            <form action="<?php echo SAP_SITE_URL . '/forgot-password-process/'; ?>" method="post">
                                <label><?php echo $sap_common->lang('reset_password_help_text'); ?></label>
                                <div class="form-group has-feedback">
                                    <input type="email" name="user_email" id="user_email" class="form-control" placeholder="<?php echo $sap_common->lang('email'); ?>" required="" autofocus="" tabindex="1">
                                    <!-- <span class="glyphicon glyphicon-envelope form-control-feedback"></span> -->
                                </div>
                                <div class="row">
                                    <!-- /.col -->
                                    <div class="col-xs-6">
                                        <button type="submit" class="btn btn-primary btn-block btn-flat"><?php echo $sap_common->lang('reset_btn_text'); ?></button>
                                    </div>

                                    <!-- /.col -->
                                </div>
                                <div class="sign-in-btn"> 
                                    <a class="back-to-login" href="<?php echo $router->generate('login') ?>"><?php echo $sap_common->lang('back_to_login_text'); ?></a><br> 
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
    </body>
    <!-- jQuery 3 -->
    <script src="<?php echo SAP_SITE_URL . '/assets/js/jquery.min.js'; ?>"></script>
    <!-- Bootstrap 3.3.7 -->
    <script src="<?php echo SAP_SITE_URL . '/assets/js/bootstrap.min.js'; ?>"></script>
</body>
</html>