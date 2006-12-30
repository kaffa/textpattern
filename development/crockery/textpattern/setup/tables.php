<?php

if (!defined('TXP_INSTALL'))
	exit;

@ignore_user_abort(1);
@set_time_limit(0);

include_once txpath.'/lib/txplib_db.php';
$GLOBALS['DB'] = $DB;
include_once txpath.'/model/txp_tables.php';

$textpattern_tables = array('txp_article_table','txp_category_table','txp_section_table');

foreach ($textpattern_tables as $table_name) {
	
	$table = new $table_name($DB);
	/* @var $table zem_table */
	$table->create_table();
}

?>