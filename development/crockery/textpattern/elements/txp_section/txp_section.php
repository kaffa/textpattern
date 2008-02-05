<?php

/*

$HeadURL$
$LastChangedRevision$

*/

if (!defined('txpinterface')) die('txpinterface is undefined.');

include_once(txpath.'/lib/txplib_controller.php');
include_once(txpath.'/lib/txplib_view.php');

register_controller('SectionController', 'section');

class SectionController extends ZemAdminController
{
	var $area = 'section';
	var $event = 'section';
	var $caption = 'sections';
	var $default_step = 'list';
	var $context = array();

	function SectionController()
	{
		parent::ZemAdminController();
		$this->context = gpsa(array('page', 'sort', 'dir', 'crit', 'search_method'));
		// @todo: sensible standard list view
		if(empty($this->context['sort'])) $this->context['sort'] = 'name';
		if($this->context['dir'] != 'desc') $this->context['dir'] = 'asc';
	}

// -------------------------------------------------------------
	function list_view()
	{
		$default = safe_row('page, css', 'txp_section', "name = 'default'");

		$pages = safe_column('name', 'txp_page', "1 = 1");
		$styles = safe_column('name', 'txp_css', "1 = 1");

		echo n.n.startTable('list').

			n.n.tr(
				tda(
					n.n.hed(gTxt('section_head').sp.popHelp('section_category'), 1).

		/*
		 *  insert new section
		 */
					n.n.form(
						fInput('text', 'name', '', 'edit', '', '', 10).
						fInput('submit', '', gTxt('create'), 'smallerbox').
						eInput('section').
						sInput('insert')
					)
				, ' colspan="3"')
			).
		endTable();


		/*
		 *  edit 'default' section
			n.n.tr(
				td(gTxt('default')).

				td(
					form(
						'<table>'.

						tr(
							fLabelCell(gTxt('uses_page').':').
							td(
								selectInput('page', $pages, $default['page']).sp.popHelp('section_uses_page')
							, '', 'noline')
						).

						tr(
							fLabelCell(gTxt('uses_style').':') .
							td(
								selectInput('css', $styles, $default['css']).sp.popHelp('section_uses_css')
							, '', 'noline')
						).

						tr(
							tda(
								fInput('submit', '', gTxt('save_button'), 'smallerbox').
								eInput('section').
								sInput('save').
								hInput('name','default')
							, ' colspan="2" class="noline"')
						).

						endTable()
					)
				).

				td()
			);
		 */

		/*
		 * edit any other section
		 */
		# $rs = safe_rows_start('*', 'txp_section', "name != 'default' order by name");
		$rs = safe_rows('*', 'txp_section', "1=1 order by name");

		if($rs) {
			$v = new SectionListView($rs, $this);
			echo $v->render();
		}
return;

		if ($rs)
		{
			while ($a = nextRow($rs))
			{
				extract($a);

				echo n.n.tr(
					n.td($name).

					n.td(
						form(
							'<table>'.

							n.n.tr(
								fLabelCell(gTxt('section_name').':').
								fInputCell('name', $name, 1, 20)
							).

							n.n.tr(
								fLabelCell(gTxt('section_longtitle').':').
								fInputCell('title', $title, 1, 20)
							).

							n.n.tr(
								fLabelCell(gTxt('uses_page').':').
								td(
									selectInput('page', $pages, $page).sp.popHelp('section_uses_page')
								, '', 'noline')
							).

							n.n.tr(
								fLabelCell(gTxt('uses_style').':').
								td(
									selectInput('css', $styles, $css).sp.popHelp('section_uses_css')
								, '', 'noline')
							).

							n.n.tr(
								fLabelCell(gTxt('parent_section').':').
								td(
									$this->adopters_dropdown($id, $parent).sp.popHelp('section_parent_section')
								, '', 'noline')
							).

							n.n.tr(
								fLabelCell(gTxt('selected_by_default').'?').
								td(
									yesnoradio('is_default', $is_default, '', $name).sp.popHelp('section_is_default')
								, '', 'noline')
							).

							n.n.tr(
								fLabelCell(gTxt('on_front_page').'?').
								td(
									yesnoradio('on_frontpage', $on_frontpage, '', $name).sp.popHelp('section_on_frontpage')
								, '', 'noline')
							).

							n.n.tr(
								fLabelCell(gTxt('syndicate').'?') .
								td(
									yesnoradio('in_rss', $in_rss, '', $name).sp.popHelp('section_syndicate')
								, '', 'noline')
							).

							n.n.tr(
								fLabelCell(gTxt('include_in_search').'?').
								td(
									yesnoradio('searchable', $searchable, '', $name).sp.popHelp('section_searchable')
								, '', 'noline')
							).

							n.n.tr(
								tda(
									fInput('submit', '', gTxt('save_button'), 'smallerbox').
									eInput('section').
									sInput('save').
									hInput('old_name', $name)
								, ' colspan="2" class="noline"')
							).

							endTable()
						)
					).

					td(
						dLink('section', 'delete', 'name', $name, '', 'type', 'section')
					)
				);
			}
		}

		echo n.n.endTable();
	}

//-------------------------------------------------------------
	function insert_post()
	{
		global $txpcfg;
		$name = doSlash(ps('name'));

		// prevent non url chars on section names
		include_once txpath.'/lib/classTextile.php';
		$textile = new Textile();
		$title = $textile->TextileThis($name,1);
		$name = dumbDown($textile->TextileThis(trim(doSlash($name)),1));
		$name = preg_replace("/[^[:alnum:]\-_]/", "", str_replace(" ","-",$name));

		// prevent duplicate section names
		// @todo: lose this for nested sections when parents differ
		$chk = fetch('name','txp_section','name',$name);

		if (!$chk) {
			if ($name) {
				$rs = safe_insert(
				   "txp_section",
				   "name         = '$name',
					title        = '$title',
					page         = 'default',
					css          = 'default',
					is_default   = 0,
					in_rss       = 1,
					on_frontpage = 1"
				);

				if ($rs) {
					$message = gTxt('section_created', array('{name}' => $name));
					$this->_message($message);
					$this->_set_view('list');
				} else {
					$message = gTxt('section_not_created', array('{name}' => $name));
					$this->_error($message);
					$this->_set_view('list'); // @todo: stay in detail view
				}
			} else {
				$this->_set_view('list');
			}
		} else {
			$message = gTxt('section_name_already_exists', array('{name}' => $name));
			$this->_error($message);
			$this->_set_view('list');
		}
	}

//-------------------------------------------------------------

	function save_post()
	{
		global $txpcfg;
		extract(doSlash(psa(array('page','css','old_name'))));
		extract(psa(array('name', 'title')));
		$parent = $this->psi('parent', 0);
		if (empty($title)) {
			$title = $name;
		}
		// Prevent non url chars on section names
		include_once txpath.'/lib/classTextile.php';

		$textile = new Textile();
		$title = doSlash($textile->TextileThis($title,1));
		$name  = doSlash(sanitizeForUrl($name));

		// rename existing section
		if ($old_name && (strtolower($name) != strtolower($old_name))) {
			if (safe_field('name', 'txp_section', "name='$name'")) {
				$message = gTxt('section_name_already_exists', array('{name}' => $name));

				$this->_error($message);
				$this->_set_view('list'); // @todo: stay in detail view
				return;
			}
		}

		if ($name == 'default')	{ // @todo: default section is defined by parent == '0', not by name
			safe_update('txp_section', "page = '$page', css = '$css'", "name = 'default'");
			update_lastmod();
		} else {
			extract(array_map('assert_int',psa(array('is_default','on_frontpage','in_rss','searchable'))));
			// note this means 'selected by default' not 'default page'
			if ($is_default) {
				safe_update("txp_section", "is_default = 0", "name != '$old_name'");
			}

			safe_update('txp_section', "
				name         = '$name',
				title        = '$title',
				page         = '$page',
				css          = '$css',
				parent   	 = $parent,
				is_default   = $is_default,
				on_frontpage = $on_frontpage,
				in_rss       = $in_rss,
				searchable   = $searchable
			", "name = '$old_name'");

			safe_update('textpattern', "Section = '$name'", "Section = '$old_name'");

			update_lastmod();
		}

		$message = gTxt('section_updated', array('{name}' => $name));
		$this->_message($message);
		$this->_set_view('list');
	}

// -------------------------------------------------------------

	function delete_post()
	{
		$name = ps('name');

		safe_delete('txp_section', "name = '$name'");

		$message = gTxt('section_deleted', array('{name}' => $name));

		$this->_message($message);
		$this->_set_view('list');
	}

// -------------------------------------------------------------

	function adopters($child_id)
	{
		static $adopters;
		if(empty($adopters)) {
			$adopters = safe_rows('name, id', 'txp_section','1=1');
		}

		// do not attempt to adopt yourself, child!
		foreach($adopters as $a) {
			if($a['id'] != $child_id) {
				$out[$a['id']] = $a['name'];
			}
		}

		// @todo: prevent circular section graphs
		return($out);
	}

// -------------------------------------------------------------

	function adopters_dropdown($child_id, $parent_id=0)
	{
		return selectInput('parent', SectionController::adopters($child_id), $parent_id);
	}
} // SectionController

class SectionListView extends TxpTableView
{
	var $controller = NULL;

	function SectionListView(&$rows, $controller, $caption='', $edit_actions=array())
	{
		parent::TxpTableView($rows, $caption, $edit_actions);
		$this->controller = $controller;
		$this->pages = safe_column('name', 'txp_page', "1 = 1");
		$this->styles = safe_column('name', 'txp_css', "1 = 1");
	}

	function head($cols)
	{
		if (!$this->controller) return;
		extract($this->controller->context);

		$switch_dir = ($dir == 'asc') ? 'desc' : 'asc';
		$e = $this->controller->event;
		return
			'<col class="col-id" />'.n.
			'<col class="col-name" />'.n.
			'<col class="col-title" />'.n.
			'<col class="col-page" />'.n.
			'<col class="col-css" />'.n.
			'<col class="col-parent" />'.n.
			'<col class="col-on_front_page" />'.n.
			'<col class="col-in_rss" />'.n.
			'<col class="col-delete" />'.n.
			n.'<thead>'.tr(
			column_head('ID', 'id', $e, true, $switch_dir, $crit, $search_method, ('id' == $sort) ? $dir : '').
			column_head('section_name', 'name', $e, true, $switch_dir, $crit, $search_method, ('name' == $sort) ? $dir : '').
			column_head('section_longtitle', 'title', $e, true, $switch_dir, $crit, $search_method, ('title' == $sort) ? $dir : '').
			column_head('uses_page', 'page', $e, true, $switch_dir, $crit, $search_method, ('page' == $sort) ? $dir : '').
			column_head('uses_style', 'css', $e, true, $switch_dir, $crit, $search_method, ('css' == $sort) ? $dir : '').
			column_head('parent_section', 'parent', $e, true, $switch_dir, $crit, $search_method, ('parent' == $sort) ? $dir : '').
			column_head('selected_by_default', 'is_default', $e, true, $switch_dir, $crit, $search_method, ('is_default' == $sort) ? $dir : '').
			column_head('on_front_page', 'on_front_page', $e, true, $switch_dir, $crit, $search_method, ('on_fornt_page' == $sort) ? $dir : '').
			column_head('syndicate', 'in_rss', $e, true, $switch_dir, $crit, $search_method, ('in_rss' == $sort) ? $dir : '').
			column_head('include_in_search', 'searchable', $e, true, $switch_dir, $crit, $search_method, ('searchable' == $sort) ? $dir : '').
			hCell()
		).'</thead>';
	}

	function row($row) {
		if (!$this->controller) return;
		extract($this->controller->context);

		extract($row);
		$event = $this->controller->event;

		$tr = array();
		$tr[] = $id;
		$tr[] = fInput('text', 'name', $name, '', '', '', 20);
		$tr[] =	fInput('text', 'title', $title, '', '', '', 20);
		$tr[] = selectInput('page', $this->pages, $page);//.sp.popHelp('section_uses_page');
		$tr[] = selectInput('css', $this->styles, $css);//.sp.popHelp('section_uses_css');
		$tr[] = SectionController::adopters_dropdown($id, $parent);//.sp.popHelp('section_parent_section');
		$tr[] = yesnoradio('is_default', $is_default, '', $name);//.sp.popHelp('section_is_default');
		$tr[] = yesnoradio('on_frontpage', $on_frontpage, '', $name);//.sp.popHelp('section_on_frontpage');
		$tr[] = yesnoradio('in_rss', $in_rss, '', $name);//.sp.popHelp('section_syndicate');
		$tr[] = yesnoradio('searchable', $searchable, '', $name);//.sp.popHelp('section_searchable');
		$tr[] = fInput('submit', '', gTxt('save_button'), 'smallerbox').
									eInput('section').
									sInput('save').
									hInput('old_name', $name);

		$tr = doWrap($tr, 'tr', 'td', 'row-'.(++$this->count % 2 ? 'odd' : 'even'));
		#echo "<pre>".htmlspecialchars($tr)."</pre><br />===<br />";
		$tr = preg_replace('/<tr.*>/', '\\0'.start_form(), $tr);
		$tr = preg_replace('/<\/tr>/', end_form().'</tr>', $tr);
		return $tr;

	}
} // SectionListView

?>