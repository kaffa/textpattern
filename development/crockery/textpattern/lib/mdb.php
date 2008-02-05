<?php
/*

	Micro Database Layer

	A simple database compatibility layer for PHP

	Copyright 2005 by Alex Shiels <http://thresholdstate.com/>

	This program is free software; you can redistribute it and/or
	modify it under the terms of the GNU General Public License
	as published by the Free Software Foundation; either version 2
	of the License, or (at your option) any later version.

$HeadURL$
$LastChangedRevision$

*/

# this is required for isolation:
require_once txpath.'/lib/txplib_misc.php';

// One of 'my' or 'pg', MySQL and Postgresql respectively
global $txpcfg;
define('MDB_TYPE', (empty($txpcfg['dbtype']) ? 'my' : $txpcfg['dbtype']));

// get the driver specific functions
require_once txpath.'/lib/mdb/'.MDB_TYPE.'.php';

function &mdb_factory($host, $db, $user, $pass, $charset='', $logfunc=NULL) {
	$class = 'MDB_'.MDB_TYPE;
	$obj = new $class($host, $db, $user, $pass, $charset, $logfunc);
	return $obj;
}

// Abstract MDB class
class MDB {
	var $host;
	var $db;
	var $user;
	var $pass;
	var $charset;

	var $connected = false;
	var $selected = false;
	var $debug = false;
	var $qtime = 0;
	var $qcount = 0;

	var $logfunc = NULL;

	function MDB($host, $db, $user, $pass, $charset='', $logfunc=NULL)
	{
		$this->host = $host;
		$this->db   = $db;
		$this->user = $user;
		$this->pass = $pass;

		if (!$charset) $charset = 'utf8';
		$this->charset = $charset;

		$this->logfunc = $logfunc;

		$this->link = $this->connect($this->host, $this->user, $this->pass, $this->db);
		// PDO returns an object. Do strict comparison
		if ($this->link === false)
			return;

		$this->connected = true;

		if (!$this->selectdb($this->db))
			return;

		$this->selected = true;

		$this->set_charset();
	}


	// abstract functions - override in child classes
	function connect($host, $user, $pass, $db) {
		trigger_error(__FUNCTION__.' not implemented', E_USER_ERROR);
	}

	function selectdb($dbname) {
		trigger_error(__FUNCTION__.' not implemented', E_USER_ERROR);
	}

	function do_query($q) {
		trigger_error(__FUNCTION__.' not implemented', E_USER_ERROR);
	}

	function table_exists($tbl) {
		trigger_error(__FUNCTION__.' not implemented', E_USER_ERROR);
	}

	function table_list() {
		trigger_error(__FUNCTION__.' not implemented', E_USER_ERROR);
	}

	function index_exists($tbl, $idxname) {
		trigger_error(__FUNCTION__.' not implemented', E_USER_ERROR);
	}

	function column_list($tbl) {
		trigger_error(__FUNCTION__.' not implemented', E_USER_ERROR);
	}

	function last_insert_id($tbl) {
		trigger_error(__FUNCTION__.' not implemented', E_USER_ERROR);
	}

	function lasterror() {
		trigger_error(__FUNCTION__.' not implemented', E_USER_ERROR);
	}

	function free($rs) {
		trigger_error(__FUNCTION__.' not implemented', E_USER_ERROR);
	}

	function num_rows($rs) {
		trigger_error(__FUNCTION__.' not implemented', E_USER_ERROR);
	}

	function fetch_assoc($rs) {
		trigger_error(__FUNCTION__.' not implemented', E_USER_ERROR);
	}

	function fetch_result($rs, $row) {
		trigger_error(__FUNCTION__.' not implemented', E_USER_ERROR);
	}

	function interval($interval) {
		trigger_error(__FUNCTION__.' not implemented', E_USER_ERROR);
	}

	function escape($str) {
		trigger_error(__FUNCTION__.' not implemented', E_USER_ERROR);
	}

	function limit($limit, $offset=0) {
		trigger_error(__FUNCTION__.' not implemented', E_USER_ERROR);
	}

	function match($cols, $against) {
		trigger_error(__FUNCTION__.' not implemented', E_USER_ERROR);
	}

	function rlike() {
		trigger_error(__FUNCTION__.' not implemented', E_USER_ERROR);
	}

	function affected_rows($rs) {
		trigger_error(__FUNCTION__.' not implemented', E_USER_ERROR);
	}

	// these needn't be overridden except in special cases

	function set_charset() {
		$this->query("SET NAMES '".$this->charset."'");
	}

	function query($sql, $debug=false) {
		$start = getmicrotime();
		$result = $this->do_query($sql);
		$time = sprintf('%02.6f', getmicrotime() - $start);
		$this->qtime += $time;
		$this->qcount++;

		if (is_callable($this->logfunc))
			call_user_func($this->logfunc, "SQL[{$time}] $sql");

		if ($result === false and ($this->debug or $debug)) {
			trigger_error($this->lasterror() . n . $sql, E_USER_WARNING);
		}

		if(!$result) return false;
		return $result;
	}

	function column_exists($tbl, $col) {
		$cols = $this->column_list($tbl, $res);
		return !empty($cols[$col]);
	}


	function insert($table, $set) {
		$set = 'SET '.$set;

		if (!$this->query('INSERT INTO '.$table.' '.$set)) {
			return false;
		}

		$last = $this->last_insert_id($table);
		return ($last ? $last : true);
	}


	function insert_rec($table, $rec) {

		$cols = array();
		$vals = array();
		foreach (doSlash($rec) as $k=>$v) {
			$cols[] = $k;
			$vals[] = "'".$v."'";
		}

		$sql = '('.join(',', $cols).')'.
			' VALUES ('.join(',', $vals).')';

		if (!$this->query('INSERT INTO '.$table.' '.$sql))
			return false;

		$last = $this->last_insert_id($table);
		return ($last ? $last : true);
	}

	function update_rec($table, $rec, $where) {
		$set = array();
		foreach (doSlash($rec) as $k=>$v) {
			$set[] = $k."='".$v."'";
		}

		$sql = join(',', $set);

		return $this->query('UPDATE '.$table.' SET '.$sql.' WHERE '.$where);
	}

}

?>
