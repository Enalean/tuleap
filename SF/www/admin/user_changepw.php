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
  if (!$GLOBALS['Update']) {
    return 0;
  }
  if (!$GLOBALS['user_id']) {
    $GLOBALS['register_error'] = "Internal error: no user ID";
    return 0;
  }
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
  $res = db_query("UPDATE user SET user_pw='" . md5($GLOBALS['form_pw']) . "',"
		  . "unix_pw='" . account_genunixpw($GLOBALS['form_pw']) . "',"
		  . "windows_pw='" . account_genwinpw($GLOBALS['form_pw']) . "' WHERE "
		  . "user_id=" . $GLOBALS['user_id']);

  if (! $res) {
    $GLOBALS['register_error'] = "Internal error: Could not update password.";
    return 0;
  }
    
  return 1;
}

// ###### first check for valid login, if so, congratulate

if (register_valid()) {
	$HTML->header(array(title=>"Successfully Changed Password"));
?>
<p><b>CodeX Change Confirmation</b>
<p>The user's password has been changed.
This change is immediate on the web site, but will not take
effect on the user's shell/cvs account until the next cron update,
which will happen in
<?php
     $d = getdate(time());
     $h = ($sys_crondelay - 1) - ($d[hours] % $sys_crondelay);
     $m= 60 - $d[minutes];
     print "<span class=\"highlight\"><b> $h&nbsp;h&nbsp;$m&nbsp;minutes</b></span>";
?>
 from now.

<p>You should now <a href="/admin/userlist.php">Return to UserList</a>.
<?php
} else { // not valid registration, or first time to page
	$HTML->header(array(title=>"Change Password"));

?>
<p><b>CodeX Password Change</b>
<?php if ($register_error) print "<p>$register_error"; ?>
<form action="user_changepw.php" method="post">
<p>New Password:
<br><input type="password" name="form_pw">
<p>New Password (repeat):
<br><input type="password" name="form_pw2">
<INPUT type=hidden name="user_id" value="<?php print $user_id; ?>">
<p><input type="submit" name="Update" value="Update">
</form>

<?php
}
$HTML->footer(array());

?>
