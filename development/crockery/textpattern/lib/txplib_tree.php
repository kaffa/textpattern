<?php

/*
$HeadURL: http://svn.textpattern.com/development/crockery/textpattern/lib/txplib_db.php $
$LastChangedRevision: 1093 $
*/

// -------------------------------------------------------------
 	function tree_get($table, $root=NULL, $where='1=1')
 	{ 
		// this is a generalization of the old getTree() function

		$root = doSlash($root);

		// don't apply $whwere here, since we assume the supplied root
		// already meets that constraint

		if ($root !== NULL) {
			 extract(safe_row(
				"lft as l, rgt as r", 
				$table, 
				"id='$root'"
			));

			if (empty($l) or empty($r))
				return array();

			$out = array();
			$right = array(); 

			 $rs = safe_rows_start(
				"*", 
				$table,
				"lft >= $l and lft <= $r and $where order by lft asc"
			); 
		}
		else {
			$rs = safe_rows_start('*', $table, $where.' order by lft asc');
		}

	    while ($rs and $row = nextRow($rs)) {
	   		extract($row);
			while (count($right) > 0 && $right[count($right)-1] < $rgt) { 
				array_pop($right);
			}

			$row['level'] = count($right);
			$row['children'] = ($rgt - $lft - 1) / 2;
			$out[] = $row;

			$right[] = $rgt; 
	    }
    	return($out);
 	}

// -------------------------------------------------------------
 	function tree_get_path($table, $target, $where='1=1')
 	{ 

	    extract(safe_row(
	    	"lft as l, rgt as r", 
	    	$table, 
			"id='".doSlash($target)."' and $where"
		));

		if (empty($l) or empty($r))
			return array();

	    $rs = safe_rows_start(
	    	"*", 
	    	$table,
				"lft <= $l and rgt >= $r and $where order by lft asc"
		); 

		$out = array();
		$right = array(); 

	    while ($rs and $row = nextRow($rs)) {
	   		extract($row);
			while (count($right) > 0 && $right[count($right)-1] < $rgt) { 
				array_pop($right);
			}

			$row['level'] = count($right);
			$row['children'] = ($rgt - $lft - 1) / 2;
			$out[] = $row;

			$right[] = $rgt; 
	    }
		return $out;
	}

// -------------------------------------------------------------
// make room for a new node, and return the lft and rgt values it should use
	function tree_insert_space($table, $parent, $where='1=1') 
	{ 
		$root = doSlash($parent);

	    $row = safe_row(
	    	"lft as l, rgt as r", 
	    	$table, 
			"id='$root' and $where"
		);
		if (empty($row)) {
			trigger_error('no such node '.$parent);
			return false;
		}

		extract($row);

		safe_update($table, "rgt=rgt+2", "rgt >= '$r' and $where");
		safe_update($table, "lft=lft+2", "lft >= '$r' and $where");

		return array($r, $r+1);
	}

// -------------------------------------------------------------
	function tree_rebuild($table, $parent, $left, $where='1=1', $sortby='name') 
	{ 
		$right = $left+1;

		$parent = doSlash($parent);

		$result = safe_column("id", $table, 
			"parent='$parent' and $where order by $sortby");
	
		foreach($result as $row) { 
    	    $right = tree_rebuild($table, $row, $right, $where, $sortby); 
	    } 

	    safe_update(
	    	$table, 
	    	"lft=$left, rgt=$right",
	    	"id='$parent' and $where"
	    );
    	return $right+1; 
 	} 

?>
