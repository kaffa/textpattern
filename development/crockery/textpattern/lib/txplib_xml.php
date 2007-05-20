<?php

/*
Copyright 2006 Alex Shiels http://thresholdstate.com/

$HeadURL$
$LastChangedRevision$
*/


class txpXmlParser {

	var $xml;
	var $result;
  
	function txpXmlParser() {

		$this->xml = xml_parser_create('UTF-8');
		xml_parser_set_option($this->xml,XML_OPTION_CASE_FOLDING, 0);
		xml_set_object($this->xml,$this);
		xml_set_character_data_handler($this->xml, 'dataHandler');   
		xml_set_element_handler($this->xml, "startHandler", "endHandler");
		xml_set_default_handler($this->xml, 'defaultHandler');
	}
  
  
	function parse($data, $is_final=true) {
		// this works with partial data - use $is_final=false
		
		if (!xml_parse($this->xml, $data, $is_final)) {
			trigger_error(sprintf("XML error: %s at line %d",
				xml_error_string(xml_get_error_code($this->xml)),
				xml_get_current_line_number($this->xml)));
				xml_parser_free($this->xml);
		}
	
		if ($is_final)	
			return $this->result;

		// return partial data and reset
		$out = $this->result;
		$this->result = NULL;
		return $out;
	}

	function startHandler($parser, $tag, $atts) {
	}

	function dataHandler($parser, $data) {
	}

	function endHandler($parser, $tag) {
	}

	function defaultHandler($parser, $data) {
	}

}

class txpBackupParser extends txpXmlParser {

	var $textpattern;
	var $record;
	var $field;
	var $thisrec;

	function txpBackupParser() {
		return $this->txpXmlParser();
	}

	function startHandler($parser, $tag, $atts) {
		if ($tag == 'record') {
			$this->record = $atts;
		}
		elseif ($tag == 'field') {
			$this->field = $atts;
			if (!empty($this->field['name'])) {
				$field = $this->field['name'];
				$this->thisrec[$field] = '';
			}
		}
		elseif ($tag == 'textpattern') {
			$this->textpattern = $atts;
		}

	}

	function dataHandler($parser, $data) {
		if (!empty($this->field['name'])) {
			$field = $this->field['name'];
			$this->thisrec[$field] .= $data;
		}
	}

	function endHandler($parser, $tag) {
		if ($tag == 'record') {
			if (!empty($this->record['table'])) {
				$table = $this->record['table'];
				$this->result[$table][] = $this->thisrec;
			}
			$this->record = NULL;
			$this->thisrec = NULL;
		}
		elseif ($tag == 'field')
			$this->field = NULL;
	}

}

// -------------------------------------------------------------
	function xml_backup_table($table, $where='1=1')
	{
		$out = '';
		$rs = safe_rows_start('*', $table, $where);

		while ($rs and $row = nextRow($rs)) {
			$out .= '<record table="'.$table.'">'.n;
			foreach ($row as $k=>$v)
				if ($v !== NULL)
					$out .= '<field name="'.$k.'">'.escape_output($v).'</field>'.n;
			$out .= '</record>'.n;
		}

		return $out;
	}

// -------------------------------------------------------------
	function xml_backup($tables=array())
	{
		global $thisversion;

		// Assume all tables if none are specified
		if (empty($tables))
			$tables = safe_table_list();

		$out = '<?xml version="1.0" encoding="UTF-8"?>'.n;
		$out .= '<textpattern version="'.$thisversion.'">'.n;

		foreach ($tables as $table)
			$out .= xml_backup_table($table);

		$out .= '</textpattern>'.n;
	}

// -------------------------------------------------------------
	function xml_restore_decode($xml)
	{
		$parser = new txpBackupParser();

		return $parser->parse($xml);
	}

// -------------------------------------------------------------
	function xml_restore_backup($xml, $tables=array())
	{
		$data = xml_restore_decode($xml);
	
		// Assume all tables if none are specified
		if (empty($tables))
			$tables = safe_table_list();

		foreach (array_keys($data) as $table) {
			if (in_array($table, $tables)) {
				foreach ($data[$table] as $rec) {
					// FIXME: this should ignore duplicate key errors only
					@safe_insert_rec($table, $rec);
				}
			}
		}
	}

?>
