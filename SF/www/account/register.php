<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// $Id$

require($DOCUMENT_ROOT.'/include/pre.php');
require($DOCUMENT_ROOT.'/include/proj_email.php');
require($DOCUMENT_ROOT.'/include/account.php');
require($DOCUMENT_ROOT.'/include/timezones.php');
   
$LANG->loadLanguageMsg('account/account');

// ###### function register_valid()
// ###### checks for valid register from form post

function register_valid($confirm_hash)	{
    global $HTTP_POST_VARS, $G_USER, $LANG;

    if (db_numrows(db_query("SELECT user_id FROM user WHERE "
			    . "user_name LIKE '$HTTP_POST_VARS[form_loginname]'")) > 0) {
	$GLOBALS['register_error'] = $LANG->getText('account_register', 'err_exist');
	return 0;
    }
    if (!$HTTP_POST_VARS['form_loginname']) {
	$GLOBALS['register_error'] = $LANG->getText('account_register', 'err_nouser');
	return 0;
    }
    if (!$HTTP_POST_VARS['form_pw']) {
	$GLOBALS['register_error'] = $LANG->getText('account_register', 'err_nopasswd');
	return 0;
    }
    if ($HTTP_POST_VARS['form_pw'] != $HTTP_POST_VARS['form_pw2']) {
	$GLOBALS['register_error'] = $LANG->getText('account_register', 'err_passwd');
	return 0;
    }
    if ($HTTP_POST_VARS['timezone'] == 'None') {
	$GLOBALS['register_error'] = $LANG->getText('account_register', 'err_notz');
	return 0;
    }
    if (!$HTTP_POST_VARS['form_register_purpose'] && $GLOBALS['sys_user_approval']) {
	$GLOBALS['register_error'] = $LANG->getText('account_register', 'err_nopurpose');
	return 0;
    }
        if (!account_pwvalid($HTTP_POST_VARS['form_pw'])) {
	return 0;
    }
    if (!account_namevalid($HTTP_POST_VARS['form_loginname'])) {
	return 0;
    }
    if (!validate_email($HTTP_POST_VARS['form_email'])) {
	$GLOBALS['register_error'] = $LANG->getText('account_register', 'err_email');
	return 0;
    }

    $result=db_query("INSERT INTO user (user_name,user_pw,unix_pw,windows_pw,realname,register_purpose,email,add_date,"
		     . "status,confirm_hash,mail_siteupdates,mail_va,timezone) "
		     . "VALUES ('$HTTP_POST_VARS[form_loginname]','"
		     . md5($HTTP_POST_VARS['form_pw']) . "','"
		     . account_genunixpw($HTTP_POST_VARS['form_pw']) . "','"
		     . account_genwinpw($HTTP_POST_VARS['form_pw']) . "',"
		     . "'".$GLOBALS[form_realname]."',"
		     . "'".$GLOBALS[form_register_purpose]."',"
		     . "'".$GLOBALS[form_email]."',"
		     . time() . ","
		     . "'P','" // status pending
		     . $confirm_hash
		     . "',".($GLOBALS['form_mail_site']?"1":"0")
		     . ",".($GLOBALS['form_mail_va']?"1":"0")
		     . ",'".$GLOBALS['timezone']."')");

    if (!$result) {
	exit_error($LANG->getText('include_exit', 'error'),db_error());
	return 0;
    } else {
	return db_insertid($result);
    }
}


// ###### first check for valid login, if so, congratulate

if ($Register) {

    $confirm_hash = substr(md5($session_hash . $HTTP_POST_VARS['form_pw'] . time()),0,16);

    if ($new_userid = register_valid($confirm_hash)) {
    
	$HTML->header(array('title'=>$LANG->getText('account_register', 'title_confirm')));

	$user_name = user_getname($new_userid);
	if ($GLOBALS['sys_user_approval'] == 0) {
	    send_new_user_email($GLOBALS['form_email'], $confirm_hash);
	    echo '<p><b>'.$LANG->getText('account_register', 'title_confirm').'</b>';
	    echo '<p>'.$LANG->getText('account_register', 'msg_confirm', array($GLOBALS['sys_name'],$user_name));
	} else {
	    // Registration requires approval - send a mail to site admin and
	    // inform the user that approval is required
	    $href_approval = 'http'.(session_issecure() ? 's':'').'://'.
		$GLOBALS['sys_default_domain'].'/admin/approve_pending_users.php';

	    echo '<p><b>'.$LANG->getText('account_register', 'title_approval').'</b>';
	    echo '<p>'.$LANG->getText('account_register', 'msg_approval', array($GLOBALS['sys_name'],$user_name,$href_approval));

	    // Send a notification message to the Site administrator
	    list($host,$port) = explode(':',$GLOBALS['sys_default_domain']);
	    $hdrs = 'From: noreply@'.$host."\n";
	    $to = $GLOBALS['sys_email_admin'];
	    $subject = $LANG->getText('account_register', 'mail_approval_subject', array($user_name));
	    $body = stripcslashes($LANG->getText('account_register', 'mail_approval_body', array($GLOBALS['sys_name'], $user_name, $href_approval)));
	    mail($to,$subject,$body,$hdrs);
	    
	}
	$HTML->footer(array());
	exit;
    }
}

//
// not valid registration, or first time to page
//
$HTML->header(array('title'=>$LANG->getText('account_register', 'title') ));

?>
    
<h2><?php print $LANG->getText('account_register', 'title').' '.help_button('UserRegistration.html');?></h2>

<?php 
if ($register_error) {
    print "<p><blink><b><span class=\"feedback\">$register_error</span></b></blink>";
}
$star = '<span class="highlight"><big>*</big></span>';
?>

<form action="/account/register.php" method="post">
<p><?php print $LANG->getText('account_register', 'login').'&nbsp;'.$star; ?>:<br>
<input type="text" name="form_loginname" value="<?php print stripslashes($form_loginname); ?>">
<?php print $LANG->getText('account_register', 'login_directions'); ?>

<p><?php print $LANG->getText('account_register', 'passwd').'&nbsp;'.$star; ?>:<br>
<input type="password" name="form_pw" value="<?php print stripslashes($form_pw); ?>">
<?php print $LANG->getText('account_register', 'passwd_directions'); ?>

<p><?php print $LANG->getText('account_register', 'passwd2').'&nbsp;'.$star; ?>:<br>
<input type="password" name="form_pw2" value="<?php print stripslashes($form_pw2); ?>">
<?php print $LANG->getText('account_register', 'passwd2_directions'); ?>

<P><?php print $LANG->getText('account_register', 'realname').'&nbsp;'.$star; ?>:<br>
<INPUT size=40 type="text" name="form_realname" value="<?php print stripslashes($form_realname); ?>">
<?php print $LANG->getText('account_register', 'realname_directions'); ?>

<P><?php print $LANG->getText('account_register', 'email').'&nbsp;'.$star; ?>:<BR>
<INPUT size=40 type="text" name="form_email" value="<?php print stripslashes($form_email); ?>"><BR>
<?php print $LANG->getText('account_register', 'email_directions'); ?>


<P><?php print $LANG->getText('account_register', 'tz').'&nbsp;'.$star; ?>:<BR>
<?php 
$timezone = ($timezone?stripslashes($timezone):'None');
echo html_get_timezone_popup ('timezone',$timezone); ?>
<?php print $LANG->getText('account_register', 'tz_directions'); ?>
<P>

<P><INPUT type="checkbox" name="form_mail_site" value="1" checked>
<?php print $LANG->getText('account_register', 'siteupdate'); ?>

<P><INPUT type="checkbox" name="form_mail_va" value="1">
<?php print $LANG->getText('account_register', 'communitymail'); ?>

<P>
<?php
if ($GLOBALS['sys_user_approval'] == 1) {
    print $LANG->getText('account_register', 'purpose').'&nbsp;'.$star.":<br>";
    print $LANG->getText('account_register', 'purpose_directions');
    echo '<textarea wrap="virtual" rows="5" cols="70" name="form_register_purpose"></textarea></p>';
}
?>

<p>
<?php print $LANG->getText('account_register', 'mandatory', array($star)); ?>
</p>
<p><input type="submit" name="Register" value="<?php print $LANG->getText('account_register', 'btn_register'); ?>">

</form>
<?php

$HTML->footer(array());
?>
