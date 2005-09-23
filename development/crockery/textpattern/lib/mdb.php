<?php
/*

	Micro Database Layer

	A simple database compatibility layer for PHP

	Copyright 2005 by Alex Shiels <http://thresholdstate.com/>

	This program is free software; you can redistribute it and/or
	modify it under the terms of the GNU General Public License
	as published by the Free Software Foundation; either version 2
	of the License, or (at your option) any later version.

$HeadURL: http://svn.textpattern.com/stable/4.0/textpattern/setup/index.php $
$LastChangedRevision: 820 $

*/

// One of 'my' or 'pg', MySQL and Postgresql respectively
global $txpcfg;
define('MDB_TYPE', (empty($txpcfg['dbtype']) ? 'my' : $txpcfg['dbtype']));

function get_caller($bt) {
	$caller = $bt[count($bt)-1];
	extract($caller);
	return "$file:$line $function()";
}

function db_connect($host, $user, $pass, $dbname='') {
	global $mdb_res;
	if (MDB_TYPE == 'pg')
		return $mdb_res = pg_connect(($host and $host != 'localhost' ? "host='".addslashes($host)."' " : '')."user='".addslashes($user)."' password='".addslashes($pass)."' dbname='".addslashes($dbname)."'");
	else
		return $mdb_res = mysql_connect($host, $user, $pass);
}

function db_selectdb($dbname) {
	global $mdb_res;
	if (MDB_TYPE == 'pg')
		return (pg_connection_status($mdb_res) === PGSQL_CONNECTION_OK);
	else
		return mysql_select_db($dbname, $mdb_res);
}

function db_query($q, $res=0) {
	global $mdb_res, $db_last_query;
	$res = ($res ? $res : $mdb_res);
	if (MDB_TYPE == 'pg') {
		$r = pg_query($res, $q);
	}
	else
		$r = mysql_query($q, $res);

	if (!$r) {
		trigger_error('failed query: '.$q.n);
		trigger_error('error: '.db_lasterror());
	}
	$db_last_query[$r] = $q;

	return $r;
}

function db_insert($table, $set, $res=0) {
	global $mdb_res;
	$res = ($res ? $res : $mdb_res);
	if (MDB_TYPE == 'pg')
		$set = my_insert_to_values($set);
	else
		$set = 'SET '.$set;

	if (!db_query('INSERT INTO '.$table.' '.$set, $res)) {
		echo 'failed insert: INSERT INTO '.$table.' '.$set.n;
		echo db_lasterror().n;
		return false;
	}

	$last = db_last_insert_id($table);
	return ($last ? $last : true);
}

function db_table_exists($tbl) {
	global $mdb_res;
	if (MDB_TYPE == 'pg')
		return @pg_meta_data($mdb_res, $tbl) != false;
	else
		return mysql_query('describe '.$tbl) != false;
}

function db_table_list() {
	global $mdb_res;
	if (MDB_TYPE == 'pg') {
		if ($rs = pg_query($mdb_res, 'select relname from pg_stat_user_tables order by relname'))
			$out = pg_fetch_all($rs);
	}
	else {
		$out = array();
		if ($rs = mysql_query('show tables')) 
			while ($row = mysql_fetch_assoc($rs))
				$out[] = $row;
	}

	$rs = array();
	if ($out) {
		foreach ($out as $r)
			$rs[] = array_shift($r);
	}

	return $rs;
}

function db_column_exists($tbl, $col, $res=0) {
	global $mdb_res;
	$res = ($res ? $res : $mdb_res);

	if (MDB_TYPE == 'pg') {
		$cols = @pg_meta_data($mdb_res, $tbl);
		return (!empty($cols[$col]));
	}
	else {
		$cols = array();
		if ($rs = mysql_query('describe '.$tbl)) 
			while ($row = mysql_fetch_assoc($rs))
				$cols[$row['Field']] = $row;
		return (!empty($cols[$col]));
	}
}

function db_last_insert_id($table, $res=0) {
	global $mdb_res;
	$res = ($res ? $res : $mdb_res);

	if (MDB_TYPE == 'pg') {
		# This assumes that the very first column in the table is a sequence
		$cols = pg_meta_data($res, $table);
		$colnames = array_keys($cols);
		$col = $colnames[0];
		if (strpos(@$cols[$col]['type'], 'int') === 0) {
			$sql = "select currval('{$table}_{$col}_seq') as lastinsertid";
			$rs = pg_query($res, $sql);
			$last_id = pg_fetch_result($rs, 0);
			return $last_id;
		}
	}
	else
		return mysql_insert_id($res);
}

function db_lasterror() {
	global $mdb_res;
	if (MDB_TYPE == 'pg')
		return pg_last_error($mdb_res);
	else
		return mysql_error();
}

function db_free($rs) {

	if (MDB_TYPE == 'pg')
		return pg_free_result($rs);
	else 
		return mysql_free_result($rs);
}

function db_num_rows($rs) {
	if (MDB_TYPE == 'pg')
		return pg_num_rows($rs);
	else 
		return mysql_num_rows($rs);
}

function db_fetch_assoc($rs) {
	global $db_last_query;

	if (MDB_TYPE == 'pg') {
		$table = '';
		if (preg_match('@from\s+(\S+)@i', $db_last_query[$rs], $m)) {
			$table = $m[1];
		}
		$row = pg_fetch_assoc($rs);
		db_fixup_row($table, $row);
	}
	else {
		$row = mysql_fetch_assoc($rs);
	}

	return $row;
}

function db_fetch_result($rs, $row) {
	if (MDB_TYPE == 'pg')
		return pg_fetch_result($rs, $row);
	else 
		return mysql_result($rs, $row);
}

function db_interval($interval) {
	// INTERVAL is standard SQL, but MySQL's quoting is non-standard
	// e.g. "where date > NOW() - ".db_interval('1 day')
	if (MDB_TYPE == 'pg')
		return "INTERVAL '$interval'";
	else
		return "INTERVAL $interval";
}

function db_escape($str) {
	if (MDB_TYPE == 'pg')
		return pg_escape_string($str);
	else {
		if (is_callable('mysql_real_escape_string'))
			return mysql_real_escape_string($str);
		else
			return mysql_escape_string($str);
	}
}

function db_limit($limit, $offset=0) {
	$limit = (int)$limit;
	$offset = (int)$offset;

	if (MDB_TYPE == 'pg')
		return "limit $limit offset $offset";
	else
		return "limit $offset, $limit";
}

function db_match($cols, $against) {
	if (MDB_TYPE == 'pg')
		return 'oid as score';
	else
		return "match ($cols) against ('$against') as score";
}

function db_rlike() {
	if (MDB_TYPE == 'pg')
		return '~*';
	else
		return 'rlike';
}

function array_key_rename(&$array, $old, $new) {
	if (isset($array[$old])) {
		$array[$new] = $array[$old];
		unset($array[$old]);
	}
}

// Fix key case
function db_fixup_row($table, &$row) {
	if ($table == PFX.'textpattern') {
		array_key_rename($row, 'id', 'ID');
		array_key_rename($row, 'posted', 'Posted');
		array_key_rename($row, 'authorid', 'AuthorID');
		array_key_rename($row, 'lastmod', 'LastMod');
		array_key_rename($row, 'lastmodid', 'LastModID');
		array_key_rename($row, 'title', 'Title');
		array_key_rename($row, 'title_html', 'Title_html');
		array_key_rename($row, 'body', 'Body');
		array_key_rename($row, 'body_html', 'Body_html');
		array_key_rename($row, 'excerpt', 'Excerpt');
		array_key_rename($row, 'excerpt_html', 'Excerpt_html');
		array_key_rename($row, 'image', 'Image');
		array_key_rename($row, 'category1', 'Category1');
		array_key_rename($row, 'category2', 'Category2');
		array_key_rename($row, 'annotate', 'Annotate');
		array_key_rename($row, 'annotateinvite', 'AnnotateInvite');
		array_key_rename($row, 'status', 'Status');
		array_key_rename($row, 'section', 'Section');
		array_key_rename($row, 'keywords', 'Keywords');
		array_key_rename($row, 'uposted', 'uPosted');
		array_key_rename($row, 'sposted', 'sPosted');
		array_key_rename($row, 'slastmod', 'sLastMod');
	}
	elseif ($table == PFX.'txp_users') {
		array_key_rename($row, 'realname', 'RealName');
	}
	elseif ($table == PFX.'txp_form') {
		array_key_rename($row, 'form', 'Form');
	}
}


// Helper functions to convert mysql INSERT .. SET syntax to valid SQL

function my_csv_explode($str, $delim = ',', $qual = "'", $esc = '\\') {
	$len = strlen($str);
	$inside = false;
	$word = '';
	for ($i = 0; $i < $len; ++$i) {
		if ($str[$i]==$delim && !$inside) {
			$out[] = $word;
			$word = '';
		} else if ($inside && $str[$i] == $esc && $i<($len-1)) {
			$word .= ($esc.$str[$i+1]);
			++$i;
		} else if ($str[$i] == $qual) {
			$inside = !$inside;
			$word .= $str[$i];
		} else {
			$word .= $str[$i];
		}
	}
	$out[] = $word;
	return $out;
}

function my_insert_to_values($ins) {
	$items = my_csv_explode($ins, ',', "'");

	$cols = array();
	$vals = array();
	foreach ($items as $item) {
		@list($k, $v) = split('=', $item, 2);
		$cols[] = trim($k);
		$vals[] = trim($v);
	}

	return '('.join(',', $cols).')'.
		' VALUES ('.join(',', $vals).')';
}

?>
