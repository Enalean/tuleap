<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// $Id$

// Try to avoid 'sploits by requiring the request to come from localhost
list($host,$port) = explode(':', $HTTP_HOST);
if ($host != 'localhost') {
	exit;
}

require ('pre.php');
require ($DOCUMENT_ROOT.'/include/vote_function.php');
require ($DOCUMENT_ROOT.'/include/features_boxes.php');
require ($DOCUMENT_ROOT.'/include/stats_function.php');
require ($DOCUMENT_ROOT.'/include/snippet_caching.php');

/*
	list of valid cacheable functions to
	overcome possible exploit found by rilel
*/

$function= intval($function);

unset($valid_array);

$valid_array[0]='show_features_boxes()';
$valid_array[1]='stats_sf_stats()';
$valid_array[2]='stats_project_stats()';
$valid_array[3]='stats_browser_stats()';
$valid_array[4]='snippet_mainpage()';

eval("echo $valid_array[$function];");

?>
