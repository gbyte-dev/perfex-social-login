'use strict';

$(document).ready(function ($) {
    $(".description.update_msg").hide();
    $.validator.setDefaults({
        submitHandler: function (form) {
            if ($(form).valid())
                form.submit();
            return false; // prevent normal form posting
        }
    });
    $.validator.methods.email = function (value, element) {
        return this.optional(element) || /[a-zA-Z0-9_\.\-\+]+@[a-zA-Z0-9\-]+\.[a-zA-Z0-9]+/.test(value);
    };
    
    //Registration form validation
    $("#sap_license_form").validate({

        rules: {
            sap_license_key: {
                required: true,
                normalizer: function (value) {
                    return $.trim(value);
                }
            },
            sap_license_email: {
                required: true,
                email: true
            }
        },
        messages: {
            sap_license_key: "Please enter your license key",
            sap_license_email: {
                required: "Please enter your email address",
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

            // Add the span element, if doesn't exists, and apply the icon classes to it.

        },
        success: function (label, element) {
        },
        highlight: function (element, errorClass, validClass) {
            $(element).parents(".form-group").addClass("has-error").removeClass("has-success");
        },
        unhighlight: function (element, errorClass, validClass) {
            $(element).parents(".form-group").removeClass("has-error");
        }
    });

    //Check Update and handle SAP Script
    $(".update_loader").css('display', 'none');
    $(document).on('click', '.sap-check-for-update', function () {
        $(".description.update_msg").show();
        var object = $(this).prop("disabled", true);
        $(".update_loader").css('display', 'block');
        $.ajax({
            type: 'POST',
            url: SAP_SITE_URL + '/mingle-update/version_updating/',
            data: {update_type: 'update'},
            async: true,
            cache: false,
            /* timeout: 20000, */
            success: function (response) {
                var response = $.parseJSON(response);
                if (response.success) {
                    $('.sap-response').find('.alert').removeClass('alert-error').addClass('alert-success').html(response.success).show();
                    $.ajax({
                        type: 'POST',
                        url: SAP_SITE_URL + '/mingle-update/version_compress/',
                        data: {update_type: 'update', 'filename': response.filename },
                        async: true,
                        cache: false,
                        /* timeout: 20000, */
                        success: function (response) {
                            var response = $.parseJSON(response);
                            if (response.success) {
                                $('.sap-response').find('.alert').removeClass('alert-error').addClass('alert-success').html(response.success).show();
                                object.prop("disabled", false);
                            } else if (response.error) {
                                $('.sap-response').find('.alert').removeClass('alert-success').addClass('alert-error').html(response.error).show();
                                object.prop("disabled", false);
                            }
                            $(".update_loader").css('display', 'none');
                            location.reload(true);
                        },
                    });
                } else if (response.error) {
                    $(".update_loader").css('display', 'none');
                    $('.sap-response').find('.alert').removeClass('alert-success').addClass('alert-error').html(response.error).show();
                    object.prop("disabled", false);
                }
            },
            complete: function() {
                $(".update_loader").css('display', 'none');
            },
            error: function (jqXHR, exception) {
                $(".update_loader").css('display', 'none');
                var msg = '';
                if (jqXHR.status === 0) {
                    msg = 'Not connect.\n Verify Network.';
                } else if (jqXHR.status == 404) {
                    msg = 'Requested page not found. [404]';
                } else if (jqXHR.status == 500) {
                    msg = 'Internal Server Error [500].';
                } else if (exception === 'parsererror') {
                    msg = 'Requested JSON parse failed.';
                } else if (exception === 'timeout') {
                    msg = 'Time out error.';
                } else if (exception === 'abort') {
                    msg = 'Ajax request aborted.';
                } else {
                    msg = 'Uncaught Error.\n' + jqXHR.responseText;
                }
                $('.sap-response').find('.alert').removeClass('alert-success').addClass('alert-error').html(msg).show();
                object.prop("disabled", false);
            },
        });
    });

});