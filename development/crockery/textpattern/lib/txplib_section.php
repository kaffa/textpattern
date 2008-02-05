<?php

/*
Copyright 2006 Alex Shiels http://thresholdstate.com/

$HeadURL$
$LastChangedRevision$
*/

// -------------------------------------------------------------
	function section_add($parent, $name, $atts)
	{
		$rec = lAtts(array(
			'path' => '',
			'parent' => $parent,
			'name' => '',
			'title' => $name,
			'inherit' => 0,
			'page' => 'default',
			'css' => 'default',
			'in_rss' => '1',
			'on_frontpage' => '1',
			'searchable' => '1',
		), $atts);

		$default = section_default();

		if (empty($rec['parent']))
			$rec['parent'] = $default['id'];
		if (empty($rec['name']))
			$rec['name'] = dumbDown($rec['title']);

		if ($rec['inherit']) {
			// find the closest ancestor
			// we do this at insert to save time when fetching pages
			$ancestor = section_inherit($parent);

			$rec['page']         = $ancestor['page'];
			$rec['css']          = $ancestor['css'];
			$rec['in_rss']       = $ancestor['in_rss'];
			$rec['on_frontpage'] = $ancestor['on_frontpage'];
			$rec['searchable']   = $ancestor['searchable'];
		}

		if ($parent)
			list($lft, $rgt) = tree_insert_space('txp_section', $parent);
		else
			$lft = $rgt = 0;

		$rec['lft'] = $lft;
		$rec['rgt'] = $rgt;

		$res = safe_insert_rec('txp_section', doSlash($rec));

		return $res;
	}

// -------------------------------------------------------------
// delete a single section (keeping children intact)
	function section_del($id)
	{
		$s = safe_row('*', 'txp_section', "id='".doSlash($id)."'");
		// can't delete the default section
		if (!$s or empty($s['parent']))
			return false;

		# FIXME: move articles to the parent section first?
		# what if the parent is 'default'?

		$res = safe_delete('txp_section', "id='".doSlash($id)."'");

		if ($res and $s['rgt'] - $s['lft'] > 1) {
			// section has children, so reconnect them to the parent
			safe_update('txp_section', "parent='".$s['parent']."'",
				"parent='".$s['id']."'");
			// children might have to inherit from the new parent
			section_resolve_inheritance($s['parent']);
		}

		return $res;

	}

// -------------------------------------------------------------
// delete an entire branch, children and all
	function section_prune($id)
	{
		$s = safe_row('*', 'txp_section', "id='".doSlash($id)."'");
		// can't delete the default section
		if (!$s or empty($s['parent']))
			return false;

		# FIXME: move articles to the parent section first?
		# what if the parent is 'default'?

		return safe_delete('txp_section', "lft between '".$s['lft']."' and '".$s['rgt']."'");
	}

// -------------------------------------------------------------
	function section_update($id, $atts)
	{
		$old = safe_row('*', 'txp_section', "id='".doSlash($id)."'");
		if (!$old)
			return false;

		$rec = array_merge($old, $atts);

		$default = section_default();

		if (empty($rec['parent']))
			$rec['parent'] = $default['id'];
		if (empty($rec['name']))
			$rec['name'] = dumbDown($rec['title']);

		$res = safe_update_rec('txp_section', $rec, "id='".doSlash($id)."'");
		if ($res)
			section_resolve_inheritance($id);

		return $res;

	}

// -------------------------------------------------------------
	function section_default()
	{
		static $def;
		if ($def)
			return $def;

		return $def = safe_row('*', 'txp_section', "parent='0'");
	}

// -------------------------------------------------------------
	function section_resolve_inheritance($root)
	{
		// trickle settings down to the specified node and any children
		// that have inherit set to 1
		// there's probably a more efficient way to do this
		$children = tree_get('txp_section', $root, "inherit='1'");
		foreach ($children as $child) {
			$ancestor = section_inherit($child['parent']);
			$rec = array(
				'page'         => $ancestor['page'],
				'css'          => $ancestor['css'],
				'in_rss'       => $ancestor['in_rss'],
				'on_frontpage' => $ancestor['on_frontpage'],
				'searchable'   => $ancestor['searchable'],
			);
			safe_update_rec('txp_section', $rec, "id='".doSlash($child['id'])."'");
		}
	}

// -------------------------------------------------------------
	function section_inherit($id)
	{
		// find the closest ancestor of $id that has inheret == 0
		$from = safe_row('*', 'txp_section', "id='".doSlash($id)."'");
		if ($from['parent'] == 0)
			return $from;

		return safe_row('*', 'txp_section', "lft <= '".$from['lft']."' and rgt >= '".$from['rgt']."' and inherit=0 order by lft desc limit 1");
	}

?>
