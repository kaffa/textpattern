<?php

/*
$HeadURL: http://svn.textpattern.com/development/crockery/textpattern/lib/txplib_head.php $
$LastChangedRevision: 1098 $
*/

// -------------------------------------------------------------
	function pagetop($pagetitle,$message="")
	{
		global $css_mode,$siteurl,$sitename,$txp_user,$event;
		$area = gps('area');
		$event = (!$event) ? 'article' : $event;
		$bm = gps('bm');

		$privs = safe_field("privs", "txp_users", "name='$txp_user'");
		
		$GLOBALS['privs'] = $privs;

		$areas = areas();
		foreach ($areas as $k=>$v) {
			if (in_array($event, $v))
				$area = $k;
		}
		
	?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
			"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
	<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
	<head>
	<meta http-equiv="content-type" content="text/html; charset=utf-8" />
	<meta name="robots" content="noindex, nofollow" />
	<title>Txp &#8250; <?php echo $sitename ?> &#8250; <?php echo $pagetitle?></title>
	<link href="textpattern.css" rel="stylesheet" type="text/css" />
	<script type="text/javascript" src="js/textpattern.js"></script>
	<script type="text/javascript">
	<!--

		var cookieEnabled = checkCookies();

		if (!cookieEnabled)
		{
			confirm('<?php echo trim(gTxt('cookies_must_be_enabled')); ?>');
		}	
<?php

	if ($event == 'list')
	{
		$sections = '';

		$rs = safe_column('name', 'txp_section', "name != 'default'");

		if ($rs)
		{
			$sections = str_replace("\n", '', stripslashes(selectInput('Section', $rs, '', true)));
		}

		$category1 = '';
		$category2 = '';

		$rs = getTree('root', 'article');

		if ($rs)
		{
			$category1 = str_replace("\n", '', treeSelectInput('Category1', $rs, ''));
			$category2 = str_replace("\n", '', treeSelectInput('Category2', $rs, ''));
		}

		$statuses = str_replace("\n", '', stripslashes(selectInput('Status', array(
			1 => gTxt('draft'),
			2 => gTxt('hidden'),
			3 => gTxt('pending'),
			4 => gTxt('live'),
			5 => gTxt('sticky'),
		), '', true)));

		$comments_annotate = onoffRadio('Annotate', safe_field('val', 'txp_prefs', "name = 'comments_on_default'"));

		$authors = '';

		$rs = safe_column('name', 'txp_users', "privs not in(0,6)");

		if ($rs)
		{
			$authors = str_replace("\n", '', stripslashes(selectInput('AuthorID', $rs, '', true)));
		}

		// output JavaScript
?>
		function poweredit(elm)
		{
			var something = elm.options[elm.selectedIndex].value;

			// Add another chunk of HTML
			var pjs = document.getElementById('js');

			if (pjs == null)
			{
				var br = document.createElement('br');
				elm.parentNode.appendChild(br);

				pjs = document.createElement('P');
				pjs.setAttribute('id','js');
				elm.parentNode.appendChild(pjs);
			}

			if (pjs.style.display == 'none' || pjs.style.display == '')
			{
				pjs.style.display = 'block';
			}

			if (something != '')
			{
				switch (something)
				{
					case 'changesection':
						var sections = '<?php echo $sections; ?>';
						pjs.innerHTML = '<span><?php echo gTxt('section') ?>: '+sections+'</span>';
					break;

					case 'changecategory1':
						var categories = '<?php echo $category1; ?>';
						pjs.innerHTML = '<span><?php echo gTxt('category1') ?>: '+categories+'</span>';
					break;

					case 'changecategory2':
						var categories = '<?php echo $category2; ?>';
						pjs.innerHTML = '<span><?php echo gTxt('category2') ?>: '+categories+'</span>';
					break;

					case 'changestatus':
						var statuses = '<?php echo $statuses; ?>';
						pjs.innerHTML = '<span><?php echo gTxt('status') ?>: '+statuses+'</span>';
					break;

					case 'changecomments':
						var comments = '<?php echo $comments_annotate; ?>';
						pjs.innerHTML = '<span><?php echo gTxt('comments'); ?>: '+comments+'</span>';
					break;

					case 'changeauthor':
						var authors = '<?php echo $authors; ?>';
						pjs.innerHTML = '<span><?php echo gTxt('author'); ?>: '+authors+'</span>';
					break;

					default:
						pjs.style.display = 'none';
					break;
				}
			}

			return false;
		}

		addEvent(window, 'load', cleanSelects);
<?php
	}
?>
	-->
	</script>
	</head>
	<body>
  <table cellpadding="0" cellspacing="0" width="100%" style="margin-bottom:2em">
  <tr><td align="left" style="background:#FFCC33"><img src="txp_img/textpattern.gif" height="15" width="368" alt="textpattern" /></td><td style="background:#FFCC33" align="right"><?php echo navPop(1); ?></td></tr>
  <tr><td align="center" class="tabs" colspan="2">
 		<?php
 		if (!$bm) {
			echo '<table cellpadding="0" cellspacing="0" align="center"><tr>
  <td valign="middle" style="width:368px">&nbsp;'.$message.'</td>';

  			foreach (areas() as $a => $tabs) {
				if ($tabs and has_privs("tab.{$a}"))
					echo areatab(gTxt("tab_{$a}"), $a, array_shift($tabs), $area);
			}

			echo
			'<td class="tabdown"><a href="'.hu.'" class="plain" target="blank">'.gTxt('tab_view_site').'</a></td>',
		 '</tr></table>',
		
		'</td></tr><tr><td align="center" class="tabs" colspan="2">
			<table cellpadding="0" cellspacing="0" align="center"><tr>',
				tabsort($area,$event),
			'</tr></table>';
		}
		echo '</td></tr></table>';
	}

// -------------------------------------------------------------
	function areatab($label,$event,$tarea,$area) 
	{
		$tc = ($area == $event) ? 'tabup' : 'tabdown';
		$atts=' class="'.$tc.'" onclick="window.location.href=\'?event='.$tarea.'\'"';
		$hatts=' href="?event='.$tarea.'" class="plain"';
      	return tda(tag($label,'a',$hatts),$atts);
	}

// -------------------------------------------------------------
	function tabber($label,$tabevent,$event) 
	{
		$tc = ($event==$tabevent) ? 'tabup' : 'tabdown2';
		$out = '<td class="'.$tc.'" onclick="window.location.href=\'?event='.$tabevent.'\'" ><a href="?event='.$tabevent.'" class="plain">'.$label.'</a></td>';
      	return $out;
	}

// -------------------------------------------------------------
	function tabsort($area,$event) 
	{
		$areas = areas();
		foreach($areas[$area] as $a=>$b) {
			$out[] = tabber(gTxt($a),$b,$event,2);
		}
		return join('',$out);
	}

// -------------------------------------------------------------
	function areas() 
	{
		global $privs, $plugin_areas;
		
		$areas['content'] = array(
			'tab_write'    => 'article',
			'tab_list'    =>  'list',
			'tab_organise' => 'category',
			'tab_image'    => 'image',
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
	function navPop($inline='') 
	{
		$areas = areas();
		$st = ($inline) ? ' style="display:inline"': '';
		$o = '<form action="index.php" method="get"'.$st.'>
				<select name="event" onchange="submit(this.form)">
				<option>'.gTxt('go').'...</option>';
		foreach ($areas as $a => $b) {
			if (count($b) > 0) {
				$o .= '<optgroup label="'.gTxt('tab_'.$a).'">';
				foreach ($b as $c => $d) {
					$o .= '<option value="'.$d.'">'.$c.'</option>';
				}
				$o .= '</optgroup>';
			}
		}
		$o .= '</select></form>';
		return $o;
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
				$out[] = '<div class="'.$type.'">'.escape_output($msg).'</div>';
			}
		}

		if ($out)
			return '<div id="'.$id.'">'.n.join(n, $out).n.'</div>'.n;
		return '';
	}

?>
