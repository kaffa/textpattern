<?php

/*
$HeadURL$
$LastChangedRevision$
*/

register_callback('element_list', 'element');

function element_list($event, $step) {

	$message = '';
	pagetop(gTxt('element'),$message);

	echo startTable('list').
		assHead('element','version','date','active','checksum');

	$rs = safe_rows('*', 'txp_element', '1=1');
	foreach ($rs as $row) {
		extract($row);
		if ($hash)
			$checksum = (md5_file(txpath.'/elements/'.$name.'.php') == $hash ? gTxt('ok') : gTxt('modified'));
		else
			$checksum = gTxt('unknown');

		echo tr(
			td($name).
			td($version).
			td($modified).
			td(($status ? gTxt('yes') : gTxt('no'))).
			td($checksum)
		);
	}

	echo endTable();
}

?>
