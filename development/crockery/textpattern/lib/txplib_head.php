<?php

/*
$HeadURL$
$LastChangedRevision$
*/

// -------------------------------------------------------------

	function pagetop($pagetitle, $message = '', $msgclass = '')
	{
		global $css_mode, $siteurl, $sitename, $txp_user, $event;

		$area = gps('area');
		$event = (!$event) ? 'article' : $event;
		$bm = gps('bm');

		$privs = safe_field('privs', 'txp_users', "name = '".doSlash($txp_user)."'");

		$GLOBALS['privs'] = $privs;

		$areas = areas();
		$area = false;

		foreach ($areas as $k => $v)
		{
			if (in_array($event, $v))
			{
				$area = $k;
				break;
			}
		}

		if (gps('logout'))
		{
			$body_id = 'page-logout';
		}

		elseif (!$txp_user)
		{
			$body_id = 'page-login';
		}

		else
		{
			$body_id = 'page-'.$event;
		}

		$theme = 'default';

		include(txpath.DS.'theme'.DS.$theme.DS.'header.php');
	}

// -------------------------------------------------------------

	function areatab($label, $event, $tarea, $area)
	{
		$tc = ($area == $event) ? 'tabup' : 'tabdown';
		$atts = ' class="'.$tc.'"';
		$hatts = ' href="?event='.$tarea.'" class="plain"';

		return n.t.t.t.tda(tag($label, 'a', $hatts), $atts);
	}

// -------------------------------------------------------------

	function tabber($label, $tabevent, $event)
	{
		$tc = ($event == $tabevent) ? 'tabup' : 'tabdown2';

		return n.t.t.t.'<td class="'.$tc.'"><a href="?event='.$tabevent.'" class="plain">'.gTxt($label).'</a></td>';
	}

// -------------------------------------------------------------
	function tabsort($area,$event)
	{
		if ($area)
		{
			$areas = areas();
			foreach($areas[$area] as $a=>$b) {
				if (has_privs($b)) {
					$out[] = tabber($a,$b,$event,2);
				}
	
	
			}
			return (empty($out) ? '' : join('',$out));
		}

		return '';
	}

// -------------------------------------------------------------
	function areas()
	{
		global $privs, $plugin_areas;

		$areas['content'] = array(
			'tab_write'    => 'article',
			'tab_list'    =>  'list',
			'tab_organise' => 'category',
			'tab_link'     => 'link',
			'tab_comments' => 'discuss'
		);

		$areas['presentation'] = array(
			'tab_pages'    => 'page',
			'tab_forms'    => 'form',
			'tab_sections' => 'section',
			'tab_style'    => 'css'
		);

		$areas['admin'] = array(
			'tab_preferences' => 'prefs',
			'tab_diagnostics' => 'diag',
			'tab_site_admin'  => 'admin',
			'tab_logs'        => 'log',
			'tab_plugins'     => 'plugin',
			'tab_import'      => 'import'
		);

		$areas['extensions'] = array(
		);

		if (is_array($plugin_areas))
			$areas = array_merge_recursive($areas, $plugin_areas);

		return $areas;
	}

// -------------------------------------------------------------

	function navPop($inline = '')
	{
		$areas = areas();

		$out = array();

		foreach ($areas as $a => $b)
		{
			if (!has_privs('tab.'.$a))
			{
				continue;
			}

			if (count($b) > 0)
			{
				$out[] = n.t.'<optgroup label="'.gTxt('tab_'.$a).'">';

				foreach ($b as $c => $d)
				{
					if (has_privs($d))
					{
						$out[] = n.t.t.'<option value="'.$d.'">'.gTxt($c).'</option>';
					}
				}

				$out[] = n.t.'</optgroup>';
			}
		}

		if ($out)
		{
			$style = ($inline) ? ' style="display: inline;"': '';

			return '<form method="get" action="index.php" class="navpop"'.$style.'>'.
				n.'<select name="event" onchange="submit(this.form);">'.
				n.t.'<option>'.gTxt('go').'&#8230;</option>'.
				join('', $out).
				n.'</select>'.
				n.'</form>';
		}
	}

// -------------------------------------------------------------
	function button($label,$link)
	{
		return '<span style="margin-right:2em"><a href="?event='.$link.'" class="plain">'.$label.'</a></span>';
	}

// -------------------------------------------------------------
	function user_message($msg, $type='message')
	{
		global $_user_messages;
		@$_user_messages[] = array($msg, $type);
	}

// -------------------------------------------------------------
	function show_user_messages($id='user_messages')
	{
		global $_user_messages;
		$out = array();

		if (is_array($_user_messages)) {
			foreach ($_user_messages as $m) {
				list($msg, $type) = $m;
				$out[] = '<div class="'.$type.'">'.htmlspecialchars($msg).'</div>';
			}
		}

		if ($out)
			return '<div id="'.$id.'">'.n.join(n, $out).n.'</div>'.n;
		return '';
	}

?>
