<?php
/*
            _______________________________________
   ________|            Textpattern                |________
   \       |          Mod File Upload              |       /
    \      |   Michael Manfre (http://manfre.net)  |      /
    /      |_______________________________________|      \
   /___________)                               (___________\

	Textpattern Copyright 2004 by Dean Allen. All rights reserved.
	Use of this software denotes acceptance of the Textpattern license agreement

	"Mod File Upload" Copyright 2004 by Michael Manfre. All rights reserved.
	Use of this mod denotes acceptance of the Textpattern license agreement

$HeadURL$
$LastChangedRevision$


*/

include_once(txpath.'/lib/txplib_controller.php');

// tell txp to run this controller for the 'file' event, and place it in the 'content' tab area
register_controller('FileController', 'file');


// -------------------------------------------------------------
class FileController extends ZemAdminController {
	var $area = 'file';
	var $event = 'file';
	var $default_step = 'list';
	
	var $file_statuses = array(
			2 => 'hidden',
			3 => 'pending',
			4 => 'live',
	);


	function file_statuses() {
		$out = array();
		foreach ($this->file_statuses as $k=>$v)
			$out[$k] = gTxt($v);
		return $out;
	}


	function list_view($message = '')
	{
		global $txpcfg, $extensions, $file_base_path;

		extract($txpcfg);
		extract(get_prefs());

		extract(gpsa(array('page', 'sort', 'dir', 'crit', 'search_method')));

		if (!is_dir($file_base_path) or !is_writeable($file_base_path))
		{
			echo graf(
				gTxt('file_dir_not_writeable', array('{filedir}' => $file_base_path))
			, ' id="warning"');
		}

		else
		{
			$existing_files = $this->get_filenames();

			if (count($existing_files) > 0)
			{
				echo form(
					eInput($this->event).
					sInput('create').

					graf(gTxt('existing_file').sp.selectInput('filename', $existing_files, '', 1).sp.
						fInput('submit', '', gTxt('Create'), 'smallerbox'))

				, 'text-align: center;');
			}

			echo $this->file_upload_form(gTxt('upload_file'), 'upload', 'insert');
		}

		$dir = ($dir == 'desc') ? 'desc' : 'asc';

		switch ($sort)
		{
			case 'id':
				$sort_sql = 'id '.$dir;
			break;

			case 'filename':
				$sort_sql = 'filename '.$dir;
			break;

			case 'description':
				$sort_sql = 'description '.$dir.', filename desc';
			break;

			case 'category':
				$sort_sql = 'category '.$dir.', filename desc';
			break;

			case 'downloads':
				$sort_sql = 'downloads '.$dir.', filename desc';
			break;

			default:
				$dir = 'desc';
				$sort_sql = 'filename '.$dir;
			break;
		}

		$switch_dir = ($dir == 'desc') ? 'asc' : 'desc';

		$criteria = 1;

		if ($crit or $search_method)
		{
			$crit_escaped = doSlash($crit);

			$critsql = array(
				'id'			    => "id = '$crit_escaped'",
				'filename'    => "filename like '%$crit_escaped%'",
				'description' => "description like '%$crit_escaped%'",
				'category'    => "category like '%$crit_escaped%'"
			);

			if (array_key_exists($search_method, $critsql))
			{
				$criteria = $critsql[$search_method];
				$limit = 500;
			}

			else
			{
				$search_method = '';
			}
		}

		$total = safe_count('txp_file', "$criteria");

		if ($total < 1)
		{
			if ($criteria != 1)
			{
				echo n.$this->file_search_form($crit, $search_method).
					n.graf(gTxt('no_results_found'), ' style="text-align: center;"');
			}

			else
			{
				echo n.graf(gTxt('no_files_recorded'), ' style="text-align: center;"');
			}

			return;
		}

		$limit = max(@$file_list_pageby, 15);

		list($page, $offset, $numPages) = pager($total, $limit, $page);

		echo $this->file_search_form($crit, $search_method);

		$rs = safe_rows_start('*', 'txp_file', "$criteria order by $sort_sql limit $offset, $limit");

		if ($rs)
		{
			echo startTable('list').

				tr(
					column_head('ID', 'id', 'file', true, $switch_dir, $crit, $search_method).
					td().
					column_head('file_name', 'filename', 'file', true, $switch_dir, $crit, $search_method).
					column_head('description', 'description', 'file', true, $switch_dir, $crit, $search_method).
					column_head('file_category', 'category', 'file', true, $switch_dir, $crit, $search_method).
					// column_head('permissions', 'permissions', 'file', true, $switch_dir, $crit, $search_method).
					hCell(gTxt('tags')).
					hCell(gTxt('status')).
					column_head('downloads', 'downloads', 'file', true, $switch_dir, $crit, $search_method).
					hCell()
				);

			while ($a = nextRow($rs))
			{
				extract($a);

				$edit_url = '?event=file'.a.'step=edit'.a.'id='.$id.a.'sort='.$sort.
					a.'dir='.$dir.a.'page='.$page.a.'search_method='.$search_method.a.'crit='.$crit;

				$file_exists = file_exists(build_file_path($file_base_path, $filename));

				$download_link = ($file_exists) ? '<li>'.$this->make_download_link($id).'</li>' : '';

				$category = ($category) ? '<span title="'.fetch_category_title($category, 'file').'">'.$category.'</span>' : '';

				$tag_url = '?event=tag'.a.'tag_name=file_download_link'.a.'id='.$id.a.'description='.urlencode($description).
					a.'filename='.urlencode($filename);

				$status = '<span class="';
				$status .= ($file_exists) ? 'ok' : 'not-ok';
				$status .= '">';
				$status .= ($file_exists) ? gTxt('file_status_ok') : gTxt('file_status_missing');
				$status .= '</span>';

				// does the downloads column exist?
				if (!isset($downloads))
				{
					// nope, add it
					safe_alter('txp_file', "ADD downloads INT DEFAULT '0' NOT NULL");
					$downloads = 0;
				}

				elseif (empty($downloads))
				{
					$downloads = '0';
				}

				echo tr(

					n.td($id).

					td(
						'<ul>'.
						'<li>'.href(gTxt('edit'), $edit_url).'</li>'.
						$download_link.
						'</ul>'
					, 65).

					td(
						href($filename, $edit_url)
					, 125).

					td($description, 150).
					td($category, 90).

					/*
					td(
						($permissions == '1') ? gTxt('private') : gTxt('public')
					,80).
					*/

					td(
						n.'<ul>'.
						n.t.'<li><a target="_blank" href="'.$tag_url.a.'type=textile" onclick="popWin(this.href, 400, 250); return false;">Textile</a></li>'.
						n.t.'<li><a target="_blank" href="'.$tag_url.a.'type=textpattern" onclick="popWin(this.href, 400, 250); return false;">Textpattern</a></li>'.
						n.t.'<li><a target="_blank" href="'.$tag_url.a.'type=xhtml" onclick="popWin(this.href, 400, 250); return false;">XHTML</a></li>'.
						n.'</ul>'
					, 75).

					td($status, 45).

					td(
						($downloads == '0' ? gTxt('none') : $downloads)
					, 25).

					td(
						dLink('file', 'delete', 'id', $id)
					, 10)
				);
			}

			echo endTable().

			nav_form('file', $page, $numPages, $sort, $dir, $crit, $search_method).

			pageby_form('file', $file_list_pageby);
		}
	}
	
// -------------------------------------------------------------

	function file_search_form($crit, $method)
	{
		$methods =	array(
			'id'					=> gTxt('ID'),
			'filename'		=> gTxt('file_name'),
			'description' => gTxt('description'),
			'category'		=> gTxt('file_category')
		);

		return search_form('file', 'file_list', $crit, $methods, $method, 'filename');
	}

// -------------------------------------------------------------

	function edit_view($id = '')
	{
		global $txpcfg, $file_base_path, $levels, $path_from_root;

		extract(gpsa(array('name', 'category', 'permissions', 'description', 'sort', 'dir', 'page', 'crit', 'method','publish_now')));

		if (!$id)
		{
			$id = gps('id');
		}

		$categories = getTree('root', 'file');

		$rs = safe_row('*, unix_timestamp(created) as created, unix_timestamp(modified) as modified', 'txp_file', "id = '$id'");

		if ($rs)
		{
			extract($rs);

			if ($permissions=='') $permissions='-1';

			$file_exists = file_exists(build_file_path($file_base_path,$filename));

			$existing_files = $this->get_filenames();

			$condition = '<span class="';
			$condition .= ($file_exists) ? 'ok' : 'not-ok';
			$condition .= '">';
			$condition .= ($file_exists)?gTxt('file_status_ok'):gTxt('file_status_missing');
			$condition .= '</span>';

			$downloadlink = ($file_exists) ? $this->make_download_link($id, $filename) : $filename;
			
			$created =
					n.graf(checkbox('publish_now', '1', $publish_now, '', 'publish_now').'<label for="publish_now">'.gTxt('set_to_now').'</label>').

					n.graf(gTxt('or_publish_at').sp.popHelp('timestamp')).

					n.graf(gtxt('date').sp.
						tsi('year', '%Y', $rs['created']).' / '.
						tsi('month', '%m', $rs['created']).' / '.
						tsi('day', '%d', $rs['created'])
					).

					n.graf(gTxt('time').sp.
						tsi('hour', '%H', $rs['created']).' : '.
						tsi('minute', '%M', $rs['created']).' : '.
						tsi('second', '%S', $rs['created'])
					);


			$form = '';

			if ($file_exists) {
				$form =	tr(
							td(
								form(
									graf(gTxt('file_category').br.treeSelectInput('category',
									 		$categories,$category)) .
//									graf(gTxt('permissions').br.selectInput('perms',$levels,$permissions)).
									graf(gTxt('description').br.text_area('description','100','400',$description)) .
									fieldset(radio_list('status', $this->file_statuses(), $status, 4), gTxt('status'), 'file-status').
									fieldset($created, gTxt('timestamp'), 'file-created').
									graf(fInput('submit','',gTxt('save'))) .

									eInput($this->event) .
									sInput('save').

									hInput('filename', $filename).
									hInput('id', $id) .

									hInput('sort', $sort).
									hInput('dir', $dir).
									hInput('page', $page).
									hInput('crit', $crit).
									hInput('method', $method)
								)
							)
						);
			} else {

				$form =	tr(
							tda(
								hed(gTxt('file_relink'),3).
								file_upload_form(gTxt('upload_file'),'upload','file_replace',$id).
								form(
									graf(gTxt('existing_file').' '.
									selectInput('filename',$existing_files,"",1).
									fInput('submit','',gTxt('Save'),'smallerbox').

									eInput($this->event).
									sInput('save').

									hInput('id',$id).
									hInput('category',$category).
									hInput('perms',($permissions=='-1')?'':$permissions).
									hInput('description',$description).

									hInput('sort', $sort).
									hInput('dir', $dir).
									hInput('page', $page).
									hInput('crit', $crit).
									hInput('method', $method)

									)
								),
								' colspan="4" style="border:0"'
							)
						);
			}
			echo startTable('list'),
			tr(
				td(
					graf(gTxt('file_status').br.$condition) .
					graf(gTxt('file_name').br.$downloadlink) .
					graf(gTxt('file_download_count').br.(isset($downloads)?$downloads:0))					
				)
			),
			$form,
			endTable();
		}
	}

// -------------------------------------------------------------
	function file_db_add($filename,$category,$permissions,$description,$size)
	{
		$rs = safe_insert("txp_file",
			"filename = '$filename',
			 category = '$category',
			 permissions = '$permissions',
			 description = '$description',
			 size = '$size',
			 created = now(),
			 modified = now()
		");
		
		if ($rs) {
			$GLOBALS['ID'] = mysql_insert_id( );
			return $GLOBALS['ID'];
		}

		return false;
	}

// -------------------------------------------------------------
	function create_post()
	{
		global $txpcfg,$extensions,$txp_user,$file_base_path;
		extract($txpcfg);
		extract(doSlash(gpsa(array('filename','category','permissions','description'))));

		$id = $this->file_db_add($filename,$category,$permissions,$description);

		if($id === false){
			$this->_error(gTxt('file_upload_failed').' (db_add)');
		} else {
			$newpath = build_file_path($file_base_path,trim($filename));

			if (is_file($newpath)) {
				$this->file_set_perm($newpath);
				$this->_message(gTxt('linked_to_file').' '.$filename);
			} else {
				$this->_error(gTxt('file_not_found').' '.$filename);
			}
		}
	}

// -------------------------------------------------------------
	function create_view()
	{
		$this->list_view();
	}

// -------------------------------------------------------------
	function insert_post()
	{
		global $txpcfg,$extensions,$txp_user,$file_base_path,$file_max_upload_size;
		extract($txpcfg);
		extract(doSlash(gpsa(array('category','permissions','description'))));

		$name = $this->file_get_uploaded_name();
		$file = $this->file_get_uploaded();

		if ($file === false) {
			// could not get uploaded file
			$this->_error(gTxt('file_upload_failed') ." $name - ".upload_get_errormsg(@$_FILES['file']['error']));
			return;
		}

		$size = filesize($file);
		if ($file_max_upload_size < $size) {
			unlink($file);
			$this->_error(gTxt('file_upload_failed') ." $name - ".upload_get_errormsg(UPLOAD_ERR_FORM_SIZE));
			return;
		}

		if (!is_file(build_file_path($file_base_path,$name))) {

			$id = $this->file_db_add($name,$category,$permissions,$description,$size);

			if(!$id){
				$this->_error(gTxt('file_upload_failed').' (db_add)');
				return;
			} else {

				$newpath = build_file_path($file_base_path,trim($name));

				if(!shift_uploaded_file($file, $newpath)) {
					safe_delete("txp_file","id='$id'");
					safe_alter("txp_file", "auto_increment=$id");
					if ( isset( $GLOBALS['ID'])) unset( $GLOBALS['ID']);
					$this->_error($newpath.' '.gTxt('upload_dir_perms'));
					// clean up file
				} else {
					$this->file_set_perm($newpath);
					$this->_message(messenger('file',$name,'uploaded'));
					// switch to the 'edit' view, passing $id
					$this->_set_view('edit', $id);
				}
			}
		} else {
			$this->_error(messenger(gTxt('file'),$name,gTxt('already_exists')));
		}
	}

// -------------------------------------------------------------
	function insert_view()
	{
		$this->list_view();
	}


// -------------------------------------------------------------
	function replace_post()
	{
		global $txpcfg,$extensions,$txp_user,$file_base_path;
		extract($txpcfg);
		$id = gps('id');

		$rs = safe_row('filename','txp_file',"id='$id'");

		if (!$rs) {
			$this->_error(messenger(gTxt('invalid_id'),$id,''));
			return;
		}

		extract($rs);

		$file = $this->file_get_uploaded();
		$name = $this->file_get_uploaded_name();

		if ($file === false) {
			// could not get uploaded file
			$this->_error(gTxt('file_upload_failed') ." $name ".upload_get_errormsg($_FILES['file']['error']));
			return;
		}

		if (!$filename) {
			$this->_error(gTxt('invalid_filename'));
		} else {
			$newpath = build_file_path($file_base_path,$filename);

			if (is_file($newpath)) {
				rename($newpath,$newpath.'.tmp');
			}

			if(!shift_uploaded_file($file, $newpath)) {
				safe_delete("txp_file","id='$id'");

				$this->_error($newpath.sp.gTxt('upload_dir_perms'));
				// rename tmp back
				rename($newpath.'.tmp',$newpath);

				// remove tmp upload
				unlink($file);
			} else {
				$this->file_set_perm($newpath);
				if ($size = filesize($newpath))
					safe_update('txp_file', "size='".doSlash($size)."'", "id='".doSlash($id)."'");

				$this->_message(messenger('file',$name,'uploaded'));
				$this->_set_view('edit', $id);
				// clean up old
				if (is_file($newpath.'.tmp'))
					unlink($newpath.'.tmp');
			}
		}
	}

// -------------------------------------------------------------
	function replace_view()
	{
		$this->list_view();
	}


// -------------------------------------------------------------
	function reset_count_post()
	{
		extract(doSlash(gpsa(array('id','filename','category','description'))));


		if ($id) {
			if (safe_update('txp_file','downloads=0',"id='${id}'")) {
				$this->_message(gTxt('reset_file_count_success'),$id);
				$this->_set_view('edit');
			}
		} else {
			$this->_error(gTxt('reset_file_count_failure'));
		}
	}

// -------------------------------------------------------------
	function reset_count_view()
	{
		$this->list_view();
	}

// -------------------------------------------------------------
	function save_post()
	{
		global $file_base_path;
		extract(doSlash(gpsa(array('id', 'filename', 'category', 'description', 'status', 'publish_now', 'year', 'month', 'day', 'hour', 'minute', 'second'))));

		$permissions = "";
		if (isset($_GET['perms'])) {
			$permissions =  urldecode($_GET['perms']);
		} elseif (isset($_POST['perms'])) {
			$permissions = $_POST['perms'];
		}
		if (is_array($permissions)) {
			asort($permissions);
			$permissions = implode(",",$permissions);
		}

		$perms = doSlash($permissions);

		$old_filename = fetch('filename','txp_file','id','$id');

		if ($old_filename != false && strcmp($old_filename,$filename)!=0) {
			$old_path = build_file_path($file_base_path,$old_filename);
			$new_path = build_file_path($file_base_path,$filename);

			if (file_exists($old_path) && shift_uploaded_file($old_path,$new_path) === false) {
				$this->_error(messenger("file",$filename,"could not be renamed"));
				return;
			} else {
				$this->file_set_perm($new_path);
			}
		}

		$created_ts = @safe_strtotime($year.'-'.$month.'-'.$day.' '.$hour.':'.$minute.':'.$second);
		if ($publish_now)
			$created = 'now()';
		elseif ($created_ts > 0)
			$created = "from_unixtime('".$created_ts."')";
		else
			$created = '';

		$size = filesize(build_file_path($file_base_path,$filename));
		$rs = safe_update('txp_file', "
			filename = '$filename',
			category = '$category',
			permissions = '$perms',
			description = '$description',
			status = '$status',
			size = '$size',
			modified = now()"
			.($created ? ", created = $created" : '')
		, "id = $id");

		if (!$rs) {
			// update failed, rollback name
			if (shift_uploaded_file($new_path,$old_path) === false) {
				$this->_error(messenger("file",$filename,"has become unsyned with database. Manually fix file name."));
				return;
			} else {
				$this->_error(messenger(gTxt('file'),$filename,"was not updated"));
				return;
			}
		}

		$this->_message(messenger(gTxt('file'),$filename,"updated"));
	}

// -------------------------------------------------------------
	function save_view()
	{
		$this->list_view();
	}

// -------------------------------------------------------------
	function delete_post()
	{
		global $txpcfg,$file_base_path;
		extract($txpcfg);
		$id = ps('id');

		$rs = safe_row("*", "txp_file", "id='$id'");
		if ($rs) {
			extract($rs);

			$filepath = build_file_path($file_base_path,$filename);

			$rsd = safe_delete("txp_file","id='$id'");
			$ul = false;
			if ($rsd && is_file($filepath))
				$ul = unlink($filepath);
			if ($rsd && $ul) {
				$this->_message(messenger(gTxt('file'),$filename,gTxt('deleted')));
				return;
			} else {
				$this->_error(messenger(gTxt('file_delete_failed'),$filename,''));
			}
		} else
			$this->_error(messenger(gTxt('file_not_found'),$filename,''));
	}

// -------------------------------------------------------------
	function delete_view()
	{
		$this->list_view();
	}

// -------------------------------------------------------------
	function file_get_uploaded_name()
	{
		return $_FILES['thefile']['name'];
	}

// -------------------------------------------------------------
	function file_get_uploaded()
	{
		return get_uploaded_file($_FILES['thefile']['tmp_name']);
	}

// -------------------------------------------------------------
	function file_set_perm($file)
	{
		return @chmod($file,0755);
	}

// -------------------------------------------------------------
	function file_upload_form($label,$pophelp,$step,$id='')
	{
		global $file_max_upload_size;

		if (!$file_max_upload_size || intval($file_max_upload_size)==0) $file_max_upload_size = 2*(1024*1024);

		$max_file_size = (intval($file_max_upload_size) == 0) ? '': intval($file_max_upload_size);

		return upload_form($label, $pophelp, $step, 'file', $id, $max_file_size);
	}

// -------------------------------------------------------------
	function change_pageby_post()
	{
		event_change_pageby('file');
	}

// -------------------------------------------------------------
	function change_pageby_view()
	{
		$this->list_view();
	}

// -------------------------------------------------------------

	function make_download_link($id, $label = '')
	{
		global $permlink_mode;

		$label = ($label) ? $label : gTxt('download');

		$url = ($permlink_mode == 'messy') ?
			hu.'index.php?s=file_download'.a.'id='.$id :
			hu.''.gTxt('file_download').'/'.$id;

		return '<a href="'.$url.'">'.$label.'</a>';
	}

// -------------------------------------------------------------
	function get_filenames()
	{
		global $file_base_path;

		$dirlist = array();

		if (!is_dir($file_base_path))
			return $dirlist;

		if (chdir($file_base_path)) {
			if (function_exists('glob'))
				$g_array = glob("*.*");
			else {
				$dh = opendir($file_base_path);
				$g_array = array();
				while (false !== ($filename = readdir($dh))) {
					$g_array[] = $filename;
				}
				closedir($dh);

			}

			if ($g_array) {
				foreach ($g_array as $filename) {
					if (is_file($filename)) {
						$dirlist[$filename] = $filename;
					}
				}
			}
		}

		$files = array();
		$rs = safe_rows("filename", "txp_file", "1=1");

		if ($rs) {
			foreach ($rs as $a) {
				$files[$a['filename']] = $a['filename'];
			}
		}

		return array_diff($dirlist,$files);
	}
}

?>
