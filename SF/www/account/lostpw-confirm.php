<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// $Id$

require($DOCUMENT_ROOT.'/include/pre.php');    

$confirm_hash = md5($session_hash . strval(time()) . strval(rand()));

$res_user = db_query("SELECT * FROM user WHERE user_name='$form_loginname'");
if (db_numrows($res_user) < 1) exit_error("Invalid User","That user does not exist.");
$row_user = db_fetch_array($res_user);

db_query("UPDATE user SET confirm_hash='$confirm_hash' WHERE user_id=$row_user[user_id]");

if (session_issecure()) {
    $server = 'https://'.$GLOBALS['sys_https_host'];
} else {
    $server = 'http://'.$GLOBALS['sys_default_domain'];
}

list($host,$port) = explode(':',$GLOBALS['sys_default_domain']);		

$message = "Someone (presumably you) on the ".$GLOBALS['sys_name']." site requested a\n"
	. "password change through email verification. If this was not you,\n"
	. "ignore this message and nothing will happen.\n\n"
	. "If you requested this verification, visit the following URL\n"
	. "to change your password:\n\n"
	. "$server/account/lostlogin.php?confirm_hash=$confirm_hash\n\n"
	. " -- The ".$GLOBALS['sys_name']." Team\n";

$hdrs = "From: noreply@".$host.$GLOBALS['sys_lf'];
$hdrs .='Content-type: text/plain; charset=iso-8859-1'.$GLOBALS['sys_lf'];

mail ($row_user['email'],$GLOBALS['sys_name']." Password Verification",$message,$hdrs);

$HTML->header(array('title'=>"Lost Password Confirmation"));

?>

<P><B>Confirmation mailed</B>

<P>An email has been sent to the address you have on file. Follow
the instructions in the email to change your account password.

<P><A href="/">[ Return to <?php print $GLOBALS['sys_name']; ?> ]</A>

<?php
$HTML->footer(array());

?>
