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

<H2>CodeX Project Registration</H2>

<p>
The CodeX team would like to extend an invitation to any 
Xerox Software project to be hosted for no price and no catch on the CodeX Website and to provide the source code to the entire Xerox community. 
</p>

<p><B>The Process</B>

<P>Registering a project with CodeX is an easy process, but we do require
a some information in order to automate things as much as possible. The entire
process should take about 5 to 10 minutes.

<P>During signup, we will present you with some legal documents 
regarding your account with us. Please do not
ignore these; they are very important to you and your project.

<p>&nbsp;
<BR><H3 align=center><a href="requirements.php">Step 1: Services and Requirements</a></H3>
</p>

<?php
$HTML->footer(array());

?>

