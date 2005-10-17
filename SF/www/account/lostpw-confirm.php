<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// $Id$

require_once('pre.php');    
require_once('common/mail/Mail.class');

$Language->loadLanguageMsg('account/account');

if ($GLOBALS['sys_auth_type'] == 'ldap') {
    // Don't send LDAP password!
    // There should be no link to this page...
    exit_permission_denied();
 }

$confirm_hash = md5($session_hash . strval(time()) . strval(rand()));

$res_user = db_query("SELECT * FROM user WHERE user_name='$form_loginname'");
if (db_numrows($res_user) < 1) exit_error("Invalid User","That user does not exist.");
$row_user = db_fetch_array($res_user);

db_query("UPDATE user SET confirm_hash='$confirm_hash' WHERE user_id=$row_user[user_id]");

list($host,$port) = explode(':',$GLOBALS['sys_default_domain']);		

$message = stripcslashes($Language->getText('account_lostpw-confirm', 'mail_body',
	      array($GLOBALS['sys_name'], 
		    get_server_url()."/account/lostlogin.php?confirm_hash=$confirm_hash")));

$mail =& new Mail();
$mail->setTo($row_user['email']);
$mail->setSubject($Language->getText('account_lostpw-confirm', 'mail_subject', array($GLOBALS['sys_name'])));
$mail->setBody($message);
$mail->setFrom($GLOBALS['sys_noreply']);
$mail_is_sent = $mail->send();

site_header(array('title'=>$Language->getText('account_lostpw-confirm', 'title')));
if ($mail_is_sent) {
?>

	      <P><?php echo $Language->getText('account_lostpw-confirm', 'msg_confirm'); ?>

<P><A href="/">[<?php echo $Language->getText('global', 'back_home'); ?>]</A>

<?php
} else {
    $GLOBALS['feedback'] .= $GLOBALS['Language']->getText('global', 'mail_failed', array($GLOBALS['sys_email_admin']));
}
site_footer(array());

?>
