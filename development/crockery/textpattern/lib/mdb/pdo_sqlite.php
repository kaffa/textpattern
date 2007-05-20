<?php

/*
$HeadURL$
$LastChangedRevision$
*/

/**
 * PDO_SQLITE specific functions
 * 
 * Warning: this driver is highly experimental and needs an advanced
 * PHP5 version to work properly. Do not use it for production sites.
 * 
 * @author Pedro Palazón http://kusor.com
 * @todo better now() function based on the sqlite column datatype
 * 
 * Use of this software implies aceptance of the mdb license agreement
 */

# I am using the comment /* @var $res PDO */ to get code completion on ZendIDE-5:
# "Best programmers are the lazy ones" :-)

# supported PDO:: constants by sqlite (complete this list).
# PDO::ATTR_SERVER_VERSION
# PDO::ATTR_ERRMODE
# PDO::ATTR_CASE
# PDO::ATTR_DRIVER_NAME

define('DB_AUTOINC', 'INTEGER');
define('NULLDATETIME', 0); /* @todo: add a PDO specific constant for 'no date' */

if (!extension_loaded('pdo') && !load_extension('pdo')) {
	die("PDO extension not loaded");
}
	
if (!extension_loaded('pdo_sqlite') && !load_extension('pdo_sqlite')) {
	die("SQLite extension not loaded");
}

// Move this to a top library file?
function load_extension($ext_name){
	
	if (is_windows()){
		$loaded = @dl($ext_name.'.dll');
	}else{
		$loaded = @dl($ext_name.'.so');
	}
	return $loaded;
}

function db_connect($host, $user, $pass, $dbname='') {
	global $mdb_res, $pdo_error;

	try {
		$mdb_res = new PDO("sqlite:$dbname");
		// PDO::sqliteCreateFunction() does not works on windows PHP 5.0.5
		// and pdo_sqlite build of 2005-11-08 15:11:44 (http://pecl4win.php.net/ext.php/php_pdo_sqlite.dll)
		$mdb_res->sqliteCreateFunction('now','mdb_sqlite_now',0);
		$mdb_res->sqliteCreateFunction('md5','md5',1);
		$mdb_res->sqliteCreateFunction('unix_timestamp','strtotime',1);
		$mdb_res->sqliteCreateFunction('from_unixtime','mdb_sqlite_from_unixtime',1);
		$mdb_res->sqliteCreateFunction('password','md5',1);
		$mdb_res->sqliteCreateFunction('old_password','md5',1);
		return $mdb_res;
	}catch (PDOException $e){
		$pdo_error = $e;
		echo 'Connection failed: ' . $e->getMessage();
		return false;
	}
}

function db_selectdb($dbname) {
	global $mdb_res;
	return ($mdb_res->errorCode() == '');
}

function db_query($q, $res=0) { 
	
	global $mdb_res, $db_last_query,$db_affected_rows;
	$res = ($res ? $res : $mdb_res);
	
	/* @var $res PDO */
	
	try {
		$r = $res->query($q);
		if (get_class($r) =='PDOStatement') {
			$db_affected_rows = $r->rowCount();
		}
	}catch (PDOException  $e){
		echo 'Connection failed: ' . $e->getMessage();
	}

	if (!$r) {
		trigger_error('failed query: '.$q.n);
		trigger_error('error: '.db_lasterror());
	}
	
	# We have a ["queryString"] =>  string(23) "PRAGMA encoding="UTF-8"" member on the statement object
	//$db_last_query[$r] = $q;
	return $r;
}

function db_insert($table, $set, $res=0) {
	global $mdb_res;
	$res = ($res ? $res : $mdb_res);
	/* @var $res PDO */
	$set = my_insert_to_values($set);
	$q = 'INSERT INTO '.$table.' '.$set;
	if (db_query($q) === false) {
		echo 'failed insert: '.$q.n;
		echo db_lasterror().n;
		return false;
	}

	$last = $res->lastInsertId();
	return ($last ? $last : true);
}

function db_table_exists($tbl) {
	global $mdb_res;
	/* @var $st PDOStatement */
	$st = $mdb_res->query("SELECT name FROM sqlite_master WHERE type='table' AND tbl_name='$tbl'");
	if ($st !== false) {
		$table = $st->fetch(PDO::FETCH_ASSOC);
		return ($table['name'] == $tbl);
	}
	return false;
}

function db_table_list() {
	global $mdb_res;
	/* @var $res PDO */
	$st = $mdb_res->query("SELECT * FROM sqlite_master WHERE type='table'");

	$rs = array();
	if ($out) {
		foreach ($out as $r)
			$rs[] = array_shift($r);
	}

	return $rs;
}

function db_index_exists($tbl, $idxname) {
	global $mdb_res;
	/* @var $mdb_res PDO */
	$st = $mdb_res->query("SELECT tbl_name FROM sqlite_master WHERE type='index' AND name='$idxname'");
	if ($st !== false) {
		$table = $st->fetch(PDO::FETCH_ASSOC);
		return ($tbl == $table['tbl_name']);
	}
	return false;
}

function db_column_list($tbl, $res=0) {
	global $mdb_res;
	$res = ($res ? $res : $mdb_res);
	/* @var $res PDO */
	$st = $res->query("SELECT * FROM $tbl LIMIT 1");
	/* @var $res PDOStatement */
	$c = $st->columnCount();
	if ($c > 0) {
		for ($i=0;$i<$c;$i++){
			$meta = $st->getColumnMeta($i);
			$cols[$meta['name']] = $meta['sqlite:decl_type'];
		}
		# getColumnMeta does not work with empty tables:
		if (sizeof($cols)!=$c) {
			unset($cols);
			$st2 = $res->query("SELECT sql FROM sqlite_master WHERE type='table' AND tbl_name='$tbl'");
			/* @var $st PDOStatement */
			if ($st2 !== false) {
				$sql = $st2->fetch(PDO::FETCH_ASSOC);
				extract($sql);
				$sql = substr($sql, (strpos($sql,'(')+1));
				$cls = explode(',',$sql);
				$cls = doArray($cls, 'trim');
				foreach ($cls as $val) {
					$col = explode(' ',$val);
					$cols[$col[0]]= $col[1];
				}
				# remove any primary key sentence and keep the lenght equal to the columns number
				$cols = array_slice($cols, 0, $c);
			}
		}
	}
	return $cols ? $cols : array();
}

function db_last_insert_id($table, $res=0) {
	global $mdb_res;
	$res = ($res ? $res : $mdb_res);
	/* @var $res PDO */
	return $res->lastInsertId();
}

function db_lasterror() {
	global $mdb_res;
	/* @var $mdb_res PDO */
	$einfo = $mdb_res->errorInfo();
	return ((sizeof($einfo)>1) ?$einfo[2]: '');
}

function db_free($rs) {
	unset($rs);
	return true;
}

/*
For most databases, PDOStatement::rowCount() does not return the number of rows affected 
by a SELECT statement. Instead, use PDO::query() to issue a SELECT COUNT(*) statement 
with the same predicates as your intended SELECT statement, 
then use PDOStatement::fetchColumn() to retrieve the number of rows that will be returned
*/

function db_num_rows($rs) {
	/* @var $mdb_res PDO */
	global $mdb_res;
//	echo var_dump($rs);
	/* @var $rs PDOStatement */
	$is_select = (isset($rs->queryString) && strpos($rs->queryString,'select') !== false)? true : false;
	if ($is_select !== false) {
		$what = explode('from',$rs->queryString);
		$c_q = 'select count(*) from'.$what[1];
		if($r = $mdb_res->query($c_q)){
			/* @var $r PDOStatement */
			$f = $r->fetch(PDO::FETCH_NUM);
			return $f[0];
		}
	}
	return 0;
}

function db_fetch_assoc($rs) {
	global $db_last_query;
	/* @var $mdb_res PDOStatement */
	
	$table = '';
	if (preg_match('@from\s+(\S+)@i', $rs->queryString, $m)) {
		$table = $m[1];
	}
	$row = $rs->fetch(PDO::FETCH_ASSOC);
	db_fixup_row($table, $row);

	return $row;
}

function db_fetch_result($rs, $row) {
	// improve
	$col = $rs->fetchAll(PDO::FETCH_COLUMN, $row);
	return $col[0];
}

function db_interval($interval) {
	// INTERVAL is standard SQL, but MySQL's quoting is non-standard
	// e.g. "where date > NOW() - ".db_interval('1 day')
	return "INTERVAL '$interval'";
}
function db_quote($str) {
	global $mdb_res;
	/* @var $mdb_res PDO */
	return $mdb_res->quote($str);
	// Warning: the quote method is not available for all drivers
	// quote allways enclose the string into single quotes
}

// due to the problems when you use doSlash and pdo quote at once,
// better to define a backward compatible funtion and keep txp as is

function db_escape($str) {
	global $mdb_res;
	/* @var $mdb_res PDO */
	$str = $mdb_res->quote($str);
	# ugly, ugly, ugly
	if (strpos($str,"'") === 0 && (strrpos($str,"'") === (strlen($str)-1))) {
		$str = substr($str,1,(strlen($str)-2));
	}
	return $str;
}

function db_limit($limit, $offset=0) {
	$limit = (int)$limit;
	$offset = (int)$offset;

	return "limit $limit offset $offset";
}
/*
function db_match($cols, $against) {
	
	return 'oid as score';
}

function db_rlike() {
	
	return '~*';
}*/

function db_affected_rows(){
	global $db_affected_rows;
	return $db_affected_rows;
}

# could we add these ones when supported? 
# It will be faster for language installs, for example:

function db_prepare($q){
	global $mdb_res;
	/* @var $mdb_res PDO */
	return $mdb_res->prepare($q);
}

function db_exec($q, $res=0) { 
	
	global $mdb_res, $db_last_query;
	$res = ($res ? $res : $mdb_res);
	
	/* @var $res PDO */
	
	try {
		$r = $res->exec($q);
	}catch (PDOException  $e){
		echo 'Connection failed: ' . $e->getMessage();
	}

	if (!$r) {
		trigger_error('failed query: '.$q.n);
		trigger_error('error: '.db_lasterror());
	}
	return $r;
}

/**
 * MySQL mimic of some functions to be used as UDF
 */


# this takes not fields type into consideration, adding the whole time to date columns
function mdb_sqlite_now(){
	return date('Y-m-d H:i:s');
}

function mdb_sqlite_from_unixtime($integer){ return strftime("%Y-%m-%d %H:%M:%s",$integer);}


?>
