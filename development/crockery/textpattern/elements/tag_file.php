<?php

/*
$HeadURL: $
$LastChangedRevision: $

*/


//--------------------------------------------------------------------------

	function file_download_list($atts)
	{
		global $thisfile;

		extract(lAtts(array(
			'break'		 => br,
			'category' => '',
			'class'		 => __FUNCTION__,
			'form'		 => 'files',
			'label'		 => '',
			'labeltag' => '',
			'limit'		 => '10',
			'offset'	 => '0',
			'sort'		 => 'filename asc',
			'wraptag'	 => '',
			'status'     => '4',
		), $atts));
		
		if (!is_numeric($status))
			$status = getStatusNum($status);

		$where = array('1=1');
		if ($category) $where[] = "category = '".doSlash($category)."'";
		if ($status) $where[] = "status = '".doSlash($status)."'";

		$qparts = array(
			'order by '.doSlash($sort),
			($limit) ? 'limit '.intval($offset).', '.intval($limit) : '',
		);

		$rs = safe_rows_start('*', 'txp_file', join(' and ', $where).' '.join(' ', $qparts));

		if ($rs)
		{
			$out = array();

			while ($a = nextRow($rs))
			{
				$GLOBALS['thisfile'] = file_download_format_info($a);

				$out[] = parse_form($form);

				$GLOBALS['thisfile'] = '';
			}

			if ($out)
			{
				if ($wraptag == 'ul' or $wraptag == 'ol')
				{
					return doLabel($label, $labeltag).doWrap($out, $wraptag, $break, $class);
				}

				return ($wraptag) ? tag(join($break, $out), $wraptag) : join(n, $out);
			}
		}
		return '';
	}

//--------------------------------------------------------------------------

	function file_download($atts)
	{
		global $thisfile;

		extract(lAtts(array(
			'filename' => '',
			'form'		 => 'files',
			'id'			 => '',
		), $atts));

		$from_form = false;

		if ($id)
		{
			$thisfile = fileDownloadFetchInfo('id = '.intval($id));
		}

		elseif ($filename)
		{
			$thisfile = fileDownloadFetchInfo("filename = '".doSlash($filename)."'");
		}

		elseif ($thisfile)
		{
			$from_form = true;
		}

		if ($thisfile)
		{
			$out = parse_form($form);

			// cleanup: this wasn't called from a form,
			// so we don't want this value remaining
			if (!$from_form)
			{
				$GLOBALS['thisfile'] = '';
			}

			return $out;
		}
	}

//--------------------------------------------------------------------------

	function file_download_link($atts, $thing)
	{
		global $thisfile, $permlink_mode;

		extract(lAtts(array(
			'filename' => '',
			'id'			 => '',
		), $atts));

		$from_form = false;

		if ($id)
		{
			$thisfile = fileDownloadFetchInfo('id = '.intval($id));
		}

		elseif ($filename)
		{
			$thisfile = fileDownloadFetchInfo("filename = '".doSlash($filename)."'");
		}

		elseif ($thisfile)
		{
			$from_form = true;
		}

		if ($thisfile)
		{
			$url = filedownloadurl($thisfile['id'], $thisfile['filename']);

			$out = ($thing) ? href(parse($thing), $url) : $url;

			// cleanup: this wasn't called from a form,
			// so we don't want this value remaining
			if (!$from_form)
			{
				$GLOBALS['thisfile'] = '';
			}

			return $out;
		}
	}

//--------------------------------------------------------------------------

	function fileDownloadFetchInfo($where)
	{
		$rs = safe_row('*', 'txp_file', $where);

		if ($rs)
		{
			return file_download_format_info($rs);
		}

		return false;
	}

//--------------------------------------------------------------------------

	function file_download_format_info($file)
	{
		if (($unix_ts = @strtotime($file['created'])) > 0)
			$file['created'] = $unix_ts;
		if (($unix_ts = @strtotime($file['modified'])) > 0)
			$file['modified'] = $unix_ts;

		return $file;
	}

//--------------------------------------------------------------------------

	function file_download_size($atts)
	{
		global $thisfile;

		extract(lAtts(array(
			'decimals' => 2,
			'format'	 => '',
		), $atts));

		if (is_numeric($decimals) and $decimals >= 0)
		{
			$decimals = intval($decimals);
		}

		else
		{
			$decimals = 2;
		}

		if ($thisfile['size'])
		{
			$size = $thisfile['size'];

			if (!in_array($format, array('B','KB','MB','GB','PB')))
			{
				$divs = 0;

				while ($size >= 1024)
				{
					$size /= 1024;
					$divs++;
				}

				switch ($divs)
				{
					case 1:
						$format = 'KB';
					break;

					case 2:
						$format = 'MB';
					break;

					case 3:
						$format = 'GB';
					break;

					case 4:
						$format = 'PB';
					break;

					case 0:
					default:
						$format = 'B';
					break;
				}
			}

			$size = $thisfile['size'];

			switch ($format)
			{
				case 'KB':
					$size /= 1024;
				break;

				case 'MB':
					$size /= (1024*1024);
				break;

				case 'GB':
					$size /= (1024*1024*1024);
				break;

				case 'PB':
					$size /= (1024*1024*1024*1024);
				break;

				case 'B':
				default:
					// do nothing
				break;
			}

			return number_format($size, $decimals).$format;
		}

		else
		{
			return '';
		}
	}

//--------------------------------------------------------------------------

	function file_download_created($atts)
	{
		global $thisfile;

		extract(lAtts(array(
			'format' => '',
		), $atts));

		if ($thisfile['created']) {
			return fileDownloadFormatTime(array(
				'ftime'	 => $thisfile['created'],
				'format' => $format
			));
		}
	}

//--------------------------------------------------------------------------

	function file_download_modified($atts)
	{
		global $thisfile;

		extract(lAtts(array(
			'format' => '',
		), $atts));

		if ($thisfile['modified']) {
			return fileDownloadFormatTime(array(
				'ftime'	 => $thisfile['modified'],
				'format' => $format
			));
		}
	}

//-------------------------------------------------------------------------
// All the time related file_download tags in one
// One Rule to rule them all... now using safe formats

	function fileDownloadFormatTime($params)
	{
		global $prefs;

		extract($params);

		if (!empty($ftime))
		{
			return !empty($format) ?
				safe_strftime($format, $ftime) : safe_strftime($prefs['archive_dateformat'], $ftime);
		}
		return '';
	}

//--------------------------------------------------------------------------

	function file_download_id($atts)
	{
		global $thisfile;
		return $thisfile['id'];
	}

//--------------------------------------------------------------------------

	function file_download_name($atts)
	{
		global $thisfile;
		return $thisfile['filename'];
	}

//--------------------------------------------------------------------------

	function file_download_category($atts)
	{
		global $thisfile;

		extract(lAtts(array(
			'class'   => '',
			'escape'  => '',
			'wraptag' => '',
		), $atts));

		if ($thisfile['category'])
		{
			$category = ($escape == 'html') ? 
				escape_output($thisfile['category']) : $thisfile['category'];

			return ($wraptag) ? doTag($category, $wraptag, $class) : $category;
		}
	}

//--------------------------------------------------------------------------

	function file_download_downloads($atts)
	{
		global $thisfile;
		return $thisfile['downloads'];
	}

//--------------------------------------------------------------------------

	function file_download_description($atts)
	{
		global $thisfile;

		extract(lAtts(array(
			'class'   => '',
			'escape'  => '',
			'wraptag' => '',
		), $atts));

		if ($thisfile['description'])
		{
			$description = ($escape == 'html') ?
				escape_output($thisfile['description']) : $thisfile['description'];

			return ($wraptag) ? doTag($description, $wraptag, $class) : $description;
		}
	}
	
?>