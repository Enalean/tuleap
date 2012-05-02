<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// 

require_once('pre.php');
require_once('proj_email.php');


$request =& HTTPRequest::instance();

if($request->get('user_name')!=null){
    $user_name= $request->get('user_name');
} else {
    $user_name = $request->get('form_user');
}

$res_user = db_query("SELECT * FROM user WHERE user_name='$user_name'");
$row_user = db_fetch_array($res_user);

// only mail if pending
list($host,$port) = explode(':',$GLOBALS['sys_default_domain']);
if ($GLOBALS['sys_user_approval'] != 0 && $row_user['status'] != 'V') {
    exit_error($Language->getText('include_exit', 'error'),
               $Language->getText('account_pending-resend', 'needapproval'));
 }
if ($row_user['status'] == 'P' || $row_user['status'] == 'V') {
    if (!send_new_user_email($row_user['email'], $row_user['confirm_hash'], $row_user['user_name'])) {
	exit_error($Language->getText('include_exit', 'error'),
                   $row_user['email']." - ".$GLOBALS['Language']->getText('global', 'mail_failed', array($GLOBALS['sys_email_admin'])));
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
