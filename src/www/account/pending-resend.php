<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// $Id$

require_once('pre.php');    
require_once('proj_email.php');

$Language->loadLanguageMsg('account/account');

$res_user = db_query("SELECT * FROM user WHERE user_name='$form_user'");
$row_user = db_fetch_array($res_user);

// only mail if pending
list($host,$port) = explode(':',$GLOBALS['sys_default_domain']);
if ($GLOBALS['sys_user_approval'] != 0 && $row_user[status] != 'V') {
    exit_error($Language->getText('include_exit', 'error'),
               $Language->getText('account_pending-resend', 'needapproval'));
 }
if ($row_user[status] == 'P' || $row_user[status] == 'V') {
    send_new_user_email($row_user['email'], $row_user['confirm_hash']);
    $HTML->header(array(title=>$Language->getText('account_pending-resend', 'title')));
?>

<P><?php echo $Language->getText('account_pending-resend', 'message'); ?>

     <P><A href="/">[<?php echo $Language->getText('global', 'back_home'); ?>]</A>
 
<?php
} else {
	exit_error($Language->getText('include_exit', 'error'),
		   $Language->getText('account_pending-resend', 'notpending'));
}

$HTML->footer(array());

?>
