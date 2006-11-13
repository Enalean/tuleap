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

$res_lostuser = db_query("SELECT * FROM user WHERE confirm_hash='$confirm_hash'");
if (db_numrows($res_lostuser) > 1) {
    exit_error($Language->getText('include_exit', 'error'),
	       $Language->getText('account_lostlogin', 'duplicate_hash'));
}
if (db_numrows($res_lostuser) < 1) {
	exit_error($Language->getText('include_exit', 'error'),
		   $Language->getText('account_lostlogin', 'invalid_hash'));
}
$row_lostuser = db_fetch_array($res_lostuser);

if ($Update && $form_pw && !strcmp($form_pw,$form_pw2)) {
	db_query("UPDATE user SET "
		. "user_pw='" . md5($form_pw) . "',"
		. "unix_pw='" . account_genunixpw($form_pw) . "',"
		. "windows_pw='" . account_genwinpw($form_pw) . "' WHERE "
		. "confirm_hash='$confirm_hash'");

	session_redirect("/");
}

$HTML->header(array('title'=>$Language->getText('account_lostlogin', 'title')));
?>
<p><b><?php echo $Language->getText('account_lostlogin', 'title'); ?></b>
<P><?php echo $Language->getText('account_lostlogin', 'message', array($row_lostuser['realname'])); ?>.

<FORM action="lostlogin.php">
<p><?php echo $Language->getText('account_lostlogin', 'newpasswd'); ?>:
<br><input type="password" name="form_pw">
<p><?php echo $Language->getText('account_lostlogin', 'newpasswd2'); ?>:
<br><input type="password" name="form_pw2">
<input type="hidden" name="confirm_hash" value="<?php print $confirm_hash; ?>">
<p><input type="submit" name="Update" value="<?php echo $Language->getText('global', 'btn_update'); ?>">
</form>

<?php
$HTML->footer(array());

?>
