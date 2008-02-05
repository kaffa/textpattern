<?php
/*
$HeadURL$
$LastChangedRevision$
*/
	if (!defined('TXP_UPDATE'))
		exit("Nothing here. You can't access this file directly.");
	global $txpcfg, $thisversion, $dbversion, $txp_using_svn, $dbupdatetime;

	function get_update_numbers() {
		// get the numbers of all the _to_x.y.z.php files in the update directory
		$files = array();
		$dp = opendir(txpath.'/update/');
		$n = array();
		while (false !== ($file = readdir($dp)))
		{
			if (preg_match('/^_to_([0-9.]+)[.]php$/', $file, $m))
				$n[] = $m[1];
		}
		closedir($dp);
		// sort so they're in the correct order to apply
		sort($n);
		return $n;
	}

	@ignore_user_abort(1);
	@set_time_limit(0);

	// Run any update file newer than the last dbupdatetime
	$newest = 0;
	$newversion = 0;

	$updated = false;
	foreach (get_update_numbers() as $n) {
		$newversion = $n;
		$f = txpath."/update/_to_{$n}.php";
		if (filemtime($f) > $dbupdatetime) {
			include($f);
			$updated = true;
			$newest = max($newest, filemtime($f));
		}
	}
	if (!$updated) return;

/*
	FIXME: is any of this relevant?
	else {
		//Use "ENGINE" if version of MySQL > (4.0.18 or 4.1.2)
		// On 4.1 or greater use utf8-tables, if that is configures in config.php
		$mysqlversion = mysql_get_server_info();
		$tabletype = ( intval($mysqlversion[0]) >= 5 || preg_match('#^4\.(0\.[2-9]|(1[89]))|(1\.[2-9])#',$mysqlversion))
						? " ENGINE=MyISAM "
						: " TYPE=MyISAM ";
		if ( isset($txpcfg['dbcharset']) && (intval($mysqlversion[0]) >= 5 || preg_match('#^4\.[1-9]#',$mysqlversion)))
		{
			$tabletype .= " CHARACTER SET = ". $txpcfg['dbcharset'] ." ";
		}

		// Run any update file newer than the last dbupdatetime
		foreach (get_update_files() as $file) {
			if (preg_match('@_to_(.*)\.php@', $file, $m)) {
				$file_ver = $m[1];
				$f = txpath."/update/$file";
				if (version_compare($file_ver, $dbversion) > 0) {
					include($f);
					$dbversion = $file_ver;
					$newest = max($newest, filemtime($f));
				}
			}
		}
	}

	if (version_compare($dbversion, '4.0.5', '<'))
	{
		if ((include txpath.DS.'update'.DS.'_to_4.0.5.php') !== false)
			$dbversion = '4.0.5';
	}
*/

	// keep track of updates for svn users
	safe_delete('txp_prefs',"name = 'dbupdatetime'");
	safe_insert('txp_prefs', "prefs_id=1, name='dbupdatetime',val='".max($newest,time())."', type='2'");
	// update version
	safe_delete('txp_prefs',"name = 'version'");
	safe_insert('txp_prefs', "prefs_id=1, name='version',val='$newversion', type='2'");
	// updated, baby. So let's get the fresh prefs and send them to languages
	$event = 'prefs';
	$step = 'list_languages';
	$prefs = extract(get_prefs());
?>
