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

<H2>Step 3: CodeX Project Registration</H2>

<p>We now need to know a little bit more about your project. All information typed here will be accessible to CodeX users once your project is registered. So please be as accurate as possible.

<P>If the CodeX team approves your project account, the account
is to be used purely to meet the goals set forth in this statement.
Shall the purpose and goals change in the future, you'll have to modify the statement below accordingly.

<P>(<em>Remark</em>: Your text formatting (e.g. line breaks) will be preserved. URLs typed in the text will be automatically transformed into hyperlinks in the final text.)
<P>
<FONT size=-1>
<FORM action="projectname.php" method="post">
<INPUT TYPE="HIDDEN" NAME="insert_purpose" VALUE="y">

<P>
<B><u>Project Description:</u></B>
<BR>What's the purpose of your project and what are your goals.
<br><TEXTAREA name="form_purpose" wrap="virtual" cols="70" rows="12">
</TEXTAREA>



<P><B><u>Intellectual Property:</u></B>
<br> If your project is covered by Patents or IPs list them here:
<br><TEXTAREA name="form_patents" wrap="virtual" cols="70" rows="6">
</TEXTAREA>

<P><B><u>Required Software:</u></B>
<br>If your project requires the use of 3rd Party (commercial or Open Source) or other internal Xerox software to work properly, list them here:
<br><TEXTAREA name="form_required_sw" wrap="virtual" cols="70" rows="6">
</TEXTAREA>

<P><B><u>Other Comments:</u></B>
<br>Anything you'd like to say about your projects that is not covered above. For instance, if your software is used in Xerox products you might want to list them here. If the soft is being commercialized you can also give information about pricing or the person to contact, etc.
<br><TEXTAREA name="form_comments" wrap="virtual" cols="70" rows="4">
</TEXTAREA>
<BR><INPUT type=submit name="Submit" value="Step 4: Project Name">
</FORM>
</FONT>

<?php
$HTML->footer(array());

?>
