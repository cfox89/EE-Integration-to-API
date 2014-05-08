<?php
if ( ! defined('BASEPATH')) exit('No direct script access allowed');
 
/**
 * the config for the Forms JS Validation plugin
 *
 * @package             Forms JS validation for EE2
 * @author              Rein de Vries (info@reinos.nl)
 * @copyright           Copyright (c) 2013 Rein de Vries
 * @license  			http://reinos.nl/add-ons/commercial-license
 * @link                http://reinos.nl/add-ons/forms-js-validation
 */

include(PATH_THIRD.'forms_js_validation/config.php');

$plugin_info = array( 
  'pi_name' => FJV_NAME,
  'pi_version' => FJV_VERSION,
  'pi_author' => 'Rein de Vries',
  'pi_author_url' => 'http://www.reinos.nl',
  'pi_description' => FJV_DESCRIPTION,
  'pi_usage' => Forms_js_validation::usage()
);

class Forms_js_validation
{
	//private $EE; 
	private $site_id;
	private $_validation_rules;
	
	public $return_data = '';
	
	/* Are we in development? */
	const DEVELOPMENT = FJV_DEBUG;

	/**
	 * Constructor
	 * 
	 * @return unknown_type
	 */
	function Forms_js_validation()
	{
		//get EE object
		//$this->EE =& get_instance();
		
		//sets site_id
		$this->site_id = ee()->config->item('site_id');  	

		//define the themes url
		if (defined('URL_THIRD_THEMES') === TRUE)
		{
			$theme_url = URL_THIRD_THEMES;
		}
		else
		{
			$theme_url = ee()->config->item('theme_folder_url').'third_party/';
		}

		// Are we working on SSL?
		if ((isset($_SERVER['HTTPS']) === TRUE && empty($_SERVER['HTTPS']) === FALSE) OR (isset($_SERVER['HTTP_HTTPS']) === TRUE && empty($_SERVER['HTTP_HTTPS']) === FALSE))
		{
			$theme_url = str_replace('http://', 'https://', $theme_url);
		}
	
		//set the theme url
		$this->theme_url = $theme_url . 'forms_js_validation/';
		
		//set the theme path
		$this->theme_path = PATH_THEMES . '/third_party/forms_js_validation/';
	}

	// ----------------------------------------------------------------------------------
	
	/**
	 * init
	 * 
	 * @return unknown_type
	 */
	function init()
	{	
		$this->_set_param();
		ee()->session->userdata['forms_js_validation_init'] = true;
		
		//add jquery
		$this->return_data .=  $this->_add_jquery();

		//set the vars for the first time
		$this->return_data .=  $this->_set_js_vars(true);

		//set the lang, we handle this in the php script only
		$this->lang = ee()->TMPL->fetch_param('lang', 'en');

		//set css file, we handle this in the php script only
		$this->css_location = ee()->TMPL->fetch_param('css_location', $this->theme_url . 'css/validationEngine.jquery.css');

		//add css
		$this->return_data .= '<link rel="stylesheet" href="' . $this->css_location . '" type="text/css" />';
		
		//minify css on dev only
		if ( strstr( $_SERVER['SERVER_NAME'], '.local' ) && self::DEVELOPMENT)
		{
			$this->_compress_js(array(
				$this->theme_url . 'js/src/jquery.validationEngine.js',
				$this->theme_url . 'js/src/jquery.validate.js'
			));
			
			//add dev js
			$this->return_data .= '<script type="text/javascript" src="' . $this->theme_url . 'js/src/jquery.validationEngine.js" ></script>';
			$this->return_data .= '<script type="text/javascript" src="' . $this->theme_url . 'js/src/jquery.validate.js" ></script>';					
		}
		else
		{
			//add base js
			$this->return_data .= '<script type="text/javascript" src="' . $this->theme_url . 'js/forms_js_validation.min.js" ></script>';
		}
		
		//add lang
		$this->return_data .= '<script type="text/javascript" src="' . $this->theme_url . 'js/languages/jquery.validationEngine-'.$this->lang.'.js" ></script>'."\n";

		return $this->return_data;
	}
	
	// ----------------------------------------------------------------------------------
	 
	/**
	 * add_validation
	 * 
	 * @return unknown_type
	 */
	function add()
	{	
		//set the params
		$this->_set_param();
		
		//set the caller times
		$this->_add_call_timer();
		
		//add the css id and class
		$this->return_data .= $this->_set_js_vars();

		//parse the variables {count}
		$variables[] = array(
            'count' => ($this->caller_id-1)
        );	

        $this->return_data .= ee()->TMPL->parse_variables(ee()->TMPL->tagdata, $variables);

        return $this->return_data;
	}

	// ----------------------------------------------------------------------------------

	//set the js vars
	private function _set_js_vars($first = false)
	{
		//add the css id and class
		$return_data = '<script type="text/javascript">var JS_FORMS = JS_FORMS || {version : "'.FJV_VERSION.'"};';
		
		//get only the vars to declarate the vars
		if($first) 
		{
			$return_data .= $this->_init_js_vars();
		}
		else
		{
			//declaration of the vars
			//if(!$this->_is_init()) 
			//{		
			//	$return_data .= $this->_init_js_vars();
			//}
			
			//add js vars
			$return_data .= $this->_insert_js_vars();
		}

		return $return_data.'</script>'."\n";
	}

	// ----------------------------------------------------------------------------------

	// is the JS_FROMS already set?
	private function _is_init()
	{
		//set the time of calling inthe session
		if(ee()->session->userdata('forms_js_validation_init') == '')
		{
			return false;
		}
		return true;
	}

	// ----------------------------------------------------------------------------------

	//add jquery if not loaded
	private function _add_jquery()
	{
		if($this->_is_init())
		{
			$return_data = '
	            <script type="text/javascript">
	                    if(!window.jQuery || window.jQuery === undefined){
	                       document.write(unescape("%3Cscript src=\'' . $this->theme_url . 'js/jquery.min.js\' type=\'text/javascript\'%3E%3C/script%3E"));
	                    }
	            </script>
	    	';

	    	return $return_data;
	    }
	}
	
	// ----------------------------------------------------------------------------------
	
	//set the param vars
	private function _set_param()
	{
		// ----------------------------------------------------------------------------------
		//Vars for the PHP
		// ----------------------------------------------------------------------------------
		//Add base files, you have to do this when youre first form is in a {if}{/if} and is not visible. (The plugin will run even when the if is not true.), we handle this in the php script only
		//$this->add_base_files = ee()->TMPL->fetch_param('add_base_files') == 'yes' ? true : false ;
		
		// ----------------------------------------------------------------------------------
		//set the validation classes
		// ----------------------------------------------------------------------------------
		$this->_create_var('require_class', ee()->TMPL->fetch_param('require_class', 'validation_required'));
		$this->_create_var('email_class', ee()->TMPL->fetch_param('email_class', 'validation_email'));
		$this->_create_var('creditcard_class', ee()->TMPL->fetch_param('creditcard_class', 'validation_creditcard'));
		$this->_create_var('group_class', ee()->TMPL->fetch_param('group_class', 'validation_group'));
		$this->_create_var('min_class', ee()->TMPL->fetch_param('min_class', 'validation_min'));
		$this->_create_var('max_class', ee()->TMPL->fetch_param('max_class', 'validation_max'));
		$this->_create_var('min_size_class', ee()->TMPL->fetch_param('min_size_class', 'validation_min_size'));
		$this->_create_var('max_size_class', ee()->TMPL->fetch_param('max_size_class', 'validation_max_size'));
		$this->_create_var('integer_class', ee()->TMPL->fetch_param('integer_class', 'validation_integer'));
		$this->_create_var('number_class', ee()->TMPL->fetch_param('number_class', 'validation_number'));
		$this->_create_var('phone_class', ee()->TMPL->fetch_param('phone_class', 'validation_phone'));
		$this->_create_var('url_class', ee()->TMPL->fetch_param('url_class', 'validation_url'));
		$this->_create_var('date_class', ee()->TMPL->fetch_param('date_class', 'validation_date'));
		$this->_create_var('ipv4_class', ee()->TMPL->fetch_param('ipv4_class', 'validation_ipv4'));
		$this->_create_var('equals_class', ee()->TMPL->fetch_param('equals_class', 'validation_equals'));
		$this->_create_var('min_checkbox_class', ee()->TMPL->fetch_param('min_checkbox_class', 'validation_min_checkbox'));
		$this->_create_var('max_checkbox_class', ee()->TMPL->fetch_param('max_checkbox_class', 'validation_max_checkbox'));
		$this->_create_var('condition_class', ee()->TMPL->fetch_param('condition_class', 'validation_condition'));
		$this->_create_var('past_class', ee()->TMPL->fetch_param('past_class', 'validation_past'));
		$this->_create_var('future_class', ee()->TMPL->fetch_param('future_class', 'validation_future'));
		
		// ----------------------------------------------------------------------------------
		//Vars for the JS scripts
		// ----------------------------------------------------------------------------------
		
		//set the promt_position - Where should the prompt show?
		switch(ee()->TMPL->fetch_param('promt_position'))
		{
			case 'top-left' : $promt_position = 'topLeft'; break;
			case 'top-right' : $promt_position = 'topRight'; break;
			case 'bottom-left' : $promt_position = 'bottomLeft'; break;
			case 'center-right' : $promt_position = 'centerRight'; break;
			case 'bottom-right' : $promt_position = 'bottomRight'; break;
			default : $promt_position = 'topRight'; break;
		}
		$this->_create_var('promt_position', $promt_position);
		
		//set the class
		$selector = ee()->TMPL->fetch_param('selector', '');
		$this->_create_var('selector', $selector);

		//set the element where the input is inside
		$input_element_wrapper = ee()->TMPL->fetch_param('input_element_wrapper', '.control-group');
		$this->_create_var('input_element_wrapper', $input_element_wrapper);
		
		//set the scroll - Determines if we should scroll the page to the first error, defaults to true.
		$scroll = ee()->TMPL->fetch_param('scroll') == 'no' ? 'false' : 'true' ;
		$this->_create_var('scroll', $scroll);
		
		//Determines if the prompt should hide itself automatically after a set period. Defaults to false.
		$auto_hide_prompt = ee()->TMPL->fetch_param('auto_hide_prompt') == 'yes' ? 'true' : 'false' ;
		$this->_create_var('auto_hide_prompt', $auto_hide_prompt);
		
		//Sets the number of ms that the prompt should appear for if autoHidePrompt is set to true. Defaults to 10000.
		$auto_hide_delay = ee()->TMPL->fetch_param('auto_hide_delay', '10000');
		$this->_create_var('auto_hide_delay', $auto_hide_delay);

		//If set to true, turns Ajax form validation logic on. Defaults to false. Form validation takes place when the validate() action is called or when the form is submitted.
		$ajax_form_validation = ee()->TMPL->fetch_param('ajax_form_validation') == 'yes' ? 'true' : 'false' ;
		$this->_create_var('ajax_form_validation', $ajax_form_validation);

		//If set, the ajax submit validation will use this url instead of the form action
		$ajax_form_validation_url = ee()->TMPL->fetch_param('ajax_form_validation_url', '');
		$this->_create_var('ajax_form_validation_url', $ajax_form_validation_url);
		
		//HTTP method used for ajax validation, defaults to 'get', can be set to 'post'
		$ajax_form_validation_method = ee()->TMPL->fetch_param('ajax_form_validation_method', 'get');
		$this->_create_var('ajax_form_validation_method', $ajax_form_validation_method);
		
		//set the binded var - If set to false, it remove blur events and only validate on submit.
		$binded = ee()->TMPL->fetch_param('binded') == 'no' ? 'false' : 'true' ;
		$this->_create_var('binded', $binded);
		
		//Only display the first incorrect validation message instead of normally stacking it. It will follows the validation hierarchy you used in the input and only show the first error.
		$showOneMessage = ee()->TMPL->fetch_param('show_one_message') == 'yes' ? 'true' : 'false' ;
		$this->_create_var('show_one_message', $showOneMessage);

		//do we need to place the validation classes or not (eg: DevDemon Forms)
		$add_class = ee()->TMPL->fetch_param('add_class') == 'no' ? 'false' : 'true' ;
		$this->_create_var('add_class', $add_class);
	}

	// ----------------------------------------------------------------------------------
	
	//create a validation rule
	private function _create_var($param_name, $default_value = '', $force_default_value = true)
	{
		$this->_validation_rules[] = array(
			'name'					=> $param_name,
			'value' 				=> ee()->TMPL->fetch_param($param_name) != '' ? str_replace('.','',ee()->TMPL->fetch_param($param_name)) : $default_value,
			'force_default_value'	=> $force_default_value,
			'default_value'			=> $default_value
		);
	}
	
	// ----------------------------------------------------------------------------------
	
	//set the vars for the first time
	private function _init_js_vars()
	{
		//add newline for dev
		$nl = self::DEVELOPMENT ? "\n" : '' ;		
	
		$js_vars = '';
		foreach($this->_validation_rules as $rules)
		{
			$js_vars .= 'JS_FORMS.'.$rules['name'].' = [];'.$nl;
		}
		return $js_vars;
	}
	
	// ----------------------------------------------------------------------------------
	
	//Add the values for the js vars
	private function _insert_js_vars()
	{
		//add newline for dev
		$nl = self::DEVELOPMENT ? "\n" : '' ;		
	
		$js_vars = '';
		foreach($this->_validation_rules as $rules)
		{
			if($rules['force_default_value']) 
			{
				$value = $rules['default_value'] == 'false' || $rules['default_value'] == 'true'  ? $rules['default_value'] : '"'.$rules['default_value'].'"' ;
				$js_vars .= 'JS_FORMS.'.$rules['name'].'['.($this->caller_id-1).'] = '.$value.';'.$nl;
			}
			else
			{
				$value = $rules['value'] == 'false' || $rules['value'] == 'true' ? $rules['value'] : '"'.$rules['value'].'"' ;
				$js_vars .= 'JS_FORMS.'.$rules['name'].'['.($this->caller_id-1).'] = '.$value.';'.$nl;
			}	
		}
		return $js_vars;
	}
	
	// ----------------------------------------------------------------------------------
	
	//add call timer
	private function _add_call_timer()
	{
		//set the time of calling inthe session
		if(ee()->session->userdata('forms_js_validation_call_times') == '')
		{
			ee()->session->userdata['forms_js_validation_call_times'] = 1;
		}
		else
		{
			ee()->session->userdata['forms_js_validation_call_times'] = ee()->session->userdata('forms_js_validation_call_times') + 1;
		}
		
		//and if the add_base_files="yes" than set this to 1 to avoid problems when the first is not loaded
		/*if($this->add_base_files)
		{
			$this->caller_id = 1;
		}
		else
		{*/
		$this->caller_id = ee()->session->userdata('forms_js_validation_call_times');	
		/*}*/
	}
	
	// ----------------------------------------------------------------------------------
	
	//compress js
	private function _compress_js($files)
	{
		require 'Minifier.php';

		$out = $this->theme_path . 'js/forms_js_validation.min.js';
		$script = '';
		foreach($files as $file)
		{
			$script .= Minifier::minify(file_get_contents($file))."\n";
		}

		file_put_contents($out, $script);	
	}
	
	// ----------------------------------------------------------------------------------
	
	//  Plugin Usage
	// ----------------------------------------------------------------------------------

	// This function describes how the plugin is used.
	//  Make sure and use output buffering

	function usage()
	{
		ob_start();
		?>
		
		This little plugin will add JavaScript validation to exisiting forms. No more hacking in  the JS files, just one plugin witch will handle all the stuff for you. 
		It is compatible with all kind of forms, even with the form module from DevDemon.
	
		=============================
		The Tag
		=============================
        {exp:forms_js_validation}
		
        More info on http://reinos.nl/add-ons/forms-js-validation/docs		
		
		<?php
		$buffer = ob_get_contents();

		ob_end_clean();

		return $buffer;
	}
	// END
}