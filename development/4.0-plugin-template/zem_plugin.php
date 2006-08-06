<?php

// This is a PLUGIN TEMPLATE.

// Copy this file to a new name like abc_myplugin.php.  Edit the code, then
// run this file at the command line to produce a plugin for distribution:
// $ php abc_myplugin.php > abc_myplugin-0.1.txt

// Plugin name is optional.  If unset, it will be extracted from the current
// file name. Uncomment and edit this line to override:
# $plugin['name'] = 'abc_plugin';
# $plugin['compress'] = 1;

$plugin['version'] = '0.1';
$plugin['author'] = 'Alex Shiels';
$plugin['author_uri'] = 'http://thresholdstate.com/';
$plugin['description'] = 'Short description';

// Plugin types:
// 0 = regular plugin; loaded on the public web side only
// 1 = admin plugin; loaded on both the public and admin side
// 2 = library; loaded only when include_plugin() or require_plugin() is called
$plugin['type'] = 0; 

// Help type:
// 0 = textile applied, otherwise untouched (old way)
// 1 = textile applied, but all other tags are stripped (recommended)
$plugin['help_type'] = 1; 

@include_once('zem_tpl.php');

if (0) {
?>
# --- BEGIN PLUGIN HELP ---
h1. Textile-formatted help goes here

# --- END PLUGIN HELP ---
<?php
}

# --- BEGIN PLUGIN CODE ---

// Plugin code goes here.  No need to escape quotes.

# --- END PLUGIN CODE ---

?>
