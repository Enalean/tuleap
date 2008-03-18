<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// 

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

    $request =& HTTPRequest::instance();

	if (!$request->isPost() || !$request->exist('Update')) {
		return 0;
	}
	
	if (!$request->existAndNonEmpty('form_realname')) {
		$GLOBALS['Response']->addFeedback('error', $Language->getText('account_change_realname', 'error'));
		return 0;
	}
	
    // Escape name
    // @see register.php
    $purifier =& CodeX_HTMLPurifier::instance();

    $realname = $purifier->purify($request->get('form_realname'), CODEX_PURIFIER_STRIP_HTML);

	// if we got this far, it must be good
	db_query("UPDATE user SET realname='".db_es($realname)."' WHERE user_id=" . user_getid());
	return 1;
}

// ###### first check for valid login, if so, congratulate

if (register_valid()) {
	session_redirect("/account/");
} else { // not valid registration, or first time to page
	$HTML->header(array('title'=>$Language->getText('account_change_realname', 'title')));

?>
<p><b><?php $Language->getText('account_change_realname', 'title'); ?></b>
<form action="change_realname.php" method="post">
<p><?php echo $Language->getText('account_change_realname', 'new_name'); ?>:
<br><input type="text" name="form_realname">
<p><input type="submit" name="Update" value="<?php echo $Language->getText('global', 'btn_update'); ?>">
</form>

<?php
}
$HTML->footer(array());

?>
