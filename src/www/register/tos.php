<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// $Id$

require_once('pre.php');    // Initial db and session library, opens session

$Language->loadLanguageMsg('register/register');

session_require( array( 'isloggedin'=>1 ) );

$HTML->header(array('title'=>$Language->getText('register_tos','tos')));

echo '<p><h2>'.$Language->getText('register_tos','tos_agreement').'</h2></p>';
include($Language->getContent('register/tos'));

echo '<BR><HR><BR>

<P align=center>'.$Language->getText('register_tos','tos_agree','template.php');

$HTML->footer(array());

?>

