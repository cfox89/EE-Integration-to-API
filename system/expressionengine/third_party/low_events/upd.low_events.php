<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

// include config file
include(PATH_THIRD.'low_events/config.php');

/**
 * Low Events Update class
 *
 * @package        low_events
 * @author         Lodewijk Schutte <hi@gotolow.com>
 * @link           http://gotolow.com/addons/low-events
 * @copyright      Copyright (c) 2012-2013, Low
 */
class Low_events_upd {

	// --------------------------------------------------------------------
	// PROPERTIES
	// --------------------------------------------------------------------

	/**
	 * This version
	 *
	 * @access      public
	 * @var         string
	 */
	public $version = LOW_EVENTS_VERSION;

	/**
	 * Class name
	 *
	 * @access      private
	 * @var         array
	 */
	private $class_name;

	/**
	 * Actions used
	 *
	 * @access      private
	 * @var         array
	 */
	private $actions = array();

	/**
	 * Extension hooks
	 *
	 * @var        array
	 * @access     private
	 */
	private $hooks = array(
		'low_search_pre_search'
	);

	// --------------------------------------------------------------------
	// METHODS
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
		ee()->load->library(LOW_EVENTS_PACKAGE.'_model');

		Low_events_model::load_models();

		// --------------------------------------
		// Set class name
		// --------------------------------------

		$this->class_name = ucfirst(LOW_EVENTS_PACKAGE);
	}

	// --------------------------------------------------------------------

	/**
	 * Install the module
	 *
	 * @access      public
	 * @return      bool
	 */
	public function install()
	{
		// --------------------------------------
		// Install tables
		// --------------------------------------

		ee()->low_events_event_model->install();

		// --------------------------------------
		// Add row to modules table
		// --------------------------------------

		ee()->db->insert('modules', array(
			'module_name'    => $this->class_name,
			'module_version' => $this->version,
			'has_cp_backend' => 'n'
		));

		// --------------------------------------
		// Add rows to action table
		// --------------------------------------

		foreach ($this->actions AS $row)
		{
			list($class, $method) = $row;

			ee()->db->insert('actions', array(
				'class'  => $class,
				'method' => $method
			));
		}

		// --------------------------------------
		// Add rows to extensions table
		// --------------------------------------

		foreach ($this->hooks AS $hook)
		{
			$this->_add_hook($hook);
		}

		return TRUE;
	}

	// --------------------------------------------------------------------

	/**
	 * Uninstall the module
	 *
	 * @return	bool
	 */
	public function uninstall()
	{
		// --------------------------------------
		// get module id
		// --------------------------------------

		$query = ee()->db->select('module_id')
		       ->from('modules')
		       ->where('module_name', $this->class_name)
		       ->get();

		// --------------------------------------
		// remove references from module_member_groups
		// --------------------------------------

		ee()->db->where('module_id', $query->row('module_id'));
		ee()->db->delete('module_member_groups');

		// --------------------------------------
		// remove references from modules
		// --------------------------------------

		ee()->db->where('module_name', $this->class_name);
		ee()->db->delete('modules');

		// --------------------------------------
		// remove references from actions
		// --------------------------------------

		ee()->db->where_in('class', array($this->class_name, $this->class_name.'_mcp'));
		ee()->db->delete('actions');

		// --------------------------------------
		// remove references from extensions
		// --------------------------------------

		ee()->db->where('class', $this->class_name.'_ext');
		ee()->db->delete('extensions');

		// --------------------------------------
		// Uninstall tables
		// --------------------------------------

		ee()->low_events_event_model->uninstall();

		return TRUE;
	}

	// --------------------------------------------------------------------

	/**
	 * Update the module
	 *
	 * @return	bool
	 */
	public function update($current = '')
	{
		// --------------------------------------
		// Same version? A-okay, daddy-o!
		// --------------------------------------

		if ($current == '' OR version_compare($current, $this->version) === 0)
		{
			return FALSE;
		}

		// Update to next version
		if (version_compare($current, '1.1.0', '<'))
		{
			// Add LS hook
			$this->_add_hook($this->hooks[0]);
		}

		// Update to 1.2.0 version
		if (version_compare($current, '1.2.0', '<'))
		{
			// Get all fields
			$query = ee()->db->select('field_id, field_settings')
			       ->from('channel_fields')
			       ->where('field_type', LOW_EVENTS_PACKAGE)
			       ->get();

			// Loop through results to change settings
			foreach ($query->result() AS $row)
			{
				// Read current settings
				$settings = unserialize(base64_decode($row->field_settings));

				// Check default values for overwrite dates
				$val = (isset($settings['overwrite_dates']) &&
					$settings['overwrite_dates'] == 'y') ? 'y' : 'n';

				// Set new values based on previous
				$settings['overwrite_entry_date']      = $val;
				$settings['overwrite_expiration_date'] = $val;

				// Remove overwrite_dates setting
				unset($settings['overwrite_dates']);

				// Encode for saving
				$settings = base64_encode(serialize($settings));

				// Update row
				ee()->db->update(
					'channel_fields',
					array('field_settings' => $settings),
					"field_id = '{$row->field_id}'"
				);
			}
		}

		// Return TRUE to update version number in DB
		return TRUE;
	}

	// --------------------------------------------------------------------

	/**
	 * Add hook to table
	 *
	 * @access	private
	 * @param	string
	 * @return	void
	 */
	private function _add_hook($hook)
	{
		ee()->db->insert('extensions', array(
			'class'    => $this->class_name.'_ext',
			'method'   => $hook,
			'hook'     => $hook,
			'settings' => '',
			'priority' => 5,
			'version'  => $this->version,
			'enabled'  => 'y'
		));

	}

} // End class

/* End of file upd.low_events.php */