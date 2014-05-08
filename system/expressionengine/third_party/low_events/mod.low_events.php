<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

// include config file
include(PATH_THIRD.'low_events/config.php');

/**
 * Low Events Module class
 *
 * @package        low_events
 * @author         Lodewijk Schutte <hi@gotolow.com>
 * @link           http://gotolow.com/addons/low-events
 * @copyright      Copyright (c) 2012-2013, Low
 */
class Low_events {

	// --------------------------------------------------------------------
	// PROPERTIES
	// --------------------------------------------------------------------

	/**
	 * Custom channel:entries params
	 *
	 * @access     private
	 * @var        array
	 */
	private $params = array(
		'date',
		'date_from',
		'date_to',
		'unit',
		'show_active',
		'show_passed',
		'show_upcoming'
	);

	/**
	 * Date formats for variables
	 *
	 * @access     private
	 * @var        array
	 */
	private $formats = array(
		'%F' => 'month',
		'%m' => 'month_num',
		'%M' => 'month_short',
		'%n' => 'month_num_short',
		'%Y' => 'year',
		'%y' => 'year_short'
	);

	/**
	 * Weekdays and their ISO numeric representation
	 *
	 * @access     private
	 * @var        array
	 */
	private $weekdays = array(
		1 => 'monday',
		2 => 'tuesday',
		3 => 'wednesday',
		4 => 'thursday',
		5 => 'friday',
		6 => 'saturday',
		7 => 'sunday'
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
	 * channel fields shortcut/cache
	 *
	 * @access     private
	 * @var        array
	 */
	private $fields;

	/**
	 * Shortcut to today's date
	 *
	 * @access     private
	 * @var        string
	 */
	private $today;

	/**
	 * For custom units, date from
	 *
	 * @access     private
	 * @var        string
	 */
	private $date_from;

	/**
	 * For custom units, date to
	 *
	 * @access     private
	 * @var        string
	 */
	private $date_to;

	// --------------------------------------------------------------------
	// PUBLIC METHODS
	// --------------------------------------------------------------------

	/**
	 * Constructor
	 *
	 * @access     public
	 * @return     void
	 */
	public function __construct()
	{
		// --------------------------------------
		// Load stuff
		// --------------------------------------

		ee()->load->add_package_path(PATH_THIRD.LOW_EVENTS_PACKAGE);
		ee()->load->helper(LOW_EVENTS_PACKAGE);
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

		// Make sure fields are present
		$this->_get_channel_fields();
	}

	// --------------------------------------------------------------------

	/**
	 * Show events
	 *
	 * @access      public
	 * @param       bool
	 * @return      string
	 */
	public function entries($ids_only = FALSE)
	{
		// --------------------------------------
		// Initiate the date to work with
		// --------------------------------------

		$this->_init_date();

		// --------------------------------------
		// Prep no_results to avoid conflicts
		// --------------------------------------

		$this->_prep_no_results();

		// --------------------------------------
		// Determine unit to display
		// --------------------------------------

		// Unit parameter defaults to what was given in date param
		$unit = $this->_get_param('unit', $this->date->given());

		// Log it for debugging
		$this->_log("Unit: {$unit}");

		// --------------------------------------
		// Initiate rows
		// --------------------------------------

		$rows = array();

		// --------------------------------------
		// Initiate range array (faux object)
		// --------------------------------------

		$range = $this->_get_range();

		// --------------------------------------
		// Get the rows depending on unit
		// --------------------------------------

		switch ($unit)
		{
			case 'year':
				list($range['start_date'], $range['end_date']) = $this->date->get_year_range();
			break;

			case 'month':
				list($range['start_date'], $range['end_date']) = $this->date->get_month_range();
			break;

			case 'week':
				list($range['start_date'], $range['end_date']) = $this->date->get_week_range();
			break;

			case 'day':
				$range['start_date'] = $range['end_date'] = $this->date->date();
			break;

			case 'custom':
				$range['start_date'] = $this->date->from();
				$range['end_date']   = $this->date->to();
			break;

			case 'passed':
				$range['end_date'] = $this->date->date();
				$range['end_time'] = $this->date->time();
				//$range['passed']   = TRUE;
			break;

			default:
				$unit = 'upcoming';
				$range['start_date'] = $this->date->date();
				//$range['start_time'] = $show_passed ? NULL : $this->date->time();
			break;
		}

		// --------------------------------------
		// Get the rows depending on unit
		// --------------------------------------

		$rows = $this->model->get_range($range);

		// --------------------------------------
		// Optionally sort by end date instead of start date
		// Do it here so caching of rows can stay intact
		// --------------------------------------

		if ($orderby = $this->_get_param('orderby'))
		{
			if (preg_match('/^low_events:(start|end)$/', $orderby, $match))
			{
				// Sort by end with php
				if ($match[1] == 'end') usort($rows, 'low_sort_by_end');

				// Get rid of orderby param
				unset(ee()->TMPL->tagparams['orderby']);
			}
		}

		// Get ids only
		$entry_ids = $rows ? low_flatten_results($rows, 'entry_id') : array();

		// Clean up
		unset($rows);

		// --------------------------------------
		// Check for show_pages parameter
		// --------------------------------------

		if ($show_pages = $this->_get_param('show_pages'))
		{
			// Get all page IDs
			$page_ids = $this->_get_page_ids();

			switch ($show_pages)
			{
				case 'no':
					// Filter out page ids
					$entry_ids = array_diff($entry_ids, $page_ids);
				break;

				case 'only':
					// Only page ids
					$entry_ids = array_intersect($entry_ids, $page_ids);
				break;
			}
		}

		// --------------------------------------
		// Check for existing entry_id parameter
		// --------------------------------------

		if ($entry_id_param = $this->_get_param('entry_id'))
		{
			$this->_log('entry_id parameter found, filtering event ids accordingly');

			// Get the parameter value
			list($ids, $in) = low_explode_param($entry_id_param);

			// Either remove $ids from $entry_ids OR limit $entry_ids to $ids
			$method = $in ? 'array_intersect' : 'array_diff';

			// Get list of entry ids that should be listed
			$entry_ids = $method($entry_ids, $ids);
		}

		// --------------------------------------
		// If IDs only, return those
		// --------------------------------------

		if ($ids_only)
		{
			return $entry_ids;
		}

		// --------------------------------------
		// If there are no entry_ids, return nothin
		// --------------------------------------

		if (empty($entry_ids))
		{
			$this->_log('No event ids found, returning no results');
			return ee()->TMPL->no_results();
		}

		// --------------------------------------
		// set fixed_order / entry_id according to presence of orderby param
		// --------------------------------------

		$param = ($this->_get_param('orderby')) ? 'entry_id' : 'fixed_order';
		$param_val = implode('|', $entry_ids);

		$this->_log(sprintf('Setting %s="%s"', $param, $param_val));
		ee()->TMPL->tagparams[$param] = $param_val;

		// --------------------------------------
		// Make sure the following params are set
		// --------------------------------------

		$set_params = array(
			'dynamic'  => 'no',
			'paginate' => 'bottom'
		);

		foreach ($set_params AS $key => $val)
		{
			if ( ! ee()->TMPL->fetch_param($key))
			{
				ee()->TMPL->tagparams[$key] = $val;
			}
		}

		// --------------------------------------
		// Let the Channel module do all the heavy lifting
		// --------------------------------------

		return $this->_channel_entries();
	}

	/**
	 * Show event IDs
	 *
	 * @access      public
	 * @return      string
	 */
	public function entry_ids()
	{
		// --------------------------------------
		// Get some parameters and check pair tag
		// --------------------------------------

		$pair       = ($tagdata = ee()->TMPL->tagdata) ? TRUE : FALSE;
		$no_results = ee()->TMPL->fetch_param('no_results');
		$separator  = ee()->TMPL->fetch_param('separator', '|');

		// --------------------------------------
		// Get ids and create single string from entry ids
		// --------------------------------------

		$entry_ids = $this->entries(TRUE);
		$entry_ids = empty($entry_ids) ? $no_results : implode($separator, $entry_ids);

		// --------------------------------------
		// Parse+return or just return, depending on tag pair or not
		// --------------------------------------

		if ($pair)
		{
			return ee()->TMPL->parse_variables_row($tagdata, array(
				'low_events:entry_ids' => $entry_ids
			));
		}
		else
		{
			return $entry_ids;
		}
	}

	// --------------------------------------------------------------------

	/**
	 * Get current/given date
	 *
	 * @access     public
	 * @return     string
	 */
	public function this_date()
	{
		return $this->_format();
	}

	/**
	 * Get next date
	 */
	public function next_date()
	{
		return $this->_format('add');
	}

	/**
	 * Get previous date
	 */
	public function prev_date()
	{
		return $this->_format('sub');
	}

	/**
	 * Return date in format
	 */
	private function _format($mod = FALSE)
	{
		// --------------------------------------
		// Initiate the date to work with
		// --------------------------------------

		$this->_init_date();

		// --------------------------------------
		// What are we going to display?
		// --------------------------------------

		$unit = ee()->TMPL->fetch_param('unit', $this->date->given());

		// --------------------------------------
		// Do we need to modify the date given
		// --------------------------------------

		if ($mod && $unit)
		{
			// Change to 1st of the month if unit is month,
			// to prevent mismatch by the end of the month
			if ($unit == 'month') $this->date->first_of_month();

			$this->date->$mod($unit);

			// Log it
			$this->_log("Modify date: {$mod} {$unit} to ".$this->date->date());
		}

		// --------------------------------------
		// Check in what format
		// --------------------------------------

		// Get format="" param
		$format = ee()->TMPL->fetch_param('format');

		// Check for year_format="", month_format="" or day_format=""
		$format = ee()->TMPL->fetch_param($unit.'_format', $format);

		// Get lang="" param
		$lang = ee()->TMPL->fetch_param('lang');

		if ( ! $format)
		{
			switch ($unit)
			{
				case 'week':
					$this->return_data = $this->date->week_url();
				break;

				case 'month':
					$this->return_data = $this->date->month_url();
				break;

				case 'year':
					$this->return_data = $this->date->year();
				break;

				default:
					$this->return_data = $this->date->date();
			}
		}
		else
		{
			$this->return_data = $this->date->ee_format($format, $lang);
		}

		return $this->return_data;
	}

	// --------------------------------------------------------------------

	/**
	 * Generate Calendar based on events
	 *
	 * @access      public
	 * @return      string
	 */
	public function calendar()
	{
		// --------------------------------------
		// Initiate the date to work with
		// --------------------------------------

		$this->_init_date();

		// Keep track of given date
		$given_date  = $this->date->date();
		$given_month = $this->date->month_url();
		$given_week  = ($this->date->given() == 'week') ? $this->date->week_url() : FALSE;

		// --------------------------------------
		// If a week is given, make sure the thursday
		// is in the same month, or else advance one month
		// --------------------------------------

		if ($given_week)
		{
			// Get the thursday
			$this->date->modify('+ 3 days');

			if ($given_month == $this->date->month_url())
			{
				// No probs, same month given, reset back to original given date
				$this->date->reset();
			}
			else
			{
				// Thursday is in next month, so set the date to that
				$given_date = $this->date->date();
				$given_month = $this->date->month_url();
			}
		}

		// Set date to first of the month
		$this->date->first_of_month();

		// Get next and previous month
		$next = new Low_date($this->date->date());
		$prev = new Low_date($this->date->date());
		$next->add('month');
		$prev->sub('month');

		// Days in month
		$dim = $this->date->days_in_month();

		// Day of the Week
		$dotw = $this->date->first_day_of_month();

		// Week of the month
		$wotm = 0;

		// --------------------------------------
		// Day to start the week
		// --------------------------------------

		$start_day = strtolower(ee()->TMPL->fetch_param('start_day'));

		// Force monday on non-existent weekday or if a week is given
		if ( ! in_array($start_day, $this->weekdays) || $given_week !== FALSE)
		{
			$start_day = 'monday';
		}

		$start_day = array_search($start_day, $this->weekdays);

		// --------------------------------------
		// Calculate number of leading days (prev month)
		// --------------------------------------

		if ($leading_days = (($dotw - $start_day) + 7) % 7)
		{
			$this->date->modify("- {$leading_days} days");
		}

		// Keep track of start date
		$start_date = $this->date->date();

		// Initiate weeks and weekdays arrays
		$weeks = $weekdays = $days = array();

		// Initiate day count
		$day_count = 0;

		// Add leading 0s to day number?
		$leading = (ee()->TMPL->fetch_param('leading_zeroes', 'no') == 'yes');

		// --------------------------------------
		// Language parameter
		// --------------------------------------

		$lang = ee()->TMPL->fetch_param('lang');

		// --------------------------------------
		// Populate weeks array
		// --------------------------------------

		while (TRUE)
		{
			// Initiate week
			if ( ! isset($weeks[$wotm]))
			{
				$weeks[$wotm] = array(
					'days'     => array(),
					'week_url' => $this->date->week_url(),
					'is_given_week' => ($given_week == $this->date->week_url()) ? 'y' : ''
				);
			}

			// Add the day row to the week
			$weeks[$wotm]['days'][] = array(
				'day_number'    => $leading ? $this->date->day() : intval($this->date->day()),
				'day_url'       => $this->date->day_url(),
				'day'           => $this->date->day_url(),
				'is_prev'       => ($this->date->month_url() == $prev->month_url()) ? 'y' : '',
				'is_next'       => ($this->date->month_url() == $next->month_url()) ? 'y' : '',
				'is_current'    => ($this->date->month_url() == $given_month) ? 'y' : '',
				'is_given'      => ($this->date->given() == 'day' && $this->date->date() == $given_date) ? 'y' : '',
				'is_today'      => ($this->date->date() == $this->today) ? 'y' : '',
				'events_on_day' => 0
			);

			// Populate weekdays
			if ( ! $wotm)
			{
				$weekdays[] = array(
					'weekday' => $this->date->ee_format('%l', $lang),
					'weekday_short' => $this->date->ee_format('%D', $lang),
					'weekday_1' => substr($this->date->ee_format('%D', $lang), 0, 1)
				);
			}

			// Advance by one day
			$this->date->add('day');

			// if days is divisible by 7, a week is done
			if ($done = ! (++$day_count % 7))
			{
				// If we're caught up with the next month too, exit the loop
				if ($this->date->month_url() == $next->month_url()) break;

				// Or else just increase the week of the month
				$wotm++;
			}
		}

		// End date
		$end_date = $this->date->date();
		$this->date->reset();

		$this->_log("Initiated calendar from {$start_date} to {$end_date}");

		// --------------------------------------
		// Get events for this calendar range
		// --------------------------------------

		// Initiate events
		$events = $entries = array();

		$range = $this->_get_range();
		$range['start_date'] = $start_date;
		$range['end_date']   = $end_date;

		$rows = $this->model->get_range($range);

		// Query the rest of the entry details if there are events present
		if ($entries = $this->_get_event_entries($rows))
		{
			foreach ($entries AS $row)
			{
				// Skip the ones not found in $entries
				if ($row['start_date'] == $row['end_date'])
				{
					$events[$row['start_date']][] = $row;
				}
				else
				{
					// Assign each day between start and end to events array
					$date = new Low_date($row['start_date']);

					while (($start = $date->date()) <= $row['end_date'])
					{
						$events[$start][] = $row;
						$date->add('day');
					}
				}
			}
		}
		else
		{
			// No events in this range
		}

		// Keep track of total events found
		$total_entries = count($entries);
		$total_days    = count($events);

		$this->_log("In this range: {$total_entries} entries, spanning {$total_days} days");

		// --------------------------------------
		// Assign entry count to days
		// --------------------------------------

		if ($events)
		{
			foreach ($weeks AS &$week)
			{
				foreach ($week['days'] AS &$day)
				{
					if (array_key_exists($day['day'], $events))
					{
						$day['events_on_day'] = count($events[$day['day']]);
					}
				}
			}
		}

		// --------------------------------------
		// Parse prev/this/next month links ourselves
		// --------------------------------------

		$this->return_data = ee()->TMPL->tagdata;

		foreach (ee()->TMPL->var_single AS $key => $format)
		{
			if (preg_match('/^(prev|this|next)_month(\s|$)/', $key, $match))
			{
				$format = (strpos($format, '%') !== FALSE) ? $format : '%Y-%m';

				if (($match[1]) == 'this')
				{
					$month = $this->date->ee_format($format, $lang);
				}
				else
				{
					$month = $$match[1]->ee_format($format, $lang);
				}

				$this->return_data = str_replace(LD.$key.RD, $month, $this->return_data);
			}
		}

		// --------------------------------------
		// Create data array for parsing vars
		// --------------------------------------

		$data = array(
			'next_month_url' => $next->month_url(),
			'prev_month_url' => $prev->month_url(),
			'this_month_url' => $this->date->month_url(),
			'weekdays' => $weekdays,
			'weeks' => $weeks
		);

		$this->_log('Parsing calendar tagdata');

		return ee()->TMPL->parse_variables_row($this->return_data, $data);
	}

	// --------------------------------------------------------------------

	/**
	 * Generate month list based on events
	 *
	 * @access      public
	 * @return      string
	 */
	public function archive()
	{
		// --------------------------------------
		// Prep no_results to avoid conflicts
		// --------------------------------------

		$this->_prep_no_results();

		// --------------------------------------
		// Get the events
		// --------------------------------------

		$events = $this->model->get_range($this->_get_range());

		if ( ! ($events = $this->_get_event_entries($events)))
		{
			$this->_log('No events found, returning no results');
			return ee()->TMPL->no_results();
		}

		// --------------------------------------
		// Check unit parameter (month is default)
		// --------------------------------------

		$unit = ee()->TMPL->fetch_param('unit', 'month');

		if ( ! in_array($unit, $this->date->units()))
		{
			$unit = 'month';
		}

		// --------------------------------------
		// Loop through events and add them to the rows array
		// --------------------------------------

		$rows = array();

		foreach ($events AS $event)
		{
			// Create Low Date objects from each date
			$start = new Low_date($event['start_date']);
			$end   = new Low_date($event['end_date']);

			// Set both dates to the first of the month
			// and return the month url: YYYY-MM
			$start_date = ($unit == 'year') ? $start->year() : $start->first_of_month()->month_url();
			$end_date   = ($unit == 'year') ? $end->year() : $end->first_of_month()->month_url();

			// If event starts and ends in the same date,
			// simply add it to the dates array
			if ($start_date == $end_date)
			{
				$rows[$start_date][] = $event['entry_id'];
			}
			// Or else add each spanning date to the rows array
			else
			{
				// To do this, increase the start date by a month
				// until it exceeds the end month
				$method = ($unit == 'year') ? 'year' : 'month_url';
				while ($start->$method() <= $end_date)
				{
					$rows[$start->$method()][] = $event['entry_id'];
					$start->add($unit);
				}
			}
		}

		// Sort ascending for now
		ksort($rows);

		// --------------------------------------
		// Fill'er up?
		// --------------------------------------

		if (ee()->TMPL->fetch_param('show_empty') == 'yes')
		{
			// Get all dates
			$keys   = array_keys($rows);
			$suffix = ($unit == 'year') ? '-01-01' : '-01';
			$method = ($unit == 'year') ? 'year' : 'month_url';

			// Get start and end dates
			$start = new Low_date($keys[0].$suffix);
			$end   = $keys[count($keys)-1].$suffix;

			while ($start->date() < $end)
			{
				$start->add($unit);

				$key = $start->$method();

				if ( ! array_key_exists($key, $rows))
				{
					$rows[$key] = array();
				}
			}

			// and sort again
			ksort($rows);
		}

		// --------------------------------------
		// Reverse sort, if necessary
		// --------------------------------------

		if (ee()->TMPL->fetch_param('sort', 'asc') == 'desc')
		{
			krsort($rows);
		}

		// --------------------------------------
		// Limit/offset the output array by slicing
		// --------------------------------------

		$offset = (int) ee()->TMPL->fetch_param('offset', 0);
		$limit  = (int) ee()->TMPL->fetch_param('limit');

		// Force NULL to limit
		if ( ! $limit) $limit = NULL;

		// Slice it
		if ($offset || $limit)
		{
			$rows = array_slice($rows, $offset, $limit, TRUE);
		}

		// --------------------------------------
		// Language parameter
		// --------------------------------------

		$lang = ee()->TMPL->fetch_param('lang');

		// --------------------------------------
		// Create data array based on unit given
		// --------------------------------------

		$data = array();

		foreach ($rows AS $key => $events)
		{
			// Create new date for this key
			$date = new Low_Date($key);

			// Initiate new row for data
			$row = array(
				'unit'       => $unit,
				'date_url'   => $key,
				'num_events' => count($events)
			);

			// Depricated: use generic vars above instead
			if ($unit == 'month')
			{
				$row['month_url'] = $row['date_url'];
				$row['events_in_month'] = $row['num_events'];
			}

			// Add each possible format to this row
			foreach ($this->formats AS $fmt => $k)
			{
				$row[$k] = $date->ee_format($fmt, $lang);
			}

			// Then add the row to the data array
			$data[] = $row;
		}

		// --------------------------------------
		// Sweet magic
		// --------------------------------------

		return ee()->TMPL->parse_variables(ee()->TMPL->tagdata, $data);
	}

	// --------------------------------------------------------------------
	// PRIVATE METHODS
	// --------------------------------------------------------------------

	/**
	 * Check for {if low_events_no_results}
	 *
	 * @access      private
	 * @return      void
	 */
	private function _prep_no_results()
	{
		// Shortcut to tagdata
		$td =& ee()->TMPL->tagdata;
		$open = 'if '.LOW_EVENTS_PACKAGE.'_no_results';
		$close = '/if';

		// Check if there is a custom no_results conditional
		if (strpos($td, $open) !== FALSE && preg_match('#'.LD.$open.RD.'(.*?)'.LD.$close.RD.'#s', $td, $match))
		{
			$this->_log("Prepping {$open} conditional");

			// Check if there are conditionals inside of that
			if (stristr($match[1], LD.'if'))
			{
				$match[0] = ee()->functions->full_tag($match[0], $td, LD.'if', LD.'\/if'.RD);
			}

			// Set template's no_results data to found chunk
			ee()->TMPL->no_results = substr($match[0], strlen(LD.$open.RD), -strlen(LD.$close.RD));

			// Remove no_results conditional from tagdata
			$td = str_replace($match[0], '', $td);
		}
	}

	/**
	 * Get channel fields from API
	 *
	 *
	 * @access      private
	 * @return      void
	 */
	private function _get_channel_fields()
	{
		if ( ! ($this->fields = low_get_cache('channel', 'custom_channel_fields')))
		{
			$this->_log('Fetching channel fields from API');

			ee()->load->library('api');
			ee()->api->instantiate('channel_fields');

			$fields = ee()->api_channel_fields->fetch_custom_channel_fields();

			foreach ($fields AS $key => $val)
			{
				low_set_cache('channel', $key, $val);
			}

			$this->fields = $fields['custom_channel_fields'];
		}
	}

	/**
	 * Call the native channel:entries method
	 *
	 * @access     private
	 * @return     string
	 */
	private function _channel_entries()
	{
		// --------------------------------------
		// Unset custom parameters
		// --------------------------------------

		foreach ($this->params AS $param)
		{
			unset(ee()->TMPL->tagparams[$param]);
		}

		$this->_log('Calling the channel module');

		// --------------------------------------
		// Take care of related entries (< EE 2.6.0)
		// --------------------------------------

		if (version_compare(APP_VER, '2.6.0', '<'))
		{
			// We must do this, 'cause the template engine only does it for
			// channel:entries or events:events_results. The bastard.
			ee()->TMPL->tagdata = ee()->TMPL->assign_relationship_data(ee()->TMPL->tagdata);

			// Add related markers to single vars to trigger replacement
			foreach (ee()->TMPL->related_markers AS $var)
			{
				ee()->TMPL->var_single[$var] = $var;
			}
		}

		// --------------------------------------
		// Include channel module
		// --------------------------------------

		if ( ! class_exists('channel'))
		{
			require_once PATH_MOD.'channel/mod.channel'.EXT;
		}

		// --------------------------------------
		// Create new Channel instance
		// --------------------------------------

		$channel = new Channel();

		// --------------------------------------
		// Let the Channel module do all the heavy lifting
		// --------------------------------------

		return $channel->entries();
	}

	/**
	 * Events based on parameters present
	 *
	 * @access     private
	 * @return     array
	 */
	private function _get_event_entries($rows = array())
	{
		// --------------------------------------
		// No rows? No entries
		// --------------------------------------

		if (empty($rows))
		{
			return $rows;
		}

		// --------------------------------------
		// Get entry ids
		// --------------------------------------

		$rows = low_associate_results($rows, 'entry_id');

		// --------------------------------------
		// Start composing query
		// --------------------------------------

		ee()->db->distinct()
		        ->select('t.entry_id')
		        ->from('channel_titles t')
		        ->where_in('t.site_id', ee()->TMPL->site_ids);

		// --------------------------------------
		// Event entry ids
		// --------------------------------------

		if ($entry_ids = array_keys($rows))
		{
			ee()->db->where_in('t.entry_id', $entry_ids);
		}

		// --------------------------------------
		// Apply simple filters
		// --------------------------------------

		$filters = array(
			'entry_id'   => 't.entry_id',
			'url_title'  => 't.url_title',
			'channel_id' => 't.channel_id',
			'author_id'  => 't.author_id',
			'status'     => 't.status'
		);

		foreach ($filters AS $param => $attr)
		{
			$this->_simple_filter($param, $attr);
		}

		// --------------------------------------
		// Filter by channel name
		// --------------------------------------

		if ($channel = $this->_get_param('channel'))
		{
			// Determine which channels to filter by
			list($channel, $in) = low_explode_param($channel);

			// Adjust query accordingly
			ee()->db->join('channels c', 'c.channel_id = t.channel_id');
			ee()->db->{($in ? 'where_in' : 'where_not_in')}('c.channel_name', $channel);
		}

		// --------------------------------------
		// Filter by category
		// --------------------------------------

		if ($categories_param = $this->_get_param('category'))
		{
			// Determine which categories to filter by
			list($categories, $in) = low_explode_param($categories_param);

			// Allow for inclusive list: category="1&2&3"
			if (strpos($categories_param, '&'))
			{
				// Execute query the old-fashioned way, so we don't interfere with active record
				// Get the entry ids that have all given categories assigned
				$query = ee()->db->query(
					"SELECT entry_id, COUNT(*) AS num
					FROM exp_category_posts
					WHERE cat_id IN (".implode(',', $categories).")
					GROUP BY entry_id HAVING num = ". count($categories));

				// If no entries are found, make sure we limit the query accordingly
				if ( ! ($entry_ids = low_flatten_results($query->result_array(), 'entry_id')))
				{
					$entry_ids = array(0);
				}

				ee()->db->{($in ? 'where_in' : 'where_not_in')}('t.entry_id', $entry_ids);
			}
			else
			{
				// Join category table
				ee()->db->join('category_posts cp', 'cp.entry_id = t.entry_id');
				ee()->db->{($in ? 'where_in' : 'where_not_in')}('cp.cat_id', $categories);
			}
		}

		// --------------------------------------
		// Hide expired entries
		// --------------------------------------

		if ($this->_get_param('show_expired', 'no') != 'yes')
		{
			ee()->db->where('(t.expiration_date = 0 OR t.expiration_date >= '.$this->date->now().')');
		}

		// --------------------------------------
		// Hide future entries
		// --------------------------------------

		if ($this->_get_param('show_future_entries', 'no') != 'yes')
		{
			ee()->db->where('t.entry_date <=', $this->date->now());
		}

		// --------------------------------------
		// Handle search fields
		// --------------------------------------

		if ($search_fields = $this->_search_where(ee()->TMPL->search_fields, 'd.'))
		{
			// Join exp_channel_data table
			ee()->db->join('channel_data d', 't.entry_id = d.entry_id');
			ee()->db->where(implode(' AND ', $search_fields), NULL, FALSE);
		}

		// --------------------------------------
		// Exclude pages / pages only
		// --------------------------------------

		if ($show_pages = $this->_get_param('show_pages'))
		{
			// Get page ids, add 0 to force no results if array is empty
			$page_ids   = $this->_get_page_ids();
			$page_ids[] = 0;

			// Include or Exclude page IDs
			switch ($show_pages)
			{
				case 'no':
					ee()->db->where_not_in('t.entry_id', $page_ids);
				break;

				case 'only':
					ee()->db->where_in('t.entry_id', $page_ids);
				break;
			}
		}

		// --------------------------------------
		// Return the results
		// --------------------------------------

		if ($entries = ee()->db->get()->result_array())
		{
			$ids = low_flatten_results($entries, 'entry_id', 'entry_id');
			//low_dump($ids, 0);
			//low_dump($rows, 0);
			$entries = array_intersect_key($rows, $ids);
			//low_dump($entries);
		}

		return $entries;
	}

	/**
	 * Add simple filter to current query
	 *
	 * @access     private
	 * @param      string    template parameter to look for
	 * @param      string    attribute to apply filter to
	 * @return     void
	 */
	private function _simple_filter($param, $attr)
	{
		if ($param = ee()->TMPL->fetch_param($param))
		{
			// Determine which channels to filter by
			list($param, $in) = low_explode_param($param);

			// Adjust query accordingly
			ee()->db->{($in ? 'where_in' : 'where_not_in')}($attr, $param);
		}
	}

	/**
	 * Get field ids for event fields from param
	 *
	 * @access     private
	 * @return     array
	 */
	private function _get_event_field_ids()
	{
		// Check parameter
		if ($events_field = $this->_get_param('events_field'))
		{
			// Init array
			$ids = array();

			// Get fields from parameter
			list($fields, $in) = low_explode_param($events_field);

			// Get id for each field
			foreach ($fields AS $field)
			{
				$ids[] = $this->_get_field_id($field);
			}

			// Back to a param
			$events_field = implode('|', $ids);
		}

		return $events_field;
	}

	/**
	 * Get field id for given field short name
	 *
	 * @access     private
	 * @param      string
	 * @return     int
	 */
	private function _get_field_id($str)
	{
		return (int) @$this->fields[$this->site_id][$str];
	}

	/**
	 * Create a list of where-clauses for given search parameters
	 *
	 * @access     private
	 * @param      array
	 * @param      string
	 * @return     array
	 */
	private function _search_where($search = array(), $prefix = '')
	{
		// --------------------------------------
		// Initiate where array
		// --------------------------------------

		$where = array();

		// --------------------------------------
		// Loop through search filters and create where clause accordingly
		// --------------------------------------

		foreach ($search AS $key => $val)
		{
			// Skip non-existent fields
			if ( ! ($field_id = $this->_get_field_id($key))) continue;

			// Initiate some vars
			$exact = $all = FALSE;
			$field = $prefix.'field_id_'.$field_id;

			// Exact matches
			if (substr($val, 0, 1) == '=')
			{
				$val   = substr($terms, 1);
				$exact = TRUE;
			}

			// All items? -> && instead of |
			if (strpos($val, '&&') !== FALSE)
			{
				$all = TRUE;
				$val = str_replace('&&', '|', $val);
			}

			// Convert parameter to bool and array
			list($items, $in) = low_explode_param($val);

			// Init sql for where clause
			$sql = array();

			// Loop through each sub-item of the filter an create sub-clause
			foreach ($items AS $item)
			{
				// Convert IS_EMPTY constant to empty string
				$empty = ($item == 'IS_EMPTY');
				$item  = str_replace('IS_EMPTY', '', $item);

				// whole word? Regexp search
				if (substr($item, -2) == '\W')
				{
					$operand = $in ? 'REGEXP' : 'NOT REGEXP';
					$item    = "'[[:<:]]".preg_quote(substr($item, 0, -2))."[[:>:]]'";
				}
				else
				{
					// Not a whole word
					if ($exact || $empty)
					{
						// Use exact operand if empty or = was the first char in param
						$operand = $in ? '=' : '!=';
						$item = "'".ee()->db->escape_str($item)."'";
					}
					else
					{
						// Use like operand in all other cases
						$operand = $in ? 'LIKE' : 'NOT LIKE';
						$item = "'%".ee()->db->escape_str($item)."%'";
					}
				}

				// Add sub-clause to this statement
				$sql[] = sprintf("(%s %s %s)", $field, $operand, $item);
			}

			// Inclusive or exclusive
			$andor = $all ? ' AND ' : ' OR ';

			// Add complete clause to where array
			$where[] = (count($sql) == 1) ? $sql[0] : '('.implode($andor, $sql).')';
		}

		// --------------------------------------
		// Where now contains a list of clauses
		// --------------------------------------

		return $where;
	}

	/**
	 * Get all page IDs
	 *
	 * @access     private
	 * @return     array
	 */
	private function _get_page_ids()
	{
		// Init at 0 to force no results
		$page_ids = array();

		// Loop through all site pages rows, get entry ids from uris key
		if ($pages = ee()->config->item('site_pages'))
		{
			foreach ($pages AS $site_id => $row)
			{
				$page_ids = array_merge($page_ids, array_keys($row['uris']));
			}
		}

		return $page_ids;
	}

	// --------------------------------------------------------------------

	/**
	 * Initiate date by param or given fallback
	 *
	 * @access     private
	 * @param      string
	 * @return     void
	 */
	private function _init_date()
	{
		// Get date params
		$date      = $this->_get_param('date');
		$date_from = $this->_get_param('date_from', '');
		$date_to   = $this->_get_param('date_to', '');

		// If from / to are set, override date param
		if ($date_from || $date_to)
		{
			$date = $date_from.';'.$date_to;
		}

		// Initiate the date
		$this->date->init($date);

		// Log the custom date range if given
		$msg = ($this->date->given() == 'custom')
		     ? sprintf('Custom date range given from %s to %s', $this->date->from(), $this->date->to())
		     : sprintf('Working date set to %s %s', $this->date->date(), $this->date->time());

		$this->_log($msg);
	}

	/**
	 * Get parameter from TMPL or post
	 */
	private function _get_param($str, $fallback = FALSE)
	{
		// Get date from tag parameter
		$val = ee()->TMPL->fetch_param($str, $fallback);

		// Check if date is dynamic
		if ($dynamic = ee()->TMPL->fetch_param('dynamic_parameters'))
		{
			// If date is in the dynamic_parameters param, check POST data
			list($dynamic, $in) = low_explode_param($dynamic);

			if (in_array($str, $dynamic) && ($posted_val = ee()->input->post($str)))
			{
				// Param was posted, use that instead
				$this->_log('Using posted dynamic '.$str.': '.$posted_val);
				$val = $posted_val;
			}
		}

		return $val;
	}

	/**
	 * Get range array based on params
	 */
	private function _get_range()
	{
		// --------------------------------------
		// Initiate range array (faux object)
		// --------------------------------------

		$range = array(
			'start_date' => NULL,
			'start_time' => NULL,
			'end_date'   => NULL,
			'end_time'   => NULL
		);

		// --------------------------------------
		// Passed, Active and Upcoming
		// --------------------------------------

		foreach (array('passed', 'active', 'upcoming') AS $key)
		{
			$range[$key] = ! ($this->_get_param('show_'.$key) == 'no');
		}

		// --------------------------------------
		// Get field IDs from param
		// --------------------------------------

		$range['fields'] = $this->_get_event_field_ids();

		// --------------------------------------
		// Site IDs
		// --------------------------------------

		$range['site_id'] = implode('|', ee()->TMPL->site_ids);

		// Return it
		return $range;
	}

	/**
	 * Log message to Template Logger
	 *
	 * @access     private
	 * @param      string
	 * @return     void
	 */
	private function _log($msg)
	{
		ee()->TMPL->log_item("Low Events: {$msg}");
	}

} // End Class

/* End of file mod.low_events.php */