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

		$dir = abspath(elements_dir);
		$file = secpath($name.'.php', $dir);
		
		if ($file == false)
			trigger_error("path for element '$name' is invalid ($dir)", E_USER_ERROR);

		if (!is_file($file) or !is_readable($file))
			trigger_error("$file is inaccessible", E_USER_ERROR);

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
	function build_element_list($ini_file)
	{
		global $elements;

		$ini_file = secpath($ini_file, txpath.DS.elements_dir);

		if (file_exists($ini_file)) {
			$e = parse_ini_file($ini_file, true);
			if ($e) {
				foreach ($e as $name => $row) {
					$er = array_merge(array(
						'name' => $name,
						'event' => 'null',
						'status' => '1',
					), $row);

					if (!empty($er['status'])) {
						$elements[$name] = $er;

					}
				}
			}
		}

	}

// -------------------------------------------------------------
	function load_elements($event)
	{
		global $elements;

		if (is_array($elements)) {
			foreach ($elements as $e) {
				if ($e['event'] == $event) {
					load_element($e['name']);
				}
			}
		}
	}

// -------------------------------------------------------------
	function register_element_tabs()
	{
		global $elements;

		if (is_array($elements)) {
			foreach ($elements as $e) {
				if (!empty($e['tabarea'])) {
					register_tab($e['tabarea'], $e['event'], 'tab_'.$e['event']);
				}
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


		foreach ($files as $f) {
			$file = $dir.DS.$f;
			if (preg_match('@_to_(.*)\.php@', $f, $m)) {
				$file_ver = $m[1];
				if (is_file($file) and is_readable($file)
						and (version_compare($file_ver, $ver) > 0 or filemtime($file) > $ts)) {
					include($file);
				}
			}
		}

		foreach ($dirs as $d)
			update_elements($ver, $ts, $d);

	}


?>
