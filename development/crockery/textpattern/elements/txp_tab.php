<?php

/*
$HeadURL: $
$LastChangedRevision: $
*/

// we can't call register_tab in the element itself, since that's not always
// loaded.  txp_tab is loaded for all events though, so we can register tabs
// here until there's a better solution.

if (element_active('txp_element'))
	register_tab('admin', 'element', gTxt('tab_element'));

if (element_active('txp_file'))
	register_tab('content', 'file', gTxt('tab_file'));

?>
