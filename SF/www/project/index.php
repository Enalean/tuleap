<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// $Id$

require ('pre.php');    

/*
	Project Summary Page
	Written by dtype Oct. 1999
*/

if ((!$group_id) && $form_grp) {
	$group_id=$form_grp;
}

if (!$group_id) {
	exit_error("Missing Group Argument","A group must be specified for this page.");
}

header ("Location: /projects/". group_getunixname($group_id) ."/");

?>
