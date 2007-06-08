<?php

/*
	This is Textpattern

	Copyright 2005 by Dean Allen
	www.textpattern.com
	All rights reserved

	Use of this software indicates acceptance ofthe Textpattern license agreement 

$HeadURL: http://svn.textpattern.com/development/crockery/textpattern/include/txp_category.php $
$LastChangedRevision: 2139 $
*/

if (!defined('txpinterface')) die('txpinterface is undefined.');

include_once(txpath.'/lib/txplib_tree.php');
include_once(txpath.'/lib/txplib_controller.php');

register_controller('CategoryController', 'category');

function register_category_type($type=NULL) {
	static $types = array('article','link','image','file');

	if ($type) {
		$types[] = $type;
		$types = array_unique($types);
	}

	return $types;
}


class CategoryController extends ZemAdminController {
	var $area = 'content';
	var $event = 'category';
	var $default_step = 'list';
	var $types = array();

	function CategoryController() {
		$this->ZemAdminController();

		$this->types = register_category_type();
	}

//-------------------------------------------------------------
	function list_view()
	{
		$out = '<table cellspacing="20" align="center">'.n.
			'<tr>'.n;
		foreach ($this->types as $t)
			$out .= tdtl($this->category_list($t),' class="categories"').n;
		$out .= '</tr>'.n;
		$out .= endTable();
		return $out;
	}

//--------------------------------------------------------------

	function cat_parent_pop($input_name, $type, $id, $current_parent)
	{

		$id = assert_int($id);
		list($lft, $rgt) = array_values(safe_row('lft, rgt', 'txp_category', 'id = '.$id));

#			$rs = getTree('root', $type, "lft not between $lft and $rgt");
		$rs = tree_get('txp_category', NULL, "parent != 0 and type='".doSlash($type)."' and lft not between $lft and $rgt");

		if ($rs)
		{
			return treeSelectInput($input_name, $rs, $current_parent);
		}

		return gTxt('no_other_categories_exist');
	}


// -------------------------------------------------------------
	function multiedit_form($type, $array)
	{
		$methods = array('delete'=>gTxt('delete'));
		if ($array) {
		return
		form(
			join('',$array).
			eInput('category').sInput('multiedit').hInput('type',$type).
			small(gTxt('with_selected')).sp.selectInput('method',$methods,'',1).sp.
			fInput('submit','',gTxt('go'),'smallerbox')
			,'margin-top:1em',"verify('".gTxt('are_you_sure')."')"
		);
		} return;
	}

// -------------------------------------------------------------
	function multiedit_post()
	{
		$type = ps('type');
		$method = ps('method');
		$things = ps('selected');
		$root = tree_root_id('txp_category', "type='".doSlash($type)."'");

		if ($things) {
			foreach($things as $catid) {
				$catid = assert_int($catid);
				if ($method == 'delete') {
					if (safe_delete('txp_category',"id=$catid")) {
						safe_update('txp_category', "parent=".doSlash($root), "type='".doSlash($type)."' and parent='".doSlash($catid)."'");
						$categories[] = $catid;
					}
				}
			}
			tree_rebuild_full('txp_category', "type='".doSlash($type)."'");

			$this->_message(gTxt($type.'_categories_deleted', array('{list}' => join(', ',$categories))));
		}
		$this->_set_view('list');
	}

//Refactoring: Functions are more or less the same for all event types
// so, merge them. Addition of new event categories is easiest now.

//-------------------------------------------------------------

	function category_list($type)
	{

		$out = n.n.hed(gTxt($type.'_head').sp.popHelp($type.'_category'), 3).

			form(
				fInput('text', 'title', '', 'edit', '', '', 20).
				fInput('submit', '', gTxt('Create'), 'smallerbox').
				eInput('category').
				sInput('create').
				hInput('type', $type)
			);

		$rs = tree_get('txp_category', NULL, "parent != 0 and type='".doSlash($type)."'");

		if ($rs)
		{
			$total_count = array();

			if ($type == 'article')
			{
				$rs2 = safe_rows_start('Category1, count(*) as num', 'textpattern', "1 = 1 group by Category1");

				while ($a = nextRow($rs2))
				{
					$name = $a['Category1'];
					$num = $a['num'];

					$total_count[$name] = $num;
				}

				$rs2 = safe_rows_start('Category2, count(*) as num', 'textpattern', "1 = 1 group by Category2");

				while ($a = nextRow($rs2))
				{
					$name = $a['Category2'];
					$num = $a['num'];

					if (isset($total_count[$name]))
					{
						$total_count[$name] += $num;
					}

					else
					{
						$total_count[$name] = $num;
					}
				}
			}
			else {
				if ($type == 'link')
					$rs2 = safe_rows_start('category, count(*) as num', 'txp_link', "1 group by category");
				elseif ($type == 'image')
					$rs2 = safe_rows_start('category, count(*) as num', 'txp_image', "1 group by category");
				elseif ($type == 'file')
					$rs2 = safe_rows_start('category, count(*) as num', 'txp_file', "1 group by category");

				if (!empty($rs2)) {
					while ($a = nextRow($rs2))
					{
						$name = $a['category'];
						$num = $a['num'];
	
						$total_count[$name] = $num;
					}
				}
			}

			$items = array();

			foreach ($rs as $a)
			{
				extract($a);
				
				if ($type == 'article')
					$url = 'index.php?event=list'.a.'search_method=categories'.a.'crit='.$name;
				elseif ($type == 'link')
						$url = 'index.php?event=link'.a.'search_method=category'.a.'crit='.$name;
				elseif ($type == 'image')
						$url = 'index.php?event=image'.a.'search_method=category'.a.'crit='.$name;
				elseif ($type == 'file')
						$url = 'index.php?event=file'.a.'search_method=category'.a.'crit='.$name;
				else
					$url = '';

				$count = '';
				if ($url)
					$count = isset($total_count[$name]) ? '('.href($total_count[$name], $url).')' : '(0)';

				$items[] = graf(
					checkbox('selected[]', $id, 0).sp.str_repeat(sp.sp, $level * 2).
					eLink('category', 'edit', 'id', $id, $title, 'type', $type).sp.small($count)

				);
			}

			if ($items)
			{
				$out .= $this->multiedit_form($type, $items);
			}
		}

		else
		{
			$out .= graf(gTxt('no_categories_exist'));
		}

		return $out;
	}

//-------------------------------------------------------------

	function create_post()
	{
		global $txpcfg, $DB;

		$title = ps('title');
		$type = ps('type');

		$name = strtolower(sanitizeForUrl($title));
		$this->_set_view('list');

		if (!$name)
		{
			$this->_message(gTxt($type.'_category_invalid', array('{name}' => $name)));
			return;
		}

		$exists = safe_field('name', 'txp_category', "name = '".doSlash($name)."' and type = '".doSlash($type)."'");

		if ($exists)
		{
			$this->_message(gTxt($type.'_category_already_exists', array('{name}' => $name)));
			return;
		}

		$root = tree_root_id('txp_category', "type='".doSlash($type)."'");
		if (!$root)
			$root = safe_insert('txp_category', "name='root', title='root', type='".doSlash($type)."', parent=0");
		if (!$root) {
			$this->_error(gTxt('no_root_category', array('{type}' => $type)));
			return;
		}

		$q = safe_insert('txp_category', "name = '".doSlash($name)."', title = '".doSlash($title)."', type = '".doSlash($type)."', parent = '".doSlash($root)."'");

		if ($q)
		{
			tree_rebuild_full('txp_category', "type='".doSlash($type)."'");

			$this->_message(gTxt($type.'_category_created', array('{name}' => $name)));
		}
		else {
			$this->_error(gTxt('error_adding_category',  array('{error}' => $DB->lasterror())));
		}
	}

//-------------------------------------------------------------

	function edit_view() {

		$id = assert_int(gps('id'));
		$type = ps('type');

		$row = safe_row('*', 'txp_category', "id = $id");

		if ($row) {
			extract($row);

			echo form(
				n.startTable('edit').

				stackRows(
					n.tdcs(n.hed(gTxt($type.'_category'), 1), 2),

					fLabelCell('name').
					fInputCell('name', $name, 1, 20),

					fLabelCell('parent').
					n.td($this->cat_parent_pop('parent', $type, $id, $parent)),

					fLabelCell('title').
					fInputCell('title', $title, 1, 30),

					n.tdcs(fInput('submit', '', gTxt('save_button'), 'smallerbox'), 2)
				).

				endTable().

				n.eInput('category').
				n.sInput('edit').

				n.hInput('id', $id).
				n.hInput('old_name', $name).
				n.hInput('type', $type)
			);
		}
	}

//-------------------------------------------------------------

	function edit_post()
	{
		extract(doSlash(psa(array('name', 'old_name', 'title', 'type'))));
		$id = $this->psi('id');
		$parent = $this->ps('parent');
		if (!$parent)
			$parent = tree_root_id('txp_category', "type='".doSlash($type)."'");

		$name = sanitizeForUrl($name);

		// make sure the name is valid
		if (!$name)	{
			$this->_error(gTxt($type.'_category_invalid', array('{name}' => $name)));
			return;
		}

		// don't allow rename to clobber an existing category
		if (safe_field('id', 'txp_category', "name = '$name' and type = '$type' and id != $id")) {
			$this->_error(gTxt($type.'_category_already_exists', array('{name}' => $name)));
			return;
		}

		safe_update('txp_category', "name = '$name', parent = '$parent', title = '$title'", "id = $id");
		tree_rebuild_full('txp_category', "type='$type'");

		if ($type == 'article')
		{
			safe_update('textpattern', "Category1 = '$name'", "Category1 = '$old_name'");
			safe_update('textpattern', "Category2 = '$name'", "Category2 = '$old_name'");
		}
		elseif ($type == 'link')
			safe_update('txp_link', "category = '$name'", "category = '$old_name'");
		elseif ($type == 'image')
			safe_update('txp_image', "category = '$name'", "category = '$old_name'");
		elseif ($type == 'file')
			safe_update('txp_file', "category = '$name'", "category = '$old_name'");

		// show a success message and switch back to the list view
		$this->_message(gTxt($type.'_category_updated', array('{name}' => doStrip($name))));
		$this->_set_view('list');
	}

}

?>
