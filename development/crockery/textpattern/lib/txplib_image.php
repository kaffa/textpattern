<?php
/*
Copyright 2005-2006 Alex Shiels http://thresholdstate.com/

$HeadURL$
$LastChangedRevision$

*/


function img_newsize($old_w, $old_h, $max_w, $max_h, $crop=0) {

	if (!$max_w) $max_w = $max_h;
	if (!$max_h) $max_h = $max_w;

	if (!$max_w or !$max_h)
		return false;

	if ($crop) {
		$new_w = min($max_w, $old_w);
		$new_h = min($max_h, $old_h);

		$ratio = max($new_w / $old_w, $new_h / $old_h);

		$crop_w = $new_w / $ratio;
		$crop_h = $new_h / $ratio;

		$s_x = ($old_w - $crop_w)/2;
		$s_y = ($old_h - $crop_h)/2;
	}
	else {
		$s_x = 0;
		$s_y = 0;
		$crop_w = $old_w;
		$crop_h = $old_h;

		list($new_w, $new_h) = img_fit($old_w, $old_h, $max_w, $max_h);
	}

	# int dst_x, int dst_y, int src_x, int src_y, int dst_w, int dst_h, int src_w, int src_h
	return array(0, 0, $s_x, $s_y, $new_w, $new_h, $crop_w, $crop_h);
}

function img_downsize($old_fn, $new_fn, $max_w, $max_h, $crop=0, $q=75, $interlace=0) {
	list($old_w, $old_h, $type) = getimagesize($old_fn);

	// Make sure we have enough memory if the image is large 
	if (max($old_w, $old_h) > 1024)
		// this won't work on all servers but it's worth a try 
		ini_set('memory_limit', EXTRA_MEMORY);

	$old_img = null;
	if ($type == 1)
		$old_img = imagecreatefromgif($old_fn);
	elseif ($type == 2)
		$old_img = imagecreatefromjpeg($old_fn);
	elseif ($type == 3)
		$old_img = imagecreatefrompng($old_fn);

	if (!$old_img) {
		trigger_error('error_loading_image', array('{file}' => $old_fn));
		return false;
	}

	$newsize = img_newsize($old_w, $old_h, $max_w, $max_h, $crop);
	if (!$newsize) {
		trigger_error('invalid_size', array('{w}' => $max_w, '{h}' => $max_h));
		return false;
	}

	list($dst_x, $dst_y, $src_x, $src_y, $dst_w, $dst_h, $src_w, $src_h) = $newsize;

	$new_img = imagecreatetruecolor($dst_w, $dst_h);
	if (imagecopyresampled($new_img, $old_img, $dst_x, $dst_y, $src_x, $src_y, $dst_w, $dst_h, $src_w, $src_h)) {
		$r = false;
		if (file_exists($new_fn)) {
			unlink($new_fn);
		}
		if ($type == 1) {
			imagetruecolortopalette($new_img, false, 255);
			$r = imagegif($new_img, $new_fn);
		}
		elseif ($type == 2) {
			imageinterlace($new_img, $interlace);
			$r = imagejpeg($new_img, $new_fn, $q);
		}
		elseif ($type == 3) {
			$r = imagepng($new_img, $new_fn, $q);
		}
		if (!$r)
			trigger_error('error_creating_new_image', array('{file}' => $old_fn));
		return $r;
	}
	else {
		trigger_error('error_creating_new_image', array('{file}' => $old_fn));
		return false;
	}

}

// fit an image into a bounding box
function img_fit($old_w, $old_h, $max_w, $max_h) {

	$ratio = min(1.0, $max_w / $old_w, $max_h / $old_h);

	return array(floor($old_w * $ratio), floor($old_h * $ratio));
}

function img_makethumb($id, $w, $h, $crop) {
	$rs = safe_row("*", "txp_image", "id='".doSlash($id)."' limit 1");
	if ($rs and (intval($w) or intval($h))) {
		if (img_downsize(IMPATH.$id.$rs['ext'], IMPATH.$id.'t'.$rs['ext'], $w, $h, $crop))
			return safe_update("txp_image", "thumbnail='1'", "id='".doSlash($id)."'");
	}
	return false;
}

// check GD info
function check_gd($image_type) {
	// GD is installed
	if (function_exists('gd_info')) {
		$gd_info = gd_info();

		switch ($image_type) {
			// check gif support
			case '.gif':
				return ($gd_info['GIF Create Support'] == 1) ? true : false;
			break;

			// check png support
			case '.png':
				return ($gd_info['PNG Support'] == 1) ? true : false;
			break;

			// check jpg support
			case '.jpg':
				return ($gd_info['JPG Support'] == 1) ? true : false;
			break;

			// unsupported format
			default:
				return false;
			break;
		}
	} else { // GD isn't installed
		return false;
	}
}

// -------------------------------------------------------------
// Allow other - plugin - functions to
// upload images without the need for writting duplicated code.

function image_data($file , $meta = '', $id = '', $uploaded = true)
{
	global $txpcfg, $txp_user, $prefs, $file_max_upload_size;
	$extensions = array(0,'.gif','.jpg','.png','.swf');

	extract($txpcfg);

	$name = $file['name'];
	$error = $file['error'];
	$file = $file['tmp_name'];

	if ($uploaded) {
		$file = get_uploaded_file($file);

		if ($file_max_upload_size < filesize($file)) {
			unlink($file);
			return upload_get_errormsg(UPLOAD_ERR_FORM_SIZE);
		}
	}

	list($w, $h, $extension) = @getimagesize($file);

	if (($file !== false) && @$extensions[$extension]) {
		$ext = $extensions[$extension];

		$name = doSlash(substr($name, 0, strrpos($name, '.')).$ext);

		if ($meta == false)	{
			$meta = array('category' => '', 'caption' => '', 'alt' => '');
		}

		extract(doSlash($meta));

		$q = "
			name = '$name',
			ext = '$ext',
			w = $w,
			h = $h,
			alt = '$alt',
			caption = '$caption',
			category = '$category',
			date = now(),
			author = '$txp_user'
		";

		if (empty($id)) {
			$rs = safe_insert('txp_image', $q);
			$id = $GLOBALS['ID'] = mysql_insert_id();
		} else {
			$id = assert_int($id);
			$rs = safe_update('txp_image', $q, "id = $id");
		}

		if (!$rs) {
			return gTxt('image_save_error');
		} else {
			$newpath = IMPATH.$id.$ext;

			if (shift_uploaded_file($file, $newpath) == false) {
				$id = assert_int($id);
				safe_delete('txp_image', "id = $id");
				safe_alter('txp_image', "auto_increment = $id");
				if (isset($GLOBALS['ID'])) {
					unset( $GLOBALS['ID']);
				}

				return $newpath.sp.gTxt('upload_dir_perms');
			} else {
				@chmod($newpath, 0644);

				// Auto-generate a thumbnail using the last settings
				if (isset($prefs['thumb_w'], $prefs['thumb_h'], $prefs['thumb_crop'])) {
					img_makethumb($id, $prefs['thumb_w'], $prefs['thumb_h'], $prefs['thumb_crop']);
				}

				update_lastmod();

				$message = gTxt('image_uploaded', array('{name}' => $name));
				return array($message, $id);
			}
		}
	} else { 
		// missing or invalid file
		if ($file === false) {
			return upload_get_errormsg($error);
		} else {
			return gTxt('only_graphic_files_allowed');
		}
	}
}

?>
