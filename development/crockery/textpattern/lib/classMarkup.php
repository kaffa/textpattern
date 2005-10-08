<?php

include_once(txpath . '/lib/classTextile.php');

// Abstract class for markup methods like nl2br, Textile, etc
class txpMarkup {
	function txpMarkup() {
	}

	function doMarkup($in) {
	}
}

function do_markup($type, $in) {
	static $objs;

	// default to nl2br if the requested class is unavailable
	if (@strtolower(get_parent_class($type)) != 'txpmarkup')
		$type = 'txpNL2br';
	$type = strtolower($type);

	// Cache singleton objects
	if (empty($objs[$type]))
		$objs[$type] = new $type();

	return $objs[$type]->doMarkup($in);
}

function get_markup_types() {
	static $types = array();

	if (!empty($types))
		return $types;

	foreach (get_declared_classes() as $class) {
		$class = strtolower($class);
		if (@strtolower(get_parent_class($class)) == 'txpmarkup')
			$types[$class] = gTxt($class);
	}

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

	function txpTextile() {
		$this->txpMarkup();

		$this->textile = new Textile();
	}

	function doMarkup($in) {
		return $this->textile->TextileThis($in, 1);
	}
}


?>
