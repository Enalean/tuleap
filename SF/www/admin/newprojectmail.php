<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// $Id$

require($DOCUMENT_ROOT.'/include/pre.php');
require($DOCUMENT_ROOT.'/include/proj_email.php');

session_require(array('group'=>'1','admin_flags'=>'A'));

$HTML->header(array('title'=>"Project Intro email"));

send_new_project_email($group_id);

print "<P>Mail successfully sent.";

$HTML->footer(array());
?>
