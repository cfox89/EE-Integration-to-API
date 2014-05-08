<?php
/**
 * the config for the Forms JS Validation plugin
 *
 * @package             Forms JS validation for EE2
 * @author              Rein de Vries (info@reinos.nl)
 * @copyright           Copyright (c) 2013 Rein de Vries
 * @license  			http://reinos.nl/add-ons/commercial-license
 * @link                http://reinos.nl/add-ons/forms-js-validation
 */

if ( ! defined('FJV_NAME'))
{
	define('FJV_NAME', 'Forms JS validation');
	define('FJV_CLASS', 'forms_js_validation');
	define('FJV_MAP', 'forms_js_validation');
	define('FJV_VERSION', '1.6.2');
	define('FJV_DESCRIPTION', 'Add js validation to exisiting forms');
	define('FJV_DOCS', 'http://reinos.nl/add-ons/forms-js-validation');
	define('FJV_DEBUG', false);
}

$config['name'] = FJV_NAME;
$config['version'] = FJV_VERSION;


//load compat file
require_once(PATH_THIRD.FJV_MAP.'/compat.php');

/* End of file config.php */
/* Location: /system/expressionengine/third_party/forms_js_validation/config.php */
