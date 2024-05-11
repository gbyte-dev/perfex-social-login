<?php 

/* Check the absolute path to the Social Auto Poster directory. */
if ( !defined( 'SAP_APP_PATH' ) ) {
    // If SAP_APP_PATH constant is not defined, perform some action, show an error, or exit the script
    // Or exit the script if required
    exit();
}

global $sap_common; ?>
<!DOCTYPE html>
<html>
    <head>
        <meta charset="utf-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <title><?php echo empty( $this->setting->get_options('mingle_site_name') ) ? SAP_NAME : $this->setting->get_options('mingle_site_name');; ?></title>
        <!-- Tell the browser to be responsive to screen width -->
        <meta content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no" name="viewport">
        <!-- Bootstrap 3.3.7 -->
        <link rel="stylesheet" href="<?php echo SAP_SITE_URL . '/assets/css/bootstrap.min.css'; ?>">
        <link rel="icon" href="<?php echo SAP_SITE_URL . '/assets/images/favicon.png'; ?>" type="image/png" sizes="32x32">
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
      
        <div class="login-box">
            <div class="login-logo">
                 
                <?php if(!empty($this->setting->get_options('mingle_logo'))){ ?>
                  
                  <img src="<?php echo SAP_IMG_URL .$this->setting->get_options('mingle_logo'); ?>" class="mingle-logo" />
                  
                  <?php }else{ ?>

                   <img src="<?php echo SAP_SITE_URL .'/assets/images/Mingle-Logo.svg'; ?>" class="mingle-logo" />
                  <?php }?>

                <p><?php echo empty( $this->setting->get_options('mingle_site_name') ) ? SAP_NAME : $this->setting->get_options('mingle_site_name'); ?></p>
            </div>
            <!-- /.login-logo -->
            <div class="login-box-body">
                <?php
                if(isset($_SESSION['flash']['messageStack']) && !empty($_SESSION['flash']['messageStack'])){
                    foreach ($_SESSION['flash']['messageStack'] as $key => $value){
                        if( isset($value['unique']) && !empty($value['unique']) ){
                            unset($_SESSION['flash']['messageStack'][$key]);
                        }
                    }
                }
                echo $this->flash->renderFlash();
                ?>
                <p class="login-box-msg"><?php echo $sap_common->lang('reset_pass_enter_pass_text'); ?></p>
                <form id="reset_password" action="" method="post">

                    <div class="form-group has-feedback">
                        <input type="password" name="password" id="password" class="form-control" placeholder="<?php echo $sap_common->lang('reset_pass_plh'); ?>" required="" autofocus="" tabindex="1">
                        <span class="glyphicon glyphicon-lock form-control-feedback"></span>
                    </div>
                    <div class="form-group has-feedback">
                        <input type="password" name="confirm_password" id="confirm_password" class="form-control" placeholder="<?php echo $sap_common->lang('reset_pass_re_pass_plh'); ?>" required="" autofocus="" tabindex="1">
                        <span class="glyphicon glyphicon-lock form-control-feedback"></span>
                    </div>
                    <div class="sign-in-btn">
                        <!-- /.col -->
                        <div class="col-xs-6">
                            <button type="submit" name="reset_password_submit" class="btn btn-primary btn-block btn-flat"><?php echo $sap_common->lang('reset_pass_save_text'); ?></button>
                        </div>
                        <!-- /.col -->
                    </div>
                    <div class="row">
                        <div class="col-md-12">
                            <a class="back-to-login" href="<?php echo $router->generate('login') ?>"><?php echo $sap_common->lang('reset_pass_back_login'); ?></a><br>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </body>
    <!-- jQuery 3 -->
    <script src="<?php echo SAP_SITE_URL . '/assets/js/jquery.min.js'; ?>"></script>
    <!-- Bootstrap 3.3.7 -->
    <script src="<?php echo SAP_SITE_URL . '/assets/js/bootstrap.min.js'; ?>"></script>

    <script type="text/javascript" src="<?php echo SAP_SITE_URL . '/assets/js/jQuery-validate/jquery.validate.js'; ?>"></script>
	<script type="text/javascript" src="<?php echo SAP_SITE_URL . '/assets/js/users.js'; ?>"></script>
</body>
</html>