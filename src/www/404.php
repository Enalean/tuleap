<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// $Id: 404.php 1911 2005-08-22 13:36:18Z nterray $

require_once('pre.php');

$Language->loadLanguageMsg('homepage/homepage');

$HTML->header(array(title=>$Language->getText('404', 'title')));

echo '<a href="'.get_server_url().'">';

if (strpos($REQUEST_URI, "pipermail")) {
  echo "<CENTER><H1>".$Language->getText('404', 'no_archive')."</H1></CENTER><P>";
}
else {
  echo "<CENTER><H1>".$Language->getText('404', 'no_page')."</H1></CENTER>";

  echo "<P>";
}

$HTML->box1_top('Search');
$HTML->searchBox();
$HTML->box1_bottom();

echo "<P>";

$HTML->footer(array());

?>
