<?php
/*
	This is Textpattern
	Copyright 2005 by Dean Allen 
 	All rights reserved.

	Use of this software indicates acceptance of the Textpattern license agreement 

$HeadURL$
$LastChangedRevision$

*/

	if (!defined('txpinterface')) die('txpinterface is undefined.');

	global $statuses;
	$statuses = array(
		1 => gTxt('draft'),
		2 => gTxt('hidden'),
		3 => gTxt('pending'),
		4 => gTxt('live'),
		5 => gTxt('sticky'),
	);

	if ($event=='list') {
		require_privs('article');

		if(!$step or !in_array($step, array('list_change_pageby','list_list','list_multi_edit','list_list'))){
			list_list();
		} else $step();
	}

//--------------------------------------------------------------

	function list_list($message = '', $post = '')
	{
		global $statuses, $step;

		pagetop(gTxt('tab_list'), $message);

		extract(get_prefs());

		extract(gpsa(array('page', 'sort', 'dir', 'crit', 'search_method')));

		$sesutats = array_flip($statuses);

		$dir = ($dir == 'desc') ? 'desc' : 'asc';

		switch ($sort)
		{
			case 'id':
				$sort_sql = 'ID '.$dir;
			break;

			case 'posted':
				$sort_sql = 'Posted '.$dir;
			break;

			case 'title':
				$sort_sql = 'Title '.$dir.', Posted desc';
			break;

			case 'section':
				$sort_sql = 'Section '.$dir.', Posted desc';
			break;

			case 'category1':
				$sort_sql = 'Category1 '.$dir.', Posted desc';
			break;

			case 'category2':
				$sort_sql = 'Category2 '.$dir.', Posted desc';
			break;

			case 'status':
				$sort_sql = 'Status '.$dir.', Posted desc';
			break;

			case 'author':
				$sort_sql = 'AuthorID '.$dir.', Posted desc';
			break;

			case 'comments':
				$sort_sql = 'comments_count '.$dir.', Posted desc';
			break;

			default:
				$dir = 'desc';
				$sort_sql = 'Posted '.$dir;
			break;
		}

		$switch_dir = ($dir == 'desc') ? 'asc' : 'desc';

		$criteria = 1;

		if ($crit or $search_method)
		{
			$crit_escaped = doSlash($crit);

			$critsql = array(
				'id'         => "ID = '$crit_escaped'",
				'title_body' => "Title rlike '$crit_escaped' or Body rlike '$crit_escaped'",
				'section'		 => "Section rlike '$crit_escaped'",
				'categories' => "Category1 rlike '$crit_escaped' or Category2 rlike '$crit_escaped'",
				'status'		 => "Status = '".(@$sesutats[gTxt($crit_escaped)])."'",
				'author'		 => "AuthorID rlike '$crit_escaped'",
			);

			if (array_key_exists($search_method, $critsql))
			{
				$criteria = $critsql[$search_method];
				$limit = 500;
			}

			else
			{
				$search_method = '';
			}
		}

		$total = safe_count('textpattern', "$criteria");

		if ($total < 1)
		{
			if ($criteria != 1)
			{
				echo n.list_search_form($crit, $search_method).
					n.graf(gTxt('no_results_found'), ' style="text-align: center;"');
			}

			else
			{
				echo graf(gTxt('no_articles_recorded'), ' style="text-align: center;"');
			}

			return;
		}

		$limit = max(@$article_list_pageby, 15);

		list($page, $offset, $numPages) = pager($total, $limit, $page);

		echo n.list_search_form($crit, $search_method);

		$rs = safe_rows_start('*, unix_timestamp(Posted) as posted', 'textpattern',
			"$criteria order by $sort_sql limit $offset, $limit"
		);

		if ($rs)
		{
			$total_comments = array();

			// fetch true comment count, not the public comment count
			// maybe we should have another row in the db?
			$rs2 = safe_rows_start('parentid, count(*) as num', 'txp_discuss', "1 group by parentid order by parentid");

			if ($rs2)
			{
				while ($a = nextRow($rs2))
				{
					$pid = $a['parentid'];
					$num = $a['num'];
	
					$total_comments[$pid] = $num;
				}
			}

			echo n.n.'<form name="longform" method="post" action="index.php" onsubmit="return verify(\''.gTxt('are_you_sure').'\')">'.

				n.startTable('list').
				n.tr(
					n.column_head('ID', 'id', 'list', true, $switch_dir, $crit, $search_method).
					column_head('posted', 'posted', 'list', true, $switch_dir, $crit, $search_method).
					column_head('title', 'title', 'list', true, $switch_dir, $crit, $search_method).
					column_head('section', 'section', 'list', true, $switch_dir, $crit, $search_method).
					column_head('category1', 'category1', 'list', true, $switch_dir, $crit, $search_method).
					column_head('category2', 'category2', 'list', true, $switch_dir, $crit, $search_method).
					column_head('status', 'status', 'list', true, $switch_dir, $crit, $search_method).
					column_head('author', 'author', 'list', true, $switch_dir, $crit, $search_method).
					column_head('comments', 'comments', 'list', true, $switch_dir, $crit, $search_method).
					hCell()
				);

			include_once txpath.'/publish/taghandlers.php';

			while ($a = nextRow($rs))
			{
				extract($a);

				if (empty($Title))
				{
					$Title = '<em>'.eLink('article', 'edit', 'ID', $ID, gTxt('untitled')).'</em>';
				}

				else
				{
					$Title = eLink('article', 'edit', 'ID', $ID, $Title);
				}

				$Category1 = ($Category1) ? '<span title="'.fetch_category_title($Category1).'">'.$Category1.'</span>' : '';
				$Category2 = ($Category2) ? '<span title="'.fetch_category_title($Category2).'">'.$Category2.'</span>' : '';

				$manage = n.'<ul>'.
						n.t.'<li>'.eLink('article', 'edit', 'ID', $ID, gTxt('edit')).'</li>'.
						( ($Status == 4 or $Status == 5) ? n.t.'<li><a href="'.permlinkurl($a).'">'.gTxt('view').'</a></li>' : '' ).
						n.'</ul>';

				$Status = !empty($Status) ? $statuses[$Status] : '';

				$comments = gTxt('none');

				if (isset($total_comments[$ID]) and $total_comments[$ID] > 0)
				{
					$comments = href(gTxt('manage'), 'index.php?event=discuss'.a.'step=list'.a.'search_method=parent'.a.'crit='.$ID).
						' ('.$total_comments[$ID].')';
				}

				$comment_status = ($Annotate) ? gTxt('on') : gTxt('off');

				if ($comments_disabled_after)
				{
					$lifespan = $comments_disabled_after * 86400;
					$time_since = time() - $posted;

					if ($time_since > $lifespan)
					{
						$comment_status = gTxt('expired');
					}
				}

				$comments = n.'<ul>'.
					n.t.'<li>'.( $Annotate ? gTxt('on') : gTxt('off') ).'</li>'.
					n.t.'<li>'.$comments.'</li>'.
					n.'</ul>';

				echo n.n.tr(

					n.td($ID.$manage, 40).

					td(
						safe_strftime('%d %b %Y %I:%M %p', $posted)
					, 75).

					td($Title, 175).

					td(
						'<span title="'.fetch_section_title($Section).'">'.$Section.'</span>'
					, 75).

					td($Category1, 100).
					td($Category2, 100).
					td($Status, 50).

					td(
						'<span title="'.get_author_name($AuthorID).'">'.$AuthorID.'</span>'
					, 75).

					td($comments).

					td(
						fInput('checkbox', 'selected[]', $ID, '', '', '', '', '', $ID)
					)
				);
			}

			echo n.n.tr(
				tda(
					select_buttons().
					list_multiedit_form($page, $sort, $dir, $crit, $search_method)
				,' colspan="11" style="text-align: right; border: none;"')
			).

			n.endTable().
			n.'</form>'.

			n.nav_form('list', $page, $numPages, $sort, $dir, $crit, $search_method).

			n.pageby_form('list', $article_list_pageby);
		}
	}

// -------------------------------------------------------------
	function list_change_pageby() 
	{
		event_change_pageby('article');
		list_list();
	}

// -------------------------------------------------------------

	function list_search_form($crit, $method)
	{
		$methods =	array(
			'id'				 => gTxt('ID'),
			'title_body' => gTxt('title_body'),
			'section'		 => gTxt('section'),
			'categories' => gTxt('categories'),
			'status'		 => gTxt('status'),
			'author'		 => gTxt('author')
		);

		return search_form('list', 'list', $crit, $methods, $method, 'title_body');
	}

// -------------------------------------------------------------

	function list_multiedit_form($page, $sort, $dir, $crit, $search_method)
	{
		$methods = array(
			'changesection'   => gTxt('changesection'),
			'changecategory1' => gTxt('changecategory1'),
			'changecategory2' => gTxt('changecategory2'),
			'changestatus'    => gTxt('changestatus'),
			'changecomments'  => gTxt('changecomments'),
			'changeauthor'    => gTxt('changeauthor'),
			'delete'          => gTxt('delete'),
		);

		return event_multiedit_form('list', $methods, $page, $sort, $dir, $crit, $search_method);
	}

// -------------------------------------------------------------

	function list_multi_edit()
	{
		global $txp_user;

		$selected = ps('selected');

		if (!$selected)
		{
			return list_list();
		}

		$method = ps('edit_method');
		$changed = false;

		if ($method == 'delete')
		{
			if (!has_privs('article.delete'))
			{
				$allowed = array();

				if (has_privs('article.delete.own'))
				{
					foreach ($selected as $id)
					{
						$id = intval($id);
						$author = safe_field('AuthorID', 'textpattern', "ID = '$id'");

						if ($author == $txp_user)
						{
							$allowed[] = $id;
						}
					}
				}

				$selected = $allowed;
			}

			foreach ($selected as $id)
			{
				$id = intval($id);

				if (safe_delete('textpattern', "ID = '".$id."'"))
				{
					$ids[] = $id;
				}
			}

			$changed = join(', ', $ids);
		}

		else
		{
			switch ($method)
			{
				// change section
				case 'changesection':
					$key = 'Section';
					$val = ps('Section');
				break;

			// change category1
				case 'changecategory1':
					$key = 'Category1';
					$val = ps('Category1');
				break;

			// change category2
				case 'changecategory2':
					$key = 'Category2';
					$val = ps('Category2');
				break;

			// change status
				case 'changestatus':
					$key = 'Status';
					$val = ps('Status');
				break;

			// change comments
				case 'changecomments':
					$key = 'Annotate';
					$val = ps('Annotate');
				break;

			// change author
				case 'changeauthor':
					if (has_privs('article.edit'))
					{
						$key = 'AuthorID';
						$val = ps('AuthorID');
					}

					else
					{
						$selected = array();
					}
				break;
			}

			if ($selected)
			{
				foreach ($selected as $id)
				{
					$id = intval($id);

					if (safe_update('textpattern', "$key = '".doSlash($val)."'", "ID = '$id'"))
					{
						$ids[] = $id;
					}
				}

				$changed = join(', ', $ids);
			}
		}

		if ($changed)
		{
			return list_list(
				messenger('article', $changed, (($method == 'delete') ? 'deleted' : 'modified' ))
			);
		}

		return list_list();
	}

?>
