<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// $Id$

require($DOCUMENT_ROOT.'/include/pre.php');    
require($DOCUMENT_ROOT.'/include/account.php');

// ###### function register_valid()
// ###### checks for valid register from form post

$res_user = db_query("SELECT * FROM user WHERE confirm_hash='$confirm_hash'");
if (db_numrows($res_user) > 1) {
	exit_error("Error","This confirm hash exists more than once.");
}
if (db_numrows($res_user) < 1) {
	exit_error("Error","Invalid confirmation hash.");
}
$row_user = db_fetch_array($res_user);

db_query("UPDATE user SET "
	. "email='" . $row_user['email_new'] . "',"
	. "confirm_hash='none',"
	. "email_new='" . $row_user['email'] . "' WHERE "
	. "confirm_hash='$confirm_hash'");

$HTML->header(array('title'=>"Email Change Complete"));
?>
<p><b>Email Change Complete</b>
<P>Welcome, <?php print $row_user['realname'] ?>.<BR>
 Your email change is complete. Your new email address on file is 
<B><?php print $row_user[email_new]; ?></B>. Mail sent to
<?php print $GLOBALS['sys_name']; ?> user <?php print $row_user['realname'] ?> (<?php print $row_user['user_name']; ?>) <!-- LJ @ --><?php //LJ print $GLOBALS['sys_users_host']; ?>
will now be forwarded to this new email.

<P><A href="/">[Return to <?php print $GLOBALS['sys_name']; ?>]</A>

<?php
$HTML->footer(array());

?>
