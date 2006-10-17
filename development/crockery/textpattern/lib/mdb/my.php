<?php

/**
 * MySQL specific functions
 */

	define('DB_AUTOINC', 'BIGINT NOT NULL AUTO_INCREMENT');

function db_connect($host, $user, $pass, $dbname='') {
	global $mdb_res;
	return $mdb_res = mysql_connect($host, $user, $pass);
}

function db_selectdb($dbname) {
	global $mdb_res;
	return mysql_select_db($dbname, $mdb_res);
}

function db_query($q, $res=0) {
	global $mdb_res, $db_last_query;
	$res = ($res ? $res : $mdb_res);
	$r = mysql_query($q, $res);

	$db_last_query[$r] = $q;

	return $r;
}

function db_insert($table, $set, $res=0) {
	global $mdb_res;
	$res = ($res ? $res : $mdb_res);
	$set = 'SET '.$set;

	if (!db_query('INSERT INTO '.$table.' '.$set, $res)) {
		return false;
	}

	$last = db_last_insert_id($table);
	return ($last ? $last : true);
}

function db_table_exists($tbl) {
	global $mdb_res;
	return mysql_query('describe '.$tbl) != false;
}

function db_table_list() {
	global $mdb_res;
	$out = array();
	if ($rs = mysql_query('show tables')) 
		while ($row = mysql_fetch_assoc($rs))
			$out[] = $row;

	$rs = array();
	if ($out) {
		foreach ($out as $r)
			$rs[] = array_shift($r);
	}

	return $rs;
}

function db_index_exists($tbl, $idxname) {
	global $mdb_res;

	if ($rs = mysql_query('show index from '.$tbl)) 
		while ($row = mysql_fetch_assoc($rs))
			if ($row['Key_name'] == $idxname)
				return true;
}

function db_column_list($tbl, $res=0) {
	global $mdb_res;
	$res = ($res ? $res : $mdb_res);

	$cols = array();
	if ($rs = mysql_query('describe '.$tbl)) 
		while ($row = mysql_fetch_assoc($rs))
			$cols[$row['Field']] = $row;
	return $cols;
}

function db_last_insert_id($table, $res=0) {
	global $mdb_res;
	$res = ($res ? $res : $mdb_res);

	return mysql_insert_id($res);
}

function db_lasterror() {
	global $mdb_res;
	return mysql_error();
}

function db_free($rs) {
	return mysql_free_result($rs);
}

function db_num_rows($rs) {
	return mysql_num_rows($rs);
}

function db_fetch_assoc($rs) {
	global $db_last_query;

	$row = mysql_fetch_assoc($rs);

	return $row;
}

function db_fetch_result($rs, $row) {
	
	return mysql_result($rs, $row);
}

function db_interval($interval) {
	// INTERVAL is standard SQL, but MySQL's quoting is non-standard
	// e.g. "where date > NOW() - ".db_interval('1 day')
	return "INTERVAL $interval";
}

function db_escape($str) {

	if (is_callable('mysql_real_escape_string'))
		return mysql_real_escape_string($str);
	else
		return mysql_escape_string($str);
}

function db_limit($limit, $offset=0) {
	$limit = (int)$limit;
	$offset = (int)$offset;

	return "limit $offset, $limit";
}

function db_match($cols, $against) {
	
	return "match ($cols) against ('$against') as score";
}

function db_rlike() {
	
	return 'rlike';
}

function db_affected_rows(){
	
	return mysql_affected_rows();
}

?>
