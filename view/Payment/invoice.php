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
?>
<html lang="en-US" prefix="og: http://ogp.me/ns# fb: http://ogp.me/ns/fb#"><head>
	<head>
	    <!-- Metadata -->
	    <meta charset="UTF-8">
	    <meta name="HandheldFriendly" content="true">

		<!-- Title -->
		<title><?php echo $sap_common->lang('invoice'); ?></title>
		<style type="text/css">
			.styled-table {
  			  border-collapse: collapse;
		    margin: 25px 0;
		    font-size: 0.9em;
		    font-family: sans-serif;
		    min-width: 400px;
		    box-shadow: 0 0 20px rgba(0, 0, 0, 0.15);
		}
		.styled-table thead tr {
		    background-color: #dddddd;
		    color: #111;
		    text-align: left;
		}
		.styled-table th,
		.styled-table td {
		    padding: 12px 15px;
		}
		.styled-table tbody tr {
		    border-bottom: 1px solid #dddddd;
		}

		.styled-table tbody tr:nth-of-type(even) {
		    background-color: #dddddd;
		}

		.styled-table tbody tr:last-of-type {
		    border-bottom: 2px solid #dddddd;
		}

		.styled-table tbody tr.active-row {
		    font-weight: bold;
		    color: #009879;
		}

		</style>
	</head>
	<body>

		<div id="printableArea" tyle="width: 50%;margin: 0 auto;padding: 15px;display: block;clear: both;">
			<section id="contacts" style="width: 50%;margin: 0 auto;padding: 15px;display: block;">
				<div class="alignleft" style=" float: left;  width: 45%;  background: #ddd; padding: 10px;  font-size: 20px;  font-weight: bold;">
					<header><?php echo $sap_common->lang('invoice'); ?> <?php echo $payments_details->id ?></header>		
				</div>

				<div class="alignright" style="float: left; width: 45%;">

					<header style="background: #ddd;padding: 10px;font-size: 20px;font-weight: bold;width: 109%;"><?php echo $sap_common->lang('bill_to'); ?>:</header>

					<article>
						<p><strong><?php echo $payments_details->first_name .' '. $payments_details->last_name ?></strong></p>
						<p><strong><?php echo $payments_details->email ?></strong></p>			
					</article>
				</div>
			</section>

			<section id="items" style="width: 50%;margin: 0 auto;padding: 15px;display: block;clear: both;">

				<table class="styled-table" style="width: 100%; padding: 10px;">
					<thead style="text-align: left; padding: 15px; background: #ddd;">
						<tr style="width: 100%;">
							<th style="width: 50%;"><?php echo $sap_common->lang('subscription'); ?></th>
							<th style="width: 50%;"><?php echo $sap_common->lang('allowed_networks'); ?></th>
							<th><?php echo $sap_common->lang('amount'); ?></th>
						</tr>
					</thead>
					<tbody style="margin: 20px 0;">
						<tr>

							<?php 
							$networklist = '';
							$networks = unserialize($payments_details->networks);
							if( !empty( $networks ) ){
								foreach ($networks as $key => $network) {
									$networklist .=  sap_get_networks_label( $network ).',';
								}
							}
							
							?>
							<td class="name"><?php echo $payments_details->plan_name ?></td>
							<td class="name"><?php echo rtrim( $networklist,',') ?></td>
							<td class="price">$<?php echo round($payments_details->amount,2) ?></td>
						</tr>
					</tbody>
					<tfoot>						
						
						<tr>
							<td class="name"></td>
							<td class="name">Subtotal:</td>
							<td class="price">$<?php echo round($payments_details->amount,2) ?></td>
						</tr>
						<tr>
							<td class="name"><strong></strong></td>
							<td class="name">
											<?php
											echo $sap_common->lang('discount_amount');
											echo isset($payments_details->coupon_name) != '' || isset($payments_details->coupon_name) != null ? " (".$payments_details->coupon_name.")" : ""; ?>:</td>
							<td class="price">$<?php echo isset($payments_details->coupon_discount_amount) != '' || isset($payments_details->coupon_discount_amount) != null ? round($payments_details->coupon_discount_amount,2) : "0"; ?></td>
						</tr>
						<tr>
							<td class="name"><strong></strong></td>
							<td class="name"><strong><?php echo $sap_common->lang('total_price'); ?>:</strong></td>
							<td class="price"><strong>$<?php echo round($payments_details->amount,2) > round($payments_details->coupon_discount_amount,2) ? round($payments_details->amount,2) - round($payments_details->coupon_discount_amount,2) : "0"; ?></strong></td>
						</tr>
						<tr>
							<td class="name"></td>
							<td class="name"><?php echo $sap_common->lang('payment_status'); ?>:</td>
							<td class="price"><?php echo get_payment_status_label($payments_details->payment_status) ?></td>
						</tr>
					</tfoot>
				</table>
				

				<p style=" float: left;  width: 100%;  background: #ddd; padding: 10px;  font-size: 16px;"><?php echo $sap_common->lang('ADDITIONAL_INFO'); ?> :</p>
				<p style="margin-top: 10px;"><?php echo $sap_common->lang('payment_date'); ?> : <?php echo sap_format_date($payments_details->payment_date,true) ?></p>
				
				
				
			</section>
			<p class="print alignright" style="text-align: center;"><a href="#" onclick="window.print()"><?php echo $sap_common->lang('print'); ?></a></p>

		</div>
	</body>
</html>