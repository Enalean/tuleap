<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// $Id$

require "pre.php";    
session_require(array(isloggedin=>1));
$HTML->header(array(title=>"Change Email Address"));
?>

<P><B>Change Email Address</B>

<P>Changing your email address will require confirmation from your 
new email address, so that we can ensure we have a good email address
on file.

<P>We need to maintain an accurate email address for each user due
to the level of access we grant via this account. If we need to reach a user
for issues arriving from a shell or project account, it is important that
we be able to do so.

<P>Submitting the form below will mail a confirmation URL to the new
email address. Visiting this link will complete the email change.

<FORM action="change_email-confirm.php" method="post">
<P><INPUT type="hidden" name="form_user" value="<?php print user_getid(); ?>">
New Email Address:
<INPUT type="text" name="form_newemail">
<INPUT type="submit" name="Send Confirmation to New Address" value="Send Confirmation to New Address">
</FORM>

<P><A href="/">[Return to SourceForge]</A>

<?php
$HTML->footer(array());

?>
