<?php

// Textpattern string functions

/*
Copyright 2006 Alex Shiels http://thresholdstate.com/

$HeadURL: $
$LastChangedRevision: $
*/

define('TXP_USE_MBSTRING', extension_loaded('mbstring'));
if (TXP_USE_MBSTRING)
	mb_internal_encoding('UTF-8');

// -------------------------------------------------------------
function txp_strtolower($str) {
	if (TXP_USE_MBSTRING)
		return mb_strtolower($str);

	return strtolower($str);
}

// -------------------------------------------------------------
function txp_strtoupper($str) {
	if (TXP_USE_MBSTRING)
		return mb_strtoupper($str);

	return strtoupper($str);
}

// -------------------------------------------------------------
function txp_ucwords($str) {
	if (TXP_USE_MBSTRING)
		return mb_convert_case($str, MB_CASE_TITLE);

	return ucwords($str);
}

// -------------------------------------------------------------
function txp_ucfirst($str) {
	if (TXP_USE_MBSTRING)
		return mb_convert_case(mb_substr($str, 0, 1), MB_CASE_UPPER).
			mb_convert_case(mb_substr($str, 1), MB_CASE_LOWER);

	return ucfirst($str);
}

?>
