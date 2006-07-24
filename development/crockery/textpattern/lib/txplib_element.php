<?php

/*
Copyright 2006 Alex Shiels http://thresholdstate.com/

$HeadURL: $
$LastChangedRevision: $
*/

define('elements_dir', 'elements');

// -------------------------------------------------------------
	function load_element($name)
	{
		global $elements, $prefs;

		if (empty($elements[$name]))
			return false;

		if (!empty($elements[$name]['loaded']))
			return true;

		extract($elements[$name]);

		$dir = realpath(txpath.DS.elements_dir);
		$file = $dir.DS.$name.'.php';

		if (!is_file($file) or !is_readable($file))
			trigger_error("$file is inaccessible", E_USER_ERROR);

		if (strncmp($dir, realpath($file), strlen($dir)) !== 0)
			trigger_error("$file directory is invalid ($dir)", E_USER_ERROR);

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
	function build_element_list()
	{
		global $elements;

		// only do this the first time load_elements() is called
		if (!isset($elements)) {
			$elements = array();
			$rs = safe_rows_start('*', 'txp_element', "status = '1'");
			while ($row = nextRow($rs))
				$elements[$row['name']] = $row;
		}
	}

// -------------------------------------------------------------
	function load_elements($event, $step)
	{
		global $elements, $prefs;

		build_element_list();

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

// -------------------------------------------------------------
	function update_elements($ver, $ts, $dir='')
	{
		// recursively find and run element update files
		if (!$dir)
			$dir = realpath(txpath.DS.elements_dir);

		$dh = @opendir($dir);
		$dirs = array();
		$files = array();
		while ($dh and false !== ($f = @readdir($dh))) {
			if ($f{0} == '.')
				continue;
			if (is_dir($dir.DS.$f))
				$dirs[] = $dir.DS.$f;
			elseif (is_file($dir.DS.$f))
				$files[] = $f;
		}
		closedir($dh);

		foreach ($files as $f)
			$file = $dir.DS.$f;
			if (preg_match('@_to_(.*)\.php@', $f, $m)) {
				$file_ver = $m[1];
				if (is_file($file) and is_readable($file)
						and (version_compare($file_ver, $ver) > 0 or filemtime($file) > $ts)) {
					include($file);
				}
			}

		foreach ($dirs as $d)
			update_elements($ver, $ts, $d);

	}

// -------------------------------------------------------------
	function add_element($name, $event, $type = 0, $required=1)
	{
		// type == 0 means load at startup
		// type == 1 means load only when included or required

		return safe_upsert('txp_element',
		   "event='".doSLash($event)."',
			required='".doSlash($required)."',
			type='".doSlash($type)."',
			status='1',
			created=now(),
			modified=now()",
			"name='".doSlash($name)."'");
	}

?>
