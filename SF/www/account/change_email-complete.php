<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// $Id$

require($DOCUMENT_ROOT.'/include/pre.php');    
require($DOCUMENT_ROOT.'/include/account.php');

$Language->loadLanguageMsg('account/account');

// ###### function register_valid()
// ###### checks for valid register from form post

$res_user = db_query("SELECT * FROM user WHERE confirm_hash='$confirm_hash'");
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
	. "confirm_hash='$confirm_hash'");

$HTML->header(array('title'=>$Language->getText('account_change_email-complete', 'title_complete')));
?>
<p><b><?php echo $Language->getText('account_change_email-complete', 'title'); ?></b>
<P><?php echo $Language->getText('account_change_email-complete', 'message',
			     array( $row_user['realname'], $row_user[email_new],
				    $GLOBALS['sys_name'], $row_user['user_name'])); ?>

<P><A href="/">[ <?php echo $Language->getText('global', 'back_home'); ?> ]</A>

<?php
$HTML->footer(array());

?>
