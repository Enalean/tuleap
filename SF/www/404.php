<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// $Id$

require "pre.php";    // Initial db and session library, opens session
$HTML->header(array(title=>"Requested Page not Found (Error 404)"));

if (session_issecure()) {
	echo "<a href=\"https://$GLOBALS[sys_default_domain]\">";
} else {
	echo "<a href=\"http://$GLOBALS[sys_default_domain]\">";
}

echo "<CENTER><H1>PAGE NOT FOUND</H1></CENTER>";

echo "<P>";

$HTML->box1_top('Search');
menu_show_search_box();
$HTML->box1_bottom();

echo "<P>";

$HTML->footer(array());

?>
