<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// $Id$

require($DOCUMENT_ROOT.'/include/pre.php');
    
$LANG->loadLanguageMsg('project/project');

/*
	Project Summary Page
	Written by dtype Oct. 1999
*/

if ((!$group_id) && $form_grp) {
	$group_id=$form_grp;
}

if (!$group_id) {
	exit_error($LANG->getText('project_index','g_missed'),$LANG->getText('project_index','must_spec_g'));
}

header ("Location: /projects/". group_getunixname($group_id) ."/");

?>
