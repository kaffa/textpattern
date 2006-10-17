<?php

/**
 * PostgreSQL specific functions
 */

	define('DB_AUTOINC', 'SERIAL NOT NULL');

function db_connect($host, $user, $pass, $dbname='') {
	global $mdb_res;
	return $mdb_res = pg_connect(($host and $host != 'localhost' ? "host='".addslashes($host)."' " : '')."user='".addslashes($user)."' password='".addslashes($pass)."' dbname='".addslashes($dbname)."'");
}

function db_selectdb($dbname) {
	global $mdb_res;
	return (pg_connection_status($mdb_res) === PGSQL_CONNECTION_OK);
}

function db_query($q, $res=0) {
	global $mdb_res, $db_last_query;
	$res = ($res ? $res : $mdb_res);
	$r = pg_query($res, $q);

	$db_last_query[$r] = $q;

	return $r;
}

function db_insert($table, $set, $res=0) {
	global $mdb_res;
	$res = ($res ? $res : $mdb_res);
	$set = my_insert_to_values($set);

	if (!db_query('INSERT INTO '.$table.' '.$set, $res)) {
		return false;
	}

	$last = db_last_insert_id($table);
	return ($last ? $last : true);
}

function db_table_exists($tbl) {
	global $mdb_res;
	return @pg_meta_data($mdb_res, $tbl) != false;
}

function db_table_list() {
	if ($rs = pg_query($mdb_res, 'select relname from pg_stat_user_tables order by relname'))
		$out = pg_fetch_all($rs);

	$rs = array();
	if ($out) {
		foreach ($out as $r)
			$rs[] = array_shift($r);
	}

	return $rs;
}

function db_index_exists($tbl, $idxname) {
	global $mdb_res;
	# select c1.relname as name, c2.relname as table from pg_catalog.pg_class as c1  JOIN pg_catalog.pg_index i ON i.indexrelid = c1.oid join pg_catalog.pg_class c2 ON i.indrelid = c2.oid where c1.relkind='i';
	return db_query("select c1.relname as name, c2.relname as table from pg_catalog.pg_class as c1 JOIN pg_catalog.pg_index i ON i.indexrelid = c1.oid join pg_catalog.pg_class c2 ON i.indrelid = c2.oid where c1.relkind='i' and table='".db_escape($tbl)."' and name='".db_escape($tbl)."';");
}

function db_column_list($tbl, $res=0) {
	global $mdb_res;
	$res = ($res ? $res : $mdb_res);

	$cols = @pg_meta_data($mdb_res, $tbl);
	return $cols ? $cols : array();
}

function db_last_insert_id($table, $res=0) {
	global $mdb_res;
	$res = ($res ? $res : $mdb_res);

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

function db_lasterror() {
	global $mdb_res;
	return pg_last_error($mdb_res);
}

function db_free($rs) {

	return pg_free_result($rs);
}

function db_num_rows($rs) {
	
	return pg_num_rows($rs);
}

function db_fetch_assoc($rs) {
	global $db_last_query;

	$table = '';
	if (preg_match('@from\s+(\S+)@i', $db_last_query[$rs], $m)) {
		$table = $m[1];
	}
	$row = pg_fetch_assoc($rs);
	db_fixup_row($table, $row);

	return $row;
}

function db_fetch_result($rs, $row) {
	
	return pg_fetch_result($rs, $row);
}

function db_interval($interval) {
	// INTERVAL is standard SQL, but MySQL's quoting is non-standard
	// e.g. "where date > NOW() - ".db_interval('1 day')
	return "INTERVAL '$interval'";
}

function db_escape($str) {

	return pg_escape_string($str);
}

function db_limit($limit, $offset=0) {
	$limit = (int)$limit;
	$offset = (int)$offset;

	return "limit $limit offset $offset";
}

function db_match($cols, $against) {
	
	return 'oid as score';
}

function db_rlike() {
	
	return '~*';
}

function db_affected_rows(){
	
	return pg_affected_rows();
}

?>
