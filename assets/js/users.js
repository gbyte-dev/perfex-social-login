'use strict';

$(document).ready(function () {

    $.validator.setDefaults({
        submitHandler: function (form) {
             if ($(form).valid())
                form.submit();
            return false; // prevent normal form posting
        }
    });

    $.validator.methods.email = function( value, element ) {
      return this.optional( element ) || /[a-zA-Z0-9_\.\-\+]+@[a-zA-Z0-9\-]+\.[a-zA-Z0-9]+/.test( value );
    };
    
    $.validator.addMethod(
        "passwordCheck",
        function(value, element) {
            return this.optional(element) || /(?=.*\d)(?=.*[a-z])(?=.*[A-Z])(?=.*[!@#$%^&amp;*()_+}{&quot;:;'?/&gt;.&lt;,])(?!.*\s).*$/gm.test(value);
    }, );

    $.validator.addMethod("noSpace", function(value, element) { 
      return value.indexOf(" ") < 0 && value != ""; 
    }, "No space allowed.");

	//Registration form validation
    $("#my_account").validate({
       normalizer: function(value) {
            // Trim the value of every element
            return $.trim(value);
        },
        rules: {
            sap_user_name: "required",
            sap_user_password: {
                required: false,
                minlength: 8,
                normalizer: function(value) {
                    return $.trim(value);
                },
                passwordCheck: true
            },
            sap_user_repassword: {
                required: false,
                minlength: 8,
                equalTo: "#sap_user_password"
            },
            sap_user_email: {
                required: true,
                email: true
            }
        },
        messages: {
            sap_user_name: "Please enter your name",
            sap_user_password: {
                required: "Please provide a password",
                minlength: "Your password must be at least 8 characters long",
                passwordCheck: "Password should be 8 characters long as well as it should contain the capital , lower case letters, at least one digit and one special character (1-9, !, *, _, etc.).",
            },
            sap_user_repassword: {
                required: "Please provide a password",
                minlength: "Your password must be at least 8 characters long",
                equalTo: "Please enter the same password as above",
            },
            sap_user_email: {
                email: "Please enter a valid email address",
            },
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
    });

    //Registration form validation
    $("#reset_password").validate({
        rules: {
            password: {
                required: true,
                minlength: 8,
                normalizer: function(value) {
                    return $.trim(value);
                },
                passwordCheck: true,
                noSpace: true,
            },
            confirm_password: {
                required: true,
                minlength: 8,
                equalTo: "#password",
                noSpace: true,
            },
        },
        messages: {
            password: {
                required: "Please provide a new password",
                minlength: "Your password must be at least 8 characters long",
                passwordCheck: "Password should be 8 characters long as well as it should contain the capital , lower case letters, at least one digit and one special character (1-9, !, *, _, etc.).",
            },
            confirm_password: {
                required: "Please provide a re-enter new password",
                minlength: "Your password must be at least 8 characters long",
                equalTo: "Please enter the same password as above"
            },
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
    });
});