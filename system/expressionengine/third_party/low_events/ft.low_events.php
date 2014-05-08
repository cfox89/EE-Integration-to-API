<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

// include config file
include(PATH_THIRD.'low_events/config.php');

/**
 * Low Events Fieldtype class
 *
 * @package        low_events
 * @author         Lodewijk Schutte <hi@gotolow.com>
 * @link           http://gotolow.com/addons/low-events
 * @copyright      Copyright (c) 2012-2013, Low
 */
class Low_events_ft extends EE_Fieldtype {

	// --------------------------------------------------------------------
	//  PROPERTIES
	// --------------------------------------------------------------------

	/**
	 * Info array
	 *
	 * @access     public
	 * @var        array
	 */
	public $info = array(
		'name'    => LOW_EVENTS_NAME,
		'version' => LOW_EVENTS_VERSION
	);

	/**
	 * Does fieldtype work in var pair
	 *
	 * @access     public
	 * @var        bool
	 */
	public $has_array_data = TRUE;

	/**
	 * BWF compat
	 */
	public $ep_better_workflow_use_save_method = TRUE;

	// --------------------------------------------------------------------

	/**
	 * Control Panel assets
	 *
	 * @var        array
	 * @access     private
	 */
	private $mcp_assets = array(
		'styles/jquery.timepicker.css',
		'styles/low_events.css',
		'scripts/jquery.timepicker.min.js',
		'scripts/low_events.js'
	);

	/**
	 * Default settings
	 *
	 * @access     private
	 * @var        array
	 */
	private $default_settings = array(
		'time_interval'    => '30',
		'default_duration' => '60',
		'default_all_day'  => 'n',
		'hide_end_date'    => 'n',
		'overwrite_entry_date'      => 'n',
		'overwrite_expiration_date' => 'n'
	);

	/**
	 * Shortcut to Low_date lib
	 *
	 * @access     private
	 * @var        Object
	 */
	private $date;

	/**
	 * Shortcut to Low_events_event_model lib
	 *
	 * @access     private
	 * @var        Object
	 */
	private $model;

	/**
	 * Site id shortcut
	 *
	 * @access     private
	 * @var        int
	 */
	private $site_id;

	/**
	 * Shortcut to today's date
	 *
	 * @access     private
	 * @var        string
	 */
	private $today;

	// --------------------------------------------------------------------
	//  METHODS
	// --------------------------------------------------------------------

	/**
	 * Constructor
	 *
	 * @return	void
	 */
	public function __construct()
	{
		parent::__construct();

		// --------------------------------------
		// Load stuff
		// --------------------------------------

		ee()->lang->loadfile(LOW_EVENTS_PACKAGE);
		ee()->load->add_package_path(PATH_THIRD.LOW_EVENTS_PACKAGE);
		ee()->load->helper(LOW_EVENTS_PACKAGE);
		ee()->load->helper('date');
		ee()->load->library(LOW_EVENTS_PACKAGE.'_model');
		ee()->load->library('Low_date');

		Low_events_model::load_models();

		// --------------------------------------
		// Shortcuts
		// --------------------------------------

		$this->date    =& ee()->low_date;
		$this->model   =& ee()->low_events_event_model;
		$this->site_id =  ee()->config->item('site_id');
		$this->today   =  date('Y-m-d');
	}

	// --------------------------------------------------------------------

	/**
	 * Display field settings
	 *
	 * @param	array	field settings
	 * @return	string
	 */
	public function display_settings($settings = array())
	{
		$rows = $this->_display_settings($settings);

		foreach ($rows AS $row)
		{
			ee()->table->add_row($row);
		}
	}

	/**
	 * Return array with html for setting forms
	 *
	 * @param	array	field settings
	 * @return	string
	 */
	private function _display_settings($settings = array())
	{
		// -------------------------------------
		//  Load language file
		// -------------------------------------

		ee()->lang->loadfile('low_events');

		// -------------------------------------
		//  Make sure we have all settings
		// -------------------------------------

		foreach ($this->default_settings AS $key => $val)
		{
			if ( ! array_key_exists($key, $settings))
			{
				$settings[$key] = $val;
			}
		}

		// -------------------------------------
		//  Build per-setting HTML
		// -------------------------------------

		$it = array();

		// Multiple selections?
		$it[] = array(
			lang('le_time_interval'),
			form_dropdown('time_interval', array(
				'15' => '15',
				'30' => '30',
				'60' => '60'
			), $settings['time_interval'])
		);

		// Multiple selections?
		$it[] = array(
			lang('le_default_duration'),
			form_dropdown('default_duration', array(
				'0' => '0',
				'30' => '30',
				'60' => '60',
				'120' => '120'
			), $settings['default_duration'])
		);

		// All Day checked by default?
		$it[] = array(
			lang('le_default_all_day'),
			low_yes_no('default_all_day', $settings['default_all_day'])
		);

		// Hide end date?
		$it[] = array(
			lang('le_hide_end_date'),
			low_yes_no('hide_end_date', $settings['hide_end_date'])
		);

		// Overwite entry date field?
		$it[] = array(
			lang('le_overwrite_entry_date'),
			low_yes_no('overwrite_entry_date', $settings['overwrite_entry_date'])
		);

		// Overwite expiration date field?
		$it[] = array(
			lang('le_overwrite_expiration_date'),
			low_yes_no('overwrite_expiration_date', $settings['overwrite_expiration_date'])
		);

		// Return the settings
		return $it;
	}

	/**
	 * Save field settings
	 *
	 * @access	   public
	 * @param	   array
	 * @return	   array
	 */
	public function save_settings($data)
	{
		$settings = array();

		foreach ($this->default_settings AS $key => $val)
		{
			if (($settings[$key] = ee()->input->post($key)) === FALSE)
			{
				$settings[$key] = $val;
			}
		}

		return $settings;
	}

	// --------------------------------------------------------------------

	/**
	 * Delete events
	 *
 	 * @access	   public
 	 * @param      array
	 * @return	   void
	 */
	public function delete($ids)
	{
		$this->model->delete($ids, 'entry_id');
	}

	// --------------------------------------------------------------------

	/**
	 * Display field in publish form
	 *
	 * @param	string	Current value for field
	 * @return	string	HTML containing input field
	 */
	public function display_field($data)
	{
		static $loaded;

		if ( ! $loaded)
		{
			$this->_load_assets();
			$loaded = TRUE;
		}

		// -------------------------------------
		//  What's the field name?
		// -------------------------------------

		$field_name = $this->field_name;

		// -------------------------------------
		//  All day?
		// -------------------------------------

		$default_all_day = isset($this->settings['default_all_day'])
			? $this->settings['default_all_day']
			: 'n';

		// -------------------------------------
		//  Get event dates details
		// -------------------------------------

		if ($data)
		{
			if ( ! is_array($data))
			{
				$data = str_replace('&quot;', '"', $data);
				$data = $this->_json_decode($data);
			}
		}
		else
		{
			$data = $this->model->empty_row();

			// Shortcut to now
			$now = $this->date->now();

			// Duration
			$duration = isset($this->settings['default_duration'])
			          ? $this->settings['default_duration']
			          : 60;

			// Time to round to
			$round = $this->settings['time_interval'] * 60;

			// Round to nearest time interval
			$start = $now - ($now % $round) + $round;
			$end   = $start + ($duration * 60);

			// Initiate data
			$data['start_date'] = date('Y-m-d', $start);
			$data['end_date']   = date('Y-m-d', $end);

			$data['start_time'] = date('H:i', $start);
			$data['end_time']   = date('H:i', $end);

			$data['all_day']    = $default_all_day;
		}

		// Add field name to data
		$data['field_name'] = $field_name;

		// Make sure all_day is set
		if ( ! isset($data['all_day'])) $data['all_day'] = $default_all_day;

		// Convert to 12h clock if needed
		if (($fmt = ee()->config->item('time_format')) == 'us')
		{
			$data['start_time'] = $this->_time_to_12($data['start_time'], $data['start_date']);
			$data['end_time'] = $this->_time_to_12($data['end_time'], $data['end_date']);
		}

		// -------------------------------------
		//  Hide the end date?
		// -------------------------------------

		$data['hide_end_date'] = (@$this->settings['hide_end_date'] == 'y');

		// -------------------------------------
		//  Add some settings to data
		// -------------------------------------

		$data['field_id'] = $this->settings['field_id'];
		$data['data'] = array(
			'time-format'   => $fmt,
			'time-interval' => $this->settings['time_interval'],
			'lang-decimal'  => lang('le_decimal'),
			'lang-mins'     => lang('le_mins'),
			'lang-hr'       => lang('le_hr'),
			'lang-hrs'      => lang('le_hrs')
		);

		// -------------------------------------
		//  Build date picker interface
		// -------------------------------------

		$it = ee()->load->view('ft_events', $data, TRUE);

		return $it;
	}

	// --------------------------------------------------------------------

	/**
	 * Make sure given data is correct
	 *
	 * @access     private
	 * @param      array
	 * @return     array
	 */
	private function _prep_data($data)
	{
		// -------------------------------------
		// Merge settings
		// -------------------------------------

		if ( ! empty($this->settings))
		{
			$this->settings = array_merge($this->default_settings, $this->settings);
		}

		// -------------------------------------
		// If no data exists, bail out
		// -------------------------------------

		if (empty($data)) return;

		// -------------------------------------
		// If data isn't an array decode it
		// -------------------------------------

		if ( ! is_array($data))
		{
			$data = $this->_json_decode($data);
		}

		// -------------------------------------
		// Check all_day, remove times if enabled
		// -------------------------------------

		if (@$data['all_day'] == 'y')
		{
			$data['start_time'] = $data['end_time'] = NULL;
		}

		// -------------------------------------
		// Set to 'n' in other cases
		// -------------------------------------

		else
		{
			$data['all_day'] = 'n';

			// Set end_time to start_time if not given
			if ($data['end_time'] === '')
			{
				$data['end_time'] = $data['start_time'];
			}

			// Convert to 24h clock
			if (ee()->config->item('time_format') == 'us')
			{
				$data['start_time'] = $this->_time_to_24($data['start_time'], $data['start_date']);
				$data['end_time'] = $this->_time_to_24($data['end_time'], $data['end_date']);
			}
		}

		return $data;
	}

	// --------------------------------------------------------------------

	/**
	 * Validate dates for saving
	 *
	 * @access	   public
	 * @param	   mixed
	 * @return	   mixed
	 */
	public function validate($data)
	{
		// Events field wasn't in the form
		if (is_null($data)) return TRUE;

		// Prep the data
		$data = $this->_prep_data($data);

		// Initiate error message array
		$errors = array();

		// -------------------------------------
		// Check if dates are valid
		// -------------------------------------

		if ( ! $this->_is_date($data['start_date']))
		{
			$errors[] = lang('start_date_invalid');
		}

		if ( ! $this->_is_date($data['end_date']))
		{
			$errors[] = lang('end_date_invalid');
		}

		// -------------------------------------
		// Check if times are valid
		// -------------------------------------

		if ($data['all_day'] == 'n')
		{
			if ( ! $this->_is_time($data['start_time']))
			{
				$errors[] = lang('start_time_invalid');
			}

			if ( ! $this->_is_time($data['end_time']))
			{
				$errors[] = lang('end_time_invalid');
			}
		}

		// -------------------------------------
		// If dates and times are valid,
		// Check if end time is after start time
		// -------------------------------------

		if ( ! $errors)
		{
			$start = strtotime($data['start_date'].' '.$data['start_time']);
			$end = strtotime($data['end_date'].' '.$data['end_time']);

			if ($end < $start)
			{
				$errors[] = lang('event_ends_before_start');
			}
		}

		// -------------------------------------
		// Return error messages or TRUE if none
		// -------------------------------------

		return ($errors) ? implode("<br />", $errors) : TRUE;
	}

	/**
	 * Rough validation for date
	 */
	private function _is_date($str)
	{
		return preg_match('/^(19|20)\d\d-(0[1-9]|1[012])-(0[1-9]|[12][0-9]|3[01])$/', $str);
	}

	/**
	 * Rough validation for time
	 */
	private function _is_time($str)
	{
		return preg_match('/^(?:0?[0-9]|1[0-9]|2[0-3]):[0-5][0-9]$/', $str);
	}

	// --------------------------------------------------------------------

	/**
	 * Return prepped field data to save
	 *
	 * @param	mixed	Posted data
	 * @return	string	Data to save
	 */
	public function save($data = '')
	{
		// Return empty
		if (empty($data)) return $data;

		// Prep it
		$data = $this->_prep_data($data);

		// Return json coded string
		$data = json_encode($data);

		return $data;
	}

	/**
	 * Insert/update row into low_events table
	 *
	 * @access     public
	 * @param      mixed     Posted data
	 * @return     void
	 */
	public function post_save($data)
	{
		// Return null if null
		if (empty($data)) return;

		$data = $this->_prep_data($data);

		// Add IDs to the data array
		$data['entry_id'] = $this->settings['entry_id'];
		$data['field_id'] = $this->settings['field_id'];
		$data['site_id']  = $this->site_id;

		// Check if there's an existing entry
		ee()->db->where('field_id', $data['field_id']);
		$event = $this->model->get_one($data['entry_id'], 'entry_id');

		// If so, update
		if ($event)
		{
			$this->model->update($event['event_id'], $data);
		}
		// Or else just insert the new event
		else
		{
			$this->model->insert($data);
		}

		// Get dates from data
		list($start, $end) = low_prep_dates($data);

		// Possibly update entry date
		if (@$this->settings['overwrite_entry_date'] == 'y')
		{
			// Convert to timestamp
			$start = strtotime($start);

			// Update entry in DB
			ee()->db->update('channel_titles', array(
				'entry_date'      => $start,
				'year'            => date('Y', $start),
				'month'           => date('m', $start),
				'day'             => date('d', $start)
			), "entry_id = '{$data['entry_id']}'");
		}

		// Possibly update expiration date
		if (@$this->settings['overwrite_expiration_date'] == 'y')
		{
			// Convert to timestamp
			$end = strtotime($end);

			// Update entry in DB
			ee()->db->update(
				'channel_titles',
				array('expiration_date' => $end),
				"entry_id = '{$data['entry_id']}'"
			);
		}
	}

	// --------------------------------------------------------------------

	/**
	 * Pre-process the given data
	 */
	public function pre_process($data)
	{
		return $this->_prep_data($data);
	}

	/**
	* Display tag in template
	*
	* @access      public
	* @param       string    Current value for field
	* @param       array     Tag parameters
	* @param       bool
	* @return      string
	*/
	public function replace_tag($data, $params = array(), $tagdata = FALSE)
	{
		return '';
	}

	// --------------------------------------------------------------------

	/**
	 * Return a formatted date
	 */
	private function _replace_date($date, $params)
	{
		$this->date->init($date);
		if ( ! isset($params['format'])) $params['format'] = '%Y-%m-%d';
		return $this->date->ee_format($params['format'], @$params['lang']);
	}

	/**
	* Display {var_name:start_date format="foo" lang="dutch"}
	*
	* @param       string    Current value for field
	* @param       array     Tag parameters
	* @return      string
	*/
	public function replace_start_date($data, $params)
	{
		$time = ($data['all_day'] == 'y') ? '00:00' : $data['start_time'];
		return $this->_replace_date($data['start_date'].' '.$time, $params);
	}

	/**
	* Display {var_name:end_date format="foo"}
	*
	* @param       string    Current value for field
	* @param       array     Tag parameters
	* @return      string
	*/
	public function replace_end_date($data, $params)
	{
		$time = ($data['all_day'] == 'y') ? '23:59' : $data['end_time'];
		return $this->_replace_date($data['end_date'].' '.$time, $params);
	}

	// --------------------------------------------------------------------

	/**
	 * Return a time format based on given data
	 */
	private function _replace_time($data, $which, $format = FALSE)
	{
		$this->date->init($data["{$which}_date"], $data["{$which}_time"]);
		if ( ! $format) $format = '%H:%i';
		return $this->date->ee_format($format, @$params['lang']);
	}

	/**
	 * start time
	 */
	public function replace_start_time($data, $params)
	{
		return $data['start_time']
		     ? $this->_replace_time($data, 'start', @$params['format'])
		     : '';
	}

	/**
	 * end time
	 */
	public function replace_end_time($data, $params)
	{
		return $data['end_time']
		     ? $this->_replace_time($data, 'end', @$params['format'])
		     : '';
	}

	/**
	 * start timestamp
	 */
	public function replace_start_stamp($data, $params)
	{
		return $this->_get_start_stamp($data);
	}

	/**
	 * end timestamp
	 */
	public function replace_end_stamp($data, $params)
	{
		return $this->_get_end_stamp($data);
	}

	// --------------------------------------------------------------------

	/**
	 * all day
	 */
	public function replace_all_day($data, $params)
	{
		return ($data['all_day'] == 'y') ? 'y' : '';
	}

	/**
	 * one day
	 */
	public function replace_one_day($data, $params)
	{
		return ($data['start_date'] == $data['end_date']) ? 'y' : '';
	}

	/**
	 * Duration
	 */
	public function replace_duration($data, $params)
	{
		// If event is all day, set the times to span the whole day
		$start = $this->_get_start_stamp($data);
		$end = $this->_get_end_stamp($data);
		$now = $this->date->now();

		switch (@$params['until'])
		{
			case 'start':
				$from = ($start > $now) ? $now : $start;
				$to   = ($start > $now) ? $start : $now;
			break;

			case 'end':
				$from = ($end > $now) ? $now : $end;
				$to   = ($end > $now) ? $end : $now;
			break;

			default:
				$from = $start;
				$to   = $end;
		}

		// Use EE's native format_timespan function to get the duration string
		return timespan($from, $to);
	}

	// --------------------------------------------------------------------

	/**
	 * Relative start date
	 */
	public function replace_relative_start_date($data, $params)
	{
		return $this->_relative_date($data, $params, 'start');
	}

	/**
	 * Relative end date
	 */
	public function replace_relative_end_date($data, $params)
	{
		return $this->_relative_date($data, $params, 'end');
	}

	/**
	 * Relative date
	 */
	private function _relative_date($data, $params, $which)
	{
		if (version_compare(APP_VER, '2.8.0', '>='))
		{
			$method = "_get_{$which}_stamp";
			return ee()->TMPL->process_date($this->$method($data), $params, TRUE, FALSE);
		}
		else
		{
			$params['until'] = $which;
			return $this->replace_duration($data, $params);
		}
	}

	// --------------------------------------------------------------------

	/**
	 * Is passed?
	 */
	public function replace_passed($data, $params)
	{
		return ($this->date->now() > $this->_get_end_stamp($data)) ? 'y' : '';
	}

	/**
	 * Is upcoming?
	 */
	public function replace_upcoming($data, $params)
	{
		return ($this->date->now() < $this->_get_start_stamp($data)) ? 'y' : '';
	}

	/**
	 * Is active?
	 */
	public function replace_active($data, $params)
	{
		$start = $this->_get_start_stamp($data);
		$end = $this->_get_end_stamp($data);

		return ($this->date->now() >= $start && $this->date->now() <= $end) ? 'y' : '';
	}

	// --------------------------------------------------------------------

	/**
	 * Display 'y' or '' depending if entry is the first in [unit]
	 *
	 * @access     public
	 * @param      array
	 * @param      array
	 * @return     string
	 */
	public function replace_first($data, $params)
	{
		// Remember last time?
		static $last = array();

		// Unit
		$unit = (isset($params['unit']) && in_array($params['unit'], $this->date->units()))
		      ? $params['unit']
		      : 'month';

		// The field id
		$fid = $this->settings['field_name'];

		// Initiate return value
		$it = '';

		// Init so we can calculate
		$this->date->init($data['start_date']);

		// Get the value for this start date according to unit
		switch ($unit)
		{
			case 'day':
				$val = $this->date->date();
			break;

			case 'week':
				$val = $this->date->week_url();
			break;

			case 'month':
				$val = $this->date->month_url();
			break;

			case 'year':
				$val = $this->date->year();
			break;
		}

		// If it's a different value than the last one, header is yes!
		if ( ! isset($last[$fid][$unit]) || $last[$fid][$unit] != $val)
		{
			$last[$fid][$unit] = $val;
			$it = 'y';
		}

		// Please
		return $it;
	}

	// --------------------------------------------------------------------

	/**
	 * Display 'y' or '' depending if date is this date (now)
	 *
	 * @access     private
	 * @param      array
	 * @param      array
	 * @return     string
	 */
	private function _is_this($date, $unit = FALSE)
	{
		// Unit
		if ( ! in_array($unit, $this->date->units()))
		{
			$unit = 'month';
		}

		// Init so we can calculate
		$this->date->init($date);

		// Do the same for now
		$now = new Low_date;
		$now->init($this->today);

		// Get the value for this start date according to unit
		switch ($unit)
		{
			case 'day':
				$val = $this->date->date();
				$now = $now->date();
			break;

			case 'week':
				$val = $this->date->week_url();
				$now = $now->week_url();
			break;

			case 'month':
				$val = $this->date->month_url();
				$now = $now->month_url();
			break;

			case 'year':
				$val = $this->date->year();
				$now = $now->year();
			break;
		}

		return ($now == $val) ? 'y' : '';
	}

	/**
	 * Display 'y' or '' depending if entry is the first in [unit]
	 */
	public function replace_starts_this($data, $params)
	{
		return $this->_is_this($data['start_date'], @$params['unit']);
	}

	/**
	 * Display 'y' or '' depending if entry is the first in [unit]
	*/
	public function replace_ends_this($data, $params)
	{
		return $this->_is_this($data['end_date'], @$params['unit']);
	}

	/**
	 * Event starts this year?
	 */
	public function replace_starts_this_year($data)
	{
		return $this->_is_this($data['start_date'], 'year');
	}

	/**
	 * Event starts this month?
	 */
	public function replace_starts_this_month($data)
	{
		return $this->_is_this($data['start_date'], 'month');
	}

	/**
	 * Event starts this week?
	 */
	public function replace_starts_this_week($data)
	{
		return $this->_is_this($data['start_date'], 'week');
	}

	/**
	 * Event starts this day?
	 */
	public function replace_starts_this_day($data)
	{
		return $this->_is_this($data['start_date'], 'day');
	}

	/**
	 * Event ends this year?
	 */
	public function replace_ends_this_year($data)
	{
		return $this->_is_this($data['end_date'], 'year');
	}

	/**
	 * Event ends this month?
	 */
	public function replace_ends_this_month($data)
	{
		return $this->_is_this($data['end_date'], 'month');
	}

	/**
	 * Event ends this week?
	 */
	public function replace_ends_this_week($data)
	{
		return $this->_is_this($data['end_date'], 'week');
	}

	/**
	 * Event ends this day?
	 */
	public function replace_ends_this_day($data)
	{
		return $this->_is_this($data['end_date'], 'day');
	}

	// --------------------------------------------------------------------

	/**
	 * Get timestamp for start date
	 */
	private function _get_start_stamp($data)
	{
		if ($data['all_day'] == 'y')
		{
			$data['start_time'] = '00:00';
		}

		$start = new Low_date($data['start_date'], $data['start_time']);

		return $start->stamp();
	}

	/**
	 * Get timestamp for end date
	 */
	private function _get_end_stamp($data)
	{
		$mod = 0;

		if ($data['all_day'] == 'y')
		{
			$data['end_time'] = '23:59';
			$mod = 60;
		}

		$end = new Low_date($data['end_date'], $data['end_time']);

		return $end->stamp() + $mod;
	}

	/**
	 * Convert 24h time to 12h time
	 */
	private function _time_to_12($time, $date = '2000-01-01')
	{
		return date('g:i a', strtotime("{$date} {$time}:00"));
	}

	/**
	 * Convert 12h time to 24h time
	 */
	private function _time_to_24($time, $date = '2000-01-01')
	{
		return date('H:i', strtotime("{$date} {$time}"));
	}

	/**
	 * JSON Decode string, make sure an array is returned
	 *
	 * @access     private
	 * @param      string
	 * @return     array
	 */
	private function _json_decode($str)
	{
		return (array) json_decode($str, TRUE);
	}

	// --------------------------------------------------------------------

	/**
	 * Load assets: extra JS and CSS
	 *
	 * @access     private
	 * @return     void
	 */
	private function _load_assets()
	{
		ee()->cp->add_js_script(array('ui' => 'datepicker'));

		// -------------------------------------
		//  Define placeholder
		// -------------------------------------

		$header = $footer = array();

		// -------------------------------------
		//  Loop through assets
		// -------------------------------------

		$asset_url = ((defined('URL_THIRD_THEMES'))
		           ? URL_THIRD_THEMES
		           : ee()->config->item('theme_folder_url') . 'third_party/')
		           . LOW_EVENTS_PACKAGE . '/';

		foreach ($this->mcp_assets AS $file)
		{
			// location on server
			$file_url = $asset_url.$file.'?v='.LOW_EVENTS_VERSION;

			if (substr($file, -3) == 'css')
			{
				$header[] = '<link type="text/css" rel="stylesheet" href="'.$file_url.'" />';
			}
			elseif (substr($file, -2) == 'js')
			{
				$footer[] = '<script type="text/javascript" src="'.$file_url.'"></script>';
			}
		}

		// -------------------------------------
		//  Add combined assets to header/footer
		// -------------------------------------

		if ($header) ee()->cp->add_to_head(implode(NL, $header));
		if ($footer) ee()->cp->add_to_foot(implode(NL, $footer));
	}

	// --------------------------------------------------------------------

	/**
	 * Simple Zenbu Support for Low Events
	 */
	public function zenbu_display($entry_id, $channel_id, $data)
	{
		$data = $this->_prep_data($data);

		if ($data['all_day'] == 'y')
		{
			$start = $data['start_date'];
			$end   = $data['end_date'];
		}
		else
		{
			$start = $data['start_date'].'&nbsp;'.$data['start_time'];
			$end   = $data['end_date'].'&nbsp;'.$data['end_time'];
		}

		return sprintf('%s &ndash; %s', $start, $end);
	}

}
// END Low_events_ft class