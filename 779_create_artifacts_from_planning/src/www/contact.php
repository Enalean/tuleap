<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// 

require_once('pre.php');


$HTML->header(array('title'=>$Language->getText('contact', 'title')));

echo '<h2>'.$Language->getText('contact', 'title')."</h2>\n";

include($Language->getContent('contact/contact'));

$HTML->footer(array());
?>
