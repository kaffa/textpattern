<?php

/*
	This is Textpattern
	Copyright 2005 by Dean Allen - all rights reserved.

	Use of this software denotes acceptance of the Textpattern license agreement

$HeadURL$
$LastChangedRevision$

*/

// -------------------------------------------------------------

	function page_title($atts)
	{
		global $parentid, $thisarticle, $id, $q, $c, $s, $pg, $sitename;

		extract(lAtts(array(
			'separator' => ': ',
		), $atts));

		if ($parentid) {
			$parent_id = (int) $parent_id;
			$out = $sitename.$separator.gTxt('comments_on').' '.safe_field('Title', 'textpattern', "ID = $parentid");
		} elseif ($thisarticle['title']) {
			$out = $sitename.$separator.$thisarticle['title'];
		} elseif ($q) {
			$out = $sitename.$separator.gTxt('search_results')."$separator $q";
		} elseif ($c) {
			$out = $sitename.$separator.fetch_category_title($c);
		} elseif ($s and $s != 'default') {
			$out = $sitename.$separator.fetch_section_title($s);
		} elseif ($pg) {
			$out = $sitename.$separator.gTxt('page')." $pg";
		} else {
			$out = $sitename;
		}

		return escape_title($out);
	}

// -------------------------------------------------------------

	function css($atts)
	{
		global $s;

		extract(lAtts(array(
			'format' => 'url',
			'media'  => 'screen',
			'n'      => '',
			'rel'    => 'stylesheet',
			'title'  => '',
		), $atts));

		if ($n) {
			$url = hu.'textpattern/css.php?n='.$n;
		} elseif ($s) {
			$url = hu.'textpattern/css.php?s='.$s;
		} else {
			$url = hu.'textpattern/css.php?n=default';
		}

		if ($format == 'link') {
			return '<link rel="'.$rel.'" type="text/css"'.
				($media ? ' media="'.$media.'"' : '').
				($title ? ' title="'.$title.'"' : '').
				' href="'.$url.'" />';
		}

		return $url;
	}

// -------------------------------------------------------------

	function image($atts)
	{
		global $img_dir;

		static $cache = array();

		extract(lAtts(array(
			'class'		=> '',
			'escape'	=> '',
			'html_id' => '',
			'id'			=> '',
			'name'		=> '',
			'wraptag' => '',
		), $atts));

		if ($name)
		{
			if (isset($cache['n'][$name]))
			{
				$rs = $cache['n'][$name];
			}

			else
			{
				$name = doSlash($name);

				$rs = safe_row('*', 'txp_image', "name = '$name' limit 1");

				$cache['n'][$name] = $rs;
			}
		}

		elseif ($id)
		{
			if (isset($cache['i'][$id]))
			{
				$rs = $cache['i'][$id];
			}

			else
			{
				$id = (int) $id;

				$rs = safe_row('*', 'txp_image', "id = $id limit 1");

				$cache['i'][$id] = $rs;
			}
		}

		else
		{
			trigger_error(gTxt('unknown_image'));
			return;
		}

		if ($rs)
		{
			extract($rs);

			if ($escape == 'html')
			{
				$alt = escape_output($alt);
				$caption = escape_output($caption);
			}

			$out = '<img src="'.hu.$img_dir.'/'.$id.$ext.'" width="'.$w.'" height="'.$h.'" alt="'.$alt.'"'.
				($caption ? ' title="'.$caption.'"' : '').
				( ($html_id and !$wraptag) ? ' id="'.$html_id.'"' : '' ).
				( ($class and !$wraptag) ? ' class="'.$class.'"' : '' ).
				' />';

			return ($wraptag) ? doTag($out, $wraptag, $class, '', $html_id) : $out;
		}

		trigger_error(gTxt('unknown_image'));
	}

// -------------------------------------------------------------

	function thumbnail($atts)
	{
		global $img_dir;

		extract(lAtts(array(
			'class'			=> '',
			'escape'		=> '',
			'html_id'		=> '',
			'id'				=> '',
			'name'			=> '',
			'poplink'		=> '',
			'wraptag'		=> ''
		), $atts));

		if ($name)
		{
			$name = doSlash($name);

			$rs = safe_row('*', 'txp_image', "name = '$name' limit 1");
		}

		elseif ($id)
		{
			$id = (int) $id;

			$rs = safe_row('*', 'txp_image', "id = $id limit 1");
		}

		else
		{
			trigger_error(gTxt('unknown_image'));
			return;
		}

		if ($rs)
		{
			extract($rs);

			if ($thumbnail)
			{
				if ($escape == 'html')
				{
					$alt = escape_output($alt);
					$caption = escape_output($caption);
				}

				$out = '<img src="'.hu.$img_dir.'/'.$id.'t'.$ext.'" alt="'.$alt.'"'.
					($caption ? ' title="'.$caption.'"' : '').
					( ($html_id and !$wraptag) ? ' id="'.$html_id.'"' : '' ).
					( ($class and !$wraptag) ? ' class="'.$class.'"' : '' ).
					' />';

				if ($poplink)
				{
					$out = '<a href="'.hu.$img_dir.'/'.$id.$ext.'"'.
						' onclick="window.open(this.href, \'popupwindow\', '.
						'\'width='.$w.', height='.$h.', scrollbars, resizable\'); return false;">'.$out.'</a>';
				}

				return ($wraptag) ? doTag($out, $wraptag, $class, '', $html_id) : $out;
			}

		}

		trigger_error(gTxt('unknown_image'));
	}

// -------------------------------------------------------------
	function output_form($atts)
	{
		extract(lAtts(array(
			'form' => '',
		), $atts));

		if (!$form)
			trigger_error(gTxt('form_not_specified'));
		else
			return parse_form($form);

	}

// -------------------------------------------------------------

	function feed_link($atts, $thing=NULL)
	{
		global $s, $c;

		extract(lAtts(array(
			'category' => $c,
			'flavor'   => 'rss',
			'format'   => 'a',
			'label'    => '',
			'limit'    => '',
			'section'  => ( $s == 'default' ? '' : $s),
			'title'    => gTxt('rss_feed_title'),
			'wraptag'  => '',
		), $atts));

		$url = pagelinkurl(array(
			$flavor    => '1',
			'section'  => $section,
			'category' => $category,
			'limit'    => $limit
		));

		if ($flavor == 'atom')
		{
			$title = ($title == gTxt('rss_feed_title')) ? gTxt('atom_feed_title') : $title;
		}

		$title = escape_output($title);

		$type = ($flavor == 'atom') ? 'application/atom+xml' : 'application/rss+xml';

		if ($format == 'link')
		{
			return '<link rel="alternate" type="'.$type.'" title="'.$title.'" href="'.$url.'" />';
		}

		$txt = ($thing === NULL ? $label : parse($thing));

		$out = '<a rel="alternate" type="'.$type.'" href="'.$url.'" title="'.$title.'">'.$txt.'</a>';

		return ($wraptag) ? tag($out, $wraptag) : $out;
	}

// -------------------------------------------------------------

	function link_feed_link($atts, $thing = NULL)
	{
		global $c;

		extract(lAtts(array(
			'category' => $c,
			'flavor'   => 'rss',
			'format'   => 'a',
			'label'    => '',
			'title'    => gTxt('rss_feed_title'),
			'wraptag'  => '',
		), $atts));

		$url = pagelinkurl(array(
			$flavor => '1',
			'area'  =>'link',
			'c'     => $category
		));

		if ($flavor == 'atom')
		{
			$title = ($title == gTxt('rss_feed_title')) ? gTxt('atom_feed_title') : $title;
		}

		$title = escape_output($title);

		$type = ($flavor == 'atom') ? 'application/atom+xml' : 'application/rss+xml';

		if ($format == 'link')
		{
			return '<link rel="alternate" type="'.$type.'" title="'.$title.'" href="'.$url.'" />';
		}

		$txt = ($thing === NULL ? $label : parse($thing));

		$out = '<a rel="alternate" type="'.$type.'" href="'.$url.'"  title="'.$title.'">'.$txt.'</a>';

		return ($wraptag) ? tag($out, $wraptag) : $out;
	}

// -------------------------------------------------------------

	function linklist($atts)
	{
		global $thislink;

		extract(lAtts(array(
			'break'    => '',
			'category' => '',
			'class'    => __FUNCTION__,
			'form'     => 'plainlinks',
			'label'    => '',
			'labeltag' => '',
			'limit'    => '',
			'sort'     => 'linksort asc',
			'wraptag'  => '',
		), $atts));

		$qparts = array(
			($category) ? "category IN ('".join("','", doSlash(do_list($category)))."')" : '1=1',
			'order by '.doSlash($sort),
			($limit) ? 'limit '.intval($limit) : ''
		);

		$rs = safe_rows_start('*, unix_timestamp(date) as uDate', 'txp_link', join(' ', $qparts));

		if ($rs)
		{
			$out = array();

			while ($a = nextRow($rs))
			{
				extract($a);

				$thislink = array(
					'id'          => $id,
					'linkname'    => $linkname,
					'url'         => $url,
					'description' => $description,
					'date'        => $uDate,
					'category'    => $category,
				);

				$out[] = parse_form($form);

				$thislink = '';
			}

			if ($out)
			{
				return doLabel($label, $labeltag).doWrap($out, $wraptag, $break, $class);
			}
		}

		return false;
	}

// -------------------------------------------------------------

	function tpt_link($atts)
	{
		global $thislink;
		assert_link();

		extract(lAtts(array(
			'rel' => '',
		), $atts));

		return tag(
			escape_output($thislink['linkname']), 'a',
			($rel ? ' rel="'.$rel.'"' : '').
			' href="'.doSpecial($thislink['url']).'"'
		);
	}

// -------------------------------------------------------------

	function linkdesctitle($atts)
	{
		global $thislink;
		assert_link();

		extract(lAtts(array(
			'rel' => '',
		), $atts));

		$description = ($thislink['description']) ?
			' title="'.escape_output($thislink['description']).'"' :
			'';

		return tag(
			escape_output($thislink['linkname']), 'a',
			($rel ? ' rel="'.$rel.'"' : '').
			' href="'.doSpecial($thislink['url']).'"'.$description
		);
	}

// -------------------------------------------------------------

	function link_name($atts)
	{
		global $thislink;
		assert_link();

		extract(lAtts(array(
			'escape' => '',
		), $atts));

		return ($escape == 'html') ?
			escape_output($thislink['linkname']) :
			$thislink['linkname'];
	}

// -------------------------------------------------------------

	function link_url($atts)
	{
		global $thislink;
		assert_link();

		return $thislink['url'];
	}

// -------------------------------------------------------------

	function link_description($atts)
	{
		global $thislink;
		assert_link();

		extract(lAtts(array(
			'class'    => '',
			'escape'   => '',
			'label'    => '',
			'labeltag' => '',
			'wraptag'  => '',
		), $atts));

		if ($thislink['description'])
		{
			$description = ($escape == 'html') ?
				escape_output($thislink['description']) :
				$thislink['description'];

			return doLabel($label, $labeltag).doTag($description, $wraptag, $class);
		}
	}

// -------------------------------------------------------------

	function link_date($atts)
	{
		global $thislink, $dateformat;
		assert_link();

		extract(lAtts(array(
			'format' => $dateformat,
			'gmt'    => '',
			'lang'   => '',
		), $atts));

		return safe_strftime($format, $thislink['date'], $gmt, $lang);
	}

// -------------------------------------------------------------

	function link_category($atts)
	{
		global $thislink;
		assert_link();

		extract(lAtts(array(
			'class'    => '',
			'label'    => '',
			'labeltag' => '',
			'title'    => 1,
			'wraptag'  => '',
		), $atts));

		if ($thislink['category'])
		{
			$category = ($title) ?
				fetch_category_title($thislink['category'], 'link') :
				$thislink['category'];

			return doLabel($label, $labeltag).doTag($category, $wraptag, $class);
		}
	}

// -------------------------------------------------------------
	function email($atts, $thing='') // simple contact link
	{
		extract(lAtts(array(
			'email'    => '',
			'linktext' => gTxt('contact'),
			'title'    => '',
		),$atts));

		if($email) {
			$out  = array(
				'<a href="'.eE('mailto:'.$email).'"',
				($title) ? ' title="'.$title.'"' : '',
				'>',
				($thing) ? parse($thing) : $linktext,
				'</a>'
			);
			return join('',$out);
		}
		return '';
	}

// -------------------------------------------------------------
	function password_protect($atts)
	{
		ob_start();

		extract(lAtts(array(
			'login' => '',
			'pass'  => '',
		),$atts));

		$au = serverSet('PHP_AUTH_USER');
		$ap = serverSet('PHP_AUTH_PW');
		//For php as (f)cgi, two rules in htaccess often allow this workaround
		$ru = serverSet('REDIRECT_REMOTE_USER');
		if ($ru && !$au && !$ap && substr( $ru,0,5) == 'Basic' ) {
			list ( $au, $ap ) = explode( ':', base64_decode( substr( $ru,6)));
		}
		if ($login && $pass) {
			if (!$au || !$ap || $au!= $login || $ap!= $pass) {
				header('WWW-Authenticate: Basic realm="Private"');
				txp_die(gTxt('auth_required'), '401');
			}
		}
	}

// -------------------------------------------------------------

	function recent_articles($atts)
	{
		global $prefs;
		extract(lAtts(array(
			'break'    => br,
			'category' => '',
			'class'    => __FUNCTION__,
			'label'    => '',
			'labeltag' => '',
			'limit'    => 10,
			'section'  => '',
			'sort'     => 'Posted desc',
			'wraptag'  => '',
			'no_widow' => @$prefs['title_no_widow'],
		), $atts));

		$category   = join("','", doSlash(do_list($category)));
		$categories = ($category) ? "and (Category1 IN ('".$category."') or Category2 IN ('".$category."'))" : '';
		$section = ($section) ? " and Section IN ('".join("','", doSlash(do_list($section)))."')" : '';
		$expired = ($prefs['publish_expired_articles']) ? '' : ' and (now() <= Expires or Expires = '.NULLDATETIME.') ';

		$rs = safe_rows_start('*, id as thisid, unix_timestamp(Posted) as posted', 'textpattern',
			"Status = 4 $section $categories and Posted <= now()$expired order by ".doSlash($sort).' limit 0,'.intval($limit));

		if ($rs)
		{
			$out = array();

			while ($a = nextRow($rs))
			{
				$a['Title'] = ($no_widow) ? noWidow(escape_title($a['Title'])) : escape_title($a['Title']);
				$out[] = href($a['Title'], permlinkurl($a));
			}

			if ($out)
			{
				return doLabel($label, $labeltag).doWrap($out, $wraptag, $break, $class);
			}
		}

		return '';
	}

// -------------------------------------------------------------

	function recent_comments($atts)
	{
		global $prefs;
		extract(lAtts(array(
			'break'    => br,
			'class'    => __FUNCTION__,
			'label'    => '',
			'labeltag' => '',
			'limit'    => 10,
			'sort'     => 'posted desc',
			'wraptag'  => '',
		), $atts));

		$pattern = '/\s*(parentid|name|discussid|web|email|ip|posted|message|visible)/';
		$replace = 'd.\\1';
		$sort = preg_replace($pattern, $replace, $sort);
		$expired = ($prefs['publish_expired_articles']) ? '' : ' and (now() <= t.Expires or t.Expires = '.NULLDATETIME.') ';
		
		$fields = 'd.name, d.discussid, t.ID as thisid, unix_timestamp(t.Posted) as posted, unix_timestamp(t.Expires) as expires,'.
			't.AuthorID, t.LastMod, t.LastModID, t.Title, t.Image, t.Category1, t.Category2, '.
			't.Annotate, t.AnnotateInvite, t.comments_count, t.Status, t.Section, t.override_form, t.Keywords, t.url_title,'.
			't.custom_1, t.custom_2, t.custom_3, t.custom_4, t.custom_5, t.custom_6, t.custom_7, t.custom_8, t.custom_9, t.custom_10, t.uid';

		$rs = startRows('select '. $fields .
				' from '. safe_pfx('txp_discuss') .' as d inner join '. safe_pfx('textpattern') .' as t on d.parentid = t.ID '.
				'where t.Status >= 4'.$expired.' and d.visible = '.VISIBLE.' order by '.doSlash($sort).' limit 0,'.intval($limit));	
		if ($rs) {
			while ($c = nextRow($rs)) {
				$out[] = href(
					$c['name'].' ('.escape_title($c['Title']).')', 
					permlinkurl($c).'#c'.$c['discussid']
				);
			}

			if ($out) {
				return doLabel($label, $labeltag).doWrap($out, $wraptag, $break, $class);
			}
		}

		return '';
	}

// -------------------------------------------------------------

	function related_articles($atts)
	{
		global $thisarticle, $prefs;

		assert_article();

		extract(lAtts(array(
			'break'    => br,
			'class'    => __FUNCTION__,
			'label'    => '',
			'labeltag' => '',
			'limit'    => 10,
			'match'    => 'Category1,Category2',
			'no_widow' => @$prefs['title_no_widow'],
			'section'  => '',
			'sort'     => 'Posted desc',
			'wraptag'  => '',
		), $atts));

		if (empty($thisarticle['category1']) and empty($thisarticle['category2']))
		{
			return;
		}

		$match = do_list($match);

		if (!in_array('Category1', $match) and !in_array('Category2', $match))
		{
			return;
		}

		$id = $thisarticle['thisid'];

		$cats = array();

		if ($thisarticle['category1'])
		{
			$cats[] = doSlash($thisarticle['category1']);
		}

		if ($thisarticle['category2'])
		{
			$cats[] = doSlash($thisarticle['category2']);
		}

		$cats = join("','", $cats);

		$categories = array();

		if (in_array('Category1', $match))
		{
			$categories[] = "Category1 in('$cats')";
		}

		if (in_array('Category2', $match))
		{
			$categories[] = "Category2 in('$cats')";
		}

		$categories = 'and ('.join(' or ', $categories).')';
		$section = ($section) ? " and Section IN ('".join("','", doSlash(do_list($section)))."')" : '';
		$expired = ($prefs['publish_expired_articles']) ? '' : ' and (now() <= Expires or Expires = '.NULLDATETIME.') ';

		$rs = safe_rows_start('*, unix_timestamp(Posted) as posted', 'textpattern',
			'ID != '.intval($id)." and Status = 4 and Posted <= now() $expired $categories $section order by ".doSlash($sort).' limit 0,'.intval($limit));

		if ($rs)
		{
			$out = array();

			while ($a = nextRow($rs))
			{
				$a['Title'] = ($no_widow) ? noWidow(escape_title($a['Title'])) : escape_title($a['Title']);

				$out[] = href($a['Title'], permlinkurl($a));
			}

			if ($out)
			{
				return doLabel($label, $labeltag).doWrap($out, $wraptag, $break, $class);
			}
		}

		return '';
	}

// -------------------------------------------------------------

	function popup($atts)
	{
		global $s, $c;

		extract(lAtts(array(
			'label'        => gTxt('browse'),
			'wraptag'      => '',
			'section'      => '',
			'this_section' => 0,
			'type'         => 'c',
		), $atts));

		if ($type == 's')
		{
			$rs = safe_rows_start('name, title', 'txp_section', "name != 'default' order by name");
		}

		else
		{
			$rs = safe_rows_start('name, title', 'txp_category', "type = 'article' and name != 'root' order by name");
		}

		if ($rs)
		{
			$out = array();

			$current = ($type == 's') ? $s : $c;

			$sel = '';
			$selected = false;

			while ($a = nextRow($rs))
			{
				extract($a);

				if ($name == $current)
				{
					$sel = ' selected="selected"';
					$selected = true;
				}

				$out[] = '<option value="'.$name.'"'.$sel.'>'.htmlspecialchars($title).'</option>';

				$sel = '';
			}

			if ($out)
			{
				$section = ($this_section) ? ( $s == 'default' ? '' : $s) : $section;

				$out = n.'<select name="'.$type.'" onchange="submit(this.form);">'.
					n.t.'<option value=""'.($selected ? '' : ' selected="selected"').'>&nbsp;</option>'.
					n.t.join(n.t, $out).
					n.'</select>';

				if ($label)
				{
					$out = $label.br.$out;
				}

				if ($wraptag)
				{
					$out = tag($out, $wraptag);
				}

				return '<form method="get" action="'.hu.'">'.
					'<div>'.
					( ($type != 's' and $section and $s) ? n.hInput('s', $section) : '').
					n.$out.
					n.'<noscript><div><input type="submit" value="'.gTxt('go').'" /></div></noscript>'.
					n.'</div>'.
					n.'</form>';
			}
		}
	}

// -------------------------------------------------------------
// output href list of site categories

	function category_list($atts, $thing='')
	{
		global $s, $c;

		extract(lAtts(array(
			'active_class' => '',
			'break'        => br,
			'categories'   => '',
			'class'        => __FUNCTION__,
			'exclude'      => '',
			'form'         => '',	// @TODO, $thing
			'label'        => '',
			'labeltag'     => '',
			'parent'       => '',
			'section'      => '',
			'sort'         => '',
			'this_section' => 0,
			'type'         => 'article',
			'wraptag'      => '',
		), $atts));

		$sort = doSlash($sort);

		if ($categories) {
			$categories = do_list($categories);
			$categories = join("','", doSlash($categories));

			$rs = safe_rows_start('name, title', 'txp_category',
				"type = '".doSlash($type)."' and name in ('$categories') order by ".($sort ? $sort : "field(name, '$categories')"));
		} else {
			if ($exclude) {
				$exclude = do_list($exclude);

				$exclude = join("','", doSlash($exclude));

				$exclude = "and name not in('$exclude')";
			}

			if ($parent) {
				$qs = safe_row('lft, rgt', 'txp_category', "name = '".doSlash($parent)."'");

				if ($qs) {
					extract($qs);

					$rs = safe_rows_start('name, title', 'txp_category',
						"(lft between $lft and $rgt) and type = '".doSlash($type)."' and name != 'default' $exclude order by ".($sort ? $sort : 'lft ASC'));
				}
			} else {
				$rs = safe_rows_start('name, title', 'txp_category',
					"type = '$type' and name not in('default','root') $exclude order by ".($sort ? $sort : 'name ASC'));
			}
		}

		if ($rs) {
			$out = array();

			while ($a = nextRow($rs)) {
				extract($a);

				if ($name) {
					$section = ($this_section) ? ( $s == 'default' ? '' : $s ) : $section;

					$out[] = tag(str_replace('& ', '&#38; ', $title), 'a',
						( ($active_class and (0 == strcasecmp($c, $name))) ? ' class="'.$active_class.'"' : '' ).
						' href="'.pagelinkurl(array('s' => $section, 'c' => $name)).'"'
					);
				}
			}

			if ($out) {
				return doLabel($label, $labeltag).doWrap($out, $wraptag, $break, $class);
			}
		}

		return '';
	}

// -------------------------------------------------------------
// output href list of site sections

	function section_list($atts, $thing='')
	{
		global $sitename, $s, $thissection;

		extract(lAtts(array(
			'active_class'    => '',
			'break'           => br,
			'class'           => __FUNCTION__,
			'default_title'   => $sitename,
			'exclude'         => '',
			'form'            => '',
			'include_default' => '',
			'label'           => '',
			'labeltag'        => '',
			'parents'         => '', // @todo
			'sections'        => '',
			'sort'            => '',
			'wraptag'         => ''
		), $atts));

		$sort = doSlash($sort);

		if ($sections) {
			if ($include_default) {
				$sections = 'default,'.$sections;
			}
			$sections = do_list($sections);
			$sections = join("','", doSlash($sections));
			$rs = safe_rows('*', 'txp_section', "name in ('$sections') order by ".($sort ? $sort : "field(name, '$sections')"));
		} else {
			if ($exclude)
			{
				$exclude = do_list($exclude.',default');
				$exclude = join("','", doSlash($exclude));
				$exclude = "name not in('$exclude')";
			} else {
				$exclude = "name != 'default'";
			}

			$rs = safe_rows('*', 'txp_section', "$exclude order by ".($sort ? $sort : 'name ASC'));
			if ($include_default) {
				array_unshift($rs, array('name' => 'default',
										'title' => $default_title,
										'parent' => 0,
										'url' => pagelinkurl(array('s' => 'default'))));
			}

		}

		if ($rs) {
			$out = array();

			$old_section = $thissection;
			foreach ($rs as $a) {
				extract($a);

				if (empty($form) && empty($thing)) {
					$out[] = tag($title, 'a',
						( ($active_class and (0 == strcasecmp($s, $name))) ? ' class="'.$active_class.'"' : '' ).
						' href="'.pagelinkurl(array('s' => $name)).'"');
				} else {
					$thissection = array(
						'name'   => $name,
						'title'  => ($name == 'default') ? $default_title : $title,
						'url'    => pagelinkurl(array('s' => $name)),
						'parent' => $parent
					);

					if (empty($form)) {
						$out[] = parse($thing);
					} else {
						$out[] = parse_form($form);
					}
				}
			}
			$thissection = $old_section;

			if ($out) {
				return doLabel($label, $labeltag).doWrap($out, $wraptag, $break, $class);
			}
		}

		return '';
	}

// -------------------------------------------------------------

	function section_name($atts)
	{
		global $thissection;
		assert_section();
		extract(lAtts(array(
			'escape'	=> ''
		), $atts));
		return ($escape == 'html') ? escape_output($thissection['name']) : $thissection['name'];
	}

// -------------------------------------------------------------

	function section_title($atts)
	{
		global $thissection;
		assert_section();
		extract(lAtts(array(
			'escape'	=> ''
		), $atts));
		return ($escape == 'html') ? escape_output($thissection['title']) : $thissection['title'];
	}

// -------------------------------------------------------------

	function section_url($atts)
	{
		global $thissection;
		assert_section();
		return $thissection['url'];
	}

// -------------------------------------------------------------

	function section_parent($atts)
	{
		global $thissection;
		assert_section();
		return $thissection['parent'];
	}

// -------------------------------------------------------------

	function if_active_section($atts, $thing)
	{
		global $thissection, $s;
		assert_section();
		return parse(EvalElse($thing, $thissection['name'] == $s));
	}

// -------------------------------------------------------------
// input form for search queries

	function search_input($atts)
	{
		global $pretext, $prefs;

		extract(lAtts(array(
			'button'  => '',
			'form'    => 'search_input',
			'label'   => gTxt('search'),
			'section' => '',
			'size'    => '15',
			'wraptag' => 'p',
		), $atts));

		if ($form) {
			$rs = fetch('form', 'txp_form', 'name', $form);

			if ($rs) {
				return $rs;
			}
		}

		$out = fInput('text', 'q', $pretext['q'], '', '', '', $size);

		if ($label) {
			$out = $label.br.$out;
		}

		if ($button) {
			$out .= '<input type="submit" value="'.$button.'" />';
		}

		if ($wraptag) {
			$out = tag($out, $wraptag);
		}

		if (!$section) {
			return '<form method="get" action="'.hu.'">'.
				n.$out.
				n.'</form>';
		}

		if ($prefs['permlink_mode'] != 'messy') {
			return '<form method="get" action="'.pagelinkurl(array('s' => $section)).'">'.
				n.$out.
				n.'</form>';
		}

		return '<form method="get" action="'.hu.'">'.
			n.hInput('s', $section).
			n.$out.
			n.'</form>';
	}

// -------------------------------------------------------------
// link to next article, if it exists

	function link_to_next($atts, $thing)
	{
		global $id, $next_id, $next_title;

		extract(lAtts(array(
			'showalways' => 0,
		), $atts));

		if (intval($id) == 0)
		{
			global $thisarticle, $s;

			assert_article();

			extract(getNextPrev(
				@$thisarticle['thisid'],
				@strftime('%Y-%m-%d %H:%M:%S', $thisarticle['posted']),
				@$s
			));
		}

		if ($next_id)
		{
			$url = permlinkurl_id($next_id);

			if ($thing)
			{
				$thing = parse($thing);

				return '<a rel="next" href="'.$url.'"'.
					($next_title != $thing ? ' title="'.$next_title.'"' : '').
					'>'.$thing.'</a>';
			}

			return $url;
		}

		return ($showalways) ? parse($thing) : '';
	}

// -------------------------------------------------------------
// link to next article, if it exists

	function link_to_prev($atts, $thing)
	{
		global $id, $prev_id, $prev_title;

		extract(lAtts(array(
			'showalways' => 0,
		), $atts));

		if (intval($id) == 0)
		{
			global $thisarticle, $s;

			assert_article();

			extract(getNextPrev(
				$thisarticle['thisid'],
				@strftime('%Y-%m-%d %H:%M:%S', $thisarticle['posted']),
				@$s
			));
		}

		if ($prev_id)
		{
			$url = permlinkurl_id($prev_id);

			if ($thing)
			{
				$thing = parse($thing);

				return '<a rel="prev" href="'.$url.'"'.
					($prev_title != $thing ? ' title="'.$prev_title.'"' : '').
					'>'.$thing.'</a>';
			}

			return $url;
		}

		return ($showalways) ? parse($thing) : '';
	}

// -------------------------------------------------------------

	function next_title()
	{
		return $GLOBALS['next_title'];
	}

// -------------------------------------------------------------

	function prev_title()
	{
		return $GLOBALS['prev_title'];
	}

// -------------------------------------------------------------

	function site_name()
	{
		return $GLOBALS['sitename'];
	}

// -------------------------------------------------------------

	function site_slogan()
	{
		return $GLOBALS['site_slogan'];
	}

// -------------------------------------------------------------

	function link_to_home($atts, $thing = false)
	{
		extract(lAtts(array(
			'class' => false
		), $atts));

		if ($thing)
		{
			$class = ($class) ? ' class="'.$class.'"' : '';
			return '<a rel="home" href="'.hu.'"'.$class.'>'.parse($thing).'</a>';
		}

		return hu;
	}

// -------------------------------------------------------------

	function newer($atts, $thing = false, $match = '')
	{
		global $thispage, $pretext, $permlink_mode;

		extract(lAtts(array(
			'showalways' => 0,
		), $atts));

		$numPages = $thispage['numPages'];
		$pg				= $thispage['pg'];

		if ($numPages > 1 and $pg > 1 and $pg <= $numPages)
		{
			$nextpg = ($pg - 1 == 1) ? 0 : ($pg - 1);

			// author urls should use RealName, rather than username
			if (!empty($pretext['author'])) {
				$author = safe_field('RealName', 'txp_users', "name = '".doSlash($pretext['author'])."'");
			} else {
				$author = '';
			}

			$url = pagelinkurl(array(
				'month'  => @$pretext['month'],
				'pg'     => $nextpg,
				's'      => @$pretext['s'],
				'c'      => @$pretext['c'],
				'q'      => @$pretext['q'],
				'author' => $author
			));

			if ($thing)
			{
				return '<a href="'.$url.'"'.
					(empty($title) ? '' : ' title="'.$title.'"').
					'>'.parse($thing).'</a>';
			}

			return $url;
		}

		return ($showalways) ? parse($thing) : '';
	}

// -------------------------------------------------------------

	function older($atts, $thing = false, $match = '')
	{
		global $thispage, $pretext, $permlink_mode;

		extract(lAtts(array(
			'showalways' => 0,
		), $atts));

		$numPages = $thispage['numPages'];
		$pg				= $thispage['pg'];

		if ($numPages > 1 and $pg > 0 and $pg < $numPages)
		{
			$nextpg = $pg + 1;

			// author urls should use RealName, rather than username
			if (!empty($pretext['author'])) {
				$author = safe_field('RealName', 'txp_users', "name = '".doSlash($pretext['author'])."'");
			} else {
				$author = '';
			}

			$url = pagelinkurl(array(
				'month'  => @$pretext['month'],
				'pg'     => $nextpg,
				's'      => @$pretext['s'],
				'c'      => @$pretext['c'],
				'q'      => @$pretext['q'],
				'author' => $author
			));

			if ($thing)
			{
				return '<a href="'.$url.'"'.
					(empty($title) ? '' : ' title="'.$title.'"').
					'>'.parse($thing).'</a>';
			}

			return $url;
		}

		return ($showalways) ? parse($thing) : '';
	}

// -------------------------------------------------------------
	function text($atts)
	{
		extract(lAtts(array(
			'item' => '',
		),$atts));
		return ($item) ? gTxt($item) : '';
	}

// -------------------------------------------------------------

	function article_id()
	{
		global $thisarticle;

		assert_article();

		return $thisarticle['thisid'];
	}

// -------------------------------------------------------------

	function article_url_title()
	{
		global $thisarticle;

		assert_article();

		return $thisarticle['url_title'];
	}

// -------------------------------------------------------------

	function if_article_id($atts, $thing)
	{
		global $thisarticle;

		assert_article();

		extract(lAtts(array(
			'id' => '',
		), $atts));

		if ($id)
		{
			return parse(EvalElse($thing, in_list($thisarticle['thisid'], $id)));
		}
	}

// -------------------------------------------------------------

	function posted($atts)
	{
		global $thisarticle, $pretext, $prefs;

		assert_article();

		extract(lAtts(array(
			'format'  => '',
			'gmt'     => '',
			'lang'    => '',
		), $atts));

		if ($format) {
			return safe_strftime($format, $thisarticle['posted'], $gmt, $lang);
		} else {
			if ($pretext['id'] or $pretext['c'] or $pretext['pg']) {
				return safe_strftime($prefs['archive_dateformat'], $thisarticle['posted']);
			} else {
				return safe_strftime($prefs['dateformat'], $thisarticle['posted']);
			}
		}
	}


// -------------------------------------------------------------

	function expires($atts)
	{
		global $thisarticle, $pretext, $prefs;

		assert_article();

		if($thisarticle['expires'] == NULLDATETIME)	{
			return;
		}

		extract(lAtts(array(
			'format'  => '',
			'gmt'     => '',
			'lang'    => '',
		), $atts));

		if ($format) {
			return safe_strftime($format, $thisarticle['expires'], $gmt, $lang);
		} else {
			if ($pretext['id'] or $pretext['c'] or $pretext['pg']) {
				return safe_strftime($prefs['archive_dateformat'], $thisarticle['expires']);
			} else {
				return safe_strftime($prefs['dateformat'], $thisarticle['expires']);
			}
		}
	}

// -------------------------------------------------------------

	function if_expires($atts, $thing)
	{
		global $thisarticle;
		assert_article();
		return parse(EvalElse($thing, $thisarticle['expires'] != NULLDATETIME));
	}

// -------------------------------------------------------------

	function if_expired($atts, $thing)
	{
		global $thisarticle;
		assert_article();
		return parse(EvalElse($thing,
			($thisarticle['expires'] != NULLDATETIME) && ($thisarticle['expires'] <= time() )));
	}

// -------------------------------------------------------------

	function modified($atts)
	{
		global $thisarticle, $pretext, $prefs;

		assert_article();

		extract(lAtts(array(
			'format'  => '',
			'gmt'     => '',
			'lang'    => '',
		), $atts));

		if ($format) {
			return safe_strftime($format, $thisarticle['modified'], $gmt, $lang);
		} else {
			if ($pretext['id'] or $pretext['c'] or $pretext['pg']) {
				return safe_strftime($prefs['archive_dateformat'], $thisarticle['modified']);
			} else {
				return safe_strftime($prefs['dateformat'], $thisarticle['modified']);
			}
		}
	}

// -------------------------------------------------------------

	function comments_count($atts)
	{
		global $thisarticle;

		assert_article();

		return $thisarticle['comments_count'];
	}

// -------------------------------------------------------------
	// This whole function is a FIXME. Still need to rethink comments_invite for crockery
	function comments_invite($atts)
	{
		global $thisarticle,$is_article_list;

		assert_article();

		extract($thisarticle);
		global $comments_mode;

		if (!$comments_invite)
			$comments_invite = @$GLOBALS['prefs']['comments_default_invite'];

		extract(lAtts(array(
			'class'		=> __FUNCTION__,
			'showcount'	=> true,
			'textonly'	=> false,
			'showalways'=> false,  //FIXME in crockery. This is only for BC.
			'wraptag'   => '',
		), $atts));

		$invite_return = '';
		if (($annotate or $comments_count) && ($showalways or $is_article_list) ) {

			$ccount = ($comments_count && $showcount) ?  ' ['.$comments_count.']' : '';
			if ($textonly)
				$invite_return = $comments_invite.$ccount;
			else
			{
				if (!$comments_mode) {
					$invite_return = doTag($comments_invite, 'a', $class, ' href="'.permlinkurl($thisarticle).'#'.gTxt('comment').'" '). $ccount;
				} else {
					$invite_return = "<a href=\"".hu."?parentid=$thisid\" onclick=\"window.open(this.href, 'popupwindow', 'width=500,height=500,scrollbars,resizable,status'); return false;\"".(($class) ? ' class="'.$class.'"' : '').'>'.$comments_invite.'</a> '.$ccount;
				}
			}
			if ($wraptag) $invite_return = doTag($invite_return, $wraptag, $class);
		}

		return $invite_return;
	}
// -------------------------------------------------------------
	function comments_form($atts)
	{
		global $thisarticle, $comment_preview;

		extract(lAtts(array(
			'class'        => __FUNCTION__,
			'form'         => 'comment_form',
			'isize'        => '25',
			'msgcols'      => '25',
			'msgrows'      => '5',
			'msgstyle'     => '',
			'preview'      => false,
			'wraptag'      => '',
		), $atts));

		# don't display the comment form at the bottom, since it's
		# already shown at the top
		if (ps('preview') and empty($comment_preview) and !$preview)
			return '';

		assert_article();

		extract($thisarticle);

		$ip = serverset('REMOTE_ADDR');

		$blacklisted = is_blacklisted($ip);

		if (!checkCommentsAllowed($thisid)) {
			$out = graf(gTxt("comments_closed"), ' id="comments_closed"');
		} elseif (!checkBan($ip)) {
			$out = graf(gTxt('you_have_been_banned'), ' id="comments_banned"');
		} elseif ($blacklisted) {
			$out = graf(gTxt('your_ip_is_blacklisted_by'.' '.$blacklisted), ' id="comments_blacklisted"');
		} elseif (gps('commented')!=='') {
			$out = gTxt("comment_posted");
			if (gps('commented')==='0')
				$out .= " ". gTxt("comment_moderated");
			$out = graf($out, ' id="txpCommentInputForm"');
		} else {
			$out = commentForm($thisid,$atts);
		}

		return (!$wraptag ? $out : doTag($out,$wraptag,$class) );
	}

// -------------------------------------------------------------

	function comments_error($atts)
	{
		extract(lAtts(array(
			'break'		=> 'br',
			'class'		=> __FUNCTION__,
			'wraptag'	=> 'div',
		), $atts));

		$evaluator =& get_comment_evaluator();

		$errors = $evaluator->get_result_message();

		if ($errors)
		{
			return doWrap($errors, $wraptag, $break, $class);
		}
	}

// -------------------------------------------------------------
	function if_comments_error($atts, $thing)
	{
		$evaluator =& get_comment_evaluator();
		return parse(EvalElse($thing,(count($evaluator -> get_result_message()) > 0)));
	}

// -------------------------------------------------------------
	function comments($atts)
	{
		global $thisarticle, $prefs;
		extract($prefs);

		extract(lAtts(array(
			'form'		=> 'comments',
			'wraptag'	=> ($comments_are_ol ? 'ol' : ''),
			'break'		=> ($comments_are_ol ? 'li' : 'div'),
			'class'		=> __FUNCTION__,
			'breakclass'=> '',
			'sort'		=> 'posted ASC',
		),$atts));

		assert_article();

		extract($thisarticle);

		if (!$comments_count) return '';

		$rs = safe_rows_start("*, unix_timestamp(posted) as time", "txp_discuss",
			'parentid='.intval($thisid).' and visible='.VISIBLE.' order by '.doSlash($sort));

		$out = '';

		if ($rs) {
			$comments = array();

			while($vars = nextRow($rs)) {
				$GLOBALS['thiscomment'] = $vars;
				$comments[] = parse_form($form).n;
				unset($GLOBALS['thiscomment']);
			}

			$out .= doWrap($comments,$wraptag,$break,$class,$breakclass);
		}

		return $out;
	}

// -------------------------------------------------------------
	function comments_preview($atts, $thing='', $me='')
	{
		global $has_comments_preview;

		if (!ps('preview'))
			return;

		extract(lAtts(array(
			'form'    => 'comments',
			'wraptag' => '',
			'class'   => __FUNCTION__,
		),$atts));

		assert_article();

		$preview = psa(array('name','email','web','message','parentid','remember'));
		$preview['time'] = time();
		$preview['discussid'] = 0;
		if ($preview['message'] == '')
		{
			$in = getComment();
			$preview['message'] = $in['message'];

		}
		$preview['message'] = markup_comment($preview['message']);
		$preview['web'] = clean_url($preview['web']);

		$GLOBALS['thiscomment'] = $preview;
		$comments = parse_form($form).n;
		unset($GLOBALS['thiscomment']);
		$out = doTag($comments,$wraptag,$class);

		# set a flag, to tell the comments_form tag that it doesn't have to show a preview
		$has_comments_preview = true;

		return $out;
	}

// -------------------------------------------------------------
	function if_comments_preview($atts, $thing)
	{
		return parse(EvalElse($thing, ps('preview') && checkCommentsAllowed(gps('parentid')) ));
	}

// -------------------------------------------------------------
	function comment_permlink($atts,$thing)
	{
		global $thisarticle, $thiscomment;

		assert_article();
		assert_comment();

		extract($thiscomment);
		extract(lAtts(array(
			'anchor' => empty($thiscomment['has_anchor_tag']),
		),$atts));

		$dlink = permlinkurl($thisarticle).'#c'.$discussid;

		$thing = parse($thing);

		$name = ($anchor ? ' id="c'.$discussid.'"' : '');

		return tag($thing,'a',' href="'.$dlink.'"'.$name);
	}

// -------------------------------------------------------------
	function comment_id($atts)
	{
		global $thiscomment;

		assert_comment();

		return $thiscomment['discussid'];
	}

// -------------------------------------------------------------

	function comment_name($atts)
	{
		global $thiscomment, $prefs;

		assert_comment();

		extract($prefs);
		extract($thiscomment);

		extract(lAtts(array(
			'link' => 1,
		), $atts));

		if ($link)
		{
			$web = str_replace('http://', '', $web);
			$nofollow = (@$comment_nofollow ? ' rel="nofollow"' : '');

			if ($web)
			{
				return '<a href="http://'.$web.'"'.$nofollow.'>'.$name.'</a>';
			}

			if ($email && !$never_display_email)
			{
				return '<a href="'.eE('mailto:'.$email).'"'.$nofollow.'>'.$name.'</a>';
			}
		}

		return $name;
	}

// -------------------------------------------------------------
	function comment_email($atts)
	{
		global $thiscomment;

		assert_comment();

		return $thiscomment['email'];
	}

// -------------------------------------------------------------
	function comment_web($atts)
	{
		global $thiscomment;
		assert_comment();

		return $thiscomment['web'];
	}

// -------------------------------------------------------------

	function comment_time($atts)
	{
		global $thiscomment, $comments_dateformat;

		assert_comment();

		extract(lAtts(array(
			'format' => $comments_dateformat,
			'gmt'    => '',
			'lang'   => '',
		), $atts));

		return safe_strftime($format, $thiscomment['time'], $gmt, $lang);
	}

// -------------------------------------------------------------
	function comment_message($atts)
	{
		global $thiscomment;
		assert_comment();

		return $thiscomment['message'];
	}

// -------------------------------------------------------------
	function comment_anchor($atts)
	{
		global $thiscomment;

		assert_comment();

		$thiscomment['has_anchor_tag'] = 1;
		return '<a id="c'.$thiscomment['discussid'].'"></a>';
	}

// -------------------------------------------------------------

	function author($atts)
	{
		global $thisarticle, $s;

		assert_article();

		extract(lAtts(array(
			'link'         => 1,
			'section'      => '',
			'this_section' => 0
		), $atts));

		$author_name = get_author_name($thisarticle['authorid']);

		$section = ($this_section) ? ( $s == 'default' ? '' : $s ) : $section;

		return ($link) ?
			href($author_name, pagelinkurl(array('s' => $section, 'author' => $author_name))) :
			$author_name;
	}

// -------------------------------------------------------------
	function if_author($atts, $thing)
	{
		global $author;

		extract(lAtts(array(
			'name' => '',
		), $atts));

		if ($name)
		{
			return parse(EvalElse($thing, in_list($author, $name)));
		}

		return parse(EvalElse($thing, !empty($author)));
	}

// -------------------------------------------------------------

	function if_article_author($atts, $thing)
	{
		global $thisarticle;

		assert_article();

		extract(lAtts(array(
			'name' => '',
		), $atts));

		$author = $thisarticle['authorid'];

		if ($name)
		{
			return parse(EvalElse($thing, in_list($author, $name)));
		}

		return parse(EvalElse($thing, !empty($author)));
	}

// -------------------------------------------------------------

	function body($atts)
	{
		global $thisarticle, $is_article_body;
		assert_article();

		$is_article_body = 1;
		$out = parse($thisarticle['body']);
		$is_article_body = 0;
		return $out;
	}

// -------------------------------------------------------------
	function title($atts)
	{
		global $thisarticle, $prefs;
		assert_article();
		extract(lAtts(array(
			'no_widow' => @$prefs['title_no_widow'],
		), $atts));

		$t = escape_title($thisarticle['title']);
		if ($no_widow)
			$t = noWidow($t);
		return $t;
	}

// -------------------------------------------------------------
	function excerpt($atts)
	{
		global $thisarticle, $is_article_body;
		assert_article();

		$is_article_body = 1;
		$out = parse($thisarticle['excerpt']);
		$is_article_body = 0;
		return $out;
	}

// -------------------------------------------------------------

	function category1($atts, $thing = '')
	{
		global $thisarticle, $s, $permlink_mode;

		assert_article();

		extract(lAtts(array(
			'class'        => '',
			'link'         => 1,
			'title'        => 1,
			'section'      => '',
			'this_section' => 0,
			'wraptag'      => '',
		), $atts));

		if ($thisarticle['category1'])
		{
			$section = ($this_section) ? ( $s == 'default' ? '' : $s ) : $section;
			$category = $thisarticle['category1'];

			$label = ($title) ? fetch_category_title($category) : $category;

			if ($thing)
			{
				$out = '<a'.
					($permlink_mode != 'messy' ? ' rel="tag"' : '').
					( ($class and !$wraptag) ? ' class="'.$class.'"' : '' ).
					' href="'.pagelinkurl(array('s' => $section, 'c' => $category)).'"'.
					($title ? ' title="'.$label.'"' : '').
					'>'.parse($thing).'</a>';
			}

			elseif ($link)
			{
				$out = '<a'.
					($permlink_mode != 'messy' ? ' rel="tag"' : '').
					' href="'.pagelinkurl(array('s' => $section, 'c' => $category)).'">'.$label.'</a>';
			}

			else
			{
				$out = $label;
			}

			return doTag($out, $wraptag, $class);
		}
	}

// -------------------------------------------------------------

	function category2($atts, $thing = '')
	{
		global $thisarticle, $s, $permlink_mode;

		assert_article();

		extract(lAtts(array(
			'class'        => '',
			'link'         => 1,
			'title'        => 1,
			'section'      => '',
			'this_section' => 0,
			'wraptag'      => '',
		), $atts));

		if ($thisarticle['category2'])
		{
			$section = ($this_section) ? ( $s == 'default' ? '' : $s ) : $section;
			$category = $thisarticle['category2'];

			$label = ($title) ? fetch_category_title($category) : $category;

			if ($thing)
			{
				$out = '<a'.
					($permlink_mode != 'messy' ? ' rel="tag"' : '').
					( ($class and !$wraptag) ? ' class="'.$class.'"' : '' ).
					' href="'.pagelinkurl(array('s' => $section, 'c' => $category)).'"'.
					($title ? ' title="'.$label.'"' : '').
					'>'.parse($thing).'</a>';
			}

			elseif ($link)
			{
				$out = '<a'.
					($permlink_mode != 'messy' ? ' rel="tag"' : '').
					' href="'.pagelinkurl(array('s' => $section, 'c' => $category)).'">'.$label.'</a>';
			}

			else
			{
				$out = $label;
			}

			return doTag($out, $wraptag, $class);
		}
	}

// -------------------------------------------------------------

	function category($atts, $thing = '')
	{
		global $s, $c;

		extract(lAtts(array(
			'class'        => '',
			'link'         => 1,
			'name'         => '',
			'section'      => '',
			'this_section' => 0,
			'title'        => 1,
			'type'         => 'article',
			'wraptag'      => '',
		), $atts));

		$category = ($name) ? $name : $c;

		if ($category)
		{
			$section = ($this_section) ? ( $s == 'default' ? '' : $s ) : $section;
			$label = ($title) ? fetch_category_title($category, $type) : $category;

			if ($thing)
			{
				$out = '<a href="'.pagelinkurl(array('s' => $section, 'c' => $category,)).'"'.
					( ($class and !$wraptag) ? ' class="'.$class.'"' : '' ).
					($title ? ' title="'.$label.'"' : '').
					'>'.parse($thing).'</a>';
			}

			elseif ($link)
			{
				$out = href($label,
					pagelinkurl(array('s' => $section, 'c' => $category))
				);
			}

			else
			{
				$out = $label;
			}

			return doTag($out, $wraptag, $class);
		}
	}

// -------------------------------------------------------------

	function section($atts, $thing = '')
	{
		global $thisarticle, $s;

		extract(lAtts(array(
			'class'   => '',
			'link'		=> 1,
			'name'		=> '',
			'title'		=> 1,
			'wraptag' => '',
		), $atts));

		if ($name)
		{
			$sec = $name;
		}

		elseif (!empty($thisarticle['section']))
		{
			$sec = $thisarticle['section'];
		}

		else
		{
			$sec = $s;
		}

		if ($sec)
		{
			$label = ($title) ? fetch_section_title($sec) : $sec;

			if ($thing)
			{
				$out = '<a href="'.pagelinkurl(array('s' => $sec)).'"'.
					( ($class and !$wraptag) ? ' class="'.$class.'"' : '' ).
					($title ? ' title="'.$label.'"' : '').
					'>'.parse($thing).'</a>';
			}

			elseif ($link)
			{
				$out = href($label,
					pagelinkurl(array('s' => $sec))
				);
			}

			else
			{
				$out = $label;
			}

			return doTag($out, $wraptag, $class);
		}
	}

// -------------------------------------------------------------

	function if_keywords($atts, $thing = NULL)
	{
		global $thisarticle;

		assert_article();

		$condition = ($thisarticle['keywords']);

		return EvalElse($thing, $condition);
	}

// -------------------------------------------------------------
	function keywords($atts)
	{
		global $thisarticle;
		assert_article();

		return $thisarticle['keywords'];
	}

// -------------------------------------------------------------

	function if_article_image($atts, $thing = NULL)
	{
		global $thisarticle;

		assert_article();

		$condition = ($thisarticle['article_image']);

		return EvalElse($thing, $condition);
	}

// -------------------------------------------------------------

	function article_image($atts)
	{
		global $thisarticle, $img_dir;

		assert_article();

		extract(lAtts(array(
			'class'     => '',
			'escape'    => '',
			'html_id'   => '',
			'thumbnail' => 0,
			'wraptag'   => '',
		), $atts));

		if ($thisarticle['article_image'])
		{
			$image = $thisarticle['article_image'];
		}

		else
		{
			return;
		}

		if (is_numeric($image))
		{
			$rs = safe_row('*', 'txp_image', 'id = '.intval($image));

			if ($rs)
			{
				if ($thumbnail)
				{
					if ($rs['thumbnail'])
					{
						extract($rs);

						if ($escape == 'html')
						{
							$alt = escape_output($alt);
							$caption = escape_output($caption);
						}

						$out = '<img src="'.hu.$img_dir.'/'.$id.'t'.$ext.'" alt="'.$alt.'"'.
							($caption ? ' title="'.$caption.'"' : '').
							( ($html_id and !$wraptag) ? ' id="'.$html_id.'"' : '' ).
							( ($class and !$wraptag) ? ' class="'.$class.'"' : '' ).
							' />';
					}

					else
					{
						return '';
					}
				}

				else
				{
					extract($rs);

					if ($escape == 'html')
					{
						$alt = escape_output($alt);
						$caption = escape_output($caption);
					}

					$out = '<img src="'.hu.$img_dir.'/'.$id.$ext.'" width="'.$w.'" height="'.$h.'" alt="'.$alt.'"'.
						($caption ? ' title="'.$caption.'"' : '').
						( ($html_id and !$wraptag) ? ' id="'.$html_id.'"' : '' ).
						( ($class and !$wraptag) ? ' class="'.$class.'"' : '' ).
						' />';
				}
			}

			else
			{
				trigger_error(gTxt('unknown_image'));
				return;
			}
		}

		else
		{
			$out = '<img src="'.$image.'" alt=""'.
				( ($html_id and !$wraptag) ? ' id="'.$html_id.'"' : '' ).
				( ($class and !$wraptag) ? ' class="'.$class.'"' : '' ).
				' />';
		}

		return ($wraptag) ? doTag($out, $wraptag, $class, '', $html_id) : $out;
	}

// -------------------------------------------------------------

	function search_result_title($atts)
	{
		return title($atts);
	}

// -------------------------------------------------------------

	function search_result_excerpt($atts)
	{
		global $thisarticle, $pretext;

		assert_article();

		extract(lAtts(array(
			'break'   => ' &#8230;',
			'hilight' => 'strong',
			'limit'   => 5,
		), $atts));

		$q = $pretext['q'];

		$result = preg_replace('/\s+/', ' ', strip_tags(str_replace('><', '> <', $thisarticle['body'])));
		preg_match_all("/\b.{1,50}".preg_quote($q).".{1,50}\b/iu", $result, $concat);

		if ($concat)
		{
			for ($i = 0, $r = array(); $i < min($limit, count($concat[0])); $i++)
			{
				$r[] = trim($concat[0][$i]);
			}

			if ($r)
			{
				$concat = join($break.n, $r);
				$concat = preg_replace('/^[^>]+>/U', '', $concat);
				$concat = preg_replace('/('.preg_quote($q).')/i', "<$hilight>$1</$hilight>", $concat);

				return trim($break.$concat.$break);
			}
		}
	}

// -------------------------------------------------------------

	function search_result_permlink($atts, $thing = NULL)
	{
		return permlink($atts, $thing);
	}

// -------------------------------------------------------------
	function search_result_date($atts)
	{
		assert_article();
		return posted($atts);
	}

// -------------------------------------------------------------
	function search_result_count($atts)
	{
		global $thispage;
		$t = @$thispage['grand_total'];
		extract(lAtts(array(
			'text'     => ($t == 1 ? gTxt('article_found') : gTxt('articles_found')),
		),$atts));

		return $t . ($text ? ' ' . $text : '');
	}

// -------------------------------------------------------------
	function image_index($atts)
	{
		global $s,$c,$p,$img_dir,$path_to_site;
		extract(lAtts(array(
			'label'    => '',
			'break'    => br,
			'wraptag'  => '',
			'class'    => __FUNCTION__,
			'labeltag' => '',
			'c' => $c, // Keep the option to override categories due to backward compatiblity
			'sort'     => 'name ASC',
		),$atts));
		$c = doSlash($c);

		$rs = safe_rows_start("*", "txp_image","category='$c' and thumbnail=1 order by ".doSlash($sort));

		if ($rs) {
			$out = array();
			while ($a = nextRow($rs)) {
				extract($a);
				$impath = $img_dir.'/'.$id.'t'.$ext;
				$imginfo = getimagesize($path_to_site.'/'.$impath);
				$dims = (!empty($imginfo[3])) ? ' '.$imginfo[3] : '';
				$url = pagelinkurl(array('c'=>$c, 's'=>$s, 'p'=>$id));
				$out[] = '<a href="'.$url.'">'.
					'<img src="'.hu.$impath.'"'.$dims.' alt="'.$alt.'" />'.'</a>';

			}
			if (count($out)) {
				return doLabel($label, $labeltag).doWrap($out, $wraptag, $break, $class);
			}
		}
		return '';
	}

// -------------------------------------------------------------
	function image_display($atts)
	{
		if (is_array($atts)) extract($atts);
		global $s,$c,$p,$img_dir;
		if($p) {
			$rs = safe_row("*", "txp_image", 'id='.intval($p).' limit 1');
			if ($rs) {
				extract($rs);
				$impath = hu.$img_dir.'/'.$id.$ext;
				return '<img src="'.$impath.
					'" style="height:'.$h.'px;width:'.$w.'px" alt="'.$alt.'" />';
			}
		}
	}

// -------------------------------------------------------------
	function if_comments($atts, $thing)
	{
		global $thisarticle;
		assert_article();

		return parse(EvalElse($thing, ($thisarticle['comments_count'] > 0)));
	}

// -------------------------------------------------------------
	function if_comments_allowed($atts, $thing)
	{
		global $thisarticle;
		assert_article();

		return parse(EvalElse($thing, checkCommentsAllowed($thisarticle['thisid'])));
	}

// -------------------------------------------------------------
	function if_comments_disallowed($atts, $thing)
	{
		global $thisarticle;
		assert_article();

		return parse(EvalElse($thing, !checkCommentsAllowed($thisarticle['thisid'])));
	}

// -------------------------------------------------------------
	function if_individual_article($atts, $thing)
	{
		global $is_article_list;
		return parse(EvalElse($thing, ($is_article_list == false)));
	}

// -------------------------------------------------------------
	function if_article_list($atts, $thing)
	{
		global $is_article_list;
		return parse(EvalElse($thing, ($is_article_list == true)));
	}

// -------------------------------------------------------------
	function meta_keywords()
	{
		global $id_keywords;
		return ($id_keywords)
		?	'<meta name="keywords" content="'.$id_keywords.'" />'
		:	'';
	}

// -------------------------------------------------------------
	function meta_author()
	{
		global $id_author;
		return ($id_author)
		?	'<meta name="author" content="'.$id_author.'" />'
		:	'';
	}

// -------------------------------------------------------------

	function permlink($atts, $thing = NULL)
	{
		global $thisarticle;

		extract(lAtts(array(
			'class' => '',
			'id'		=> '',
			'title' => ''
		), $atts));

		if (!$id)
		{
			assert_article();
		}

		$url = ($id) ? permlinkurl_id($id) : permlinkurl($thisarticle);

		if ($url)
		{
			if ($thing === NULL)
			{
				return $url;
			}

			return tag(parse($thing), 'a', ' rel="bookmark" href="'.$url.'"'.
				($title ? ' title="'.$title.'"' : '').
				($class ? ' class="'.$class.'"' : '')
			);
		}
	}


// -------------------------------------------------------------
	function lang($atts)
	{
		return LANG;
	}

// -------------------------------------------------------------

	function breadcrumb($atts)
	{
		global $pretext,$sitename;

		extract(lAtts(array(
			'wraptag' => 'p',
			'sep' => '&#160;&#187;&#160;',
			'link' => 1,
			'label' => $sitename,
			'title' => 1,
			'class' => '',
			'linkclass' => 'noline',
		),$atts));

		if ($link) $label = doTag($label,'a',$linkclass,' href="'.hu.'"');

		$content = array();
		extract($pretext);
		if(!empty($s) && $s!= 'default')
		{
			$section_title = ($title) ? fetch_section_title($s) : $s;
			$section_title_html = escape_title($section_title);
			$content[] = ($link)? (
					doTag($section_title_html,'a',$linkclass,' href="'.pagelinkurl(array('s'=>$s)).'"')
				):$section_title_html;
		}

		$category = empty($c)? '': $c;

		foreach (getTreePath($category, 'article') as $cat) {
			if ($cat['name'] != 'root') {
				$category_title_html = $title ? escape_title($cat['title']) : $cat['name'];
				$content[] = ($link)
					? doTag($category_title_html,'a',$linkclass,' href="'.pagelinkurl(array('c'=>$cat['name'])).'"')
					: $category_title_html;
			}
		}

		// add the label at the end, to prevent breadcrumb for home page
		if ($content)
		{
			$content = array_merge(array($label), $content);

			return doTag(join($sep, $content), $wraptag, $class);
		}
	}


//------------------------------------------------------------------------

	function if_excerpt($atts, $thing)
	{
		global $thisarticle;
		assert_article();
		# eval condition here. example for article excerpt
		$excerpt = trim($thisarticle['excerpt']);
		$condition = (!empty($excerpt))? true : false;
		return parse(EvalElse($thing, $condition));
	}

//--------------------------------------------------------------------------
// Searches use default page. This tag allows you to use different templates if searching
//--------------------------------------------------------------------------

	function if_search($atts, $thing)
	{
		global $pretext;
		return parse(EvalElse($thing, !empty($pretext['q'])));
	}

//--------------------------------------------------------------------------

	function if_search_results($atts, $thing)
	{
		global $thispage, $pretext;

		if(empty($pretext['q'])) return '';

		extract(lAtts(array(
			'min' => 0,
			'max' => 0,
		),$atts));

		$results = (int)$thispage['grand_total'];
		return parse(EvalElse($thing, $results >= $min and (!$max || $results <= $max)));
	}

//--------------------------------------------------------------------------
	function if_category($atts, $thing)
	{
		global $c;

		extract(lAtts(array(
			'name' => '',
		),$atts));

		if (trim($name)) {
			return parse(EvalElse($thing, in_list($c, $name)));
		}

		return parse(EvalElse($thing, !empty($c)));
	}

//--------------------------------------------------------------------------

	function if_article_category($atts, $thing)
	{
		global $thisarticle;

		assert_article();

		extract(lAtts(array(
			'name'   => '',
			'number' => '',
		), $atts));

		$cats = array();

		if ($number) {
			if (!empty($thisarticle['category'.$number])) {
				$cats = array($thisarticle['category'.$number]);
			}
		} else {
			if (!empty($thisarticle['category1'])) {
				$cats[] = $thisarticle['category1'];
			}

			if (!empty($thisarticle['category2'])) {
				$cats[] = $thisarticle['category2'];
			}

			$cats = array_unique($cats);
		}

		if ($name) {
			return parse(EvalElse($thing, array_intersect(do_list($name), $cats)));
		} else {
			return parse(EvalElse($thing, ($cats)));
		}
	}

//--------------------------------------------------------------------------
	function if_section($atts, $thing)
	{
		global $pretext;
		extract($pretext);

		extract(lAtts(array(
			'name' => '',
		),$atts));

		$section = ($s == 'default' ? '' : $s);

		if ($section)
			return parse(EvalElse($thing, in_list($section, $name)));
		else
			return parse(EvalElse($thing, in_list('', $name) or in_list('default', $name)));

	}

//--------------------------------------------------------------------------
	function if_article_section($atts, $thing)
	{
		global $thisarticle;
		assert_article();

		extract(lAtts(array(
			'name' => '',
		),$atts));

		$section = $thisarticle['section'];

		return parse(EvalElse($thing, in_list($section, $name)));
	}

//--------------------------------------------------------------------------
	function php($atts, $thing)
	{
		global $is_article_body, $thisarticle, $prefs;

		ob_start();
		if (empty($is_article_body)) {
			if (!empty($prefs['allow_page_php_scripting']))
				eval($thing);
			else
				trigger_error(gTxt('php_code_disabled_page'));
		}
		else {
			if (!empty($prefs['allow_article_php_scripting'])) {
				if (has_privs('article.php', $thisarticle['authorid']))
					eval($thing);
				else
					trigger_error(gTxt('php_code_forbidden_user'));
			}
			else
				trigger_error(gTxt('php_code_disabled_article'));
		}
		return ob_get_clean();
	}

//--------------------------------------------------------------------------
	function custom_field($atts)
	{
		global $thisarticle, $prefs;
		assert_article();

		extract(lAtts(array(
			'name' => @$prefs['custom_1_set'],
			'escape' => '',
			'default' => '',
		),$atts));

		$name = strtolower($name);
		if (!empty($thisarticle[$name]))
			$out = $thisarticle[$name];
		else
			$out = $default;

		return ($escape == 'html' ? escape_output($out) : $out);
	}

//--------------------------------------------------------------------------
	function if_custom_field($atts, $thing)
	{
		global $thisarticle, $prefs;
		assert_article();

		extract(lAtts(array(
			'like' => 0,
			'name' => @$prefs['custom_1_set'],
			'val' => NULL,
		),$atts));

		$name = strtolower($name);
		if ($val !== NULL)
			$cond =  preg_match( ($like ? "/$val/i" : "/^$val\$/i"), @$thisarticle[$name]);
		else
			$cond = !empty($thisarticle[$name]);

		return parse(EvalElse($thing, $cond));
	}

// -------------------------------------------------------------
	function site_url($atts)
	{
		return hu;
	}

// -------------------------------------------------------------
	function img($atts)
	{
		extract(lAtts(array(
			'src' => '',
		), $atts));

		$img = rtrim(hu, '/').'/'.ltrim($src, '/');

		$out = '<img src="'.$img.'" />';

		return $out;
	}

// -------------------------------------------------------------
	function error_message($atts)
	{
		return @$GLOBALS['txp_error_message'];
	}

// -------------------------------------------------------------
	function error_status($atts)
	{
		return @$GLOBALS['txp_error_status'];
	}

// -------------------------------------------------------------
	function if_status($atts, $thing='')
	{
		global $pretext;

		extract(lAtts(array(
			'status' => '200',
		), $atts));

		$page_status = !empty($GLOBALS['txp_error_code'])
			? $GLOBALS['txp_error_code']
			: $pretext['status'];

		return parse(EvalElse($thing, $status == $page_status));
	}

// -------------------------------------------------------------
	function page_url($atts)
	{
		global $pretext;

		extract(lAtts(array(
			'type' => 'request_uri',
		), $atts));

		return @htmlspecialchars($pretext[$type]);
	}

// -------------------------------------------------------------
	function if_different($atts, $thing)
	{
		static $last;

		$key = md5($thing);

		$cond = EvalElse($thing, 1);

		$out = parse($cond);
		if (empty($last[$key]) or $out != $last[$key]) {
			return $last[$key] = $out;
		}
		else
			return parse(EvalElse($thing, 0));
	}

// -------------------------------------------------------------
	function if_first_article($atts, $thing)
	{
		global $thisarticle;
		assert_article();
		return parse(EvalElse($thing, !empty($thisarticle['is_first'])));
	}

// -------------------------------------------------------------
	function if_last_article($atts, $thing)
	{
		global $thisarticle;
		assert_article();
		return parse(EvalElse($thing, !empty($thisarticle['is_last'])));
	}

// -------------------------------------------------------------
	function if_plugin($atts, $thing)
	{
		global $plugins, $plugins_ver;
		extract(lAtts(array(
			'name'    => '',
			'ver'     => '',
		),$atts));

		return parse(EvalElse($thing, @in_array($name, $plugins) and (!$ver or version_compare($plugins_ver[$name], $ver) >= 0)));
	}

// -------------------------------------------------------------
	function hide($atts, $thing)
	{
		return '';
	}

?>
