<?php
//
// Copyright (c) Enalean, 2015. All Rights Reserved.
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// 

require_once('pre.php');
require_once('proj_email.php');


$request = HTTPRequest::instance();

if ($request->get('user_name') != null) {
    $user_name = $request->get('user_name');
} else {
    $user_name = $request->get('form_user');
}

$user = UserManager::instance()->getUserByUserName($user_name);

if (!$user) {
    exit_error($Language->getText('include_exit', 'error'),
        $Language->getText('account_pending-resend', 'notpending'));
}

// only mail if pending
list($host,$port) = explode(':',$GLOBALS['sys_default_domain']);
if ($GLOBALS['sys_user_approval'] != 0 && $user->getStatus() != PFUser::STATUS_VALIDATED) {
    exit_error($Language->getText('include_exit', 'error'),
               $Language->getText('account_pending-resend', 'needapproval'));
 }
if ($user->getStatus() === PFUser::STATUS_PENDING || $user->getStatus() === PFUser::STATUS_VALIDATED) {
    if (!send_new_user_email($user->getEmail(), $user->getUserName(), '', $user->getConfirmHash(), 'mail', false)) {
	exit_error($Language->getText('include_exit', 'error'),
                   $user->getEmail()." - ".$GLOBALS['Language']->getText('global', 'mail_failed', array($GLOBALS['sys_email_admin'])));
    }
    $HTML->header(array('title'=>$Language->getText('account_pending-resend', 'title')));
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
