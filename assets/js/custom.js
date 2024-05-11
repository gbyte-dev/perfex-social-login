'use strict';
$(document).ready(function () {

	if($('.datepicker').length > 0){
		$( ".datepicker" ).datepicker({
			dateFormat: 'yy-mm-dd',
		  	changeMonth: true,
		  	changeYear: true
		});	
	}

	/* Code to display plan section on role select in add user page */
	$('.sap_role').on( 'change', function (e) {
		if ( this.value == 'superadmin' ) {
			$('.sap_plan').hide();
		} else {
			$('.sap_plan').show();
		}    
	});

	/* Code to display plan section on role select in edit user page */
	var admin = $('.sap_role option:selected').val();
	if ( admin=='superadmin' ) {
		$('.sap_plan').hide();
	} else {
		$('.sap_plan').show();
	}

	/* Custom code to for select all and deselect functionality - QuickPost */
	$(document).on( 'click','.quickpost-select-all', function (e) {
		$(this).closest('table').find('td input:checkbox').prop('checked', this.checked);
	} );

	$(document).on( 'click','.multipost-select-all',function (e) {
		$(this).closest('table').find('td input:checkbox').prop('checked', this.checked);
	} );

	$(document).on( 'click','.logs-select-all', function (e) {
		$(this).closest('table').find('td input:checkbox').prop('checked', this.checked);
	} );
});

// equalheight
var equalheight;
equalheight = function ( container) {

	var currentTallest = 0,
	currentRowStart = 0,
	rowDivs = new Array(),
	$el,
	topPosition = 0;
	$(container).each(function () {

		$el = $(this);
		$($el).height('auto')
		topPostion = $el.position().top;

		if (currentRowStart != topPostion) {
			for (currentDiv = 0; currentDiv < rowDivs.length; currentDiv++) {
				rowDivs[currentDiv].height(currentTallest);
			}
			rowDivs.length = 0; // empty the array
			currentRowStart = topPostion;
			currentTallest = $el.height();
			rowDivs.push($el);
		} else {
			rowDivs.push($el);
			currentTallest = (currentTallest < $el.height()) ? ($el.height()) : (currentTallest);
		}
		for (currentDiv = 0; currentDiv < rowDivs.length; currentDiv++) {
			rowDivs[currentDiv].height(currentTallest);
		}
	});
}

$(window).ready(function () {
	equalheight('.custom-user-list-init li');
});

$(window).resize(function () {
	equalheight('.custom-user-list-init li');
});

// File select
$(function () {

	// We can attach the `fileselect` event to all file inputs on the page
	$(document).on('change', ':file', function () {
		var input = $(this),
		numFiles = input.get(0).files ? input.get(0).files.length : 1,
		label = input.val().replace(/\\/g, '/').replace(/.*\//, '');
		input.trigger('fileselect', [numFiles, label]);
	});

	$('#sap_yt_video').on('change', function() {
        // Get the file name
        var fileName = $(this).val().split('\\').pop();
        
        // Update hidden input value
        $('#uploaded_video').val(fileName);
    });

	// Setup time
	if ($('.sap-datetime').length) {
		var b = moment.tz(SapTimeZone).format('YYYY-MM-DD HH:mm');

		jQuery('.sap-datetime').datetimepicker({
			format: 'yyyy-mm-dd hh:ii',
			pickerPosition: 'top-left',
			minuteStep: 30,
			timeZone: SapTimeZone,
			startDate: b,
			useLocalTimezone: false,
			autoclose: true,
			clearBtn: true,
		});
	}

	/**
	**/
	$(document).on('click', '.tgl-btn.float-right-cs-init', function () {
		$(this).parents('.box-title').find("a").click();
	});

	// Hide video Wrap
	$(".quick-video-wrap").hide();

	// Check quick post video
	if( $("#quick-post-video").length > 0 ) {
		$("#quick-post-video").fileinput({        
			maxFileSize: 1024000,
			allowedFileExtensions:['mp4','mkv','mov'],
			showPreview:true
		});
	}	

	// Check quick post image
	if( $("#quick-post-image").length > 0 ) {
		$("#quick-post-image").fileinput({        
			maxFileSize: 1024000,
			allowedFileExtensions:['jpg','png','gif'],
			showPreview:true
		});
	}	
		
	$('.video-label-notification').hide();
	$("#sap_posting_type option[value='video']").remove();
	$(".yt-wrap").hide();
	$(document).on('change', 'input[type=radio][name=enable_video_image]', function () {
		var selected_value = jQuery(this).val();
		if( selected_value == 'enablevideo' ){
			$(".yt-wrap").show();
			$(".pin-wrap").hide();
			$(".gmb-wrap").hide();
			$(".li-wrap").hide();
			$(".blogger-wrap").hide();
			$(".insta-wrap").hide();
			$(".quick-image-wrap").hide();
			$(".quick-video-wrap").show();
			$(".quick-video-wrap").show();
			jQuery('#quick-post-image').fileinput('reset').fileinput({showPreview: false});
			$('.video-label-notification').show();

			//Remove options from dropdown
			$("#sap_posting_type").empty();
			$('#sap_posting_type').append(new Option("Video", "video"));
		} 

		if( selected_value == 'enableimage' ){
			$(".tw-wrap").show();
			$(".pin-wrap").show();
			$(".gmb-wrap").show();
			$(".blogger-wrap").show();
			$(".li-wrap").show();
			$(".yt-wrap").hide();
			$(".quick-image-wrap").show();
			$(".quick-video-wrap").hide();
			jQuery('#quick-post-video').fileinput('reset').fileinput({showPreview: false});
			$('.video-label-notification').hide();
			$(".insta-wrap").show();
			//Remove options from dropdown
			$("#sap_posting_type").empty();
			$('#sap_posting_type').append(new Option("Text", "text"));
			$('#sap_posting_type').append(new Option("Link", "link"));
			$('#sap_posting_type').append(new Option("Photo", "photo"));
			
		}
	});	

	$(document).on('change', '.custom-accordion .tgl.tgl-ios', function () {
		var ischecked = $(this).is(':checked');
		var content_id = $(this).attr('data-content');
		if (!ischecked) {
			$(content_id).hide("slow");
		} else {
			$(content_id).show("slow");
		}
	});

	$(document).on('change', '.custom-accordion .tgl.tgl-ios', function () {
		var ischecked = $(this).is(':checked');
		var content_id = $(this).attr('data-content');
		if (!ischecked) {
			$(content_id).hide("slow");
		} else {
			$(content_id).show("slow");
		}
	});

	// We can watch for our custom `fileselect` event like this
	$(document).ready(function () {

		$(':file').on('fileselect', function (event, numFiles, label) {

			var input = $(this).parents('.input-group').find(':text'),
			log = numFiles > 1 ? numFiles + ' files selected' : label;

			if (input.length) {
				input.val(log);
			}
		});

		//Disable Button show can't post second time
		$(document).on( 'submit', 'form.add-post-form', function () {
			$('.add-new-post').prop('disabled', true);
		});

		/***/
		$(document).on('click', '.post-img-edit-pre .fileinput-remove-button', function () {
			$('.post-img-edit-pre #featured-img').val('');
			
		});

		$(document).on('click', '.logo-img-edit .fileinput-remove-button', function () {
			$('.logo-img-edit #mingle_logo_file').val('');
			
		});

		$(document).on('click', '.favicon-img-edit .fileinput-remove-button', function () {
			$('.favicon-img-edit #mingle_favicon_file').val('');
			
		});

		if ($('#sap_graph_start_date').length) {
			jQuery('#sap_graph_start_date').datepicker({
				format: 'yyyy-mm-dd',
				todayHighlight: true
			}).on('changeDate', function () {
				// set the "toDate" start to not be later than "fromDate" ends:
				$('#sap_graph_end_date').datepicker('setStartDate', new Date($(this).val()));
			});
		}

		if ($('#sap_graph_end_date').length) {
			jQuery('#sap_graph_end_date').datepicker({
				format: 'yyyy-mm-dd',
				todayHighlight: true
			}).on('changeDate', function () {
				// set the "fromDate" end to not be later than "toDate" starts:
				$('#sap_graph_start_date').datepicker('setEndDate', new Date($(this).val()));
			});
		}

		//Filtering Graph Data Process
		$(document).on('click', '.sap_graph_filter', function () {
			get_poster_logs_json_graph();
		});

		//Filtering Graph Data Process
		$(document).on('change', 'input[type=radio][name=sap_filter_type], #sap_graph_social_type', function () {

			if (this.value == 'custom') {
				$('.sap-custom-wrap').show();
			} else {
				var filter_type = $("input[type=radio][name=sap_filter_type]:checked").val();
				if (filter_type != 'custom') {
					$('.sap-custom-wrap').hide();
				}
				get_poster_logs_json_graph();
			}
		});

		//Onload logs report page only display
		if ($('#sap-logs-graph').length) {
			get_poster_logs_json_graph();
		}

		//Build Graph
		function get_poster_logs_json_graph() {

			$('.sap-loader-wrap').show();

			var social_type = start_date = end_date = '';
			var filter_type = $("input[type=radio][name=sap_filter_type]:checked").val();
			var social_type = $('#sap_graph_social_type').val();

			if (filter_type == 'custom') {
				//Filter data
				var start_date = $('#sap_graph_start_date').val();
				var end_date = $('#sap_graph_end_date').val();
			}

			var data = {
				action: 'sap_poster_logs_graph',
				social_type: social_type,
				start_date: start_date,
				end_date: end_date,
				filter_type: filter_type,
			};
			$.ajax({
				type: 'POST',
				url: SAP_SITE_URL + '/log/sap_poster_logs_graph/',
				data: data,
				success: function (response) {
                    
					var graph_data = $.parseJSON(response);
                    var graph_data_arr = [];
                    var graph_data_arr2 = [];
                    var objs = {
						"role": "annotation",
					}
					var objs2 = {
						"type": "number",
					}
                    if (graph_data) {

						google.charts.load('current', {'packages': ['corechart']});
						google.charts.setOnLoadCallback(function () {
							var total_sum_point = 0;
							for (var i = 0; i < graph_data.length; i++) {
								if(i > 0){
									for (var j = 0; j < graph_data[i].length; j++) {
								        if(j > 0){
								        	total_sum_point += graph_data[i][j]; 
								        }
								        if(graph_data[i][j] == 0){
									    	if(social_type.length == 0){
									    	   graph_data[i][j] = 0.4; 	
									    	}else{
									    		graph_data[1][0] = 'Data not found';
									    	}
									    }
								        
									}
								}else{
									for (var j = 0; j < graph_data[i].length; j++) {
								    	if(j > 0){
								    		graph_data_arr.push(graph_data[i][j]);
								    		graph_data_arr.push(objs);
								    	}else{
								    		graph_data_arr.push(graph_data[i][j]);
								    	}
								    }
								}
								
							}
							var herights = total_sum_point * 50;
							if(herights < 500){
								herights = 600;
							}else if(herights > 900){
                               herights = 900;
							}
							graph_data[0] = graph_data_arr;
							var data = google.visualization.arrayToDataTable(graph_data);
							var formatter = new google.visualization.NumberFormat({pattern: '#,###'});

							  // format number columns
							  for (var i = 1; i < data.getNumberOfColumns(); i++) {
							    formatter.format(data, i);
							  }
                            var groupWidthVal = '18%';
                            if(filter_type){
                            	if(filter_type == 'current_year' || filter_type == 'current_month'){
                            		var groupWidthVal = '12%';
                            	}else{
                            		var groupWidthVal = '18%';
                            	}
                            }
                            var colorsArray = [];
                            if(social_type == 'facebook'){
								colorsArray = ['red'];
                            }else if(social_type == 'twitter'){
								colorsArray = ['green'];
                            }else if(social_type == 'linkedin'){
								colorsArray = ['yellow'];
                            }else if(social_type == 'tumblr'){
								colorsArray = ['orange'];
                            }else if(social_type == 'pinterest'){
								colorsArray = ['blue'];
                            }else if(social_type == 'googlemybusiness'){
								colorsArray = ['purple'];
                            }else if(social_type == 'reddit'){
								colorsArray = ['pink'];
                            }else if(social_type == 'instagram'){
								colorsArray = ['brown'];
                            }else if(social_type == 'blogger'){
								colorsArray = ['gray'];
                            }else{
                            	colorsArray = ['red', 'green','yellow','orange','blue','purple','pink','brown','gray'];
                            }
                            var options = {
								title: 'Social Network Statistics',
								titlePosition: 'center',
								isStacked:true,
								bar: { groupWidth: groupWidthVal },
								height: herights,
								legend: {position: 'right'},
								vAxis: {
									textPosition: 'none',
								},
								colors: colorsArray,
								annotations: {
							      textStyle: {
							        fontSize: 10
							      }
							    },
								
							}
							var chart = new google.visualization.ColumnChart(document.getElementById('sap-logs-graph'));
							chart.draw(data, options);
						});
					} else {
						alert('no data available');
					}
					$('.sap-loader-wrap').hide();
				}
			});
			//Ajax send
		}

		/*code to get response from user and add Pinterest account */
		$(".update_loader_pinterest").css('display', 'none');
		$(document).on('click', '.add-pin-account', function(){
			$(".update_loader_pinterest").css('display', 'block');
			var pin_cookie_data = $('#sap-pinterest-cookie-data').val();
			if( pin_cookie_data != '' ){

				$(this).attr('disabled','true');

				var data = {

					pin_cookie_data : pin_cookie_data,
					
				};

				$(this).addClass('active');

				$.ajax({

					type: 'POST',
					url:'../settings/sap_auto_poster_pinterest_add_accounts/',
					data: data,
					dataType: "json",
					success: function (result) {
						if(result.status == 'success' ){
							$(".update_loader_pinterest").css('display', 'none');
							
							$('#pinterest-result').addClass('success');
							$('#pinterest-result').html(result.message);
							setTimeout(function(){ 
								location.reload(); 
							}, 3000);

						} else{
							$(".update_loader_pinterest").css('display', 'none');
							$('#pinterest-result').addClass('error');
							$('#pinterest-result').html(result.message);
							$(".add-pin-account").removeAttr('disabled');
						}

					}
				});
			} else {
				$(".update_loader_pinterest").css('display', 'none');
				$('#pinterest-result').addClass('error');
				$('#pinterest-result').html("Enter pinterest session id");
			}
		} );   

		// Validate link pattern on change for quick post form
		$(document).on('change', '.sap-quick-valid-url', function () {
			var website_url = $(this).val();
			if (website_url != '' && !sap_is_url_valid(website_url)) {
				websitecontent = $(this).addClass('sap-not-rec').focus();
				$(this).parent().find('.sap-share-link-err').remove();
				$(this).parent().append('<span class="sap-share-link-err">Please enter valid url (i.e. http://www.example.com).</span>');
				$(this).parent().find('.sap-share-link-err').show();
				$('html, body').animate({scrollTop: websitecontent.offset().top - 50}, 500);
				$(this).val('');
				return false;
			} else {
				$(this).parent().find('.sap-share-link-err').hide();
				$(this).removeClass('sap-not-rec');
				return true;
			}
		} );

		/**Quick Post Submit Form **/
		$(document).on( 'submit', "#quick-post-form", function (e) {

			$('.sap_quick_post_add').prop('disabled', true);

			var error = [];
			if( $('.sap-custom-tab').hasClass('sap-msg-tab-content-active') ){
				var message = $.trim($('.quick-post-message').val());
			}else{
				var message = $.trim($('.quick-post-ai-message').val());
			}
			var facebook = $('#enable_facebook:checked').val();
			var fb_accounts = $('#sap_fb_user_id').val();
			var twitter = $('#enable_twitter:checked').val();
			var tw_accounts = $('#sap_twitter_user_id').val();
			var linkedind = $('#enable_linkedin:checked').val();
			var li_accounts = $('#sap_linkedin_user_id').val();
			var tumblr = $('#enable_tumblr:checked').val();
			var tum_posting_type = $('#sap_posting_type').val();
			var ins_accounts = $('#sap_ins_user_id').val();
			var pinterest = $('#enable_pinterest:checked').val();
			var pin_accounts = $('#sap_pin_user_id').val();
			var any_network = $('#accordion .box-title input:checkbox:checked').length;
			var google_my_business =  $('#enable_gmb:checked').val();
			var google_my_business_accounts = $('#sap_gmb_location_id').val();
			var tumblr_accounts = $('#sap_tumblr_user_id').val();
			var blogger_setting = $('#sap_tumblr_user_id').val();

			if (message == "") {
				$('.quick-post-message').val('');
				$('.quick-post-message').closest('.sap-msg-wrap').addClass('has-error');
				$('.quick-post-message').focus();
				error.push(1);
			} else {
				$('.quick-post-message').closest('.sap-msg-wrap').removeClass('has-error');
				error.push(0);
			}

			if( $('#enable_blogger').is(':checked') ){
				
				if ( $.trim( $('#sap_blogger_title').val() ) == "" ) {
					$('#sap_blogger_title').val('');
					$('#sap_blogger_title').closest('.sap-msg-wrap').addClass('has-error');
					error.push(1);
				} else {
					$('#sap_blogger_title').closest('.sap-msg-wrap').removeClass('has-error');
					error.push(0);
				}
			}
				

			if ($('#accordion .box-title input:checkbox:checked').length == 0) {
				error.push(1);
				$('.network-error').addClass('has-error').html('<label class="control-label">Please activate at least one networks.</label>');
			}

			if (facebook == 1 && fb_accounts == "") {
				$('.fb-wrap').addClass('has-error');
				error.push(1);
			} else {
				$('.fb-wrap').removeClass('has-error');
				error.push(0);
			}

			if (twitter == 1 && tw_accounts == "") {
				$('.tw-wrap').addClass('has-error');
				error.push(1);
			} else {
				$('.tw-wrap').removeClass('has-error');
				error.push(0);
			}

			if (linkedind == 1 && li_accounts == "") {
				$('.li-wrap').addClass('has-error');
				error.push(1);
			} else {
				$('.li-wrap').removeClass('has-error');
				error.push(0);
			}

			if(tumblr == 1 && tumblr_accounts == ""){
				$('#quick_post_tumblr .location-label').addClass('has-error');
				$('.tum-wrap .box-title').addClass('has-error');
				error.push(1);
			} else {
				$('#quick_post_tumblr .location-label').removeClass('has-error');
				$('.tum-wrap .box-title').removeClass('has-error');
				error.push(0);
			}

			if( google_my_business == 1 && google_my_business_accounts == "" ){
				$('#quick_post_gmb .location-label').addClass('has-error');
				$('.gmb-wrap .box-title').addClass('has-error');
				error.push(1);
			} else {
				$('.gmb-wrap .box-title').removeClass('has-error');
				$('#quick_post_gmb .location-label').removeClass('has-error');
				error.push(0);
			}

			if ( pinterest == 1 && pin_accounts == "" ) {
				$('.pin-wrap').addClass('has-error');
				error.push(1);
			} else {
				$('.pin-wrap').removeClass('has-error');
				error.push(0);
			}



			if (jQuery.inArray(1, error) != -1) {
				$('.sap_quick_post_add').prop('disabled', false);
				return false;
			} else {
				$("#quick-post-form").submit();
			}

		} );


		/**Multi Add Post Submit Form **/
		$(document).on( 'submit', "#addpost", function (e) {

			var error = checkPostsError();

			if (jQuery.inArray(1, error) != -1) {
				$('.add-new-post').prop('disabled', false);
				return false;
			}

		} );

		/**Multi Edit Post Submit Form **/
		$(document).on( 'submit', "#updatepost", function (e) {

			var error = checkPostsError();

			if (jQuery.inArray(1, error) != -1) {
				$('.add-new-post').prop('disabled', false);
				return false;
			} else {
				return true;
			}

		});

		function checkPostsError(){

			var error = [];

			var message = $.trim($('.multi-post-message').val());
			if (message == "") {
				$('.multi-post-message').val('');
				$('.multi-post-message').closest('.sap-msg-wrap').addClass('has-error');
				$('.multi-post-message').focus();
				error.push(1);
			} else {
				$('.multi-post-message').closest('.sap-msg-wrap').removeClass('has-error');
				error.push(0);
			}


			if ( $('.sap-blogger-title').length > 0 ) {
				var blogger_title = $.trim( $('.sap-blogger-title').val() );
				if ( blogger_title == "" ) {
					$('.sap-blogger-title').val('');
					$('.sap-blogger-title').closest('.sap-msg-wrap').addClass('has-error');
					$('li.blogger_tab').css('border-top-color','#dd4b39');
					error.push(1);
				} else {
					$('.sap-blogger-title').closest('.sap-msg-wrap').removeClass('has-error');
					$('li.blogger_tab.active').removeAttr('style');
					error.push(0);
				}
			}

			if ( $('#sap_reddit_msg').length > 0 ) {
				var blogger_title = $.trim( $('#sap_reddit_msg').val() );
				if ( blogger_title == "" ) {
					$('#sap_reddit_msg').val('');
					//$('#sap_reddit_msg').closest('.sap-msg-wrap').addClass('has-error');
					// $('li.reddit_tab').css('border-top-color','#dd4b39');
					// error.push(1);
				} else {
					$('#sap_reddit_msg').closest('.sap-msg-wrap').removeClass('has-error');
					$('li.reddit_tab.active').removeAttr('style');
					error.push(0);
				}
			}

			return error;

		}

		if ( $('#add-plan').length > 0 || $('#edit-plan').length > 0 ||
			$('#add-member').length > 0  || $('#edit-member').length > 0 ) {

			$.validator.addMethod( "passwordCheck",
				function(value, element) {
					return this.optional(element) || /(?=.*\d)(?=.*[a-z])(?=.*[A-Z])(?=.*[!@#$%^&amp;*()_+}{&quot;:;'?/&gt;.&lt;,])(?!.*\s).*$/gm.test(value);
				},
			);

			// Add edit plan validations
			$( '#add-plan, #edit-plan').validate( {
				normalizer: function(value) {
					// Trim the value of every element
					return $.trim(value);
				},
				rules: {
					sap_name: {
						required: true
					},
					sap_price: {
						required: true,
						number: true
					}
				},
				messages: {
					sap_name: {
						required: 'Please enter the membership level name.'
					},
					sap_price: {
						required: 'Please enter membership level price',
						number: 'Price support digits only'
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
			} );

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
						required: "Please provide a password",
						minlength: "Your password must be at least 8 characters long",
						passwordCheck: "Password should be 8 characters long as well as it should contain the capital , lower case letters, at least one digit and one special character (1-9, !, *, _, etc.).",
					},
					sap_repassword: {
						required: "Please provide a password",
						minlength: "Your password must be at least 8 characters long",
						equalTo: "Please enter the same password",
					},
					sap_plan: {
						required: "Please select valid plan",
					}
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

			$( '#edit-member').validate( {
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
						required: false,
						minlength: 8,
						normalizer: function(value) {
							return $.trim(value);
						},
						passwordCheck: true
					},
					sap_repassword: {
						required: false,
						minlength: 8,
						equalTo: "#sap_password"
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
						required: "Please provide a password",
						minlength: "Your password must be at least 8 characters long",
						passwordCheck: "Password should be 8 characters long as well as it should contain the capital , lower case letters, at least one digit and one special character (1-9, !, *, _, etc.).",
					},
					sap_repassword: {
						required: "Please provide a password",
						minlength: "Your password must be at least 8 characters long",
						equalTo: "Please enter the same password",
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
		}

	});



	function checkUrl(url) {
		//regular expression for URL
		var pattern = /^(http|https)?:\/\/[a-zA-Z0-9-\.]+\.[a-z]{2,4}/;

		if ( pattern.test(url) ) {
			return true;
		} else {
			return false;
		}
	}

	// hide/show Tweet Image
	if( $('#disable-image-tweet').prop("checked") === true ){
		$(".sap-tweet-img-wrap").parent().hide();
	} else if( $('#disable-image-tweet').prop("checked") === false ){
		$(".sap-tweet-img-wrap").parent().show();
	}

		// hide/show Tweet Image
	if( $('#disable-image-reddit').prop("checked") === true ){
		$(".sap-reddit-img-wrap").parent().hide();
	} else if( $('#disable-image-reddit').prop("checked") === false ){
		$(".sap-reddit-img-wrap").parent().show();
	}

	$(document).on( 'change', '#disable-image-tweet', function() {
		if( $(this).prop("checked") === true ) {
			$(".sap-tweet-img-wrap").parent().hide();
		}
		else if( $(this).prop("checked") === false ){
			$(".sap-tweet-img-wrap").parent().show();
		}
	} );

	$(document).on( 'change', '#disable-image-reddit', function() {
		if( $(this).prop("checked") === true ) {
			$(".sap-reddit-img-wrap").parent().hide();
		}
		else if( $(this).prop("checked") === false ){
			$(".sap-reddit-img-wrap").parent().show();
		}
	} );

	// hide/show approved company note
	if( $('#enable_company_pages').prop("checked") === true ){
		$(".alert.organization-approved").show();
	} else if( $('#enable_company_pages').prop("checked") === false ){
		$(".alert.organization-approved").hide();
	}

	$(document).on( 'change', '#enable_company_pages', function() {
		if( $(this).prop("checked") === true ){
			$(".alert.organization-approved").show();
		}
		else if( $(this).prop("checked") === false ){
			$(".alert.organization-approved").hide();
		}
	} );

	if ( $(".sap-url-shortener-select").length ) {
		$(".sap-url-shortener-select").each(function(index,element){
			var value = $(this).val();
			if ( $("input.bitly-token").length && $("input.shorte-token").length ) {
				var main_parent = $(this).parent().parent().parent();
				var bitly_parent = main_parent.find("input.bitly-token").parent().parent();
				var shorte_parent = main_parent.find("input.shorte-token").parent().parent();
				if ( value === 'tinyurl' ) {
					bitly_parent.hide();
					shorte_parent.hide();
				} else if ( value === 'bitly' ) {
					bitly_parent.show();
					shorte_parent.hide();
				} else if ( value === 'shorte.st' ) {
					shorte_parent.show();
					bitly_parent.hide();
				} else {
					bitly_parent.hide();
					shorte_parent.hide();
				}
			}
		});
	}

	$(".sap-url-shortener-select").on('change', function (e) {
		var value = $(this).val();
		if ( $("input.bitly-token").length && $("input.shorte-token").length ) {
			var main_parent = $(this).parent().parent().parent();
			var bitly_parent = main_parent.find("input.bitly-token").parent().parent();
			var shorte_parent = main_parent.find("input.shorte-token").parent().parent();
			if ( value === 'tinyurl' ) {
				bitly_parent.hide();
				shorte_parent.hide();
			}else if( value === 'bitly' ){
				bitly_parent.show();
				shorte_parent.hide();
			}else if( value === 'shorte.st' ){
				shorte_parent.show();
				bitly_parent.hide();
			}else{
				bitly_parent.hide();
				shorte_parent.hide();
			}
		}
	} );

	if ( $(".sap_share_posting_type_fb").length ) {
		var value = $(".sap_share_posting_type_fb option:selected").val();
		if( value == 'image_posting' ){
			$(".show-fb-image-post").show();
		} else {
			$(".show-fb-image-post").hide();
		}
	}

	$(".sap_share_posting_type_fb").on('change', function (e) {
		var value = $(this).val();
		if( value == 'image_posting' ){
			$(".show-fb-image-post").show();
		}else{
			$(".show-fb-image-post").hide();
		}
	} );

	if ( $(".sap_share_posting_type_fb_meta").length ) {
		var value = $(".sap_share_posting_type_fb_meta option:selected").val();
		if( value == 'image_posting' ){
			$(".show-fb-image-post").show();
			$(".hide-fb-custom-link").hide();
		}else{
			$(".show-fb-image-post").hide();
			$(".hide-fb-custom-link").show();
		}
	}

	$(".sap_share_posting_type_fb_meta").on('change', function (e) {
		var value = $(this).val();
		if( value == 'image_posting' ){
			$(".show-fb-image-post").show();
			$(".hide-fb-custom-link").hide();
		}else{
			$(".show-fb-image-post").hide();
			$(".hide-fb-custom-link").show();
		}
	} );

	if ( $("#sap_tumblr_posting_type").length ) {
		var value = $("#sap_tumblr_posting_type option:selected").val();
		
		if( value == 'text' ){
			$(".hide-tumblr-post-img").hide();
		}  else {
			$(".hide-tumblr-post-img").show();
		}

		if( value == 'link' ){
			$(".hide-tumblr-post-link").show();
		}  else {
			$(".hide-tumblr-post-link").hide();
		}
	}

	$("#sap_tumblr_posting_type").on('change', function (e) {
		var value = $(this).val();
		if( value == 'text' ){
			$(".hide-tumblr-post-img").hide();
		}  else {
			$(".hide-tumblr-post-img").show();
		}

		if( value == 'link' ){
			$(".hide-tumblr-post-link").show();
		}  else {
			$(".hide-tumblr-post-link").hide();
		}
	} );

	if ( $(".sap-tumblr-post-type").length ) {
		var value = $(".sap-tumblr-post-type option:selected").val();
		
		if( value == 'text' ){
			$(".sap-tumblr-post-image").hide();
		}  else {
			$(".sap-tumblr-post-image").show();
		}

		if( value == 'link' ){
			$(".sap-tumblr-post-link").show();
		}  else {
			$(".sap-tumblr-post-link").hide();
		}
	}

	$(".sap-tumblr-post-type").on('change', function (e) {
		var value = $(this).val();
		if( value == 'text' ){
			$(".sap-tumblr-post-image").hide();
		}  else {
			$(".sap-tumblr-post-image").show();
		}
		if( value == 'link' ){
			$(".sap-tumblr-post-link").show();
		}  else {
			$(".sap-tumblr-post-link").hide();
		}
	} );

	if ( $("input#sap_is_display_schedule").length ) {
		var is_display_schedule = $("input#sap_is_display_schedule").val();
		if (is_display_schedule === "false") {
			$(".edit-multi-post-schedule").hide();
		}
	}

	if( $(".payment-page").length > 0){
		$('.wrapper').addClass('payment-page-body');
	}

	
	$(document).on("change","#stripe_test_mode",function(){
		
		$('.stripe-test').hide();
		$('.stripe-live').hide();

		if( $(this).is(':checked') ){
			$('.stripe-test').show();
			$('.stripe-live').hide();
		}
		else{
			$('.stripe-test').hide();
			$('.stripe-live').show();
		}
	});

	$(document).on('change','#no_expiration',function(){	

		if($(this).is(":checked")) {
	 		$('.add-membership-form #expiration_date').closest('.row').hide();
		}
		else{
		 	$('.add-membership-form #expiration_date').closest('.row').show();
		}


		if($(this).is(":checked")) {
	 		$('.edit-membership-form #expiration_date').closest('.row').hide();
		}
		else{
		 	$('.edit-membership-form #expiration_date').closest('.row').show();
		}
	});

	$("#no_expiration").trigger('change');
	
	$("#stripe_test_mode").trigger('change');


	$(document).on('change','.price_zero_cls',function(){
        
        $('.payment_method_cls input[type="radio"]').prop('checked',false); 
        $('.payment_method_cls').css('display','none');
        $('.stripe-payment-fields').css('display','none');
        $('.auto-renew-opt').css('display','none');
        $('.stripe-payment-fields .form-group').removeClass('has-error');
        $('.stripe-payment-fields .form-group .error.help-block').remove();

        $('.payment-detail-wrap').hide();       

    });

    $(document).on('change','.price_not_zero_cls',function(){

        $('#payment_stripe').prop('checked',true); 
        $('.payment_method_cls').css('display','block');
        $('.stripe-payment-fields').css('display','block');
        $('.auto-renew-opt').css('display','block');

        $('.payment-detail-wrap').show();

        var data = {
			plan_id: $(this).val(),
			is_ajax: true,
		};

		$.ajax({
			type: 'POST',
			url: SAP_SITE_URL + '/plan-proration-credit/',
			data: data,
			success: function (response) {				

				if( response != '' ){
					$('.discount-fees').show();
					$('.discount-fees').html(response);
				}

				if( response == '0'){
					$('.discount-fees').hide();
				}
			}
		});
    });

   	$(document).on('change',' .unlimited_plan',function(){
    	$('.auto-renew-opt').hide();
    });


    


   //
   $(document).on('change','.add-membership-form #sap_plan',function(){
   		
   		var data = {
			plan_id: $(this).val(),
		};

		$.ajax({
			type: 'POST',
			url: SAP_SITE_URL + '/plan/plan-expiry-date/',
			data: data,
			success: function (response) {
				$("#expiration_date").val('');
				if( response != '' ){
					$("#expiration_date").val(response);
				}
			}
		});

   });

    $(document).on('change','.payment-gateway',function(){	

		var payment_gateway = $(this).val();

		$('.stripe-payment-fields').hide();
		$('.auto-renew-opt').hide();
		$('#stripe-submit').text('Continue')


		if( payment_gateway == 'stripe'){
			$('.stripe-payment-fields').show();
			$('#stripe-submit').text('Make Payment');
		}

		if( payment_gateway == 'stripe' || payment_gateway == 'paypal'){			
			$('.auto-renew-opt').show();
			$('#stripe-submit').text('Make Payment');
		}
	});
	$('.form-check.upgrade .plan' ).prop('checked',false);


	$(document).on('click','.sap-general-setting-tab li a',function(){
		$("#sap_active_tab").val($(this).data('tab'));
	});


	$(document).on('click','.created-edit-link',function(){
		$('.membership-created-date-text').hide();
		$('.membership-created-date-input').show();

	});



	$(document).on('change','#add-payment #user_id, #edit-payment #user_id',function(){
		var data = {
			user_id: $(this).val(),
		};

		$.ajax({
			type: 'POST',
			url: SAP_SITE_URL + '/payments/get_user_membership_details/',
			data: data,
			success: function (response) {

				var obj = JSON.parse( response );

				if( obj.status){
					
					$("#plan_id").html(obj.result);
				}
				
			}
		});
		
	});

	$("#add-payment #user_id").trigger('change');
	$("#edit-payment #user_id").trigger('change');



	if($('.membership-created-date-input').length > 0){
		$( ".membership-created-date-input" ).datepicker({
			dateFormat: 'yy-mm-dd',
		  	changeMonth: true,
		  	changeYear: true,
		  	maxDate: new Date()
		});	
	}


	$('.tt_large').tooltip({
	    template: '<div class="tooltip" role="tooltip"><div class="tooltip-arrow"></div><div class="tooltip-inner large"></div></div>'
	});


	$(document).on('change','#auto_renew',function(){
		$('.auto-renew-note').show();
	})
	
	if( $('.sap-custom-tab').hasClass('sap-msg-tab-content-active') ){
		$('.sap-custom-link').show();
		$('.sap-active-tab').val('sap-custom-tab');
	}else{
		$('.sap-custom-link').hide();
		$('.sap-active-tab').val('sap-ai-tab');
	}
	$(document).on('click','.sap-message-tab-nav',function (e) {
		e.preventDefault();
		$('#sap_caption_link_error_msg').hide();
		var id = $(this).attr('id');
		$('a.sap-message-tab-nav').each(function (index, element) {
			$(this).removeClass('sap-msg-tab-nav-active');
			$(this).closest('ul').find('li').removeClass('sap-msg-tab-li-active');
		});
		$('.sap-message-tab-content').each(function (index, element) {
			$(this).removeClass('sap-msg-tab-content-active');
		});
		$(".sap-message-tab-link").each(function (index, element) {
			$(this).removeClass("sap-tab-link-active");
		});
		$(this).addClass("sap-msg-tab-nav-active");
		$(this).closest('li').addClass("sap-msg-tab-li-active");
		$(".sap-message-tab-content#" + id).addClass("sap-msg-tab-content-active");
		if( ".sap-message-tab-content#" + id == ".sap-message-tab-content#sap-ai-message"){
			$(".sap-custom-link").hide();
			$(".sap-active-tab").val("sap-ai-tab");
		}
		else{
			$(".sap-custom-link").show();
			$(".sap-active-tab").val("sap-custom-tab");
		}
	});
	$('.sap-caption-loader-img').hide();
	$("#sap_caption_error_msg").hide();
	$(document).on('click','.sap-ai-caption-btn',function(e){
		e.preventDefault();
		$('.sap-ai-div').addClass('sap-ai-caption-loader-effect');
		$('.sap-caption-loader-img').show();
		$('#sap_caption_error_msg').hide();
		$('#sap_caption_link_error_msg').hide();
		
		var number_of_words = $('.sap-caption-words').val();
		var sap_ai_content_link = $('.sap-ai-link').val();
		var urlRegex = /^(ftp|http|https):\/\/[^ "]+$/;
		if( sap_ai_content_link == '' || !urlRegex.test(sap_ai_content_link) ){
			$('.sap-ai-div').removeClass('sap-ai-caption-loader-effect');
			$('.sap-caption-loader-img').hide();
			$('#sap_caption_link_error_msg').attr({ style: 'color:red' });
			$('#sap_caption_link_error_msg').show();
			$('#sap_caption_link_error_msg').text('Please Provide Valid Link');
		}else{
			$('#sap_caption_link_error_msg').hide();
			$.ajax({
				type: 'POST',
				url:  SAP_SITE_URL + '/quick-post/sap_generate_caption/',
				data: {
					number_of_words: number_of_words,
					sap_ai_content_link: sap_ai_content_link 
				},
				success: function(response){
					var data_res = JSON.parse(response);
					if (data_res.created != '' && data_res.created != undefined ) {
					  var caption = '';
					  caption = data_res.choices[0].message.content;
					  $('.quick-post-ai-message').val(caption);
					  $('.sap-ai-div').removeClass('sap-ai-caption-loader-effect');
					  filter_caption('#quick-post-ai-message');
					  $('.sap-caption-loader-img').hide();
					  $('.sap-ai-div').show();
					}
					else {
						$('.sap-caption-loader-img').hide();
						$('.sap-ai-div').removeClass('sap-ai-caption-loader-effect');
						jQuery('#sap_caption_error_msg').show();
						jQuery('#sap_caption_error_msg').attr({ style: 'color:red' });
						jQuery('#sap_caption_error_msg').text(data_res.error.message);
						if( data_res.error.message == ''){
							jQuery('#sap_caption_error_msg').text(data_res.error.code);
						}
					  }
				},
				error: function (errorThrown) {
					alert(errorThrown);
				}
			});	
		}
	});
	function filter_caption(input_id) {
		  var caption = jQuery(input_id).val();  
		  if (caption != "") {
			var txt = "";
			var caption_arr = caption.split(" ");
			if (caption_arr.length > 0) {
			  for (var i = 0; i < caption_arr.length; i++) {
				var word = caption_arr[i];
				if (word.startsWith("#") != true && typeof word !== "undefined") {
				  txt += word + " ";
				}
			  }
			}
			jQuery(input_id).val(txt);
		  }
	  }
} );

function validateEmail($email) {
	var emailReg = /^([\w-\.]+@([\w-]+\.)+[\w-]{2,4})?$/;
	return emailReg.test( $email );
}
