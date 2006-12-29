<?php

if (!defined('txpath'))
{
	define("txpath", dirname(dirname(__FILE__)));
	define("txpinterface", "admin");
}

error_reporting(E_ALL);
@ini_set("display_errors","1");

include_once 'controller.php';

$installer = new textpattern_setup_controller();
$installer->render();

?>