<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// $Id$

require("pre.php");    // Initial db and session library, opens session
session_require( array( isloggedin=>1 ) );

$HTML->header(array(title=>"Terms of Service"));

util_get_content('register/tos');

$HTML->footer(array());

?>

