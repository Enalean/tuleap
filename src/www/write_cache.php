<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// $Id: write_cache.php 1490 2005-05-04 17:28:28Z ljulliar $

// Try to avoid 'sploits by requiring the request to come from localhost
if (($GLOBALS['SERVER_NAME'] != 'localhost') &&
     ($GLOBALS['SERVER_ADDR'] != '127.0.0.1')) {
    exit;
}

require_once('pre.php');
require_once('features_boxes.php');
require_once('stats_function.php');
require_once('snippet_caching.php');

/*
	list of valid cacheable functions to
	overcome possible exploit found by rilel
*/

$function= intval($function);

// Set the language context as specified in the URL
if ( $lang_code != $Language->getLanguageCode() ) {
    $Language->loadLanguage($lang_code);
}

unset($valid_array);

$valid_array[0]='show_features_boxes()';
$valid_array[1]='stats_sf_stats()';
$valid_array[2]='stats_project_stats()';
$valid_array[3]='stats_browser_stats()';
$valid_array[4]='snippet_mainpage()';

eval("echo $valid_array[$function];");

?>
