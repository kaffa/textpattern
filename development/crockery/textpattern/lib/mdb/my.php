<?php

/*
$HeadURL$
$LastChangedRevision$
*/

/**
 * MySQL specific functions
 */

	define('DB_AUTOINC', 'BIGINT NOT NULL AUTO_INCREMENT');
	define('NULLDATETIME', 0);

class MDB_my extends MDB {

	function connect($host, $user, $pass, $dbname='') {
		return mysql_connect($host, $user, $pass);
	}

	function selectdb($dbname) {
		return mysql_select_db($dbname, $this->link);
	}

	function do_query($q) {
		return mysql_query($q, $this->link);
	}

	function table_exists($tbl) {
		return mysql_query('describe '.$tbl, $this->link) != false;
	}

	function table_list() {
		if ($rs = mysql_query('show tables', $this->link))
			while ($row = mysql_fetch_assoc($rs))
				$out[] = $row;

		$rs = array();
		if ($out) {
			foreach ($out as $r)
				$rs[] = array_shift($r);
		}

		return $rs;
	}

	function index_exists($tbl, $idxname) {
		if ($rs = mysql_query('show index from '.$tbl, $this->link))
			while ($row = mysql_fetch_assoc($rs))
				if ($row['Key_name'] == $idxname)
					return true;
	}

	function column_list($tbl) {
		$cols = array();
		if ($rs = mysql_query('describe '.$tbl, $this->link))
			while ($row = mysql_fetch_assoc($rs))
				$cols[$row['Field']] = $row;
		return $cols;
	}

	function last_insert_id($table) {
		return mysql_insert_id($this->link);
	}

	function lasterror() {
		return mysql_error($this->link);
	}

	function free($rs) {
		return mysql_free_result($rs);
	}

	function num_rows($rs) {
		return mysql_num_rows($rs);
	}

	function fetch_assoc($rs) {
		return mysql_fetch_assoc($rs);
	}

	function fetch_result($rs, $row) {
		return mysql_result($rs, $row);
	}

	function interval($interval) {
		// INTERVAL is standard SQL, but MySQL's quoting is non-standard
		// e.g. "where date > NOW() - ".db_interval('1 day')
		return "INTERVAL $interval";
	}

	function escape($str) {

		if (is_callable('mysql_real_escape_string'))
			return mysql_real_escape_string($str, $this->link);
		else
			return mysql_escape_string($str);
	}

	function limit($limit, $offset=0) {
		$limit = (int)$limit;
		$offset = (int)$offset;

		return "limit $offset, $limit";
	}

	function match($cols, $against) {
		return "match ($cols) against ('$against') as score";
	}

	function rlike() {
		return 'rlike';
	}

	function affected_rows($rs){
		return mysql_affected_rows($this->link);
	}

}

?>
