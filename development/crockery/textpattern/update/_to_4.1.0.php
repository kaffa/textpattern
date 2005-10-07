<?php
/*

$HeadURL: $
$LastChangedRevision: $

*/

safe_upgrade_table('textpattern', array(
	'markup_body' => 'varchar(32)',
	'markup_excerpt' => 'varchar(32)',
));

?>
