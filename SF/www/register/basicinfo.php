<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// $Id$

require "pre.php";    // Initial db and session library, opens session
session_require(array('isloggedin'=>'1'));
$HTML->header(array('title'=>'Basic Project Information'));
?>

<H2>Step 3: SourceForge Project Registration</H2>

<p>We now need a short description of your project. This description
needs to contain the purpose of the project and a summarization of your
goals.

<P>If the SourceForge staff approves your project account, the account
is to be used purely to meet the goals set forth in this statement.
Use of the project account for anything other than 
the purposes and goals in this statement is prohibited.
If you need to change this statement at any time, please inform a
staff member and we will assist you in getting a new statement approved.

<P>
<B>Project Purpose and Summarization</B>
<P>
<FONT COLOR="RED"><B>REQUIRED:</B> Provide detailed, accurate description</FONT>
<P>
<FONT size=-1>
<FORM action="projectname.php" method="post">
<INPUT TYPE="HIDDEN" NAME="insert_purpose" VALUE="y">
<TEXTAREA name=form_purpose wrap=virtual cols=70 rows=20></TEXTAREA>
<BR><INPUT type=submit name="Submit" value="Step 4: Project Name">
</FORM>
</FONT>

<?php
$HTML->footer(array());

?>
