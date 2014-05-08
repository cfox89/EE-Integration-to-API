<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

// include config file
include(PATH_THIRD.'low_events/config.php');

/**
 * Low Events Extension class
 *
 * @package        low_events
 * @author         Lodewijk Schutte <hi@gotolow.com>
 * @link           http://gotolow.com/addons/low-events
 * @copyright      Copyright (c) 2012-2013, Low
 */
class Low_events_ext {

	// --------------------------------------------------------------------
	// PROPERTIES
	// --------------------------------------------------------------------

	/**
	 * Name, required for Extensions page
	 *
	 * @access      public
	 * @var         string
	 */
	public $name = LOW_EVENTS_NAME;

	/**
	 * Docs URL, required for Extensions page
	 *
	 * @access      public
	 * @var         string
	 */
	public $docs_url = LOW_EVENTS_DOCS;

	/**
	 * This version
	 *
	 * @access      public
	 * @var         string
	 */
	public $version = LOW_EVENTS_VERSION;

	/**
	 * Settings
	 *
	 * @access      public
	 * @var         mixed
	 */
	public $settings = FALSE;

	/**
	 * Do settings exist?
	 *
	 * @access      public
	 * @var         bool
	 */
	public $settings_exist = FALSE;

	/**
	 * Extension is required by module
	 *
	 * @access      public
	 * @var         array
	 */
	public $required_by = array('module');

	// --------------------------------------------------------------------
	// PUBLIC METHODS
	// --------------------------------------------------------------------

	/**
	 * Add/modify entry in search index
	 *
	 * @access      public
	 * @param       int
	 * @param       array
	 * @param       array
	 * @return      void
	 */
	public function low_search_pre_search($params)
	{
		// -------------------------------------------
		// Get the latest version of $params
		// -------------------------------------------

		if (ee()->extensions->last_call !== FALSE)
		{
			$params = ee()->extensions->last_call;
		}

		// -------------------------------------------
		// Get all low_events: parameters
		// -------------------------------------------

		$events_params = array();

		foreach ($params AS $key => $val)
		{
			if (substr($key, 0, 11)  == 'low_events:')
			{
				$events_params[substr($key, 11)] = $val;
			}
		}

		// If no events params exist, bail out again
		if (empty($events_params)) return $params;

		// -------------------------------------------
		// Set parameters to TMPL
		// -------------------------------------------

		// Add entry_id to params if it exists
		if (isset($params['entry_id'])) $events_params['entry_id'] = $params['entry_id'];

		// Log what we're doing
		ee()->TMPL->log_item('Low Events: Setting search params '.json_encode($events_params));

		// Save old params for later
		$old_params = ee()->TMPL->tagparams;

		// Overwrite current tagparams so Low_events::entries can work with it
		ee()->TMPL->tagparams = $events_params;

		// -------------------------------------------
		// Load Module File
		// -------------------------------------------

		if ( ! class_exists('Low_events'))
		{
			require_once PATH_THIRD.LOW_EVENTS_PACKAGE.'/mod.low_events.php';
		}

		$LE = new Low_events;

		// -------------------------------------------
		// Call entries to get entry ids
		// -------------------------------------------

		if ($entry_ids = $LE->entries(TRUE))
		{
			$ids = implode('|', $entry_ids);
			ee()->TMPL->log_item('Low Events: Found events for Low Search: '. $ids);
			$params['entry_id'] = $ids;
		}
		else
		{
			ee()->TMPL->log_item('Low Events: No events found for Low Search');
			ee()->TMPL->tagdata = ee()->TMPL->no_results();
			ee()->extensions->end_script = TRUE;
		}

		// -------------------------------------------
		// Restore original params
		// -------------------------------------------

		ee()->TMPL->tagparams = $old_params;

		return $params;
	}

}
// END CLASS

/* End of file ext.low_events.php */