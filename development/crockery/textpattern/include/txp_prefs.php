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
		$view->type = '0';
		echo $view->render();
	}

	function advanced_prefs_view() {
		global $prefs;
		$view = new PrefsView($prefs, $this->event, 'advanced_prefs', gTxt('advanced_preferences'));
		$view->type = '1';
		echo $view->render();
	}

	function list_languages_view() {
		global $prefs;
		$view = new LanguagesPrefsView($prefs, $this->event, 'list_languages', gTxt('manage_languages'));
		echo $view->render();
	}

	function edit_post() {
		// save all the prefs values
		update_pref('markup_default', $this->ps('markup_default', 'txptextile'));
		update_pref('sitename', $this->ps('sitename'));
		update_pref('siteurl', $this->ps('siteurl'));
		update_pref('site_slogan', $this->ps('site_slogan'));
		update_pref('gmtoffset', $this->ps('gmtoffset', 0));
		update_pref('is_dst', $this->ps('is_dst', 0));
		update_pref('dateformat', $this->ps('dateformat'));
		update_pref('archive_dateformat', $this->ps('archive_dateformat'));
		update_pref('permlink_mode', $this->ps('permlink_mode'));
		update_pref('logging', $this->ps('logging'));
		update_pref('use_comments', $this->ps('use_comments'));
		update_pref('production_status', $this->ps('production_status'));

		update_pref('publish_expired_articles', $this->ps('publish_expired_articles'));

		update_lastmod();

	}

}

// -------------------------------------------------------------
class PrefsView extends TxpDetailView {

	var $type = 0;

	function PrefsView($data, $event, $step, $caption='') {
		parent::TxpDetailView($data, $event, $step, $caption);
		$this->listtag = '';
		$this->rowtag = '';		
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
		$evt_list =  safe_column('distinct event', 'txp_prefs', "type = $this->type and prefs_id = 1 order by event desc");
		if (!$use_comments) {
			unset($evt_list['comments']);
		}

		foreach ($evt_list as $event) {
			$rs = safe_rows_start('*', 'txp_prefs', "type = $this->type and prefs_id = 1 and event = '".doSlash($event)."' order by position");
			$out = array();
			while ($a = nextRow($rs)) {	
				$name = $a['name'];
				$widget = array($this, $this->widget($a['html']));
				if(empty($a['choices'])) {
					$thing = call_user_func( $widget, $name, $this->data[$name]);
				} else {
					$thing = call_user_func( $widget, $name, call_user_func( array($this, $a['choices'])), $this->data[$name]);					
				}			
				$out[] = tag($thing, 'tr');
			}
			$set[] = fieldset(
						tag(join(n, $out), 'table'), 
						gTxt($event)
					 );
		}
/*

		$out[] = $this->i_text('siteurl', $this->data['siteurl']);
		$out[] = $this->i_text('site_slogan', $this->data['site_slogan']);

		$out[] = $this->i_select('gmtoffset', $this->gmtoffset_options(), $this->data['gmtoffset']);
		$out[] = $this->i_checkbox('is_dst', $this->data['is_dst']);

		$out[] = $this->i_select('dateformat', $this->dateformat_options(), $this->data['dateformat']);
		$out[] = $this->i_select('archive_dateformat', $this->dateformat_options(), $this->data['archive_dateformat']);

		$out[] = $this->i_select_radio('permlink_mode', $this->permlinkmode_options(), $this->data['permlink_mode']);
		$out[] = $this->i_select('logging', $this->logging_options(), $this->data['logging']);
		$out[] = $this->i_select('production_status', $this->prod_options(), $this->data['production_status']);

		$out[] = $this->i_checkbox('use_comments', $this->data['use_comments']);
		
		$out[] = $this->i_checkbox('publish_expired_articles', $this->data['publish_expired_articles']);
 */
 		
		return join(n, $set).$this->i_button('save');
	}


	function widget($widget)
	{
		/* map prefs UI widgets to code
		 * use Zend's nomenclature whereever available
		 * @see: http://framework.zend.com/manual/en/zend.view.helpers.html
		 */ 
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

//-------------------------------------------------------------

	function languages() 
	{
		$installed_langs = safe_column('lang', 'txp_lang', "1 = 1 GROUP BY lang");
		
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
		return $vals;
/*		$out = array();

		foreach ($vals as $avalue => $alabel) {
			$out[] = n.t.'<option value="'.htmlspecialchars($avalue).'"'.
				( ($avalue == $val || $alabel == $val) ? ' selected="selected"' :	'' ).
				'>'.$alabel.'</option>';
		}

		return n.'<select id="'.$name.'" name="'.$name.'" class="list">'.
			join('', $out).
			n.'</select>';			
*/
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

class LanguagesPrefsView extends PrefsView {
	
	function body() 
	{
		return 'TODO: list_languages_view';
	}

}
?>
