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

//-------------------------------------------------------------

	include_once txpath.'/lib/txplib_update.php';

	if ($event == 'prefs') {
		require_privs('prefs');

		$available_steps = array(
			'advanced_prefs',
			'prefs_save',
			'advanced_prefs_save',
			'get_language',
			'get_textpack',
			'list_languages',
			'prefs_list'
		);

		if(!$step or !in_array($step, $available_steps)){
			$step = 'prefs_list';
		}
		$step();
	}

// -------------------------------------------------------------
	function prefs_save()
	{
		global $prefs, $gmtoffset, $is_dst, $auto_dst, $timezone_key;

		$prefnames = safe_column("name", "txp_prefs", "prefs_id = 1");

		$post = doSlash(stripPost());

		// Forge $auto_dst for (in-)capable servers
		if (!timezone::is_supported())
		{
			$post['auto_dst'] = false;
		}
		$prefs['auto_dst'] = $auto_dst = $post['auto_dst'];

		if (!$post['auto_dst'])
		{
			$is_dst = $post['is_dst'];
		}

		// Forge $gmtoffset and $is_dst from $timezone_key if present
		if (isset($post['timezone_key']))
		{
			$key = $post['timezone_key'];
			$tz = new timezone;
			$tzd = $tz->details();
			if (isset($tzd[$key]))
			{
				$prefs['timezone_key'] = $timezone_key = $key;
				$post['gmtoffset'] = $prefs['gmtoffset'] = $gmtoffset = $tzd[$key]['offset'];
				$post['is_dst'] = $prefs['is_dst'] = $is_dst = timezone::is_dst(time(), $key);
			}

		}

		foreach($prefnames as $prefname) {
			if (isset($post[$prefname])) {
				if ($prefname == 'siteurl')
				{
					$post[$prefname] = str_replace("http://",'',$post[$prefname]);
					$post[$prefname] = rtrim($post[$prefname],"/ ");
				}

				safe_update(
					"txp_prefs",
					"val = '".$post[$prefname]."'",
					"name = '".doSlash($prefname)."' and prefs_id = 1"
				);
			}
		}

		update_lastmod();

		prefs_list(gTxt('preferences_saved'));
	}

// -------------------------------------------------------------

	function prefs_list($message = '')
	{
		global $prefs;
		extract($prefs);

		// Read real DB value instead of potentially 'stale' $prefs array when value has just changed
		$use_comments = safe_field('val', 'txp_prefs', "name='use_comments'");

		echo pagetop(gTxt('edit_preferences'), $message);

		$locale = setlocale(LC_ALL, $locale);

		echo n.'<div id="prefs_container" class="txp-container txp-list">'.
			n.n.'<form method="post" class="prefs-form basic" action="index.php">'.

			n.n.startTable('list', '', 'list').

			n.n.tr(
				tdcs(
					hed(gTxt('site_prefs'), 2)
				, 3)
			).

			n.n.tr(
				tdcs(
					sLink('prefs', 'prefs_list', gTxt('site_prefs'), 'navlink-active').sp.
					sLink('prefs', 'advanced_prefs', gTxt('advanced_preferences'), 'navlink').sp.
					sLink('prefs', 'list_languages', gTxt('manage_languages'), 'navlink')
				, '3', '', 'nav-tertiary')
			);

		$evt_list = safe_column('event', 'txp_prefs', "type = 0 and prefs_id = 1 group by event order by event desc");

		foreach ($evt_list as $event)
		{
			$rs = safe_rows_start('*', 'txp_prefs', "type = 0 and prefs_id = 1 and event = '".doSlash($event)."' order by position");

			$cur_evt = '';

			while ($a = nextRow($rs))
			{
				if ($a['event'] != $cur_evt)
				{
					$cur_evt = $a['event'];
					$ctr = 1;

					if ($cur_evt == 'comments' && !$use_comments)
					{
						continue;
					}

					echo n.n.tr(
						tdcs(
							hed(gTxt($a['event']), 3, ' class="pref-heading '.$a['event'].'-prefs"')
						, 3)
					);
				}

				if ($cur_evt == 'comments' && !$use_comments)
				{
					continue;
				}

				// Skip old settings that don't have an input type
				if (!is_callable($a['html']))
				{
					continue;
				}

				$label = ($a['html'] != 'yesnoradio') ?
					'<label for="'.$a['name'].'">'.gTxt($a['name']).'</label>' :
					gTxt($a['name']);

				$out = tda($label, ' style="text-align: right; vertical-align: middle;" class="pref-label"');

				if ($a['html'] == 'text_input')
				{
					$out.= td(
						pref_func('text_input', $a['name'], $a['val'], 20)
					, '', 'pref-value');
				}

				else
				{
					$out.= td(pref_func($a['html'], $a['name'], $a['val']), '', 'pref-value');
				}

				$out.= tda(popHelp($a['name']), ' style="vertical-align: middle;"');

				echo tr($out, " id='prefs-{$a['name']}' class='{$a['event']}-prefs ".(($ctr%2 == 0) ? 'even' : 'odd')."'");
				$ctr++;
			}
		}

		echo n.n.tr(
			tda(
				fInput('submit', 'Submit', gTxt('save_button'), 'publish').
				n.sInput('prefs_save').
				n.eInput('prefs').
				n.hInput('prefs_id', '1')
			, ' colspan="3" class="noline"')
		).

		n.n.endTable().

		n.n.'</form>'.
		n.'</div>';

		$check_updates = gps('check_updates');

		echo '<div id="prefs_control" class="txp-control-panel">';

		if ($check_updates)
		{
			$updates = checkUpdates();

			if (is_array($updates))
			{
				$out = join(br, $updates);
			}

			else{
				$out = $updates;
			}

			echo n.n.startTable('edit').

				n.n.tr(
					tda($out)
				).

				n.n.endTable();
		}

		else
		{
			echo form(
				graf(
					'<strong>'.gTxt('check_for_txp_updates').'</strong>'.sp.
					n.'<input type="submit" name="check_updates" value="'.gTxt('go').'" class="smallerbox" />'.
					n.eInput('prefs').
					n.sInput('prefs_list')
				)
			, 'text-align: center;');
		}
		echo '</div>';
	}

//-------------------------------------------------------------

	function pref_func($func, $name, $val, $size = '')
	{
		$func = (is_callable('pref_'.$func) ? 'pref_'.$func : $func);

		return call_user_func($func, $name, $val, $size);
	}

//-------------------------------------------------------------

	function text_input($name, $val, $size = '')
	{
		return fInput('text', $name, $val, 'edit', '', '', $size, '', $name);
	}

//-------------------------------------------------------------

	function gmtoffset_select($name, $val)
	{
		// Fetch *hidden* pref
		$key = safe_field('val', 'txp_prefs', "name='timezone_key'");
		$tz = new timezone;
		$ui = $tz->selectInput('timezone_key', $key, true, '', 'gmtoffset');
		return pluggable_ui('prefs_ui', 'gmtoffset', $ui, $name, $val);
	}


//-------------------------------------------------------------

	function is_dst($name, $val)
	{
		$ui = yesnoRadio ($name, $val).n.
		script_js ("textpattern.timezone_is_supported = ".(int)timezone::is_supported().";").
		script_js (<<<EOS
			$(document).ready(function(){
				var radio = $("#prefs-is_dst input");
				if (radio) {
					if ($("#auto_dst-1").attr("checked") && textpattern.timezone_is_supported) {
						radio.attr("disabled","disabled");
					}
					$("#auto_dst-0").click(
						function(){
							radio.removeAttr("disabled");
						});
				   	$("#auto_dst-1").click(
						function(){
							radio.attr("disabled","disabled");
					  	});
			   	}
				if (!textpattern.timezone_is_supported) {
					$("#prefs-auto_dst input").attr("disabled","disabled");
				}
	});
EOS
		);
		return pluggable_ui('prefs_ui', 'is_dst', $ui, $name, $val);
	}

//-------------------------------------------------------------

	function logging($name, $val)
	{
		$vals = array(
			'all'   => gTxt('all_hits'),
			'refer' => gTxt('referrers_only'),
			'none'  => gTxt('none')
		);

		return selectInput($name, $vals, $val, '', '', $name);
	}

//-------------------------------------------------------------

	function permlinkmodes($name, $val)
	{
		$vals = array(
			'messy'                => gTxt('messy'),
			'id_title'             => gTxt('id_title'),
			'section_id_title'     => gTxt('section_id_title'),
			'year_month_day_title' => gTxt('year_month_day_title'),
			'section_title'        => gTxt('section_title'),
			'title_only'           => gTxt('title_only'),
			// 'category_subcategory' => gTxt('category_subcategory')
		);

		return selectInput($name, $vals, $val, '', '', $name);
	}

//-------------------------------------------------------------
// Deprecated; permlinkmodes is used instead now
	function urlmodes($name, $val)
	{
		$vals = array(
			'0' => gTxt('messy'),
			'1' => gTxt('clean')
		);

		return selectInput($name, $vals, $val, '', '', $name);
	}

//-------------------------------------------------------------

	function commentmode($name, $val)
	{
		$vals = array(
			'0' => gTxt('nopopup'),
			'1' => gTxt('popup')
		);

		return selectInput($name, $vals, $val, '', '', $name);
	}

//-------------------------------------------------------------

	function weeks($name, $val)
	{
		$weeks = gTxt('weeks');

		$vals = array(
			'0' => gTxt('never'),
			7   => '1 '.gTxt('week'),
			14  => '2 '.$weeks,
			21  => '3 '.$weeks,
			28  => '4 '.$weeks,
			35  => '5 '.$weeks,
			42  => '6 '.$weeks
		);

		return pluggable_ui('prefs_ui', 'weeks', selectInput($name, $vals, $val, '', '', $name), $name, $val);
	}

//-------------------------------------------------------------

	function languages($name, $val)
	{
		$installed_langs = safe_column('lang', 'txp_lang', "1 = 1 group by lang");

		$vals = array();

		foreach ($installed_langs as $lang)
		{
			$vals[$lang] = safe_field('data', 'txp_lang', "name = '".doSlash($lang)."' AND lang = '".doSlash($lang)."'");

			if (trim($vals[$lang]) == '')
			{
				$vals[$lang] = $lang;
			}
		}

		asort($vals);
		reset($vals);

		$out = n.'<select id="'.$name.'" name="'.$name.'" class="list languages">';

		foreach ($vals as $avalue => $alabel)
		{
			$selected = ($avalue == $val || $alabel == $val) ?
				' selected="selected"' :
				'';

			$out .= n.t.'<option value="'.htmlspecialchars($avalue).'"'.$selected.'>'.htmlspecialchars($alabel).'</option>'.n;
		}

		$out .= n.'</select>';

		return $out;
	}

// -------------------------------------------------------------

	function dateformats($name, $val)
	{
		$dayname = '%A';
		$dayshort = '%a';
		$daynum = is_numeric(@strftime('%e')) ? '%e' : '%d';
		$daynumlead = '%d';
		$daynumord = is_numeric(substr(trim(@strftime('%Oe')), 0, 1)) ? '%Oe' : $daynum;
		$monthname = '%B';
		$monthshort = '%b';
		$monthnum = '%m';
		$year = '%Y';
		$yearshort = '%y';
		$time24 = '%H:%M';
		$time12 = @strftime('%p') ? '%I:%M %p' : $time24;
		$date = @strftime('%x') ? '%x' : '%Y-%m-%d';

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

		$vals = array();

		foreach ($formats as $f)
		{
			if ($d = safe_strftime($f, $ts))
			{
				$vals[$f] = $d;
			}
		}

		$vals['since'] = gTxt('hours_days_ago');

		return selectInput($name, array_unique($vals), $val, '', '', $name);
	}
//-------------------------------------------------------------

	function prod_levels($name, $val)
	{
		$vals = array(
			'debug'   => gTxt('production_debug'),
			'testing' => gTxt('production_test'),
			'live'    => gTxt('production_live'),
		);

		return selectInput($name, $vals, $val, '', '', $name);
	}

//-------------------------------------------------------------
	function default_event($name, $val)
	{
		$vals = areas();

		$out = array();

		foreach ($vals as $a => $b)
		{
			if (count($b) > 0)
			{
				$out[] = n.t.'<optgroup label="'.gTxt('tab_'.$a).'">';

				foreach ($b as $c => $d)
				{
					$out[] = n.t.t.'<option value="'.$d.'"'.( $val == $d ? ' selected="selected"' : '' ).'>'.$c.'</option>';
				}

				$out[] = n.t.'</optgroup>';
			}
		}

		return n.'<select name="'.$name.'" class="list default-events">'.
			join('', $out).
			n.'</select>';
	}

//-------------------------------------------------------------
	function commentsendmail($name, $val)
	{
		$vals = array(
			'1' => gTxt('all'),
			'0' => gTxt('none'),
			'2' => gTxt('ham')
		);

		return selectInput($name, $vals, $val, '', '', $name);
	}

//-------------------------------------------------------------
	function custom_set($name, $val)
	{
		return pluggable_ui('prefs_ui', 'custom_set', text_input($name, $val, 20), $name, $val);
	}

//-------------------------------------------------------------
	function themename($name, $val)
	{
		$themes = theme::names();
		foreach ($themes as $t)
		{
			$theme = theme::factory($t);
			if ($theme) {
				$m = $theme->manifest();
				$title = empty($m['title']) ? ucwords($theme->name) : $m['title'];
				$vals[$t] = $title;
				unset($theme);
			}
		}
		asort($vals, SORT_STRING);

		return pluggable_ui('prefs_ui', 'theme_name',
			selectInput($name, $vals, $val, '', '', $name));
	}
//-------------------------------------------------------------
	function advanced_prefs($message = '')
	{
		echo pagetop(gTxt('advanced_preferences'), $message).

			n.'<div id="prefs_container" class="txp-container txp-list">'.
			n.n.'<form method="post" class="prefs-form advanced" action="index.php">'.

			n.n.startTable('list', '', 'list').

			n.n.tr(
				tdcs(
					hed(gTxt('advanced_preferences'), 2)
				, 3)
			).

			n.n.tr(
				tdcs(
					sLink('prefs', 'prefs_list', gTxt('site_prefs'), 'navlink').sp.
					sLink('prefs', 'advanced_prefs', gTxt('advanced_preferences'), 'navlink-active').sp.
					sLink('prefs', 'list_languages', gTxt('manage_languages'), 'navlink')
				, '3', '', 'nav-tertiary')
			);

		$rs = safe_rows_start('*', 'txp_prefs', "type = 1 and prefs_id = 1 order by event, position");

		$cur_evt = '';

		while ($a = nextRow($rs))
		{
			if ($a['event']!= $cur_evt)
			{
				$cur_evt = $a['event'];

				$ctr = 1;

				echo n.n.tr(
					tdcs(
						hed(gTxt($a['event']), 3, ' class="pref-heading '.$a['event'].'-prefs"')
					, 3)
				);
			}

				$label = ($a['html'] != 'yesnoradio') ?
					'<label for="'.$a['name'].'">'.gTxt($a['name']).'</label>' :
					gTxt($a['name']);

			$out = tda($label, ' style="text-align: right; vertical-align: middle;" class="pref-label"');

			if ($a['html'] == 'text_input')
			{
				$look_for = array('expire_logs_after', 'max_url_len', 'time_offset', 'rss_how_many', 'logs_expire');

				$size = in_array($a['name'], $look_for) ? 3 : 20;

				$out.= td(
					pref_func('text_input', $a['name'], $a['val'], $size)
				);
			}

			else
			{
				if (is_callable($a['html']))
				{
					$out.= td(
						pref_func($a['html'], $a['name'], $a['val'])
					, '', 'pref-value');
				}

				else
				{
					$out.= td($a['val'], '', 'pref-value');
				}
			}

			$out .= tda(
				popHelp($a['name'])
			, ' style="vertical-align: middle;"');

			echo n.n.tr($out, " id='prefs-{$a['name']}' class='{$a['event']}-prefs ".(($ctr%2 == 0) ? 'even' : 'odd')."'");
			$ctr++;
		}

		echo n.n.tr(
			tda(
				fInput('submit', 'Submit', gTxt('save_button'), 'publish').
				sInput('advanced_prefs_save').
				eInput('prefs').
				hInput('prefs_id', '1')
			, ' colspan="3" class="noline"')
		).

		n.n.endTable().

		n.n.'</form>'.
		n.'</div>';
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

//-------------------------------------------------------------
	function advanced_prefs_save()
	{
		// update custom fields count from database schema and cache it as a hidden pref
		$max_custom_fields = count(preg_grep('/^custom_\d+/', getThings('describe `'.PFX.'textpattern`')));
		set_pref('max_custom_fields', $max_custom_fields, 'publish', 2);

		// safe all regular advanced prefs
		$prefnames = safe_column("name", "txp_prefs", "prefs_id = 1 AND type = 1");

		$post = doSlash(stripPost());

		if (empty($post['tempdir']))
			$post['tempdir'] = doSlash(find_temp_dir());

		if (!empty($post['file_max_upload_size']))
			$post['file_max_upload_size'] = real_max_upload_size($post['file_max_upload_size']);

		foreach($prefnames as $prefname) {
			if (isset($post[$prefname])) {
					safe_update(
						"txp_prefs",
						"val = '".$post[$prefname]."'",
						"name = '".doSlash($prefname)."' and prefs_id = 1"
					);
			}
		}

		update_lastmod();

		advanced_prefs(gTxt('preferences_saved'));
	}

//-------------------------------------------------------------
	# RPC install/update languages
	function list_languages($message='')
	{
		global $prefs, $locale, $txpcfg, $textarray;
		require_once txpath.'/lib/IXRClass.php';

		// Select and save active language
		if (!$message && ps('step')=='list_languages' && ps('language'))
		{
				$locale = doSlash(getlocale(ps('language')));
				safe_update("txp_prefs","val='".doSlash(ps('language'))."'", "name='language'");
				safe_update("txp_prefs","val='". $locale ."'", "name='locale'");
				$textarray = load_lang(doSlash(ps('language')));
				$locale = setlocale(LC_ALL, $locale);
				$message = gTxt('preferences_saved');
		}
		$active_lang = safe_field('val','txp_prefs',"name='language'");
		$lang_form = tda('<div id="language_control" class="txp-control-panel">'.
								form(gTxt('active_language').'&nbsp;&nbsp;'.
								languages('language',$active_lang).'&nbsp;&nbsp;'.
								fInput('submit','Submit',gTxt('save_button'),'').
								eInput('prefs').sInput('list_languages')
							,'display:inline;').'</div>'
						,' style="text-align:center" colspan="3"');


		$client = new IXR_Client(RPC_SERVER);
		#$client->debug = true;

		$available_lang = array();
		$rpc_connect = false;$show_files = false;

		# Get items from RPC
		@set_time_limit(90);
		if (gps('force')!='file' && $client->query('tups.listLanguages',$prefs['blog_uid']))
		{
			$rpc_connect = true;
			$response = $client->getResponse();
			foreach ($response as $language)
				$available_lang[$language['language']]['rpc_lastmod'] = gmmktime($language['lastmodified']->hour,$language['lastmodified']->minute,$language['lastmodified']->second,$language['lastmodified']->month,$language['lastmodified']->day,$language['lastmodified']->year);
		} elseif (gps('force')!='file')
		{
			$msg = gTxt('rpc_connect_error')."<!--".$client->getErrorCode().' '.$client->getErrorMessage()."-->";
		}

		# Get items from Filesystem
		$files = get_lang_files();
		if (gps('force')=='file' || !$rpc_connect)
			$show_files = true;
		if ( $show_files && is_array($files) && !empty($files) )
		{
			foreach ($files as $file)
			{
				if ($fp = @fopen(txpath.DS.'lang'.DS.$file,'r'))
				{
					$name = str_replace('.txt','',$file);
					$firstline = fgets($fp, 4069);
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
		# Get installed items from the database
		# I'm affraid we need a value here for the language itself, not for each one of the rows
		$rows = safe_rows('lang, UNIX_TIMESTAMP(MAX(lastmod)) as lastmod','txp_lang',"1 GROUP BY lang ORDER BY lastmod DESC");
		foreach ($rows as $language)
		{
			$available_lang[$language['lang']]['db_lastmod'] = $language['lastmod'];
		}

		$list = '';
		$ctr = 1;
		# Show the language table
		foreach ($available_lang as $langname => $langdat)
		{
			$file_updated = ( isset($langdat['db_lastmod']) && @$langdat['file_lastmod'] > $langdat['db_lastmod']);
			$rpc_updated = ( @$langdat['rpc_lastmod'] > @$langdat['db_lastmod']);
			$rpc_install = tda( strong(eLink('prefs','get_language','lang_code',$langname,(isset($langdat['db_lastmod']))
										? gTxt('update') : gTxt('install'),'updating',isset($langdat['db_lastmod']) )).
								br.safe_strftime('%d %b %Y %X',@$langdat['rpc_lastmod'])
							,(isset($langdat['db_lastmod']))
								? ' class="highlight" style="vertical-align:middle;text-align:center"'
								: ' style="vertical-align:middle;text-align:center"');
			$list.= tr (
				# Lang-Name & Date
				tda(gTxt($langname).
					tag( ( isset($langdat['db_lastmod']) )
							? br.'&nbsp;'.safe_strftime('%d %b %Y %X',$langdat['db_lastmod'])
							: ''
						, 'span',' class="date modified"')
					, (isset($langdat['db_lastmod']) && $rpc_updated) #tda attribute
							? ' nowrap="nowrap" class="highlight" style="vertical-align:middle"'
							: ' nowrap="nowrap" style="vertical-align:middle"' ).n.
				# RPC - Info
				(  ($rpc_updated)
					? $rpc_install
					: tda( (isset($langdat['rpc_lastmod'])) ? gTxt('updated') : '-'
						,' style="vertical-align:middle;text-align:center"')
				).n.
				# File - Info
				( ($show_files)
					? tda( tag( ( isset($langdat['file_lastmod']) )
									? eLink('prefs','get_language','lang_code',$langname,($file_updated) ? gTxt('update') : gTxt('install'),'force','file').
											br.'&nbsp;'.safe_strftime($prefs['archive_dateformat'],$langdat['file_lastmod'])
									: ' &nbsp; '  # No File available
								, 'span', ($file_updated) ? ' class="date created"' : ' class="date modified"' )
							, ' class="langfile" style="text-align:center;vertical-align:middle"').n
					: '')
			, ' class="'.(($ctr%2 == 0) ? 'even' : 'odd').'"'
			).n.n;
			$ctr++;
		}


		// Output Table + Content
		pagetop(gTxt('update_languages'),$message);

		echo n.'<div id="language_container" class="txp-container txp-list">';

		if (isset($msg) && $msg)
			echo tag ($msg,'p',' class="not-ok" style="text-align:center;width:50%;margin:2em auto"' );

		echo startTable('list', '', 'list'),

		tr(
			tdcs(
				hed(gTxt('manage_languages'), 2)
			, 3)
		),

		tr(
			tdcs(
				sLink('prefs', 'prefs_list', gTxt('site_prefs'), 'navlink').sp.
				sLink('prefs','advanced_prefs',gTxt('advanced_preferences'),'navlink').sp.
				sLink('prefs', 'list_languages', gTxt('manage_languages'), 'navlink-active')
			, '3', '', 'nav-tertiary')
		),

		tr(tda('&nbsp;',' colspan="3" style="font-size:0.25em"')),
		tr( $lang_form ),
		tr(tda('&nbsp;',' colspan="3" style="font-size:0.25em"')),
		tr(tda(gTxt('language')).tda(gTxt('from_server')).( ($show_files) ? tda(gTxt('from_file')) : '' ), ' style="font-weight:bold"');
		echo $list;

		if (gps('force')!='file')
		{
			echo n.
			tr(
				tda(
					form(
						tag(gTxt('install_textpack'), 'label', ' for="textpack-install"').n.
						'<textarea id="textpack-install" class="code" name="textpack" cols="45" rows="5"></textarea>'.n.
						tag(popHelp('get_textpack'), 'span', ' style="vertical-align: top;"').
						graf(fInput('submit', 'install_new', gTxt('upload'), 'smallerbox')).
						eInput('prefs').
						sInput('get_textpack')
					, '', '', 'post', 'edit-form', '', 'text_uploader')
				,' colspan="3"')
			);
		}

		if (!$show_files)
		{
			$linktext =  gTxt('from_file').' ('.gTxt('experts_only').')';
			echo tr(tda('&nbsp;',' colspan="3" style="font-size:0.25em"')).
				tr(tda(strong(eLink('prefs','list_languages','force','file',$linktext)),' colspan="3" style="text-align:center"') );
		} elseif (gps('force')=='file') {
			echo tr(tda('&nbsp;',' colspan="3" style="font-size:0.25em"')).
				tr(tda(sLink('prefs','list_languages',strong(gTxt('from_server'))),' colspan="3" style="text-align:center"') );
		}
		echo endTable();

		$install_langfile = gTxt('install_langfile', array(
			'{url}' => strong('<a href="'.RPC_SERVER.'/lang/">'.RPC_SERVER.'/lang/</a>')
			),
			'raw');

		if ( $install_langfile == 'install_langfile')
			$install_langfile = 'To install new languages from file you can download them from <b><a href="'.RPC_SERVER.'/lang/">'.RPC_SERVER.'/lang/</a></b> and place them inside your ./textpattern/lang/ directory.';
		echo tag( $install_langfile ,'p',' style="text-align:center;width:50%;margin: 2em auto"' );
		echo '</div>'; // end language_container
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
						,'p',' class="not-ok" style="text-align:center;width:50%;margin:2em auto"' );
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

//-------------------------------------------------------------
	function get_textpack()
	{
		$textpack = ps('textpack');
		$n = install_textpack($textpack, true);
		return list_languages(gTxt('textpack_strings_installed', array('{count}' => $n)));
	}

// ----------------------------------------------------------------------

function get_lang_files()
{
	$lang_dir = txpath.DS.'lang'.DS;

	if (!is_dir($lang_dir))
	{
		trigger_error('Lang directory is not a directory: '.$lang_dir, E_USER_WARNING);
		return array();
	}

	if (chdir($lang_dir))
	{
		$files = glob('*.txt');
	}
	return (is_array($files)) ? $files : array();
}

?>
