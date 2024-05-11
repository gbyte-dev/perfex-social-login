var stripe = Stripe(stripe_publishable_key);
var elements = stripe.elements();
var cardElement = elements.create('card');
cardElement.mount('#stripe-card-element');

jQuery(document).ready(function($){
	console.log(stripe);
	var allowed_submit = false;
	var paymentForm = document.getElementById('add-member');
	paymentForm.addEventListener('submit', function (event) {
		if (allowed_submit) {
			return '';
		}

		event.preventDefault();
		paymentForm.querySelector('button[name="sap_add_member_submit"]').disabled = true;
		var data = {
			sap_plan: $('input[name=sap_plan]:checked').val(),
			sap_email: $('input[name=sap_email]').val(),
			sap_firstname: $('input[name=sap_firstname]').val(),
			auto_renew: $('input[name=auto_renew]:checked').val(),
			apply_coupon_amount: $('input[name=apply_coupon_amount]').val(),
			applied_coupon_amount: $('input[name=applied_coupon_amount]').val(),
			city: $('input[name=city]').val(),
			country: $('input[name=country]').val(),
			line1: $('input[name=line1]').val(),
			line2: $('input[name=line2]').val(),
			postal_code: $('input[name=postal_code]').val(),
			state: $('input[name=state]').val(),
		};

		$.ajax({
			type: 'POST',
			url: SAP_SITE_URL + '/set_payment_intent/',
			data: data,
			success: function (response) {
				var responseData = JSON.parse(response);
				console.log("before if: " + responseData);
				console.log("response: " + response);
				if (responseData.status == 'success') {
					if( !responseData.free ){
						stripe.confirmCardPayment(responseData.PI_client_secret, {
							payment_method: {
								card: cardElement
							},
						}).then(function(result) {
							console.log("Result here:", result); // Use separate argument for the object
							if (result.error) {
								// Display result.error.message in your UI.
								$('#response-message').text(result.error.message);
							} else {
								$html = '<input type="hidden" value="' + result.paymentIntent.id + '" name="stripe_payment_id"><input type="hidden" value="' + result.paymentIntent.next_action + '" name="stripe_payment_next_action"><input type="hidden" value="' + result.paymentIntent.payment_method + '" name="stripe_payment_method"><input type="hidden" value="' + result.paymentIntent.status + '" name="payment_status_succeeded"><input type="hidden" value="' + responseData.subscriptionId + '" name="stripe_subscriptionId">';
								$('#response-message').html($html);
								allowed_submit = true;
								$("#add-member").submit();
							}
						});
					} else {
						$html = '<input type="hidden" value="succeeded" name="payment_status_succeeded">';
						$('#response-message').html($html);
						allowed_submit = true;
						$("#add-member").submit();
					}
				}
			}
		});
	});
});
