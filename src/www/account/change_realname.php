<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// $Id: change_realname.php 4433 2006-12-07 09:43:33 +0000 (Thu, 07 Dec 2006) ahardyau $

require_once('pre.php');    
require_once('account.php');

require_once('common/event/EventManager.class.php');
$em =& EventManager::instance();
$em->processEvent('before_change_realname', array());

$Language->loadLanguageMsg('account/account');

// ###### function register_valid()
// ###### checks for valid register from form post

function register_valid()	{
    global $Language;

	if (!$GLOBALS["Update"]) {
		return 0;
	}
	
	if (!$GLOBALS['form_realname']) {
		$GLOBALS['register_error'] = $Language->getText('account_change_realname', 'error');
		return 0;
	}
	
	// if we got this far, it must be good
	db_query("UPDATE user SET realname='$GLOBALS[form_realname]' WHERE user_id=" . user_getid());
	return 1;
}

// ###### first check for valid login, if so, congratulate

if (register_valid()) {
	session_redirect("/account/");
} else { // not valid registration, or first time to page
	$HTML->header(array(title=>$Language->getText('account_change_realname', 'title')));

?>
<p><b><?php $Language->getText('account_change_realname', 'title'); ?></b>
<?php if ($register_error) print "<p>$register_error"; ?>
<form action="change_realname.php" method="post">
<p><?php echo $Language->getText('account_change_realname', 'new_name'); ?>:
<br><input type="text" name="form_realname">
<p><input type="submit" name="Update" value="<?php echo $Language->getText('global', 'btn_update'); ?>">
</form>

<?php
}
$HTML->footer(array());

?>
