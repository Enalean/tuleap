<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// 

require_once('pre.php');    
require_once('account.php');
require_once('common/include/CSRFSynchronizerToken.class.php');
require_once('common/event/EventManager.class.php');

$em =& EventManager::instance();
$em->processEvent('before_change_realname', array());
$csrf = new CSRFSynchronizerToken('/account/change_realname.php');

// ###### function register_valid()
// ###### checks for valid register from form post

function register_valid(CSRFSynchronizerToken $csrf)	{
    global $Language;

    $request =& HTTPRequest::instance();
	if (!$request->isPost() || !$request->exist('Update')) {
		return 0;
	}
	
	if (!$request->existAndNonEmpty('form_realname')) {
		$GLOBALS['Response']->addFeedback('error', $Language->getText('account_change_realname', 'error'));
		return 0;
	}
	$csrf->check();
	// if we got this far, it must be good
    $sql = "UPDATE user SET realname='".db_es($request->get('form_realname'))."' WHERE user_id=" . user_getid();
    db_query($sql);
	return 1;
}

// ###### first check for valid login, if so, congratulate

if (register_valid($csrf)) {
	session_redirect("/account/");
} else { // not valid registration, or first time to page
	$HTML->header(array('title'=>$Language->getText('account_change_realname', 'title')));

    $um = UserManager::instance();
    $user = $um->getCurrentUser();
    $hp = Codendi_HTMLPurifier::instance();
?>
<h2><?php echo $Language->getText('account_change_realname', 'title'); ?></h2>
<form action="change_realname.php" method="post">
<?php
echo $csrf->fetchHTMLInput();
echo $Language->getText('account_change_realname', 'new_name'); ?>:
<br><input type="text" name="form_realname" class="textfield_medium" value="<?php echo $hp->purify($user->getRealname(), CODENDI_PURIFIER_CONVERT_HTML) ?>" />
<br>
<input class="btn btn-primary" type="submit" name="Update" value="<?php echo $Language->getText('global', 'btn_update'); ?>">
</form>

<?php
}
$HTML->footer(array());

?>
