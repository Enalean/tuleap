<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// $Id$

require "pre.php";
require "account.php";  // LJ needed to create unix account

// ###### function login_valid()
// ###### checks for valid login from form post

function verify_login_valid()	{
	global $HTTP_POST_VARS;

	if (!$GLOBALS['form_loginname']) return 0;

	// first check just confirmation hash
	$res = db_query('SELECT confirm_hash,status FROM user WHERE '
		.'user_name=\''.$GLOBALS['form_loginname'].'\'');

	if (db_numrows($res) < 1) {
		$GLOBALS['error_msg'] = 'Invalid username.';
		return 0;
	}
	$usr = db_fetch_array($res);

	if (strcmp($GLOBALS['confirm_hash'],$usr['confirm_hash'])) {
		$GLOBALS['error_msg'] = 'Invalid confirmation hash.';
		return 0;
	}

	// then check valid login	
	return (session_login_valid($GLOBALS['form_loginname'],$GLOBALS['form_pw'],1));
}

// ###### first check for valid login, if so, redirect

if ($Login){
	$success=verify_login_valid();
	if ($success) {
	  // LJ in CodeX we now activate the Unix account upfront to limit
	  // LJ source code access control(CVS, File Release) to registered
	  // LJ users only
	  // LJ	$res = db_query("UPDATE user SET status='A' WHERE user_name='$GLOBALS[form_loginname]'");

	// LJ Since the URL in the e-mail notification can be used
	// LJ several times we must make sure that we do not generate
	// LJ a unix user_id a second time
	  $res_user = db_query("SELECT unix_uid FROM user WHERE user_name='$GLOBALS[form_loginname]'");
	  if (db_result($res_user,0,'unix_uid') == 0) {	
	    $res = db_query("UPDATE user SET status='A',unix_status='A',unix_uid=". account_nextuid()."  WHERE user_name='$GLOBALS[form_loginname]'");
	  } else {
	    $res = db_query("UPDATE user SET status='A',unix_status='A'  WHERE user_name='$GLOBALS[form_loginname]'");
	  }
		session_redirect("/account/first.php");
	}
}

$HTML->header(array('title'=>'Login'));

?>
<p><h2><?php print $GLOBALS['sys_name']; ?> Account Verification</h2>
<P>In order to complete your registration, login now. Your account will
then be activated for normal logins.
<?php 
if ($GLOBALS['error_msg']) {
	print '<P><FONT color="#FF0000">'.$GLOBALS['error_msg'].'</FONT>';
}
if ($Login && !$success) {
	echo '<h2><FONT COLOR="RED">'. $feedback .'</FONT></H2>';
}
?>
<form action="verify.php" method="post">
<p>Login Name:
<br><input type="text" name="form_loginname">
<p>Password:
<br><input type="password" name="form_pw">
<INPUT type="hidden" name="confirm_hash" value="<?php print $confirm_hash; ?>">
<p><input type="submit" name="Login" value="Login">
</form>

<?php
$HTML->footer(array());

?>
