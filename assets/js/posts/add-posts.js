'use strict';

var fileTypes = ['jpg', 'jpeg', 'png', 'gif'];  //acceptable file types

function readURL(input) {
    if (input.files && input.files[0]) {
        var extension = input.files[0].name.split('.').pop().toLowerCase(), //file extension from input file
                isSuccess = fileTypes.indexOf(extension) > -1;  //is extension in acceptable types

        if (isSuccess) { //yes
            var reader = new FileReader();
            reader.onload = function (e) {
                $('#img-upload').attr('src', e.target.result);
            }

            reader.readAsDataURL(input.files[0]);
        } else {
            $(".input-group input").val('');
        }
    }
}
// function to validate url
function sap_is_url_valid( url ) {
    return /^(https?|s?ftp):\/\/(((([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(%[\da-f]{2})|[!\$&'\(\)\*\+,;=]|:)*@)?(((\d|[1-9]\d|1\d\d|2[0-4]\d|25[0-5])\.(\d|[1-9]\d|1\d\d|2[0-4]\d|25[0-5])\.(\d|[1-9]\d|1\d\d|2[0-4]\d|25[0-5])\.(\d|[1-9]\d|1\d\d|2[0-4]\d|25[0-5]))|((([a-z]|\d|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(([a-z]|\d|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])*([a-z]|\d|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])))\.)+(([a-z]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(([a-z]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])*([a-z]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])))\.?)(:\d*)?)(\/((([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(%[\da-f]{2})|[!\$&'\(\)\*\+,;=]|:|@)+(\/(([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(%[\da-f]{2})|[!\$&'\(\)\*\+,;=]|:|@)*)*)?)?(\?((([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(%[\da-f]{2})|[!\$&'\(\)\*\+,;=]|:|@)|[\uE000-\uF8FF]|\/|\?)*)?(#((([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(%[\da-f]{2})|[!\$&'\(\)\*\+,;=]|:|@)|\/|\?)*)?$/i.test(url);
}

$(document).ready(function () {

	$('[data-toggle="popover"]').popover();

    $(document).on('change',"#imgInput",function () {
        readURL(this);
    });
    $(".sap_select").select2();

    $('#checkbox1').iCheck();
    
    $('.sap-post-social-list ul').find('li:first').addClass('active');
    $('.sap-post-social-list-content').find('div:first').addClass('active');

	$(document).on( 'change', '.sap-valid-url', function() {
		var website_url = $(this).val();
		if( website_url != '' && !sap_is_url_valid( website_url ) ) {
			websitecontent = $(this).addClass('sap-not-rec').focus();
			$(this).parent().find('.sap-share-link-err').remove();
			$(this).parent().append('<span class="sap-share-link-err">Please enter valid url (i.e. http://www.example.com).</span>');
			$(this).parent().find('.sap-share-link-err').show();
			$('html, body').animate({ scrollTop: websitecontent.offset().top - 50 }, 500);
			$(this).val('');
			return false;
		}else{
			$(this).parent().find('.sap-share-link-err').hide();
			$(this).removeClass('sap-not-rec');
			return true;
		}
	});

    $(document).on('click', '.reset_post_status', function () {
        var obj = $(this);
        var post_id = $(this).attr('aria-data-id');
        var metaname = $(this).attr('aria-label');
        $(this).attr('disabled', 'disabled');
        $.ajax({
            type: 'POST',
            url: SAP_SITE_URL+'/posts/reset_post_status/',
            data: {post_id: post_id, meta_key: metaname},
            success: function (result) {
                var result = jQuery.parseJSON(result);
                if (result.status){
                    $('.'+ metaname + '_lbl').html('Unpublished');
                    $(obj).remove();
                    if (result.is_display_schedule === "false") {
                        $(".edit-multi-post-schedule").hide();
                    }else{
                        $(".edit-multi-post-schedule").show();
                    }
                }
            }
        });
    });

    //Social Images remove when click on removed button
    $(document).on( 'click', '.file-caption-main .fileinput-remove-button', function(){
      	$(this).parent().parent().parent().parent().find('.sap-default-img').val('');
    });
});
