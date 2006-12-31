<?php

// Copyright 2006 Alex Shiels http://thresholdstate.com/

/*
$HeadURL$
$LastChangedRevision$
*/

// db engine specific
// FIXME: reconcile this with mdb
define('ZEM_PRIMARY_KEY', 'bigint auto_increment');
define('ZEM_FOREIGN_KEY', 'bigint');
define('ZEM_MEDIUMTEXT', 'mediumtext');
if (MDB_TYPE == 'pg') {
	define('ZEM_DATETIME','timestamp without time zone');
	define('ZEM_INCVAL','DEFAULT');
	define('ZEM_TINYTEXT','text');
}else {
	define('ZEM_DATETIME','datetime');
	define('ZEM_INCVAL','NULL');
	
	if (MDB_TYPE == 'pdo_sqlite') {
		define('ZEM_TINYTEXT','text');
	}else{
		define('ZEM_TINYTEXT','tinytext');
	}
	
}



// abstract table class, represents a database table
// nb: this is concerned with _rows_, not the particular contents of each row
class zem_table {
	var $DB;
	var $_table_name;
	var $_debug = false;

	// name => sql column type definition
	var $_cols = array(
		'id' => ZEM_PRIMARY_KEY,
	);

	// primary key name
	var $_primary_key = 'id';

	// constructor
	function zem_table(&$db=NULL) {
		// store a reference to the database
		if ($db) {
			$this->DB = $db;
		}
		else {
			global $DB;
			$this->DB =& $DB;
		}
	}

	function create_table() {
		// upgrade_table will create the table if it doesn't exist
		$this->upgrade_table();
	}

	function upgrade_table() {
		safe_upgrade_table($this->_table_name, $this->_cols, $this->_primary_key, $this->_debug);
	}

	function drop_table() {
		return safe_query('DROP TABLE '.safe_pfx($this->_table_name), $this->_debug);
	}

	function table_exists() {
		return db_table_exists(PFX.$this->_table_name);
	}

//-------------------------------------------------------------
	function table_name($table='')
	{
		if (!$table)
			return PFX.trim($this->_table_name);

		$ts = array();
		foreach (explode(',', $table) as $t) {
			$name = PFX.trim($t);
				$ts[] = "$name".(PFX ? " as $t" : '');
		}
		return join(', ', $ts);
	}

//-------------------------------------------------------------
	function query($q='',$debug='')
	{
		global $qcount, $qtime;

		if (!$q)
			return false;

		$start = getmicrotime();
		$result = db_query($q,$this->DB->link);

		if ($debug or $this->_debug)
			dmp($q, $result);

		$time = sprintf('%02.6f', getmicrotime() - $start);
		@$qtime += $time;
		@$qcount++;
		if ($result === false and (txpinterface === 'admin' or !is_production_status('live'))) {
			$caller = is_production_status('debug') ? n . join("\n", get_caller()) : '';
			trigger_error(db_lasterror() . n . $q . $caller, E_USER_WARNING);
		}

		trace_add("[SQL ($time): $q]");

		if(!$result) return false;
		return $result;
	}

// -------------------------------------------------------------
	function getRow($query,$debug='') 
	{
		if ($r = $this->query($query,$debug)) {
			$row = (db_num_rows($r) > 0) ? db_fetch_assoc($r) : false;
			db_free($r);
			return $row;
		}
		return false;
	}

// -------------------------------------------------------------
	function getRows($query,$debug='') 
	{
		if ($r = $this->query($query,$debug)) {
			if (db_num_rows($r) > 0) {
				while ($a = db_fetch_assoc($r)) $out[] = $a; 
				db_free($r);
				return $out;
			}
		}
		return false;
	}


//-------------------------------------------------------------
	function nextRow(&$r)
	{
		$row = db_fetch_assoc($r);
		if ($row === false)
			db_free($r);
		return $row;
	}

//-------------------------------------------------------------
	function getThing($query,$debug='')
	{
		if ($r = $this->query($query,$debug)) {
			$thing = (db_num_rows($r) != 0) ? db_fetch_result($r,0) : '';
			db_free($r);
			return $thing;
		}
		return false;
	}

//-------------------------------------------------------------
	function _where($where, $extra=array()) {
		// $where: an array of column=>value pairs (use a numeric key to include a more complex clause)
		// $extra: an array of query parameters like 'sort', 'group', 'limit', 'offset'
		// examples:
		// _where(array('foo'=>'123', 'bar'=>'baz'))
		// _where(array('foo in (1,2,3)', 'bar'=>'baz'))
		// _where(array('foo'=>'123', array('limit'=>5))
		// or, for quick queries:
		// _where('foo > 1')

		$w = array();
		if (is_array($where)) {
			foreach ($where as $k=>$v)
				// if the key is numeric, pass the value through as-is
				if (is_numeric($k))
					$w[] = $v;
				// otherwise use an equals comparison
				else
					$w[] = doSlash($k)."='".doSlash($v)."'";
		}
		else {
			// a simple string
			if ($where)
				$w[] = $where;
		}

		if ($w)
			$out = join(' AND ', $w);
		else
			$out = '1=1';

		if (isset($extra['group']))
			$out .= ' GROUP BY '.doSlash($extra['group']);

		if (isset($extra['sort']))
			$out .= ' ORDER BY '.doSlash($extra['sort']);

		if (isset($extra['limit']) or isset($extra['offset']))
			$out .= ' '.db_limit(@$extra['limit'], @$extra['offset']);

		return $out;
	}

//-------------------------------------------------------------
// following are the main public functions for querying tables.
// use these rather than those above whenever possible

	// fetch multiple rows
	// returns a resultset to be passed to $this->next_row()
	function rows($where = array(), $extra = array(), $cols='*') {
		$q = 'select '.$cols.' from '.$this->table_name().' where '.$this->_where($where, $extra);
		return $this->query($q, $this->_debug);
	}

	function next_row(&$rs) {
		return $this->nextRow($rs);
	}

	// return all rows in an array - for fetching small sets only
	function rows_array($where = array(), $extra = array(), $cols='*') {
		$q = 'select '.$cols.' from '.$this->table_name().' where '.$this->_where($where, $extra);
		return $this->getRows($q, $this->_debug);
	}

	// return one single row
	function row($where = array(), $extra = array(), $cols='*') {
		$e = array('limit' => 1, 'offset' => 0);
		$q = 'select '.$cols.' from '.$this->table_name().' where '.$this->_where($where, $extra + $e);
		return $this->getRow($q, $this->_debug);
	}

	// fetch a row by its primary key
	function row_id($id, $extra = array()) {
		return $this->row(array($this->_primary_key => $id), $extra);
	}

	function count($where=array(), $what='*') {
		return $this->getThing('select count('.$what.') from '.$this->table_name().' where '.$this->_where($where));
	}

	// delete a row by its primary key
	function delete_id($id) {
		return $this->delete(array($this->_primary_key => $id));
	}

// -------------------------------------------------------------
	function insert($row) {
		$cols = array();
		$vals = array();
		foreach (array_keys($this->_cols) as $c) {
			if (isset($row[$c])) {
				$cols[] = $c;
				$vals[] = "'".doSlash($row[$c])."'";
			}
		}

		$q = 'INSERT INTO '.$this->table_name()
			.(' ('.join(',', $cols).')')
			.(' VALUES ('.join(',', $vals).')');

		if (!$this->query($q, $this->_debug)) {
			return false;
		}

		$last = db_last_insert_id($this->table_name());
		return ($last ? $last : true);
	}

// -------------------------------------------------------------
	function update($row, $where=array()) {
		$set = array();
		foreach (array_keys($this->_cols) as $c) {
			if (isset($row[$c])) {
				$set[] = $c."='".doSlash($row[$c])."'";
			}
		}

		if (!$where) {
			if (isset($row[$this->_primary_key]))
				$where = array($this->_primary_key => $row[$this->_primary_key]);
			else {
				trigger_error('update: no where clause specified');
				return false;
			}
		}

		$q = 'update '.$this->table_name().' set '.join(',', $set).' where '.$this->_where($where);
		return $this->query($q, $this->_debug);
	}

// -------------------------------------------------------------
	function delete($where, $debug='')
	{
		$q = 'delete from '.$this->table_name().' where '.$this->_where($where);
		if ($r = $this->query($q,$debug)) {
			return true;
		}
		return false;
	}


}


?>
