<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// $Id$

require($DOCUMENT_ROOT.'/include/pre.php');

$LANG->loadLanguageMsg('homepage/homepage');

$HTML->header(array('title'=>$LANG->getText('contact', 'title')));

echo '<h2>'.$LANG->getText('contact', 'title')."</h2>\n";

echo $LANG->getText('contact', 'message', array($GLOBALS['sys_name'],$GLOBALS['sys_email_contact']));

$HTML->footer(array());
?>
