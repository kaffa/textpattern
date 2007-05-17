<?php

/*
	This is Textpattern

	Copyright 2005 by Dean Allen
	www.textpattern.com
	All rights reserved

	Use of this software indicates acceptance of the Textpattern license agreement 

$HeadURL$
$LastChangedRevision$

*/

if (!defined('txpinterface')) die('txpinterface is undefined.');

include_once(txpath.'/lib/txplib_controller.php');
include_once(txpath.'/lib/txplib_image.php');

register_controller('ImageController', 'image');

global $path_to_site, $img_dir;
define("IMPATH",$path_to_site.'/'.$img_dir.'/');

// -------------------------------------------------------------
class ImageController extends ZemAdminController
{
	var $area = 'image';
	var $event = 'image';
	var $caption = 'images';
	var $default_step = 'list';
	var $extensions = array(0,'.gif','.jpg','.png','.swf');

// -------------------------------------------------------------

	function list_view($message = '')
	{
		global $prefs;
		extract($prefs);

		extract(gpsa(array('page', 'sort', 'dir', 'crit', 'search_method')));
	
		if (!is_dir(IMPATH) or !is_writeable(IMPATH)) {
			echo graf(
				gTxt('img_dir_not_writeable', array('{imgdir}' => IMPATH))
			,' id="warning"');
		} else {
			echo upload_form(gTxt('upload_image'), 'upload', 'insert', $this->event, '', $file_max_upload_size);
		}
	
		$dir = ($dir == 'asc') ? 'asc' : 'desc';
	
		switch ($sort) {
			case 'name':
				$sort_sql = 'name '.$dir;
			break;
	
			case 'thumbnail':
				$sort_sql = 'thumbnail '.$dir.', id asc';
			break;
	
			case 'category':
				$sort_sql = 'category '.$dir.', id asc';
			break;
	
			case 'date':
				$sort_sql = 'date '.$dir.', id asc';
			break;
	
			case 'author':
				$sort_sql = 'author '.$dir.', id asc';
			break;
	
			default:
				$sort = 'id';
				$sort_sql = 'id '.$dir;
			break;
		}
	
		$switch_dir = ($dir == 'desc') ? 'asc' : 'desc';
	
		$criteria = 1;
	
		if ($search_method and $crit) {
			$crit_escaped = doSlash($crit);
	
			$critsql = array(
				'id'			 => "id = '$crit_escaped'",
				'name'		 => "name like '%$crit_escaped%'",
				'category' => "category like '%$crit_escaped%'",
				'author'	 => "author like '%$crit_escaped%'"
			);
	
			if (array_key_exists($search_method, $critsql)) {
				$criteria = $critsql[$search_method];
				$limit = 500;
			} else {
				$search_method = '';
				$crit = '';
			}
		} else {
			$search_method = '';
			$crit = '';
		}
		$total = safe_count('txp_image', "$criteria");
	
		if ($total < 1) {
			if ($criteria != 1) {
				echo n.$this->search_form($crit, $search_method);
				$this->_message(gTxt('no_results_found'));
			} else {
				$this->_message(gTxt('no_images_recorded'));
			}	
			return;
		}
	
		$limit = max(@$image_list_pageby, 15);
	
		list($page, $offset, $numPages) = pager($total, $limit, $page);
	
		echo $this->search_form($crit, $search_method);
	
		$rs = safe_rows_start('*, unix_timestamp(date) as uDate', 'txp_image',
			"$criteria order by $sort_sql limit $offset, $limit");
	
		if ($rs) {
			echo n.n.startTable('list').
				n.tr(
					column_head('ID', 'id', $this->event, true, $switch_dir, $crit, $search_method, ('id' == $sort) ? $dir : '').
					hCell().
					column_head('date', 'date', $this->event, true, $switch_dir, $crit, $search_method, ('date' == $sort) ? $dir : '').
					column_head('name', 'name', $this->event, true, $switch_dir, $crit, $search_method, ('name' == $sort) ? $dir : '').
					column_head('thumbnail', 'thumbnail', $this->event, true, $switch_dir, $crit, $search_method, ('thumbnail' == $sort) ? $dir : '').
					hCell(gTxt('tags')).
					column_head('image_category', 'category', $this->event, true, $switch_dir, $crit, $search_method, ('category' == $sort) ? $dir : '').
					column_head('author', 'author', $this->event, true, $switch_dir, $crit, $search_method, ('author' == $sort) ? $dir : '').
					hCell()
				);
	
			while ($a = nextRow($rs)) {
				extract($a);
	
				$edit_url = "?event=$this->event".a.'step=edit'.a.'id='.$id.a.'sort='.$sort.
					a.'dir='.$dir.a.'page='.$page.a.'search_method='.$search_method.a.'crit='.$crit;
	
				$name = empty($name) ? gTxt('unnamed') : $name;
	
				$thumbnail = ($thumbnail) ?
					href('<img src="'.hu.$img_dir.'/'.$id.'t'.$ext.'" alt="" />', $edit_url) :
					gTxt('no');
	
				$tag_url = '?event=tag'.a.'tag_name=image'.a.'id='.$id.a.'ext='.$ext.
					a.'w='.$w.a.'h='.$h.a.'alt='.urlencode($alt).a.'caption='.urlencode($caption);
	
				$category = ($category) ? '<span title="'.fetch_category_title($category, 'image').'">'.$category.'</span>' : '';
	
				echo n.n.tr(
	
					n.td($id, 20).
	
					td(
						n.'<ul>'.
						n.t.'<li>'.href(gTxt('edit'), $edit_url).'</li>'.
						n.t.'<li><a href="'.hu.$img_dir.'/'.$id.$ext.'">'.gTxt('view').'</a></li>'.
						n.'</ul>'
					, 35).
	
					td(
						safe_strftime('%d %b %Y %X', $uDate)
					, 75).
	
					td(
						href($name, $edit_url)
					, 75).
	
					td($thumbnail, 76).
	
					td(
						'<ul>'.
						'<li><a target="_blank" href="'.$tag_url.a.'type=textile" onclick="popWin(this.href); return false;">Textile</a></li>'.
						'<li><a target="_blank" href="'.$tag_url.a.'type=textpattern" onclick="popWin(this.href); return false;">Textpattern</a></li>'.
						'<li><a target="_blank" href="'.$tag_url.a.'type=xhtml" onclick="popWin(this.href); return false;">XHTML</a></li>'.
						'</ul>'
					, 85).
	
					td($category, 75).
	
					td(
						'<span title="'.get_author_name($author).'">'.$author.'</span>'
					, 75).
	
					td(
						dLink($this->event, 'delete', 'id', $id)
					, 10)
				);
			}
	
			echo endTable().
	
			nav_form($this->event, $page, $numPages, $sort, $dir, $crit, $search_method).
	
			$this->pageby_form($this->event, $image_list_pageby);
		}
	}
	
	
// -------------------------------------------------------------

	function edit_view($id='') 
	{
		global $txpcfg,$img_dir,$file_max_upload_size;

		if (!$id) $id = assert_int(gps('id'));

		extract(gpsa(array('page', 'sort', 'dir', 'crit', 'search_method')));

		$categories = getTree("root", "image");
		
		$rs = safe_row("*", "txp_image", "id = $id");
		
		if ($rs) {
			extract($rs);
			echo startTable('edit'),
			tr(
				td(
					'<img src="'.hu.$img_dir.
						'/'.$id.$ext.'" height="'.$h.'" width="'.$w.'" alt="" '.
						"title='$id$ext ($w &#215; $h)' />".
						br.upload_form(gTxt('replace_image'),'replace_image_form',
							'replace',$this->event,$id,$file_max_upload_size, 'image-replace', '')
				)
			),
			tr(
				td(
					join('',
						array(
							($thumbnail)
							?	'<img src="'.hu.$img_dir.
								'/'.$id.'t'.$ext.'" alt="" />'.br
							:	'',
							upload_form(gTxt('upload_thumbnail'),'upload_thumbnail',
								'thumbnail_insert',$this->event,$id,$file_max_upload_size, 'upload-thumbnail', '')
						)
					)
				)
			),

			(check_gd($ext))
			?	$this->thumb_ui($id, $thumbnail)
			:	'',

			tr(
				td(
					form(
						graf('<label for="image-name">'.gTxt('image_name').'</label>'.br.
							fInput('text', 'name', $name, 'edit', '', '', '', '', 'image-name')).

						graf('<label for="image-category">'.gTxt('image_category').'</label>'.br.
							categorySelectInput('image', 'category', $category, 'image-category')).

						graf('<label for="alt-text">'.gTxt('alt_text').'</label>'.br.
							fInput('text', 'alt', $alt, 'edit', '', '', 50, '', 'alt-text')).

						graf('<label for="caption">'.gTxt('caption').'</label>'.br.
							text_area('caption', '100', '400', $caption, 'caption')).

						n.graf(fInput('submit', '', gTxt('save'), 'publish')).
						// web two.oh ;-) href(gTxt('or_cancel'), "?event=$this->event".a.'step=list').
						n.hInput('id', $id).
						n.eInput($this->event).
						n.sInput('save').
						n.hInput('sort', $sort).
						n.hInput('dir', $dir).
						n.hInput('page', $page).
						n.hInput('search_method', $search_method).
						n.hInput('crit', $crit)
					)
				)
			),
			endTable();
		}
	}
	
// -------------------------------------------------------------

	function save_post() 
	{
		extract(doSlash(gpsa(array('name','category','caption','alt'))));
		$id = $this->psi('id');
		
		safe_update(
			"txp_image",
			"name    = '$name',
			category = '$category',
			alt      = '$alt',
			caption  = '$caption'",
			"id = $id"
		);
		$this->_message(gTxt('image_updated', array('{name}' => $name)));
		$this->_set_view('list');
	}
  
// -------------------------------------------------------------

	function insert_post()
	{
		$meta = doSlash(gpsa(array('caption', 'alt', 'category')));
		$img_result = image_data($_FILES['thefile'], '');

		if (is_array($img_result)) {
			list($message, $id) = $img_result;
			$this->_message($message);
			$this->_set_view('edit', $id);
		} else {
			$this->_error(gTxt($img_result));
			$this->_set_view('list');
		}
	}
	
// -------------------------------------------------------------

	function delete_post() 
	{
		$id = assert_int(ps('id'));
		
		$rs = safe_row("*", "txp_image", "id = $id");
		if ($rs) {
			extract($rs);
			$rsd = safe_delete("txp_image","id = $id");
			$ul = unlink(IMPATH.$id.$ext);
			if(is_file(IMPATH.$id.'t'.$ext)) {
				$ult = unlink(IMPATH.$id.'t'.$ext);
			}

			if ($rsd && $ul) {
				$this->_message(gTxt('image_deleted', array('{name}' => $name)));
			}
		}
		$this->_set_view('list');
	}

// -------------------------------------------------------------

	function replace_post() 
	{	
		$id = assert_int(gps('id'));
		$rs = safe_row("*", "txp_image", "id = $id");
		
		if ($rs) {
			$meta = array('category' => $rs['category'], 'caption' => $rs['caption'], 'alt' => $rs['alt']);
		} else {
			$meta = '';
		} 

		$img_result = image_data($_FILES['thefile'], $meta, $id);
		
		if(is_array($img_result)) {
			list($message, $id) = $img_result;
			$this->_message($message);
		} else {
			$this->_error($img_result);
		}
		$this->_set_view('edit', $id);
	}

// -------------------------------------------------------------

	function thumbnail_insert_post() 
	{
		global $img_dir;
		$id = $this->psi('id');
		
		$file = $_FILES['thefile']['tmp_name'];
		$name = $_FILES['thefile']['name'];

		$file = get_uploaded_file($file);
		
		list(,,$extension) = @getimagesize($file);
	
		if (($file !== false) && @$this->extensions[$extension]) {
			$ext = $this->extensions[$extension];
			$newpath = IMPATH.$id.'t'.$ext;

			if (shift_uploaded_file($file, $newpath) == false) {
				image_list($newpath.sp.gTxt('upload_dir_perms'));
			} else {
				chmod($newpath,0644);
				safe_update("txp_image", "thumbnail = 1", "id = $id");

				$this->_message(gTxt('image_uploaded', array('{name}' => $name)));
				$this->_set_view('edit', $id);
			}
		} else {
			if ($file === false) {
				$this->_error(upload_get_errormsg($_FILES['thefile']['error']));
				$this->_set_view('edit', $id);
			} else {
				$this->_error(gTxt('only_graphic_files_allowed'));
				$this->_set_view('edit', $id);
			}
		}
	}

// -------------------------------------------------------------

	function thumbnail_create_post()
	{
		$id = $this->psi('id');
		extract(doSlash(gpsa(array('thumbnail_clear_settings', 'thumbnail_delete', 'width', 'height', 'crop'))));

		if($thumbnail_clear_settings) {
			$message = $this->thumbnail_clear_settings($id);			
		} elseif($thumbnail_delete) {
			$message = $this->thumbnail_delete($id);
		} else {
			$width = (int) $width;
			$height = (int) $height;	
			if ($width != 0 || $height != 0) {
				if (img_makethumb($id, $width, $height, $crop)) {
					global $prefs;
		
					if ($width == 0) $width = '';
					if ($height == 0) $height = '';
					$prefs['thumb_w'] = $width;
					$prefs['thumb_h'] = $height;
					$prefs['thumb_crop'] = $crop;
		
					// hidden prefs
					set_pref('thumb_w', $width, 'image',	2);
					set_pref('thumb_h', $height, 'image',	 2);
					set_pref('thumb_crop', $crop, 'image',	2);
		
					$message = gTxt('thumbnail_saved', array('{id}' => $id));
				} else {
					$message = gTxt('thumbnail_not_saved', array('{id}' => $id));
				}
			} else {
				$message = messenger('invalid_width_or_height', "($width)/($height)", '');
			}
		}
		$this->_message($message);
		$this->_set_view('edit', $id);
	}

// -------------------------------------------------------------

	function thumbnail_clear_settings($id)
	{
		set_pref('thumb_w', '', 'image', 2);
		set_pref('thumb_h', '', 'image', 2);
		set_pref('thumb_crop', 0, 'image', 2);
		$GLOBALS['prefs'] = get_prefs();
		return '';
	}


// -------------------------------------------------------------

	function thumbnail_delete($id)
	{
		$ext = safe_field('ext', 'txp_image', "id = $id");

		$file = IMPATH.DS.$id.'t'.$ext;

		if (unlink($file)) {
			safe_update('txp_image', 'thumbnail = 0', "id = $id");
			$message = gTxt('thumbnail_removed');
		} else {
			$message = gTxt('thumbnail_not_removed');
		}
		return $message;
	}

// -------------------------------------------------------------

	function thumb_ui($id,$thumbnail)
	{		
		global $prefs, $sort, $dir, $page, $search_method, $crit;
		extract($prefs);
		return
		tr(
			td(
				form(
					graf(gTxt('manage_thumbnail')).

					startTable('', 'left', '', 1).
					tr(
						tda(
							'<label for="width">'.gTxt('thumb_width').'</label>'.sp.
								fInput('text', 'width', @$thumb_w, 'edit', '', '', 4, '', 'width').sp.

							'<label for="height">'.gTxt('thumb_height').'</label>'.sp.
								fInput('text', 'height', @$thumb_h, 'edit', '', '', 4, '', 'height').sp.

							'<label for="crop">'.gTxt('keep_square_pixels').'</label>'.sp.
								checkbox('crop', 1, @$thumb_crop, '', 'crop')
						, ' class="noline" style="vertical-align: top;"').

						tda(
							graf(fInput('submit', 'create', gTxt('create'), 'smallerbox').
							($thumbnail ? sp.fInput('submit', 'thumbnail_delete', gTxt('remove'), 'smallerbox') : '')).

							graf(fInput('submit', 'thumbnail_clear_settings', gTxt('clear_settings'), 'smallerbox'))
						, ' colspan="6" class="noline"')
					).
					endTable().

					n.hInput('id', $id).
					n.eInput($this->event).
					n.sInput('thumbnail_create').

					n.hInput('sort', $sort).
					n.hInput('dir', $dir).
					n.hInput('page', $page).
					n.hInput('search_method', $search_method).
					n.hInput('crit', $crit)
				)
			)
		);
	}

// -------------------------------------------------------------

	function search_form($crit, $method)
	{
		$methods =	array(
			'id'       => gTxt('ID'),
			'name'     => gTxt('name'),
			'category' => gTxt('image_category'),
			'author'	 => gTxt('author')
		);
		return search_form($this->event, 'list', $crit, $methods, $method, 'name');
	}

// -------------------------------------------------------------
	function change_pageby()
	{
		event_change_pageby($this->event);
		$this->_set_view('list');
	}
	
}
?>
