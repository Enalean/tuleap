<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// $Id$

require($DOCUMENT_ROOT.'/include/pre.php');

$LANG->loadLanguageMsg('docman/docman');

$HTML->header(array(title=>$LANG->getText('docs_site_about','title', array($GLOBALS['sys_name']))));

include(util_get_content('docman/about_codex'));

$HTML->footer(array());

?>

