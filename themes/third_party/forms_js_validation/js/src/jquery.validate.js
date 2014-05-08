;(function ($) {
    
    //validate options
	JS_FORMS._validation_options = {};
	JS_FORMS._callbacks = [];

	//Create a validation function
    JS_FORMS.validation = function (selector, caller_id) {

		//find the form element
        $(selector).find(JS_FORMS.input_element_wrapper[caller_id]).each(function () {
			//default validations vars
			_default_val = '';
			_validation_default = [];
			
			//custom validations vars
            _custom_val = '';
			_validation_custom = [];
			
			//minimum validations vars
			_min_val = '';
			_validation_min = '';
			
			//maximum validations vars
			_max_val = '';
			_validation_max = '';
			
			//minimum size validations vars
			_min_size_val = '';
			_validation_min_size = '';
			
			//maximum size validations vars
			_max_size_val = '';
			_validation_max_size = '';
			
			//minimum checkbox size 
			_min_checkbox_val = '';
			_validation_min_checkbox = '';
			
			//maximum checkbox size
			_max_checkbox_val = '';
			_validation_max_checkbox = '';

			//equal vars
			_equals_val = '';
			_validation_equals = '';

			//condition vars
			_condition_val = '';
			_validation_condition = '';

			//Past vars
			_past_val = '';
			_validation_past = '';

			//Future vars
			_future_val = '';
			_validation_future = '';

			//------------------------------------------------------------------------
			
            _id = '';
			_group_name = [];
			_validation_type = '';
			
			_combined_rules = [];
			
			//cache the input elem
			$_input = $(this).find('input, textarea, select');

			//------------------------------------------------------------------------
			//ADD THE VALIDATION CLASSES
			//TO THE ELEMENTS
			//------------------------------------------------------------------------
			if(JS_FORMS.add_class[caller_id] && $_input.attr('class') != undefined) {

                // add groups required
				var group_regex = new RegExp(JS_FORMS.group_class[caller_id]+':[a-zA-Z0-9_-]*', 'g');
				var group_name = $_input.attr('class').match(group_regex);
				if (group_name != '' && group_name != null) {
					//set validation type
					_validation_type = 'group';
					
					//add the validation
					_validation_default.push('groupRequired');
					_group_name.push(group_name[0].replace(JS_FORMS.group_class[caller_id]+':',''));
					
					//remove the class
					$_input.removeClass(group_name[0]);
                
				// normal line required
				} else if ($(this).hasClass(JS_FORMS.require_class[caller_id]) || $_input.hasClass(JS_FORMS.require_class[caller_id])) {
                    //set validation type
					_validation_type = 'single';
					
					//add rule
					_validation_default.push('required');
		
					$_input.removeClass(JS_FORMS.require_class[caller_id]);
                }

                // email validation
                if ($(this).hasClass(JS_FORMS.email_class[caller_id]) || $_input.hasClass(JS_FORMS.email_class[caller_id])) {
                    _validation_custom.push('email');
					$_input.removeClass(JS_FORMS.email_class[caller_id]);
                }
				
				// integer validation
                if ($(this).hasClass(JS_FORMS.integer_class[caller_id]) || $_input.hasClass(JS_FORMS.integer_class[caller_id])) {
                    _validation_custom.push('integer');
					$_input.removeClass(JS_FORMS.integer_class[caller_id]);
                }
				
				// number validation
                if ($(this).hasClass(JS_FORMS.number_class[caller_id]) || $_input.hasClass(JS_FORMS.number_class[caller_id])) {
                    _validation_custom.push('number');
					$_input.removeClass(JS_FORMS.number_class[caller_id]);
                }
				
				// phone validation
                if ($(this).hasClass(JS_FORMS.phone_class[caller_id]) || $_input.hasClass(JS_FORMS.phone_class[caller_id])) {
                    _validation_custom.push('phone');
					$_input.removeClass(JS_FORMS.phone_class[caller_id]);
                }
				
				// url validation
                if ($(this).hasClass(JS_FORMS.url_class[caller_id]) || $_input.hasClass(JS_FORMS.url_class[caller_id])) {
                    _validation_custom.push('url');
					$_input.removeClass(JS_FORMS.url_class[caller_id]);
                }
				
				// date validation
                if ($(this).hasClass(JS_FORMS.date_class[caller_id]) || $_input.hasClass(JS_FORMS.date_class[caller_id])) {
                    _validation_custom.push('date');
					$_input.removeClass(JS_FORMS.date_class[caller_id]);
                }
				
				// ipv4 validation
                if ($(this).hasClass(JS_FORMS.ipv4_class[caller_id]) || $_input.hasClass(JS_FORMS.ipv4_class[caller_id])) {
                    _validation_custom.push('ipv4');
					$_input.removeClass(JS_FORMS.ipv4_class[caller_id]);
                }

                // Creditcard validation
                if ($(this).hasClass(JS_FORMS.creditcard_class[caller_id]) || $_input.hasClass(JS_FORMS.creditcard_class[caller_id])) {
                    _combined_rules.push('creditCard');
					$_input.removeClass(JS_FORMS.creditcard_class[caller_id]);
                }

                // Equals validation
				var equals_regex = new RegExp(JS_FORMS.equals_class[caller_id]+':[a-zA-Z0-9_-]*', 'g');
				var equals_name = $_input.attr('class').match(equals_regex);
				if (equals_name != '' && equals_name != null) {
					_validation_equals = equals_name[0].replace(JS_FORMS.equals_class[caller_id]+':','');
					$_input.removeClass(equals_name[0]);
				}

				// Past validation
				var past_regex = new RegExp(JS_FORMS.past_class[caller_id]+':[a-zA-Z0-9_-]*', 'g');
				var past_name = $_input.attr('class').match(past_regex);
				if (past_name != '' && past_name != null) {
					_validation_past = past_name[0].replace(JS_FORMS.past_class[caller_id]+':','');
					$_input.removeClass(past_name[0]);
				}

				// Past validation
				var future_regex = new RegExp(JS_FORMS.future_class[caller_id]+':[a-zA-Z0-9_-]*', 'g');
				var future_name = $_input.attr('class').match(future_regex);
				if (future_name != '' && future_name != null) {
					_validation_future = future_name[0].replace(JS_FORMS.future_class[caller_id]+':','');
					$_input.removeClass(future_name[0]);
				}

				// condition validation
				var condition_regex = new RegExp(JS_FORMS.condition_class[caller_id]+':[a-zA-Z0-9_-]*', 'g');
				var condition_name = $_input.attr('class').match(condition_regex);
				if (condition_name != '' && condition_name != null) {
					_validation_condition = condition_name[0].replace(JS_FORMS.condition_class[caller_id]+':','');
					$_input.removeClass(condition_name[0]);
				}

				// MIN validation
				var min_regex = new RegExp(JS_FORMS.min_class[caller_id]+':[a-zA-Z0-9_-]*', 'g');
				var min_name = $_input.attr('class').match(min_regex);
				if (min_name != '' && min_name != null) {
					_validation_min = min_name[0].replace(JS_FORMS.min_class[caller_id]+':','');
					$_input.removeClass(min_name[0]);
				}
				// MAX validation
				var max_regex = new RegExp(JS_FORMS.max_class[caller_id]+':[a-zA-Z0-9_-]*', 'g');
				var max_name = $_input.attr('class').match(max_regex);
				if (max_name != '' && max_name != null) {
					_validation_max = max_name[0].replace(JS_FORMS.max_class[caller_id]+':','');
					$_input.removeClass(max_name[0]);
				}
				
				// MIN validation
				var min_size_regex = new RegExp(JS_FORMS.min_size_class[caller_id]+':[a-zA-Z0-9_-]*', 'g');
				var min_size_name = $_input.attr('class').match(min_size_regex);
				if (min_size_name != '' && min_size_name != null) {
					_validation_min_size = min_size_name[0].replace(JS_FORMS.min_size_class[caller_id]+':','');
					$_input.removeClass(min_size_name[0]);
				}	
				// MAX validation
				var max_size_regex = new RegExp(JS_FORMS.max_size_class[caller_id]+':[a-zA-Z0-9_-]*', 'g');
				var max_size_name = $_input.attr('class').match(max_size_regex);
				if (max_size_name != '' && max_size_name != null) {
					_validation_max_size = max_size_name[0].replace(JS_FORMS.max_size_class[caller_id]+':','');
					$_input.removeClass(max_size_name[0]);
				}
				
				// MIN checkbox validation
				var min_checkbox_regex = new RegExp(JS_FORMS.min_checkbox_class[caller_id]+':[a-zA-Z0-9_-]*', 'g');
				var min_checkbox_name = $_input.attr('class').match(min_checkbox_regex);
				if (min_checkbox_name != '' && min_checkbox_name != null) {
					_validation_min_checkbox = min_checkbox_name[0].replace(JS_FORMS.min_checkbox_class[caller_id]+':','');
					$_input.removeClass(min_checkbox_name[0]);
				}				
				// MIN checkbox validation
				var max_checkbox_regex = new RegExp(JS_FORMS.max_checkbox_class[caller_id]+':[a-zA-Z0-9_-]*', 'g');
				var max_checkbox_name = $_input.attr('class').match(max_checkbox_regex);
				if (max_checkbox_name != '' && max_checkbox_name != null) {
					_validation_max_checkbox = max_checkbox_name[0].replace(JS_FORMS.max_checkbox_class[caller_id]+':','');
					$_input.removeClass(max_checkbox_name[0]);
				}
			}
			
			//------------------------------------------------------------------------
			//END OF THE VALIDATIONS CLASSES
			//WHO ARE APPEND TO INPUT OBJECTS
			//------------------------------------------------------------------------

            //join all default validation to string
            if (_validation_default.length > 0) {				
                _default_val = _validation_default.join(',');
				
				//is group validation, show also the groupname
				if(_validation_type == 'group') {
					_default_val += '['+_group_name.join(',')+']';
				}
				
				_combined_rules.push(_default_val);
            }

            //join all custom validation to string
            if (_validation_custom.length > 0) {
                _custom_val = 'custom[' + _validation_custom.join(',') + ']';
				_combined_rules.push(_custom_val);
            }
			
			//join all Minimum validation to string
            if (_validation_min != '') {
                _min_val = 'min[' + _validation_min + ']';
				_combined_rules.push(_min_val);
            }
			//join all maximum validation to string
            if (_validation_max != '') {
                _max_val = 'max[' + _validation_max + ']';
				_combined_rules.push(_max_val);
            }

			//join all Minimum size validation to string
            if (_validation_min_size != '') {
                _min_size_val = 'minSize[' + _validation_min_size + ']';
				_combined_rules.push(_min_size_val);
            }
			//join all maximum size validation to string
            if (_validation_max_size != '') {
                _max_size_val = 'maxSize[' + _validation_max_size + ']';
				_combined_rules.push(_max_size_val);
            }

            //join all equals validation to string
            if (_validation_equals != '') {
                _equals_val = 'equals[' + _validation_equals + ']';
				_combined_rules.push(_equals_val);
            }

            //join all condition validation to string
            if (_validation_condition != '') {
                _condition_val = 'condRequired[' + _validation_condition + ']';
				_combined_rules.push(_condition_val);
            }
            
            //join all maximum checkbox size validation to string
            if (_validation_min_checkbox != '') {
                _min_checkbox_val = 'minCheckbox[' + _validation_min_checkbox + ']';
				_combined_rules.push(_min_checkbox_val);
            }
            //join all maximum checkbox size validation to string
           	if (_validation_max_checkbox != '') {
                _max_checkbox_val = 'maxCheckbox[' + _validation_max_checkbox + ']';
				_combined_rules.push(_max_checkbox_val);
            }

            //join all past validation to string
            if (_validation_past != '') {
                _past_val = 'past[' + _validation_past + ']';
				_combined_rules.push(_past_val);
            }

            //join all future validation to string
            if (_validation_future != '') {
                _future_val = 'future[' + _validation_future + ']';
				_combined_rules.push(_future_val);
            }

            //set the id
            _id = $(this).find('input, select, textarea').attr('name');

            //add the class
			if(_combined_rules.length > 0) {
				//$(this).find('input, select, textarea').addClass('validate[' +_combined_rules.join(',')+ ']');
				$(this).find('input, select, textarea').attr('data-validation-engine', 'validate[' +_combined_rules.join(',')+ ']');
			}

            //add ID
            $(this).find('input, select, textarea').attr('id', _id);
        });
		
		//detach for safe
		if(typeof($(selector).validationEngine) != 'undefined' ) {
			 $(selector).validationEngine('detach');
		}

		//set the validation options
    	$.extend(JS_FORMS._validation_options, {
			promptPosition : JS_FORMS.promt_position[caller_id], 
			scroll : JS_FORMS.scroll[caller_id],
			autoHidePrompt : JS_FORMS.auto_hide_prompt[caller_id], 
			autoHideDelay : JS_FORMS.auto_hide_delay[caller_id],
			binded : JS_FORMS.binded[caller_id],
			showOneMessage : JS_FORMS.show_one_message[caller_id],
			ajaxFormValidation : JS_FORMS.ajax_form_validation[caller_id],
			ajaxFormValidationURL : JS_FORMS.ajax_form_validation_url[caller_id],
			ajaxFormValidationMethod : JS_FORMS.ajax_form_validation_method[caller_id]
		});

		//set the callback functions
		for (var key in JS_FORMS._callbacks[caller_id]) {
			var obj = {};
			obj[key] = JS_FORMS._callbacks[caller_id][key];
			$.extend(JS_FORMS._validation_options, obj);			
		};

	   //run the validation
       $(selector).validationEngine('attach', JS_FORMS._validation_options);
        
    }

    //add callback functions
    JS_FORMS.addCallbackEvent = function (type, n, callback) {
		if(typeof(callback) == 'function' && n != '' && type != '') {
			//create an array
			if(!jQuery.isArray(JS_FORMS._callbacks[n])) {
				JS_FORMS._callbacks[n] = [];
			}
			JS_FORMS._callbacks[n][type] = callback;
		}
    };

    //call all validation
    JS_FORMS.init = function() {
    	if (JS_FORMS.selector != '') {
			jQuery.each(JS_FORMS.selector, function(key,val){
				JS_FORMS.validation(JS_FORMS.selector[key], key);
			});      
	    }
    }
    JS_FORMS.refresh = function() {
    	JS_FORMS.init();
    }

	//loop over the form that have to be validated
	$(window).ready(function() {	
	    JS_FORMS.init();
	});  
    	
})(jQuery);