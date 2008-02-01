<?php
/*
	This is Textpattern

	Copyright 2005 by Dean Allen
	www.textpattern.com
	All rights reserved

	Use of this software indicates acceptance of the Textpattern license agreement.

$HeadURL$
$LastChangedRevision$

*/

if (!defined('txpath'))
{
	define("txpath", dirname(dirname(__FILE__)));
	define("txpinterface", "admin");
}

error_reporting(E_ALL);
@ini_set("display_errors","1");

include_once txpath.'/lib/constants.php';
include_once txpath.'/lib/txplib_html.php';
include_once txpath.'/lib/txplib_forms.php';
include_once txpath.'/lib/txplib_misc.php';

# get all the moved stuff from its own file
include_once 'setup.php';

header("Content-type: text/html; charset=utf-8");

$rel_siteurl = preg_replace('#^(.*)/textpattern.*$#i','\\1',$_SERVER['PHP_SELF']);
print <<<eod
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
			"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
	<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
	<head>
	<meta http-equiv="content-type" content="text/html; charset=utf-8" />
	<title>Textpattern &#8250; setup</title>
	<link rel="stylesheet" href="$rel_siteurl/textpattern/textpattern.css" type="text/css" />
	</head>
	<body style="border-top:15px solid #FC3">
	<div align="center">
eod;


	$step = ps('step');
	switch ($step) {	
		case "": chooseLang(); break;
		case "getDbInfo": getDbInfo(); break;
		case "getTxpLogin": getTxpLogin(); break;
		case "printConfig": printConfig(); break;
		case "createTxp": createTxp();
	}
?>
</div>
</body>
</html>
<?php

// dmp($_POST);

// Move all the non global stuff to its own file so you can test it without deal with headers 
// and browser related output.

?>
