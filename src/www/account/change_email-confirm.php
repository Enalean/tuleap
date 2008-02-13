<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// 

require_once('pre.php');
require_once('common/mail/Mail.class.php');
require_once('common/event/EventManager.class.php');

$Language->loadLanguageMsg('account/account');

$em =& EventManager::instance();
$em->processEvent('before_change_email-confirm', array());

$request =& HTTPRequest::instance();

$confirm_hash = substr(md5($GLOBALS['session_hash'] . time()),0,16);

$res_user = db_query("SELECT * FROM user WHERE user_id=".user_getid());
if (db_numrows($res_user) < 1) exit_error("Invalid User","That user does not exist.");
$row_user = db_fetch_array($res_user);

$mail_is_sent = false;

$form_newemail = $request->get('form_newemail');
if(validate_email($form_newemail)) {

    db_query("UPDATE user SET confirm_hash='".$confirm_hash."',email_new='".db_es($form_newemail)."' "
             . "WHERE user_id=".$row_user['user_id']);

    list($host,$port) = explode(':',$GLOBALS['sys_default_domain']);

    $message = stripcslashes($Language->getText('account_change_email-confirm', 'message', array($GLOBALS['sys_name'], get_server_url()."/account/change_email-complete.php?confirm_hash=".$confirm_hash)));

    $mail =& new Mail();
    $mail->setTo($form_newemail);
    $mail->setSubject($GLOBALS['sys_name'].': '.$Language->getText('account_change_email-confirm', 'title'));
    $mail->setBody($message);
    $mail->setFrom($GLOBALS['sys_noreply']);
    $mail_is_sent = $mail->send();
    if (!$mail_is_sent) {
        $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('global', 'mail_failed', array($GLOBALS['sys_email_admin'])));
    }
} else {
    $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('include_utils', 'invalid_email'));
}
site_header(array('title'=>$Language->getText('account_change_email-confirm', 'title'))); ?>

<P><B><?php if ($mail_is_sent) { echo $Language->getText('account_change_email-confirm', 'title'); ?></B>

<P><?php echo $Language->getText('account_change_email-confirm', 'mailsent'); ?>.

<?php
}
echo '<p><a href="/">['. $Language->getText('global', 'back_home'). ']</a></p>';
site_footer(array());

?>
