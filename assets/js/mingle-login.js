'use strict';
$(document).ready(function () {

	$(document).on('change','.price_zero_cls',function(){
        
        $('.payment_method_cls input[type="radio"]').prop('checked',false); 
        $('.payment_method_cls').css('display','none');
        $('.stripe-payment-fields').css('display','none');
        $('.auto-renew-opt').css('display','none');
        $('.stripe-payment-fields .form-group').removeClass('has-error');
        $('.stripe-payment-fields .form-group .error.help-block').remove();
        $('.apply_coupon_section').hide();

    });

    $(document).on('change','.price_not_zero_cls',function(){

        $('#payment_stripe').prop('checked',true); 
        $('.payment_method_cls').css('display','block');
        $('.stripe-payment-fields').css('display','block');
        $('.auto-renew-opt').css('display','block');
        $('.apply_coupon_section').show();

    });


    $(document).on('change','.add-member-form .unlimited_plan',function(){
    	$('.auto-renew-opt').hide();
    });


	$(document).on('change','.plan',function(){	

		var plan_id = $(this).val();
		
		var data = {
			plan_id: plan_id
		}

		$.ajax({
			type: 'POST',
			url: SAP_SITE_URL + '/plan_details/',
			data: data,
			success: function (response) {
				
				if(plan_id == ''){
					$("#plan_result").fadeOut();
				}
				else{
					$("#plan_result").fadeIn();
				}
				$("#plan_result").html(response);
				$('.apply_coupon_button').trigger("click", 'plan');
			}
		});
	});

	$(document).on('change','.payment-gateway',function(){	

		var payment_gateway = $(this).val();

		$('.stripe-payment-fields').hide();
		$('.auto-renew-opt').hide();

		if( payment_gateway == 'stripe'){
			$('.stripe-payment-fields').show();
		}

		if( payment_gateway == 'stripe' || payment_gateway == 'paypal'){			
			$('.auto-renew-opt').show();
		}

	});


	$(".plan:radio:first").attr("checked", true).trigger("change"); 



	if( $('#add-member').length > 0  ){

		$.validator.addMethod( "passwordCheck",
			function(value, element) {
				return this.optional(element) || /(?=.*\d)(?=.*[a-z])(?=.*[A-Z])(?=.*[!@#$%^&amp;*()_+}{&quot;:;'?/&gt;.&lt;,])(?!.*\s).*$/gm.test(value);
			},
		);
		
		// Add member validation
		$( '#add-member').validate( {			


			normalizer: function(value) {
				// Trim the value of every element
				return $.trim(value);
			},
			rules: {
				sap_firstname: {
					required: true
				},
				sap_email: {
					required: true,
					email: true
				},
				sap_password: {
					required: true,
					minlength: 8,
					normalizer: function(value) {
						return $.trim(value);
					},
					passwordCheck: true
				},
				sap_repassword: {
					required: true,
					minlength: 8,
					equalTo: "#sap_password"
				},
				sap_plan: {
					required: true,
				},
			},
			messages: {
				sap_firstname: {
					required: 'Please enter your first name.'
				},
				sap_email: {
					required: 'Please enter your email',
					email: 'please enter valid email'
				},
				sap_password: {
					required: "Please enter a password",
					minlength: "Your password must be at least 8 characters long",
					passwordCheck: "Password should be 8 characters long as well as it should contain the capital , lower case letters, at least one digit and one special character (1-9, !, *, _, etc.).",
				},
				sap_repassword: {
					required: "Please re-enter a password",
					//minlength: "Your password must be at least 8 characters long",
					equalTo: "Please enter the same password",
				}
				/*sap_plan: {
					required: "Please select valid plan",
				}*/
			},
			errorElement: "em",
			errorPlacement: function (error, element) {
				// Add the `help-block` class to the error element
				error.addClass("help-block");
				
				// Add `has-feedback` class to the parent div.form-group
				// in order to add icons to inputs
				element.parents(".form-group").addClass("has-error");

				error.insertAfter(element);
			},
			success: function (label, element) {
				// Add the span element, if doesn't exists, and apply the icon classes to it.
			},
			highlight: function (element, errorClass, validClass) {
				$(element).parents(".form-group").addClass("has-error").removeClass("has-success");
			},
			unhighlight: function (element, errorClass, validClass) {
				$(element).parents(".form-group").removeClass("has-error");
			}
		} );
	}
	
	$(document).on('click','.apply_coupon_button',function(event, callType = ''){	
		console.log(callType);
		var coupon_code = $('#apply_coupon_text').val();
		if(coupon_code) {
			var data = {
				coupon_code: coupon_code
			}

			$.ajax({
				type: 'POST',
				url: SAP_SITE_URL + '/check_coupon_code/',
				data: data,
				dataType: 'JSON',
				success: function (response) {
					console.log(response);
					var alertType = 'danger';
					if(response.status == 200) {
						alertType = 'success';
						var disc_amt = 0;
						var main_amt = $('.rcp-main-total').html().split('$');
						if(parseFloat(main_amt[1]) > 0) {
							if(response.data.coupon_type != "fixed_discount") {
								disc_amt = (parseFloat(main_amt[1]) * parseFloat(response.data.coupon_amount)) / 100;
							} else {
								disc_amt = parseFloat(response.data.coupon_amount);
							}
							var payable_amt = 0;
							if(parseFloat(main_amt[1]) > disc_amt) {
								payable_amt = parseFloat(main_amt[1]) - disc_amt;
							}
							$('#coupon_id').val(response.data.id);
							$('#apply_coupon_amount').val(payable_amt);
							$('#applied_coupon_amount').val(disc_amt);
							var tr = "<tr class='coupon-discount-amount'>" +
										"<th>" + disc_amount + "</th>" +
										"<td> $" + disc_amt + "</td>" +
									"</tr>"+
									"<tr class='coupon-payable-amount'>" +
										"<th>" + payable_amount + "</th>" +
										"<td> $" + payable_amt + "</td>" +
									"</tr>";
							$('.membership_detail_tr').find('.coupon-discount-amount').remove();
							$('.membership_detail_tr').find('.coupon-payable-amount').remove();
							$(tr).insertBefore(".next_renewal_due");
						}
					} else {
						$('.membership_detail_tr').find('.coupon-discount-amount').remove();
						$('.membership_detail_tr').find('.coupon-payable-amount').remove();
						$('#apply_coupon_amount').val('');
						$('#coupon_id').val('');
					}
					var msgDiv = '<div class="alert alert-' + alertType + ' alert-padding alert-width">' +
										response.message +
									'</div>';
					$(".coupon_message").fadeIn();
					$(".coupon_message").html(msgDiv);
				}
			});
		} else {
			if(callType != 'plan') {
				var msgDiv = '<div class="alert alert-danger alert-padding alert-width">' +
								coupon_error_message +
							'</div>';
				$(".coupon_message").fadeIn();
				$(".coupon_message").html(msgDiv);
			} else {
				$(".coupon_message").html('');
			}
			$('#coupon_id').val('');
			$('#apply_coupon_amount').val('');
			$('.membership_detail_tr').find('.coupon-discount-amount').remove();
			$('.membership_detail_tr').find('.coupon-payable-amount').remove();
		}
	});			
});