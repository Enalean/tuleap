<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// $Id$

require($DOCUMENT_ROOT.'/include/pre.php');

$LANG->loadLanguageMsg('homepage/homepage');

$HTML->header(array(title=>$LANG->getText('404', 'title')));

if (session_issecure()) {
	echo "<a href=\"https://$GLOBALS[sys_default_domain]\">";
} else {
	echo "<a href=\"http://$GLOBALS[sys_default_domain]\">";
}

if (strpos($REQUEST_URI, "pipermail")) {
  echo "<CENTER><H1>".$LANG->getText('404', 'no_archive')."</H1></CENTER><P>";
}
else {
  echo "<CENTER><H1>".$LANG->getText('404', 'no_page')."</H1></CENTER>";

  echo "<P>";
}

$HTML->box1_top('Search');
menu_show_search_box();
$HTML->box1_bottom();

echo "<P>";

$HTML->footer(array());

?>
