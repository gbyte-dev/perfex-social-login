<?php global $sap_common; 

/* Check the absolute path to the Social Auto Poster directory. */
if ( !defined( 'SAP_APP_PATH' ) ) {
    // If SAP_APP_PATH constant is not defined, perform some action, show an error, or exit the script
    // Or exit the script if required
    exit();
}

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
  <head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <title><?php echo $sap_common->lang('subscription_renew_for'); ?></title>

    <style type="text/css" rel="stylesheet" media="all">
    *:not(br):not(tr):not(html) {
      font-family: Arial, 'Helvetica Neue', Helvetica, sans-serif;
      box-sizing: border-box;
    }
    .email-wrapper {
      width: 100%;
      margin: 0;
      padding: 0;
      -premailer-width: 100%;
      -premailer-cellpadding: 0;
      -premailer-cellspacing: 0;
      background-color: #F2F4F6;
    }
    .email-content {
      width: 100%;
      margin: 0;
      padding: 0;
      -premailer-width: 100%;
      -premailer-cellpadding: 0;
      -premailer-cellspacing: 0;
    }
    .email-masthead {
      padding: 25px 0;
      text-align: center;
    }
    .email-body {
      width: 100%;
      margin: 0;
      padding: 0;
      -premailer-width: 100%;
      -premailer-cellpadding: 0;
      -premailer-cellspacing: 0;
      border-top: 1px solid #EDEFF2;
      border-bottom: 1px solid #EDEFF2;
      background-color: #FFFFFF;
    }
    .email-body_inner {
      width: 570px;
      margin: 0 auto;
      padding: 0;
      -premailer-width: 570px;
      -premailer-cellpadding: 0;
      -premailer-cellspacing: 0;
      background-color: #FFFFFF;
    }
    .content-cell {
      padding: 35px;
    }
    .body-action {
      width: 100%;
      margin: 30px auto;
      padding: 0;
      -premailer-width: 100%;
      -premailer-cellpadding: 0;
      -premailer-cellspacing: 0;
      text-align: center;
    }

    
    
    body {
      width: 100% !important;
      height: 100%;
      margin: 0;
      line-height: 1.4;
      background-color: #F2F4F6;
      color: #74787E;
      -webkit-text-size-adjust: none;
    }
    p,ul,ol,blockquote {
      line-height: 1.4;
      text-align: left;
    }
    a{ color: #3869D4; }
    a img {border: none;}
    
    
    .email-masthead_logo {
      width: 94px;
    }
    .email-masthead_name {
      font-size: 21px;
      font-weight: bold;
      color: #bbbfc3;
      text-decoration: none;
      text-shadow: 0 1px 0 white;
    }
    
    .email-footer {
      width: 570px;
      margin: 0 auto;
      padding: 0;
      -premailer-width: 570px;
      -premailer-cellpadding: 0;
      -premailer-cellspacing: 0;
      text-align: center;
    }
    .email-footer p {
      color: #AEAEAE;
    }
    .body-sub {
      margin-top: 25px;
      padding-top: 25px;
      border-top: 1px solid #EDEFF2;
    }
    .align-center {
      text-align: center;
    }
    @media only screen and (max-width: 600px) {
      .email-body_inner,
      .email-footer {
        width: 100% !important;
      }
    }
    @media only screen and (max-width: 500px) {
      .button {
        width: 100% !important;
      }
    }   
    .button {
      background-color: #3869D4;
      border-top: 10px solid #3869D4;
      border-right: 18px solid #3869D4;
      border-bottom: 10px solid #3869D4;
      border-left: 18px solid #3869D4;
      display: inline-block;
      color: #FFF;
      text-decoration: none;
      border-radius: 3px;
      box-shadow: 0 2px 3px rgba(0, 0, 0, 0.16);
      -webkit-text-size-adjust: none;
    }
    .button-green {
      background-color: #22BC66;
      border-top: 10px solid #22BC66;
      border-right: 18px solid #22BC66;
      border-bottom: 10px solid #22BC66;
      border-left: 18px solid #22BC66;
      color: #FFF !important;
    }
    p {
      margin-top: 0;
      color: #74787E;
      font-size: 16px;
      line-height: 1.5em;
      text-align: left;
    }
    p.sub {
      font-size: 12px;
    }
    p.center {
      text-align: center;
    }
    </style>
  </head>
  <body>
    <table class="email-wrapper" width="100%" cellpadding="0" cellspacing="0">
      <tr>
        <td align="center">
          <table class="email-content" width="100%" cellpadding="0" cellspacing="0">
            <tr>
              <td class="email-masthead" style="vertical-align: middle;">
                <div style="display: flex;">
                    <a href="<?php echo SAP_SITE_URL; ?>" style="display: flex;
 display: flex; align-items: center;
    margin: 0 auto;" class="email-masthead_name">
                    <?php if(!empty($this->setting->get_options('mingle_logo'))){ ?>
                     <img style="width: 150px;" src="<?php echo SAP_IMG_URL .$this->setting->get_options('mingle_logo'); ?>" class="mingle-logo" />
                        <?php }else{ ?>
                     <img style="width: 150px;" src="<?php echo SAP_SITE_URL .'/assets/images/mingle-logo.png'; ?>" class="mingle-logo" />
                        <?php }?>
          <p style="margin-bottom: 0;margin-top: 15px;margin-left: 2px;color: #2f5aa9;font-weight: bold;font-size: 20px;"><?php echo empty( $this->setting->get_options('mingle_site_name') ) ? SAP_NAME : $this->setting->get_options('mingle_site_name'); ?></p></a>
                </div>
              </td>
            </tr>
            <tr>
              <td class="email-body" width="100%" cellpadding="0" cellspacing="0">
                <table class="email-body_inner" align="center" width="570" cellpadding="0" cellspacing="0">
                  <tr>
                    <td class="content-cell">
                    <?php                                      

                    $userName = $cust_plan_result->first_name .' '.$cust_plan_result->last_name;

                    $email_tags   = array('{membership_level}','{subscription_id}','{user_name}','{expiration_date}','{sap_name}');

                    $replace_tags = array($plan_data->name,$cust_plan_result->stripe_subscription_id,$userName,date( "M j, Y g:i a", strtotime($expiration_date) ),empty( $this->setting->get_options('mingle_site_name') ) ? SAP_NAME : $this->setting->get_options('mingle_site_name'));

                      echo nl2br(str_replace($email_tags,$replace_tags,$renewal_email_content));
                    	
                   	?>
                      	
                    </td>
                  </tr>
                </table>
              </td>
            </tr>
            <tr>
              <td>
                <table class="email-footer" align="center" width="570" cellpadding="0" cellspacing="0">
                  <tr>
                    <td class="content-cell" align="center">
                        <p class="sub align-center">
                            <?php echo sprintf($sap_common->lang('new_account_email_temp_copy_rights'), 'Copyright &copy;', date('Y')); ?>
                        </p>
                    </td>
                  </tr>
                </table>
              </td>
            </tr>
          </table>
        </td>
      </tr>
    </table>
  </body>
</html>