<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// $Id$

require($DOCUMENT_ROOT.'/include/pre.php');

$Language->loadLanguageMsg('homepage/homepage');

$HTML->header(array('title'=>$Language->getText('contact', 'title')));

echo '<h2>'.$Language->getText('contact', 'title')."</h2>\n";

echo $Language->getText('contact', 'message', array($GLOBALS['sys_name'],$GLOBALS['sys_email_contact']));

$HTML->footer(array());
?>
