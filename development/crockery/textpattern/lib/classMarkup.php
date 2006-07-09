<?php

include_once(txpath . '/lib/classTextile.php');

// Abstract class for markup methods like nl2br, Textile, etc
class txpMarkup {
	function txpMarkup() {
	}

	function doMarkup($in) {
	}

	function sidehelp() {
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
}


?>
