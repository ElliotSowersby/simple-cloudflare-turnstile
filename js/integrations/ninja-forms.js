

jQuery(document).ready(function ($) {
	
	// NF heavily relies on Marionette for the majority of their client-side
	// functionality, so we'll play nice with that system by instantiating a controller
	// object that is specific to our needs.
	
	var nfController = null;
	
	
	// should this script toss stuff to the console?
	
	var nfDebugStuff = true;
	

	// wait on NF to announce a particular form is ready...
	
	$(document).on("nfFormReady", function(e, layout_view) {
		
		
		// if this is the very first NF form being prepped, let's ensure our global controller
		// is instantiated for it (and any subsequent NF forms) — we do this here _only after_
		// we know the first form is ready in order to avoid prematurely attempting to reference
		// or extend Marionette if it's not available yet.
		
		nfControllerInit();
		
		
		// generate new tokens for any NF CFT fields on this form
		
		nfResetAllWidgets(layout_view.model.id); // (passes the form_id)
		
	});
	
	
	// reset all CFT widgets on a form, via form_id
	
	function nfResetAllWidgets(form_id) {
		
		var $form 				= $("#nf-form-" + form_id + "-cont");
		var $containers 		= $form.find(".nf-field-container.simplecloudflareturnstile-container");
		
		$containers.each(function() {
			
			var widget_id = $(this).find(".cf-turnstile").prop("id");
			
			nfResetWidget(widget_id);
		
		});
		
	}
	
	
	// reset specific CFT widget, via widget_id
	
	function nfResetWidget(widget_id) {
		
		
		// useful values for resetting a CFT widget
		
		widget_id 				= (widget_id.length>0&&widget_id.split("")[0]=="#")?widget_id:"#"+widget_id;
		var $widget 			= $(widget_id);
		var form_id				= $widget.data("form_id");
		var field_id			= $widget.data("field_id");
		var guid 				= $widget.data("guid");
		var $container 			= $widget.closest(".nf-field-container.simplecloudflareturnstile-container");		
		var $response 			= $container.find(".cf-turnstile-response-" + guid);
		
		
		// since we're resetting let's nix the old token ASAP at the outset
		
		$response.val("");
		
		
		// unsure if timeout is needed... but other implementations in this plugin do it...
		// so it must be for a reason... and it doesn't hurt anything...
		
		setTimeout(function() {
			
			
			// kill the widget! (start fresh)
			
			turnstile.remove(widget_id);
			
			
			// alive the widget!
			
			turnstile.render(widget_id, { 
				
				
				// listen for new response tokens
				
				"callback": function(token) {
					
					_nfDebug(field_id + ": callback\n" + token);
					
					
					// manually assign the token value to our NF field value
					
					$response.val(token);
					
					
					var $form_error = $(".nf-error-msg.nf-error-field-errors");
					var $cft_error = $form_error.find(".nf-error-cfturnstile");
					
					$cft_error.hide();
					
					
					// clear out any client-side or server-side errors associated with this
					// particular CFT field to ensure it doesn't hold up a submission attempt
					
					nfController.channel.fields.request("remove:error", field_id, "CLOUDFLARE_TURNSTILE_SERVER_ERROR");
					nfController.channel.fields.request("remove:error", field_id, "CLOUDFLARE_TURNSTILE_CLIENT_ERROR");
				
				},
				
				"expired-callback": function() { 
					
					_nfDebug(field_id + ": expired-callback"); 
					
					
					// expired token is worthless so nix it while we wait for a new one
					
					$response.val(""); 
				
				},
				
			});	// ends turnstile.render block
			
		}, 0); // ends setTimeout block
		
	} // ends nfResetWidget function
	
	
	// we define our controller here which is purposely lightweight and tailored to 
	// our specific needs associated with CFT functionality

	function nfControllerInit() {
		
		
		// bail early if our controller is already defined
		
		if (nfController!=null) { return; }
		
		
		// temp var to hold definition (used immediately afterwards for instantiation)
		
		var _nfController = Marionette.Object.extend( {
			
			
			// we only listen to NF channels/events that might impact CFT
			
			initialize: function() {
				this.listenTo( this.channel.forms, 		"submit:response", 		this.listen.forms.submit_response);
				this.listenTo( this.channel.forms, 		"submit:failed", 		this.listen.forms.submit_failed);
				this.listenTo( this.channel.forms, 		"submit:cancel", 		this.listen.forms.submit_cancel);
				this.listenTo( this.channel.fields, 	"add:error", 			this.listen.fields.add_error);
				this.listenTo( this.channel.submit, 	"validate:field",		this.listen.submit.validate_field);
			},	
			
			
			// easy refs to channels we use
			
			channel: {
				submit: 	nfRadio.channel("submit"),
				fields: 	nfRadio.channel("fields"),
				forms: 		nfRadio.channel("forms"),
			},
			
			
			// object that holds all callbacks for events announed on channels we listen to

			listen: {
				
				submit: {
					
					validate_field: function(field_model) {
						
						
						// useful values we might need
						
						var field_type = field_model.get("type");
						var field_id = field_model.get("id");
						var field_value = field_model.get("value").trim();
						
						
						// bail if it's not a CFT field
						
						if (field_type!="simplecloudflareturnstile") { return; }
						
						if (field_value=="") {
														
							nfController.channel.fields.request("add:error", field_id, "CLOUDFLARE_TURNSTILE_CLIENT_ERROR", "Captcha validation failed. Please try again.");

						}
					},
				},
				
				
				// holds callback functions for events announced on the "fields" channel
				
				fields: {
					
					add_error: function(field_model, error_id, error_message) {
						
						
						// useful values we might need
						
						var field_id 		= field_model.get("id");
						var field_type 		= field_model.get("type");
						
						
						// bail if it's not a CFT field
						
						if (field_type!="simplecloudflareturnstile") { return; }

						_nfDebug("fields_add_error");
						_nfDebug(error_id + ": " + field_id);
						
						var $form_error = $(".nf-error-msg.nf-error-field-errors");
												
						switch(error_id) {
							
							case "CLOUDFLARE_TURNSTILE_CLIENT_ERROR":
							case "CLOUDFLARE_TURNSTILE_SERVER_ERROR":
							
								var $cft_error = $form_error.find(".nf-error-cfturnstile");
								if ($cft_error.length>0) {
									$cft_error.html(error_message);
									$cft_error.show();
								}
								else {
									$cft_error = $("<div>" + error_message + "</div>");
									$cft_error.addClass("nf-error-cfturnstile");
									$form_error.append($cft_error);
								}
								
								break;
								
						}
							
					}, // ends add_error function
				}, // ends fields block
				
				
				// holds calleback functions for events announced on the "forms" channel
				
				forms: {
					
					
					// submission response (and failure) events are grounds for resetting
					// the form's CFT tokens again so we'll wire that up here
					
					submit_response: function(response, status, x, form_id) { 
						_nfDebug("forms_submit_response"); 
						nfResetAllWidgets(form_id); 
					},
					
					submit_failed: function(response) {
						_nfDebug("forms_submit_failed"); 
						nfResetAllWidgets(response.get("id")); 
					},
					
					
					// we'll also reset the form's CFT tokens for submission cancelled events, 
					// but it's handled a little differently since we only get the form_model
					// and not the explicit form_id like we did for response/failed above
					
					submit_cancel: function(form_model) {
						_nfDebug("forms_submit_cancel");
						var form_id = form_model.get("id");
						nfResetAllWidgets(form_id); 
					},
						
				}, // ends forms block
				
			}, // ends listen block
			
		});	// ends _nfController def
		
		
		// finally, instantiate our controller
		
		nfController = new _nfController();
	
	} // ends nfControllerInit function
	
	
	// easily toss stuff to the console while devvving
	
	function _nfDebug(stuff){try{if(!nfDebugStuff){return;}console.log(stuff);}catch(e){}}

});