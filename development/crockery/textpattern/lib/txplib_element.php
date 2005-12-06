<?php

/*
$HeadURL: $
$LastChangedRevision: $
*/

// -------------------------------------------------------------
	function load_element($name)
	{
		global $elements, $prefs;

		if (empty($elements[$name]))
			return false;

		if (!empty($elements[$name]['loaded']))
			return true;

		extract($elements[$name]);

		$file = txpath.'/elements/'.$name.'.php';
		if (!is_file($file) or !is_readable($file))
			trigger_error("$file is inaccessible", E_USER_ERROR);

		if (!empty($hash) and $hash != ($md5 = md5_file($file)))
			trigger_error("$file hash check failed: $hash != $md5", E_USER_ERROR);

		$elements[$name]['loaded'] = 1;
		return include_once($file);
	}

// -------------------------------------------------------------
	function include_element($name)
	{
		return load_element($name);
	}

// -------------------------------------------------------------
	function require_element($name)
	{
		if (!load_element($name))
			trigger_error("Unable to load element $name", E_USER_ERROR);
	}

// -------------------------------------------------------------
	function element_active($name)
	{
		// returns true if an element status is > 0
		global $elements;
		return !empty($elements[$name]['status']);
	}

// -------------------------------------------------------------
	function load_elements($event, $step)
	{
		global $elements, $prefs;

		// only do this the first time load_elements() is called
		if (!isset($elements)) {
			$elements = array();
			$rs = safe_rows_start('*', 'txp_element', "status = '1'");
			while ($row = nextRow($rs))
				$elements[$row['name']] = $row;
		}

		foreach ($elements as $e) {
			// type == 0 means load at startup
			// type == 1 means load only when included or required
			if ($e['type'] == 0 and
				($e['event'] == '' or $e['event'] == $event) and
				($e['step'] == '' or $e['step'] == $step)) {
				load_element($e['name']);
			}
		}
	}


?>
