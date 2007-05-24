<?php
/*

$HeadURL$
$LastChangedRevision$

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

if (!safe_column_exists('txp_section', 'id')) {
	safe_alter('txp_section', 'drop primary key');
}

safe_upgrade_table('txp_section', array(
	'id' => DB_AUTOINC.' PRIMARY KEY',
	'path' => "varchar(255) not null default ''",
	'parent' => 'INT',
	'lft' => 'INT not null default 0',
	'rgt' => 'INT not null default 0',
	'inherit' => 'SMALLINT not null default 0',
));

safe_update('txp_section', 'path=name', "path=''");

	// shortname has to be unique within a parent
if (!safe_index_exists('txp_section', 'parent_idx')) 
	safe_upgrade_index('txp_section', 'parent_idx', 'unique', 'parent,name');

#if (!safe_index_exists('txp_section', 'path_idx')) 
#	safe_upgrade_index('txp_section', 'path_idx', 'unique', 'path');

safe_update('txp_section', 'parent=0', "name='default'");
$root_id = safe_field('id', 'txp_section', "name='default'");
safe_update('txp_section', "parent='".$root_id."'", "parent IS NULL");
include_once(txpath.'/lib/txplib_tree.php');
tree_rebuild('txp_section', $root_id, 1);

safe_insert('txp_prefs', "prefs_id = 1, event='publish', name = 'markup_default', val = 'txptextile', type = '0', html='pref_markup'");

// <txp:message /> is dropped
safe_update('txp_form', "Form = REPLACE(Form, '<txp:message', '<txp:comment_message')", "1 = 1");

	// Expiry datetime for articles
safe_upgrade_table('textpattern', array(
	'Expires' => "datetime NOT NULL default '0000-00-00 00:00:00' after `Posted`"
));

safe_upgrade_index('textpattern', 'Expires_idx', '', 'Expires');

?>
