<?php

require_once dirname(dirname(__FILE__)).'/lib/txplib_table.php';

# These are due to unprefixed index for existing installs.
# Are we going to prefix the indexes or to modify the functions?

// -------------------------------------------------------------
	function unsafe_index_exists($table, $idxname, $debug='') 
	{
		return db_index_exists(PFX.$table, $idxname);
	}

// -------------------------------------------------------------
	function unsafe_upgrade_index($table, $idxname, $type, $def, $debug='') 
	{
		// $type would typically be '' or 'unique'
		if (!unsafe_index_exists($table, $idxname))
			return safe_query('create '.$type.' index '.$idxname.' on '.PFX.$table.' ('.$def.');');
	}

class txp_article_table extends zem_table {

//		$where = "1=1" . $statusq. $time.
//			$search . $id . $category . $section . $excerpted . $month . $author . $keywords . $custom . $frontpage;

	var $_table_name = 'textpattern';

	// name => sql column type definition
	var $_cols = array(
		'id' => ZEM_PRIMARY_KEY,
		'posted' => ZEM_DATETIME,
		'author_id' => ZEM_FOREIGN_KEY,
		'lastmod' => ZEM_DATETIME,
		'lastmod_id' => ZEM_FOREIGN_KEY,
		'title' => "varchar(255) not null",
		'title_html' => "varchar(255) not null",
		'body' => ZEM_MEDIUMTEXT,
		'body_html' => ZEM_MEDIUMTEXT,
		'excerpt' => 'text not null',
		'excerpt_html' => 'text not null',
		'image' => "varchar(255) not null default ''",
		'category1' => "varchar(128) not null default ''",
		'category2' => "varchar(128) not null default ''",
		'annotate' => "smallint not null default '0'",
		'annotateinvite' => "varchar(255) NOT NULL default ''",
		'comments_count' => "int not null default '0'",
		'status' => "smallint NOT NULL default '4'",
		'markup_body' => "varchar(32)",
		'markup_excerpt' => "varchar(32)",
		'section' => "varchar(64)",
		'section_id' => ZEM_FOREIGN_KEY,
		'override_form' => "varchar(255) not null default ''",
		'keywords' => "varchar(255) not null default ''",
		'url_title' => "varchar(255) not null default ''",
		'custom_1' => "varchar(255) not null default ''",
		'custom_2' => "varchar(255) not null default ''",
		'custom_3' => "varchar(255) not null default ''",
		'custom_4' => "varchar(255) not null default ''",
		'custom_5' => "varchar(255) not null default ''",
		'custom_6' => "varchar(255) not null default ''",
		'custom_7' => "varchar(255) not null default ''",
		'custom_8' => "varchar(255) not null default ''",
		'custom_9' => "varchar(255) not null default ''",
		'custom_10' => "varchar(255) not null default ''",
		'uid' => "varchar(32) not null default ''",
		'feed_time' => 'date not null',
	);
	
	function upgrade_table(){
		parent::upgrade_table();

		unsafe_upgrade_index($this->_table_name,'categories_idx','','Category1,Category2');
		unsafe_upgrade_index($this->_table_name,'Posted','','Posted');
		if (MDB_TYPE == 'my') unsafe_upgrade_index($this->_table_name,'searching','fulltext','Title,Body');
	}

	// this covers most of the query stuff that used to be in doArticles
	function article_rows($status, $time, $search, $searchsticky, $section, $category, $excerpted, $month, $author, $keywords, $custom, $frontpage, $sort) {
		$where = array();

		if ($status)
			$where['status'] = $status;
		elseif ($searchsticky)
			$where[] = 'status >= 4';
		else
			$where['status'] = 4;

		if($search) {
			include_once txpath.'/publish/search.php';
			$s_filter = ($searchall ? filterSearch() : '');
			$match = ", ".db_match('Title,Body', doSlash($q));

			$words = preg_split('/\s+/', $q);
			foreach ($words as $w) {
				$where[] = "(Title ".db_rlike()." '".doSlash(preg_quote($w))."' or Body ".db_rlike()." '".doSlash(preg_quote($w))."')";
			}
			#$search = " and " . join(' and ', $rlike) . " $s_filter";
			$where[] = $s_filter;

			// searchall=0 can be used to show search results for the current section only
			if ($searchall) $section = '';
			if (!$sort) $sort='score';
		}

		// ..etc..

	}

}
/*
class txp_category_table extends zem_table 
{
	var $_table_name = 'txp_category'; # this could be inferable through introspection
	
	var $_cols = array(
		'id' => ZEM_PRIMARY_KEY,
		'name' => "varchar(64) NOT NULL default ''",
		'type' => "varchar(64) NOT NULL default ''",
		'parent' => "varchar(64) NOT NULL default ''",
		'ltf' => "int(11) NOT NULL default '0'",
		'rgt' => "int(11) NOT NULL default '0'",
		'title' => "varchar(255) NOT NULL default ''"
	);
	
	function upgrade_table(){
		parent::upgrade_table();
		$incval = ZEM_INCVAL;
		safe_insert('txp_category',"$incval, 'root', 'article', '', 1, 2, 'root'");
		safe_insert('txp_category',"$incval, 'root', 'link', '', 1, 2, 'root'");
		safe_insert('txp_category',"$incval, 'root', 'image', '', 1, 2, 'root'");
		safe_insert('txp_category',"$incval, 'root', 'file', '', 1, 2, 'root'");
	}
}

class txp_section_table extends zem_table 
{
	var $_table_name = 'txp_section';
	
	var $_cols = array(
	  'name' => "varchar(128) NOT NULL default ''",
	  'page' => "varchar(128) NOT NULL default ''",
	  'css' => "varchar(128) NOT NULL default ''",
	  'is_default' => "smallint(6) NOT NULL default '0'",
	  'in_rss' => "smallint(6) NOT NULL default '1'",
	  'on_frontpage' => "smallint(6) NOT NULL default '1'",
	  'searchable' => "smallint(6) NOT NULL default '1'",
	  'title' => "varchar(255) NOT NULL default ''",
	  'id' => ZEM_PRIMARY_KEY,
	  'path' => "varchar(255) NOT NULL default ''",
	  'parent' => "int(11) default NULL",
	  'lft' => "int(11) NOT NULL default '0'",
	  'rgt' => "int(11) NOT NULL default '0'",
	  'inherit' => "smallint(6) NOT NULL default '0'",
	);
}*/

?>