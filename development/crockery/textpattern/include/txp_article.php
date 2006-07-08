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

global $vars, $statuses;

$vars = array(
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
		global $txp_user,$vars,$txpcfg, $ID;
		extract(get_prefs());
		$incoming = psa($vars);
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
		global $txp_user,$vars,$txpcfg;
		extract(get_prefs());
		$incoming = psa($vars);

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
		global $txpcfg,$txp_user,$vars;

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
				extract(gpsa($vars));
			} elseif($from_view=='preview' or $from_view=='html') {
					// coming from either html or preview
				if (isset($_POST['store'])) {
					$store = unserialize(base64_decode($_POST['store']));					
					extract($store);
				}
			}
			
			foreach($vars as $var){
				if(isset($$var)){
					$store_out[$var] = $$var;		
				}
			}
		}

		$GLOBALS['step'] = $step;

		if ($step=='create') {
			$markup_body = @$markup_default;
			$markup_excerpt = @$markup_default;
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

			'<tr><td>&nbsp;</td><td colspan="3">';

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
			echo br.'<input type="text" id="title" name="Title" value="'.cleanfInput($Title).'" class="edit" size="40" tabindex="1" />';

			if ($Status == 4 or $Status == 5)
			{
				include_once txpath.'/publish/taghandlers.php';

				$article_array = ($pull) ? $rs : gpsa($vars);

				echo sp.sp.'<a href="'.permlinkurl($article_array).'">'.gTxt('view').'</a>';
			}
		}

		echo '</td></tr>'.

	//-- article input --------------

  		'<tr>'.n.
				'<td id="article-col-1">',

	//-- textile help --------------

		($view=='text' && $markup_body) ?
		
		'<p><a href="#" onclick="toggleDisplay(\'textile_help\');return false;">'.gTxt('textile_help').'</a></p>
		<div id="textile_help" style="display:none;">'.sidehelp($markup_body).'</div>' : sp;

		if ($view=='text') {
		
			echo 
			'<p><a href="#" onclick="toggleDisplay(\'advanced\'); return false;">'.
				gTxt('advanced_options').'</a></p>',
			'<div id="advanced" style="display:none;">',
				
				// markup selection
			graf(gTxt('markup_type').br.
				tag(gTxt('article').br.markup_popup('markup_body', $markup_body)
					,'label').
				br.
				tag(gTxt('excerpt').br.markup_popup('markup_excerpt', $markup_excerpt)
					,'label')),

				// form override
			($allow_form_override)
			?	graf(gTxt('override_default_form').br.
					form_pop($override_form).popHelp('override_form'))
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
			graf(gTxt('keywords').popHelp('keywords').br.
				'<textarea id="keywords" name="Keywords" cols="17" rows="5">'.$Keywords.'</textarea>'),

				// article image
			graf(gTxt('article_image').popHelp('article_image').br.
				fInput('text','Image',$Image,'edit')),

				// url title
			graf(gTxt('url_title').popHelp('url_title').br.
				fInput('text','url_title',$url_title,'edit')).
		
			'</div>
			
			<p><a href="#" onclick="toggleDisplay(\'recent\'); return false;">' . gTxt('recent_articles').'</a>'.'</p>'.
			'<div id="recent" style="display:none;">';
			
			$recents = safe_rows_start("Title, ID",'textpattern',"1=1 order by LastMod desc limit 10");
			
			if ($recents) {
				echo '<p>';
				while($recent = nextRow($recents)) {
					extract($recent);
					if (!$Title) $Title = gTxt('untitled').sp.$ID;
					echo '<a href="?event=article'.a.'step=edit'.a.'ID='.$ID.'">'.$Title.'</a>'.br.n;
				}
				echo '</p>';
			}
			
			echo '</div>';
		}

		else
		{
			echo sp;
		}

  	echo '</td>'.n.'<td id="article-main">';

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
			echo '<textarea id="body" name="Body" cols="55" rows="31" tabindex="2">'.htmlspecialchars($Body).'</textarea>';
		}

	//-- excerpt --------------------

		if ($articles_use_excerpts)
		{
			if ($view == 'text')
			{
				$Excerpt = str_replace('&amp;', '&', htmlspecialchars($Excerpt));

				echo graf(gTxt('excerpt').popHelp('excerpt').br.
					'<textarea id="excerpt" name="Excerpt" cols="55" rows="5" tabindex="3">'.$Excerpt.'</textarea>');
			}

			else
			{
				echo '<hr width="50%" />';
			
				echo ($view=='preview')
					?	graf(do_markup($markup_excerpt, $Excerpt))
					:	tag(str_replace(array(n,t),
							array(br,sp.sp.sp.sp),htmlspecialchars(
								do_markup($markup_excerpt, $Excerpt))),'code');
			}
		}


	//-- author --------------
	
		if ($view=="text" && $step != "create") {
			echo "<p><small>".gTxt('posted_by')." $AuthorID: ",safe_strftime('%H:%M %d %b %Y',$sPosted);
			if($sPosted != $sLastMod) {
				echo br.gTxt('modified_by')." $LastModID: ", safe_strftime('%H:%M %d %b %Y',$sLastMod);
			}
				echo '</small></p>';
			}

	echo hInput('from_view',$view),
	'</td>';
	echo '<td valign="top" align="left" width="20">';

  	//-- layer tabs -------------------

		echo tab('text',$view).tab('html',$view).tab('preview',$view);
	echo '</td>';
?>	
<td id="article-col-2" align="left">
<?php 
	//-- prev/next article links -- 

		if ($view=='text') {
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
		}
			
	//-- status radios --------------
	 	
			echo ($view == 'text') ? n.graf(status_radio($Status)).n : '';


	//-- category selects -----------

			echo ($view=='text')
			?	graf(gTxt('categorize').
				' ['.eLink('category','','','',gTxt('edit')).']'.br.
				category_popup('Category1',$Category1).
				category_popup('Category2',$Category2))
			:	'';

	//-- section select --------------

			if(!$from_view && !$pull) $Section = getDefaultSection();
			echo ($view=='text')
			?	graf(gTxt('section').' ['.eLink('section','','','',gTxt('edit')).']'.br.
				section_popup($Section))
			:	'';

	//-- comments stuff --------------

			if($step=="create") {
				//Avoiding invite disappear when previewing
				$AnnotateInvite = (!empty($store_out['AnnotateInvite']))? $store_out['AnnotateInvite'] : $comments_default_invite;
				if ($comments_on_default==1) { $Annotate = 1; }
			}
			echo ($use_comments==1 && $view=='text')
			?	graf(gTxt('comments').br.onoffRadio("Annotate",$Annotate).br.
				gTxt('comment_invitation').br.
				fInput('text','AnnotateInvite',$AnnotateInvite,'edit'))
			:	'';
			

	//-- timestamp ------------------- 

		if ($step == "create" and empty($GLOBALS['ID'])) {
			if ($view == 'text') {
				//Avoiding modified date to disappear
				$persist_timestamp = (!empty($store_out['year']))? 
					mktime($store_out['hour'],$store_out['minute'], $store_out['second'], $store_out['month'], $store_out['day'], $store_out['year'])
					: time();
			echo
			graf(tag(checkbox('publish_now','1').gTxt('set_to_now'),'label')),
			'<p>',gTxt('or_publish_at'),popHelp("timestamp"),br,				
				tsi('year','Y',$persist_timestamp),
				tsi('month','m',$persist_timestamp),
				tsi('day','d',$persist_timestamp), sp,
				tsi('hour','H',$persist_timestamp), ':',
				tsi('minute','i',$persist_timestamp), ':',
				tsi('second', 's', $persist_timestamp),
			'</p>';
			}

	//-- publish button --------------

			if ($view == 'text') {
				echo
				(has_privs('article.publish')) ?
				fInput('submit','publish',gTxt('publish'),"publish") :
				fInput('submit','publish',gTxt('save'),"publish");
			}

		} else {
			
			if ($view == 'text') {
				echo
				'<p>',gTxt('published_at'),popHelp("timestamp"),br,
					tsi('year','Y',$sPosted,5),
					tsi('month','m',$sPosted,6),
					tsi('day','d',$sPosted,7), sp,
					tsi('hour','H',$sPosted,8), ':',
					tsi('minute','i',$sPosted,9), ':',
					tsi('second', 's', $sPosted, 10),
				'</p>',
					hInput('sPosted',$sPosted),
					hInput('sLastMod',$sLastMod),
					hInput('AuthorID',$AuthorID),
					hInput('LastModID',$LastModID),
					graf(checkbox('reset_time','1',0).gTxt('reset_time'));
			}

	//-- save button --------------

			if ($view == 'text') {
				if (   ($Status >= 4 and has_privs('article.edit.published'))
					or ($Status >= 4 and $AuthorID==$txp_user and has_privs('article.edit.own.published'))
				    or ($Status <  4 and has_privs('article.edit'))
					or ($Status <  4 and $AuthorID==$txp_user and has_privs('article.edit.own')))
					echo fInput('submit','save',gTxt('save'),"publish");
			}
		}

    	echo '</td></tr></table></form>';
	
	}


// -------------------------------------------------------------
	function custField($num,$field,$content) 
	{
		return graf($field . br .  fInput('text', 'custom_'.$num, $content,'edit'));
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
		.'" size="'.$size.'" maxlength="'.$size.'" class="edit" tabindex="'.$tab.'" />'."\n";
	}

//--------------------------------------------------------------
	function article_delete()
	{
		$dID = ps('dID');
		$rs = safe_delete("textpattern","ID=$dID");
		if ($rs) article_list(messenger('article',$dID,'deleted'),1);
	}

//--------------------------------------------------------------
	function sidehelp($markup)
	{
		// FIXME: display help for each markup type
		// perhaps this could be stored in the markup class
		global $use_textile, $textile_body;

		if ($use_textile == USE_TEXTILE || $textile_body == USE_TEXTILE)
		{
			return

				'<ul class="plain-list small">'.
					'<li>'.gTxt('header').': <strong>h<em>n</em>.</strong>'.sp.
						popHelpSubtle('header', 400, 400).'</li>'.
					'<li>'.gTxt('blockquote').': <strong>bq.</strong>'.sp.
						popHelpSubtle('blockquote',400,400).'</li>'.
					'<li>'.gTxt('numeric_list').': <strong>#</strong>'.sp.
						popHelpSubtle('numeric', 400, 400).'</li>'.
					'<li>'.gTxt('bulleted_list').': <strong>*</strong>'.sp.
						popHelpSubtle('bulleted', 400, 400).'</li>'.
				'</ul>'.

				'<ul class="plain-list small">'.
					'<li>'.'_<em>'.gTxt('emphasis').'</em>_'.sp.
						popHelpSubtle('italic', 400, 400).'</li>'.
					'<li>'.'*<strong>'.gTxt('strong').'</strong>*'.sp.
						popHelpSubtle('bold', 400, 400).'</li>'.
					'<li>'.'??<cite>'.gTxt('citation').'</cite>??'.sp.
						popHelpSubtle('cite', 500, 300).'</li>'.
					'<li>'.'-'.gTxt('deleted_text').'-'.sp.
						popHelpSubtle('delete', 400, 300).'</li>'.
					'<li>'.'+'.gTxt('inserted_text').'+'.sp.
						popHelpSubtle('insert', 400, 300).'</li>'.
					'<li>'.'^'.gTxt('superscript').'^'.sp.
						popHelpSubtle('super', 400, 300).'</li>'.
					'<li>'.'~'.gTxt('subscript').'~'.sp.
						popHelpSubtle('subscript', 400, 400).'</li>'.
				'</ul>'.

				graf(
					'"'.gTxt('linktext').'":url'.sp.popHelpSubtle('link', 400, 500)
				, ' class="small"').

				graf(
					'!'.gTxt('imageurl').'!'.sp.popHelpSubtle('image', 500, 500)
				, ' class="small"').

				graf(
					'<a href="http://textism.com/tools/textile/" target="_blank">'.gTxt('More').'</a>');
		}

		return '';
	}

//--------------------------------------------------------------
	function status_radio($Status) 
	{
		global $statuses;
		$Status = (!$Status) ? 4 : $Status;
		foreach($statuses as $a=>$b) {
			$out[] = tag(radio('Status',$a,($Status==$a)?1:0).$b,'label');	
		}
		return join(br.n,$out);
	}

//--------------------------------------------------------------
	function category_popup($name,$val) 
	{
		$rs = getTree("root",'article');
		if ($rs) {
			return treeSelectInput($name,$rs,$val);
		}
		return false;
	}

//--------------------------------------------------------------
	function section_popup($Section) 
	{
		$rs = safe_column("name", "txp_section", "name!='default'");
		if ($rs) {	
			return selectInput("Section", $rs, $Section);
		}
		return false;
	}

//--------------------------------------------------------------
	function markup_popup($name,$markup) 
	{
		$markup_types = get_markup_types();
		return selectInput($name, $markup_types, $markup);
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
	function form_pop($form)
	{
		$arr = array(' ');
		$rs = safe_column("name", "txp_form", "type='article' and name!='default'");
		if($rs) {
			return selectInput('override_form',$rs,$form,1);
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
