<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// $Id$

require "pre.php";    
require "account.php";

// ###### function register_valid()
// ###### checks for valid register from form post

function register_valid()	{

	if (!$GLOBALS["Update"]) {
		return 0;
	}
	
	// check against old pw
	db_query("SELECT user_pw, status FROM user WHERE user_id=" . user_getid());
	$row_pw = db_fetch_array();
	if ($row_pw[user_pw] != md5($GLOBALS[form_oldpw])) {
		$GLOBALS[register_error] = "Old password is incorrect.";
		return 0;
	}

	if ($row_pw[status] != 'A') {
		$GLOBALS[register_error] = "Account must be active to change password.";
		return 0;
	}

	if (!$GLOBALS[form_pw]) {
		$GLOBALS[register_error] = "You must supply a password.";
		return 0;
	}
	if ($GLOBALS[form_pw] != $GLOBALS[form_pw2]) {
		$GLOBALS[register_error] = "Passwords do not match.";
		return 0;
	}
	if (!account_pwvalid($GLOBALS[form_pw])) {
		return 0;
	}
	
	// if we got this far, it must be good
	db_query("UPDATE user SET user_pw='" . md5($GLOBALS[form_pw]) . "',"
		. "unix_pw='" . account_genunixpw($GLOBALS[form_pw]) . "',"
		. "windows_pw='" . account_genwinpw($GLOBALS[form_pw]) . "' WHERE "
		. "user_id=" . user_getid());
	return 1;
}

// ###### first check for valid login, if so, congratulate

if (register_valid()) {
	$HTML->header(array(title=>"Successfully Changed Password"));
?>
<p><b>SourceForge Change Confirmation</b>
<p>Congratulations. You have changed your password.
This change is immediate on the web site, but will not take
effect on your shell/cvs account until the next cron update,
which will happen in
<?php
     $d = getdate(time());
     $h = ($sys_crondelay - 1) - ($d[hours] % $sys_crondelay);
     $m= 60 - $d[minutes];
     print "<span class=\"highlight\"><b> $h&nbsp;h&nbsp;$m&nbsp;minutes</b></span>";
?>
 from now.

<p>You should now <a href="/account/">Return to UserPrefs</a>.
<?php
} else { // not valid registration, or first time to page
	$HTML->header(array(title=>"Change Password"));

?>
<p><b>SourceForge Password Change</b>
<?php if ($register_error) print "<p><span class=\"highlight\"><b>$register_error</b></span>"; ?>
<form action="change_pw.php" method="post">
<p>Old Password:
<br><input type="password" name="form_oldpw">
<p>New Password:
<br><input type="password" name="form_pw">
<p>New Password (repeat):
<br><input type="password" name="form_pw2">
<p><input type="submit" name="Update" value="Update">
</form>

<?php
}
$HTML->footer(array());

?>
