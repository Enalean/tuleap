<?php
//
// Copyright 2015 (c) Enalean
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
//

require_once('pre.php');

$em =& EventManager::instance();
$em->processEvent('before_lostpw-confirm', array());

$number_generator = new RandomNumberGenerator();
$confirm_hash     = $number_generator->getNumber();

$request =& HTTPRequest::instance();

$res_user = db_query("SELECT * FROM user WHERE user_name='".db_es($request->get('form_loginname'))."'");
if (db_numrows($res_user) < 1) exit_error("Invalid User","That user does not exist.");
$row_user = db_fetch_array($res_user);

db_query("UPDATE user SET confirm_hash='".$confirm_hash."' WHERE user_id=".$row_user['user_id']);

list($host,$port) = explode(':',$GLOBALS['sys_default_domain']);

$message = stripcslashes($Language->getText('account_lostpw-confirm', 'mail_body',
	      array($GLOBALS['sys_name'],
                get_server_url()."/account/lostlogin.php?confirm_hash=".$confirm_hash)));

$mail = new Mail();
$mail->setTo($row_user['email'],true);
$mail->setSubject($Language->getText('account_lostpw-confirm', 'mail_subject', array($GLOBALS['sys_name'])));
$mail->setBody($message);
$mail->setFrom($GLOBALS['sys_noreply']);
$mail_is_sent = $mail->send();
if (!$mail_is_sent) {
    $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('global', 'mail_failed', array($GLOBALS['sys_email_admin'])), CODENDI_PURIFIER_FULL);
}
site_header(array('title'=>$Language->getText('account_lostpw-confirm', 'title')));
if ($mail_is_sent) {
    echo '<p>'. $Language->getText('account_lostpw-confirm', 'msg_confirm') .'</p>';
}
echo '<p><a href="/">['. $Language->getText('global', 'back_home'). ']</a></p>';
site_footer(array());

?>
