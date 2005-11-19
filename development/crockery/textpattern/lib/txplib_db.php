<?php

/*
$HeadURL$
$LastChangedRevision$
*/

define('TXP_DEBUG', 0);

if (!defined('PFX')) {
	if (!empty($txpcfg['table_prefix'])) {
		define ("PFX",$txpcfg['table_prefix']);
	} else define ("PFX",'');
}

set_magic_quotes_runtime(0);

include_once('mdb.php');

class DB {
    function DB() 
	{
		global $txpcfg;

		$this->host = $txpcfg['host'];
		$this->db   = $txpcfg['db'];
		$this->user = $txpcfg['user'];
		$this->pass = $txpcfg['pass'];

		$this->link = @db_connect($this->host, $this->user, $this->pass, $this->db);
		if (!$this->link) die(db_down());

		if (!$this->link) {
			$GLOBALS['connected'] = false;
		} else $GLOBALS['connected'] = true;
		@db_selectdb($this->db) or die(db_down());

		@db_query("SET NAMES ". $txpcfg['dbcharset']);
    }
}
$DB = new DB;

//-------------------------------------------------------------
	function safe_query($q='',$debug='')
	{
		global $DB,$txpcfg, $qcount, $production_status;
		if (!$q) return false;
		if ($debug or TXP_DEBUG === 1) { 
			dmp($q);
			dmp(db_lasterror());
//			dmp(debug_backtrace());
		}
		$result = db_query($q,$DB->link);
		@$qcount++;
		if ($result === false and (@$production_level == 'debug' or @$production_level == 'test'))
			trigger_error(db_lasterror() . n . $q, E_USER_ERROR);

		if(!$result) return false;
		return $result;
	}

// -------------------------------------------------------------
	function safe_delete($table, $where, $debug='')
	{
		$q = "delete from ".PFX."$table where $where";
		if ($r = safe_query($q,$debug)) {
			return true;
		}
		return false;
	}

// -------------------------------------------------------------
	function safe_update($table, $set, $where, $debug='') 
	{
		$q = "update ".PFX."$table set $set where $where";
		if ($r = safe_query($q,$debug)) {
			return true;
		}
		return false;
	}

	function safe_update_rec($table, $rec, $where, $debug='')
	{
		return db_update_rec(PFX.$table, $rec, $where);
	}

// -------------------------------------------------------------
	function safe_insert($table,$set,$debug='') 
	{
		global $DB;
		return db_insert(PFX.$table, $set);
	}

// -------------------------------------------------------------
	function safe_insert_rec($table,$rec,$debug='') 
	{
		global $DB;
		return db_insert_rec(PFX.$table, $rec, $debug);
	}

// -------------------------------------------------------------
// insert or update
	function safe_upsert($table,$set,$where,$debug='') 
	{
		// FIXME: lock the table so this is atomic?
		$wa = (is_array($where) ? join(' and ', $where) : $where);
		$r = safe_update($table, $set, $wa, $debug);
		if ($r and mysql_affected_rows())
			return $r;
		else {
			$wc = (is_array($where) ? join(', ', $where) : $where);
			return safe_insert($table, join(', ', array($wc, $set)), $debug);
		}
	}

// -------------------------------------------------------------
	function safe_table_list($debug='') 
	{
		$out = array();
		$tables = db_table_list();

		// only include tables starting with PFX, and strip the prefix
		foreach ($tables as $table)
			if (preg_match('@^'.preg_quote(PFX).'@', $table))
				$out[] = preg_replace('@^'.preg_quote(PFX).'@', '', $table);

		return $out;
	}

// -------------------------------------------------------------
	function safe_alter($table, $alter, $debug='') 
	{
		$q = "alter table ".PFX."$table $alter";
		if ($r = safe_query($q,$debug)) {
			return true;
		}
		return false;
	}

// -------------------------------------------------------------
	function safe_upgrade_table($table, $cols, $debug='') 
	{
		if (db_table_exists(PFX.$table)) {
			$current = db_column_list(PFX.$table);
			foreach ($cols as $name=>$type) {
				if (empty($current[$name]))
					safe_alter($table, 'add '.$name.' '.$type);
			}
		}
		else {
			$s = array();
			foreach ($cols as $name=>$type)
				$s[] = $name.' '.$type;
			return safe_query('create table '.PFX.$table.' ('.join(",\n", $s).');');
		}
	}

// -------------------------------------------------------------
	function safe_index_exists($table, $idxname, $debug='') 
	{
		return db_index_exists(PFX.$table, PFX.$idxname);
	}

// -------------------------------------------------------------
	function safe_upgrade_index($table, $idxname, $type, $def, $debug='') 
	{
		// $type would typically be '' or 'unique'
		if (!safe_index_exists($table, $idxname))
			return safe_query('create '.$type.' index '.PFX.$idxname.' on '.PFX.$table.' ('.$def.');');
	}

// -------------------------------------------------------------
	function safe_optimize($table, $debug='') 
	{
		$q = "optimize table ".PFX."$table";
		if ($r = safe_query($q,$debug)) {
			return true;
		}
		return false;
	}

// -------------------------------------------------------------
	function safe_repair($table, $debug='') 
	{
		$q = "repair table ".PFX."$table";
		if ($r = safe_query($q,$debug)) {
			return true;
		}
		return false;
	}

// -------------------------------------------------------------
	function safe_field($thing, $table, $where, $debug='') 
	{
		$q = "select $thing from ".PFX."$table where $where";
		$r = safe_query($q,$debug);
		if (@db_num_rows($r) > 0) {
			$f = db_fetch_result($r,0);
			db_free($r);
			return $f;
		}
		return false;
	}

// -------------------------------------------------------------
	function safe_column($thing, $table, $where, $debug='') 
	{
		$q = "select $thing from ".PFX."$table where $where";
		$rs = getRows($q,$debug);
		if ($rs) {
			foreach($rs as $a) {
				$v = array_shift($a);
				$out[$v] = $v;
			}
			return $out;
		}
		return array();
	}

// -------------------------------------------------------------
	function safe_row($things, $table, $where, $debug='') 
	{
		$tables = split(',', $table);
		for ($i=0; $i < count($tables); $i++)
			$tables[$i] = PFX.trim($tables[$i]);
		$table = join(',', $tables;
			
		$q = "select $things from ".PFX."$table where $where";
		$rs = getRow($q,$debug);
		if ($rs) {
			return $rs;
		}
		return array();
	}


// -------------------------------------------------------------
	function safe_rows($things, $table, $where, $debug='') 
	{
		$tables = split(',', $table);
		for ($i=0; $i < count($tables); $i++)
			$tables[$i] = PFX.trim($tables[$i]);
		$table = join(',', $tables;

		$q = "select $things from ".PFX."$table where $where";
		$rs = getRows($q,$debug);
		if ($rs) {
			return $rs;
		}
		return array();
	}

// -------------------------------------------------------------
	function safe_rows_start($things, $table, $where, $debug='') 
	{
		$q = "select $things from ".PFX."$table where $where";
		return startRows($q,$debug);
	}

//-------------------------------------------------------------
	function safe_count($table, $where, $debug='') 
	{
		return getThing("select count(*) from ".PFX."$table where $where",$debug);
	}

// -------------------------------------------------------------
	function safe_show($thing, $table, $debug='') 
	{
		$q = "show $thing from ".PFX."$table";
		$rs = getRows($q,$debug);
		if ($rs) {
			return $rs;
		}
		return array();
	}


//-------------------------------------------------------------
	function fetch($col,$table,$key,$val,$debug='') 
	{
		$q = "select $col from ".PFX."$table where $key = '$val' limit 1";
		if ($r = safe_query($q,$debug)) {
			$thing = (db_num_rows($r) > 0) ? db_fetch_result($r,0) : '';
			db_free($r);
			return $thing;
		}
		return false;
	}

//-------------------------------------------------------------
	function getRow($query,$debug='') 
	{
		if ($r = safe_query($query,$debug)) {
			$row = (db_num_rows($r) > 0) ? db_fetch_assoc($r) : false;
			db_free($r);
			return $row;
		}
		return false;
	}

//-------------------------------------------------------------
	function getRows($query,$debug='') 
	{
		if ($r = safe_query($query,$debug)) {
			if (db_num_rows($r) > 0) {
				while ($a = db_fetch_assoc($r)) $out[] = $a; 
				db_free($r);
				return $out;
			}
		}
		return false;
	}

//-------------------------------------------------------------
	function startRows($query,$debug='')
	{
		return safe_query($query,$debug);
	}

//-------------------------------------------------------------
	function nextRow($r)
	{
		$row = db_fetch_assoc($r);
		if ($row === false)
			db_free($r);
		return $row;
	}

//-------------------------------------------------------------
	function numRows($r)
	{
		return db_num_rows($r);
	}

//-------------------------------------------------------------
	function getThing($query,$debug='') 
	{
		if ($r = safe_query($query,$debug)) {
			$thing = (db_num_rows($r) != 0) ? db_fetch_result($r,0) : '';
			db_free($r);
			return $thing;
		}
		return false;
	}

//-------------------------------------------------------------
	function getThings($query,$debug='') 
	// return values of one column from multiple rows in an num indexed array
	{
		$rs = getRows($query,$debug);
		if ($rs) {
			foreach($rs as $a) $out[] = array_shift($a);
			return $out;
		}
		return array();
	}
	
//-------------------------------------------------------------
	function getCount($table,$where,$debug='') 
	{
		return getThing("select count(*) from ".PFX."$table where $where",$debug);
	}

// -------------------------------------------------------------
 	function getTree($root, $type)
 	{ 

		$root = doSlash($root);
		$type = doSlash($type);

	    extract(safe_row(
	    	"lft as l, rgt as r", 
	    	"txp_category", 
			"name='$root' and type = '$type'"
		));

		if (empty($l) or empty($r))
			return array();

		$out = array();
		$right = array(); 

	    $rs = safe_rows_start(
	    	"id, name, lft, rgt, parent, title", 
	    	"txp_category",
	    	"lft between $l and $r and type = '$type' order by lft asc"
		); 

	    while ($rs and $row = nextRow($rs)) {
	   		extract($row);
			while (count($right) > 0 && $right[count($right)-1] < $rgt) { 
				array_pop($right);
			}

        	$out[] = 
        		array(
        			'id' => $id,
        			'name' => $name,
        			'title' => $title,
        			'level' => count($right), 
        			'children' => ($rgt - $lft - 1) / 2
        		);

	        $right[] = $rgt; 
	    }
    	return($out);
 	}

// -------------------------------------------------------------
 	function getTreePath($target, $type)
 	{ 

	    extract(safe_row(
	    	"lft as l, rgt as r", 
	    	"txp_category", 
			"name='".doSlash($target)."' and type = '".doSlash($type)."'"
		));

	    $rs = safe_rows_start(
	    	"*", 
	    	"txp_category",
				"lft <= $l and rgt >= $r and type = '".doSlash($type)."' order by lft asc"
		); 

		$out = array();
		$right = array(); 

	    while ($rs and $row = nextRow($rs)) {
	   		extract($row);
			while (count($right) > 0 && $right[count($right)-1] < $rgt) { 
				array_pop($right);
			}

        	$out[] = 
        		array(
        			'id' => $id,
        			'name' => $name,
        			'title' => $title,
        			'level' => count($right), 
        			'children' => ($rgt - $lft - 1) / 2
        		);

	        $right[] = $rgt; 
	    }
		return $out;
	}

// -------------------------------------------------------------
	function rebuild_tree($parent, $left, $type) 
	{ 
		$right = $left+1;

		$parent = doSlash($parent);

		$result = safe_column("name", "txp_category", 
			"parent='$parent' and type='$type' order by name");
	
		foreach($result as $row) { 
    	    $right = rebuild_tree($row, $right, $type); 
	    } 

	    safe_update(
	    	"txp_category", 
	    	"lft=$left, rgt=$right",
	    	"name='$parent' and type='$type'"
	    );
    	return $right+1; 
 	} 

// -------------------------------------------------------------
	function db_down() 
	{
		// 503 status might discourage search engines from indexing or caching the error message
		header('Status: 503 Service Unavailable');
		$error = db_lasterror();
		return <<<eod
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
        "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>
	<meta http-equiv="content-type" content="text/html; charset=utf-8" />
	<title>Untitled</title>
</head>
<body>
<p align="center" style="margin-top:4em">Database unavailable.</p>
<!-- $error -->
</body>
</html>
eod;
	}

?>
