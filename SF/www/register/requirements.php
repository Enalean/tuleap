<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// $Id$

require_once('pre.php');

$Language->loadLanguageMsg('register/register');

session_require(array(isloggedin=>1));
$HTML->header(array(title=>$Language->getText('register_index','project_registration')));

include($Language->getContent('register/registration'));

$HTML->footer(array());
?>

