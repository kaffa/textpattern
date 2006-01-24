<?php
/*
$HeadURL: $
$LastChangedRevision: $
*/

// Don't be tempted to edit this file.  Configuration lives elsewhere.

// These are the bare-bones default values for preferences, before the
// actual preferences have been loaded.

include_once('constants.php');

function txp_default_prefs() {
	$guess_siteurl = (isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['SCRIPT_NAME']), '/') : 'example.com');
	$guess_txpath = (defined('txpath') ? txpath : dirname(dirname(__FILE__)));
	$guess_path_to_site = dirname($guess_txpath);
	$guess_file_base_path = $guess_path_to_site.DS.'files';

	return array(
		
		'sitename'                    => 'My Site',
		'site_slogan'                 => 'My pithy slogan',
		'siteurl'                     => $guess_siteurl,
		'language'                    => 'en-us',
		#'url_mode'                    => '1',
		#'timeoffset'                  => '0',
		'comments_on_default'         => '0',
		'comments_default_invite'     => 'Comment',
		'comments_mode'               => '0',
		'comments_disabled_after'     => '0',
		#'use_textile'                 => '1',
		#'ping_weblogsdotcom'          => '0',
		'rss_how_many'                => '5',
		'logging'                     => 'all',
		'use_comments'                => '1',
		#'use_categories'              => '1',
		#'use_sections'                => '1',
		'send_lastmod'                => '0',
		'path_from_root'              => '/',
		#'lastmod'                     => '2006-01-03 22:38:37',
		'comments_dateformat'         => '%b %Oe, %I:%M %p',
		'dateformat'                  => 'since',
		'archive_dateformat'          => '%b %Oe, %I:%M %p',
		'comments_moderate'           => '0',
		'img_dir'                     => 'images',
		'comments_disallow_images'    => '0',
		'comments_sendmail'           => '1',
		'file_max_upload_size'        => '2000000',
		'file_list_pageby'            => '25',
		'path_to_site'                => $guess_path_to_site,
		'article_list_pageby'         => '25',
		'link_list_pageby'            => '25',
		'image_list_pageby'           => '25',
		'log_list_pageby'             => '25',
		'comment_list_pageby'         => '25',
		'permlink_mode'               => 'section_id_title',
		'comments_are_ol'             => '1',
		'is_dst'                      => '0',
		'locale'                      => 'en_US.UTF-8',
		'tempdir'                     => '',
		'file_base_path'              => $guess_file_base_path,
		'blog_uid'                    => '',
		'blog_mail_uid'               => '',
		'blog_time_uid'               => '2005',
		'edit_raw_css_by_default'     => '1',
		'allow_page_php_scripting'    => '1',
		'allow_article_php_scripting' => '1',
		'textile_links'               => '0',
		'show_article_category_count' => '1',
		'show_comment_count_in_feed'  => '1',
		'syndicate_body_or_excerpt'   => '1',
		'include_email_atom'          => '1',
		'comment_means_site_updated'  => '1',
		'never_display_email'         => '0',
		'comments_require_name'       => '1',
		'comments_require_email'      => '1',
		'articles_use_excerpts'       => '1',
		'allow_form_override'         => '1',
		'attach_titles_to_permalinks' => '1',
		'permalink_title_format'      => '1',
		'expire_logs_after'           => '7',
		'use_plugins'                 => '1',
		'custom_1_set'                => 'custom1',
		'custom_2_set'                => 'custom2',
		'custom_3_set'                => '',
		'custom_4_set'                => '',
		'custom_5_set'                => '',
		'custom_6_set'                => '',
		'custom_7_set'                => '',
		'custom_8_set'                => '',
		'custom_9_set'                => '',
		'custom_10_set'               => '',
		'ping_textpattern_com'        => '1',
		'use_dns'                     => '1',
		'admin_side_plugins'          => '1',
		'comment_nofollow'            => '1',
		'use_mail_on_feeds_id'        => '0',
		'max_url_len'                 => '200',
		'spam_blacklists'             => 'sbl.spamhaus.org',
		'override_emailcharset'       => '0',
		'production_status'           => 'testing',
		'comments_auto_append'        => '1',
		'dbupdatetime'                => '1135982013',
		'version'                     => '',
		'gmtoffset'                   => '+0',
		'plugin_cache_dir'            => '',
		'thumb_w'                     => '100',
		'textile_updated'             => '1',
		'thumb_h'                     => '100',
		'thumb_crop'                  => '1',
		
	);
}

?>
