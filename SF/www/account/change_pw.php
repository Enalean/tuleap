<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// $Id$

require_once('pre.php');    
require_once('account.php');

$Language->loadLanguageMsg('account/account');

// ###### function register_valid()
// ###### checks for valid register from form post

function register_valid()	{

	if (!$GLOBALS['Update']) {
		return 0;
	}
	
	// check against old pw
	$res = db_query("SELECT user_pw, status FROM user WHERE user_id=" . user_getid());
	if (! $res) {
	  $GLOBALS['register_error'] = "Internal error: Cannot locate user in database.";
	  return 0;
	}
	
	$row_pw = db_fetch_array();
	if ($row_pw[user_pw] != md5($GLOBALS['form_oldpw'])) {
		$GLOBALS['register_error'] = "Old password is incorrect.";
		return 0;
	}

	if (($row_pw[status] != 'A')&&($row_pw[status] != 'R')) {
		$GLOBALS['register_error'] = "Account must be active to change password.";
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
        if (!account_set_password(user_getid(),$GLOBALS['form_pw']) ) {
            $GLOBALS['register_error'] = "Internal error: Could not update password.";
            return 0;
	}

	return 1;
}

if ($GLOBALS['sys_auth_type'] == 'ldap') {
    // Don't send LDAP password!
    // There should be no link to this page...
    exit_permission_denied();
}


// ###### first check for valid login, if so, congratulate

if (register_valid()) {
    $HTML->header(array(title=>$Language->getText('account_change_pw', 'title_success')));
    $d = getdate(time());
    $h = ($sys_crondelay - 1) - ($d[hours] % $sys_crondelay);
    $m= 60 - $d[minutes];
?>
<p><b><? echo $Language->getText('account_change_pw', 'title_success'); ?></b>
<p><? echo $Language->getText('account_change_pw', 'message', array($GLOBALS['sys_name'],$h,$m)); ?

<p>[ <? echo $Language->getText('global', 'back_home');?> ]
<?php
} else { // not valid registration, or first time to page
	$HTML->header(array(title=>));

?>
<p><b><? echo $Language->getText('account_change_pw', 'title'); ?></b>
<?php if ($register_error) print "<p><span class=\"highlight\"><b>$register_error</b></span>"; ?>
<form action="change_pw.php" method="post">
<p><? echo $Language->getText('account_change_pw', 'old_password'); ?>:
<br><input type="password" name="form_oldpw">
<p><? echo $Language->getText('account_change_pw', 'new_password'); ?>:
<br><input type="password" name="form_pw">
<p><? echo $Language->getText('account_change_pw', 'new_password2'); ?>:
<br><input type="password" name="form_pw2">
<p><input type="submit" name="Update" value="<? echo $Language->getText('global', 'btn_update'); ?>">
</form>

<?php
}
$HTML->footer(array());

?>
