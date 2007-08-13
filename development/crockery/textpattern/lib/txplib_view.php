<?php

/*
$HeadURL$
$LastChangedRevision$
*/

include_once(txpath.'/lib/txplib_html.php');

// A generic table list view for tabs like the article list, file list, image list
class TxpTableView {

	var $class;
	var $caption;
	var $rows;
	var $edit_actions = array();
	var $type;
	var $count = 0;

	function TxpTableView(&$rows, $caption='', $edit_actions=array(), $type='list') {
		$this->class = strtolower(get_class($this));
		$this->caption = $caption;
		$this->rows = $rows;
		$this->edit_actions = $edit_actions;
		$this->type = $type;
	}

	function head($cols) {
		$col = array();
		$th = array();

		foreach ($cols as $c) {
			$col[] = '<col id="col-'.strtolower($c).'" />';
			$th[] = tag(gTxt($this->class.'.col-'.strtolower($c)), 'th');
		}

		if ($this->edit_actions) {
			$col[] = '<col id="col-multiedit" />';
			$th[] = tag('&nbsp;', 'th');
		}

		return
			'<colgroup>'.n.join(n, $col).n.'</colgroup>'.n.
			'<thead>'.n.'<tr>'.n.join(n, $th).n.'</tr>'.n.'</thead>';

	}

	function body() {
		$out = array();
		foreach ($this->rows as $row) {
			$out[] = $this->row($row);
		}

		return '<tbody>'.n.join(n, $out).n.'</tbody>';
	}

	function foot() {
		return '';
	}

	function row($row) {
		$tr = array();
		foreach ($row as $v)
			$tr[] = $v;

		if ($this->edit_actions and isset($row['id']))
			$tr[] = fInput('checkbox', 'selected[]', $row['id']);

		return doWrap($tr, 'tr', 'td', 'row-'.(++$this->count % 2 ? 'odd' : 'even'));
	}

	function table() {
		$out = array();

		$out[] = $this->head(array_keys($this->rows[0]));

		$out[] = $this->body();

		$out[] = $this->foot();

		return join(n, $out);
	}

	function render() {
		$out = array();
		$out[] = '<table class="txptableview '.$this->class." $this->type\">";
		if ($this->caption)
			$out[] = '<caption>'.escape_output($this->caption).'</caption>';
		$out[] = $this->table();
		$out[] = '</table>';

		return join(n, $out);
	}
}

// A base class detail view for things like the file edit view, link edit view
class TxpDetailView {
	var $class;
	var $caption;
	var $rows;
	var $edit_actions = array();
	var $type;
	var $count = 0;

	var $event;
	var $step;

	var $data; // the thing being displayed

	var $headtag = 'h3';
	var $listtag = 'dl';  // could be 'table'
	var $rowtag = '';     // could be 'tr'
	var $ltag = 'dt';     // could be 'td'
	var $itag = 'dd';     // could be 'td'

	function TxpDetailView($data, $event, $step, $caption='') {
		$this->class = strtolower(get_class($this));
		if ($caption)
			$this->caption = $caption;
		else
			$this->caption = $event;
		$this->event = $event;
		$this->step = $step;
		$this->data = $data;
	}

	// call these i_* functions from body() to create input fields

	// text input box
	function i_text($name, $value='', $opts = array()) {
		return tag(
				tag($this->label($name, $opts).' '.pophelp($name), $this->ltag)
				.tag(fInput('text', $name, $value, $this->_class($opts), gTxt($name), '', 40, '', $name), $this->itag),
			$this->rowtag
		);
	}

	// textarea
	function i_textarea($name, $value='', $opts = array()) {
		return tag(
				tag($this->label($name, $opts).' '.pophelp($name), $this->ltag)
				.tag(fInput('text', $name, $value, $this->_class($opts), gTxt($name), '', 40, '', $name), $this->itag),
			$this->rowtag
		);
	}

	// checkbox
	function i_checkbox($name, $checked=0, $opts = array()) {
		// there's no itag here, just ltag
		return tag(
				tag(checkbox($name, 1, $checked, '', $name).sp.
				$this->label($name, $opts).' '.pophelp($name), $this->ltag),
			$this->rowtag
		);
	}

	// select option list
	function i_select($name, $choices, $value='', $opts = array()) {
		return tag(
			tag($this->label($name, $opts).' '.pophelp($name), $this->ltag)
			.tag(selectInput($name, $choices, $value, '', '', $name), $this->itag),
			$this->rowtag
		);
	}

	// select option list implemented as a group of radio buttons
	function i_select_radio($name, $choices, $value='', $opts = array()) {
		// FIXME: not sure what the itag/ltag markup should be here
		$out = array();
		foreach ($choices as $k=>$v) {
			// each radio button goes inside an itag
			$out[] = radio($name, $k, ($k == $value), "{$name}_{$k}").sp.
				'<label for="'.$name.'_'.$k.'">'.$v.'</label>';
		}

		return tag(
				tag($this->label($name, $opts).' '.pophelp($name), $this->ltag)
				.tag(fieldset(join(br.n, $out), '', $name), $this->itag),
			$this->rowtag
		);
	}

	// submit button
	function i_button($name, $value='1', $opts = array()) {
		return tag(
				tag('<button type="submit" name="'.$name.'" value="'.$value.'" id="'.$name.'">'.gTxt($name).'</button>', $this->itag),
			$this->rowtag
		);
	}

	// hidden value
	function i_hidden($name, $value='') {
		return hInput($name, $value);
	}
	
	function label($name, $opts=array()) {
		return '<label for="'.$name.'" class="'.$this->_class($opts).'">'.gTxt($name).'</label>';
	}

	function _class($opts) {
		$class = '';
		if (!empty($opts['class']))
			$class = $opts['class'];
		elseif (!empty($opts['readonly']))
			$class = 'readonly';
		else
			$class = 'edit';

		return $class;
	}

	// html form with the event and step filled in
	function form($contents) {
		return form(
			$contents.
			eInput($this->event).
			sInput($this->step)
		);
	}

	function head() {
		return tag(gTxt($this->caption), $this->headtag);
	}

	function body() {
		// override me
	}

	function foot() {
		// override me
	}

	function render() {
		return
			'<div class="txpdetailview '.$this->class.'">'.n.
			$this->head().n.
			form(
				tag($this->body(), $this->listtag).n.
				eInput($this->event).n.
				sInput($this->step)
			).n.
			$this->foot().n.
			'</div>';
	}

}

?>
