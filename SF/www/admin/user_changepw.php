<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// $Id$

require "pre.php";    
require "account.php";
session_require(array('group'=>'1','admin_flags'=>'A'));

// ###### function register_valid()
// ###### checks for valid register from form post

function register_valid()	{
	global $form_user;

	if (!$GLOBALS["Update"]) {
		return 0;
	}
	
	// check against old pw
	db_query("SELECT user_pw FROM user WHERE user_id=$form_user");

	if (!$GLOBALS['form_pw']) {
		$GLOBALS['register_error'] = "You must supply a password.";
		return 0;
	}
	if ($GLOBALS['form_pw'] != $GLOBALS['form_pw2']) {
		$GLOBALS['register_error'] = "Passwords do not match.";
		return 0;
	}
	if (!account_pwvalid($GLOBALS['form_pw'])) {
		return 0;
	}
	
	// if we got this far, it must be good
	db_query("UPDATE user SET user_pw='" . md5($GLOBALS['form_pw']) . "',"
		. "unix_pw='" . account_genunixpw($GLOBALS['form_pw']) . "' WHERE "
		. "user_id=" . $form_user);
	return 1;
}

// ###### first check for valid login, if so, congratulate

if (register_valid()) {
	$HTML->header(array(title=>"Alexandria: Change Password"));
?>
<p><b>SourceForge Change Confirmation</b>
<p>Congratulations, genius. You have managed to change this user's password.
<p>You should now <a href="/admin/userlist.php">Return to UserList</a>.
<?php
} else { // not valid registration, or first time to page
	$HTML->header(array(title=>"Change Password"));

?>
<p><b>SourceForge Password Change</b>
<?php if ($register_error) print "<p>$register_error"; ?>
<form action="user_changepw.php" method="post">
<p>New Password:
<br><input type="password" name="form_pw">
<p>New Password (repeat):
<br><input type="password" name="form_pw2">
<INPUT type=hidden name="form_user" value="<?php print $form_user; ?>">
<p><input type="submit" name="Update" value="Update">
</form>

<?php
}
$HTML->footer(array());

?>
