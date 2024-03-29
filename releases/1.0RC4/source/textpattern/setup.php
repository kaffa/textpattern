<?php
/*
	This is Textpattern

	Copyright 2005 by Dean Allen
	www.textpattern.com
	All rights reserved

	Use of this software indicates acceptance of the Textpattern license agreement.
*/

include_once './lib/txplib_html.php';
include_once './lib/txplib_forms.php';
include_once './lib/txplib_misc.php';
print <<<eod
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
			"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
	<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
	<head>
	<meta http-equiv="content-type" content="text/html; charset=utf-8" />
	<title>Textpattern &#8250; setup</title>
	<link rel="Stylesheet" href="./textpattern.css" type="text/css" />
	</head>
	<body style="border-top:15px solid #FC3">
	<div align="center">
eod;


	$step = isPost('step');
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

// -------------------------------------------------------------
	function chooseLang() 
	{
	  echo '<form action="setup.php" method="post">',
	  	'<table id="setup" cellpadding="0" cellspacing="0" border="0">',
		tr(
			tda(
				hed('Welcome to Textpattern',3).
				graf('Please choose a language:').
				langs().
				graf(fInput('submit','Submit','Submit','publish')).
				sInput('getDbInfo')
			,' width="400" height="50" colspan="4" align="left"')
		),
		'</table></form>';
	}


// -------------------------------------------------------------
	function getDbInfo()
	{
		$lang = isPost('lang');

		

		$GLOBALS['textarray'] = setup_load_lang($lang);
	
		@include './config.php';
		
		if (!empty($txpcfg['db'])) {
			exit(graf(gTxt('already_installed')));
		}
		

		$temp_txpath = dirname(__file__);
		if (@$_SERVER['SCRIPT_NAME'] && (@$_SERVER['SERVER_NAME'] || @$_SERVER['HTTP_HOST']))
		{
			$guess_siteurl = (@$_SERVER['SERVER_NAME']) ? $_SERVER['SERVER_NAME'] : $_SERVER['HTTP_HOST'];
			$guess_siteurl .= rtrim(dirname(dirname($_SERVER['SCRIPT_NAME'])), '/');
		} else $guess_siteurl = 'mysite.com';
	  echo '<form action="setup.php" method="post">',
	  	'<table id="setup" cellpadding="0" cellspacing="0" border="0">',
		tr(
			tda(
			  hed(gTxt('welcome_to_textpattern'),3). 
			  graf(gTxt('need_details'),' style="margin-bottom:3em"').
			  hed('MySQL',3).
			  graf(gTxt('db_must_exist'))
			,' width="400" height="50" colspan="4" align="left"')
		),
		tr(
			fLabelCell(gTxt('mysql_login')).fInputCell('duser','',1).
			fLabelCell(gTxt('mysql_password')).fInputCell('dpass','',2)
		),
		tr(
			fLabelCell(gTxt('mysql_server')).fInputCell('dhost','',3).
			fLabelCell(gTxt('mysql_database')).fInputCell('ddb','',4)
		),
		tr(
			fLabelCell(gTxt('table_prefix')).fInputCell('dprefix','',5).
			tdcs(small(gTxt('prefix_warning')),2)
		),
		tr(tdcs('&nbsp;',4)),
		tr(
			tdcs(
				hed(gTxt('site_path'),3).
				graf(gTxt('confirm_site_path')),4)
		),
		tr(
			fLabelCell(gTxt('full_path_to_txp')).
				tdcs(fInput('text','txpath',$temp_txpath,'edit','','',40).
				popHelp('full_path'),3)
		),
		tr(tdcs('&nbsp;',4)),
		tr(
			tdcs(
				hed(gTxt('site_url'),3).
				graf(gTxt('please_enter_url')),4)
		),
		tr(
			fLabelCell('http://').
				tdcs(fInput('text','siteurl',$guess_siteurl,'edit','','',40).
				popHelp('site_url'),3)
		);
		echo
			tr(
				td().td(fInput('submit','Submit',gTxt('next'),'publish')).td().td()
			);
		echo endTable(),
		hInput('lang',$lang),
		sInput('printConfig'),
		'</form>';
	}

// -------------------------------------------------------------
	function printConfig()
	{
		$carry = enumPostItems('ddb','duser','dpass','dhost','dprefix','txprefix','txpath',
			'siteurl','ftphost','ftplogin','ftpass','ftpath','lang');

		$carry['txpath']   = preg_replace("/^(.*)\/$/","$1",$carry['txpath']);
		$carry['ftpath']   = preg_replace("/^(.*)\/$/","$1",$carry['ftpath']);
		
		extract($carry);

		$GLOBALS['textarray'] = setup_load_lang($lang);

		echo graf(gTxt("checking_database"));
		if (!($mylink = mysql_connect($dhost,$duser,$dpass))){
			exit(graf(gTxt('db_cant_connect')));
		}

		echo graf(gTxt('db_connected'));

		if (!$mydb = mysql_select_db($ddb)) {
			exit(graf(str_replace("{dbname}",strong($ddb)),gTxt("db_doesnt_exist")));
		}
		echo graf(str_replace("{dbname}", strong($ddb), gTxt('using_db'))),
				
		graf(strong(gTxt('before_you_proceed')).', '. gTxt('create_config')),

		'<textarea style="width:400px;height:200px" name="config" rows="1" cols="1">',
		makeConfig($carry),
		'</textarea>',
		'<form action="setup.php" method="post">',
		fInput('submit','submit','I did it','smallbox'),
		sInput('getTxpLogin'),hInput('carry',postEncode($carry)),
		'</form>';
	}

// -------------------------------------------------------------
	function getTxpLogin() 
	{
		$carry = isPost('carry');
		extract(postDecode($carry));

		$GLOBALS['textarray'] = setup_load_lang($lang);

		echo '<form action="setup.php" method="post">',
	  	startTable('edit'),
		tr(
			tda(
				graf(gTxt('thanks')).
				graf(gTxt('about_to_create'))
			,' width="400" colspan="2" align="center"')
		),
		tr(
			fLabelCell(gTxt('your_full_name')).fInputCell('RealName')
		),
		tr(
			fLabelCell(gTxt('setup_login')).fInputCell('name')
		),
		tr(
			fLabelCell(gTxt('choose_password')).fInputCell('pass')
		),
		tr(
			fLabelCell(gTxt('your_email')).fInputCell('email')
		),
		tr(
			td().td(fInput('submit','Submit',gTxt('next'),'publish'))
		),
		endTable(),
		sInput('createTxp'),
		hInput('carry',$carry),
		'</form>';
	}

// -------------------------------------------------------------
	function createTxp() 
	{
		$carry = isPost('carry');
		extract(postDecode($carry));

		$GLOBALS['textarray'] = setup_load_lang($lang);

		$siteurl = str_replace("http://",'',$siteurl);
		$siteurl = rtrim($siteurl,"/");
		
		define("PFX",trim($dprefix));
		define('TXP_INSTALL', 1);

		define("txpath", $txpath);
 		include './txpsql.php';

		// This has to come after txpsql.php, because otherwise we can't call mysql_real_escape_string
		extract(sDoSlash(gpsa(array('name','pass','RealName','email'))));

 		$nonce = md5( uniqid( rand(), true ) );

		mysql_query("INSERT INTO ".PFX."txp_users VALUES
			(1,'$name',password(lower('$pass')),'$RealName','$email',1,now(),'$nonce')");

		mysql_query("update ".PFX."txp_prefs set val = '$siteurl' where `name`='siteurl'");
		mysql_query("update ".PFX."txp_prefs set val = '$lang' where `name`='language'");

 		echo fbCreate();
	}


// -------------------------------------------------------------
	function isPost($val)
	{
		if(isset($_POST[$val])) {
			return (get_magic_quotes_gpc()) 
			?	stripslashes($_POST[$val])
			:	$_POST[$val];						
		} 
		return '';
	}

// -------------------------------------------------------------
	function makeConfig($ar) 
	{
		define("nl","';\n");
		define("o",'$txpcfg[\'');
		define("m","'] = '");
		$open = chr(60).'?php';
		$close = '?'.chr(62);
		extract($ar);
		return
		$open."\n".
		o.'db'			  .m.$ddb.nl
		.o.'user'		  .m.$duser.nl
		.o.'pass'		  .m.$dpass.nl
		.o.'host'		  .m.$dhost.nl
		.o.'table_prefix' .m.$dprefix.nl
		.o.'txpath'		  .m.$txpath.nl
		.$close;
	}

// -------------------------------------------------------------
	function fbCreate() 
	{
		if ($GLOBALS['txp_install_successful']===false)
			return
			'<div width="450" valign="top" style="margin-left:auto;margin-right:auto">'.
			graf($GLOBALS['txp_err_count'].' '.gTxt('errors_during_install'),' style="margin-top:3em"').
			'</div>';

		else
			return 
			'<div width="450" valign="top" style="margin-left:auto;margin-right:auto">'.
			graf(gTxt('that_went_well'),' style="margin-top:3em"').
			graf(gTxt('you_can_access')).
			graf(gTxt('thanks_for_interest')).
			'</div>';
	}

// -------------------------------------------------------------
	function postEncode($thing)
	{
		return base64_encode(serialize($thing));
	}

// -------------------------------------------------------------
	function postDecode($thing)
	{
		return unserialize(base64_decode($thing));
	}

// -------------------------------------------------------------
	function enumPostItems() 
	{
		foreach(func_get_args() as $item) { $out[$item] = isPost($item); }
		return $out; 
	}

//-------------------------------------------------------------
	function langs() 
	{
		$things = array(
			'en-gb' => 'English (GB)',
			'en-us' => 'English (US)',
			'fr-fr' => 'Fran&#231;ais',
			'es-es' => 'Espa&#241;ol',
			'da-dk' => 'Dansk',
			'el-gr' => '&#917;&#955;&#955;&#951;&#957;&#953;&#954;&#940;',
			'sv-se' => 'Svenska',
			'it-it' => 'Italiano',
			'cs-cz' => '&#268;e&#353;tina',
			'ja-jp' => '&#26085;&#26412;&#35486;',
			'de-de' => 'Deutsch',
			'no-no' => 'Norsk',
			'pt-pt' => 'Portugu&#234;s',
			'ru-ru' => '&#1056;&#1091;&#1089;&#1089;&#1082;&#1080;&#1081;',
			'sk-sk' => 'Sloven&#269;ina',
			'th-th' => '&#3652;&#3607;&#3618;',
			'nl-nl' => 'Nederlands'
		);

		$out = '<select name="lang">';

		foreach ($things as $a=>$b) {
			$out .= '<option value="'.$a.'">'.$b.'</option>'.n;
		}		

		$out .= '</select>';
		return $out;
	}
	

// -------------------------------------------------------------
	function setup_load_lang($lang) 
	{
		require_once './setup-langs.php';
		$lang = (isset($langs[$lang]) && !empty($langs[$lang]))? $lang : 'en-gb';
		define('LANG', $lang);
		return $langs[LANG];
	}

// -------------------------------------------------------------
	function sDoSlash($in)
	{ 
		if(phpversion() >= "4.3.0") {
			return doArray($in,'mysql_real_escape_string');
		} else {
			return doArray($in,'mysql_escape_string');
		}
	}


?>
