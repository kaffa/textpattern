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

?>
