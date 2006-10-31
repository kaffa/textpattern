<?php
/*
$HeadURL$
$LastChangedRevision$
*/
	if (!defined('TXP_UPDATE'))
		exit("Nothing here. You can't access this file directly.");
	global $txpcfg, $thisversion, $dbversion, $txp_using_svn, $dbupdatetime;

	function get_update_files() {
		$files = array();
		$dp = opendir(txpath.'/update/');
		while (false !== ($file = readdir($dp))) 
		{
			if (strpos($file,"_to_") === 0)
				$files[] = $file;
		}
		closedir($dp);
		// sort so they're in the correct order to apply
		sort($files);
		return $files;
	}

	@ignore_user_abort(1);
	@set_time_limit(0);

	// Run any update file newer than the last dbupdatetime
	$newest = 0;
	if ($txp_using_svn) {
		$updated = false;
		foreach (get_update_files() as $file) {
			$f = txpath."/update/$file";
			if (filemtime($f) > $dbupdatetime) {
				include($f);
				$updated = true;
				$newest = max($newest, filemtime($f));
			}
		}
		if (!$updated) return;
	}
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

	// keep track of updates for svn users
	safe_delete('txp_prefs',"name = 'dbupdatetime'");
	safe_insert('txp_prefs', "prefs_id=1, name='dbupdatetime',val='".max($newest,time())."', type='2'");
	// update version
	safe_delete('txp_prefs',"name = 'version'");
	safe_insert('txp_prefs', "prefs_id=1, name='version',val='$dbversion', type='2'");
	// updated, baby. So let's get the fresh prefs and send them to languages
	$event = 'prefs';
	$step = 'list_languages';
	$prefs = extract(get_prefs());
?>
