<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// 

require_once('pre.php');    
require_once('account.php');

$request =& HTTPRequest::instance();


// ###### function register_valid()
// ###### checks for valid register from form post

function register_valid($user_id)	{
    $request =& HTTPRequest::instance();

    if (!$request->isPost() || !$request->exist('Update')) {
		return 0;
	}
	
	// check against old pw
	$res = db_query("SELECT user_pw, status FROM user WHERE status IN ('A', 'R') AND user_id=".db_ei($user_id));
	if (!$res  || db_numrows($res) != 1) {
        $GLOBALS['Response']->addFeedback('error', "Internal error: Cannot locate user in database.");
	  return 0;
	}
	
	$row_pw = db_fetch_array();
	if ($row_pw['user_pw'] != md5($request->get('form_oldpw'))) {
		$GLOBALS['Response']->addFeedback('error', "Old password is incorrect.");
		return 0;
	}

	if (($row_pw['status'] != 'A')&&($row_pw['status'] != 'R')) {
		$GLOBALS['Response']->addFeedback('error', "Account must be active to change password.");
		return 0;
	}

	if (!$request->exist('form_pw')) {
		$GLOBALS['Response']->addFeedback('error', "You must supply a password.");
		return 0;
	}
	if ($request->get('form_pw') != $request->get('form_pw2')) {
		$GLOBALS['Response']->addFeedback('error', "Passwords do not match.");
		return 0;
	}
	if (!account_pwvalid($request->get('form_pw'), $errors)) {
        foreach($errors as $e) {
            $GLOBALS['Response']->addFeedback('error', $e);
        }
		return 0;
	}
	
	// if we got this far, it must be good
        if (!account_set_password($user_id,$request->get('form_pw')) ) {
            $GLOBALS['Response']->addFeedback('error', "Internal error: Could not update password.");
            return 0;
	}

	return 1;
}

require_once('common/event/EventManager.class.php');
$em =& EventManager::instance();
$em->processEvent('before_change_pw', array());

// ###### first check for valid login, if so, congratulate
$user_id = is_numeric($request->get('user_id')) ? (int)$request->get('user_id') : user_getid();
if (register_valid($user_id)) {
    $HTML->header(array('title'=>$Language->getText('account_change_pw', 'title_success')));
?>
<p><b><? echo $Language->getText('account_change_pw', 'title_success'); ?></b>
<p><? echo $Language->getText('account_change_pw', 'message', array($GLOBALS['sys_name'])); ?>

<p><a href="/">[ <? echo $Language->getText('global', 'back_home');?> ]</a>
<?php
} else { // not valid registration, or first time to page
	$HTML->includeJavascriptFile('/scripts/check_pw.js.php');
	$HTML->header(array('title'=>$Language->getText('account_options', 'change_password')));

?>
<p><b><? echo $Language->getText('account_change_pw', 'title'); ?></b>
<form action="change_pw.php" method="post" autocomplete="off" >
<p><? echo $Language->getText('account_change_pw', 'old_password'); ?>:
<br><input type="password" value="" name="form_oldpw">
<?php user_display_choose_password('',is_numeric($request->get('user_id')) ? $request->get('user_id') : 0); ?>
<p><input type="submit" name="Update" value="<? echo $Language->getText('global', 'btn_update'); ?>">
</form>

<?php
}
$HTML->footer(array());

?>
