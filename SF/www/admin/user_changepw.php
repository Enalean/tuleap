<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// $Id$

require($DOCUMENT_ROOT.'/include/pre.php');    
require($DOCUMENT_ROOT.'/include/account.php');

$LANG->loadLanguageMsg('admin/admin');

session_require(array('group'=>'1','admin_flags'=>'A'));

// ###### function register_valid()
// ###### checks for valid register from form post

function register_valid()	{
    global $LANG;

  if (!$GLOBALS['Update']) {
    return 0;
  }
  if (!$GLOBALS['user_id']) {
    $GLOBALS['register_error'] = $LANG->getText('admin_user_changepw','error_userid');
    return 0;
  }
  if (!$GLOBALS['form_pw']) {
    $GLOBALS['register_error'] = $LANG->getText('admin_user_changepw','error_nopasswd');
    return 0;
  }
  if ($GLOBALS['form_pw'] != $GLOBALS['form_pw2']) {
    $GLOBALS['register_error'] = $LANG->getText('admin_user_changepw','error_passwd');
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
    $GLOBALS['register_error'] = $LANG->getText('admin_user_changepw','error_update');
    return 0;
  }
    
  return 1;
}

// ###### first check for valid login, if so, congratulate

if (register_valid()) {
    $HTML->header(array(title=>$LANG->getText('admin_user_changepw','title_changed')));
    $d = getdate(time());
    $h = ($sys_crondelay - 1) - ($d[hours] % $sys_crondelay);
    $m= 60 - $d['minutes'];
?>
<h3><?php echo $LANG->getText('admin_user_changepw','header_changed'); ?></h3>
									       <p><?php echo $LANG->getText('admin_user_changepw','msg_changed',array($h,$m)); ?></h3>

<p><a href="/admin"><?php echo $LANG->getText('global','back'); ?></a>.
<?php
} else { // not valid registration, or first time to page
    $HTML->header(array(title=>$LANG->getText('admin_user_changepw','title')));

?>
<h3><?php echo $LANG->getText('admin_user_changepw','header'); ?></h3>
<?php if ($register_error) print "<p><span class=\"highlight\">$register_error</span>"; ?>
<form action="user_changepw.php" method="post">
<p><?php echo $LANG->getText('admin_user_changepw','new_passwd'); ?>:
<br><input type="password" name="form_pw">
<p><?php echo $LANG->getText('admin_user_changepw','new_passwd2'); ?>:
<br><input type="password" name="form_pw2">
<INPUT type=hidden name="user_id" value="<?php print $user_id; ?>">
<p><input type="submit" name="Update" value="<?php echo $LANG->getText('global','btn_update'); ?>">
</form>

<?php
}
$HTML->footer(array());

?>
