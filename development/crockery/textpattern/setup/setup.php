<?php

// -------------------------------------------------------------
	function chooseLang()
	{
	  echo '<form action="'.$GLOBALS['rel_siteurl'].'/textpattern/setup/index.php" method="post">',
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
		$lang = ps('lang');

		$GLOBALS['textarray'] = setup_load_lang($lang);

		@include txpath.'/config.php';

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
			$guess_siteurl .= $GLOBALS['rel_siteurl'];
		} else $guess_siteurl = 'mysite.com';
	  echo '<form action="'.$GLOBALS['rel_siteurl'].'/textpattern/setup/index.php" method="post">',
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
			fLabelCell(gTxt('mysql_server')).fInputCell('dhost','localhost',3).
			fLabelCell(gTxt('mysql_database')).fInputCell('ddb','',4)
		),
		tr(
			fLabelCell(gTxt('table_prefix')).fInputCell('dprefix','',5).
			tdcs(small(gTxt('prefix_warning')),2)
		),
		tr(fLabelCell(gTxt('database_engines')).td(availableDBDrivers()).tdcs('&nbsp;',2)),
		tr(tdcs('&nbsp;',4)),
		tr(
			tdcs(
				hed(gTxt('site_path'),3).
				graf(gTxt('confirm_site_path')),4)
		),
		/* tr(
			fLabelCell(gTxt('full_path_to_txp')).
				tdcs(fInput('text','txpath',$temp_txpath,'edit','','',40).
				popHelp('full_path'),3)
		),*/
		tr(tdcs('&nbsp;',4)),
		tr(
			tdcs(
				hed(gTxt('site_url'),3).
				graf(gTxt('please_enter_url')),4)
		),
		tr(
			fLabelCell('http://').
				tdcs(fInput('text','siteurl',$guess_siteurl,'edit','','',40).
				popHelp('siteurl'),3)
		);
		if (!is_callable('mail'))
		{
			echo 	tr(
							tdcs(gTxt('warn_mail_unavailable'),3,null,'" style="color:red;text-align:center')
					);
		}
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
		$carry = enumPostItems('ddb','duser','dpass','dhost','dprefix','dbtype','txprefix',
			'siteurl','ftphost','ftplogin','ftpass','ftpath','lang');

		@include txpath.'/config.php';

		if (!empty($txpcfg['db']))
		{
			exit(graf(
				gTxt('already_installed', array(
					'{txpath}' => txpath
				))
			));
		}

		$carry['ftpath']   = preg_replace("/^(.*)\/$/","$1",$carry['ftpath']);

		extract($carry);

		$GLOBALS['textarray'] = setup_load_lang($lang);
		// FIXME, remove when all languages are updated with this string
		if (!isset($GLOBALS['textarray']['prefix_bad_characters']))
			$GLOBALS['textarray']['prefix_bad_characters'] =
				'The Table prefix {dbprefix} contains characters that are not allowed.<br />'.
				'The first character must match one of <b>a-zA-Z_</b> and all following
				 characters must match one of <b>a-zA-Z0-9_</b>';

		echo graf(gTxt("checking_database"));

		$GLOBALS['txpcfg']['dbtype'] = $dbtype;
		# include here in order to load only the required driver
		include_once txpath.'/lib/mdb.php';

		if ($dbtype == 'pdo_sqlite') {
			$ddb = $txpath.DS.$ddb;
			$carry['ddb'] = $ddb;
		}

		global $DB;
		$DB =& mdb_factory($dhost, $ddb, $duser, $dpass, 'utf8');

		if (!$DB->connected){
			exit(graf(gTxt('db_cant_connect')));
		}

		echo graf(gTxt('db_connected'));

		if (! ($dprefix == '' || preg_match('#^[a-zA-Z_][a-zA-Z0-9_]*$#',$dprefix)) )
		{
			exit(graf(
				gTxt('prefix_bad_characters', array(
					'{dbprefix}' => strong($dprefix)
				))
			));
		}

		if (!$DB->selected)
		{
			exit(graf(
				gTxt('db_doesnt_exist', array(
					'{dbname}' => strong($ddb)
				))
			));
		}

/*
		// On 4.1 or greater use utf8-tables
		if ($dbtype!='pdo_sqlite' && db_query("SET NAMES 'utf8'")) {
			$carry['dbcharset'] = "utf8";
			$carry['dbcollate'] = "utf8_general_ci";
		}elseif ($dbtype == 'pdo_sqlite' && db_query('PRAGMA encoding="UTF-8"')){
			$carry['dbcharset'] = "utf8";
		}
		else {
			$carry['dbcharset'] = "latin1";
			$carry['dbcollate'] = '';
		}
*/

		// the MDB driver should tell us what charset to use
		$carry['dbcharset'] = $DB->charset;

		echo graf(
			gTxt('using_db', array('{dbname}' => strong($ddb)))
		.' ('. $carry['dbcharset'] .')' ),

		graf(
			strong(gTxt('before_you_proceed')).', '.gTxt('create_config', array('{txpath}' => txpath))
		),

		'<textarea name="config" cols="40" rows="5" style="width: 400px; height: 200px;">',
		makeConfig($carry),
		'</textarea>',
		'<form action="'.$GLOBALS['rel_siteurl'].'/textpattern/setup/index.php" method="post">',
		fInput('submit','submit',gTxt('did_it'),'smallbox'),
		sInput('getTxpLogin'),hInput('carry',postEncode($carry)),
		'</form>';
	}

// -------------------------------------------------------------
	function getTxpLogin()
	{
		$carry = postDecode(ps('carry'));
		extract($carry);

		$GLOBALS['textarray'] = setup_load_lang($lang);

		@include txpath.'/config.php';

		if (!isset($txpcfg) || ($txpcfg['db'] != $carry['ddb']) || ($txpcfg['txpath'] != $carry['txpath']))
		{
			echo graf(
				strong(gTxt('before_you_proceed')).', '.
				gTxt('create_config', array(
					'{txpath}' => txpath
				))
			),

			'<textarea name="config" cols="40" rows="5" style="width: 400px; height: 200px;">',
			makeConfig($carry),
			'</textarea>',
			'<form action="'.$GLOBALS['rel_siteurl'].'/textpattern/setup/index.php" method="post">',
			fInput('submit','submit',gTxt('did_it'),'smallbox'),
			sInput('getTxpLogin'),hInput('carry',postEncode($carry)),
			'</form>';
			return;
		}

		echo '<form action="'.$GLOBALS['rel_siteurl'].'/textpattern/setup/index.php" method="post">',
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
		hInput('carry',postEncode($carry)),
		'</form>';
	}

// -------------------------------------------------------------

	function createTxp()
	{
		$email = ps('email');

		if (!is_valid_email($email))
		{
			exit(graf(gTxt('email_required')));
		}

		$carry = ps('carry');

		extract(postDecode($carry));

		require txpath.'/config.php';
		$dbb = $txpcfg['db'];
		$duser = $txpcfg['user'];
		$dpass = $txpcfg['pass'];
		$dhost = $txpcfg['host'];
		$dprefix = $txpcfg['table_prefix'];
		$GLOBALS['txpcfg']['dbtype'] = $txpcfg['dbtype'];
		$dbcharset = $txpcfg['dbcharset'];
		include_once txpath.'/lib/mdb.php';

		$GLOBALS['textarray'] = setup_load_lang($lang);

		$siteurl = str_replace("http://",'',$siteurl);
		$siteurl = rtrim($siteurl,"/");

		define("PFX",trim($dprefix));
		define('TXP_INSTALL', 1);

		$name = addslashes(gps('name'));

		include_once txpath.'/lib/txplib_update.php';
 		include txpath.'/setup/txpsql.php';

		extract(gpsa(array('name','pass','RealName','email')));

 		$nonce = md5( uniqid( rand(), true ) );

		global $DB;
		$DB =& mdb_factory($dhost,$ddb,$duser,$dpass,$dbcharset);
		$DB->query("INSERT INTO ".PFX."txp_users VALUES
			(1,'".$DB->escape($name)."',password(lower('".$DB->escape($pass)."')),'".$DB->escape($RealName)."','".$DB->escape($email)."',1,now(),'".$DB->escape($nonce)."')");

		$DB->query("update ".PFX."txp_prefs set val = '".$DB->escape($siteurl)."' where name='siteurl'");
		$DB->query("update ".PFX."txp_prefs set val = '".$DB->escape($lang)."' where name='language'");
		$DB->query("update ".PFX."txp_prefs set val = '".$DB->escape(getlocale($lang))."' where name='locale'");

 		echo fbCreate();
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
		.o.'dbcharset'	  .m.$dbcharset.nl
		.o.'dbtype'	  .m.$dbtype.nl
		.$close;
	}

// -------------------------------------------------------------
	function fbCreate()
	{
		if ($GLOBALS['txp_install_successful'] === false)
		{
			return '<div width="450" valign="top" style="margin-right: auto; margin-left: auto;">'.
				graf(
					gTxt('errors_during_install', array(
						'{num}' => $GLOBALS['txp_err_count']
					))
				,' style="margin-top: 3em;"').
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
					'index.php' => $GLOBALS['rel_siteurl'].'/textpattern/index.php',
				))
			).

			graf(gTxt('thanks_for_interest')).

			'</div>';
		}
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
		foreach(func_get_args() as $item) { $out[$item] = ps($item); }
		return $out;
	}

//-------------------------------------------------------------
	function langs()
	{
		require_once txpath.'/setup/setup-langs.php';
		require_once txpath.'/setup/en-gb.php';
		$lang_codes = array_keys($langs);

		$things = array('en-gb' => 'English (GB)');

		foreach($lang_codes as $code){
			if(array_key_exists($code, $en_gb_lang['public']) && $code != 'en-gb'){
				$things[$code] = $en_gb_lang['public'][$code];
			}
		}

		ksort($things);

		$default	= 'en-gb';

		$out = n.'<select name="lang">';

		foreach ($things as $a=>$b) {
			$out .= n.t.'<option value="'.$a.'"'.
				( ($a == $default) ? ' selected="selected"' : '').
				'>'.$b.'</option>';
		}

		$out .= n.'</select>';

		return $out;
	}


// -------------------------------------------------------------
	function setup_load_lang($lang)
	{
		require_once txpath.'/setup/setup-langs.php';
		$lang = (isset($langs[$lang]) && !empty($langs[$lang]))? $lang : 'en-gb';
		define('LANG', $lang);
		return $langs[LANG];
	}

// -------------------------------------------------------------
	function availableDBDrivers()
	{

		$drivers_popup = getAvailableDrivers();
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

	# This one just return the associative array key=>names for existing drivers
	function getAvailableDrivers(){

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

?>
