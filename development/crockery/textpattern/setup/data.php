<?php

class textpattern_setup_rows 
{
	function txp_article_table()
	{
		$table = new txp_article_table();
		$setup_comment_invite = addslashes( ( gTxt('setup_comment_invite')=='setup_comment_invite') ? 'Comment' : gTxt('setup_comment_invite') );
		$name = ps('name');
		if(empty($name)) $name = 'textpattern';
		if(!$table->row(array('id' => 1))){
			$table->insert(array(
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
	
	function txp_category_table()
	{
		$table = new txp_category_table();
		
		if (!$table->row(array('name' => 'root','type' => 'article'))) {
			$table->insert(
				array('id' => ZEM_INCVAL,'name' => 'root','type' => 'article','ltf' => 1,'rgt' => 2,'title' => 'root')
			);
		}
		if (!$table->row(array('name' => 'root','type' => 'link'))) {
			$table->insert(
				array('id' => ZEM_INCVAL,'name' => 'root','type' => 'link','ltf' => 1,'rgt' => 2,'title' => 'root')
			);
		}
		if (!$table->row(array('name' => 'root','type' => 'image'))) {
			$table->insert(
				array('id' => ZEM_INCVAL,'name' => 'root','type' => 'image','ltf' => 1,'rgt' => 2,'title' => 'root')
			);
		}
		if (!$table->row(array('name' => 'root','type' => 'file'))) {
			$table->insert(
				array('id' => ZEM_INCVAL,'name' => 'root','type' => 'file','ltf' => 1,'rgt' => 2,'title' => 'root')
			);			
		}

		# are we going to use values to populate the DB?
		# are those values going to stay on this file?
	}
	
	
	function txp_section_table()
	{
		$table = new txp_section_table();
		
		if (!$table->row(array('name' => 'default'))) {
			$table->insert(
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
		
		if (!$table->row(array('name' => 'article'))) {
			$table->insert(
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
		if (!$table->row(array('name' => 'about'))) {
			$table->insert(
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
	}
	
	function txp_css_table()
	{
		$table = new txp_css_table();
		if (!$table->row(array('name' => 'default'))) {
			$table->insert(array('name' =>'default','css' => 'LyogYmFzZQ0KLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0gKi8NCg0KYm9keSB7DQptYXJnaW46IDA7DQpwYWRkaW5nOiAwOw0KZm9udC1mYW1pbHk6IFZlcmRhbmEsICJMdWNpZGEgR3JhbmRlIiwgVGFob21hLCBIZWx2ZXRpY2EsIHNhbnMtc2VyaWY7DQpjb2xvcjogIzAwMDsNCmJhY2tncm91bmQtY29sb3I6ICNmZmY7DQp9DQoNCmJsb2NrcXVvdGUsIGgzLCBwLCBsaSB7DQpwYWRkaW5nLXJpZ2h0OiAxMHB4Ow0KcGFkZGluZy1sZWZ0OiAxMHB4Ow0KZm9udC1zaXplOiAwLjllbTsNCmxpbmUtaGVpZ2h0OiAxLjZlbTsNCn0NCg0KYmxvY2txdW90ZSB7DQptYXJnaW4tcmlnaHQ6IDA7DQptYXJnaW4tbGVmdDogMjBweDsNCn0NCg0KaDEsIGgyLCBoMyB7DQpmb250LXdlaWdodDogbm9ybWFsOw0KfQ0KDQpoMSwgaDIgew0KZm9udC1mYW1pbHk6IEdlb3JnaWEsIFRpbWVzLCBzZXJpZjsNCn0NCg0KaDEgew0KZm9udC1zaXplOiAzZW07DQp9DQoNCmgyIHsNCmZvbnQtc2l6ZTogMWVtOw0KZm9udC1zdHlsZTogaXRhbGljOw0KfQ0KDQpociB7DQptYXJnaW46IDJlbSBhdXRvOw0Kd2lkdGg6IDM3MHB4Ow0KaGVpZ2h0OiAxcHg7DQpjb2xvcjogIzdhN2U3ZDsNCmJhY2tncm91bmQtY29sb3I6ICM3YTdlN2Q7DQpib3JkZXI6IG5vbmU7DQp9DQoNCnNtYWxsLCAuc21hbGwgew0KZm9udC1zaXplOiAwLjllbTsNCn0NCg0KLyogbGlua3MNCi0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tICovDQoNCmEgew0KdGV4dC1kZWNvcmF0aW9uOiBub25lOw0KY29sb3I6ICMwMDA7DQpib3JkZXItYm90dG9tOiAxcHggIzAwMCBzb2xpZDsNCn0NCg0KaDEgYSwgaDIgYSwgaDMgYSB7DQpib3JkZXI6IG5vbmU7DQp9DQoNCmgzIGEgew0KZm9udDogMS41ZW0gR2VvcmdpYSwgVGltZXMsIHNlcmlmOw0KfQ0KDQojc2lkZWJhci0yIGEsICNzaWRlYmFyLTEgYSB7DQpjb2xvcjogI2MwMDsNCmJvcmRlcjogbm9uZTsNCn0NCg0KLyogb3ZlcnJpZGVzDQotLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLSAqLw0KDQojc2lkZWJhci0yIHAsICNzaWRlYmFyLTEgcCB7DQpmb250LXNpemU6IDAuOGVtOw0KbGluZS1oZWlnaHQ6IDEuNWVtOw0KfQ0KDQouY2FwcyB7DQpmb250LXNpemU6IDAuOWVtOw0KbGV0dGVyLXNwYWNpbmc6IDAuMWVtOw0KfQ0KDQpkaXYuZGl2aWRlciB7DQptYXJnaW46IDJlbSAwOw0KdGV4dC1hbGlnbjogY2VudGVyOw0KfQ0KDQovKiBsYXlvdXQNCi0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tICovDQoNCiNhY2Nlc3NpYmlsaXR5IHsNCnBvc2l0aW9uOiBhYnNvbHV0ZTsNCnRvcDogLTEwMDAwcHg7DQp9DQoNCiNjb250YWluZXIgew0KbWFyZ2luOiAxMHB4IGF1dG87DQpwYWRkaW5nOiAxMHB4Ow0Kd2lkdGg6IDc2MHB4Ow0KfQ0KDQojaGVhZCB7DQpoZWlnaHQ6IDEwMHB4Ow0KdGV4dC1hbGlnbjogY2VudGVyOw0KfQ0KDQojc2lkZWJhci0xLCAjc2lkZWJhci0yIHsNCnBhZGRpbmctdG9wOiAxMDBweDsNCndpZHRoOiAxNTBweDsNCn0NCg0KI3NpZGViYXItMSB7DQptYXJnaW4tcmlnaHQ6IDVweDsNCmZsb2F0OiBsZWZ0Ow0KdGV4dC1hbGlnbjogcmlnaHQ7DQp9DQoNCiNzaWRlYmFyLTIgew0KbWFyZ2luLWxlZnQ6IDVweDsNCmZsb2F0OiByaWdodDsNCn0NCg0KI2NvbnRlbnQgew0KbWFyZ2luOiAwIDE1NXB4Ow0KcGFkZGluZy10b3A6IDMwcHg7DQp9DQoNCiNmb290IHsNCm1hcmdpbi10b3A6IDVweDsNCmNsZWFyOiBib3RoOw0KdGV4dC1hbGlnbjogY2VudGVyOw0KfQ0KDQovKiBib3ggbW9kZWwgaGFja3MNCmh0dHA6Ly9hcmNoaXZpc3QuaW5jdXRpby5jb20vdmlld2xpc3QvY3NzLWRpc2N1c3MvNDgzODYNCi0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tICovDQoNCiNjb250YWluZXIgew0KXHdpZHRoOiA3NzBweDsNCndcaWR0aDogNzYwcHg7DQp9DQoNCiNzaWRlYmFyLTEsICNzaWRlYmFyLTIgew0KXHdpZHRoOiAxNTBweDsNCndcaWR0aDogMTUwcHg7DQp9DQoNCi8qIGNvbW1lbnRzDQotLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLSAqLw0KDQouY29tbWVudHNfZXJyb3Igew0KY29sb3I6ICMwMDA7DQpiYWNrZ3JvdW5kLWNvbG9yOiAjZmZmNGY0IA0KfQ0KDQp1bC5jb21tZW50c19lcnJvciB7DQpwYWRkaW5nIDogMC4zZW07DQpsaXN0LXN0eWxlLXR5cGU6IGNpcmNsZTsNCmxpc3Qtc3R5bGUtcG9zaXRpb246IGluc2lkZTsNCmJvcmRlcjogMnB4IHNvbGlkICNmZGQ7DQp9DQoNCmRpdiNjcHJldmlldyB7DQpjb2xvcjogIzAwMDsNCmJhY2tncm91bmQtY29sb3I6ICNmMWYxZjE7DQpib3JkZXI6IDJweCBzb2xpZCAjZGRkOw0KfQ0KDQpmb3JtI3R4cENvbW1lbnRJbnB1dEZvcm0gdGQgew0KdmVydGljYWwtYWxpZ246IHRvcDsNCn0='));
		}
	}
	
	function txp_page_table()
	{
		$table = new txp_page_table();
		if (!$table->row(array('name' => 'default'))) {
			$table->insert(array('name' => 'default', 'user_html' => "<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.0 Transitional//EN\" \"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd\">\r\n<html xmlns=\"http://www.w3.org/1999/xhtml\" xml:lang=\"en\" lang=\"en\">\r\n<head>\r\n\t<meta http-equiv=\"Content-Type\" content=\"text/html; charset=utf-8\" />\r\n\r\n\t<title><txp:page_title /></title>\r\n\r\n\t<txp:feed_link flavor=\"atom\" format=\"link\" label=\"Atom\" />\r\n\t<txp:feed_link flavor=\"rss\" format=\"link\" label=\"RSS\" />\r\n\r\n\t<txp:css format=\"link\" />\r\n</head>\r\n<body>\r\n\r\n<!-- accessibility -->\r\n<div id=\"accessibility\">\r\n\t<ul>\r\n\t\t<li><a href=\"#content\">Go to content</a></li>\r\n\t\t<li><a href=\"#sidebar-1\">Go to navigation</a></li>\r\n\t\t<li><a href=\"#sidebar-2\">Go to search</a></li>\r\n\t</ul>\r\n</div>\r\n\r\n<div id=\"container\">\r\n\r\n<!-- head -->\r\n\t<div id=\"head\">\r\n\t\t<h1><txp:link_to_home><txp:sitename /></txp:link_to_home></h1>\r\n\t\t<h2><txp:site_slogan /></h2>\r\n\t</div>\r\n\r\n<!-- left -->\r\n\t<div id=\"sidebar-1\">\r\n\t<txp:linklist wraptag=\"p\" />\r\n\t</div>\r\n\r\n<!-- right -->\r\n\t<div id=\"sidebar-2\">\r\n\t\t<txp:search_input label=\"Search\" wraptag=\"p\" />\r\n\r\n\t\t<txp:popup type=\"c\" label=\"Browse\" wraptag=\"p\" />\r\n\r\n\t\t<p><txp:feed_link label=\"RSS\" /> / <txp:feed_link flavor=\"atom\" label=\"Atom\" /></p>\r\n\r\n\t\t<p><img src=\"<txp:site_url />textpattern/txp_img/txp_slug105x45.gif\" width=\"105\" height=\"45\" alt=\"Textpattern\" title=\"\" /></p>\r\n\t</div>\r\n\r\n<!-- center -->\r\n\t<div id=\"content\">\r\n\t<txp:article limit=\"5\" />\r\n\t\r\n<txp:if_individual_article>\r\n\t\t<p><txp:link_to_prev><txp:prev_title /></txp:link_to_prev> \r\n\t\t\t<txp:link_to_next><txp:next_title /></txp:link_to_next></p>\r\n<txp:else />\r\n\t\t<p><txp:older><txp:text item=\"older\" /></txp:older> \r\n\t\t\t<txp:newer><txp:text item=\"newer\" /></txp:newer></p>\r\n</txp:if_individual_article>\r\n\t</div>\r\n\r\n<!-- footer -->\r\n\t<div id=\"foot\">&nbsp;</div>\r\n\r\n</div>\r\n\r\n</body>\r\n</html>"));
		}
		if (!$table->row(array('name' => 'archive'))) {
			$table->insert(array('name' => 'archive', 'user_html' => "<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.0 Transitional//EN\" \"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd\">\r\n<html xmlns=\"http://www.w3.org/1999/xhtml\" xml:lang=\"en\" lang=\"en\">\r\n<head>\r\n\t<meta http-equiv=\"Content-Type\" content=\"text/html; charset=utf-8\" />\r\n\r\n\t<title><txp:page_title /></title>\r\n\r\n\t<txp:feed_link flavor=\"atom\" format=\"link\" label=\"Atom\" />\r\n\t<txp:feed_link flavor=\"rss\" format=\"link\" label=\"RSS\" />\r\n\r\n\t<txp:css format=\"link\" />\r\n</head>\r\n<body>\r\n\r\n<!-- accessibility -->\r\n<div id=\"accessibility\">\r\n\t<ul>\r\n\t\t<li><a href=\"#content\">Go to content</a></li>\r\n\t\t<li><a href=\"#sidebar-1\">Go to navigation</a></li>\r\n\t\t<li><a href=\"#sidebar-2\">Go to search</a></li>\r\n\t</ul>\r\n</div>\r\n\r\n<div id=\"container\">\r\n\r\n<!-- head -->\r\n\t<div id=\"head\">\r\n\t\t<h1><txp:link_to_home><txp:sitename /></txp:link_to_home></h1>\r\n\t\t<h2><txp:site_slogan /></h2>\r\n\t</div>\r\n\r\n<!-- left -->\r\n\t<div id=\"sidebar-1\">\r\n\t<txp:linklist wraptag=\"p\" />\r\n\t</div>\r\n\r\n<!-- right -->\r\n\t<div id=\"sidebar-2\">\r\n\t\t<txp:search_input label=\"Search\" wraptag=\"p\" />\r\n\r\n\t\t<txp:popup type=\"c\" label=\"Browse\" wraptag=\"p\" />\r\n\r\n\t\t<p><txp:feed_link label=\"RSS\" /> / <txp:feed_link flavor=\"atom\" label=\"Atom\" /></p>\r\n\r\n\t\t<p><img src=\"<txp:site_url />textpattern/txp_img/txp_slug105x45.gif\" width=\"105\" height=\"45\" alt=\"Textpattern\" title=\"\" /></p>\r\n\t</div>\r\n\r\n<!-- center -->\r\n\t<div id=\"content\">\r\n\t<txp:article limit=\"5\" />\r\n\t\r\n<txp:if_individual_article>\r\n\t\t<p><txp:link_to_prev><txp:prev_title /></txp:link_to_prev> \r\n\t\t\t<txp:link_to_next><txp:next_title /></txp:link_to_next></p>\r\n<txp:else />\r\n\t\t<p><txp:older><txp:text item=\"older\" /></txp:older> \r\n\t\t\t<txp:newer><txp:text item=\"newer\" /></txp:newer></p>\r\n</txp:if_individual_article>\r\n\t</div>\r\n\r\n<!-- footer -->\r\n\t<div id=\"foot\">&nbsp;</div>\r\n\r\n</div>\r\n\r\n</body>\r\n</html>"));
		}
	}
	
	function txp_discuss_table()
	{
		$table = new txp_discuss_table();
		
		if (!$table->row(array('discussid' => 000001))) {
			$table->insert(array(
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
	
	function txp_form_table()
	{
		$table = new txp_form_table();
		if (!$table->row(array('name' => 'Links'))) {
			$table->insert(array('name' => 'Links', 'type' => 'link', 'Form' => "<p><txp:link /><br />\r\n<txp:link_description /></p>"));
		}
		
		if (!$table->row(array('name' => 'lofi'))) {
			$table->insert(array('name' => 'lofi', 'type' => 'article', 'Form' => "<h3><txp:title /></h3>\r\n\r\n<p class=\"small\"><txp:permlink>#</txp:permlink> <txp:posted /></p>\r\n\r\n<txp:body />\r\n\r\n<hr />"));
		}
		
		if (!$table->row(array('name' => 'single'))) {
			$table->insert(array('name' => 'single', 'type' => 'article', 'Form' => "<h3><txp:title /> <span class=\"permlink\"><txp:permlink>::</txp:permlink></span> <span class=\"date\"><txp:posted /></span></h3>\r\n\r\n<txp:body />"));
		}
		
		if (!$table->row(array('name' => 'plainlinks'))) {
			$table->insert(array('name' => 'plainlinks', 'type' => 'link', 'Form' => "<txp:linkdesctitle /><br />"));
		}
		
		if (!$table->row(array('name' => 'comments'))) {
			$table->insert(array('name' => 'comments', 'type' => 'comment', 'Form' => "<txp:message />\r\n\r\n<p class=\"small\">&#8212; <txp:comment_name /> &#183; <txp:comment_time /> &#183; <txp:comment_permlink>#</txp:comment_permlink></p>"));
		}
		
		if (!$table->row(array('name' => 'default'))) {
			$table->insert(array('name' => 'default', 'type' => 'article', 'Form' => "<h3><txp:permlink><txp:title /></txp:permlink> &#183; <txp:posted /> by <txp:author /></h3>\r\n\r\n<txp:body />\r\n\r\n<txp:comments_invite wraptag=\"p\" />\r\n\r\n<div class=\"divider\"><img src=\"<txp:site_url />images/1.gif\" width=\"400\" height=\"1\" alt=\"---\" title=\"\" /></div>"));
		}
		
		if (!$table->row(array('name' => 'comment_form'))) {
			$table->insert(array('name' => 'comment_form', 'type' => 'comment', 'Form' => "<table cellpadding=\"4\" cellspacing=\"0\" border=\"0\">\r\n\r\n<tr>\r\n\t<td align=\"right\">\r\n\t\t<label for=\"name\"><txp:text item=\"name\" /></label>\r\n\t</td>\r\n\r\n\t<td>\r\n\t\t<txp:comment_name_input />\r\n\t</td>\r\n\r\n\t<td>\r\n\t\t<txp:comment_remember />\r\n\t</td> \r\n</tr>\r\n\r\n<tr>\r\n\t<td align=\"right\">\r\n\t\t<label for=\"email\"><txp:text item=\"email\" /></label>\r\n\t</td>\r\n\r\n\t<td colspan=\"2\">\r\n\t\t<txp:comment_email_input />\r\n\t</td>\r\n</tr>\r\n\r\n<tr> \r\n\t<td align=\"right\">\r\n\t\t<label for=\"web\">http://</label>\r\n\t</td>\r\n\r\n\t<td colspan=\"2\">\r\n\t\t<txp:comment_web_input />\r\n\t</td>\r\n</tr>\r\n\r\n<tr>\r\n\t<td align=\"right\">\r\n\t\t<label for=\"message\"><txp:text item=\"message\" /></label>\r\n\t</td>\r\n\r\n\t<td colspan=\"2\">\r\n\t\t<txp:comment_message_input />\r\n\t</td>\r\n</tr>\r\n\r\n<tr>\r\n\t<td align=\"right\">&nbsp;</td>\r\n\r\n\t<td>\r\n\t\t<txp:comments_help />\r\n\t</td>\r\n\r\n\t<td align=\"right\">\r\n\t\t<txp:comment_preview />\r\n\t\t<txp:comment_submit />\r\n\t</td>\r\n</tr>\r\n\r\n</table>"));
		}
		
		if (!$table->row(array('name' => 'noted'))) {
			$table->insert(array('name' => 'noted', 'type' => 'link', 'Form' => "<p><txp:link />. <txp:link_description /></p>"));
		}
		
		if (!$table->row(array('name' => 'popup_comments'))) {
			$table->insert(array('name' => 'popup_comments', 'type' => 'comment', 'Form' => "<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.0 Transitional//EN\" \"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd\">\r\n<html xmlns=\"http://www.w3.org/1999/xhtml\" xml:lang=\"en\" lang=\"en\">\r\n<head>\r\n\t<meta http-equiv=\"Content-Type\" content=\"text/html; charset=utf-8\" />\r\n\t<title><txp:page_title /></title>\r\n\t<link rel=\"stylesheet\" type=\"text/css\" href=\"<txp:css />\" />\r\n</head>\r\n<body>\r\n\r\n<div style=\"padding: 1em; width:300px;\">\r\n<txp:popup_comments />\r\n</div>\r\n\r\n</body>\r\n</html>"));
		}
		
		if (!$table->row(array('name' => 'files'))) {
			$table->insert(array('name' => 'files', 'type' => 'file', 'Form' => "<txp:text item=\"file\" />: \n<txp:file_download_link>\n<txp:file_download_name /> [<txp:file_download_size format=\"auto\" decimals=\"2\" />]\n</txp:file_download_link>\n<br />\n<txp:text item=\"category\" />: <txp:file_download_category /><br />\n<txp:text item=\"download\" />: <txp:file_download_downloads />"));
		}
		
		if (!$table->row(array('name' => 'search_results'))) {
			$table->insert(array('name' => 'search_results', 'type' => 'article', 'Form' => "<h3><txp:search_result_permlink><txp:search_result_title /></txp:search_result_permlink></h3>\r\n\r\n<p><txp:search_result_excerpt /></p>\r\n\r\n<p class=\"small\"><txp:search_result_permlink><txp:search_result_permlink /></txp:search_result_permlink> &#183; \r\n\t<txp:search_result_date /></p>"));
		}
		
		if (!$table->row(array('name' => 'comments_display'))) {
			$table->insert(array('name' => 'comments_display', 'type' => 'article', 'Form' => "<h3 id=\"comment\"><txp:comments_invite textonly=\"1\" showalways=\"1\" showcount=\"0\" /></h3>\r\n\r\n<txp:comments />\r\n\r\n<txp:if_comments_preview>\r\n<div id=\"cpreview\">\r\n<txp:comments_preview />\r\n</div>\r\n</txp:if_comments_preview>\r\n\r\n<txp:if_comments_allowed>\r\n<txp:comments_form preview=\"1\" />\r\n<txp:else />\r\n<p><txp:text item=\"comments_closed\" /></p>\r\n</txp:if_comments_allowed>"));
		}
	}
	
	function txp_link_table()
	{
		$table = new txp_link_table();
		if(!$table->row(array('id' => 1))){
			$table->insert(array('id' => 1, 'date' => '2005-07-20 12:54:26', 'category' => 'textpattern', 'url' => 'http://textpattern.com/', 'linkname' => 'Textpattern', 'linksort' => 'Textpattern', 'description' => ''));
		}
		
		if(!$table->row(array('id' => 2))){
			$table->insert(array('id' => 2, 'date' => '2005-07-20 12:54:41', 'category' => 'textpattern', 'url' => 'http://textpattern.net/', 'linkname' => 'TextBook', 'linksort' => 'TextBook', 'description' => ''));
		}
				
		if(!$table->row(array('id' => 3))){
			$table->insert(array('id' => 3, 'date' => '2005-07-20 12:55:04', 'category' => 'textpattern', 'url' => 'http://textpattern.org/', 'linkname' => 'Txp Resources', 'linksort' => 'Txp Resources', 'description' => ''));
		}
	}
	
	function txp_prefs_table()
	{
		$table = new txp_prefs_table();
		# Default to messy URLs if we know clean ones won't work
		$permlink_mode = 'section_id_title';
		if (is_callable('apache_get_modules')) {
			$modules = apache_get_modules();
			if (!in_array('mod_rewrite', $modules))
			$permlink_mode = 'messy';
		}
		else {
			$server_software = (@$_SERVER['SERVER_SOFTWARE'] || @$_SERVER['HTTP_HOST'])
			? ( (@$_SERVER['SERVER_SOFTWARE']) ?  @$_SERVER['SERVER_SOFTWARE'] :  $_SERVER['HTTP_HOST'] )
			: '';
			if (!stristr($server_software, 'Apache'))
			$permlink_mode = 'messy';
		}
		
		$setup_comment_invite = addslashes( ( gTxt('setup_comment_invite')=='setup_comment_invite') ? 'Comment' : gTxt('setup_comment_invite') );
		
		require_once txpath.'/lib/txplib_prefs.php';
		$prefs = get_default_prefs();
		$prefs['blog_uid'] = md5(uniqid(rand(),true));
/*		echo '<pre>';
		echo var_dump($prefs);
		echo '</pre>';*/
		$preferences = array();
		# public prefs:
		$preferences[] = array('name' => 'sitename', 'val' => gTxt('my_site'), 'type' => 0, 'event' => 'publish', 'html' => 'text_input', 'position' => 10);
		$preferences[] = array('name' => 'siteurl', 'val' => 'comment.local', 'type' => 0, 'event' => 'publish', 'html' => 'text_input', 'position' => 20);
		$preferences[] = array('name' => 'site_slogan', 'val' => gTxt('my_slogan'), 'type' => 0, 'event' => 'publish', 'html' => 'text_input', 'position' => 30);
		$preferences[] = array('name' => 'is_dst', 'val' => '0', 'type' => 0, 'event' => 'publish', 'html' => 'yesnoradio', 'position' => 60);
		$preferences[] = array('name' => 'dateformat', 'val' => 'since', 'type' => 0, 'event' => 'publish', 'html' => 'dateformats', 'position' => 70);
		$preferences[] = array('name' => 'archive_dateformat', 'val' => '%b %d, %I:%M %p', 'type' => 0, 'event' => 'publish', 'html' => 'dateformats', 'position' => 80);
		$preferences[] = array('name' => 'permlink_mode', 'val' => $permlink_mode, 'type' => 0, 'event' => 'publish', 'html' => 'permlinkmodes', 'position' => 90);
		$preferences[] = array('name' => 'logging', 'val' => 'all', 'type' => 0, 'event' => 'publish', 'html' => 'logging', 'position' => 100);
		$preferences[] = array('name' => 'use_textile', 'val' => '2', 'type' => 0, 'event' => 'publish', 'html' => 'pref_text', 'position' => 110);
		$preferences[] = array('name' => 'use_comments', 'val' => '1', 'type' => 0, 'event' => 'publish', 'html' => 'yesnoradio', 'position' => 120);
		$preferences[] = array('name' => 'production_status', 'val' => 'testing', 'type' => 0, 'event' => 'publish', 'html' => 'prod_levels', 'position' => 210);
		# public comments prefs:
		$preferences[] = array('name' => 'comments_moderate', 'val' => '1', 'type' => 0, 'event' => 'comments', 'html' => 'yesnoradio', 'position' => 130);
		$preferences[] = array('name' => 'comments_on_default', 'val' => '0', 'type' => 0, 'event' => 'comments', 'html' => 'yesnoradio', 'position' => 140);
		$preferences[] = array('name' => 'comments_are_ol', 'val' => '1', 'type' => 0, 'event' => 'comments', 'html' => 'yesnoradio', 'position' => 150);
		$preferences[] = array('name' => 'comments_sendmail', 'val' => '0', 'type' => 0, 'event' => 'comments', 'html' => 'yesnoradio', 'position' => 160);
		$preferences[] = array('name' => 'comments_disallow_images', 'val' => '0', 'type' => 0, 'event' => 'comments', 'html' => 'yesnoradio', 'position' => 170);
		$preferences[] = array('name' => 'comments_default_invite', 'val' => $setup_comment_invite, 'type' => 0, 'event' => 'comments', 'html' => 'text_input', 'position' => 180);
		$preferences[] = array('name' => 'comments_dateformat', 'val' => '%b %d, %I:%M %p', 'type' => 0, 'event' => 'comments', 'html' => 'dateformats', 'position' => 190);
		$preferences[] = array('name' => 'comments_mode', 'val' => '0', 'type' => 0, 'event' => 'comments', 'html' => 'commentmode', 'position' => 200);
		$preferences[] = array('name' => 'comments_disabled_after', 'val' => '42', 'type' => 0, 'event' => 'comments', 'html' => 'weeks', 'position' => 210);
		$preferences[] = array('name' => 'comments_auto_append', 'val' => '1', 'type' => 0, 'event' => 'comments', 'html' => 'yesnoradio', 'position' => 211);		
		# admin prefs:
		$preferences[] = array('name' => 'ping_weblogsdotcom', 'val' => '0', 'type' => 1, 'event' => 'publish', 'html' => 'yesnoradio', 'position' => 0);
		$preferences[] = array('name' => 'rss_how_many', 'val' => '5', 'type' => 1, 'event' => 'admin', 'html' => 'text_input', 'position' => 0);
		$preferences[] = array('name' => 'send_lastmod', 'val' => '0', 'type' => 1, 'event' => 'admin', 'html' => 'yesnoradio', 'position' => 0);
		$preferences[] = array('name' => 'img_dir', 'val' => 'images', 'type' => 1, 'event' => 'admin', 'html' => 'text_input', 'position' => 0);
		$preferences[] = array('name' => 'file_max_upload_size', 'val' => '2000000', 'type' => 1, 'event' => 'admin', 'html' => 'text_input', 'position' => 0);
		$preferences[] = array('name' => 'tempdir', 'val' => find_temp_dir(), 'type' => 1, 'event' => 'admin', 'html' => 'text_input', 'position' => 0);
		$preferences[] = array('name' => 'file_base_path', 'val' => dirname(txpath).DS.'files', 'type' => 1, 'event' => 'admin', 'html' => 'text_input', 'position' => 0);
		$preferences[] = array('name' => 'edit_raw_css_by_default', 'val' => '1', 'type' => 1, 'event' => 'css', 'html' => 'yesnoradio', 'position' => 0);
		$preferences[] = array('name' => 'allow_page_php_scripting', 'val' => '1', 'type' => 1, 'event' => 'publish', 'html' => 'yesnoradio', 'position' => 0);
		$preferences[] = array('name' => 'allow_article_php_scripting', 'val' => '1', 'type' => 1, 'event' => 'publish', 'html' => 'yesnoradio', 'position' => 0);
		$preferences[] = array('name' => 'allow_raw_php_scripting', 'val' => '0', 'type' => 1, 'event' => 'publish', 'html' => 'yesnoradio', 'position' => 0);
		$preferences[] = array('name' => 'textile_links', 'val' => '0', 'type' => 1, 'event' => 'link', 'html' => 'yesnoradio', 'position' => 0);
		$preferences[] = array('name' => 'show_comment_count_in_feed', 'val' => '1', 'type' => 1, 'event' => 'publish', 'html' => 'yesnoradio', 'position' => 0);
		$preferences[] = array('name' => 'syndicate_body_or_excerpt', 'val' => '1', 'type' => 1, 'event' => 'publish', 'html' => 'yesnoradio', 'position' => 0);
		$preferences[] = array('name' => 'include_email_atom', 'val' => '1', 'type' => 1, 'event' => 'publish', 'html' => 'yesnoradio', 'position' => 0);
		$preferences[] = array('name' => 'comment_means_site_updated', 'val' => '1', 'type' => 1, 'event' => 'publish', 'html' => 'yesnoradio', 'position' => 0);
		$preferences[] = array('name' => 'never_display_email', 'val' => '0', 'type' => 1, 'event' => 'publish', 'html' => 'yesnoradio', 'position' => 0);
		$preferences[] = array('name' => 'comments_require_name', 'val' => '1', 'type' => 1, 'event' => 'comments', 'html' => 'yesnoradio', 'position' => 0);
		$preferences[] = array('name' => 'comments_require_email', 'val' => '1', 'type' => 1, 'event' => 'comments', 'html' => 'yesnoradio', 'position' => 0);
		$preferences[] = array('name' => 'articles_use_excerpts', 'val' => '1', 'type' => 1, 'event' => 'publish', 'html' => 'yesnoradio', 'position' => 0);
		$preferences[] = array('name' => 'allow_form_override', 'val' => '1', 'type' => 1, 'event' => 'publish', 'html' => 'yesnoradio', 'position' => 0);
		$preferences[] = array('name' => 'attach_titles_to_permalinks', 'val' => '1', 'type' => 1, 'event' => 'publish', 'html' => 'yesnoradio', 'position' => 0);
		$preferences[] = array('name' => 'permalink_title_format', 'val' => '1', 'type' => 1, 'event' => 'publish', 'html' => 'yesnoradio', 'position' => 0);
		$preferences[] = array('name' => 'expire_logs_after', 'val' => '7', 'type' => 1, 'event' => 'publish', 'html' => 'text_input', 'position' => 0);
		$preferences[] = array('name' => 'use_plugins', 'val' => '1', 'type' => 1, 'event' => 'publish', 'html' => 'yesnoradio', 'position' => 0);
		$preferences[] = array('name' => 'custom_1_set', 'val' => 'custom1', 'type' => 1, 'event' => 'custom', 'html' => 'text_input', 'position' => 1);
		$preferences[] = array('name' => 'custom_2_set', 'val' => 'custom2', 'type' => 1, 'event' => 'custom', 'html' => 'text_input', 'position' => 2);
		$preferences[] = array('name' => 'custom_3_set', 'val' => '', 'type' => 1, 'event' => 'custom', 'html' => 'text_input', 'position' => 3);
		$preferences[] = array('name' => 'custom_4_set', 'val' => '', 'type' => 1, 'event' => 'custom', 'html' => 'text_input', 'position' => 4);
		$preferences[] = array('name' => 'custom_5_set', 'val' => '', 'type' => 1, 'event' => 'custom', 'html' => 'text_input', 'position' => 5);
		$preferences[] = array('name' => 'custom_6_set', 'val' => '', 'type' => 1, 'event' => 'custom', 'html' => 'text_input', 'position' => 6);
		$preferences[] = array('name' => 'custom_7_set', 'val' => '', 'type' => 1, 'event' => 'custom', 'html' => 'text_input', 'position' => 7);
		$preferences[] = array('name' => 'custom_8_set', 'val' => '', 'type' => 1, 'event' => 'custom', 'html' => 'text_input', 'position' => 8);
		$preferences[] = array('name' => 'custom_9_set', 'val' => '', 'type' => 1, 'event' => 'custom', 'html' => 'text_input', 'position' => 9);
		$preferences[] = array('name' => 'custom_10_set', 'val' => '', 'type' => 1, 'event' => 'custom', 'html' => 'text_input', 'position' => 10);
		$preferences[] = array('name' => 'ping_textpattern_com', 'val' => '1', 'type' => 1, 'event' => 'publish', 'html' => 'yesnoradio', 'position' => 0);
		$preferences[] = array('name' => 'use_dns', 'val' => '1', 'type' => 1, 'event' => 'publish', 'html' => 'yesnoradio', 'position' => 0);
		$preferences[] = array('name' => 'admin_side_plugins', 'val' => '1', 'type' => 1, 'event' => 'publish', 'html' => 'yesnoradio', 'position' => 0);
		$preferences[] = array('name' => 'comment_nofollow', 'val' => '1', 'type' => 1, 'event' => 'publish', 'html' => 'yesnoradio', 'position' => 0);
		$preferences[] = array('name' => 'use_mail_on_feeds_id', 'val' => '0', 'type' => 1, 'event' => 'publish', 'html' => 'yesnoradio', 'position' => 0);
		$preferences[] = array('name' => 'max_url_len', 'val' => '200', 'type' => 1, 'event' => 'publish', 'html' => 'text_input', 'position' => 0);
		$preferences[] = array('name' => 'spam_blacklists', 'val' => 'sbl.spamhaus.org', 'type' => 1, 'event' => 'publish', 'html' => 'text_input', 'position' => 0);
		$preferences[] = array('name' => 'override_emailcharset', 'val' => '0', 'type' => 1, 'event' => 'admin', 'html' => 'yesnoradio', 'position' => 21);
		# hidden prefs:
		$preferences[] = array('name' => 'prefs_id', 'val' => '1', 'type' => 2, 'event' => 'publish', 'html' => 'text_input', 'position' => 0);
		$preferences[] = array('name' => 'use_categories', 'val' => '1', 'type' => 2, 'event' => 'publish', 'html' => 'text_input', 'position' => 0);
		$preferences[] = array('name' => 'use_sections', 'val' => '1', 'type' => 2, 'event' => 'publish', 'html' => 'text_input', 'position' => 0);
		$preferences[] = array('name' => 'path_from_root', 'val' => '/', 'type' => 2, 'event' => 'publish', 'html' => 'text_input', 'position' => 0);
		$preferences[] = array('name' => 'language', 'val' => 'en-gb', 'type' => 2, 'event' => 'publish', 'html' => 'languages', 'position' => 40);
		$preferences[] = array('name' => 'url_mode', 'val' => '1', 'type' => 2, 'event' => 'publish', 'html' => 'text_input', 'position' => 0);
		$preferences[] = array('name' => 'timeoffset', 'val' => '0', 'type' => 2, 'event' => 'publish', 'html' => 'text_input', 'position' => 0);
		$preferences[] = array('name' => 'lastmod', 'val' => '2005-07-23 16:24:10', 'type' => 2, 'event' => 'publish', 'html' => 'text_input', 'position' => 0);
		$preferences[] = array('name' => 'file_list_pageby', 'val' => '25', 'type' => 2, 'event' => 'publish', 'html' => 'text_input', 'position' => 0);
		$preferences[] = array('name' => 'path_to_site', 'val' => '', 'type' => 2, 'event' => 'publish', 'html' => 'text_input', 'position' => 0);
		$preferences[] = array('name' => 'article_list_pageby', 'val' => '25', 'type' => 2, 'event' => 'publish', 'html' => 'text_input', 'position' => 0);
		$preferences[] = array('name' => 'link_list_pageby', 'val' => '25', 'type' => 2, 'event' => 'publish', 'html' => 'text_input', 'position' => 0);
		$preferences[] = array('name' => 'image_list_pageby', 'val' => '25', 'type' => 2, 'event' => 'publish', 'html' => 'text_input', 'position' => 0);
		$preferences[] = array('name' => 'log_list_pageby', 'val' => '25', 'type' => 2, 'event' => 'publish', 'html' => 'text_input', 'position' => 0);
		$preferences[] = array('name' => 'comment_list_pageby', 'val' => '25', 'type' => 2, 'event' => 'publish', 'html' => 'text_input', 'position' => 0);
		$preferences[] = array('name' => 'locale', 'val' => 'en_GB.UTF-8', 'type' => 2, 'event' => 'publish', 'html' => 'text_input', 'position' => 0);
		$preferences[] = array('name' => 'blog_uid', 'val' => $prefs['blog_uid'], 'type' => 2, 'event' => 'publish', 'html' => 'text_input', 'position' => 0);
		$preferences[] = array('name' => 'blog_mail_uid', 'val' => $_POST['email'], 'type' => 2, 'event' => 'publish', 'html' => 'text_input', 'position' => 0);
		$preferences[] = array('name' => 'blog_time_uid', 'val' => '2005', 'type' => 2, 'event' => 'publish', 'html' => 'text_input', 'position' => 0);
		$preferences[] = array('name' => 'show_article_category_count', 'val' => '1', 'type' => 2, 'event' => 'category', 'html' => 'yesnoradio', 'position' => 0);
		$preferences[] = array('name' => 'dbupdatetime', 'val' => '1122194504', 'type' => 2, 'event' => 'publish', 'html' => 'text_input', 'position' => 0);
		$preferences[] = array('name' => 'version', 'val' => '1.0rc4', 'type' => 2, 'event' => 'publish', 'html' => 'text_input', 'position' => 0);
		
		foreach ($preferences as $preference){
			if(!$table->row(array('name' => $preference['name']))){
				$table->insert($preference);
			}
		}
	}
}



?>