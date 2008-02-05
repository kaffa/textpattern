<?php

/*
	This is Textpattern

	Copyright 2005 by Dean Allen
	www.textpattern.com
	All rights reserved

	Use of this software indicates acceptance of the Textpattern license agreement

$HeadURL$
$LastChangedRevision$

*/
	if (!defined('txpinterface')) die('txpinterface is undefined.');

include_once(txpath.'/lib/txplib_controller.php');
include_once(txpath.'/lib/txplib_view.php');

register_controller('PrefsController', 'prefs');

// -------------------------------------------------------------
class PrefsController extends ZemAdminController {
	var $area = 'admin';
	var $event = 'prefs';
	var $caption = 'prefs';
	var $default_step = 'prefs_list';

	function PrefsController() {
		parent::ZemAdminController();
	}

	function prefs_list_view() {
		global $prefs;
		$view = new PrefsView($prefs, $this->event, 'prefs_list', gTxt('site_prefs'));
		echo $view->render();
	}

	function advanced_prefs_view() {
		global $prefs;
		$view = new PrefsView($prefs, $this->event, 'advanced_prefs', gTxt('advanced_preferences'));
		$view->type = '1';
		$view->event_order = 'event asc';
		echo $view->render();
	}

	function list_languages_view() {
		global $prefs;
		$view = new LanguagesView($prefs, $this->event, 'list_languages', gTxt('manage_languages'));
		echo $view->render();
	}

	function get_language_view() {
		$this->_set_view('list_languages');
	}

//-------------------------------------------------------------

	function prefs_list_post()
	{
		$this->prefs_post();
	}

	function advanced_prefs_post()
	{
		$this->prefs_post();
	}

	function prefs_post()
	{

		// special considerations
		if (isset($_POST['siteurl'])) {
			$_POST['siteurl'] = rtrim(str_replace("http://", '', $this->ps('siteurl')), "/ ");
		}

		if (isset($_POST['tempdir']) && empty($_POST['tempdir']))
			$_POST['tempdir'] = doSlash(find_temp_dir());

		if (!empty($_POST['file_max_upload_size']))
			$_POST['file_max_upload_size'] = $this->real_max_upload_size($this->ps('file_max_upload_size'));

		// safe them all
		$prefnames = array_keys(get_prefs());
		foreach($prefnames as $prefname) {
			if (isset($_POST[$prefname])) {
				update_pref($prefname, $this->ps($prefname));
			}
		}

		update_lastmod();
		$this->_message(gTxt('preferences_saved'));
	}

//-------------------------------------------------------------

	function list_languages_post()
	{
		global $locale, $textarray;
		// Select and save active language
		$language = $this->ps('active_language');
		$locale = doSlash(getlocale($language));
		update_pref('language', $language);
		update_pref('locale', $locale);
		$textarray = load_lang($language);
		$locale = setlocale(LC_ALL, $locale);
		$this->_message(gTxt('preferences_saved'));
	}

//-------------------------------------------------------------
	function real_max_upload_size($user_max)
	{
		// The minimum of the candidates, is the real max. possible size
		$candidates = array($user_max,
							ini_get('post_max_size'),
							ini_get('upload_max_filesize'));
		$real_max = null;
		foreach ($candidates as $item)
		{
			$val = trim($item);
			$modifier = strtolower( substr($val, -1) );
			switch($modifier) {
				// The 'G' modifier is available since PHP 5.1.0
				case 'g': $val *= 1024;
				case 'm': $val *= 1024;
				case 'k': $val *= 1024;
			}
			if ($val > 1) {
				if (is_null($real_max))
					$real_max = $val;
				elseif ($val < $real_max)
					$real_max = $val;
			}
		}
		return $real_max;
	}
}

// -------------------------------------------------------------
class PrefsView extends TxpDetailView {

	var $type = 0;
	var $event_order = 'event desc';

	function PrefsView($data, $event, $step, $caption='') {
		parent::TxpDetailView($data, $event, $step, $caption);
		$this->listtag = '';
		$this->rowtag = 'tr';
	 	$this->ltag = 'td';
		$this->itag = 'td';
	}

	function head()
	{
		return TxpDetailView::head().
			tag(
					tag(sLink('prefs', 'prefs_list', gTxt('site_prefs'), ('prefs_list' == $this->step) ? 'navlink-active' : 'navlink'), 'li').n.
					tag(sLink('prefs', 'advanced_prefs', gTxt('advanced_preferences'), ('advanced_prefs' == $this->step) ? 'navlink-active' : 'navlink'),'li').n.
					tag(sLink('prefs', 'list_languages', gTxt('manage_languages'), ('list_languages' == $this->step) ? 'navlink-active' : 'navlink'),'li'),
			'ul', ' id="nav-prefs"');
	}

	function body() {

		extract(get_prefs());

		$locale = setlocale(LC_ALL, $locale);
		$textarray = load_lang($language);
		$evt_list =  safe_column('distinct event', 'txp_prefs', "type = $this->type and prefs_id = 1 order by $this->event_order");
		if (!$use_comments) {
			unset($evt_list['comments']);
		}

		foreach ($evt_list as $event) {
			$rs = safe_rows_start('*', 'txp_prefs', "type = $this->type and prefs_id = 1 and event = '".doSlash($event)."' order by position");
			$out = array();
			while ($a = nextRow($rs)) {
				$name = $a['name'];
				$widget = $this->widget($a['html']);
				if(empty($a['choices'])) {
					// Assuming this widget function signature:
					// $thing = $this->i_foo( [string] $name, [string] $data);
					$thing = $this->$widget($name, $this->data[$name]);
				} else {
					$choices = $this->$a['choices']();
					$widget = $this->widget($a['html']);
					// Assuming this widget function signature:
					// $thing = $this->i_foo( [string] $name, [array of strings] $choices, [string] $data);
					$thing = $this->$widget($name, $choices, $this->data[$name]);
				}
				#$out[] = tag($thing, 'tr');
				$out[] = $thing;
			}
			$set[] = fieldset(
						tag(join(n, $out), 'table', " id='pref-panel-$event'"),
						gTxt($event)
					 );
		}
		return join(n, $set).n.tag($this->i_button('save'), 'table');
	}


	function widget($widget)
	{
		// map prefs UI widgets to code
		// use Zend's nomenclature whereever available
		// @see: http://framework.zend.com/manual/en/zend.view.helpers.html
		$map = array (
			'checkbox' => 'i_checkbox',
			'select' => 'i_select',
			'text' => 'i_text',
			'radio' => 'i_select_radio',
			// deprecated
			'text_input' => 'i_text',
			'yesnoradio' => 'i_checkbox',
		);

		$widget = strtolower($widget);
		if (is_callable( array($this, @$map[$widget]) )) {
			return $map[$widget];
		} else {
			return 'i_awol';
		}
	}

	function i_awol($name, $value='', $opts = array())
	{
		// the "something's wrong with your prefs table" fallback widget
		return tag(
				tag($this->label($name, $opts).' '.pophelp($name), $this->ltag).
				tag(gTxt('no_widget_for_pref', array('pref' => $name)), $this->itag),
			$this->rowtag
		);

	}

//-------------------------------------------------------------

	function gmtoffsets()
	{
		// Standard time zones as compiled by H.M. Nautical Almanac Office, June 2004
		// http://aa.usno.navy.mil/faq/docs/world_tzones.html
		$tz = array(
			-12, -11, -10, -9.5, -9, -8.5, -8, -7, -6, -5, -4, -3.5, -3, -2, -1,
			0,
			+1, +2, +3, +3.5, +4, +4.5, +5, +5.5, +6, +6.5, +7, +8, +9, +9.5, +10, +10.5, +11, +11.5, +12, +13, +14,
		);

		$vals = array();

		foreach ($tz as $z)
		{
			$sign = ($z >= 0 ? '+' : '');
			$label = sprintf("GMT %s%02d:%02d", $sign, $z, abs($z - (int)$z) * 60);

			$vals[sprintf("%s%d", $sign, $z * 3600)] = $label;
		}

		return $vals;
	}


//-------------------------------------------------------------

	function logging()
	{
		$vals = array(
			'all'		=> gTxt('all_hits'),
			'refer' => gTxt('referrers_only'),
			'none'	=> gTxt('none')
		);
		return $vals;
	}

//-------------------------------------------------------------

	function permlinkmodes()
	{
		$vals = array(
			'messy'										=> gTxt('messy'),
			'id_title'								=> gTxt('id_title'),
			'section_id_title'				=> gTxt('section_id_title'),
			'year_month_day_title'		=> gTxt('year_month_day_title'),
			'section_title'						=> gTxt('section_title'),
			'title_only'							=> gTxt('title_only'),
			// 'category_subcategory' => gTxt('category_subcategory')
		);
		return $vals;
	}

//-------------------------------------------------------------

	function commentmode()
	{
		$vals = array(
			'0' => gTxt('nopopup'),
			'1' => gTxt('popup')
		);

		return $vals;
	}

//-------------------------------------------------------------

	function weeks()
	{
		$weeks = gTxt('weeks');

		$vals = array(
			'0' => gTxt('never'),
			7		=> '1 '.gTxt('week'),
			14	=> '2 '.$weeks,
			21	=> '3 '.$weeks,
			28	=> '4 '.$weeks,
			35	=> '5 '.$weeks,
			42	=> '6 '.$weeks
		);

		return $vals;
	}

// -------------------------------------------------------------
	function dateformats() {

		$dayname = '%A';
		$dayshort = '%a';
		$daynum = (is_numeric(strftime('%e')) ? '%e' : '%d');
		$daynumlead = '%d';
		$daynumord = (is_numeric(substr(trim(strftime('%Oe')), 0, 1)) ? '%Oe' : $daynum);
		$monthname = '%B';
		$monthshort = '%b';
		$monthnum = '%m';
		$year = '%Y';
		$yearshort = '%y';
		$time24 = '%H:%M';
		$time12 = (strftime('%p') ? '%I:%M %p' : $time24);
		$date = (strftime('%x') ? '%x' : '%Y-%m-%d');

		$formats = array(
			"$monthshort $daynumord, $time12",
			"$daynum.$monthnum.$yearshort",
			"$daynumord $monthname, $time12",
			"$yearshort.$monthnum.$daynumlead, $time12",
			"$dayshort $monthshort $daynumord, $time12",
			"$dayname $monthname $daynumord, $year",
			"$monthshort $daynumord",
			"$daynumord $monthname $yearshort",
			"$daynumord $monthnum $year - $time24",
			"$daynumord $monthname $year",
			"$daynumord $monthname $year, $time24",
			"$daynumord. $monthname $year",
			"$daynumord. $monthname $year, $time24",
			"$year-$monthnum-$daynumlead",
			"$year-$daynumlead-$monthnum",
			"$date $time12",
			"$date",
			"$time24",
			"$time12",
			"$year-$monthnum-$daynumlead $time24",
		);

		$ts = time();
		foreach ($formats as $f)
			if ($d = safe_strftime($f, $ts))
				$dateformats[$f] = $d;

		$dateformats['since'] = 'hrs/days ago';

		return array_unique($dateformats);
	}
//-------------------------------------------------------------

	function markups()
	{
		// @todo: retrieve this list from classMarkup.php
		$vals = array(
			'txptextile'          => gTxt('use_textile'),
			# 'txpmarkup'         => gTxt('use_markup'),
			'txpnl2br'   => gTxt('convert_linebreaks'),
			'txprawxhtml' => gTxt('leave_text_untouched')
		);
		return $vals;
	}

//-------------------------------------------------------------

	function production_stati()
	{
		$vals = array(
			'debug'		=> gTxt('production_debug'),
			'testing' => gTxt('production_test'),
			'live'		=> gTxt('production_live'),
		);

		return $vals;
	}

}

class LanguagesView extends PrefsView {

	function body()
	{
		global $prefs, $locale, $txpcfg;
		require_once txpath.'/lib/IXRClass.php';

		// active language selector
		$this->data['active_language'] = safe_field('val','txp_prefs',"name='language'");
		$lang_form = $this->form($this->i_select('active_language', $this->languages(), $this->data['active_language']).
								$this->i_button('save'));


		// rpc language installer
		$client = new IXR_Client(RPC_SERVER);
		#$client->debug = true;

		$available_lang = array();
		$rpc_connect = false;$show_files = false;

		// Get items from RPC
		@set_time_limit(90);
		if (gps('force')!='file' && $client->query('tups.listLanguages',$prefs['blog_uid'])) {
			$rpc_connect = true;
			$response = $client->getResponse();
			foreach ($response as $language)
				$available_lang[$language['language']]['rpc_lastmod'] = gmmktime($language['lastmodified']->hour,$language['lastmodified']->minute,$language['lastmodified']->second,$language['lastmodified']->month,$language['lastmodified']->day,$language['lastmodified']->year);
		} elseif (gps('force')!='file') {
			$msg = gTxt('rpc_connect_error')."<!--".$client->getErrorCode().' '.$client->getErrorMessage()."-->";
		}

		// Get items from file system
		$files = $this->get_lang_files();

		if (gps('force')=='file' || !$rpc_connect)
			$show_files = true;

		if ( $show_files && is_array($files) && !empty($files) ) {
			foreach ($files as $file) {
				if ($fp = @fopen(txpath.DS.'lang'.DS.$file,'r')) {
					$name = str_replace('.txt','',$file);
					$firstline = fgets($fp, 4096);
					fclose($fp);
					if (strpos($firstline,'#@version') !== false)
						@list($fversion,$ftime) = explode(';',trim(substr($firstline,strpos($firstline,' ',1))));
					else
						$fversion = $ftime = NULL;
					$available_lang[$name]['file_note'] = (isset($fversion)) ? $fversion : 0;
					$available_lang[$name]['file_lastmod'] = (isset($ftime)) ? $ftime : 0;
				}
			}
		}

		// Get installed items from the database
		// I'm afraid we need a value here for the language itself, not for each one of the rows
		$rows = safe_rows('lang, UNIX_TIMESTAMP(MAX(lastmod)) as lastmod','txp_lang',"1 GROUP BY lang ORDER BY lastmod DESC");
		foreach ($rows as $language) {
			$available_lang[$language['lang']]['db_lastmod'] = $language['lastmod'];
		}

		$list = '';
		// Show the language table
		foreach ($available_lang as $langname => $langdat)
		{
			$file_updated = ( isset($langdat['db_lastmod']) && @$langdat['file_lastmod'] > $langdat['db_lastmod']);
			$rpc_updated = ( @$langdat['rpc_lastmod'] > @$langdat['db_lastmod']);
			$rpc_install = tda( strong(eLink('prefs','get_language','lang_code',$langname,(isset($langdat['db_lastmod']))
										? gTxt('update') : gTxt('install'),'updating',isset($langdat['db_lastmod']) )).
								br.safe_strftime('%d %b %Y %X',@$langdat['rpc_lastmod'])
							,(isset($langdat['db_lastmod']))
								? ' style="color:red;text-align:center;background-color:#FFFFCC;"'
								: ' style="color:#667;vertical-align:middle;text-align:center"');
			$list.= tr (
				// lang name and date
				tda(gTxt($langname).
					 tag( ( isset($langdat['db_lastmod']) )
							? br.'&nbsp;'.safe_strftime('%d %b %Y %X',$langdat['db_lastmod'])
							: ''
						, 'span',' style="color:#aaa;font-style:italic"')
					, (isset($langdat['db_lastmod']) && $rpc_updated) #tda attribute
							? ' nowrap="nowrap" style="color:red;background-color:#FFFFCC;"'
							: ' nowrap="nowrap" style="vertical-align:middle"' ).n.
				// RPC info
				(  ($rpc_updated)
					? $rpc_install
					: tda( (isset($langdat['rpc_lastmod'])) ? gTxt('updated') : '-'
						,' style="vertical-align:middle;text-align:center"')
				).n.
				// file info
				( ($show_files)
					?	tda( tag( ( isset($langdat['file_lastmod']) )
									? eLink('prefs','get_language','lang_code',$langname,($file_updated) ? gTxt('update') : gTxt('install'),'force','file').
											br.'&nbsp;'.safe_strftime($prefs['archive_dateformat'],$langdat['file_lastmod'])
									: ' &nbsp; '  # No File available
								, 'span', ($file_updated) ? ' style="color:#667;"' : ' style="color:#aaa;font-style:italic"' )
							, ' class="langfile" style="text-align:center;vertical-align:middle"').n
					:   '')
			).n.n;
		}


		$out[] =
		tr(tda('&nbsp;',' colspan="3" style="font-size:0.25em"')).
		tr( $lang_form ).
		tr(tda('&nbsp;',' colspan="3" style="font-size:0.25em"')).
		tr(tda(gTxt('language')).tda(gTxt('from_server')).( ($show_files) ? tda(gTxt('from_file')) : '' ), ' style="font-weight:bold"');
		$out[] = $list;
		if (!$show_files) {
			$linktext =  gTxt('from_file').' ('.gTxt('experts_only').')';
			$out[] = tr(tda('&nbsp;',' colspan="3" style="font-size:0.25em"')).
				 tr(tda(strong(eLink('prefs','list_languages','force','file',$linktext)),' colspan="3" style="text-align:center"') );
		} elseif (gps('force')=='file')	{
			$out[] =  tr(tda('&nbsp;',' colspan="3" style="font-size:0.25em"')).
				 tr(tda(sLink('prefs','list_languages',strong(gTxt('from_server'))),' colspan="3" style="text-align:center"') );
		}
		$out[] = endTable();

		$install_langfile = gTxt('install_langfile', array(
			'{url}' => strong('<a href="'.RPC_SERVER.'/lang/">'.RPC_SERVER.'/lang/</a>')
		));

		if ( $install_langfile == 'install_langfile')
			$install_langfile = 'To install new languages from file you can download them from <b><a href="'.RPC_SERVER.'/lang/">'.RPC_SERVER.'/lang/</a></b> and place them inside your ./textpattern/lang/ directory.';
		$out[] =  tag( $install_langfile ,'p',' style="text-align:center;width:50%;margin: 2em auto"' );
		return join(n, $out);

	}

//-------------------------------------------------------------

	function languages()
	{
		$installed_langs = safe_column('distinct lang', 'txp_lang', "1=1");

		$vals = array();
		foreach ($installed_langs as $lang)	{
			// human readable translated language names
			$vals[$lang] = safe_field('data', 'txp_lang', "name = '".doSlash($lang)."' AND lang = '".doSlash($lang)."'");

			// no translation of lang code available
			if (trim($vals[$lang]) == '') {
				$vals[$lang] = $lang;
			}
		}

		asort($vals);
		reset($vals);
		return $vals;
	}

//-------------------------------------------------------------
	function get_language()
	{
		global $prefs, $txpcfg, $textarray;
		require_once txpath.'/lib/IXRClass.php';
		$lang_code = gps('lang_code');

		$client = new IXR_Client(RPC_SERVER);
//		$client->debug = true;

		@set_time_limit(90);
		if (gps('force')=='file' || !$client->query('tups.getLanguage',$prefs['blog_uid'],$lang_code))
		{
			if ( (gps('force')=='file' || gps('updating')!=='1') && install_language_from_file($lang_code) )
			{
				if (defined('LANG'))
					$textarray = load_lang(LANG);
				return list_languages(gTxt($lang_code).sp.gTxt('updated'));
			}else{

				$install_langfile = gTxt('install_langfile', array(
					'{url}' => strong('<a href="'.RPC_SERVER.'/lang/">'.RPC_SERVER.'/lang/</a>')
				));

				if ( $install_langfile == 'install_langfile')
					$install_langfile = 'To install new languages from file you can download them from <b><a href="'.RPC_SERVER.'/lang/">'.RPC_SERVER.'/lang/</a></b> and place them inside your ./textpattern/lang/ directory.';
				pagetop(gTxt('installing_language'));
				echo tag( gTxt('rpc_connect_error')."<!--".$client->getErrorCode().' '.$client->getErrorMessage()."-->"
						 ,'p',' style="text-align:center;color:red;width:50%;margin: 2em auto"' );
				echo tag( $install_langfile ,'p',' style="text-align:center;width:50%;margin: 2em auto"' );
			}
		}else {
			$response = $client->getResponse();
			$lang_struct = unserialize($response);
			function install_lang_key(&$value, $key)
			{
				extract(gpsa(array('lang_code','updating')));
				$exists = safe_field('name','txp_lang',"name='".doSlash($value['name'])."' AND lang='".doSlash($lang_code)."'");
				$q = "name='".doSlash($value['name'])."', event='".doSlash($value['event'])."', data='".doSlash($value['data'])."', lastmod='".doSlash(strftime('%Y%m%d%H%M%S',$value['uLastmod']))."'";

				if ($exists)
				{
					$value['ok'] = safe_update('txp_lang',$q,"lang='".doSlash($lang_code)."' AND name='".doSlash($value['name'])."'");
				}else{
					$value['ok'] = safe_insert('txp_lang',$q.", lang='".doSlash($lang_code)."'");
				}
			}
			array_walk($lang_struct,'install_lang_key');
			$size = count($lang_struct);
			$errors = 0;
			for($i=0; $i < $size ; $i++)
			{
				$errors += ( !$lang_struct[$i]['ok'] );
			}
			if (defined('LANG'))
				$textarray = load_lang(LANG);
			$msg = gTxt($lang_code).sp.gTxt('updated');
			if ($errors > 0)
				$msg .= sprintf(" (%s errors, %s ok)",$errors, ($size-$errors));
			return list_languages($msg);
		}
	}

// ----------------------------------------------------------------------

function get_lang_files()
{
	global $txpcfg;

	$dirlist = array();

	$lang_dir = txpath.DS.'lang'.DS;

	if (!is_dir($lang_dir))
	{
		trigger_error('Lang directory is not a directory: '.$lang_dir, E_USER_WARNING);
		return $dirlist;
	}

	if (chdir($lang_dir)) {
		if (function_exists('glob')){
			$g_array = glob("*.txt");
		}else {
			# filter .txt only files here?
			$dh = opendir($lang_dir);
			$g_array = array();
			while (false !== ($filename = readdir($dh))) {
				if (strstr($filename, '.txt'))
					$g_array[] = $filename;
			}
			closedir($dh);

		}
		# build an array of lang-codes => filemtimes
		if ($g_array) {
			foreach ($g_array as $lang_name) {
				if (is_file($lang_name)) {
					$dirlist[substr($lang_name,0,5)] = @filemtime($lang_name);
				}
			}
		}
	}
	return $g_array;
}

}
?>
