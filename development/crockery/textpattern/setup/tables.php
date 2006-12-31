<?php

if (!defined('TXP_INSTALL'))
	exit;

@ignore_user_abort(1);
@set_time_limit(0);

include_once txpath.'/lib/txplib_db.php';
$GLOBALS['DB'] = $DB;
include_once txpath.'/model/txp_tables.php';

$textpattern_tables = array(
	'txp_article_table',
	'txp_category_table',
	'txp_section_table',
	'txp_css_table',
	'txp_page_table',
	'txp_users_table',
	'txp_discuss_table',
	'txp_discuss_ipban_table',
	'txp_discuss_nonce_table',
	'txp_file_table',
	'txp_form_table',
	'txp_image_table',
	'txp_lang_table',
	'txp_link_table',
	'txp_log_table',
/*	'txp_prefs_table',
*/

);

foreach ($textpattern_tables as $table_name) {
	
	$table = new $table_name($DB);
	/* @var $table zem_table */
	$table->create_table();
}

?>