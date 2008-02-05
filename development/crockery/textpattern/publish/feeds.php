<?php

/*
	This is Textpattern
	Copyright 2005 by Dean Allen - all rights reserved.

	Use of this software denotes acceptance of the Textpattern license agreement

$HeadURL$
$LastChangedRevision$

*/


// -------------------------------------------------------------

	function feed($type) {
		global $prefs;
		set_error_handler('feedErrorHandler');
		ob_clean();
		extract($prefs);
		extract(doSlash(gpsa(array('category','section','limit','area'))));

		if ($area != 'link')
			$area = 'article';

		$sitename .= ($section) ? ' - '.fetch_section_title($section) : '';
		$sitename .= ($category) ? ' - '.fetch_category_title($category, $area) : '';

		$self_ref = pagelinkurl(array('atom'=>1, 'area'=>($area == 'article' ? '' : $area), 'section'=>$section, 'category'=>$category, 'limit'=>$limit));
		$id_ext = ($section ? '/'.$section : '') . ($category ? '/'.$category : '');

		if ($area=='article') {

			$sfilter = ($section) ? "and Section = '".$section."'" : '';
			$cfilter = ($category) ? "and (Category1='".$category."' or Category2='".$category."')":'';
			$limit = ($limit) ? $limit : $rss_how_many;
			$limit = intval(min($limit,max(100,$rss_how_many)));

			$frs = safe_column("name", "txp_section", "in_rss != '1'");
			$query = array();
			foreach($frs as $f) $query[] = "and Section != '".doSlash($f)."'";
			$query[] = $sfilter;
			$query[] = $cfilter;

			$expired = ($publish_expired_articles) ? '' : ' and (now() <= Expires or Expires = '.NULLDATETIME.') ';
			$rs = safe_rows_start(
				"*, ID as thisid, unix_timestamp(Posted) as uPosted, unix_timestamp(Expires) as uExpires, unix_timestamp(LastMod) as uLastMod",
				"textpattern",
				"Status=4 and Posted <= now() $expired".join(' ',$query).
				"order by Posted desc limit $limit"
			);

			return render_feed($rs, $area, $type, $sitename, $self_ref, $id_ext);

		} elseif ($area=='link') {

			$cfilter = ($category) ? "category='".$category."'" : '1';
			$limit = ($limit) ? $limit : $rss_how_many;
			$limit = intval(min($limit,max(100,$rss_how_many)));

			$rs = safe_rows_start("*".($atom ? '' : ", unix_timestamp(date) as uDate"),
					"txp_link",
					"$cfilter order by date desc".($atom ? ", id desc" : '')." limit $limit");

			return render_feed($rs, $area, $type, $sitename, $self_ref, $id_ext);
		}
	}


	function render_feed($rs, $area, $type, $feedtitle, $atom_self_ref, $atom_id_ext)
	{
		global $prefs, $thisarticle;
		extract($prefs);

		set_error_handler('tagErrorHandler');

		$atom = ($type == 'atom');

		if ($atom) {
			define("t_texthtml", ' type="text/html"');
			define("t_text", ' type="text"');
			define("t_html", ' type="html"');
			define("t_xhtml", ' type="xhtml"');
			define('t_appxhtml', ' type="xhtml"');
			define("r_relalt", ' rel="alternate"');
			define("r_relself", ' rel="self"');
		}
		else {
			define("t_texthtml", '');
			define("t_text", '');
			define("t_html", '');
			define("t_xhtml", '');
			define('t_appxhtml', '');
			define("r_relalt", '');
			define("r_relself", '');
		}

		$dn = explode('/',$siteurl);
		$mail_or_domain = ($use_mail_on_feeds_id)? eE($blog_mail_uid):$dn[0];

		$last = fetch('unix_timestamp(val)','txp_prefs','name','lastmod');

		if ($atom) {
			$out[] = tag('Textpattern','generator', ' uri="http://textpattern.com/" version="'.$version.'"');
			$out[] = tag(htmlspecialchars($feedtitle),'title',t_text);
			$out[] = tag(htmlspecialchars($site_slogan),'subtitle',t_text);
			$out[] = tag(safe_strftime("w3cdtf",$last),'updated');
			$out[] = '<link'.r_relself.' href="'.$atom_self_ref.'" />';
			$out[] = '<link'.r_relalt.t_texthtml.' href="'.hu.'" />';
			//Atom feeds with mail or domain name
			$out[] = tag('tag:'.$mail_or_domain.','.$blog_time_uid.':'.$blog_uid.$atom_id_ext,'id');

			$pub = safe_row("RealName, email", "txp_users", "privs=1");
			$auth[] = tag($pub['RealName'],'name');
			$auth[] = ($include_email_atom) ? tag(eE($pub['email']),'email') : '';
			$auth[] = tag(hu,'uri');
			$out[] = tag(n.t.t.join(n.t.t,$auth).n,'author');
		} else {
			$out[] = tag('http://textpattern.com/?v='.$version, 'generator');
			$out[] = tag(doSpecial($feedtitle),'title');
			$out[] = tag(doSpecial($site_slogan),'description');
			$out[] = tag(safe_strftime('rfc822',$last),'pubDate');
			$out[] = tag(hu,'link');
			$out[] = '<atom:link href="'.pagelinkurl(array('rss'=>1,'area'=>$area,'section'=>$section,'category'=>$category,'limit'=>$limit)).'" rel="self" type="application/rss+xml" />';
		}

		$out[] = callback_event($atom ? 'atom_head' : 'rss_head');

		$articles = array();

		if (!$area or $area=='article') {

			if ($rs) {
				while ($a = nextRow($rs)) {

					extract($a);
					populateArticleData($a);

					$cb = callback_event($atom ? 'atom_entry' : 'rss_entry');

					$e = array();

					$thisauthor = doSpecial(get_author_name($AuthorID));
					$thisauthor = tag($thisauthor, $atom ? 'name' : 'dc:creator');
					if ($atom) $thisauthor = tag(n.t.t.t.$thisauthor.n.t.t,'author');
					$e['thisauthor'] = $thisauthor;

					if ($atom)
						$e['issued'] = tag(safe_strftime('w3cdtf',$uPosted),'published');
					else
						$e['issued'] = tag(safe_strftime('rfc822',$uPosted),'pubDate');

					if ($atom) {
						$e['modified'] = tag(safe_strftime('w3cdtf',$uLastMod),'updated');
						$e['category1'] = (trim($Category1) ? '<category term="'.doSpecial($Category1).'" />' : '');
						$e['category2'] = (trim($Category2) ? '<category term="'.doSpecial($Category2).'" />' : '');
					}

					$count = '';
					if ($show_comment_count_in_feed && $comments_count > 0)
						$count = ' ['.$comments_count.']';
					$escaped_title = ($atom ? htmlspecialchars($Title) : htmlspecialchars(strip_tags($Title)));
					$e['title'] = tag($escaped_title.$count, 'title', t_html);

					$a['posted'] = $uPosted;
					$permlink = permlinkurl($a);
					if ($atom)
						$e['link'] = '<link'.r_relalt.t_texthtml.' href="'.$permlink.'" />';
					else
						$e['link'] = tag($permlink, 'link');

					$e['id'] = tag('tag:'.$mail_or_domain.','.$feed_time.':'.$blog_uid.'/'.$uid,
							($atom ? 'id' : 'guid'),
							($atom ? '' : ' isPermaLink="false"'));

					$summary = trim(replace_relative_urls(parse($thisarticle['excerpt']), $permlink));
					$content = trim(replace_relative_urls(parse($thisarticle['body']), $permlink));

					if ($syndicate_body_or_excerpt) {
						# short feed: use body as summary if there's no excerpt
						if (!trim($summary))
							$summary = $content;
						$content = '';
					}

					if (trim($summary)) {
						$e['summary'] = tag(n.escape_cdata($summary).n,
								$atom ? 'summary' : 'description',
								t_html);
					}

					if (trim($content)) {
						$e['content'] = tag(n.escape_cdata($content).n,
								$atom ? 'content' : 'content:encoded',
								t_html);
					}

					$articles[$ID] = tag(n.t.t.join(n.t.t,$e).n.$cb, $atom ? 'entry' : 'item');

					$etags[$ID] = strtoupper(dechex(crc32($articles[$ID])));
					$dates[$ID] = ($atom ? $uLastMod : $uPosted);
				}
			}

		} elseif ($area=='link') {

			if ($rs) {
				while ($a = nextRow($rs)) {
					extract($a);

					$e['title'] = tag(doSpecial($linkname),'title',t_html);

					if ($atom) {
						$e['content'] = tag(n.doSpecial($description).n, 'content', t_html);

						$url = (preg_replace("/^\/(.*)/","https?://$siteurl/$1",$url));
						$url = preg_replace("/&((?U).*)=/","&amp;\\1=",$url);
						$e['link'] = '<link'.r_relalt.t_texthtml.' href="'.$url.'" />';

						$e['issued'] = tag(safe_strftime('w3cdtf', strtotime($date)),'published');
						$e['modified'] = tag(gmdate('Y-m-d\TH:i:s\Z',strtotime($date)),'updated');

						$e['id'] = tag('tag:'.$mail_or_domain.','.safe_strftime('%Y-%m-%d', strtotime( $date)).':'.$blog_uid.'/'.$id, 'id');
					} else {
						$e['content'] = tag(doSpecial($description),'description');
						$e['link'] = tag(doSpecial($url),'link');
						$e['issued'] = tag(safe_strftime('rfc822',$uDate),'pubDate');
					}

					$articles[$id] = tag(n.t.t.join(n.t.t,$e).n, $atom ? 'entry' : 'item');

					$etags[$id] = strtoupper(dechex(crc32($articles[$id])));
					$dates[$id] = $date;
				}
			}
		}

		if (!$articles) {
			if ($section) {
				if (safe_field('name', 'txp_section', "name = '$section'") == false) {
					txp_die(gTxt('404_not_found'), '404');
				}
			} elseif ($category) {
				switch ($area) {
					case 'link':
							if (safe_field('id', 'txp_category', "name = '$category' and type = 'link'") == false) {
								txp_die(gTxt('404_not_found'), '404');
							}
					break;

					case 'article':
					default:
							if (safe_field('id', 'txp_category', "name = '$category' and type = 'article'") == false) {
								txp_die(gTxt('404_not_found'), '404');
							}
					break;
				}
			}
		} else {
			//turn on compression if we aren't using it already
			if (extension_loaded('zlib') && ini_get("zlib.output_compression") == 0 && ini_get('output_handler') != 'ob_gzhandler' && !headers_sent()) {
				// make sure notices/warnings/errors don't
				// fudge up the feed when compression is used
				$buf = '';
				while ($b = @ob_get_clean())
					$buf .= $b;
				@ob_start('ob_gzhandler');
				echo $buf;
			}

			handle_lastmod();
			$hims = serverset('HTTP_IF_MODIFIED_SINCE');
			$imsd = ($hims) ? strtotime($hims) : 0;

			if (is_callable('apache_request_headers')) {
				$headers = apache_request_headers();
				if (isset($headers["A-IM"])) {
					$canaim = strpos($headers["A-IM"], "feed");
				} else {
					$canaim = false;
				}
			} else {
				$canaim = false;
			}

			$hinm = stripslashes(serverset('HTTP_IF_NONE_MATCH'));

			$cutarticles = false;

			if ($canaim !== false) {
				foreach($articles as $id=>$thing) {
					if (strpos($hinm, $etags[$id]) !== false) {
						unset($articles[$id]);
						$cutarticles = true;
						$cut_etag = true;
					}

					if ($dates[$id] < $imsd) {
						unset($articles[$id]);
						$cutarticles = true;
						$cut_time = true;
					}
				}
			}

			if (isset($cut_etag) && isset($cut_time)) {
				header("Vary: If-None-Match, If-Modified-Since");
			} else if (isset($cut_etag)) {
				header("Vary: If-None-Match");
			} else if (isset($cut_time)) {
				header("Vary: If-Modified-Since");
			}

			$etag = @join("-",$etags);

			if (strstr($hinm, $etag)) {
				txp_status_header('304 Not Modified');
				exit(0);
			}

			if ($etag) header('ETag: "'.$etag.'"');

			if ($cutarticles) {
				//header("HTTP/1.1 226 IM Used");
				//This should be used as opposed to 200, but Apache doesn't like it.
				//http://intertwingly.net/blog/2004/09/11/Vary-ETag/ says that the status code should be 200.
				header("Cache-Control: no-store, im");
				header("IM: feed");
			}
		}

		$out = array_merge($out, $articles);

		header('Content-type: application/'.($atom ? 'atom' : 'rss').'+xml; charset=utf-8');

		if ($atom) {
			return chr(60).'?xml version="1.0" encoding="UTF-8"?'.chr(62).n.
				'<feed xml:lang="'.$language.'" xmlns="http://www.w3.org/2005/Atom">'.n.join(n,$out).'</feed>';
		} else {
			return '<?xml version="1.0" encoding="utf-8"?>'.n.
				'<rss version="2.0" xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:content="http://purl.org/rss/1.0/modules/content/" xmlns:atom="http://www.w3.org/2005/Atom">'.n.
				tag(join(n,$out),'channel').n.
				'</rss>';
		}
	}


?>
