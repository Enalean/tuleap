<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// $Id$

require_once('pre.php');

$Language->loadLanguageMsg('docman/docman');

$HTML->header(array(title=>$Language->getText('docs_site_about','title', array($GLOBALS['sys_name']))));

include($Language->getContent('docman/about_codex'));

$HTML->footer(array());

?>

