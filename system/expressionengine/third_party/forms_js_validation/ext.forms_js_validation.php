<?php
if ( ! defined('BASEPATH')) exit('No direct script access allowed');
 
/**
 * the config for the Forms JS Validation plugin
 *
 * @package             Forms JS validation for EE2
 * @author              Rein de Vries (info@reinos.nl)
 * @copyright           Copyright (c) 2013 Rein de Vries
 * @license             http://reinos.nl/add-ons/commercial-license
 * @link                http://reinos.nl/add-ons/forms-js-validation
 */

include(PATH_THIRD.'forms_js_validation/config.php');

class Forms_js_validation_ext {

    var $name           = FJV_NAME;
    var $version        = FJV_VERSION;
    var $description    = FJV_DESCRIPTION;
    var $settings_exist = 'y';
    var $docs_url       = FJV_DOCS;

    var $settings       = array();

    /**
     * Constructor
     *
     * @param   mixed   Settings array or empty string if none exist.
     */
    function __construct($settings = '')
    {
        //$this->EE =& get_instance();

        $this->settings = $settings;

        //lang 
        $this->lang = 'en';

        //theme url
        $theme_url = PATH_THEMES.'third_party/'.FJV_MAP.'/';

        //if older than EE 2.4
        if(!defined('URL_THIRD_THEMES'))
        {
            //set the theme url
            $theme_url = ee()->config->slash_item('theme_folder_url') != '' ? ee()->config->slash_item('theme_folder_url').'third_party/'.FJV_MAP.'/' : ee()->config->item('theme_folder_url') .'third_party/'.FJV_MAP.'/'; 
            
            //lets define the URL_THIRD_THEMES
            $this->theme_url = $theme_url;
        }
        else
        {
            //set the Theme dir
           $this->theme_url = URL_THIRD_THEMES.FJV_MAP.'/';
        }
    }

    // ----------------------------------------------------------------------

    /**
     * The settings
     *
     * @param   mixed   Settings array or empty string if none exist.
     */
    function settings()
    {
        $settings = array();

        //fetch the channels
        $channels_sql = ee()->db->select('channel_id, channel_title, channel_name')->from('channels')->get()->result_array();
        $channels = array();
        if(!empty($channels_sql))
        {
            foreach($channels_sql as $val)
            {
                $channels[$val['channel_id']] = $val['channel_title'];
            }
        }
        // Creates a text input with a default value of "EllisLab Brand Butter"
        $settings['channels']      = array('ms', $channels);

        $settings['css_location']      = array('i');

        // General pattern:
        //
        // $settings[variable_name] => array(type, options, default);
        //
        // variable_name: short name for the setting and the key for the language file variable
        // type:          i - text input, t - textarea, r - radio buttons, c - checkboxes, s - select, ms - multiselect
        // options:       can be string (i, t) or array (r, c, s, ms)
        // default:       array member, array of members, string, nothing

        return $settings;
    }

    // ----------------------------------------------------------------------

    /**
    * Activate Extension
    *
    * This function enters the extension into the exp_extensions table
    *
    * @see http://codeigniter.com/user_guide/database/index.html for
    * more information on the db class.
    *
    * @return void
    */
    function activate_extension()
    {
        $this->settings = array(
            'channels'   => '',
            'css_location' => '',
        );

        $data = array(
            'class'     => __CLASS__,
            'method'    => 'publish_form_entry_data',
            'hook'      => 'publish_form_entry_data',
            'settings'  => serialize($this->settings),
            'priority'  => 10,
            'version'   => $this->version,
            'enabled'   => 'y'
        );

        ee()->db->insert('extensions', $data);
    }

    // ----------------------------------------------------------------------

    /**
    * Update Extension
    *
    * This function performs any necessary db updates when the extension
    * page is visited
    *
    * @return  mixed   void on update / false if none
    */
    function update_extension($current = '')
    {
        if ($current == '' OR $current == $this->version)
        {
            return FALSE;
        }

        ee()->db->where('class', __CLASS__);
        ee()->db->update(
            'extensions',
            array('version' => $this->version)
        );
    }

    // ----------------------------------------------------------------------

    /**
    * Disable Extension
    *
    * This method removes information from the exp_extensions table
    *
    * @return void
    */
    function disable_extension()
    {
        ee()->db->where('class', __CLASS__);
        ee()->db->delete('extensions');
    }

    // ----------------------------------------------------------------------

    /**
    * Allows you add javascript to every Control Panel page.
    *
    * @return void
    */
    function publish_form_entry_data($data)
    {
       
        if(in_array(ee()->input->get('channel_id'), $this->settings['channels']))
        {
            //custom css
            $css = $this->settings['css_location'] != '' ? $this->settings['css_location'] : $this->theme_url.'css/validationEngine.jquery.css';
            $this->lang = $this->get_lang(ee()->session->userdata('language'));

            //js
            ee()->cp->add_to_head('<script type="text/javascript" src="'.$this->theme_url.'js/src/jquery.validationEngine.js"></script>');
            ee()->cp->add_to_head('<script type="text/javascript" src="'.$this->theme_url.'js/languages/jquery.validationEngine-'.$this->lang.'.js"></script>');
            ee()->cp->add_to_head('<script type="text/javascript" src="'.$this->theme_url.'js/forms_validation_cp.js"></script>');
            //css
            ee()->cp->add_to_head('<link rel="stylesheet" type="text/css" href="'.$css.'" />');
            ee()->cp->add_to_head('<link rel="stylesheet" type="text/css" href="'.$this->theme_url.'css/forms_js_validation_cp.css" />');
            ee()->cp->add_to_head('<link rel="stylesheet" type="text/css" href="'.$this->theme_url.'css/font-awesome.min.css" />');
        }

        return $data;
        //return ee()->extensions->last_call . $js;
    }

    function get_lang($lang = 'english')
    {
        $lang = ucfirst(strtolower($lang));
        $languageCodes = array_flip(array(
             "aa" => "Afar",
             "ab" => "Abkhazian",
             "ae" => "Avestan",
             "af" => "Afrikaans",
             "ak" => "Akan",
             "am" => "Amharic",
             "an" => "Aragonese",
             "ar" => "Arabic",
             "as" => "Assamese",
             "av" => "Avaric",
             "ay" => "Aymara",
             "az" => "Azerbaijani",
             "ba" => "Bashkir",
             "be" => "Belarusian",
             "bg" => "Bulgarian",
             "bh" => "Bihari",
             "bi" => "Bislama",
             "bm" => "Bambara",
             "bn" => "Bengali",
             "bo" => "Tibetan",
             "br" => "Breton",
             "bs" => "Bosnian",
             "ca" => "Catalan",
             "ce" => "Chechen",
             "ch" => "Chamorro",
             "co" => "Corsican",
             "cr" => "Cree",
             "cs" => "Czech",
             "cu" => "Church Slavic",
             "cv" => "Chuvash",
             "cy" => "Welsh",
             "da" => "Danish",
             "de" => "German",
             "dv" => "Divehi",
             "dz" => "Dzongkha",
             "ee" => "Ewe",
             "el" => "Greek",
             "en" => "English",
             "eo" => "Esperanto",
             "es" => "Spanish",
             "et" => "Estonian",
             "eu" => "Basque",
             "fa" => "Persian",
             "ff" => "Fulah",
             "fi" => "Finnish",
             "fj" => "Fijian",
             "fo" => "Faroese",
             "fr" => "French",
             "fy" => "Western Frisian",
             "ga" => "Irish",
             "gd" => "Scottish Gaelic",
             "gl" => "Galician",
             "gn" => "Guarani",
             "gu" => "Gujarati",
             "gv" => "Manx",
             "ha" => "Hausa",
             "he" => "Hebrew",
             "hi" => "Hindi",
             "ho" => "Hiri Motu",
             "hr" => "Croatian",
             "ht" => "Haitian",
             "hu" => "Hungarian",
             "hy" => "Armenian",
             "hz" => "Herero",
             "ia" => "Interlingua (International Auxiliary Language Association)",
             "id" => "Indonesian",
             "ie" => "Interlingue",
             "ig" => "Igbo",
             "ii" => "Sichuan Yi",
             "ik" => "Inupiaq",
             "io" => "Ido",
             "is" => "Icelandic",
             "it" => "Italian",
             "iu" => "Inuktitut",
             "ja" => "Japanese",
             "jv" => "Javanese",
             "ka" => "Georgian",
             "kg" => "Kongo",
             "ki" => "Kikuyu",
             "kj" => "Kwanyama",
             "kk" => "Kazakh",
             "kl" => "Kalaallisut",
             "km" => "Khmer",
             "kn" => "Kannada",
             "ko" => "Korean",
             "kr" => "Kanuri",
             "ks" => "Kashmiri",
             "ku" => "Kurdish",
             "kv" => "Komi",
             "kw" => "Cornish",
             "ky" => "Kirghiz",
             "la" => "Latin",
             "lb" => "Luxembourgish",
             "lg" => "Ganda",
             "li" => "Limburgish",
             "ln" => "Lingala",
             "lo" => "Lao",
             "lt" => "Lithuanian",
             "lu" => "Luba-Katanga",
             "lv" => "Latvian",
             "mg" => "Malagasy",
             "mh" => "Marshallese",
             "mi" => "Maori",
             "mk" => "Macedonian",
             "ml" => "Malayalam",
             "mn" => "Mongolian",
             "mr" => "Marathi",
             "ms" => "Malay",
             "mt" => "Maltese",
             "my" => "Burmese",
             "na" => "Nauru",
             "nb" => "Norwegian Bokmal",
             "nd" => "North Ndebele",
             "ne" => "Nepali",
             "ng" => "Ndonga",
             "nl" => "Dutch",
             "nn" => "Norwegian Nynorsk",
             "no" => "Norwegian",
             "nr" => "South Ndebele",
             "nv" => "Navajo",
             "ny" => "Chichewa",
             "oc" => "Occitan",
             "oj" => "Ojibwa",
             "om" => "Oromo",
             "or" => "Oriya",
             "os" => "Ossetian",
             "pa" => "Panjabi",
             "pi" => "Pali",
             "pl" => "Polish",
             "ps" => "Pashto",
             "pt" => "Portuguese",
             "qu" => "Quechua",
             "rm" => "Raeto-Romance",
             "rn" => "Kirundi",
             "ro" => "Romanian",
             "ru" => "Russian",
             "rw" => "Kinyarwanda",
             "sa" => "Sanskrit",
             "sc" => "Sardinian",
             "sd" => "Sindhi",
             "se" => "Northern Sami",
             "sg" => "Sango",
             "si" => "Sinhala",
             "sk" => "Slovak",
             "sl" => "Slovenian",
             "sm" => "Samoan",
             "sn" => "Shona",
             "so" => "Somali",
             "sq" => "Albanian",
             "sr" => "Serbian",
             "ss" => "Swati",
             "st" => "Southern Sotho",
             "su" => "Sundanese",
             "sv" => "Swedish",
             "sw" => "Swahili",
             "ta" => "Tamil",
             "te" => "Telugu",
             "tg" => "Tajik",
             "th" => "Thai",
             "ti" => "Tigrinya",
             "tk" => "Turkmen",
             "tl" => "Tagalog",
             "tn" => "Tswana",
             "to" => "Tonga",
             "tr" => "Turkish",
             "ts" => "Tsonga",
             "tt" => "Tatar",
             "tw" => "Twi",
             "ty" => "Tahitian",
             "ug" => "Uighur",
             "uk" => "Ukrainian",
             "ur" => "Urdu",
             "uz" => "Uzbek",
             "ve" => "Venda",
             "vi" => "Vietnamese",
             "vo" => "Volapuk",
             "wa" => "Walloon",
             "wo" => "Wolof",
             "xh" => "Xhosa",
             "yi" => "Yiddish",
             "yo" => "Yoruba",
             "za" => "Zhuang",
             "zh" => "Chinese",
             "zu" => "Zulu"
            ));

        return isset($languageCodes[$lang]) ? $languageCodes[$lang] : 'en';
    }

}
// END CLASS