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

// get the driver specific functions
require_once txpath.'/lib/mdb/'.MDB_TYPE.'.php';

// The functions here are general purpose ones

function get_caller($bt) {
	$caller = $bt[count($bt)-1];
	extract($caller);
	return "$file:$line $function()";
}


function db_insert_rec($table, $rec, $res=0) {
	global $mdb_res;
	$res = ($res ? $res : $mdb_res);

	$cols = array();
	$vals = array();
	foreach (doSlash($rec) as $k=>$v) {
		$cols[] = $k;
		$vals[] = "'".$v."'";
	}

	$sql = '('.join(',', $cols).')'.
		' VALUES ('.join(',', $vals).')';

	if (!db_query('INSERT INTO '.$table.' '.$sql))
		return false;

	$last = db_last_insert_id($table);
	return ($last ? $last : true);
}

function db_update_rec($table, $rec, $where, $res=0) {
	global $mdb_res;
	$res = ($res ? $res : $mdb_res);

	$set = array();
	foreach (doSlash($rec) as $k=>$v) {
		$set[] = $k."='".$v."'";
	}

	$sql = join(',', $set);

	return db_query('UPDATE '.$table.' SET '.$sql.' WHERE '.$where, $res);
}

function db_column_exists($tbl, $col, $res=0) {
	$cols = db_column_list($tbl, $res);
	return !empty($cols[$col]);
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
