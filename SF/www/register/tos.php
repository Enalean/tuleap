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

echo '<p><h2>Step 2: Terms of Service Agreement</h2></p>';
util_get_content('register/tos');

echo '<BR><HR><BR>

<P align=center>By clicking below, you acknowledge that you have read 
and understand the Terms of Service agreement. Clicking "I AGREE" will
constitute your legal signature on this document.
<P><H3 align=center><A href="basicinfo.php">[I AGREE]</A>
&nbsp;&nbsp;<A href="/">[I DISAGREE]</A></H3>';

$HTML->footer(array());

?>

