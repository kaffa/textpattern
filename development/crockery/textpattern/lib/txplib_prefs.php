<?php

/*
$HeadURL: $
$LastChangedRevision: $
*/

//-------------------------------------------------------------
	function get_prefs()
	{
		$r = safe_rows_start('name, val', 'txp_prefs', 'prefs_id=1');
		if ($r) {
			while ($a = nextRow($r)) {
				$out[$a['name']] = $a['val']; 
			}
			$user_prefs = get_user_prefs();
			if ($user_prefs)
				$out = array_merge($user_prefs, $out);
			return $out;
		}
		return false;
	}

//-------------------------------------------------------------
	function get_user_prefs()
	{
		global $txp_user;
		if (empty($txp_user)) return array();

		$r = safe_rows_start('name, val', 'txp_prefs_user', "user='".doSlash($txp_user)."'");
		if ($r) {
			$out = array();
			while ($a = nextRow($r)) {
				$out[$a['name']] = $a['val']; 
			}
			return $out;
		}
		return false;
	}

//-------------------------------------------------------------
// update a preference in $prefs and in the database
	function update_pref($name, $val)
	{
		global $prefs;

		if (empty($prefs[$name]) or $prefs[$name] != $val) {
			$GLOBALS[$name] = $prefs[$name] = $val;
			return safe_upsert('txp_prefs', "val='".doSlash($val)."'", array('prefs_id=1', "name='".doSlash($name)."'"));
		}
		return true;
	}

//-------------------------------------------------------------
// update a user preference in $prefs and in the database
	function update_user_pref($name, $val)
	{
		global $prefs, $txp_user;

		if (empty($txp_user)) return;

		if (empty($prefs[$name]) or $prefs[$name] != $val) {
			$GLOBALS[$name] = $prefs[$name] = $val;
			return safe_upsert('txp_prefs_user', "val='".doSlash($val)."'", array("user='".doSlash($txp_user)."'", "name='".doSlash($name)."'"));
		}
		return true;
	}

?>
