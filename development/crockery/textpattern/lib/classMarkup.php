<?php

include_once(txpath . '/lib/classTextile.php');
include_once(txpath . '/lib/txplib_class.php');

// Abstract class for markup methods like nl2br, Textile, etc
class txpMarkup {
	function txpMarkup() {
	}

	function doMarkup($in) {
	}

	function sidehelp($where = '') {
	}
}

function do_markup($type, $in) {

	$markup_types = get_classes('txpmarkup');
	if (isset($markup_types[$type]))
		$obj = get_singleton($type);
	else
		$obj = get_singleton('txpnl2br');
	return $obj->doMarkup($in);

}

function get_markup_types() {
	static $types = array();

	if (!empty($types))
		return $types;

	$classes = get_classes('txpmarkup');

	foreach ($classes as $type)
		# ignore the txpMarkup parent class
		if ($type != 'txpmarkup')
			$types[$type] = gTxt($type);

	return $types;
}

// A few simple concrete markup classes

class txpRawXHTML extends txpMarkup {
	function doMarkup($in) {
		return trim($in);
	}
}

class txpNL2br extends TxpMarkup {
	function doMarkup($in) {
		return nl2br(trim($in));
	}
}

class txpStripTags extends TxpMarkup {
	function doMarkup($in) {
		$allow_tags = '<p><a><b><i><pre><blockquote>';
		return nl2br(trim(strip_tags($in, $allow_tags)));
	}
}

// Textile interface classes

class txpTextile extends TxpMarkup {
	var $textile;

	function txpTextile() {
		$this->txpMarkup();

		$this->textile = new Textile();
	}

	function doMarkup($in) {
		return $this->textile->TextileThis($in);
	}

	function sidehelp($where = '')
	{
		$where = ($where) ? br.' ('.$where.')' : '';

		return n.'<h3 class="plain"><a href="#" onclick="toggleDisplay(\'textile_help\'); return false;">'.gTxt('textile_help').$where.'</a></h3>'.

			n.'<div id="textile_help" style="display: none;">'.

			n.'<ul class="plain-list small">'.
				n.t.'<li>'.gTxt('header').': <strong>h<em>n</em>.</strong>'.sp.
					popHelpSubtle('header', 400, 400).'</li>'.
				n.t.'<li>'.gTxt('blockquote').': <strong>bq.</strong>'.sp.
					popHelpSubtle('blockquote',400,400).'</li>'.
				n.t.'<li>'.gTxt('numeric_list').': <strong>#</strong>'.sp.
					popHelpSubtle('numeric', 400, 400).'</li>'.
				n.t.'<li>'.gTxt('bulleted_list').': <strong>*</strong>'.sp.
					popHelpSubtle('bulleted', 400, 400).'</li>'.
			n.'</ul>'.

			n.'<ul class="plain-list small">'.
				n.t.'<li>'.'_<em>'.gTxt('emphasis').'</em>_'.sp.
					popHelpSubtle('italic', 400, 400).'</li>'.
				n.t.'<li>'.'*<strong>'.gTxt('strong').'</strong>*'.sp.
					popHelpSubtle('bold', 400, 400).'</li>'.
				n.t.'<li>'.'??<cite>'.gTxt('citation').'</cite>??'.sp.
					popHelpSubtle('cite', 500, 300).'</li>'.
				n.t.'<li>'.'-'.gTxt('deleted_text').'-'.sp.
					popHelpSubtle('delete', 400, 300).'</li>'.
				n.t.'<li>'.'+'.gTxt('inserted_text').'+'.sp.
					popHelpSubtle('insert', 400, 300).'</li>'.
				n.t.'<li>'.'^'.gTxt('superscript').'^'.sp.
					popHelpSubtle('super', 400, 300).'</li>'.
				n.t.'<li>'.'~'.gTxt('subscript').'~'.sp.
					popHelpSubtle('subscript', 400, 400).'</li>'.
			n.'</ul>'.

			n.graf(
				'"'.gTxt('linktext').'":url'.sp.popHelpSubtle('link', 400, 500)
			, ' class="small"').

			n.graf(
				'!'.gTxt('imageurl').'!'.sp.popHelpSubtle('image', 500, 500)
			, ' class="small"').

			n.graf(
				'<a href="http://textism.com/tools/textile/" target="_blank">'.gTxt('More').'</a>').

		n.'</div>';
	}
}

class txpTextileLite extends TxpMarkup {
	var $textile;

	function txpTextileLite() {
		$this->txpMarkup();

		$this->textile = new Textile();
	}

	function doMarkup($in) {
		return $this->textile->TextileThis($in, 1);
	}

	function sidehelp($where = '')
	{
		$where = ($where) ? br.' ('.$where.')' : '';

		return n.'<h3 class="plain"><a href="#" onclick="toggleDisplay(\'textile_lite_help\'); return false;">'.gTxt('textile_lite_help').$where.'</a></h3>'.

			n.'<div id="textile_lite_help" style="display: none;">'.

			n.'<ul class="plain-list small">'.
				n.t.'<li>'.'_<em>'.gTxt('emphasis').'</em>_'.sp.
					popHelpSubtle('italic', 400, 400).'</li>'.
				n.t.'<li>'.'*<strong>'.gTxt('strong').'</strong>*'.sp.
					popHelpSubtle('bold', 400, 400).'</li>'.
				n.t.'<li>'.'??<cite>'.gTxt('citation').'</cite>??'.sp.
					popHelpSubtle('cite', 500, 300).'</li>'.
				n.t.'<li>'.'-'.gTxt('deleted_text').'-'.sp.
					popHelpSubtle('delete', 400, 300).'</li>'.
				n.t.'<li>'.'+'.gTxt('inserted_text').'+'.sp.
					popHelpSubtle('insert', 400, 300).'</li>'.
				n.t.'<li>'.'^'.gTxt('superscript').'^'.sp.
					popHelpSubtle('super', 400, 300).'</li>'.
				n.t.'<li>'.'~'.gTxt('subscript').'~'.sp.
					popHelpSubtle('subscript', 400, 400).'</li>'.
			n.'</ul>'.

			n.graf(
				'"'.gTxt('linktext').'":url'.sp.popHelpSubtle('link', 400, 500)
			, ' class="small"').

			n.graf(
				'!'.gTxt('imageurl').'!'.sp.popHelpSubtle('image', 500, 500)
			, ' class="small"').

			n.graf(
				'<a href="http://textism.com/tools/textile/" target="_blank">'.gTxt('More').'</a>').

			n.'</div>';
	}
}

?>
