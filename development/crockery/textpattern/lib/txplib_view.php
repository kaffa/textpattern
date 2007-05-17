<?php

include_once(txpath.'/lib/txplib_html.php');

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

?>
