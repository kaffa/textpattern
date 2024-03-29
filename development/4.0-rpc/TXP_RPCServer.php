<?php

/**
 * XML-RPC Server for Textpattern 4.0.x
 * http://txp.kusor.com/rpc-api
 * (C)2005-2006 The Textpattern Development Team - http://textpattern.com
 * @author Pedro Palazón - http://kusor.com
 */

class TXP_RPCServer extends IXR_IntrospectionServer 
{			
	# save debug logs on tmp dir if debug enabled
	var $debug = false;
	
	function TXP_RPCServer()
	{
		$this->IXR_IntrospectionServer();
		# Add API Methods as callbacks:
		
		# Blogger API [http://www.blogger.com/developers/api/] - add as server capability
		$this->capabilities['bloggerAPI'] = array(
			'specUrl' => 'http://www.blogger.com/developers/api/',
			'specVersion' => 2
		);
		
		$this->addCallback(
			'blogger.newPost',
			'this:blogger_newPost',
			array('int', 'string', 'string', 'string', 'string', 'string', 'boolean'),
			'makes a new post to a designated blog'
		);
		$this->addCallback(
			'blogger.editPost',
			'this:blogger_editPost',
			array('boolean', 'string', 'string', 'string', 'string', 'string', 'boolean'),
			'changes the contents of a given post'
		);
		$this->addCallback(
			'blogger.getUsersBlogs',
			'this:blogger_getUsersBlogs',
			array('struct', 'string', 'string', 'string'),
			'return information about all the blogs a given user is member of'
		);
		$this->addCallback(
			'blogger.getUserInfo',
			'this:blogger_getUserInfo',
			array('struct', 'string', 'string', 'string'),
			'return information about the current user'
		);
		$this->addCallback(
			'blogger.getTemplate',
			'this:blogger_getTemplate',
			array('string', 'string', 'string', 'string', 'string', 'string'),
			'return section template - main will return default template, archiveIndex will return section template'
		);
		$this->addCallback(
			'blogger.setTemplate',
			'this:blogger_setTemplate',
			array('boolean', 'string', 'string', 'string', 'string', 'string', 'string'),
			'updates section template - main=default template, archiveIndex=section template'
		);
		
		# non-official Blogger API methods - supported by XML-RPC clients as BloggerAPI2
		# Place all this info on a public URI
		$this->addCallback(
			'blogger.getPost',
			'this:blogger_getPost',
			array('struct', 'string', 'string', 'string', 'string'),
			'retrieves contents for the given postid'
		);
		$this->addCallback(
			'blogger.deletePost',
			'this:blogger_deletePost',
			array('boolean', 'string', 'string', 'string', 'string', 'boolean'),
			'deletes a given post'
		);
		$this->addCallback(
			'blogger.getRecentPosts',
			'this:blogger_getRecentPosts',
			array('array', 'string', 'string', 'string', 'string', 'int'),
			'retrieves a list of posts (default 10)'
		);
		
		# metaWeblog API[http://www.xmlrpc.com/metaWeblogApi] - add as server capability
		$this->capabilities['metaWeblog API'] = array(
			'specUrl' => 'http://www.xmlrpc.com/metaWeblogApi',
			'specVersion' => 1
		);
		# Implements also MovableType extension of the API methods
		$this->addCallback(
			'metaWeblog.getPost',
			'this:metaWeblog_getPost',
			array('struct','string','string','string'),
			'retrieves contents for the given postid'
		);
		$this->addCallback(
			'metaWeblog.newPost',
			'this:metaWeblog_newPost',
			array('string','string', 'string', 'string', 'struct', 'boolean'),
			'creates a new post'
		);
		$this->addCallback(
			'metaWeblog.editPost',
			'this:metaWeblog_editPost',
			array('boolean','string', 'string', 'string', 'struct', 'boolean'),
			'creates a new post'
		);
		$this->addCallback(
			'metaWeblog.getCategories',
			'this:metaWeblog_getCategories',
			array('struct','string', 'string', 'string'),
			'retrieves a list of categories for the current blog'
		);
		
		$this->addCallback(
			'metaWeblog.getRecentPosts',
			'this:metaWeblog_getRecentPosts',
			array('array','string', 'string', 'string', 'int'),
			'retrieves a given number of recent posts'
		);
		# TO-DO: metaWeblog.newMediaObject (blogid, username, password, struct) returns struct
		
		# MovableType API[] - add as server capability
		$this->capabilities['MovableType API'] = array(
			'specUrl' => 'http://www.sixapart.com/movabletype/docs/mtmanual_programmatic.html#xmlrpc%20api',
			'specVersion' => 1
		);
		# Not completelly implemented.
		$this->addCallback(
			'mt.getRecentPostTitles',
			'this:mt_getRecentPostTitles',
			array('array','string','string','string','id'),
			'returns a bandwidth-friendly list of the most recent posts in the system'
		);
		$this->addCallback(
			'mt.getCategoryList',
			'this:mt_getCategoryList',
			array('array','string','string','string'),
			'returns a list of all categories defined in the weblog'
		);
		$this->addCallback(
			'mt.supportedMethods',
			'this:listMethods',
			array('array'),
			'return the XML-RPC Methods supported by the server(Redundant).'
		);
		$this->addCallback(
			'mt.supportedTextFilters',
			'this:mt_supportedTextFilters',
			array('array'),
			'return the format filters suported by the server.'
		);
		$this->addCallback(
			'mt.getPostCategories',
			'this:mt_getPostCategories',
			array('array','string','string','string'),
			'returns a list of all categories for the given article'
		);
		$this->addCallback(
			'mt.setPostCategories',
			'this:mt_setPostCategories',
			array('boolean','string','string','string','array'),
			'sets categories for the given article'
		);
		$this->addCallback(
			'mt.publishPost',
			'this:mt_publishPost',
			array('boolean','string','string','string'),
			'changes the status of the current article to published'
		);		
				
		
	}

	# Override serve method in order to keep requests logs too
	# while dealing with unknown clients
	function serve($data = false) {
        if (!$data) {
            global $HTTP_RAW_POST_DATA;            
            
            if (!$HTTP_RAW_POST_DATA) {
               die('XML-RPC server accepts POST requests only.');
            }            
            
            $rx = '/<?xml.*encoding=[\'"](.*?)[\'"].*?>/m';            
            
            #first, handle the known UAs in order to serve proper content
        	if (strpos('w.bloggar',$_SERVER['HTTP_USER_AGENT'])!==false){
        		$encoding = 'iso-8859-1';
        	# find for supplied encoding before to try other things
        	}elseif (preg_match($rx, $HTTP_RAW_POST_DATA, $xml_enc)) {
				$encoding = strtolower($xml_enc[1]);
			# try utf-8 detect
			}elseif(preg_match('/^([\x00-\x7f]|[\xc2-\xdf][\x80-\xbf]|\xe0[\xa0-\xbf][\x80-\xbf]|[\xe1-\xec][\x80-\xbf]{2}|\xed[\x80-\x9f][\x80-\xbf]|[\xee-\xef][\x80-\xbf]{2}|f0[\x90-\xbf][\x80-\xbf]{2}|[\xf1-\xf3][\x80-\xbf]{3}|\xf4[\x80-\x8f][\x80-\xbf]{2})*$/', $HTTP_RAW_POST_DATA) === 1) {
				$encoding = 'utf-8';
			# otherwise, use iso-8859-1
			}else {
				$encoding = 'iso-8859-1';
			}

            switch ($encoding)
            {
            	case 'utf-8':
            		$data = $HTTP_RAW_POST_DATA;
            	break;
            	case 'iso-8859-1':
            		# this will fails on parser if utf8_encode is unavailiable
            		$data = (function_exists('utf8_encode') && is_callable('utf8_encode'))? utf8_encode($HTTP_RAW_POST_DATA) : $HTTP_RAW_POST_DATA;
            	break;
            	default:
            		# this will fails on parser if mb_convert_encoding is unavailiable
            		$data = (function_exists('mb_convert_encoding') && is_callable('mb_convert_encoding'))? mb_convert_encoding($HTTP_RAW_POST_DATA, 'utf-8', $encoding) : $HTTP_RAW_POST_DATA;
            	break;
            }

        }
        $this->message = new IXR_Message($data);
        if (!$this->message->parse()) {
            $this->error(-32700, 'parse error. not well formed');
        }
        if ($this->message->messageType != 'methodCall') {
            $this->error(-32600, 'server error. invalid xml-rpc. not conforming to spec. Request must be a methodCall');
        }
        $result = $this->call($this->message->methodName, $this->message->params);
        # Is the result an error?
        if (is_a($result, 'IXR_Error')) {
            $this->error($result);
        }
        # Encode the result
        $r = new IXR_Value($result);
        $resultxml = $r->getXml();
        # Create the XML
        $xml = <<<EOD
<methodResponse>
  <params>
    <param>
      <value>
        $resultxml
      </value>
    </param>
  </params>
</methodResponse>

EOD;
        # Added for testing purposes only
        if (!isset($encoding)) $encoding = 'utf-8';
        # Send it
        return $this->output($xml, $encoding);
        
    }
    
	#Override default utf-8 output, if needed
	function output($xml,$enc='utf-8') {
	
		# Be kind with non-utf-8 capable clients
		if ($enc != 'utf-8')
		{
			if ($enc=='iso-8859-1' && function_exists('utf8_decode') && is_callable('utf8_decode'))
			{
				$xml = utf8_decode($xml);
			}elseif (function_exists('mb_convert_encoding') && is_callable('mb_convert_encoding')){
				$xml = mb_convert_encoding($xml, $enc, 'utf-8');
			}else {
				# if no decoding possible, serve contents as utf-8
				$enc = 'utf-8';
			}
		}
		
		$xml = "<?xml version=\"1.0\" encoding=\"$enc\" ?>"."\n".$xml;
		$length = strlen($xml);
		header('Connection: close');
		header('Content-Length: '.$length);
		header("Content-Type: text/xml; charset=$enc");
		header('Date: '.date('r'));        
		echo $xml;
		exit;
	}
	
	# Blogger API
	
	function blogger_newPost($params)
	{
		list(/*$appkey*/,$blogid, $username, $password, $content, $publish) = $params;
		
		$txp = new TXP_Wrapper($username, $password);
		if (!$txp->loggedin)
		{
			return new IXR_Error(100, gTxt('bad_login'));
		}
		
		$contents = $this->_getBloggerContents($content);
		
		$contents['Section'] = $blogid;
		$contents['Status'] = ($publish)? '4' : '1';
		
		$rs = $txp->newArticle($contents);
		
		if ($rs){
			return intval($rs);
		}
		
		return new IXR_Error(201, gTxt('problem_creating_article'));
	}
	
	function blogger_editPost($params)
	{
		list(/*$appkey*/,$postid, $username, $password, $content, $publish) = $params;
		
		$txp = new TXP_Wrapper($username, $password);
		if (!$txp->loggedin)
		{
			return new IXR_Error(100, gTxt('bad_login'));
		}
		
		$id = $txp->getArticleID($postid, 'ID');
		if (!$id) return new IXR_Error(404, gTxt('invalid_article_id'));
		
		$contents = $this->_getBloggerContents($content);		
		
		$contents['Status'] = ($publish)? '4' : '1';		
		
		$rs = $txp->updateArticleID($postid, $contents);
				
		if (!$rs) return new IXR_Error(202, gTxt('problem_updating_article'));
		
		return true;
	}
	
	function blogger_getUsersBlogs($params)
	{
		list(/*$appkey*/,$username, $password) = $params;
		
		global $permlink_mode;
		
		$txp = new TXP_Wrapper($username, $password);
		if (!$txp->loggedin)
		{
			return new IXR_Error(100, gTxt('bad_login'));
		}
		
		$rs = $txp->getSectionsList();
		if (!$rs) return new IXR_Error(203, gTxt('problem_retrieving_sections'));
		
		$sections = array();
		foreach ($rs as $section)
		{
			$sections[]= array(
				'blogid'  => $section['name'],
				'blogName'=> $section['title'],
				'url' => pagelinkurl(array('s'=>$section['name']))
			);
		}
		
		return $sections;
	}
	
	function blogger_getUserInfo($params)
	{
		list(/*$appkei*/,$username, $password) = $params;
		
		$txp = new TXP_Wrapper($username, $password);
		
		if (!$txp->loggedin)
		{
			return new IXR_Error(100, gTxt('bad_login'));
		}
		
		$rs = $txp->getUser();
		if (!$rs) return new IXR_Error(204, gTxt('unable_retrieve_user'));
		
		extract($rs);
		
		if (strpos($RealName, ' ')!=0){
			list($firstname, $lastname) = split(" ", $RealName);
		}else{
			$firstname =  $RealName;
			$lastname = '';
		}
		
		$uinfo = array(
			'userid'    => $user_id,
			'firstname' => $firstname,
			'lastname'  => $lastname,
			'nickname'  => $name,
			'email'     => $email,
			'url'		=> hu
		);
		
		return $uinfo;		
	}
	
	function blogger_getTemplate($params)
	{
		list(/*$appkei*/, $blogid, $username, $password, $templateType) = $params;
		$txp = new TXP_Wrapper($username, $password);
		
		if (!$txp->loggedin)
		{
			return new IXR_Error(100, gTxt('bad_login'));
		}
		
		if ($templateType == 'archiveIndex' && $blogid!='default')
		{
			$section = $txp->getSection($blogid);
			if (!$section) return new IXR_Error(208, gTxt('unable_retrieve_template'));
			$name = $section['page'];
		}else {
			$name = 'default';
		}
		
		$rs = $txp->getTemplate($name);
		if (!$rs) return new IXR_Error(208, gTxt('unable_retrieve_template'));
		
		return $rs;
	}
	
	function blogger_setTemplate($params)
	{
		list(/*$appkei*/, $blogid, $username, $password, $template, $templateType) = $params;
		$txp = new TXP_Wrapper($username, $password);
		
		if (!$txp->loggedin)
		{
			return new IXR_Error(100, gTxt('bad_login'));
		}
		
		if ($templateType == 'archiveIndex' && $blogid!='default')
		{
			$section = $txp->getSection($blogid);
			if (!$section) return new IXR_Error(209, gTxt('unable_set_template'));
			$name = $section['page'];
		}else {
			$name = 'default';
		}
		
		$rs = $txp->setTemplate($name, $template);
		if (!$rs) return new IXR_Error(209, gTxt('unable_set_template'));
		
		return true;
	}
	
	# Blogger 2.0
	
	function blogger_getPost($params)
	{
		list(/*$appkei*/, $postid, $username, $password) = $params;
		$txp = new TXP_Wrapper($username, $password);
		
		if (!$txp->loggedin)
		{
			return new IXR_Error(100, gTxt('bad_login'));
		}
		
		$rs = $txp->getArticleID($postid,'ID, Body, AuthorId, unix_timestamp(Posted) as uPosted');
		if (!$rs) return new IXR_Error(205, gTxt('problem_retrieving_article'));
		$out = array(
			'content' => $rs['Body'],
			'userId' => $rs['AuthorId'],
			'postId' => $rs['ID'],
			'dateCreated' => new IXR_Date($rs['uPosted']+tz_offset())
		);
		
		return $out;
	}
	
	function blogger_deletePost($params)
	{
		list(/*$appkei*/, $postid, $username, $password, $publish) = $params;
		$txp = new TXP_Wrapper($username, $password);
		
		if (!$txp->loggedin)
		{
			return new IXR_Error(100, gTxt('bad_login'));
		}
		# Always delete, no matter of publish
		$rs = $txp->deleteArticleID($postid);
		
		if (!$rs) return new IXR_Error(206, gTxt('problem_deleting_article'));
		
		return $rs;
	}
	
	function blogger_getRecentPosts($params)
	{
		list(/*$appkei*/, $blogid, $username, $password, $numberOfPosts) = $params;
		$txp = new TXP_Wrapper($username, $password);
		
		if (!$txp->loggedin)
		{
			return new IXR_Error(100, gTxt('bad_login'));
		}
		
		$articles = $txp->getArticleList('ID, Body, AuthorId, unix_timestamp(Posted) as uPosted', '1', '0', $numberOfPosts);
		
		if (!$articles) return new IXR_Error(207, gTxt('problem_getting_articles'));
		
		foreach ($articles as $rs)
		{
			$out[] = array(
				'content' => $rs['Body'],
				'userId' => $rs['AuthorId'],
				'postId' => $rs['ID'],
				'dateCreated' => new IXR_Date($rs['uPosted']+tz_offset())
			);
		}
		return $out;
	}
	
	# metaWeblog API
	
	function metaWeblog_getPost($params)
	{
		list($postid, $username, $password) = $params;		
		
		$txp = new TXP_Wrapper($username, $password);
		
		if (!$txp->loggedin)
		{
			return new IXR_Error(100, gTxt('bad_login'));
		}
		
		$rs = $txp->getArticleID($postid,'ID, Title, Body, Excerpt, Annotate, Keywords, Section, Category1, Category2, textile_body, url_title, unix_timestamp(Posted) as uPosted');
		if (!$rs) return new IXR_Error(205, gTxt('problem_retrieving_article'));
		
		$out =& $this->_buildMetaWeblogStruct($rs, $txp->txp_user);				
		
		return $out;
	}
	
	function metaWeblog_newPost($params)
	{
		list($blogid, $username, $password, $struct, $publish) = $params;
		
		$txp = new TXP_Wrapper($username, $password);
		
		if (!$txp->loggedin)
		{
			return new IXR_Error(100, gTxt('bad_login'));
		}
		
		$contents = $this->_getMetaWeblogContents($struct, $publish);
		
		$contents['Section'] = $blogid;
				
		$rs = $txp->newArticle($contents);
		
		if ($rs){
			return "$rs";
		}
		
		return new IXR_Error(201, gTxt('problem_creating_article'));
	}
	
	function metaWeblog_editPost($params)
	{
		list($postid, $username, $password, $struct, $publish) = $params;
		
		$txp = new TXP_Wrapper($username, $password);
		
		if (!$txp->loggedin)
		{
			return new IXR_Error(100, gTxt('bad_login'));
		}
				
		$contents = $this->_getMetaWeblogContents($struct, $publish);
		
		$rs = $txp->updateArticleID($postid,$contents);		
		
		if ($rs){
			return true;
		}
		
		return new IXR_Error(201, gTxt('problem_updating_article'));
	}
	function metaWeblog_getCategories($params)
	{
		list($blogid, $username, $password) = $params;
		
		global $permlink_mode;
		
		$txp = new TXP_Wrapper($username, $password);
		
		if (!$txp->loggedin)
		{
			return new IXR_Error(100, gTxt('bad_login'));
		}
		
		$rs = $txp->getCategoryList();
		
		if (!$rs) return new IXR_Error(210, gTxt('problem_retrieving_categories'));
		
		$cats = array();
		
		foreach ($rs as $c)
		{
			$cats[] = array(
				'categoryName'=>$c['name'],
				'description'=>$c['title'],
				'htmlUrl'=>pagelinkurl(array('c'=>$c['name'])),
				'rssUrl'=>hu.'?rss=1&#38;category='.$c['name']
			);
		}
		return $cats;
	}
	
	function metaWeblog_getRecentPosts($params)
	{
		list($blogid, $username, $password, $numberOfPosts) = $params;		
		
		$txp = new TXP_Wrapper($username, $password);
		
		if (!$txp->loggedin)
		{
			return new IXR_Error(100, gTxt('bad_login'));
		}
		
		$articles = $txp->getArticleList('ID, Title, url_title, Body, Excerpt, Annotate, Keywords, Section, Category1, Category2, textile_body, AuthorID, unix_timestamp(Posted) as uPosted', '1', '0', $numberOfPosts);
		
		if (!$articles) return new IXR_Error(207, gTxt('problem_getting_articles'));		
		
		$out = array();
		
		foreach ($articles as $rs){									
			$out[] = $this->_buildMetaWeblogStruct($rs, $txp->txp_user);
		}
		return $out;
	}
	
	# MovableType API
	
	function mt_getRecentPostTitles($params)
	{
		list($blogid, $username, $password, $numberOfPosts) = $params;
		
		global $prefs;
		
		$txp = new TXP_Wrapper($username, $password);
		
		if (!$txp->loggedin)
		{
			return new IXR_Error(100, gTxt('bad_login'));
		}
		
		$articles = $txp->getArticleList('ID, Title, AuthorID, unix_timestamp(Posted) as uPosted', "Section='$blogid'", '0', $numberOfPosts);
		
		if (!$articles) return new IXR_Error(207, gTxt('problem_getting_articles_or_empty_article_list_for_this_blog'));
		
		extract($prefs);
		
		$out = array();
		
		foreach ($articles as $rs){
			
			$out[] = array(
				'userid' => $username,
				'postid' => $rs['ID'],
				'dateCreated' => new IXR_Date($rs['uPosted']+tz_offset()),
				'title'=>$rs['Title'],
			);
			
		}
		return $out;
	}
	
	function mt_getCategoryList($params)
	{
		list($blogid, $username, $password) = $params;
		
		$txp = new TXP_Wrapper($username, $password);
		
		if (!$txp->loggedin)
		{
			return new IXR_Error(100, gTxt('bad_login'));
		}
		
		$rs = $txp->getCategoryList();
		
		if (!$rs) return new IXR_Error(210, gTxt('problem_retrieving_categories'));
		
		$cats = array();
		
		foreach ($rs as $c)
		{
			$cats[] = array(
				'categoryName'=>$c['name'],
				'categoryId'=>$c['id'],
			);
		}
		return $cats;
	}
	
	function mt_supportedTextFilters($params)
	{
		$filters = array(
			array('key'=>'0','label'=>'LEAVE_TEXT_UNTOUCHED'),
			array('key'=>'1','label'=>'USE_TEXTILE'),
			array('key'=>'2','label'=>'CONVERT_LINEBREAKS'),			
		);
		
		return $filters;
	}
	
	function mt_getPostCategories($params)
	{
		list($postid,$username,$password) = $params;
		
		$txp = new TXP_Wrapper($username, $password);
		
		if (!$txp->loggedin)
		{
			return new IXR_Error(100, gTxt('bad_login'));
		}
		
		$post = $txp->getArticleID($postid, 'Category1, Category2');
		if (!$post) return new IXR_Error(211, gTxt('problem_retrieving_article_categories'));

		$out = array();
		$isPrimary = true;
		foreach ($post as $category)
		{
			$rs = $txp->getCategory($category);
			if (!$post) return new IXR_Error(212, gTxt('problem_retrieving_category_info'));
			
			$ct['categoryId'] = $rs['id'];
			$ct['categoryName'] = $rs['name'];
			$ct['isPrimary'] = $isPrimary;
			
			$out[]= $ct;
			if($isPrimary) $isPrimary = false;
		}
		
		return $out;
		
	}
	
	# supported to avoid some client expecific behaviour
	function mt_publishPost($params)
	{
		list($postid,$username,$password) = $params;
		
		$txp = new TXP_Wrapper($username, $password);
		
		if (!$txp->loggedin)
		{
			return new IXR_Error(100, gTxt('bad_login'));
		}
		
		$published = $txp->updateArticleField($postid, 'Status', '4');
		
		if (!$published)
		{
			return new IXR_Error(201, gTxt('problem_updating_article'));
		}
		return true;
	}
	
	function mt_setPostCategories($params)
	{
		list($postid,$username,$password,$categories) = $params;
		
		$txp = new TXP_Wrapper($username, $password);
		
		if (!$txp->loggedin)
		{
			return new IXR_Error(100, gTxt('bad_login'));
		}		
		
		$Category1='';
		$Category2='';
		foreach ($categories as $category)
		{
			extract($category);
			$rs = $txp->getCategoryId($categoryId);
			if (!$rs) return new IXR_Error(213, gTxt('trying_to_assign_unexisting_category_to_the_article'));
			if (empty($Category1))
			{
				$Category1 = $rs['name'];
			}else{
				$Category2 = $rs['name'];
			}
		}
		
		$ct1 = $txp->updateArticleField($postid,'Category1',$Category1);
		$ct2 = $txp->updateArticleField($postid,'Category2',$Category2);
		
		if (!$ct1 || !$ct2)  return new IXR_Error(214, gTxt('problem_saving_article_categories'));
		
		return true;
		
	}
	
	# MediaObjects
	/*
	 metaWeblog.newMediaObject
	 Description: Uploads a file to your webserver.
	 Parameters: String blogid, String username, String password, struct file
	 Return value: URL to the uploaded file.
	 Notes: the struct file should contain two keys: base64 bits (the base64-encoded contents of the file) 
	 and String name (the name of the file). The type key (media type of the file) is currently ignored.
	 */		
	
	#code refactoring for blogger_newPost & blogger_editPost
	function _getBloggerContents($content)
	{
		$body = $content;
		# Trick to add title, category and excerpts using XML-RPC
		if (preg_match('/<title>(.*)<\/title>(.*)/s',$content, $matches))
		{
			$body = $matches[2];
			$title = $matches[1];
		}
		
		$contents = array(
			'Body' => str_replace('\n',"\n",$body)
		);
		
		
		if (isset($title)) $contents['Title'] = $title;
		
		return $contents;
	}
	
	#code refactoring for metaWeblog_newPost & metaweblog_EditPost
	function _getMetaWeblogContents($struct, $publish)
	{		
		$contents = array(
			'Body' => str_replace('\n',"\n",$struct['description']),
			'Status' => ($publish)? '4' : '1',
			'Title' => $struct['title'],
		);

		if (!empty($struct['categories']))
		{
			if (!empty($struct['categories'][0]))
			{
				$contents ['Category1'] = $struct['categories'][0];
			}
			if (!empty($struct['categories'][1]))
			{
				$contents ['Category2'] = $struct['categories'][1];
			}						
		}
		
		if (isset($struct['dateCreated']))
		{
			$contents['Posted'] = date('Y-m-d H:i:s',$struct['dateCreated']->getTimestamp());
		}
		
		# MovableType Implementation Add ons
		if (isset($struct['mt_allow_comments'])) $contents['Annotate'] = $struct['mt_allow_comments'];
		if (isset($struct['mt_convert_breaks']))
		{
			$contents['textile_body'] = $contents['textile_excerpt']= intval($struct['mt_convert_breaks']);
		}
		if (isset($struct['mt_text_more'])) $contents['Body'].= "\n\n". str_replace('\n',"\n",$struct['mt_text_more']);
		if (isset($struct['mt_excerpt'])) $contents['Excerpt'] = str_replace('\n',"\n",$struct['mt_excerpt']);
		if (isset($struct['mt_keywords'])) $contents['Keywords'] = $struct['mt_keywords'];		
		
		return $contents;
	}
	
	# common code to metaWeblog_getPost and metaWeblog_getRecentPosts
	# could not be this placed on a different file from taghandlers?
	# remove if it is the case
	function _buildMetaWeblogStruct($rs, $user)
	{
		global $prefs;
		# do not extract, since we only need this one
		$permlink_mode =& $prefs['permlink_mode'];
				
		switch ($permlink_mode){
			case 'section_id_title':
				$url = hu.join('/', array($rs['Section'],$rs['ID'],$rs['url_title']));			
			break;
			case 'year_month_day_title':
				$url = hu.join('/', array(
					date("Y", $rs['uPosted']),
					date("m", $rs['uPosted']),
					date("d", $rs['uPosted']),
					$rs['url_title']
				));
			break;
			case 'title_only':
				$url = hu.$rs['url_title'];
			break;
			case 'section_title':
				$url = hu.join('/', array($rs['Section'],$rs['url_title']));
			break;
			case 'id_title':
				$url = hu.join('/', array($rs['ID'],$rs['url_title']));
			break;
			default:
			//assume messy mode?
				$url = hu.'?id='.$rs['ID'];
			break;
		}
		
		$out = array(
			'categories' => array($rs['Category1'], $rs['Category2']),
			'description' => $rs['Body'],
			'userid' => $user,
			'postid' => $rs['ID'],
			'dateCreated' => new IXR_Date($rs['uPosted']+tz_offset()),
			'link'=>$url,
			'permaLink'=>$url,
			'title'=>$rs['Title'],			
		);
		# MovableType Implementation Add ons
		if (isset($rs['Annotate']) && !empty($rs['Annotate'])) $out['mt_allow_comments'] = intval($rs['Annotate']);
		if (isset($rs['textile_body']) && !empty($rs['textile_body'])) $out['mt_convert_breaks'] = strval($rs['textile_body']);
		if (isset($rs['Excerpt']) && !empty($rs['Excerpt'])) $out['mt_excerpt'] = $rs['Excerpt'];
		if (isset($rs['Keywords']) && !empty($rs['Keywords'])) $out['mt_keywords'] = $rs['Keywords'];
		
		return $out;
	}	
}

?>