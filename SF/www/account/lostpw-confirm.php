<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// $Id$

require($DOCUMENT_ROOT.'/include/pre.php');    
 
$LANG->loadLanguageMsg('account/account');

$confirm_hash = md5($session_hash . strval(time()) . strval(rand()));

$res_user = db_query("SELECT * FROM user WHERE user_name='$form_loginname'");
if (db_numrows($res_user) < 1) exit_error("Invalid User","That user does not exist.");
$row_user = db_fetch_array($res_user);

db_query("UPDATE user SET confirm_hash='$confirm_hash' WHERE user_id=$row_user[user_id]");

if (session_issecure()) {
    $server = 'https://'.$GLOBALS['sys_https_host'];
} else {
    $server = 'http://'.$GLOBALS['sys_default_domain'];
}

list($host,$port) = explode(':',$GLOBALS['sys_default_domain']);		

$message = stripcslashes($LANG->getText('account_lostpw-confirm', 'mail_body',
	      array($GLOBALS['sys_name'], 
		    "$server/account/lostlogin.php?confirm_hash=$confirm_hash")));

$hdrs = "From: noreply@".$host.$GLOBALS['sys_lf'];
$hdrs .='Content-type: text/plain; charset=iso-8859-1'.$GLOBALS['sys_lf'];

mail($row_user['email'],
     $LANG->getText('account_lostpw-confirm', 'mail_subject', array($GLOBALS['sys_name'])),$message,$hdrs);

$HTML->header(array('title'=>$LANG->getText('account_lostpw-confirm', 'title')));

?>

	      <P><?php echo $LANG->getText('account_lostpw-confirm', 'msg_confirm'); ?>

<P><A href="/">[<?php echo $LANG->getText('global', 'back_home'); ?>]</A>

<?php
$HTML->footer(array());

?>
