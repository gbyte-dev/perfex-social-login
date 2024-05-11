'use strict';

jQuery(document).ready(function ($) {

    $(".sap_select").select2();

    $(".sap_timezone").select2();
    $(document).on('change', '#schedule_wallpost_option', function () {
        var schedule = $(this).val();

        $('.sap-enable-random-posting-wrap,.sap-schedule-wallpost-time-wrap').hide();
        if (schedule == 'daily') {

            $('.sap-enable-random-posting-wrap,.sap-schedule-wallpost-time-wrap').show();
        }

        if ($('#sap-random-posting').is(':checked')) {
            $('.sap-schedule-wallpost-time-wrap').hide();
        }

        // Show / hide schedule limit option
        $('.sap-schedule-limit').show();
        if (schedule == '') {
            $('.sap-schedule-limit').hide();
        }

    });

    // Posting type radio button
    $(document).on('click', '.sap-popsting-type', function () {

        if ($(this).val() == 1) {
            $('.sap-schedule-wallpost-time-wrap').hide();
        } else {
            $('.sap-schedule-wallpost-time-wrap').show();
        }
    });

    //add more accounts for pinterest
    jQuery(document).on('click', '.sap-pinterest-more-account', function () {

        var jQueryfirst = jQuery(this).parents('#app-method-wrap').find('.sap-pinterest-account-details:last');
        var last_row_id = parseInt(jQueryfirst.attr("data-row-id"));
        last_row_id = last_row_id + 1;
        
        var clone_row = jQueryfirst.clone();
        clone_row.insertAfter(jQueryfirst).show();

        jQuery(this).parents('#app-method-wrap').find('.sap-pinterest-account-details:last .sap-pinterest-app-id').attr('name', 'sap_pinterest_options[pinterest_keys][' + last_row_id + '][app_id]').val('');
        jQuery(this).parents('#app-method-wrap').find('.sap-pinterest-account-details:last .sap-pinterest-app-secret').attr('name', 'sap_pinterest_options[pinterest_keys][' + last_row_id + '][app_secret]').val('');
        jQuery(this).parents('#app-method-wrap').find('.sap-pinterest-account-details:last .sap-pinterest-remove').show();
        jQuery(this).parents('#app-method-wrap').find('.sap-pinterest-account-details:last .sap-pinterest-main').addClass("show-remove-icon");
        jQuery(this).parents('#app-method-wrap').find('.sap-pinterest-account-details:last').attr('data-row-id', last_row_id);
        jQuery(this).parents('#app-method-wrap').find('.sap-pinterest-account-details:last').find('.col-sm-6').removeClass('has-error');
        jQuery(this).parents('#app-method-wrap').find('.sap-pinterest-account-details:last .fb-oauth-url').val('');
        jQuery(this).parents('#app-method-wrap').find('.sap-pinterest-account-details:last .fb-oauth-url').addClass('add-another-fb');
        jQuery(this).parents('#app-method-wrap').find('.sap-pinterest-account-details:last .copy-clipboard').remove();        
        jQuery(this).parents('#app-method-wrap').find('.sap-pinterest-account-details:last .pinterest-grant-permission').hide();
        jQuery(this).parents('#app-method-wrap').find('.sap-pinterest-account-details:last .pinterest-reset-permission').hide(); 

        return false;

    });    

    //add more account details for...
    jQuery(document).on('click', '.sap-fb-more-account', function () {

        
        //var jQueryfirst = jQuery(this).parents('.sap-api-facebook-settings').find('.sap-facebook-account-details:last');
        var jQueryfirst = jQuery(this).parents('#facebook-graph-api').find('.sap-facebook-account-details:last');
        var last_row_id = parseInt(jQueryfirst.attr("data-row-id"));
        last_row_id = last_row_id + 1;
        
        var clone_row = jQueryfirst.clone();

        clone_row.insertAfter(jQueryfirst).show();
        clone_row.find('.sap-grant-reset-data').html('');

        jQuery(this).parents('#facebook-graph-api').find('.sap-facebook-account-details:last .sap-facebook-app-id').attr('name', 'sap_facebook_options[facebook_keys][' + last_row_id + '][app_id]').val('');
        jQuery(this).parents('#facebook-graph-api').find('.sap-facebook-account-details:last .sap-facebook-app-secret').attr('name', 'sap_facebook_options[facebook_keys][' + last_row_id + '][app_secret]').val('');
        jQuery(this).parents('#facebook-graph-api').find('.sap-facebook-account-details:last .sap-facebook-remove').show();
        jQuery(this).parents('#facebook-graph-api').find('.sap-facebook-account-details:last .sap-facebook-main').addClass("show-remove-icon");
        jQuery(this).parents('#facebook-graph-api').find('.sap-facebook-account-details:last').attr('data-row-id', last_row_id);
        jQuery(this).parents('#facebook-graph-api').find('.sap-facebook-account-details:last').find('.col-sm-6').removeClass('has-error');
        jQuery(this).parents('#facebook-graph-api').find('.sap-facebook-account-details:last .fb-oauth-url').val('');
        jQuery(this).parents('#facebook-graph-api').find('.sap-facebook-account-details:last .fb-oauth-url').addClass('add-another-fb');
        jQuery(this).parents('#facebook-graph-api').find('.sap-facebook-account-details:last .copy-clipboard').remove();       
        return false;
    });

    $(document).on('change', 'input[name="sap_facebook_options[enable_proxy]"]', 
        function(){
            if(this.checked){
                $('#facebook-proxy').show();
            } else {
               $('#facebook-proxy').hide();
            }
        }
    );

    $(document).on('change', 'input[name="sap_facebook_options[facebook_auth_options]"]', 
        function(){
            if( $(this).val() == 'graph'){
                $('#facebook-graph-api').show();
                $('#facebook-app-method').hide();
            } else if( $(this).val() == 'appmethod' ){
                $('#facebook-app-method').show();
                $('#facebook-graph-api').hide();
            }
        }
    );

    $(document).on('change', 'input[name="sap_linkedin_options[linkedin_auth_options]"]', 
        function(){
            if( $(this).val() == 'graph'){
                $('#linkedin-graph-api').show();
                $('#linkedin-app-method').hide();
            } else if( $(this).val() == 'appmethod' ){
                $('#linkedin-app-method').show();
                $('#linkedin-graph-api').hide();
            }
        }
    );

    if ( $('.app-data').length == 0 ){
        $('.pinterest-listing .form-head').hide();
    } else {
        $('.pinterest-listing .form-head').show();
    }

    $(document).on('change', 'input[name="sap_pinterest_options[enable_proxy]"]', 
        function(){
            if(this.checked){
                $('#pinterest-proxy').show();
            } else {
               $('#pinterest-proxy').hide();
            }
        }
    );

    $(document).on('change', 'input[name="sap_pinterest_options[pin_auth_options]"]', 
    function(){

        if( $(this).val() == 'app'){
            $('#app-method-wrap').show();
            $('#cookie-method-wrap').hide();
            $('.app-data').show();
            $('.cookie-data').hide();
            if ( $('.app-data').length == 0 ){
                $('.pinterest-listing .form-head').hide();
            } else {
                $('.pinterest-listing .form-head').show();
            }
          
        } else if( $(this).val() == 'cookie' ){
          
            $('#cookie-method-wrap').show();
            $('#app-method-wrap').hide();
            $('.app-data').hide();
            $('.cookie-data').show();

            if ( $('.cookie-data').length == 0 ){
                $('.pinterest-listing .form-head').hide();
            } else {
                $('.pinterest-listing .form-head').show();
            }  
        }
    }
   );

    //add more account details for youtube...
    jQuery(document).on('click', '.sap-add-more-youtube-account', function () {

        var jQueryfirst = jQuery(this).parents('.sap-api-youtube-settings').find('.sap-youtube-account-details:last');
        var last_row_id = parseInt(jQueryfirst.attr('data-row-id'));
        last_row_id = last_row_id + 1;

        var clone_row = jQueryfirst.clone();

        clone_row.insertAfter(jQueryfirst).show();
        // clone_row.find('.sap-grant-reset-data').html('');
        
        jQuery(this).parents('.sap-api-youtube-settings').find('.sap-youtube-account-details:last .sap-youtube-consumer-key').attr('name', 'sap_youtube_options[youtube_keys][' + last_row_id + '][consumer_key]').val('');
        jQuery(this).parents('.sap-api-youtube-settings').find('.sap-youtube-account-details:last .sap-youtube-consumer-secret').attr('name', 'sap_youtube_options[youtube_keys][' + last_row_id + '][consumer_secret]').val('');
        jQuery(this).parents('.sap-api-youtube-settings').find('.sap-youtube-account-details:last .youtube-oauth-token').val('');
        jQuery(this).parents('.sap-api-youtube-settings').find('.sap-youtube-account-details:last .youtube-oauth-token').hide();
        jQuery(this).parents('.sap-api-youtube-settings').find('.sap-youtube-account-details:last .sap-grant-reset-data').hide();
        // jQuery(this).parents('.sap-api-youtube-settings').find('.sap-youtube-account-details:last .reset-user-permission').hide();
        jQuery(this).parents('.sap-api-youtube-settings').find('.sap-youtube-account-details:last .sap-youtube-oauth-token').attr('name', 'sap_youtube_options[youtube_keys][' + last_row_id + '][oauth_token]').val('');
        jQuery(this).parents('.sap-api-youtube-settings').find('.sap-youtube-account-details:last .sap-youtube-oauth-secret').attr('name', 'sap_youtube_options[youtube_keys][' + last_row_id + '][oauth_secret]').val('');
        jQuery(this).parents('.sap-api-youtube-settings').find('.sap-youtube-account-details:last .sap-youtube-main').addClass("show-remove-icon");
        jQuery(this).parents('.sap-api-youtube-settings').find('.sap-youtube-account-details:last').attr('data-row-id', last_row_id);
        jQuery(this).parents('.sap-api-youtube-settings').find('.sap-youtube-account-details:last').find('.col-sm-6').removeClass('has-error');
        jQuery(this).parents('.sap-api-youtube-settings').find('.sap-youtube-account-details:last .copy-clipboard').remove();
        return false;
    });

    //delete account details for youtube
    jQuery(document).on('click', '.sap-youtube-remove', function () {

        var jQueryparent = jQuery(this).parents('.sap-youtube-account-details');
        jQueryparent.remove();

        return false;
    });


    //add more account details for twitter...
    jQuery(document).on('click', '.sap-add-more-twitter-account', function () {

        var jQueryfirst = jQuery(this).parents('.sap-api-twitter-settings').find('.sap-twitter-account-details:last');
        var last_row_id = parseInt(jQueryfirst.attr('data-row-id'));
        last_row_id = last_row_id + 1;

        var clone_row = jQueryfirst.clone();

        clone_row.insertAfter(jQueryfirst).show();
        // clone_row.find('.sap-grant-reset-data').html('');

        jQuery(this).parents('.sap-api-twitter-settings').find('.sap-twitter-account-details:last .sap-twitter-consumer-key').attr('name', 'sap_twitter_options[twitter_keys][' + last_row_id + '][consumer_key]').val('');
        jQuery(this).parents('.sap-api-twitter-settings').find('.sap-twitter-account-details:last .sap-twitter-consumer-secret').attr('name', 'sap_twitter_options[twitter_keys][' + last_row_id + '][consumer_secret]').val('');
        jQuery(this).parents('.sap-api-twitter-settings').find('.sap-twitter-account-details:last .sap-twitter-oauth-token').attr('name', 'sap_twitter_options[twitter_keys][' + last_row_id + '][oauth_token]').val('');
        jQuery(this).parents('.sap-api-twitter-settings').find('.sap-twitter-account-details:last .sap-twitter-oauth-secret').attr('name', 'sap_twitter_options[twitter_keys][' + last_row_id + '][oauth_secret]').val('');
        jQuery(this).parents('.sap-api-twitter-settings').find('.sap-twitter-account-details:last .sap-twitter-main').addClass("show-remove-icon");
        jQuery(this).parents('.sap-api-twitter-settings').find('.sap-twitter-account-details:last').attr('data-row-id', last_row_id);
        jQuery(this).parents('.sap-api-twitter-settings').find('.sap-twitter-account-details:last').find('.col-sm-6').removeClass('has-error');
        return false;
    });

    //delete account details for pinterest
    jQuery(document).on('click', '.sap-pinterest-remove', function () {

        var jQueryparent = jQuery(this).parents('.sap-pinterest-account-details');
        jQueryparent.remove();

        return false;
    });

    //delete account details for facebook
    jQuery(document).on('click', '.sap-facebook-remove', function () {

        var jQueryparent = jQuery(this).parents('.sap-facebook-account-details');
        jQueryparent.remove();

        return false;
    });

    //delete account details for twitter
    jQuery(document).on('click', '.sap-twitter-remove', function () {

        var jQueryparent = jQuery(this).parents('.sap-twitter-account-details');
        jQueryparent.remove();

        return false;
    });

    //delete account details for twitter
    jQuery(document).on('click', '.sap-setting-remove-img', function () {

    	var hide_class = jQuery(this).attr('data-preview');
		var show_class = jQuery(this).attr('data-upload_img');
		var show_class = jQuery(this).attr('data-upload_img');

		jQuery(this).parent().parent().parent().find('.uploaded_img').val('');
		jQuery(hide_class).hide();
		jQuery(show_class).show();
        return false;
    });

     
    //add more account details for linkedin
    jQuery(document).on('click', '.sap-add-more-li-account', function () {

        var jQueryfirst = jQuery(this).parents('.sap-api-linkedin-settings').find('.sap-linkedin-account-details:last');
        var last_row_id = parseInt(jQueryfirst.attr('data-row-id'));
        last_row_id = last_row_id + 1;

        var clone_row = jQueryfirst.clone();

        clone_row.insertAfter(jQueryfirst).show();
        clone_row.find('.sap-grant-reset-data').html('');

        jQuery(this).parents('.sap-api-linkedin-settings').find('.sap-linkedin-account-details:last .sap-linkedin-app-id').attr('name', 'sap_linkedin_options[linkedin_keys][' + last_row_id + '][app_id]').val('');
        jQuery(this).parents('.sap-api-linkedin-settings').find('.sap-linkedin-account-details:last .sap-linkedin-app-secret').attr('name', 'sap_linkedin_options[linkedin_keys][' + last_row_id + '][app_secret]').val('');
        jQuery(this).parents('.sap-api-linkedin-settings').find('.sap-linkedin-account-details:last .li-oauth-url').val('');
        jQuery(this).parents('.sap-api-linkedin-settings').find('.sap-linkedin-account-details:last .copy-clipboard').remove();        
        jQuery(this).parents('.sap-api-linkedin-settings').find('.sap-linkedin-account-details:last .sap-linkedin-remove').show();
        jQuery(this).parents('.sap-api-linkedin-settings').find('.sap-linkedin-account-details:last .sap-linkedin-main').addClass("show-remove-icon");
        jQuery(this).parents('.sap-api-linkedin-settings').find('.sap-linkedin-account-details:last').attr('data-row-id', last_row_id);
        return false;
    });


    //add more account details for blogger
    jQuery(document).on('click', '.sap-add-more-blogger-account', function () {

        var jQueryfirst = jQuery(this).parents('.sap-api-blogger-autopost').find('.sap-blogger-url-details:last');
        var last_row_id = parseInt(jQueryfirst.attr('data-row-id'));
        last_row_id = last_row_id + 1;

        var clone_row = jQueryfirst.clone();

        clone_row.insertAfter(jQueryfirst).show();
        clone_row.find('.control-label').html('');

        jQuery(this).parents('.sap-api-blogger-autopost').find('.sap-blogger-url-details:last .sap-blogger-url').val('');      
        jQuery(this).parents('.sap-api-blogger-autopost').find('.sap-blogger-url-details:last .sap-blogger-remove').show();
        jQuery(this).parents('.sap-api-blogger-autopost').find('.sap-blogger-url-details:last .sap-blogger-main').addClass("show-remove-icon");
        jQuery(this).parents('.sap-api-blogger-autopost').find('.sap-blogger-url-details:last').attr('data-row-id', last_row_id);

        return false;
    });

    //delete account details for linkedin
    jQuery(document).on('click', '.sap-linkedin-remove', function () {

        var jQueryparent = jQuery(this).parents('.sap-linkedin-account-details');
        jQueryparent.remove();

        return false;
    });

    //delete account details for blogger
    jQuery(document).on('click', '.sap-blogger-remove', function () {

        var jQueryparent = jQuery(this).parents('.sap-blogger-url-details');
        jQueryparent.remove();

        return false;
    });

    //Add more account details for Tumblr account
    jQuery(document).on('click', '.sap-add-more-tumblr-account', function () {

        var jQueryfirst = jQuery(this).parents('.sap-api-tumblr-settings').find('.sap-tumblr-account-details:last'); 
        var last_row_id = parseInt(jQueryfirst.attr('data-row-id'));
        last_row_id = last_row_id + 1;

        var clone_row = jQueryfirst.clone();

        clone_row.insertAfter(jQueryfirst).show();
        clone_row.find('.sap-grant-reset-data').html('');

        jQuery(this).parents('.sap-api-tumblr-settings').find('.sap-tumblr-account-details:last .sap-tumblr-consumer-key').attr('name', 'sap_tumblr_options[tumblr_keys][' + last_row_id + '][tumblr_consumer_key]').val('');
        jQuery(this).parents('.sap-api-tumblr-settings').find('.sap-tumblr-account-details:last .sap-tumblr-secret-key').attr('name', 'sap_tumblr_options[tumblr_keys][' + last_row_id + '][tumblr_consumer_secret]').val('');
        jQuery(this).parents('.sap-api-tumblr-settings').find('.sap-tumblr-account-details:last .sap-tumblr-remove').show();
        jQuery(this).parents('.sap-api-tumblr-settings').find('.sap-tumblr-account-details:last .tumblr-grant-permission').hide();
        jQuery(this).parents('.sap-api-tumblr-settings').find('.sap-tumblr-account-details:last .reset-user-permission').hide();
        jQuery(this).parents('.sap-api-tumblr-settings').find('.sap-tumblr-account-details:last .sap-tumblr-main').addClass("show-remove-icon");
        jQuery(this).parents('.sap-api-tumblr-settings').find('.sap-tumblr-account-details:last').attr('data-row-id', last_row_id);

        return false;

    });    

    //delete account details for linkedin
    jQuery(document).on('click', '.sap-tumblr-remove', function () {

        var jQueryparent = jQuery(this).parents('.sap-tumblr-account-details');
        jQueryparent.remove();

        return false;
    });

    // copy Valid oauth url to clipboard
    jQuery( document).on('click', '.copy-clipboard', function(){
        var app_id = jQuery(this).attr('data-appid');
        var inputID = jQuery(this).attr('data-inputID');
        var copy_board = jQuery(inputID+app_id);
        var oauth_url = copy_board.val();

        if( oauth_url != ""){
            copy_board.select();
            document.execCommand("copy");
            jQuery( this ).parent().append( '<div class="sap-fade-message">Copied</div>' );
            jQuery( ".sap-fade-message" ).fadeOut( 3000, function() {
                jQuery( '.sap-fade-message' ).remove();
            });
        }
    });

    $('form#youtube-settings').on('submit', function(event) {
        $('.sap-youtube-consumer-key,.sap-youtube-consumer-secret').each(function() {
            $(this).rules("add", {
              required: true
            })
        });
    });

    $('form#twiiter-settings').on('submit', function(event) {
     	$('.sap-twitter-consumer-key,.sap-twitter-consumer-secret,.sap-twitter-oauth-token,.sap-twitter-oauth-secret').each(function() {
            $(this).rules("add", {
              required: true
            })
        });
    });

    $('form#facebook-settings').on('submit', function(event) {
     	$('.sap-facebook-app-id,.sap-facebook-app-secret').each(function() {
            $(this).rules("add", {
              required: true
            })
        });
    });

    $('form#pinterest-settings').on('submit', function(event) {
        $('.sap-pinterest-app-id,.sap-pinterest-app-secret').each(function() {
           $(this).rules("add", {
             required: true
           })
       });
   });


    
   $('form#linkedin-settings').on('submit', function(event) {
     	$('.sap-linkedin-app-id,.sap-linkedin-app-secret').each(function() {
            $(this).rules("add", {
              required: true
            })
        });
    });

    $('form#tumblr-settings').on('submit', function(event) {
     	$('.sap-tumblr-consumer-key,.sap-tumblr-secret-key').each(function() {
            $(this).rules("add", {
              required: true
            })
        });
    });

    /*$('form#blogger-settings').on('submit', function(event) {
        $('.sap-blogger-url').each(function() {
            $(this).rules("add", {
                required: true,
                url: true,
            })
        });
    });

     $('form#blogger-settings').validate({
        errorElement: "em",errorPlacement: function (error, element) {error.addClass("help-block");element.parents(".col-sm-6").addClass("has-error");},
        success: function (label, element) {},
        highlight: function (element, errorClass, validClass) {$(element).parents(".col-sm-6").addClass("has-error").removeClass("has-success");},
        unhighlight: function (element, errorClass, validClass) {$(element).parents(".col-sm-6").removeClass("has-error");},
        submitHandler: function(form) {$('form#blogger-settings').find(':submit').prop("disabled", true);form.submit();},
    });*/

    // $('form#facebook-settings').validate({
    // 	errorElement: "em",
    //     errorPlacement: function (error, element) {error.addClass("help-block");element.parents(".col-sm-6").addClass("has-error");},
    //     success: function (label, element) {},
    //     highlight: function (element, errorClass, validClass) {$(element).parents(".col-sm-6").addClass("has-error").removeClass("has-success");},
    //     unhighlight: function (element, errorClass, validClass) {$(element).parents(".col-sm-6").removeClass("has-error");},
    //     submitHandler: function(form) {$('form#facebook-settings').find(':submit').prop("disabled", true);form.submit();},
    // });

   
   

    // $('form#pinterest-settings').validate({errorElement: "em",errorPlacement: function (error, element) {error.addClass("help-block");element.parents(".col-sm-6").addClass("has-error");},
    //     success: function (label, element) {},
    //     highlight: function (element, errorClass, validClass) {$(element).parents(".col-sm-6").addClass("has-error").removeClass("has-success");},
    //     unhighlight: function (element, errorClass, validClass) {$(element).parents(".col-sm-6").removeClass("has-error");},
    //     submitHandler: function(form) {$('form#pinterest-settings').find(':submit').prop("disabled", true);form.submit();},
    // });

    // $('form#linkedin-settings').validate({
    // 	errorElement: "em",errorPlacement: function (error, element) {error.addClass("help-block");element.parents(".col-sm-6").addClass("has-error");},
    //     success: function (label, element) {},
    //     highlight: function (element, errorClass, validClass) {$(element).parents(".col-sm-3").addClass("has-error").removeClass("has-success");},
    //     unhighlight: function (element, errorClass, validClass) {$(element).parents(".col-sm-3").removeClass("has-error");},
    //     submitHandler: function(form) {$('form#linkedin-settings').find(':submit').prop("disabled", true);form.submit();},
    // });
    
    // $('form#tumblr-settings').validate({
    // 	errorElement: "em",errorPlacement: function (error, element) {error.addClass("help-block");element.parents(".col-sm-6").addClass("has-error");},
    //     success: function (label, element) {},
    //     highlight: function (element, errorClass, validClass) {$(element).parents(".col-sm-4").addClass("has-error").removeClass("has-success");},
    //     unhighlight: function (element, errorClass, validClass) {$(element).parents(".col-sm-4").removeClass("has-error");},
    //     submitHandler: function(form) {$('form#tumblr-settings').find(':submit').prop("disabled", true);form.submit();},
    // });

});