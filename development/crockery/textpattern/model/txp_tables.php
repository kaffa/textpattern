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
	
	function create_table(){
		parent::create_table();
		# to prefix or not prefix new/existing indexes?
		unsafe_upgrade_index($this->_table_name,'categories_idx','','Category1,Category2');
		unsafe_upgrade_index($this->_table_name,'Posted','','Posted');
		if (MDB_TYPE == 'my') unsafe_upgrade_index($this->_table_name,'searching','fulltext','Title,Body');
		$this->_default_rows();
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
	
	function _default_rows(){
		$setup_comment_invite = addslashes( ( gTxt('setup_comment_invite')=='setup_comment_invite') ? 'Comment' : gTxt('setup_comment_invite') );
		$name = ps('name');
		if(empty($name)) $name = 'textpattern';
		if(!$this->row(array('id' => 1))){
			$this->insert(array(
				'id' => ZEM_INCVAL,
				'posted' => 'now()',
				'author_id' => "$name",
				'lastmod' => 'now()',
				'title' => 'First Post',
				'body' => 'Lorem ipsum dolor sit amet, consectetuer adipiscing elit. Donec rutrum est eu mauris. In volutpat blandit felis. Suspendisse eget pede. Class aptent taciti sociosqu ad litora torquent per conubia nostra, per inceptos hymenaeos. Quisque sed arcu. Aenean purus nulla, condimentum ac, pretium at, commodo sit amet, turpis. Aenean lacus. Ut in justo. Ut viverra dui vel ante. Duis imperdiet porttitor mi. Maecenas at lectus eu justo porta tempus. Cras fermentum ligula non purus. Duis id orci non magna rutrum bibendum. Mauris tincidunt, massa in rhoncus consectetuer, lectus dui ornare enim, ut egestas ipsum purus id urna. Vestibulum volutpat porttitor metus. Donec congue vehicula ante.',
				'body_html' => '	<p>Lorem ipsum dolor sit amet, consectetuer adipiscing elit. Donec rutrum est eu mauris. In volutpat blandit felis. Suspendisse eget pede. Class aptent taciti sociosqu ad litora torquent per conubia nostra, per inceptos hymenaeos. Quisque sed arcu. Aenean purus nulla, condimentum ac, pretium at, commodo sit amet, turpis. Aenean lacus. Ut in justo. Ut viverra dui vel ante. Duis imperdiet porttitor mi. Maecenas at lectus eu justo porta tempus. Cras fermentum ligula non purus. Duis id orci non magna rutrum bibendum. Mauris tincidunt, massa in rhoncus consectetuer, lectus dui ornare enim, ut egestas ipsum purus id urna. Vestibulum volutpat porttitor metus. Donec congue vehicula ante.</p>\n\n\n ',
				'excerpt' => '',
				'excerpt_html' => '\n\n\n ',
				'annotate' => 1,
				'annotateinvite' => "$setup_comment_invite",
				'comments_count' => 1,
				'status' => 4,
				'markup_body' => 1,
				'markup_excerpt' => 1,
				'section' => 'article',
				'url_title' => 'first-post',
				'uid' => 'becfea8fd42801204463b23701199f28',
				'feed_time' => 'now()',
			));
		}

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
	
	
	function create_table(){
		parent::create_table();
		
		if (!$this->row(array('name' => 'root','type' => 'article'))) {
			$this->insert(
				array('id' => ZEM_INCVAL,'name' => 'root','type' => 'article','ltf' => 1,'rgt' => 2,'title' => 'root')
			);
		}
		if (!$this->row(array('name' => 'root','type' => 'link'))) {
			$this->insert(
				array('id' => ZEM_INCVAL,'name' => 'root','type' => 'link','ltf' => 1,'rgt' => 2,'title' => 'root')
			);
		}
		if (!$this->row(array('name' => 'root','type' => 'image'))) {
			$this->insert(
				array('id' => ZEM_INCVAL,'name' => 'root','type' => 'image','ltf' => 1,'rgt' => 2,'title' => 'root')
			);
		}
		if (!$this->row(array('name' => 'root','type' => 'file'))) {
			$this->insert(
				array('id' => ZEM_INCVAL,'name' => 'root','type' => 'file','ltf' => 1,'rgt' => 2,'title' => 'root')
			);			
		}

		# are we going to use values to populate the DB?
		# are those values going to stay on this file?
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
	
	function create_table(){
		parent::create_table();
		
		if (!$this->row(array('name' => 'default'))) {
			$this->insert(
				array(
					'name' => 'default',
					'page' => 'default',
					'css' => 'default',
					'is_default' => 1,
					'in_rss' => 1,
					'on_frontpage' => 1,
					'searchable' => 1,
					'title' => 'Article',
	  			)
			);
		}
		
		if (!$this->row(array('name' => 'article'))) {
			$this->insert(
				array(
					'name' => 'article',
					'page' => 'archive',
					'css' => 'default',
					'is_default' => 0,
					'in_rss' => 1,
					'on_frontpage' => 1,
					'searchable' => 1,
					'title' => 'default',
	  			)
			);
		}
		if (!$this->row(array('name' => 'about'))) {
			$this->insert(
				array(
					'name' => 'about',
					'page' => 'default',
					'css' => 'default',
					'is_default' => 0,
					'in_rss' => 0,
					'on_frontpage' => 0,
					'searchable' => 1,
					'title' => 'About',
	  			)
			);
		}
		$this->upgrade_table();
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
		$this->_default_rows();
	}
	
	function _default_rows(){
		if (!$this->row(array('name' => 'default'))) {
			$this->insert(array('name' =>'default','css' => 'LyogYmFzZQ0KLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0gKi8NCg0KYm9keSB7DQptYXJnaW46IDA7DQpwYWRkaW5nOiAwOw0KZm9udC1mYW1pbHk6IFZlcmRhbmEsICJMdWNpZGEgR3JhbmRlIiwgVGFob21hLCBIZWx2ZXRpY2EsIHNhbnMtc2VyaWY7DQpjb2xvcjogIzAwMDsNCmJhY2tncm91bmQtY29sb3I6ICNmZmY7DQp9DQoNCmJsb2NrcXVvdGUsIGgzLCBwLCBsaSB7DQpwYWRkaW5nLXJpZ2h0OiAxMHB4Ow0KcGFkZGluZy1sZWZ0OiAxMHB4Ow0KZm9udC1zaXplOiAwLjllbTsNCmxpbmUtaGVpZ2h0OiAxLjZlbTsNCn0NCg0KYmxvY2txdW90ZSB7DQptYXJnaW4tcmlnaHQ6IDA7DQptYXJnaW4tbGVmdDogMjBweDsNCn0NCg0KaDEsIGgyLCBoMyB7DQpmb250LXdlaWdodDogbm9ybWFsOw0KfQ0KDQpoMSwgaDIgew0KZm9udC1mYW1pbHk6IEdlb3JnaWEsIFRpbWVzLCBzZXJpZjsNCn0NCg0KaDEgew0KZm9udC1zaXplOiAzZW07DQp9DQoNCmgyIHsNCmZvbnQtc2l6ZTogMWVtOw0KZm9udC1zdHlsZTogaXRhbGljOw0KfQ0KDQpociB7DQptYXJnaW46IDJlbSBhdXRvOw0Kd2lkdGg6IDM3MHB4Ow0KaGVpZ2h0OiAxcHg7DQpjb2xvcjogIzdhN2U3ZDsNCmJhY2tncm91bmQtY29sb3I6ICM3YTdlN2Q7DQpib3JkZXI6IG5vbmU7DQp9DQoNCnNtYWxsLCAuc21hbGwgew0KZm9udC1zaXplOiAwLjllbTsNCn0NCg0KLyogbGlua3MNCi0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tICovDQoNCmEgew0KdGV4dC1kZWNvcmF0aW9uOiBub25lOw0KY29sb3I6ICMwMDA7DQpib3JkZXItYm90dG9tOiAxcHggIzAwMCBzb2xpZDsNCn0NCg0KaDEgYSwgaDIgYSwgaDMgYSB7DQpib3JkZXI6IG5vbmU7DQp9DQoNCmgzIGEgew0KZm9udDogMS41ZW0gR2VvcmdpYSwgVGltZXMsIHNlcmlmOw0KfQ0KDQojc2lkZWJhci0yIGEsICNzaWRlYmFyLTEgYSB7DQpjb2xvcjogI2MwMDsNCmJvcmRlcjogbm9uZTsNCn0NCg0KLyogb3ZlcnJpZGVzDQotLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLSAqLw0KDQojc2lkZWJhci0yIHAsICNzaWRlYmFyLTEgcCB7DQpmb250LXNpemU6IDAuOGVtOw0KbGluZS1oZWlnaHQ6IDEuNWVtOw0KfQ0KDQouY2FwcyB7DQpmb250LXNpemU6IDAuOWVtOw0KbGV0dGVyLXNwYWNpbmc6IDAuMWVtOw0KfQ0KDQpkaXYuZGl2aWRlciB7DQptYXJnaW46IDJlbSAwOw0KdGV4dC1hbGlnbjogY2VudGVyOw0KfQ0KDQovKiBsYXlvdXQNCi0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tICovDQoNCiNhY2Nlc3NpYmlsaXR5IHsNCnBvc2l0aW9uOiBhYnNvbHV0ZTsNCnRvcDogLTEwMDAwcHg7DQp9DQoNCiNjb250YWluZXIgew0KbWFyZ2luOiAxMHB4IGF1dG87DQpwYWRkaW5nOiAxMHB4Ow0Kd2lkdGg6IDc2MHB4Ow0KfQ0KDQojaGVhZCB7DQpoZWlnaHQ6IDEwMHB4Ow0KdGV4dC1hbGlnbjogY2VudGVyOw0KfQ0KDQojc2lkZWJhci0xLCAjc2lkZWJhci0yIHsNCnBhZGRpbmctdG9wOiAxMDBweDsNCndpZHRoOiAxNTBweDsNCn0NCg0KI3NpZGViYXItMSB7DQptYXJnaW4tcmlnaHQ6IDVweDsNCmZsb2F0OiBsZWZ0Ow0KdGV4dC1hbGlnbjogcmlnaHQ7DQp9DQoNCiNzaWRlYmFyLTIgew0KbWFyZ2luLWxlZnQ6IDVweDsNCmZsb2F0OiByaWdodDsNCn0NCg0KI2NvbnRlbnQgew0KbWFyZ2luOiAwIDE1NXB4Ow0KcGFkZGluZy10b3A6IDMwcHg7DQp9DQoNCiNmb290IHsNCm1hcmdpbi10b3A6IDVweDsNCmNsZWFyOiBib3RoOw0KdGV4dC1hbGlnbjogY2VudGVyOw0KfQ0KDQovKiBib3ggbW9kZWwgaGFja3MNCmh0dHA6Ly9hcmNoaXZpc3QuaW5jdXRpby5jb20vdmlld2xpc3QvY3NzLWRpc2N1c3MvNDgzODYNCi0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tICovDQoNCiNjb250YWluZXIgew0KXHdpZHRoOiA3NzBweDsNCndcaWR0aDogNzYwcHg7DQp9DQoNCiNzaWRlYmFyLTEsICNzaWRlYmFyLTIgew0KXHdpZHRoOiAxNTBweDsNCndcaWR0aDogMTUwcHg7DQp9DQoNCi8qIGNvbW1lbnRzDQotLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLSAqLw0KDQouY29tbWVudHNfZXJyb3Igew0KY29sb3I6ICMwMDA7DQpiYWNrZ3JvdW5kLWNvbG9yOiAjZmZmNGY0IA0KfQ0KDQp1bC5jb21tZW50c19lcnJvciB7DQpwYWRkaW5nIDogMC4zZW07DQpsaXN0LXN0eWxlLXR5cGU6IGNpcmNsZTsNCmxpc3Qtc3R5bGUtcG9zaXRpb246IGluc2lkZTsNCmJvcmRlcjogMnB4IHNvbGlkICNmZGQ7DQp9DQoNCmRpdiNjcHJldmlldyB7DQpjb2xvcjogIzAwMDsNCmJhY2tncm91bmQtY29sb3I6ICNmMWYxZjE7DQpib3JkZXI6IDJweCBzb2xpZCAjZGRkOw0KfQ0KDQpmb3JtI3R4cENvbW1lbnRJbnB1dEZvcm0gdGQgew0KdmVydGljYWwtYWxpZ246IHRvcDsNCn0='));
		}
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
	
	function create_table(){
		parent::create_table();
		$this->_default_rows();
	}
	
	function _default_rows(){
		if (!$this->row(array('name' => 'default'))) {
			$this->insert(array('name' => 'default', 'user_html' => '<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.0 Transitional//EN\" \"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd\">\r\n<html xmlns=\"http://www.w3.org/1999/xhtml\" xml:lang=\"en\" lang=\"en\">\r\n<head>\r\n\t<meta http-equiv=\"Content-Type\" content=\"text/html; charset=utf-8\" />\r\n\r\n\t<title><txp:page_title /></title>\r\n\r\n\t<txp:feed_link flavor=\"atom\" format=\"link\" label=\"Atom\" />\r\n\t<txp:feed_link flavor=\"rss\" format=\"link\" label=\"RSS\" />\r\n\r\n\t<txp:css format=\"link\" />\r\n</head>\r\n<body>\r\n\r\n<!-- accessibility -->\r\n<div id=\"accessibility\">\r\n\t<ul>\r\n\t\t<li><a href=\"#content\">Go to content</a></li>\r\n\t\t<li><a href=\"#sidebar-1\">Go to navigation</a></li>\r\n\t\t<li><a href=\"#sidebar-2\">Go to search</a></li>\r\n\t</ul>\r\n</div>\r\n\r\n<div id=\"container\">\r\n\r\n<!-- head -->\r\n\t<div id=\"head\">\r\n\t\t<h1><txp:link_to_home><txp:sitename /></txp:link_to_home></h1>\r\n\t\t<h2><txp:site_slogan /></h2>\r\n\t</div>\r\n\r\n<!-- left -->\r\n\t<div id=\"sidebar-1\">\r\n\t<txp:linklist wraptag=\"p\" />\r\n\t</div>\r\n\r\n<!-- right -->\r\n\t<div id=\"sidebar-2\">\r\n\t\t<txp:search_input label=\"Search\" wraptag=\"p\" />\r\n\r\n\t\t<txp:popup type=\"c\" label=\"Browse\" wraptag=\"p\" />\r\n\r\n\t\t<p><txp:feed_link label=\"RSS\" /> / <txp:feed_link flavor=\"atom\" label=\"Atom\" /></p>\r\n\r\n\t\t<p><img src=\"<txp:site_url />textpattern/txp_img/txp_slug105x45.gif\" width=\"105\" height=\"45\" alt=\"Textpattern\" title=\"\" /></p>\r\n\t</div>\r\n\r\n<!-- center -->\r\n\t<div id=\"content\">\r\n\t<txp:article limit=\"5\" />\r\n\t\r\n<txp:if_individual_article>\r\n\t\t<p><txp:link_to_prev><txp:prev_title /></txp:link_to_prev> \r\n\t\t\t<txp:link_to_next><txp:next_title /></txp:link_to_next></p>\r\n<txp:else />\r\n\t\t<p><txp:older><txp:text item=\"older\" /></txp:older> \r\n\t\t\t<txp:newer><txp:text item=\"newer\" /></txp:newer></p>\r\n</txp:if_individual_article>\r\n\t</div>\r\n\r\n<!-- footer -->\r\n\t<div id=\"foot\">&nbsp;</div>\r\n\r\n</div>\r\n\r\n</body>\r\n</html>'));
		}
		if (!$this->row(array('name' => 'archive'))) {
			$this->insert(array('name' => 'archive', 'user_html' => '<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.0 Transitional//EN\" \"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd\">\r\n<html xmlns=\"http://www.w3.org/1999/xhtml\" xml:lang=\"en\" lang=\"en\">\r\n<head>\r\n\t<meta http-equiv=\"Content-Type\" content=\"text/html; charset=utf-8\" />\r\n\r\n\t<title><txp:page_title /></title>\r\n\r\n\t<txp:feed_link flavor=\"atom\" format=\"link\" label=\"Atom\" />\r\n\t<txp:feed_link flavor=\"rss\" format=\"link\" label=\"RSS\" />\r\n\r\n\t<txp:css format=\"link\" />\r\n</head>\r\n<body>\r\n\r\n<!-- accessibility -->\r\n<div id=\"accessibility\">\r\n\t<ul>\r\n\t\t<li><a href=\"#content\">Go to content</a></li>\r\n\t\t<li><a href=\"#sidebar-1\">Go to navigation</a></li>\r\n\t\t<li><a href=\"#sidebar-2\">Go to search</a></li>\r\n\t</ul>\r\n</div>\r\n\r\n<div id=\"container\">\r\n\r\n<!-- head -->\r\n\t<div id=\"head\">\r\n\t\t<h1><txp:link_to_home><txp:sitename /></txp:link_to_home></h1>\r\n\t\t<h2><txp:site_slogan /></h2>\r\n\t</div>\r\n\r\n<!-- left -->\r\n\t<div id=\"sidebar-1\">\r\n\t<txp:linklist wraptag=\"p\" />\r\n\t</div>\r\n\r\n<!-- right -->\r\n\t<div id=\"sidebar-2\">\r\n\t\t<txp:search_input label=\"Search\" wraptag=\"p\" />\r\n\r\n\t\t<txp:popup type=\"c\" label=\"Browse\" wraptag=\"p\" />\r\n\r\n\t\t<p><txp:feed_link label=\"RSS\" /> / <txp:feed_link flavor=\"atom\" label=\"Atom\" /></p>\r\n\r\n\t\t<p><img src=\"<txp:site_url />textpattern/txp_img/txp_slug105x45.gif\" width=\"105\" height=\"45\" alt=\"Textpattern\" title=\"\" /></p>\r\n\t</div>\r\n\r\n<!-- center -->\r\n\t<div id=\"content\">\r\n\t<txp:article limit=\"5\" />\r\n\t\r\n<txp:if_individual_article>\r\n\t\t<p><txp:link_to_prev><txp:prev_title /></txp:link_to_prev> \r\n\t\t\t<txp:link_to_next><txp:next_title /></txp:link_to_next></p>\r\n<txp:else />\r\n\t\t<p><txp:older><txp:text item=\"older\" /></txp:older> \r\n\t\t\t<txp:newer><txp:text item=\"newer\" /></txp:newer></p>\r\n</txp:if_individual_article>\r\n\t</div>\r\n\r\n<!-- footer -->\r\n\t<div id=\"foot\">&nbsp;</div>\r\n\r\n</div>\r\n\r\n</body>\r\n</html>'));
		}
	}
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
		$this->_default_rows();
	}
	
	function _default_rows(){
		if (!$this->row(array('discussid' => 000001))) {
			$this->insert(array(
				'discussid' => 000001,
				'parentid' => 1,
				'name' => 'Donald Swain',
				'email' => 'me@here.com',
				'web' => 'example.com',
				'ip' => '127.0.0.1',
				'posted' => '2005-07-22 14:11:32',
				'message' => 'I enjoy your site very much.',
				'visible' => 1
			));
		}

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
	
	function create_table(){
		parent::create_table();
		$this->_default_rows();
	}
	
	function _default_rows(){
		if (!$this->row(array('name' => 'Links'))) {
			$this->insert(array('name' => 'Links', 'type' => 'link', 'Form' => '<p><txp:link /><br />\r\n<txp:link_description /></p>'));
		}
		
		if (!$this->row(array('name' => 'lofi'))) {
			$this->insert(array('name' => 'lofi', 'type' => 'article', 'Form' => '<h3><txp:title /></h3>\r\n\r\n<p class=\"small\"><txp:permlink>#</txp:permlink> <txp:posted /></p>\r\n\r\n<txp:body />\r\n\r\n<hr />'));
		}
		
		if (!$this->row(array('name' => 'single'))) {
			$this->insert(array('name' => 'single', 'type' => 'article', 'Form' => '<h3><txp:title /> <span class=\"permlink\"><txp:permlink>::</txp:permlink></span> <span class=\"date\"><txp:posted /></span></h3>\r\n\r\n<txp:body />'));
		}
		
		if (!$this->row(array('name' => 'plainlinks'))) {
			$this->insert(array('name' => 'plainlinks', 'type' => 'link', 'Form' => '<txp:linkdesctitle /><br />'));
		}
		
		if (!$this->row(array('name' => 'comments'))) {
			$this->insert(array('name' => 'comments', 'type' => 'comment', 'Form' => '<txp:message />\r\n\r\n<p class=\"small\">&#8212; <txp:comment_name /> &#183; <txp:comment_time /> &#183; <txp:comment_permlink>#</txp:comment_permlink></p>'));
		}
		
		if (!$this->row(array('name' => 'default'))) {
			$this->insert(array('name' => 'default', 'type' => 'article', 'Form' => '<h3><txp:permlink><txp:title /></txp:permlink> &#183; <txp:posted /> by <txp:author /></h3>\r\n\r\n<txp:body />\r\n\r\n<txp:comments_invite wraptag=\"p\" />\r\n\r\n<div class=\"divider\"><img src=\"<txp:site_url />images/1.gif\" width=\"400\" height=\"1\" alt=\"---\" title=\"\" /></div>'));
		}
		
		if (!$this->row(array('name' => 'comment_form'))) {
			$this->insert(array('name' => 'comment_form', 'type' => 'comment', 'Form' => '<table cellpadding=\"4\" cellspacing=\"0\" border=\"0\">\r\n\r\n<tr>\r\n\t<td align=\"right\">\r\n\t\t<label for=\"name\"><txp:text item=\"name\" /></label>\r\n\t</td>\r\n\r\n\t<td>\r\n\t\t<txp:comment_name_input />\r\n\t</td>\r\n\r\n\t<td>\r\n\t\t<txp:comment_remember />\r\n\t</td> \r\n</tr>\r\n\r\n<tr>\r\n\t<td align=\"right\">\r\n\t\t<label for=\"email\"><txp:text item=\"email\" /></label>\r\n\t</td>\r\n\r\n\t<td colspan=\"2\">\r\n\t\t<txp:comment_email_input />\r\n\t</td>\r\n</tr>\r\n\r\n<tr> \r\n\t<td align=\"right\">\r\n\t\t<label for=\"web\">http://</label>\r\n\t</td>\r\n\r\n\t<td colspan=\"2\">\r\n\t\t<txp:comment_web_input />\r\n\t</td>\r\n</tr>\r\n\r\n<tr>\r\n\t<td align=\"right\">\r\n\t\t<label for=\"message\"><txp:text item=\"message\" /></label>\r\n\t</td>\r\n\r\n\t<td colspan=\"2\">\r\n\t\t<txp:comment_message_input />\r\n\t</td>\r\n</tr>\r\n\r\n<tr>\r\n\t<td align=\"right\">&nbsp;</td>\r\n\r\n\t<td>\r\n\t\t<txp:comments_help />\r\n\t</td>\r\n\r\n\t<td align=\"right\">\r\n\t\t<txp:comment_preview />\r\n\t\t<txp:comment_submit />\r\n\t</td>\r\n</tr>\r\n\r\n</table>'));
		}
		
		if (!$this->row(array('name' => 'noted'))) {
			$this->insert(array('name' => 'noted', 'type' => 'link', 'Form' => '<p><txp:link />. <txp:link_description /></p>'));
		}
		
		if (!$this->row(array('name' => 'popup_comments'))) {
			$this->insert(array('name' => 'popup_comments', 'type' => 'comment', 'Form' => '<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.0 Transitional//EN\" \"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd\">\r\n<html xmlns=\"http://www.w3.org/1999/xhtml\" xml:lang=\"en\" lang=\"en\">\r\n<head>\r\n\t<meta http-equiv=\"Content-Type\" content=\"text/html; charset=utf-8\" />\r\n\t<title><txp:page_title /></title>\r\n\t<link rel=\"stylesheet\" type=\"text/css\" href=\"<txp:css />\" />\r\n</head>\r\n<body>\r\n\r\n<div style=\"padding: 1em; width:300px;\">\r\n<txp:popup_comments />\r\n</div>\r\n\r\n</body>\r\n</html>'));
		}
		
		if (!$this->row(array('name' => 'files'))) {
			$this->insert(array('name' => 'files', 'type' => 'file', 'Form' => '<txp:text item=\"file\" />: \n<txp:file_download_link>\n<txp:file_download_name /> [<txp:file_download_size format=\"auto\" decimals=\"2\" />]\n</txp:file_download_link>\n<br />\n<txp:text item=\"category\" />: <txp:file_download_category /><br />\n<txp:text item=\"download\" />: <txp:file_download_downloads />'));
		}
		
		if (!$this->row(array('name' => 'search_results'))) {
			$this->insert(array('name' => 'search_results', 'type' => 'article', 'Form' => '<h3><txp:search_result_permlink><txp:search_result_title /></txp:search_result_permlink></h3>\r\n\r\n<p><txp:search_result_excerpt /></p>\r\n\r\n<p class=\"small\"><txp:search_result_permlink><txp:search_result_permlink /></txp:search_result_permlink> &#183; \r\n\t<txp:search_result_date /></p>'));
		}
		
		if (!$this->row(array('name' => 'comments_display'))) {
			$this->insert(array('name' => 'comments_display', 'type' => 'article', 'Form' => '<h3 id=\"comment\"><txp:comments_invite textonly=\"1\" showalways=\"1\" showcount=\"0\" /></h3>\r\n\r\n<txp:comments />\r\n\r\n<txp:if_comments_preview>\r\n<div id=\"cpreview\">\r\n<txp:comments_preview />\r\n</div>\r\n</txp:if_comments_preview>\r\n\r\n<txp:if_comments_allowed>\r\n<txp:comments_form preview=\"1\" />\r\n<txp:else />\r\n<p><txp:text item=\"comments_closed\" /></p>\r\n</txp:if_comments_allowed>'));
		}
		
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
	
	function create_table(){
		parent::create_table();
		# still do not add them
		#$this->_default_rows();
	}
	
	function _default_rows(){
		if(!$this->row(array('id' => 1))){
			$this->insert(array('id' => 1, 'date' => '2005-07-20 12:54:26', 'category' => 'textpattern', 'url' => 'http://textpattern.com/', 'linkname' => 'Textpattern', 'linksort' => 'Textpattern', 'description' => ''));
		}
		
		if(!$this->row(array('id' => 2))){
			$this->insert(array('id' => 2, 'date' => '2005-07-20 12:54:41', 'category' => 'textpattern', 'url' => 'http://textpattern.net/', 'linkname' => 'TextBook', 'linksort' => 'TextBook', 'description' => ''));
		}
				
		if(!$this->row(array('id' => 3))){
			$this->insert(array('id' => 3, 'date' => '2005-07-20 12:55:04', 'category' => 'textpattern', 'url' => 'http://textpattern.org/', 'linkname' => 'Txp Resources', 'linksort' => 'Txp Resources', 'description' => ''));
		}
		
	}
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
		$this->_default_rows();
	}
	
	function _default_rows(){
		//$prefs['blog_uid'] = md5(uniqid(rand(),true));
		require_once txpath.'/lib/txplib_prefs.php';
		$prefs = get_default_prefs();
		echo var_dump($prefs);
	}

}

?>