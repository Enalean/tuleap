<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// $Id$

require($DOCUMENT_ROOT.'/include/pre.php');   
 
$LANG->loadLanguageMsg('account/account');

$confirm_hash = substr(md5($session_hash . time()),0,16);

$res_user = db_query("SELECT * FROM user WHERE user_id=".user_getid());
if (db_numrows($res_user) < 1) exit_error("Invalid User","That user does not exist.");
$row_user = db_fetch_array($res_user);

db_query("UPDATE user SET confirm_hash='$confirm_hash',email_new='$form_newemail' "
	. "WHERE user_id=$row_user[user_id]");

list($host,$port) = explode(':',$GLOBALS['sys_default_domain']);		

if (session_issecure()) {
    $server = 'https://'.$GLOBALS['sys_https_host'];
} else {
    $server = 'http://'.$GLOBALS['sys_default_domain'];
}
$message = stripcslashes($LANG->getText('account_change_email-confirm', 'message', array($GLOBALS['sys_name'], "$server/account/change_email-complete.php?confirm_hash=$confirm_hash")));

$hdrs = "From: noreply@".$host.$GLOBALS['sys_lf'];
$hdrs .='Content-type: text/plain; charset=iso-8859-1'.$GLOBALS['sys_lf'];

mail($form_newemail,$GLOBALS['sys_name'].': '.$LANG->getText('account_change_email-confirm', 'title'),$message,$hdrs);

$HTML->header(array('title'=>$LANG->getText('account_change_email-confirm', 'title'))); ?>

<P><B><?php echo $LANG->getText('account_change_email-confirm', 'title'); ?></B>

<P><?php echo $LANG->getText('account_change_email-confirm', 'mailsent'); ?>.

<P><A href="/">[ <?php echo $LANG->getText('global', 'back_home'); ?> ]</A>

<?php
$HTML->footer(array());

?>
