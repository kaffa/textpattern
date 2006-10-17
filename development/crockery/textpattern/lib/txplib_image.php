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

	list($dst_x, $dst_y, $src_x, $src_y, $dst_w, $dst_h, $src_w, $src_h) = img_newsize($old_w, $old_h, $max_w, $max_h, $crop);

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
	if ($rs) {
		if (img_downsize(IMPATH.$id.$rs['ext'], IMPATH.$id.'t'.$rs['ext'], $w, $h, $crop))
			return safe_update("txp_image", "thumbnail='1'", "id='".doSlash($id)."'");
	}
	return false;
}

?>