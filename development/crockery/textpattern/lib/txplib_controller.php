<?php

/*

$HeadURL: http://svn.textpattern.com/development/crockery/textpattern/elements/include/txp_section.php $
$LastChangedRevision: 1944 $

*/


// This is a kind of hybrid view-controller.

class ZemAdminController {
	var $event = NULL;
	var $default_step = 'default';

	var $_messages = array();
	var $_step;

	function ZemAdminController() {
	}

	function event_handler($event, $step) {
		// the generic event handler

		$this->event = $event;
		if (!$step)
			$step = $this->default_step;
		$this->_set_step($step);

		if ($this->_method() == 'POST') {
			// call $this->{$step}_post() if it exists
			$step_post = "{$step}_post";
			if (is_callable(array(&$this, $step_post))) {
				$this->$step_post();
			}
		}

		// now call $this->{$this->_step}_view()
		$step_view = "{$this->_step}_view";
		if (is_callable(array(&$this, $step_view))) {
			// ob buffering is for legacy reasons -- ideally, _view methods should return
			// output, not echo it, but this will help older code work during migration
			ob_start();
			$out = $this->$step_view();
			$out .= ob_get_clean();
			$this->_render($out);
		}
		else {
			txp_die('404 Not Found');
		}

	}

// ---------------------------------------------------------
// functions for use by child classes

	function _method() {
		// return the request method
		return strtoupper(serverSet('REQUEST_METHOD'));
	}

	function _render($out) {
		pagetop(gTxt('sections'));

		$msg = '';
		foreach ($this->_messages as $m) {
			$msg .= '<div class="'.$m[0].'">'.n
			.$m[1].n
			.'</div>'.n;
		}

		echo '<div id="main-view">'.n
			.$msg
			.$out.n
			.'</div>';
	}

	function _dLink($step, $thing, $value, $verify=1) {
		return dLink($this->event, $step, $thing, $value, $verify);
	}

	function _set_step($step) {
		// use this in a _post handler to switch to a different view
		$this->_step = $step;
	}

	function _fieldset($contents, $name='') {
		return '<fieldset id="'.$name.'">'.n
			.($name ? '<legend>'.gTxt($name).'</legend>'.n : '')
			.$contents.n
			.'</fieldset>'.n;
	}

	function _tform($contents, $name='') {
		return form(
			$this->_fieldset(
				startTable('list').
				$contents.
				endTable(),
				$name
			)
		);
	}

	function _irow($name, $input) {

		if ($name) $label = '<label for="'.$name.'">'.gTxt("{$this->event}_{$name}").'</label>';
		else $label = '';

		return tr(
			td($label).
			td($input)
		);
	}

	// display a message to the user
	// $type is used as the CSS class
	function _message($msg, $type='message') {
		$this->_messages[] = array($type, $msg);
	}

	function _error($msg) {
		$this->_message($msg, 'error');
	}
}

function register_controller($classname, $event) {
	global $txp_controllers;

	$func = 'txp_'.$classname.'_controller';

	$code =
		'function '.$func.'($event, $step) {
			$o = new '.$classname.'();
			$o->event_handler($event, $step);
		}';

	eval($code);

	// register an event handler and tab
	register_callback($func, $event);
	@$txp_controllers[$event] == $classname;
}

function controller_name($event) {
	global $txp_controllers;
	
	return @$txp_controllers[$event];
}

?>