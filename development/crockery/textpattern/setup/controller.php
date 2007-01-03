<?php

include_once txpath.'/lib/constants.php';
include_once txpath.'/lib/txplib_html.php';
include_once txpath.'/lib/txplib_forms.php';
include_once txpath.'/lib/txplib_misc.php';

include_once txpath.'/setup/setup-langs.php';
# cannot use on test cases with just the include, need to explicitly set $GLOBALS val?
$GLOBALS['langs'] = $langs;

class textpattern_setup_controller 
{
	
	var $_step;
	
	var $_default_step = 'chooseLang';
	
	var $_step_view;
	
	var $vars = array();
	
	var $rel_siteurl;
	
	var $langs;
	
	function textpattern_setup_controller()
	{
		$this->__construct();
	}
	
	function __construct()
	{
		
		#$this->rel_siteurl = preg_replace('#^(.*)/textpattern[/setuphindx.]*?$#i','\\1',$_SERVER['PHP_SELF']);
		$this->rel_siteurl = preg_replace('#^(.*)/textpattern[/setuphindxlra.]*?$#i','\\1',$_SERVER['PHP_SELF']);
		
		$this->_step = ps('step');
		
		if(empty($this->_step)) $this->_step = $this->_default_step;
		
		$this->langs =& $GLOBALS['langs'];
		
		# delegate control into step method
		if(method_exists($this,$this->_step) && is_callable(array(&$this,$this->_step))){
			call_user_method($this->_step,$this);
		}
	}
	
	function render()
	{

		$content = <<<eod
		<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
					"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
			<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
			<head>
			<meta http-equiv="content-type" content="text/html; charset=utf-8" />
			<title>Textpattern &#8250; setup</title>
			<link rel="Stylesheet" href="$this->rel_siteurl/textpattern/textpattern.css" type="text/css" />
			</head>
			<body style="border-top:15px solid #FC3">
			<div align="center">
			$this->_step_view
			</div>
			</body>
			</html>
eod;

		# force cache clean
		header("Cache-Control: no-cache, must-revalidate");
		header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
		header("Content-type: text/html; charset=utf-8");
		
		echo $content;

	}
	
// -------------------------------------------------------------
	# Step 1: Choose your language
	function chooseLang() 
	{
		require txpath.'/setup/en-gb.php';
		$GLOBALS['en_gb_lang'] = $en_gb_lang;
		
		#$this->_step_view =  '<form action="'.$this->rel_siteurl.'/textpattern/setup/index.php" method="post">'.
		$this->_step_view = '<form action="'.$this->rel_siteurl.'/textpattern/setup/install.php" method="post">'.
		 	'<table id="setup" cellpadding="0" cellspacing="0" border="0">'.
			tr(
				tda(
					hed('Welcome to Textpattern',3).
					graf('Please choose a language:').
					$this->_get_langs_popup($en_gb_lang).
					graf(fInput('submit','Submit','Submit','publish')).
					sInput('getDbInfo')
				,' width="400" height="50" colspan="4" align="left"')
			).
		'</table></form>';

	}
	
// -------------------------------------------------------------
	# Step 2: provide the required DB information
	function getDbInfo()
	{
		$lang = $this->vars['lang'] = ps('lang');
		$GLOBALS['textarray'] = $this->_load_lang();

		@include txpath.'/config.php';
		# this should be on the top most method of the controller!
		if (!empty($txpcfg['db']))
		{
			exit(graf(
				gTxt('already_installed', array('{txpath}' => txpath))
			));
		}

		$temp_txpath = txpath;
		if (@$_SERVER['SCRIPT_NAME'] && (@$_SERVER['SERVER_NAME'] || @$_SERVER['HTTP_HOST']))
		{
			$guess_siteurl = (@$_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : $_SERVER['SERVER_NAME'];
			$guess_siteurl .= $this->rel_siteurl;
		} else $guess_siteurl = 'mysite.com';

	  #$this->_step_view = '<form action="'.$this->rel_siteurl.'/textpattern/setup/index.php" method="post">'.
	  $this->_step_view = '<form action="'.$this->rel_siteurl.'/textpattern/setup/install.php" method="post">'.
	  	'<table id="setup" cellpadding="0" cellspacing="0" border="0">'.
		tr(
			tda(
			  hed(gTxt('welcome_to_textpattern'),3). 
			  graf(gTxt('need_details'),' style="margin-bottom:3em"').
			  hed(gTxt('database'),3).
			  graf(gTxt('db_must_exist'))
			,' width="400" height="50" colspan="4" align="left"')
		).
		tr(
			fLabelCell(gTxt('setup_db_login')).fInputCell('duser','',1).
			fLabelCell(gTxt('setup_db_password')).fInputCell('dpass','',2)
		).
		tr(
			fLabelCell(gTxt('setup_db_server')).fInputCell('dhost','localhost',3).
			fLabelCell(gTxt('setup_db_database')).fInputCell('ddb','',4)
		).
		tr(
			fLabelCell(gTxt('table_prefix')).fInputCell('dprefix','',5).
			tdcs(small(gTxt('prefix_warning')),2)
		).
		tr(fLabelCell(gTxt('database_engines')).td($this->_availableDBDrivers()).tdcs('&nbsp;',2)).
		tr(tdcs('&nbsp;',4)).
		tr(
			tdcs(
				hed(gTxt('site_path'),3).
				graf(gTxt('confirm_site_path')),4)
		).
		tr(
			fLabelCell(gTxt('full_path_to_txp')).
				tdcs(fInput('text','txpath',$temp_txpath,'edit','','',40).
				popHelp('full_path'),3)
		).
		tr(tdcs('&nbsp;',4)).
		tr(
			tdcs(
				hed(gTxt('site_url'),3).
				graf(gTxt('please_enter_url')),4)
		).
		tr(
			fLabelCell('http://').
				tdcs(fInput('text','siteurl',$guess_siteurl,'edit','','',40).
				popHelp('siteurl'),3)
		);
		if (!is_callable('mail'))
		{
			$this->_step_view.= 	tr(
							tdcs(gTxt('warn_mail_unavailable'),3,null,'" style="color:red;text-align:center')
					);
		}
		$this->_step_view.=
			tr(
				td().td(fInput('submit','Submit',gTxt('next'),'publish')).td().td()
			);
		$this->_step_view.= endTable().
		hInput('lang',$lang).
		sInput('printConfig').
		'</form>';
	}
	
	// -------------------------------------------------------------
	# Step 3: Print the contents of the config file
	function printConfig()
	{

		# Is anybody using the ftp values for something?
		$this->vars = psa(array('ddb','duser','dpass','dhost','dprefix','dbtype','txprefix','txpath',
			'siteurl','ftphost','ftplogin','ftpass','ftpath','lang'));
		$GLOBALS['textarray'] =  $this->_load_lang();
		
		@include txpath.'/config.php';

		if (!empty($txpcfg['db']))
		{
			exit(graf(
				gTxt('already_installed', array(
					'{txpath}' => txpath
				))
			));
		}
		
		
		$this->vars['txpath']   = preg_replace("/^(.*)\/$/","$1",$this->vars['txpath']);
		$this->vars['ftpath']   = preg_replace("/^(.*)\/$/","$1",$this->vars['ftpath']);

		extract($this->vars);


		// FIXME, remove when all languages are updated with this string
		if (!isset($GLOBALS['textarray']['prefix_bad_characters']))
			$GLOBALS['textarray']['prefix_bad_characters'] = 
				'The Table prefix {dbprefix} contains characters that are not allowed.<br />'.
				'The first character must match one of <b>a-zA-Z_</b> and all following 
				 characters must match one of <b>a-zA-Z0-9_</b>';

		$this->_step_view = graf(gTxt("checking_database"));

		$GLOBALS['txpcfg']['dbtype'] = $dbtype;
		# include here in order to load only the required driver
		include_once txpath.'/lib/mdb.php';

		if ($dbtype == 'pdo_sqlite') {
			$ddb = $txpath.DS.$ddb;
			$carry['ddb'] = $ddb;
		}

		if (!db_connect($dhost,$duser,$dpass,$ddb)){
			exit(graf(gTxt('db_cant_connect')));
		}

		$this->_step_view.= graf(gTxt('db_connected'));

		if (! ($dprefix == '' || preg_match('#^[a-zA-Z_][a-zA-Z0-9_]*$#',$dprefix)) )
		{
			exit(graf(
				gTxt('prefix_bad_characters', array(
					'{dbprefix}' => strong($dprefix)
				))
			));
		}

		if (!db_selectdb($ddb))
		{
			exit(graf(
				gTxt('db_doesnt_exist', array(
					'{dbname}' => strong($ddb)
				))
			));
		}

		# On 4.1 or greater use utf8-tables
		if ($dbtype!='pdo_sqlite' && db_query("SET NAMES 'utf8'")) {
			$this->vars['dbcharset'] = "utf8";
			$this->vars['dbcollate'] = "utf8_general_ci";
		}elseif ($dbtype == 'pdo_sqlite' && db_query('PRAGMA encoding="UTF-8"')){
			$this->vars['dbcharset'] = "utf8";			
		}
		else {
			$this->vars['dbcharset'] = "latin1";
			$this->vars['dbcollate'] = '';
		}

		$this->_step_view.= graf(
			gTxt('using_db', array('{dbname}' => strong($ddb)))
		.' ('. $this->vars['dbcharset'] .')' ).

		graf(
			strong(gTxt('before_you_proceed')).', '.gTxt('create_config', array('{txpath}' => txpath))
		).
		# this one is repeated, it should be its own method
		'<textarea name="config" cols="40" rows="5" style="width: 400px; height: 200px;">'.
		$this->makeConfig($this->vars).
		'</textarea>'.
		#'<form action="'.$this->rel_siteurl.'/textpattern/setup/index.php" method="post">'.
		'<form action="'.$this->rel_siteurl.'/textpattern/setup/install.php" method="post">'.
		fInput('submit','submit',gTxt('did_it'),'smallbox').
		sInput('getTxpLogin').hInput('carry',$this->postEncode($this->vars)).
		'</form>';
	}
	
// -------------------------------------------------------------

	# Step 4: Check if config file is ok and ask for login data
	function getTxpLogin() 
	{
		$carry = $this->postDecode(ps('carry'));
		$this->vars =&$carry;
		extract($carry);

		$GLOBALS['textarray'] =  $this->_load_lang();

		@include txpath.'/config.php';
		# next fragment is duplicated on previous method: refactoring!

		if (!isset($txpcfg) || ($txpcfg['db'] != $carry['ddb']) || ($txpcfg['txpath'] != $carry['txpath']))
		{
			$this->_step_view = graf(
				strong(gTxt('before_you_proceed')).', '.
				gTxt('create_config', array(
					'{txpath}' => txpath
				))
			).
	
			'<textarea name="config" cols="40" rows="5" style="width: 400px; height: 200px;">'.
			$this->makeConfig($carry).
			'</textarea>'.
			#'<form action="'.$this->rel_siteurl.'/textpattern/setup/index.php" method="post">'.
			'<form action="'.$this->rel_siteurl.'/textpattern/setup/install.php" method="post">'.
			fInput('submit','submit',gTxt('did_it'),'smallbox').
			sInput('getTxpLogin').hInput('carry',$this->postEncode($carry)).
			'</form>';
			return;
		}

		#$this->_step_view = '<form action="'.$this->rel_siteurl.'/textpattern/setup/index.php" method="post">'.
		$this->_step_view = '<form action="'.$this->rel_siteurl.'/textpattern/setup/install.php" method="post">'.
	  	startTable('edit').
		tr(
			tda(
				graf(gTxt('thanks')).
				graf(gTxt('about_to_create'))
			,' width="400" colspan="2" align="center"')
		).
		tr(
			fLabelCell(gTxt('your_full_name')).fInputCell('RealName')
		).
		tr(
			fLabelCell(gTxt('setup_login')).fInputCell('name')
		).
		tr(
			fLabelCell(gTxt('choose_password')).fInputCell('pass')
		).
		tr(
			fLabelCell(gTxt('your_email')).fInputCell('email')
		).
		tr(
			td().td(fInput('submit','Submit',gTxt('next'),'publish'))
		).
		endTable().
		sInput('createTxp').
		hInput('carry',$this->postEncode($carry)).
		'</form>';
	}

// -------------------------------------------------------------

	# Step 5:Setup Textpattern
	# This method is going to suffer a big refactoring
	function createTxp() 
	{
		$email = ps('email');

		if (!is_valid_email($email))
		{
			exit(graf(gTxt('email_required')));
		}
	
		$carry = $this->postDecode(ps('carry'));
		
		$this->vars =& $carry;

		extract($carry);

		require txpath.'/config.php';
		$GLOBALS['txpcfg'] = $txpcfg;
		$dbb = $txpcfg['db'];
		$duser = $txpcfg['user'];
		$dpass = $txpcfg['pass'];
		$dhost = $txpcfg['host'];
		$dprefix = $txpcfg['table_prefix'];
		$GLOBALS['txpcfg']['dbtype'] = $txpcfg['dbtype'];
		include_once txpath.'/lib/mdb.php';

		$GLOBALS['textarray'] =  $this->_load_lang();

		$siteurl = str_replace("http://",'',$siteurl);
		$siteurl = rtrim($siteurl,"/");
		
		define("PFX",trim($dprefix));
		define('TXP_INSTALL', 1);

		$name = addslashes(gps('name'));

		include_once txpath.'/lib/txplib_update.php';
		#include_once txpath.'/setup/txpsql.php';

 		include txpath.'/setup/tables.php';
 		
		// This has to come after txpsql.php, because otherwise we can't call mysql_real_escape_string
		if (MDB_TYPE=='pdo_sqlite') {
			extract(gpsa(array('name','pass','RealName','email')));
		}else{
			extract($this->sDoSlash(gpsa(array('name','pass','RealName','email'))));
		}
		

 		$nonce = md5( uniqid( rand(), true ) );

		db_query("INSERT INTO ".PFX."txp_users VALUES
			(1,'$name',password(lower('$pass')),'$RealName','$email',1,now(),'$nonce')");

		db_query("update ".PFX."txp_prefs set val = '$siteurl' where name='siteurl'");
		db_query("update ".PFX."txp_prefs set val = '$lang' where name='language'");
		db_query("update ".PFX."txp_prefs set val = '".getlocale($lang)."' where name='locale'");

 		$this->_step_view = $this->fbCreate();
	}
	
// -------------------------------------------------------------
	function fbCreate() 
	{
		if ($GLOBALS['txp_install_successful'] === false)
		{
			return '<div width="450" valign="top" style="margin-right: auto; margin-left: auto;">'.
				graf(
					gTxt('errors_during_install', array(
						'{num}' => sizeof($GLOBALS['txp_error_messages'])
					))
				,' style="margin-top: 3em;"').
				'<div id="error" style="text-align:left;"><pre>'.join("</pre>\r\n<pre>",$GLOBALS['txp_error_messages']).'</pre></div>'.
				'</div>';
		}

		else
		{
			return '<div width="450" valign="top" style="margin-right: auto; margin-left: auto;">'.

			graf(
				gTxt('that_went_well')
			,' style="margin-top: 3em;"').

			graf(
				gTxt('you_can_access', array(
					'index.php' => $this->rel_siteurl.'/textpattern/index.php',
				))
			).

			graf(gTxt('thanks_for_interest')).

			'</div>';
		}
	}
	
// -------------------------------------------------------------
	function makeConfig($ar) 
	{
		if(!defined('nl')) define("nl","';\n");
		if(!defined('o')) define("o",'$txpcfg[\'');
		if(!defined('m')) define("m","'] = '");
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
		.o.'dbcharset'	  .m.$dbcharset.nl
		.o.'dbtype'	  .m.$dbtype.nl
		.$close;
	}
	
//-------------------------------------------------------------
	
	function _get_langs_popup($en_gb_lang) 
	{
		$lang_codes = array_keys($this->langs);

		$things = array('en-gb' => 'English (GB)');

		foreach($lang_codes as $code){
			if(array_key_exists($code, $en_gb_lang['public']) && $code != 'en-gb'){
				$things[$code] = $en_gb_lang['public'][$code];
			}
		}

		$out = '<select name="lang">';

		foreach ($things as $a=>$b) {
			$out .= '<option value="'.$a.'">'.$b.'</option>'.n;
		}		

		$out .= '</select>';
		return $out;
	}
	
// -------------------------------------------------------------
	function _load_lang() 
	{
		$lang = (isset($this->langs[$this->vars['lang']]) && !empty($this->langs[$this->vars['lang']]))? $this->vars['lang'] : 'en-gb';
		if(!defined('LANG')) define('LANG', $this->vars['lang']);
		return $this->langs[LANG];
	}
	
// -------------------------------------------------------------
	function _availableDBDrivers()
	{

		$drivers_popup = $this->_getAvailableDrivers();
		# If no drivers at all, return a notice and exit the installer
		if (empty($drivers_popup)) {
			exit(graf('no_supported_db_drivers_installed'));
		}

		$out = '<select name="dbtype">';

		foreach ($drivers_popup as $k=>$v) {
			$out .= '<option value="'.$k.'">'.$v.'</option>'.n;
		}		

		$out .= '</select>';
		return $out;

	}
// -------------------------------------------------------------
	# This one just return the associative array key=>names for existing drivers
	function _getAvailableDrivers()
	{
		# get available mdb files first reading /lib/mdb dir
		$d = dir(txpath.'/lib/mdb');
		$drivers = array();
		while (false !== ($entry = $d->read())) {
			if (strpos($entry,'.php')) {
				$drv = explode('.php',$entry);
				if ($drv[0] != 'driver.template'){
					$drivers[] = $drv[0];
				}
			}
		}

		$drivers_popup = array();

		# do not show the list of drivers without support on this php install
		foreach ($drivers as $driver){
			if ($driver == 'my') {
				if (function_exists('mysql_connect') && is_callable('mysql_connect')) $drivers_popup[$driver] = gTxt($driver);
			}elseif ($driver == 'pg'){
				if (function_exists('pg_connect') && is_callable('pg_connect')) $drivers_popup[$driver] = gTxt($driver);
			}elseif(strpos($driver,'pdo_')!== false){
				# works nice for nix 5.0.5 and win 5.1.0?
				# try dl if allowed here too
				if (is_windows() && version_compare(phpversion(), '5.1.0','ge')) {
					if (extension_loaded('pdo') && extension_loaded($driver)) $drivers_popup[$driver] = gTxt($driver);
				}elseif (!is_windows() && version_compare(phpversion(),'5.0.5','ge')){
					if (extension_loaded('pdo') && extension_loaded($driver)) $drivers_popup[$driver] = gTxt($driver);
				}
			}
		}

		return $drivers_popup;
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
	function sDoSlash($in)
	{ 
		return doArray($in,'db_escape');
	}
}


function setup_error_handler($errno, $errstr, $errfile, $errline){
	switch ($errno){
		case E_USER_ERROR:
		case E_USER_WARNING:
			$GLOBALS['txp_error_messages'][] = $errstr;
		break;
	}
	
}

?>