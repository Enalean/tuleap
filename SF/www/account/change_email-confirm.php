<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// $Id$

require "pre.php";    

$confirm_hash = substr(md5($session_hash . time()),0,16);

$res_user = db_query("SELECT * FROM user WHERE user_id=".user_getid());
if (db_numrows($res_user) < 1) exit_error("Invalid User","That user does not exist.");
$row_user = db_fetch_array($res_user);

db_query("UPDATE user SET confirm_hash='$confirm_hash',email_new='$form_newemail' "
	. "WHERE user_id=$row_user[user_id]");

$message = "You have requested a change of email address on CodeX.\n"
	. "Please visit the following URL to complete the email change:\n\n"
	. "http://$GLOBALS[HTTP_HOST]/account/change_email-complete.php?confirm_hash=$confirm_hash\n\n"
	. " -- the CodeX staff\n";

mail ($form_newemail,"CodeX Verification",$message,"From: noreply@$GLOBALS[HTTP_HOST]");

$HTML->header(array('title'=>"Email Change Confirmation"));
?>

<P><B>Confirmation mailed</B>

<P>An email has been sent to the new address. Follow
the instructions in the email to complete the email change.

<P><A href="/">[ Home ]</A>

<?php
$HTML->footer(array());

?>
