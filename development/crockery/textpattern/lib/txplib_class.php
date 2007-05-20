<?php

/*
$HeadURL$
$LastChangedRevision$
*/

// Copyright 2006 Alex Shiels http://thresholdstate.com/


/*
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
*/

function is_subclass($child, $parent) {
	// return true if class $child is the same as, or a child class of, $parent

	if (strcasecmp($child, $parent) == 0)
		return true;
	$p = get_parent_class($child);
	while ($p) {
		if (strcasecmp($p, $parent) == 0)
			return true;
		$p = get_parent_class($p);
	}
	return false;
}

function get_ancestor($class) {
	$p = get_parent_class($class);
	while ($p) {
		$class = $p;
		$p = get_parent_class($class);
	}
	return strtolower($class);
}

function get_classes($parent) {
	static $classes = array();

	$parent = strtolower($parent);

	if (!empty($classes[$parent]))
		return $classes[$parent];

	$classes[$parent] = array();
	foreach (get_declared_classes() as $class) {
		$class = strtolower($class);
		$p = get_ancestor($class);
		$classes[$p][$class] = $class;
	}

	return $classes[$parent];
}

function &get_singleton($class) {
	static $objs = array();

	$class = strtolower($class);
	if (!empty($objs[$class]))
		return $objs[$class];

	$objs[$class] = new $class();
	return $objs[$class];
}

function find_class_method($parent, $method) {
	// find the first class from $parent or its children that has a method named $method
	static $methods = array();

	$method = strtolower($method);
	if (isset($methods[$parent]))
		return @$methods[$parent][$method];

	$classes = get_classes($parent);
	$methods[$parent] = array();
	foreach ($classes as $class) {
		$class_methods = get_class_methods($class);
		$methods[$parent] = array_merge(
			array_combine($class_methods, array_fill(0, count($class_methods), $class)),
			$methods[$parent]);
	}

	return @$methods[$parent][$method];
}

?>
