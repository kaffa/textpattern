<?php

/*
$HeadURL$
$LastChangedRevision$
*/

	if (!defined('TXP_UPDATE'))
	{
		exit("Nothing here. You can't access this file directly.");
	}

	$has_idx = 0;
	$rs = getRows('show index from `'.PFX.'txp_plugin`');
	foreach ($rs as $row) {
		if ($row['Key_name'] == 'status_type_idx')
			$has_idx = 1;
	}

	if (!$has_idx) {
		safe_query('alter ignore table `'.PFX.'txp_plugin` add index status_type_idx(`status`, `type`)');
	}

	

?>

