<?php

/*
	This is Textpattern

	Copyright 2005 by Dean Allen
	www.textpattern.com
	All rights reserved

	Use of this software indicates acceptance of the Textpattern license agreement 
*/

//-------------------------------------------------------------

	define("cs",': ');
	define("ln",str_repeat('-', 24).n);

	global $files;
	
	$files = array(
		'/include/txp_category.php',
		'/include/txp_plugin.php',
		'/include/txp_auth.php',
		'/include/txp_form.php',
		'/include/txp_section.php',
		'/include/txp_tag.php',
		'/include/txp_list.php',
		'/include/txp_page.php',
		'/include/txp_discuss.php',
		'/include/txp_prefs.php',
		'/include/txp_log.php',
		'/include/txp_preview.php',
		'/include/txp_image.php',
		'/include/txp_article.php',
		'/include/txp_css.php',
		'/include/txp_admin.php',
		'/include/txp_link.php',
		'/include/txp_diag.php',
		'/lib/admin_config.php',
		'/lib/txplib_misc.php',
		'/lib/taglib.php',
		'/lib/txplib_head.php',
		'/lib/classTextile.php',
		'/lib/txplib_html.php',
		'/lib/txplib_db.php',
		'/lib/IXRClass.php',
		'/lib/txplib_forms.php',
		'/publish/taghandlers.php',
		'/publish/atom.php',
		'/publish/log.php',
		'/publish/comment.php',
		'/publish/search.php',
		'/publish/rss.php',
		'/publish.php',
		'/index.php',
		'/css.php',
	);

	if ($event == 'diag') {
		require_privs('diag');

		$step = gps('step');
		doDiagnostics();
	}


	function apache_module($m) {
		$modules = apache_get_modules();
		return in_array($m, $modules);
	}

	function test_tempdir($dir) {
		$f = realpath(tempnam($dir, 'txp_'));
		if (is_file($f)) {
			@unlink($f);
			return true;
		}
	}

	function doDiagnostics()
	{
		global $files, $txpcfg, $step;
		extract(get_prefs());
		
	$urlparts = parse_url(hu);
	$mydomain = $urlparts['host'];
	$server_software = (@$_SERVER['SERVER_SOFTWARE'] || @$_SERVER['HTTP_HOST']) 
						? ( (@$_SERVER['SERVER_SOFTWARE']) ?  @$_SERVER['SERVER_SOFTWARE'] :  $_SERVER['HTTP_HOST'] )
						: '';
	
	$fail = array(

		'path_to_site_missing' =>
		(!isset($path_to_site))
		? gTxt('path_to_site_missing')
		: '',

		'dns_lookup_fails' =>	
		(@gethostbyname($mydomain) == $mydomain)
		?	gTxt('dns_lookup_fails').cs. $mydomain
		:	'',

		'path_not_doc_root' =>
		(0 !== strncmp(realpath($_SERVER['DOCUMENT_ROOT']), realpath($path_to_site), strlen($_SERVER['DOCUMENT_ROOT'])))
		?	gTxt('path_not_doc_root').' [ '.$_SERVER['DOCUMENT_ROOT'].' ] '
		:	'',

		'path_to_site_inacc' =>
		(!@is_dir($path_to_site))
		?	gTxt('path_to_site_inacc').cs.$path_to_site
		: 	'',

		'site_trailing_slash' =>
		(rtrim($siteurl, '/') != $siteurl)
		?	gTxt('site_trailing_slash').cs.$path_to_site
		:	'',

		'index_inaccessible' =>
		(!@is_file($path_to_site."/index.php") or !@is_readable($path_to_site."/index.php"))
		?	"{$path_to_site}/index.php ".gTxt('is_inaccessible')
		:	'',

		'dir_not_writable' =>
		trim(
			((!@is_writable($path_to_site.'/'.$img_dir))
			?	str_replace('{dirtype}', gTxt('img_dir'), gTxt('dir_not_writable')).": {$path_to_site}/{$img_dir}\r\n"
			:	'').
			((!@is_writable($file_base_path))
			?	str_replace('{dirtype}', gTxt('file_base_path'), gTxt('dir_not_writable')).": {$file_base_path}\r\n"
			:	'').
			((!@is_writable($tempdir))
			?	str_replace('{dirtype}', gTxt('tempdir'), gTxt('dir_not_writable')).": {$tempdir}\r\n"
			:	'')),

		'cleanurl_only_apache' =>
		($permlink_mode != 'messy' && $server_software && !stristr($server_software, 'Apache'))
		? gTxt('cleanurl_only_apache')
		: '',

		'htaccess_missing' =>	
		($permlink_mode != 'messy' and !@is_readable($path_to_site.'/.htaccess'))
		?	gTxt('htaccess_missing')
		:	'',

		'mod_rewrite_missing' =>
		($permlink_mode != 'messy' and is_callable('apache_get_modules') and !apache_module('mod_rewrite'))
		? gTxt('mod_rewrite_missing')
		: '',

		'file_uploads_disabled' =>
		(!ini_get('file_uploads'))
		?	gTxt('file_uploads_disabled')
		:	'',

		'setup_still_exists' =>
		(@is_readable($txpcfg['txpath'] . '/setup.php'))
		?	$txpcfg['txpath']."/setup.php ".gTxt('still_exists')
		:	'',

		'no_temp_dir' =>
		(empty($tempdir))
		? gTxt('no_temp_dir')
		: '',
	);

	if ($permlink_mode != 'messy') {
		$rs = safe_column("name","txp_section", "1");
		foreach ($rs as $name) {
			if (@file_exists($path_to_site.'/'.$name))
				$fail['old_placeholder_exists'] = gTxt('old_placeholder').": {$path_to_site}/{$name}";
		}
	}

	$missing = array();
	foreach ($files as $f) {
		if (!is_readable($txpcfg['txpath'] . $f))
			$missing[] = $txpcfg['txpath'] . $f;
	}

	if ($missing)
		$fail['missing_files'] = gTxt('missing_files').cs.join(', ', $missing);


	foreach ($fail as $k=>$v)
		if (empty($v)) unset($fail[$k]);

	echo 
	pagetop(gTxt('tab_diagnostics'),''),
	startTable('list'),
	tr(td(hed(gTxt('preflight_check'),1)));


	if ($fail) {
		foreach ($fail as $help => $message)
			echo tr(tda(nl2br($message) . popHelp($help), ' style="color:red;"'));
	}
	else {
		echo tr(td(gTxt('all_checks_passed')));
	}

	echo tr(td(hed(gTxt('diagnostic_info'),1)));


	$fmt_date = '%Y-%m-%d %H:%M:%S';
	
	$out = array(
		'<textarea style="width:500px;height:300px;" readonly="readonly">',

		gTxt('txp_version').cs.txp_version.n,

		gTxt('last_update').cs.gmstrftime($fmt_date, $dbupdatetime).'/'.gmstrftime($fmt_date, @filemtime(txpath.'/_update.php')).n,

		gTxt('document_root').cs.$_SERVER['DOCUMENT_ROOT'].n,

		'$path_to_site'.cs.$path_to_site.n,

		gTxt('txp_path').cs.$txpcfg['txpath'].n,

		gTxt('permlink_mode').cs.$permlink_mode.n,

		(ini_get('open_basedir')) ? 'open_basedir: '.ini_get('open_basedir').n : '',

		(ini_get('upload_tmp_dir')) ? 'upload_tmp_dir: '.ini_get('upload_tmp_dir').n : '',

		gTxt('tempdir').cs.$tempdir.n,

		gTxt('web_domain').cs.$siteurl.n,

		(getenv('TZ')) ? 'TZ: '.getenv('TZ').n : '',

		gTxt('php_version').cs.phpversion().n,

		(ini_get('register_globals')) ? gTxt('register_globals').cs.ini_get('register_globals').n : '',

		gTxt('magic_quotes').cs.get_magic_quotes_gpc().'/'.get_magic_quotes_runtime().n,

		gTxt('locale').cs.$locale.n,

		(isset($_SERVER['SERVER_SOFTWARE'])) ? gTxt('server').cs.$_SERVER['SERVER_SOFTWARE'].n : '',

		(is_callable('apache_get_version')) ? gTxt('apache_version').cs.apache_get_version().n : '',

		$fail
		? n.gTxt('preflight_check').cs.n.ln.join("\n", $fail).n.ln
		: '',

		(is_readable($path_to_site.'/.htaccess')) 
		?	n.gTxt('htaccess_contents').cs.n.ln.join('',file($path_to_site.'/.htaccess')).n.ln 
		:	''
	);

	if ($step == 'high') {
		$extns = get_loaded_extensions();
		$extv = array();
		foreach ($extns as $e) {
			$extv[] = $e . (phpversion($e) ? '/' . phpversion($e) : '');
		}
		$out[] = n.gTxt('php_extensions').cs.join(', ', $extv).n;

		if (is_callable('apache_get_modules'))
			$out[] = n.gTxt('apache_modules').cs.join(', ', apache_get_modules()).n.n;


		if (is_callable('md5_file')) {
			foreach ($files as $f) {
				$out[] = "MD5 $f: ".md5_file($txpcfg['txpath'] . $f) . n;
			}
		}
	}

	$out[] = '</textarea>'.br;
	
	$dets = array('low'=>gTxt('low'),'high'=>gTxt('high'));
	
	$out[] = 
		form(
			eInput('diag').n.
			gTxt('detail').cs.
			selectInput('step', $dets, $step, 0, 1)
		);

	echo tr(td(join('',$out))),

	endTable();
	}
	
?>
