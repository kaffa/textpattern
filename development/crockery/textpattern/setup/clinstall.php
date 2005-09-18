<?php

// Textpattern CLI installer
// for experts only

if (php_sapi_name() !== 'cli')
	die('command line only');

require_once('config.php');

if (count($argv) < 4)
	die("usage: $argv[0] <username> <email> <pass>\n");


global $txpcfg;

if (empty($txpcfg))
	die('please check config.php');

global $dhost, $duser, $dpass, $ddb, $ddbtype, $lang;
$dhost = $txpcfg['host'];
$duser = $txpcfg['user'];
$dpass = $txpcfg['pass'];
$ddb = $txpcfg['db'];
$ddbtype = $txpcfg['dbtype'];
$lang = 'en-gb'; // just to start with

@define("PFX",$txpcfg['prefix']);
define('TXP_INSTALL', 1);
define('txpath', $txpcfg['txpath']);

include_once(txpath."/lib/mdb.php");
if (!db_connect($dhost,$duser,$dpass, $ddb))
	die('error connecting: '.db_lasterror());
if (!db_selectdb($ddb))
	die('error selecting db: '.db_lasterror());

include(txpath."/lib/txplib_misc.php");
include(txpath."/lib/txplib_html.php");
include(txpath."/lib/txplib_update.php");
// 4.0+ expects this to be set in txpsql.php
$_POST['email'] = '';
include(txpath."/setup/txpsql.php");

if ($GLOBALS['txp_install_successful'] === false)
	exit('install failed');

$nonce = md5( uniqid( rand(), true ) );

$name = $argv[1];
$email = $argv[1];
$pass = $argv[1];

db_query("INSERT INTO ".PFX."txp_users VALUES
	(1,'$name',password(lower('$pass')),'$name','$email',1,now(),'$nonce')");


?>
