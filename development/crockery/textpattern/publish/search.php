<?php

/*
	This is Textpattern
	Copyright 2005 by Dean Allen - all rights reserved.

	Use of this software denotes acceptance of the Textpattern license agreement

$HeadURL$
$LastChangedRevision$

*/

	function filterSearch()
	{
		$rs = safe_column("name", "txp_section", "searchable != '1'");
		if ($rs) {
			foreach($rs as $name) $filters[] = "and Section != '".doSlash($name)."'";
			return join(' ',$filters);
		}
		return false;
	}

?>