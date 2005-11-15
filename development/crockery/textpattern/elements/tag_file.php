<?php

/*
$HeadURL: $
$LastChangedRevision: $

*/
//--------------------------------------------------------------------------
//File tags functions. 
//--------------------------------------------------------------------------

	function file_download_list($atts)
	{
		global $thisfile;
		
		extract(lAtts(array(
			'form'     => 'files',
			'sort'     => 'filename',
			'label'    => '',
			'break'    => br,
			'limit'    => '10',
			'wraptag'  => '',
			'category' => '',
			'class'    => __FUNCTION__,
			'labeltag' => '',
		),$atts));	
		
		$qparts = array(
			($category) ? "category='$category'" : '1',
			"order by",
			$sort,
			($limit) ? "limit $limit" : ''
		);
		
		$rs = safe_rows_start("*","txp_file",join(' ',$qparts));
	
		if ($rs) {
		
			while ($a = nextRow($rs)) {				
				$thisfile = fileDownloadFetchInfo("id='$a[id]'");
				$outlist[] = file_download(
					array('id'=>$a['id'],'filename'=>$a['filename'],'form'=>$form)
				);
			}
			
			if (!empty($outlist)) {
				if ($wraptag == 'ul' or $wraptag == 'ol') {
					return doLabel($label, $labeltag).doWrap($outlist, $wraptag, $break, $class);
				}	
				
				return ($wraptag) ? tag(join($break,$outlist),$wraptag) : join(n,$outlist);
			}
		}				
		return '';
	}

//--------------------------------------------------------------------------
	function file_download($atts)
	{
		global $thisfile;
		
		extract(lAtts(array(
			'form'=>'files',
			'id'=>'',
			'filename'=>'',
		),$atts));
		
		$where = (!empty($id) && $id != 0)? "id='$id'" : ((!empty($filename))? "filename='$filename'" : '');
		
		if (!empty($id) || !empty($filename)) {
			$thisfile = fileDownloadFetchInfo($where);
		}				
		
		$thing = fetch_form($form);

		return parse($thing);		
	}
	
//--------------------------------------------------------------------------
	function file_download_link($atts,$thing)
	{
		global $permlink_mode, $thisfile;
		extract(lAtts(array(
			'id'=>'',
			'filename'=>'',
		),$atts));
		
		$where = (!empty($id) && $id != 0)? "id='$id'" : ((!empty($filename))? "filename='$filename'" : '');
		
		if (!empty($id) || !empty($filename)) {
			$thisfile = fileDownloadFetchInfo($where);
		}
		
		$out = ($permlink_mode == 'messy') ?
					'<a href="'.hu.'index.php?s=file_download&amp;id='.$thisfile['id'].'">'.parse($thing).'</a>':
					'<a href="'.hu.gTxt('file_download').'/'.$thisfile['id'].'">'.parse($thing).'</a>';								
		return $out;
	}	
//--------------------------------------------------------------------------
	function fileDownloadFetchInfo($where)
	{
		global $file_base_path;		

		$result = array(
				'id' => 0,
				'filename' => '',
				'category' => '',
				'description' => '',
				'downloads' => 0,
				'size' => 0,
				'created' => 0,
				'modified' => 0
			);

		$rs = safe_row('*','txp_file',$where);

		if ($rs) {
			extract($rs);

			$result['id'] = $id;
			$result['filename'] = $filename;
			$result['category'] = $category;
			$result['description'] = $description;
			$result['downloads'] = $downloads;

			// get filesystem info
			$filepath = build_file_path($file_base_path , $filename);

			if (file_exists($filepath)) {
				$filesize = filesize($filepath);
				if ($filesize !== false)
					$result['size'] = $filesize;

				$created = filectime($filepath);
				if ($created !== false)
					$result['created'] = $created;

				$modified = filemtime($filepath);
				if ($modified !== false)
					$result['modified'] = $modified;
			}
		}

		return $result;
	}	
//--------------------------------------------------------------------------
	function file_download_size($atts)
	{
		global $thisfile;		
		
		extract(lAtts(array(
			'decimals' => 2,
			'format' => ''			
		), $atts));
		
		if (empty($decimals) || $decimals < 0) $decimals = 2;
		if (is_numeric($decimals)) {
			$decimals = intval($decimals);			
		} else {
			$decimals = 2;
		}
		$t = $thisfile['size'];
		if (!empty($thisfile['size']) && !empty($format)) {
			switch(strtoupper(trim($format))) {
				default:
					$divs = 0;
					while ($t > 1024) {
						$t /= 1024;
						$divs++;
					}
					if ($divs==0) $format = ' b';
					elseif ($divs==1) $format = 'kb';
					elseif ($divs==2) $format = 'mb';
					elseif ($divs==3) $format = 'gb';
					elseif ($divs==4) $format = 'pb';
					break;
				case 'B':
					// do nothing
					break;
				case 'KB':
					$t /= 1024;
					break;
				case 'MB':
					$t /= (1024*1024);
					break;
				case 'GB':
					$t /= (1024*1024*1024);
					break;
				case 'PB':
					$t /= (1024*1024*1024);
				break;
			}
			return number_format($t,$decimals) . $format;
		}
		
		return (!empty($thisfile['size']))? $thisfile['size'] : '';
	}

//--------------------------------------------------------------------------
	function file_download_created($atts)
	{
		global $thisfile;		
		extract(lAtts(array('format'=>''),$atts));		
		return fileDownloadFormatTime(array('ftime'=>$thisfile['created'], 'format' => $format));
	}
//--------------------------------------------------------------------------
	function file_download_modified($atts)
	{
		global $thisfile;		
		extract(lAtts(array('format'=>''),$atts));		
		return fileDownloadFormatTime(array('ftime'=>$thisfile['modified'], 'format' => $format));
	}
//-------------------------------------------------------------------------
	//All the time related file_download tags in one
	//One Rule to rule them all ... now using safe formats
	function fileDownloadFormatTime($params)
	{
		global $prefs;
		extract($params);
		if (!empty($ftime)) {
			return  (!empty($format))? safe_strftime($format,$ftime) : safe_strftime($prefs['archive_dateformat'],$ftime);
		}
		return '';
	}

	function file_download_id($atts)
	{
		global $thisfile;		
		return $thisfile['id'];
	}
	function file_download_name($atts)
	{
		global $thisfile;		
		return $thisfile['filename'];
	} 
	function file_download_category($atts)
	{
		global $thisfile;		
		return $thisfile['category'];
	}
	function file_download_downloads($atts)
	{
		global $thisfile;		
		return $thisfile['downloads'];
	}
	function file_download_description($atts)
	{
		global $thisfile;		
		return $thisfile['description'];
	}	


?>
