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
$em->processEvent('before_change_email-complete', array());

$hp = Codendi_HTMLPurifier::instance();
$request =& HTTPRequest::instance();
$csrf = new CSRFSynchronizerToken('/account/change_email.php');
$csrf->check();

// ###### function register_valid()
// ###### checks for valid register from form post

$confirm_hash = $request->get('confirm_hash');

$res_user = db_query("SELECT * FROM user WHERE confirm_hash='".db_es($confirm_hash)."'");
if (db_numrows($res_user) > 1) {
    exit_error($Language->getText('include_exit', 'error'),
	       $Language->getText('account_change_email-complete', 'duplicate_hash'));
}
if (db_numrows($res_user) < 1) {
    exit_error($Language->getText('include_exit', 'error'),
	       $Language->getText('account_change_email-complete', 'invalid_hash'));
}

$row_user = db_fetch_array($res_user);

db_query("UPDATE user SET "
	. "email='" . $row_user['email_new'] . "',"
	. "confirm_hash='none',"
	. "email_new='" . $row_user['email'] . "' WHERE "
	. "confirm_hash='".db_es($confirm_hash)."'");

$em = EventManager::instance();
$em->processEvent(Event::USER_EMAIL_CHANGED, $row_user['user_id']);

$HTML->header(array('title'=>$Language->getText('account_change_email-complete', 'title')));
?>
<p><b><?php echo $Language->getText('account_change_email-complete', 'title'); ?></b>
<P><?php echo $Language->getText('account_change_email-complete', 'message',
			     array(  $hp->purify($row_user['realname'], CODENDI_PURIFIER_CONVERT_HTML) , $row_user['email_new'],
				    $GLOBALS['sys_name'], $row_user['user_name'])); ?>

<P><A href="/">[ <?php echo $Language->getText('global', 'back_home'); ?> ]</A>

<?php
$HTML->footer(array());

?>
