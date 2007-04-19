<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// $Id: contact.php 1491 2005-05-09 07:18:09Z ljulliar $

require_once('pre.php');

$Language->loadLanguageMsg('homepage/homepage');

$HTML->header(array('title'=>$Language->getText('contact', 'title')));

echo '<h2>'.$Language->getText('contact', 'title')."</h2>\n";

include($Language->getContent('contact/contact'));

$HTML->footer(array());
?>
