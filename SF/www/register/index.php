<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// $Id$

require "pre.php";    // Initial db and session library, opens session
session_require(array(isloggedin=>1));
$HTML->header(array(title=>"Project Registration"));
?>

<H2>SourceForge Project Registration</H2>

<p>
SourceForge would like to extend an invitation to any 
<A href="http://www.opensource.org">Open Source</A> project to be hosted for no price and
no catch. This is our token of appreciation to the people who help make 
<A href="http://www.opensource.org">Open Source</A> a reality.
</p>

<p><B>The Process</B>

<P>Registering a project with SourceForge is an easy process, but we do require
a lot of information in order to automate things as much as possible. The entire
process should take about 10 minutes. 

<P>During signup, we will present you with some legal documents 
regarding your account with us. Please do not
ignore these; they are very important to you and your project.

<p>&nbsp;
<BR><H3 align=center><a href="requirements.php">Step 1: Services and Requirements</a></H3>
</p>

<?php
$HTML->footer(array());

?>

