<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// $Id$

require "pre.php";    
$HTML->header(array(title=>"Your account email address"));
?>

<P><B>If you lose your password...</B>

<P>If you lose your password simply visit the login page and click
"Lost Your Password?". 
A confirmation hash will be emailed to the address we have on file for you.
Load the URL in the email to reset your password.

<P><A href="/docs/site/">[Return to Site Documentation]</A>

<?php
$HTML->footer(array());

?>
