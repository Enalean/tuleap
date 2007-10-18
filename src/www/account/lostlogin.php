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

$request =& HTTPRequest::instance();

$confirm_hash = $request->get('confirm_hash');

$res_lostuser = db_query("SELECT * FROM user WHERE confirm_hash='".db_es($confirm_hash)."'");
if (db_numrows($res_lostuser) > 1) {
    exit_error($Language->getText('include_exit', 'error'),
	       $Language->getText('account_lostlogin', 'duplicate_hash'));
}
if (db_numrows($res_lostuser) < 1) {
	exit_error($Language->getText('include_exit', 'error'),
		   $Language->getText('account_lostlogin', 'invalid_hash'));
}
$row_lostuser = db_fetch_array($res_lostuser);

if ($request->isPost()
    && $request->exist('Update')
    && $request->existAndNonEmpty('form_pw')
    && !strcmp($request->get('form_pw'), $request->get('form_pw2'))) {
    $form_pw = $request->get('form_pw');
	db_query("UPDATE user SET "
		. "user_pw='" . md5($form_pw) . "',"
		. "unix_pw='" . account_genunixpw($form_pw) . "',"
		. "windows_pw='" . account_genwinpw($form_pw) . "' WHERE "
		. "confirm_hash='".db_es($confirm_hash)."'");

	session_redirect("/");
}

$purifier =& CodeX_HTMLPurifier::instance();

$HTML->header(array('title'=>$Language->getText('account_lostlogin', 'title')));
?>
<p><b><?php echo $Language->getText('account_lostlogin', 'title'); ?></b>
<P><?php echo $Language->getText('account_lostlogin', 'message', array($purifier->purify($row_lostuser['realname']))); ?>.

<form action="lostlogin.php" method="post">
<p><?php echo $Language->getText('account_lostlogin', 'newpasswd'); ?>:
<br><input type="password" name="form_pw">
<p><?php echo $Language->getText('account_lostlogin', 'newpasswd2'); ?>:
<br><input type="password" name="form_pw2">
<input type="hidden" name="confirm_hash" value="<?php echo $purifier->purify($confirm_hash); ?>">
<p><input type="submit" name="Update" value="<?php echo $Language->getText('global', 'btn_update'); ?>">
</form>

<?php
$HTML->footer(array());

?>
