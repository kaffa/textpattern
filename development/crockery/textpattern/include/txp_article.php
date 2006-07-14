<?php
/*
	This is Textpattern
	Copyright 2005 by Dean Allen 
 	All rights reserved.

	Use of this software indicates acceptance of the Textpattern license agreement 

$HeadURL$
$LastChangedRevision$

*/

include_once(txpath.'/lib/classMarkup.php');

if (!defined('txpinterface')) die('txpinterface is undefined.');

global $article_vars, $statuses;

$article_vars = array(
	'ID','Title','Title_html','Body','Body_html','Excerpt','markup_excerpt','Image',
	'markup_body', 'Keywords','Status','Posted','Section','Category1','Category2',
	'Annotate','AnnotateInvite','publish_now','reset_time','AuthorID','sPosted',
	'LastModID','sLastMod','override_form','from_view','year','month','day','hour',
	'minute','second','url_title','custom_1','custom_2','custom_3','custom_4','custom_5',
	'custom_6','custom_7','custom_8','custom_9','custom_10'
);

$statuses = array(
		1 => gTxt('draft'),
		2 => gTxt('hidden'),
		3 => gTxt('pending'),
		4 => strong(gTxt('live')),
		5 => gTxt('sticky'),
);

if (!empty($event) and $event == 'article') {
	require_privs('article');


	$save = gps('save');
	if ($save) $step = 'save';

	$publish = gps('publish');
	if ($publish) $step = 'publish';

		
	switch(strtolower($step)) {
		case "":         article_edit();    break;
		case "list":     article_list();    break;
		case "create":   article_edit();    break;
		case "publish":  article_post();    break;
		case "edit":     article_edit();    break;
		case "save":     article_save();    break;
		case "delete":   article_delete();
	}
}

//--------------------------------------------------------------
	function article_post()
	{
		global $txp_user,$article_vars,$txpcfg, $ID;
		extract(get_prefs());
		$incoming = psa($article_vars);
		$message='';

		$incoming = markup_main_fields($incoming);

		extract(doSlash($incoming));

		if ($publish_now==1) {
			$when = 'now()';
		} else {
			$when = strtotime($year.'-'.$month.'-'.$day.' '.$hour.':'.$minute.':'.$second)-tz_offset();
			$when = "from_unixtime($when)";
		}

		if ($Title or $Body or $Excerpt) {
			
			if (!has_privs('article.publish') && $Status>=4) $Status = 3;
			if (empty($url_title)) $url_title = stripSpace($Title_plain, 1);  	
			if (!$Annotate) $Annotate = 0; 

			$ID = safe_insert(
			   "textpattern",
			   "Title           = '$Title',
				Body            = '$Body',
				Body_html       = '$Body_html',
				Excerpt         = '$Excerpt',
				Excerpt_html    = '$Excerpt_html',
				Image           = '$Image',
				Keywords        = '$Keywords',
				Status          = '$Status',
				Posted          = $when,
				LastMod         = now(),
				AuthorID        = '$txp_user',
				Section         = '$Section',
				Category1       = '$Category1',
				Category2       = '$Category2',
				markup_body     = '$markup_body',
				markup_excerpt  = '$markup_excerpt',
				Annotate        = '$Annotate',
				override_form   = '$override_form',
				url_title       = '$url_title',
				AnnotateInvite  = '$AnnotateInvite',
				custom_1        = '$custom_1',
				custom_2        = '$custom_2',
				custom_3        = '$custom_3',
				custom_4        = '$custom_4',
				custom_5        = '$custom_5',
				custom_6        = '$custom_6',
				custom_7        = '$custom_7',
				custom_8        = '$custom_8',
				custom_9        = '$custom_9',
				custom_10       = '$custom_10',
				uid				= '".md5(uniqid(rand(),true))."',
				feed_time		= now()"
			);
			
			if ($Status>=4) {
				
				do_pings();
				
				safe_update("txp_prefs", "val = now()", "name = 'lastmod'");
			}
			article_edit(
				get_status_message($Status).check_url_title($url_title)
			);
		} else article_edit();
	}

//--------------------------------------------------------------
	function article_save()
	{
		global $txp_user,$article_vars,$txpcfg;
		extract(get_prefs());
		$incoming = psa($article_vars);

		$oldArticle = safe_row('Status, url_title, Title','textpattern','ID = '.(int)$incoming['ID']);

		if (! (    ($oldArticle['Status'] >= 4 and has_privs('article.edit.published'))
				or ($oldArticle['Status'] >= 4 and $incoming['AuthorID']==$txp_user and has_privs('article.edit.own.published'))
		    	or ($oldArticle['Status'] < 4 and has_privs('article.edit'))
				or ($oldArticle['Status'] < 4 and $incoming['AuthorID']==$txp_user and has_privs('article.edit.own'))))
		{
				// Not allowed, you silly rabbit, you shouldn't even be here. 
				// Show default editing screen.
			article_edit();
			return;
		}

		$incoming = markup_main_fields($incoming);

		extract(doSlash($incoming));

		if (!has_privs('article.publish') && $Status>=4) $Status = 3;
		
		if($reset_time) {
			$whenposted = "Posted=now()"; 
		} else {
			$when = strtotime($year.'-'.$month.'-'.$day.' '.$hour.':'.$minute.':'.$second)-tz_offset();
			$when = "from_unixtime('$when')";
			$whenposted = "Posted=$when";
		}
		
		//Auto-Update custom-titles according to Title, as long as unpublished and NOT customized
		if ( empty($url_title)
			  || ( ($oldArticle['Status'] < 4) 
					&& ($oldArticle['url_title'] == $url_title ) 
					&& ($oldArticle['url_title'] == stripSpace($oldArticle['Title'],1))
					&& ($oldArticle['Title'] != $Title)
				 )
		   )
		{
			$url_title = stripSpace($Title_plain, 1);
		}
		if (!$Annotate) $Annotate = 0; 

		safe_update("textpattern", 
		   "Title           = '$Title',
			Body            = '$Body',
			Body_html       = '$Body_html',
			Excerpt         = '$Excerpt',
			Excerpt_html    = '$Excerpt_html',
			Keywords        = '$Keywords',
			Image           = '$Image',
			Status          = '$Status',
			LastMod         =  now(),
			LastModID       = '$txp_user',
			Section         = '$Section',
			Category1       = '$Category1',
			Category2       = '$Category2',
			Annotate        = '$Annotate',
			markup_body     = '$markup_body',
			markup_excerpt  = '$markup_excerpt',
			override_form   = '$override_form',
			url_title       = '$url_title',
			AnnotateInvite  = '$AnnotateInvite',
			custom_1        = '$custom_1',
			custom_2        = '$custom_2',
			custom_3        = '$custom_3',
			custom_4        = '$custom_4',
			custom_5        = '$custom_5',
			custom_6        = '$custom_6',
			custom_7        = '$custom_7',
			custom_8        = '$custom_8',
			custom_9        = '$custom_9',
			custom_10       = '$custom_10',
			$whenposted",
			"ID='$ID'"
		);

		if($Status >= 4) {
			if ($oldArticle['Status'] < 4) {
				do_pings();	
			}
			safe_update("txp_prefs", "val = now()", "name = 'lastmod'");
		}
		
		article_edit(
			get_status_message($Status).check_url_title($url_title)
		);

	}

//--------------------------------------------------------------
	function article_edit($message="")
	{
		global $txpcfg,$txp_user,$article_vars;

		extract(get_prefs());
		extract(gpsa(array('view','from_view','step')));
		
		if(!empty($GLOBALS['ID'])) { // newly-saved article
			$ID = intval($GLOBALS['ID']);
			$step = 'edit';
		} else {  
			$ID = gps('ID');
		}
		
		if (!$view) $view = "text";
		if (!$step) $step = "create";

		if ($step == "edit" 
			&& $view=="text" 
			&& !empty($ID) 
			&& $from_view != "preview" 
			&& $from_view != 'html') {

			$pull = true;          //-- it's an existing article - off we go to the db

			$rs = safe_row(
				"*, unix_timestamp(Posted) as sPosted,
				unix_timestamp(LastMod) as sLastMod",
				"textpattern", 
				"ID=$ID"
			);

			extract($rs);
						
			if ($AnnotateInvite!= $comments_default_invite) {
				$AnnotateInvite = $AnnotateInvite;
			} else {
				$AnnotateInvite = $comments_default_invite;
			}
		} else {
		
			$pull = false;         //-- assume they came from post
		
			if (!$from_view or $from_view=='text') {
				extract(gpsa($article_vars));
			} elseif($from_view=='preview' or $from_view=='html') {
					// coming from either html or preview
				if (isset($_POST['store'])) {
					$store = unserialize(base64_decode($_POST['store']));					
					extract($store);
				}
			}
			
			foreach($article_vars as $var){
				if(isset($$var)){
					$store_out[$var] = $$var;		
				}
			}
		}

		$GLOBALS['step'] = $step;

		if ($step == 'create' or empty($markup_body) or empty($markup_excerpt))
		{
			$markup_body = $markup_default;
			$markup_excerpt = $markup_default;
		}

		if ($step!='create') {

			// Previous record?				
			$prev_id = checkIfNeighbour('prev',$sPosted);
			
			// Next record?
			$next_id = checkIfNeighbour('next',$sPosted);
		}

		pagetop($Title, $message);

		echo n.n.'<form name="article" method="post" action="index.php">';

		if (!empty($store_out))
		{
			echo hInput('store', base64_encode(serialize($store_out)));
		}

		echo hInput('ID', $ID).
			eInput('article').
			sInput($step).
			'<input type="hidden" name="view" />'.

			startTable('edit').

  		'<tr>'.n.
				'<td id="article-col-1">';

		if ($view == 'text')
		{

		//-- markup help --------------

			echo side_help($markup_body, $markup_excerpt).

			'<h3><a href="#" onclick="toggleDisplay(\'advanced\'); return false;">'.gTxt('advanced_options').'</a></h3>',
			'<div id="advanced" style="display:none;">',

			// markup selection
				n.graf('<label for="markup-body">'.gTxt('article_markup').'</label>'.br.
					pref_markup('markup_body', $markup_body, 'markup-body')),

				n.graf('<label for="markup-excerpt">'.gTxt('excerpt_markup').'</label>'.
					pref_markup('markup_excerpt', $markup_excerpt, 'markup-excerpt')),

				// form override
			($allow_form_override)
			?	graf('<label for="override-form">'.gTxt('override_default_form').'</label>'.br.
					form_pop($override_form, 'override-form').sp.popHelp('override_form'))
			:	'',
			
				// custom fields, believe it or not
			($custom_1_set)  ? custField(  1, $custom_1_set,  $custom_1 )    : '',
			($custom_2_set)  ? custField(  2, $custom_2_set,  $custom_2 )    : '',
			($custom_3_set)  ? custField(  3, $custom_3_set,  $custom_3 )    : '',
			($custom_4_set)  ? custField(  4, $custom_4_set,  $custom_4 )    : '',
			($custom_5_set)  ? custField(  5, $custom_5_set,  $custom_5 )    : '',
			($custom_6_set)  ? custField(  6, $custom_6_set,  $custom_6 )    : '',
			($custom_7_set)  ? custField(  7, $custom_7_set,  $custom_7 )    : '',
			($custom_8_set)  ? custField(  8, $custom_8_set,  $custom_8 )    : '',
			($custom_9_set)  ? custField(  9, $custom_9_set,  $custom_9 )    : '',
			($custom_10_set) ? custField( 10, $custom_10_set, $custom_10 )   : '',


			// keywords
				n.graf('<label for="keywords">'.gTxt('keywords').'</label>'.sp.popHelp('keywords').br.
					'<textarea id="keywords" name="Keywords" cols="18" rows="5">'.$Keywords.'</textarea>'),

			// article image
				n.graf('<label for="article-image">'.gTxt('article_image').'</label>'.sp.popHelp('article_image').br.
					fInput('text', 'Image', $Image, 'edit', '', '', 22, '', 'article-image')),

			// url title
				n.graf('<label for="url-title">'.gTxt('url_title').'</label>'.sp.popHelp('url_title').br.
					fInput('text', 'url_title', $url_title, 'edit', '', '', 22, '', 'url-title')).
		
			'</div>
			
			<h3><a href="#" onclick="toggleDisplay(\'recent\'); return false;">'.gTxt('recent_articles').'</a>'.'</h3>'.
			'<div id="recent" style="display:none;">';
			
			$recents = safe_rows_start("Title, ID",'textpattern',"1=1 order by LastMod desc limit 10");
			
			if ($recents)
			{
				echo '<ul class="plain-list">';

				while ($recent = nextRow($recents))
				{
					if (!$recent['Title'])
					{
						$recent['Title'] = gTxt('untitled').sp.$recent['ID'];
					}

					echo n.t.'<li><a href="?event=article'.a.'step=edit'.a.'ID='.$recent['ID'].'">'.$recent['Title'].'</a></li>';
				}

				echo '</ul>';
			}

			echo '</div>';
		}

		else
		{
			echo sp;
		}

  	echo '</td>'.n.'<td id="article-main">';

	//-- title input -------------- 

		if ($view == 'preview')
		{
			echo hed(gTxt('preview'), 2).graf($Title);
		}

		if ($view == 'html')
		{
			echo hed('XHTML',2).graf($Title);
		}

		if ($view == 'text')
		{
			echo '<p><input type="text" id="title" name="Title" value="'.cleanfInput($Title).'" class="edit" size="40" tabindex="1" />';

			if ($Status == 4 or $Status == 5)
			{
				include_once txpath.'/publish/taghandlers.php';

				$article_array = ($pull) ? $rs : gpsa($article_vars);

				echo sp.sp.'<a href="'.permlinkurl($article_array).'">'.gTxt('view').'</a>';
			}

			echo '</p>';
		}

    if ($view == 'preview')
    { 
			echo do_markup($markup_body, $Body);
    }

    elseif ($view == 'html')
    {
			$bod = do_markup($markup_body, $Body);
			echo tag(str_replace(array(n,t), array(br,sp.sp.sp.sp), htmlspecialchars($bod)), 'code');
		}

		else
		{
			echo n.graf('<textarea id="body" name="Body" cols="55" rows="31" tabindex="2">'.htmlspecialchars($Body).'</textarea>');
		}

	//-- excerpt --------------------

		if ($articles_use_excerpts)
		{
			if ($view == 'text')
			{
				$Excerpt = str_replace('&amp;', '&', htmlspecialchars($Excerpt));

				echo n.graf('<label for="excerpt">'.gTxt('excerpt').'</label>'.sp.popHelp('excerpt').br.
					'<textarea id="excerpt" name="Excerpt" cols="55" rows="5" tabindex="3">'.$Excerpt.'</textarea>');
			}

			else
			{
				echo n.'<hr width="50%" />';
			
				echo ($view=='preview')
					?	graf(do_markup($markup_excerpt, $Excerpt))
					:	tag(str_replace(array(n,t),
							array(br,sp.sp.sp.sp),htmlspecialchars(
								do_markup($markup_excerpt, $Excerpt))),'code');
			}
		}


	//-- author --------------
	
		if ($view=="text" && $step != "create") {
			echo '<p class="small">'.gTxt('posted_by').": $AuthorID &#183; ".safe_strftime('%d %b %Y &#183; %I:%M:%S %p',$sPosted);
			if($sPosted != $sLastMod) {
				echo br.gTxt('modified_by').": $LastModID &#183; ".safe_strftime('%d %b %Y &#183; %I:%M:%S %p',$sLastMod);
			}
				echo '</p>';
			}

	echo hInput('from_view',$view),
	'</td>';
	echo '<td id="article-tabs">';

  	//-- layer tabs -------------------

		echo graf(tab('text',$view).br.tab('html',$view).br.tab('preview',$view));
	echo '</td>';
?>	
<td id="article-col-2">
<?php 

		if ($view == 'text')
		{
			if ($step != 'create')
			{
				echo n.graf(href(gtxt('create_new'), 'index.php?event=article'));
			}

		//-- prev/next article links -- 

			if ($step!='create' and ($prev_id or $next_id)) {
				echo '<p>',
				($prev_id)
				?	prevnext_link('&#8249;'.gTxt('prev'),'article','edit',
						$prev_id,gTxt('prev'))
				:	'',
				($next_id)
				?	prevnext_link(gTxt('next').'&#8250;','article','edit',
						$next_id,gTxt('next'))
				:	'',
				'</p>';
			}

		//-- status radios --------------

			echo n.n.'<fieldset id="write-status">'.
				n.'<legend>'.gTxt('status').'</legend>'.
				n.status_radio($Status).
				n.'</fieldset>';


		//-- category selects -----------

			echo n.n.'<fieldset id="write-sort">'.
				n.'<legend>'.gTxt('sort_display').'</legend>'.

				n.graf('<label for="category-1">'.gTxt('category1').'</label> '.
					'<span class="small">['.eLink('category', '', '', '', gTxt('edit')).']</span>'.br.
					n.category_popup('Category1', $Category1, 'category-1')).

				n.graf('<label for="category-2">'.gTxt('category2').'</label>'.br.
					n.category_popup('Category2', $Category2, 'category-2'));

		//-- section select --------------

			if(!$from_view && !$pull) $Section = getDefaultSection();

			echo n.graf('<label for="section">'.gTxt('section').'</label> '.
				'<span class="small">['.eLink('section', '', '', '', gTxt('edit')).']</span>'.br.
				section_popup($Section, 'section')).

				n.'</fieldset>';

		//-- comments stuff --------------

			if($step=="create") {
				//Avoiding invite disappear when previewing
				$AnnotateInvite = (!empty($store_out['AnnotateInvite']))? $store_out['AnnotateInvite'] : $comments_default_invite;
				if ($comments_on_default==1) { $Annotate = 1; }
			}

			if ($use_comments == 1)
			{
				echo n.n.'<fieldset id="write-comments">'.
					n.'<legend>'.gTxt('comments').'</legend>'.
					n.n.graf(
						onoffRadio('Annotate', $Annotate)
					).

					n.n.graf(
						'<label for="comment-invite">'.gTxt('comment_invitation').'</label>'.br.
						fInput('text', 'AnnotateInvite', $AnnotateInvite, 'edit', '', '', '', '', 'comment-invite')
					).

				n.n.'</fieldset>';
			}

		//-- timestamp ------------------- 

			if ($step == "create" and empty($GLOBALS['ID']))
			{
				//Avoiding modified date to disappear
				$persist_timestamp = (!empty($store_out['year']))? 
					mktime($store_out['hour'],$store_out['minute'], $store_out['second'], $store_out['month'], $store_out['day'], $store_out['year'])
					: time();

				echo n.n.'<fieldset id="write-timestamp">'.
					n.'<legend>'.gTxt('timestamp').'</legend>'.

					n.graf(checkbox('publish_now', '1').'<label for="publish_now">'.gTxt('set_to_now').'</label>').

					n.graf(gTxt('or_publish_at').sp.popHelp('timestamp')).

					n.graf(gtxt('date').sp.
						tsi('year', 'Y', $persist_timestamp).' / '.
						tsi('month', 'm', $persist_timestamp).' / '.
						tsi('day', 'd', $persist_timestamp)
					).

					n.graf(gTxt('time').sp.
						tsi('hour', 'H', $persist_timestamp).' : '.
						tsi('minute', 'i', $persist_timestamp).' : '.
						tsi('second', 's', $persist_timestamp)
					).

				n.'</fieldset>';

		//-- publish button --------------

				echo
				(has_privs('article.publish')) ?
				fInput('submit','publish',gTxt('publish'),"publish", '', '', '', 4) :
				fInput('submit','publish',gTxt('save'),"publish", '', '', '', 4);
			}

			else
			{
				echo n.n.'<fieldset id="write-timestamp">'.
					n.'<legend>'.gTxt('timestamp').'</legend>'.

					n.graf(checkbox('reset_time', '1', 0).'<label for="reset_time">'.gTxt('reset_time').'</label>').

					n.graf(gTxt('published_at').sp.popHelp('timestamp')).

					n.graf(gtxt('date').sp.
						tsi('year', 'Y', $sPosted).' / '.
						tsi('month', 'm', $sPosted).' / '.
						tsi('day', 'd', $sPosted)
					).

					n.graf(gTxt('time').sp.
						tsi('hour', 'H', $sPosted).' : ' .
						tsi('minute', 'i', $sPosted).' : '.
						tsi('second', 's', $sPosted)
					).

					n.hInput('sPosted', $sPosted),
					n.hInput('sLastMod', $sLastMod),
					n.hInput('AuthorID', $AuthorID),
					n.hInput('LastModID', $LastModID),

				n.'</fieldset>';

		//-- save button --------------

				if (   ($Status >= 4 and has_privs('article.edit.published'))
					or ($Status >= 4 and $AuthorID==$txp_user and has_privs('article.edit.own.published'))
				    or ($Status <  4 and has_privs('article.edit'))
					or ($Status <  4 and $AuthorID==$txp_user and has_privs('article.edit.own')))
					echo fInput('submit','save',gTxt('save'),"publish", '', '', '', 4);
			}
		}

    echo '</td></tr></table></form>';
	
	}

// -------------------------------------------------------------

	function custField($num, $field, $content) 
	{
		return n.n.graf('<label for="custom-'.$num.'">'.$field.'</label>'.br.
			n.fInput('text', 'custom_'.$num, $content, 'edit', '', '', 22, '', 'custom-'.$num));
	}

// -------------------------------------------------------------
	function checkIfNeighbour($whichway,$sPosted)
	{
		$dir = ($whichway == 'prev') ? '<' : '>'; 
		$ord = ($whichway == 'prev') ? 'desc' : 'asc'; 

		if ($sPosted)
			return safe_field("ID", "textpattern", 
				"Posted $dir from_unixtime('$sPosted') order by Posted $ord limit 1");
	}

//--------------------------------------------------------------
	function tsi($name,$datevar,$time,$tab='')
	{
		$size = ($name=='year') ? 4 : 2;

		return '<input type="text" name="'.$name.'" value="'.
			date($datevar,$time+tz_offset())
		.'" size="'.$size.'" maxlength="'.$size.'" class="edit" tabindex="'.$tab.'" title="'.gTxt('article_'.$name).'" />';
	}

//--------------------------------------------------------------
	function article_delete()
	{
		$dID = ps('dID');
		$rs = safe_delete("textpattern","ID=$dID");
		if ($rs) article_list(messenger('article',$dID,'deleted'),1);
	}

//--------------------------------------------------------------

	function side_help($markup_body, $markup_excerpt)
	{
		if ($markup_body != $markup_excerpt)
		{
			$body = get_singleton($markup_body);
			$excerpt = get_singleton($markup_excerpt);

			return $body->sidehelp().$excerpt->sidehelp(gTxt('excerpt'));
		}

		else
		{
			$body = get_singleton($markup_body);

			return $body->sidehelp();
		}
	}

//--------------------------------------------------------------

	function status_radio($Status)
	{
		global $statuses;

		$Status = (!$Status) ? 4 : $Status;

		foreach ($statuses as $a => $b)
		{
			$out[] = n.t.'<li>'.radio('Status', $a, ($Status == $a) ? 1 : 0, 'status-'.$a).
				'<label for="status-'.$a.'">'.$b.'</label></li>';
		}

		return '<ul class="plain-list">'.join('', $out).n.'</ul>';
	}

//--------------------------------------------------------------

	function category_popup($name, $val, $id)
	{
		$rs = getTree('root', 'article');

		if ($rs)
		{
			return treeSelectInput($name,$rs,$val, $id);
		}

		return false;
	}

//--------------------------------------------------------------

	function section_popup($Section, $id)
	{
		$rs = safe_column('name', 'txp_section', "name != 'default'");

		if ($rs)
		{
			return selectInput('Section', $rs, $Section, true, '', $id);
		}

		return false;
	}

//--------------------------------------------------------------
	function tab($tabevent,$view) 
	{
		$state = ($view==$tabevent) ? 'up' : 'down';
		$img = 'txp_img/'.$tabevent.$state.'.gif';
		$out = '<img src="'.$img.'"';
		$out.=($tabevent!=$view) ? ' onclick="document.article.view.value=\''.$tabevent.'\'; document.article.submit(); return false;"' : "";
		$out.= ' height="100" width="19" alt="" />';
      	return $out;
	}

//--------------------------------------------------------------
	function getDefaultSection() 
	{
		return safe_field("name", "txp_section","is_default=1");
	}

// -------------------------------------------------------------

	function form_pop($form, $id)
	{
		$arr = array(' ');

		$rs = safe_column('name', 'txp_form', "type = 'article' and name != 'default'");

		if ($rs)
		{
			return selectInput('override_form', $rs, $form, true, '', $id);
		}
	}

// -------------------------------------------------------------
	function check_url_title($url_title)
	{
		// Check for blank or previously used identical url-titles
		If (strlen($url_title) === 0) {
			return ' '.gTxt("url_title_is_blank");
		} else {
			$url_title_count = safe_count("textpattern", "url_title = '".$url_title."'");
			if ($url_title_count > 1)
				return str_replace('{count}',$url_title_count,' '.gTxt("url_title_is_multiple"));
		}
		return '';
	}
// -------------------------------------------------------------
	function get_status_message($Status)
	{
		switch ($Status){
			case 3: return gTxt("article_saved_pending");
			case 2: return gTxt("article_saved_hidden");
			case 1: return gTxt("article_saved_draft");
			default: return gTxt('article_posted');
		}
	}
// -------------------------------------------------------------
	function markup_main_fields($incoming)
	{
		global $txpcfg;
		
		$incoming['Title_plain'] = $incoming['Title'];
		$incoming['Body_html'] = do_markup($incoming['markup_body'], $incoming['Body']);
		$incoming['Excerpt_html'] = do_markup($incoming['markup_excerpt'], $incoming['Excerpt']);
		
		return $incoming;
	}
// -------------------------------------------------------------
	function do_pings()
	{
		global $txpcfg;
		
		$prefs = get_prefs();
		
		include_once txpath.'/lib/IXRClass.php';
		
		if ($prefs['ping_textpattern_com']) {
			$tx_client = new IXR_Client('http://textpattern.com/xmlrpc/');
			$tx_client->query('ping.Textpattern', $prefs['sitename'], hu);
		}

		if ($prefs['ping_weblogsdotcom']==1) {
			$wl_client = new IXR_Client('http://rpc.pingomatic.com/');
			$wl_client->query('weblogUpdates.ping', $prefs['sitename'], hu);
		}
	}
?>
