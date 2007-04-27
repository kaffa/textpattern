<?php

/*
$HeadURL: $
$LastChangedRevision: $
*/

register_callback('file_download_send', 'pub_file');

function file_download_send($event, $step) {
	// just a quick transplant from publish.php, this could stand some refactoring
	global $pretext, $prefs;
	extract($prefs);
	extract($pretext);

	// we are dealing with a download
#	if (@$s == 'file_download') {

	$file_error = 0;
	$file = safe_row('*', 'txp_file', "id='".doSlash($pretext['tail'][1])."' and status >= 4");
	if (!$file)
		$file_error = 404;

		if (!$file_error) {
			extract($file);

				$fullpath = build_file_path($file_base_path,$filename);

				if (is_file($fullpath)) {

					// discard any error php messages
					ob_clean();
					$filesize = filesize($fullpath); $sent = 0;
					header('Content-Description: File Download');
					header('Content-Type: application/octet-stream');
					header('Content-Length: ' . $filesize);
					header('Content-Disposition: attachment; filename="' . basename($filename) . '"');
					@ini_set("zlib.output_compression", "Off");
					@set_time_limit(0);
					@ignore_user_abort(true);
					if ($file = fopen($fullpath, 'rb')) {
						while(!feof($file) and (connection_status()==0)) {
							echo fread($file, 1024*64); $sent+=(1024*64);
							ob_flush();
							flush();
						}
						fclose($file);
						// record download
						if ((connection_status()==0) and !connection_aborted() ) {
							safe_update("txp_file", "downloads=downloads+1", "id='".intval($id)."'");
						} else {
							$pretext['request_uri'] .= "#aborted-at-".floor($sent*100/$filesize)."%";
							logit();
						}
					}
				} else {
					$file_error = 404;
				}
#		}

		// deal with error
		if ($file_error) {
			switch($file_error) {
			case 403:
				header('HTTP/1.0 403 Forbidden');
				break;
			case 404:
				header('HTTP/1.0 404 File Not Found');
				break;
			default:
				header('HTTP/1.0 500 Internal Server Error');
				break;
			}
		}
		
		// download done
		exit(0);
	}
}

?>
