<?php

/* Check the absolute path to the Social Auto Poster directory. */
if ( !defined( 'SAP_APP_PATH' ) ) {
    // If SAP_APP_PATH constant is not defined, perform some action, show an error, or exit the script
    // Or exit the script if required
    exit();
}

?>
<!-- Left side column. contains the logo and sidebar -->
<aside class="main-sidebar">
	<!-- sidebar: style can be found in sidebar.less -->
	<section class="sidebar">
		<!-- Sidebar user panel -->

		<!-- sidebar menu: : style can be found in sidebar.less -->
		<ul class="sidebar-menu" data-widget="tree">

			<?php
			global $sap_common;
			$SAP_Mingle_Update = new SAP_Mingle_Update();
			$license_data = $SAP_Mingle_Update->get_license_data();

	
			$role = isset( $_SESSION['user_details'] ) ? $_SESSION['user_details'] : array();
			if( $role['role'] == 'user' && ! empty( $license_data['license_key'] ) ) { ?>
				<li class="<?php echo ($match['name'] == 'quick_posts' || $match['name'] == 'quick_add_post' || $match['name'] == 'quick_save_post') ? 'active':'';?>">
					<a href="<?php echo $router->generate('quick_posts'); ?>">
						<i class="fa fa-pencil-square-o"></i> <span><?php echo $sap_common->lang('quick_single_post'); ?></span>            
					</a>
				</li>
				<li class="<?php echo ($match['name'] == 'posts' || $match['name'] == 'addpost' || $match['name'] == 'viewpost' ) ? "active":"";?>">
					<a href="<?php echo $router->generate('posts'); ?>">
						<i class="fa fa-dashboard"></i> <span><?php echo $sap_common->lang('multi-post'); ?></span>
					</a>
				</li>

				<li class="<?php echo ($match['name'] == 'settings') ? "active":"";?>">
					<a href="<?php echo $router->generate('settings'); ?>">
						<i class="fa fa-gears"></i> <span><?php echo $sap_common->lang('settings'); ?></span>            
					</a>
				</li>
				<li class="<?php echo ($match['name'] == 'report') ? "active":"";?>">
					<a href="<?php echo $router->generate('report'); ?>">
						<i class="fa fa-pie-chart"></i> <span><?php echo $sap_common->lang('report'); ?></span>            
					</a>
				</li>
				<li class="<?php echo ($match['name'] == 'logs') ? "active":"";?>">
					<a href="<?php echo $router->generate('logs'); ?>">
						<i class="fa fa-list-ol"></i> <span><?php echo $sap_common->lang('posting_logs'); ?></span>            
					</a>
				</li>
				<li class="<?php echo ($match['name'] == 'debug') ? "active":"";?>">
					<a href="<?php echo $router->generate('debug'); ?>">
						<i class="fa fa-list"></i> <span><?php echo $sap_common->lang('debug_logs'); ?></span>
					</a>
				</li>
				<li class="<?php echo ($match['name'] == 'your-subscription' ) ? "active":"";?>">
					<a href="<?php echo $router->generate('your-subscription'); ?>" class="subscription-menu">
						<i class="fa fa-id-card"></i> 
						<span><?php echo $sap_common->lang('your_subscription'); ?></span>
						<span class="pull-right-container">
								
					</a>
				</li>
			<?php } else if ( $role['role'] != 'user' && empty( $license_data['license_key'] ) ) { ?>

					<li class="<?php echo ($match['name'] == 'mingle_update') ? "active":"";?>">
						<a href="<?php echo $router->generate('mingle_update'); ?>">
							<i class="fa fa-refresh"></i> <span><?php echo $sap_common->lang('license_and_updates'); ?></span>
							<?php 
							if( !empty($_SESSION['Update_version'] ) && $_SESSION['Update_version']  > SAP_VERSION ){ ?>
							<span class="pull-right-container">
								<span class="label fa <?php if( $sap_common->sap_is_license_activated() ){ echo 'fa-cloud-download bg-red'; }?> pull-right">&nbsp;</span>
							</span>
							<?php } ?>
						</a>
					</li>

			<?php } else if ( $role['role'] == 'user' && empty( $license_data['license_key'] ) ) { ?>
					<li class="<?php echo ($match['name'] == 'mingle_update') ? "active":"";?>">
						<a href="<?php echo $router->generate('mingle_update'); ?>">
							<i class="fa fa-refresh"></i> <span><?php echo $sap_common->lang('admin_licence_not_register'); ?></span>
							<?php 
							if( !empty($_SESSION['Update_version'] ) && $_SESSION['Update_version']  > SAP_VERSION ){ ?>
							<span class="pull-right-container">
								<span class="label fa <?php if( $sap_common->sap_is_license_activated() ){ echo 'fa-cloud-download bg-red'; }?> pull-right">&nbsp;</span>
							</span>
							<?php } ?>
						</a>
					</li>


			<?php } else { ?>

				<li class="<?php echo ($match['name'] == 'plan_list' || $match['name'] == 'add_plan') ? "active":"";?>">
					<a href="<?php echo $router->generate('plan_list'); ?>">
						<i class="fa fa-id-badge"></i> <span><?php echo $sap_common->lang('membership_levels'); ?></span>            
					</a>
				</li>	
				<li class="<?php echo ($match['name'] == 'member_list' || $match['name'] == 'add_member') ? "active":"";?>">
					<a href="<?php echo $router->generate('member_list'); ?>">
						<i class="fa fa-user-plus"></i> <span><?php echo $sap_common->lang('customers'); ?></span>            
					</a>
				</li>

				<li class="<?php echo ($match['name'] == 'membership_list' || $match['name'] == 'add_membership' ) ? "active":"";?>">
					<a href="<?php echo $router->generate('membership_list'); ?>">
						<i class="fa fa-id-card"></i><span><?php echo $sap_common->lang('memberships'); ?></span>            
					</a>
				</li>

				<li class="<?php echo ($match['name'] == 'payments' || $match['name'] == 'add-payment') ? "active":"";?>">
					<a href="<?php echo $router->generate('payments'); ?>">
						<i class="fa fa-credit-card-alt"></i> <span><?php echo $sap_common->lang('payments'); ?></span>            
					</a>
				</li>

				<li class="<?php echo ($match['name'] == 'coupons' || $match['name'] == 'add-coupon') ? "active":"";?>">
					<a href="<?php echo $router->generate('coupons'); ?>">
						<i class="fa fa-tag"></i> <span><?php echo $sap_common->lang('coupons'); ?></span>            
					</a>
				</li>
			
				<li class="<?php echo ($match['name'] == 'general_settings') ? "active":"";?>">
					<a href="<?php echo $router->generate('general_settings'); ?>">
						<i class="fa fa-cog"></i> <span><?php echo $sap_common->lang('general_settings'); ?></span>            
					</a>
				</li>

				<li class="<?php echo ($match['name'] == 'mingle_update') ? "active":"";?>">
					<a href="<?php echo $router->generate('mingle_update'); ?>">
						<i class="fa fa-refresh"></i> <span><?php echo $sap_common->lang('license_and_updates'); ?></span>
						<?php 
						if( !empty($_SESSION['Update_version'] ) && $_SESSION['Update_version']  > SAP_VERSION ){ ?>
							<span class="pull-right-container">
								<span class="label fa <?php if( $sap_common->sap_is_license_activated() ){ echo 'fa-cloud-download bg-red'; }?> pull-right">&nbsp;</span>
							</span>
						<?php } ?>
					</a>
				</li>
			<?php } ?>
		</ul>
	</section>
	<!-- /.sidebar -->
</aside>