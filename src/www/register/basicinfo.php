<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// $Id$

require_once('pre.php');    // Initial db and session library, opens session
session_require(array('isloggedin'=>'1'));

if (!$built_from_template) {
  exit_error($Language->getText('global','error'),$Language->getText('register_projectname','info_missed'));
}

$HTML->header(array('title'=>'Basic Project Information'));

include($Language->getContent('register/basinfo'));

$HTML->footer(array());

?>
