<?php
/*

$HeadURL: $
$LastChangedRevision: $

*/

safe_upgrade_table('textpattern', array(
	'markup_body' => 'varchar(32)',
	'markup_excerpt' => 'varchar(32)',
));

// user-specific preferences
safe_upgrade_table('txp_prefs_user', array(
	'id' => DB_AUTOINC.' PRIMARY KEY',
	'user' => "varchar(64) NOT NULL default ''",
	'name' => "varchar(255) NOT NULL default ''",
	'val' => "varchar(255) NOT NULL default ''",
));

// unique index on user+name
safe_upgrade_index('txp_prefs_user', 'user_idx', 'unique', 'user, name');

safe_upgrade_table('txp_element', array(
	'name' => 'varchar(64) NOT NULL PRIMARY KEY',
	'event' => "varchar(64) NOT NULL default ''",
	'step' => "varchar(64) NOT NULL default ''",
	'required' => "smallint default 0",
	'type' => "smallint default 0",
	'status' => "smallint default 0",
	'hash' => "varchar(40) NOT NULL default ''",
	'version' => "int NOT NULL default 0",
	'modified' => "timestamp",
	'created' => "timestamp",
));

safe_upsert('txp_element',
	"event='element', required=1, status=1, created=now(), modified=now()", "name='txp_element'");
safe_upsert('txp_element',
	"event='', required=1, status=1, created=now(), modified=now()", "name='txp_tab'");
safe_upsert('txp_element',
	"event='file', required=0, status=1, created=now(), modified=now()", "name='txp_file'");
safe_upsert('txp_element',
	"event='pub_file', required=0, status=1, created=now(), modified=now()", "name='pub_file'");
safe_upsert('txp_element',
	"event='pub_page', required=0, status=1, created=now(), modified=now()", "name='tag_file'");

?>
