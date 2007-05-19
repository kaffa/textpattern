<?php

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
		$out[] = '<table class="txp-tableview '.$this->class." $this->type".'">';
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
	function i_text($name, $value='') {
		return tag(
				tag('<label for="'.$name.'">'.gTxt($name).'</label> '.pophelp($name), $this->ltag)
				.tag(fInput('text', $name, $value, 'edit', gTxt($name), '', 40, '', $name), $this->itag),
			$this->rowtag
		);
	}

	// textarea
	function i_textarea($name, $value='') {
		return tag(
				tag('<label for="'.$name.'">'.gTxt($name).'</label> '.pophelp($name), $this->ltag)
				.tag(fInput('text', $name, $value, 'edit', gTxt($name), '', 40, '', $name), $this->itag),
			$this->rowtag
		);
	}

	// checkbox
	function i_checkbox($name, $checked=0) {
		// there's no itag here, just ltag
		return tag(
				tag(checkbox($name, 1, $checked, '', $name).sp.
				'<label for="'.$name.'">'.gTxt($name).'</label> '.pophelp($name), $this->ltag),
			$this->rowtag
		);
	}

	// select option list
	function i_select($name, $choices, $value='') {
		return
			tag('<label for="'.$name.'">'.gTxt($name).'</label> '.pophelp($name), $this->ltag)
			.tag(selectInput($name, $choices, $value, '', '', gTxt($name)), $this->itag);
	}

	// select option list implemented as a group of radio buttons
	function i_select_radio($name, $choices, $value='') {
		// FIXME: not sure what the itag/ltag markup should be here
		$out = array();
		foreach ($choices as $k=>$v) {
			// each radio button goes inside an itag
			$out[] = radio($name, $k, ($k == $value), "{$name}_{$k}").sp.
				'<label for="'.$name.'_'.$k.'">'.$v.'</label>';
		}

		return tag(
				tag('<label for="'.$name.'">'.gTxt($name).'</label> '.pophelp($name), $this->ltag)
				.tag(fieldset(join(br.n, $out), '', $name), $this->itag),
			$this->rowtag
		);
	}

	// submit button
	function i_button($name, $value='1') {
		return tag(
				tag('<button type="submit" name="'.$name.'" value="'.$value.'" id="'.$name.'">'.gTxt($name).'</button>', $this->itag),
			$this->rowtag
		);
	}

	// hidden value
	function i_hidden($name, $value='') {
		return hInput($name, $value);
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
			$this->head().n.
			form(
				tag($this->body(), $this->listtag).n.
				eInput($this->event).n.
				sInput($this->step)
			).n.
			$this->foot();
	}

}

?>
