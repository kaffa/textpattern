<?php

if (!defined('TXP_INSTALL'))
	exit;

@ignore_user_abort(1);
@set_time_limit(0);

include_once txpath.'/lib/txplib_db.php';
#$GLOBALS['DB'] = $DB;
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
	'txp_plugin_table',
	'txp_prefs_table',
);

$GLOBALS['txp_install_successful'] = true;
$GLOBALS['txp_error_messages'] = array();

error_reporting(E_ALL ^ E_NOTICE);
set_error_handler('setup_error_handler');

include_once txpath.'/setup/data.php';
$default_rows = new textpattern_setup_rows();

foreach ($textpattern_tables as $table_name) {
	
	$table = new $table_name($DB);
	/* @var $table zem_table */
	$result = $table->create_table();
	$db_error = db_lasterror();
	if (!$result && !empty($db_error)) 
	{
		$GLOBALS['txp_error_messages'][] = $db_error;
		$GLOBALS['txp_install_successful'] = false;
	}else{
		if (method_exists($default_rows,"$table_name")){
			$default_rows->{$table_name}();
			$table->upgrade_table();
		}
		
	}
}

restore_error_handler();
error_reporting(E_ALL);

if (MDB_TYPE == 'pg') {
	# mimic some mysql-specific functions in postgres
	db_query("create function unix_timestamp(timestamp) returns integer as 'select date_part(''epoch'', $1)::int4 as result' language 'sql';");
	db_query("create function from_unixtime(integer) returns abstime as 'select abstime($1) as result' language 'sql';");
	db_query("create function password(text) returns text as 'select md5($1) as result' language 'sql';");
	db_query("create function old_password(text) returns text as 'select md5($1) as result' language 'sql';");
}

# Skip the RPC language fetch when testing
if (defined('TXP_TEST'))
	return;
$blog_uid = safe_field('val','txp_prefs',"name='blog_uid'");
require_once txpath.'/lib/IXRClass.php';
$client = new IXR_Client('http://rpc.textpattern.com');
if (!$client->query('tups.getLanguage',$blog_uid,$lang))
{
	# If cannot install from lang file, setup the english lang
	if (!install_language_from_file($lang))
	{
		$lang = 'en-gb';
		include_once txpath.'/setup/en-gb.php';
		if (!@$lastmod) $lastmod = $zerodatetime;
		foreach ($en_gb_lang as $evt_name => $evt_strings)
		{
			foreach ($evt_strings as $lang_key => $lang_val)
			{
				$lang_val = addslashes($lang_val);
				if (@$lang_val)
					db_query("INSERT INTO ".PFX."txp_lang (lang,name,event,data,lastmod) VALUES ('en-gb','$lang_key','$evt_name','$lang_val','$lastmod')");
			}
		}
	}
}else {
	$response = $client->getResponse();
	$lang_struct = unserialize($response);
	if (MDB_TYPE == 'pdo_sqlite') {
		
		$stmt = db_prepare("INSERT INTO ".PFX."txp_lang (lang,name,event,data,lastmod) VALUES ('$lang', ?, ?, ?, ?)");
		foreach ($lang_struct as $item){
			$stmt->execute(array_values($item));
		}
	}else{
		foreach ($lang_struct as $item)
		{
			foreach ($item as $name => $value) 
				$item[$name] = addslashes($value);
			db_query("INSERT INTO ".PFX."txp_lang (lang,name,event,data,lastmod) VALUES ('$lang','$item[name]','$item[event]','$item[data]','".strftime('%Y-%m-%d %H:%M:%S',$item['uLastmod'])."')");
		}
	}		
}

?>