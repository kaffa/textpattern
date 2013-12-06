<?php

	if (!defined('TXP_UPDATE'))
	{
		exit("Nothing here. You can't access this file directly.");
	}

	// Updates comment email length.
	safe_alter('txp_discuss', "MODIFY email VARCHAR(254) NOT NULL default ''");
