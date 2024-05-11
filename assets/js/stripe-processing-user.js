var stripe = Stripe(stripe_publishable_key);
var elements = stripe.elements();
var cardElement = elements.create('card');
cardElement.mount('#stripe-card-element-user');

jQuery(document).ready(function($){

	jQuery( '.form-group.auto-renew-opt' ).hide();

	var allowed_submit = false;
	var paymentForm = document.getElementById('user-payment');
	
	paymentForm.addEventListener('submit', function (event) {

        if (allowed_submit) {
			return '';
		}
		event.preventDefault();
		paymentForm.querySelector('button[name="sap_add_member_user_submit"]').disabled = true;
		var data = {
			sap_plan: $('input[name=sap_plan]:checked').val(),
			auto_renew: $('input[name=auto_renew]:checked').val(),
			is_upgrade: $('input[name=is_upgrade]').val(),
			user_id: $('input[name=user_id]').val(),
			city: $('input[name=city]').val(),
			country: $('input[name=country]').val(),
			line1: $('input[name=line1]').val(),
			line2: $('input[name=line2]').val(),
			postal_code: $('input[name=postal_code]').val(),
			state: $('input[name=state]').val(),
		};
		$.ajax({
			type: 'POST',
			url: SAP_SITE_URL + '/set_payment_intent_user/',
			data: data,
			success: function (response) {
				var responseData = JSON.parse(response);
				console.log("before if: " + responseData);
				console.log("response: " + response);
				if (responseData.status == 'success') {
					console.log("status: " + responseData.status);
					stripe.confirmCardPayment(responseData.PI_client_secret, {
						payment_method: {
							card: cardElement
						},
					}).then(function(result) {
						console.log("Result here:", result); // Use separate argument for the object
						if (result.error) {
							// Display result.error.message in your UI.
							$('#response-message-user').text(result.error.message);
						} else {
							$html = '<input type="hidden" value="' + result.paymentIntent.id + '" name="stripe_payment_id"><input type="hidden" value="' + result.paymentIntent.next_action + '" name="stripe_payment_next_action"><input type="hidden" value="' + result.paymentIntent.payment_method + '" name="stripe_payment_method"><input type="hidden" value="' + result.paymentIntent.status + '" name="payment_status_succeeded"><input type="hidden" value="' + responseData.subscriptionId + '" name="stripe_subscriptionId">';
							$('#response-message-user').html($html);
							allowed_submit = true;
							$("#user-payment").submit();
						}
					});
				}
			}
		});
	});
});
