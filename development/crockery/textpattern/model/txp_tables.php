<?php

/*
$HeadURL$
$LastChangedRevision$
*/

require_once dirname(dirname(__FILE__)).'/lib/txplib_table.php';

# These are due to unprefixed index for existing installs.
# Are we going to prefix the indexes or to modify the functions?

// -------------------------------------------------------------
	function unsafe_index_exists($table, $idxname, $debug='') 
	{
		global $DB;
		return $DB->index_exists(PFX.$table, $idxname);
	}

// -------------------------------------------------------------
	function unsafe_upgrade_index($table, $idxname, $type, $def, $debug='') 
	{
		global $DB;
		// $type would typically be '' or 'unique'
		if (!unsafe_index_exists($table, $idxname))
			return $DB->do_query('create '.$type.' index '.$idxname.' on '.PFX.$table.' ('.$def.');');
	}

class txp_article_table extends zem_table {

//		$where = "1=1" . $statusq. $time.
//			$search . $id . $category . $section . $excerpted . $month . $author . $keywords . $custom . $frontpage;

	var $_table_name = 'textpattern';

	// name => sql column type definition
	var $_cols = array(
		'id' => ZEM_PRIMARY_KEY,
		'posted' => ZEM_DATETIME,
		'expires' => ZEM_DATETIME, // @todo NOT NULL default '0000-00-00 00:00:00'
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
	
	function create_table(){
		parent::create_table();
		# to prefix or not prefix new/existing indexes?
		unsafe_upgrade_index($this->_table_name,'categories_idx','','Category1,Category2');
		unsafe_upgrade_index($this->_table_name,'Posted','','Posted');
		unsafe_upgrade_index($this->_table_name,'expires_idx','','expires');
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
	
	function create_table(){
		parent::create_table();
	}
	
	function upgrade_table() {
		parent::upgrade_table();
		safe_update($this->_table_name, 'path=name', "path=''");

		# shortname has to be unique within a parent
		if (!safe_index_exists($this->_table_name, 'parent_idx')) 
		safe_upgrade_index($this->_table_name, 'parent_idx', 'unique', 'parent,name');

		safe_update('txp_section', 'parent=0', "name='default'");
		$this->update(array('parent' => 0), array('name' => 'default'));

		$root_id = safe_field('id', $this->_table_name, "name='default'");
		safe_update($this->_table_name, "parent='".$root_id."'", "parent IS NULL");
		include_once(txpath.'/lib/txplib_tree.php');
		tree_rebuild($this->_table_name, $root_id, 1);
	}
}

class txp_css_table extends zem_table 
{
	var $_table_name = 'txp_css';
	
	var $_cols = array(
	  'name' => "varchar(255) default NULL",
	  'css' => "text",
	);
	
	var $_primary_key = null;
	
	function create_table(){
		parent::create_table();
		safe_upgrade_index($this->_table_name,'name','','name');
	}
}

class txp_page_table extends zem_table 
{
	var $_table_name = 'txp_page';
	
	var $_cols = array(
	  'name' => "varchar(128) NOT NULL default ''",
	  'user_html' => "text NOT NULL",
	);
	
	var $_primary_key = 'name';

}

class txp_users_table extends zem_table 
{
	
	var $_table_name = 'txp_users';
	
	var $_cols = array(
		'user_id' => ZEM_PRIMARY_KEY,
		'name' => "varchar(64) NOT NULL default ''",
		'pass' => "varchar(128) NOT NULL default ''",
		'RealName' => "varchar(64) NOT NULL default ''",
		'email' => "varchar(100) NOT NULL default ''",
		'privs' => "smallint NOT NULL default '1'",
		'last_access' => ZEM_DATETIME,
		'nonce' => "varchar(64) NOT NULL default ''",
	);
	
	var $_primary_key = 'user_id';
	
	function create_table(){
		parent::create_table();
		safe_upgrade_index($this->_table_name,'user_name','UNIQUE','name');
	}
}


class txp_discuss_table extends zem_table 
{
	var $_table_name = 'txp_discuss';
	
	var $_primary_key = 'discussid';
	
	var $_cols = array(
  		'discussid' => ZEM_PRIMARY_KEY,
  		'parentid' => "int NOT NULL default '0'",
  		'name' => "varchar(255) NOT NULL default ''",
  		'email' => "varchar(50) NOT NULL default ''",
  		'web' => "varchar(255) NOT NULL default ''",
  		'ip' => "varchar(100) NOT NULL default ''",
  		'posted' => ZEM_DATETIME,
  		'message' => "text NOT NULL",
  		'visible' => "smallint NOT NULL default '1'",
	);
	
	function create_table(){
		parent::create_table();
		safe_upgrade_index($this->_table_name,'parentid','','parentid');
	}
	
}

class txp_discuss_ipban_table extends zem_table 
{
	var $_table_name = 'txp_discuss_ipban';
	
	var $_cols = array(
		'ip' => "varchar(255) NOT NULL default ''",
  		'name_used' => "varchar(255) NOT NULL default ''",
  		'date_banned' => ZEM_DATETIME,
  		'banned_on_message' => "smallint NOT NULL default '0'",
	);
	
	var $_primary_key = 'ip';
}

class txp_discuss_nonce_table extends zem_table 
{
	var $_table_name = 'txp_discuss_nonce';
	
	var $_cols = array(
		'issue_time' => ZEM_DATETIME,
		'nonce' => "varchar(255) NOT NULL default ''",
		'used' => "smallint NOT NULL default '0'",
		'secret' => "varchar(255) NOT NULL default ''",
	);
	
	var $_primary_key = 'nonce';
}

class txp_file_table extends zem_table 
{
	var $_table_name = 'txp_file';
	
	var $_cols = array(
		'id' => ZEM_PRIMARY_KEY,
  		'filename' => "varchar(255) NOT NULL default ''",
  		'category' => "varchar(255) NOT NULL default ''",
  		'permissions' => "varchar(32) NOT NULL default '0'",
  		'description' => "text NOT NULL",
  		'downloads' => "int NOT NULL default '0'",
	);
	
	function create_table(){
		parent::create_table();
		safe_upgrade_index($this->_table_name,'filename','UNIQUE','filename');
	}
}

class txp_form_table extends zem_table 
{
	var $_table_name = 'txp_form';
	
	var $_cols = array(
		'name' => "varchar(64) NOT NULL default ''",
		'type' => "varchar(28) NOT NULL default ''",
		'Form' => "text NOT NULL",
	);
	
	var $_primary_key = 'name';
	
	function upgrade_table(){
		parent::upgrade_table();
		safe_update('txp_form', "Form = REPLACE(Form, '<txp:message', '<txp:comment_message')", "1 = 1");
	}
	
	function create_table(){
		parent::create_table();
	}
}

class txp_image_table extends zem_table 
{
	var $_table_name = 'txp_image';
	
	var $_cols = array(
		'id' => ZEM_PRIMARY_KEY,
		'name' => "varchar(255) NOT NULL default ''",
		'category' => "varchar(255) NOT NULL default ''",
		'ext' => "varchar(20) NOT NULL default ''",
		'w' => "int NOT NULL default '0'",
		'h' => "int NOT NULL default '0'",
		'alt' => "varchar(255) NOT NULL default ''",
		'caption' => "text NOT NULL",
		'date' => ZEM_DATETIME,
		'author' => "varchar(255) NOT NULL default ''",
		'thumbnail' => "smallint NOT NULL default '0'",
	);
}

class txp_lang_table extends zem_table 
{
	var $_table_name = 'txp_lang';
	
	var $_cols = array(
		'id' => ZEM_PRIMARY_KEY,
		'lang' => "varchar(16) default NULL",
		'name' => "varchar(64) default NULL",
		'event' => "varchar(64) default NULL",
		'data' => ZEM_TINYTEXT,
		'lastmod' => "timestamp",
	);
	
	function create_table(){
		parent::create_table();
		safe_upgrade_index($this->_table_name,'lang','UNIQUE','lang,name');
		safe_upgrade_index($this->_table_name,'lang_2','','lang,event');
	}
}

class txp_link_table extends zem_table 
{
	var $_table_name = 'txp_link';
	
	var $_cols = array(
  		'id' => ZEM_PRIMARY_KEY,
  		'date' => ZEM_DATETIME,
  		'category' => "varchar(64) NOT NULL default ''",
  		'url' => "text NOT NULL",
  		'linkname' => "varchar(255) NOT NULL default ''",
  		'linksort' => "varchar(128) NOT NULL default ''",
  		'description' => "text NOT NULL",
	);

}

class txp_log_table extends zem_table 
{
	var $_table_name = 'txp_log';
	
	var $_cols = array(
		'id' => ZEM_PRIMARY_KEY,
		'time' => ZEM_DATETIME,
		'host' => "varchar(255) NOT NULL default ''",
		'page' => "varchar(255) NOT NULL default ''",
		'refer' => "text NOT NULL",
		'status' => "int NOT NULL default '200'",
		'method' => "varchar(16) NOT NULL default 'GET'",
		'ip' => "varchar(16) NOT NULL default ''",
	);
	
	function create_table(){
		parent::create_table();
		safe_upgrade_index($this->_table_name,'time','','time');
	}
}

class txp_plugin_table extends zem_table 
{
	var $_table_name = 'txp_plugin';
	
	var $_cols = array(
  		'name' => "varchar(64) NOT NULL default ''",
  		'status' => "smallint NOT NULL default '1'",
  		'author' => "varchar(128) NOT NULL default ''",
  		'author_uri' => "varchar(128) NOT NULL default ''",
  		'version' => "varchar(10) NOT NULL default '1.0'",
  		'description' => "text NOT NULL",
  		'help' => "text NOT NULL",
  		'code' => "text NOT NULL",
  		'code_restore' => "text NOT NULL",
  		'code_md5' => "varchar(32) NOT NULL default ''",
  		'type' => "smallint NOT NULL default '0'",
	);

	var $_primary_key = 'name';
}

class txp_prefs_table extends zem_table 
{
	var $_table_name = 'txp_prefs';
	
	var $_cols = array(
  		'prefs_id' => "INT NOT NULL default '1'",
  		'name' => "varchar(255) default NULL",
  		'val' => "varchar(255) default NULL",
  		'type' => "smallint NOT NULL default '2'",
  		'event' => "varchar(12) NOT NULL default 'publish'",
  		'html' => "varchar(64) NOT NULL default 'text_input'",
  		'position' => "smallint NOT NULL default '0'",
	);
	
	var $_primary_key = 'prefs_id, name';
	
	function create_table(){
		parent::create_table();
		safe_upgrade_index($this->_table_name,'prefs_idx','UNIQUE','prefs_id,name');
		safe_upgrade_index($this->_table_name,'name','','name');
	}

}

?>