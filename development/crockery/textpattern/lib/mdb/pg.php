<?php

/*
$HeadURL$
$LastChangedRevision$
*/

/**
 * PostgreSQL specific functions
 */

	define('DB_AUTOINC', 'SERIAL NOT NULL');
	define('NULLDATETIME', 'allballs');

class MDB_pg extends MDB {
	var $last_query = array();

	function connect($host, $user, $pass, $dbname='') {
		return pg_connect(($host and $host != 'localhost' ? "host='".addslashes($host)."' " : '')."user='".addslashes($user)."' password='".addslashes($pass)."' dbname='".addslashes($dbname)."'");
	}

	function selectdb($dbname) {
		return (pg_connection_status($this->link) === PGSQL_CONNECTION_OK);
	}

	function do_query($q) {
		$r = pg_query($this->link, $q);
		$this->last_query[$r] = $q;
		return $r;
	}

	function insert($table, $set) {
		$set = $this->my_insert_to_values($set);

		if (!$this->query('INSERT INTO '.$table.' '.$set)) {
			return false;
		}

		$last = $this->last_insert_id($table);
		return ($last ? $last : true);
	}
	
	function table_exists($tbl) {
		return @pg_meta_data($this->link, $tbl) != false;
	}

	function table_list() {
		if ($rs = $this->do_query('select relname from pg_stat_user_tables order by relname'))
			$out = pg_fetch_all($rs);

		$rs = array();
		if ($out) {
			foreach ($out as $r)
				$rs[] = array_shift($r);
		}

		return $rs;
	}

	function index_exists($tbl, $idxname) {
		# select c1.relname as name, c2.relname as table from pg_catalog.pg_class as c1  JOIN pg_catalog.pg_index i ON i.indexrelid = c1.oid join pg_catalog.pg_class c2 ON i.indrelid = c2.oid where c1.relkind='i';
		return $this->do_query("select c1.relname as name, c2.relname as table from pg_catalog.pg_class as c1 JOIN pg_catalog.pg_index i ON i.indexrelid = c1.oid join pg_catalog.pg_class c2 ON i.indrelid = c2.oid where c1.relkind='i' and table='".$this->escape($tbl)."' and name='".$this->escape($idxname)."';");
	}

	function column_list($tbl) {
		$cols = @pg_meta_data($this->link, $tbl);
		return $cols ? $cols : array();
	}

	function last_insert_id($table) {

		# This assumes that the very first column in the table is a sequence
		$cols = pg_meta_data($this->link, $table);
		$colnames = array_keys($cols);
		$col = $colnames[0];
		if (strpos(@$cols[$col]['type'], 'int') === 0) {
			$sql = "select currval('{$table}_{$col}_seq') as lastinsertid";
			$rs = pg_query($this->link, $sql);
			$last_id = pg_fetch_result($rs, 0);
			return $last_id;
		}
	}

	function lasterror() {
		return pg_last_error($this->link);
	}

	function free($rs) {

		return pg_free_result($rs);
	}

	function num_rows($rs) {

		return pg_num_rows($rs);
	}

	function fetch_assoc($rs) {
		// lame hack: re-map column names in certain tables to preserve case
		$table = '';
		if (preg_match('@from\s+(\S+)@i', $this->last_query[$rs], $m)) {
			$table = $m[1];
		}
		$row = pg_fetch_assoc($rs);
		$this->fixup_row($table, $row);

		return $row;
	}

	function fetch_result($rs, $row) {

		return pg_fetch_result($rs, $row);
	}

	function interval($interval) {
		// INTERVAL is standard SQL, but MySQL's quoting is non-standard
		// e.g. "where date > NOW() - ".db_interval('1 day')
		return "INTERVAL '$interval'";
	}

	function escape($str) {

		return pg_escape_string($str);
	}

	function limit($limit, $offset=0) {
		$limit = (int)$limit;
		$offset = (int)$offset;

		return "limit $limit offset $offset";
	}

	function match($cols, $against) {

		return 'oid as score';
	}

	function rlike() {

		return '~*';
	}

	function affected_rows($rs){

		return pg_affected_rows($rs);
	}

	// Compatibility hacks: Postgres converts column names to lowercase,
	// and doesn't support mysql's INSERT .. SET syntax.  Till we fix
	// those things, we need to convert them on-the-fly.
	function array_key_rename(&$array, $old, $new) {
		if (isset($array[$old])) {
			$array[$new] = $array[$old];
			unset($array[$old]);
		}
	}

	function fixup_row($table, &$row) {
		if ($table == PFX.'textpattern') {
			$this->array_key_rename($row, 'id', 'ID');
			$this->array_key_rename($row, 'posted', 'Posted');
			$this->array_key_rename($row, 'authorid', 'AuthorID');
			$this->array_key_rename($row, 'lastmod', 'LastMod');
			$this->array_key_rename($row, 'lastmodid', 'LastModID');
			$this->array_key_rename($row, 'title', 'Title');
			$this->array_key_rename($row, 'title_html', 'Title_html');
			$this->array_key_rename($row, 'body', 'Body');
			$this->array_key_rename($row, 'body_html', 'Body_html');
			$this->array_key_rename($row, 'excerpt', 'Excerpt');
			$this->array_key_rename($row, 'excerpt_html', 'Excerpt_html');
			$this->array_key_rename($row, 'image', 'Image');
			$this->array_key_rename($row, 'category1', 'Category1');
			$this->array_key_rename($row, 'category2', 'Category2');
			$this->array_key_rename($row, 'annotate', 'Annotate');
			$this->array_key_rename($row, 'annotateinvite', 'AnnotateInvite');
			$this->array_key_rename($row, 'status', 'Status');
			$this->array_key_rename($row, 'section', 'Section');
			$this->array_key_rename($row, 'keywords', 'Keywords');
			$this->array_key_rename($row, 'uposted', 'uPosted');
			$this->array_key_rename($row, 'sposted', 'sPosted');
			$this->array_key_rename($row, 'slastmod', 'sLastMod');
		}
		elseif ($table == PFX.'txp_users') {
			$this->array_key_rename($row, 'realname', 'RealName');
		}
		elseif ($table == PFX.'txp_form') {
			$this->array_key_rename($row, 'form', 'Form');
		}
	}


	// Helper functions to convert mysql INSERT .. SET syntax to valid SQL
	
	function my_csv_explode($str, $delim = ',', $qual = "'", $esc = '\\') {
		$len = strlen($str);
		$inside = false;
		$word = '';
		for ($i = 0; $i < $len; ++$i) {
			if ($str[$i]==$delim && !$inside) {
				$out[] = $word;
				$word = '';
			} else if ($inside && $str[$i] == $esc && $i<($len-1)) {
				$word .= ($esc.$str[$i+1]);
				++$i;
			} else if ($str[$i] == $qual) {
				$inside = !$inside;
				$word .= $str[$i];
			} else {
				$word .= $str[$i];
			}
		}
		$out[] = $word;
		return $out;
	}
	
	function my_insert_to_values($ins) {
		$items = $this->my_csv_explode($ins, ',', "'");
	
		$cols = array();
		$vals = array();
		foreach ($items as $item) {
			@list($k, $v) = split('=', $item, 2);
			$cols[] = trim($k);
			$vals[] = trim($v);
		}
	
		return '('.join(',', $cols).')'.
			' VALUES ('.join(',', $vals).')';
	}
}

?>
