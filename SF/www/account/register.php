<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// $Id$

require "pre.php";
require "proj_email.php";
require "account.php";
require "timezones.php";

// ###### function register_valid()
// ###### checks for valid register from form post

function register_valid($confirm_hash)	{
    global $HTTP_POST_VARS, $G_USER;

    if (db_numrows(db_query("SELECT user_id FROM user WHERE "
			    . "user_name LIKE '$HTTP_POST_VARS[form_loginname]'")) > 0) {
	$GLOBALS['register_error'] = "That username already exists.";
	return 0;
    }
    if (!$HTTP_POST_VARS['form_loginname']) {
	$GLOBALS['register_error'] = "You must supply a username.";
	return 0;
    }
    if (!$HTTP_POST_VARS['form_pw']) {
	$GLOBALS['register_error'] = "You must supply a password.";
	return 0;
    }
    if ($HTTP_POST_VARS['form_pw'] != $HTTP_POST_VARS['form_pw2']) {
	$GLOBALS['register_error'] = "Passwords do not match.";
	return 0;
    }
    if (!$HTTP_POST_VARS['form_register_purpose'] && $GLOBALS['sys_user_approval']) {
	$GLOBALS['register_error'] = "You must explain the purpose of your registration.";
	return 0;
    }
        if (!account_pwvalid($HTTP_POST_VARS['form_pw'])) {
	return 0;
    }
    if (!account_namevalid($HTTP_POST_VARS['form_loginname'])) {
	return 0;
    }
    if (!validate_email($HTTP_POST_VARS['form_email'])) {
	$GLOBALS['register_error'] = ' Invalid Email Address ';
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
	exit_error('error',db_error());
	return 0;
    } else {
	return db_insertid($result);
    }
}


// ###### first check for valid login, if so, congratulate

if ($Register) {

    $confirm_hash = substr(md5($session_hash . $HTTP_POST_VARS['form_pw'] . time()),0,16);

    if ($new_userid = register_valid($confirm_hash)) {
    
	$HTML->header(array('title'=>'Register Confirmation'));

	$user_name = user_getname($new_userid);
	if ($GLOBALS['sys_user_approval'] == 0) {
	    send_new_user_email($GLOBALS['form_email'], $confirm_hash);
	    include(util_get_content('account/register_confirmation'));
	} else {
	    include(util_get_content('account/register_needs_approval'));
	}
	$HTML->footer(array());
	exit;
    }
}

//
// not valid registration, or first time to page
//
$HTML->header(array('title'=> $GLOBALS['sys_name'].': Register'));

?>
    
<h2><?php print $GLOBALS['sys_name']; ?> New Account Registration 
<?php echo help_button('UserRegistration.html');?></h2>

<?php 
if ($register_error) {
    print "<p><blink><b><span class=\"feedback\">$register_error</span></b></blink>";
}
$star = '<span class="highlight"><big>*</big></span>';
?>

<form action="/account/register.php" method="post">
<p>Login Name <strong>(Lower case only!)</strong> <? echo $star; ?>:<br>
<input type="text" name="form_loginname" value="<?php print stripslashes($form_loginname); ?>">
<? include(util_get_content('account/register_login')); ?>

<p>Password (min. 6 chars) <? echo $star; ?>:<br>
<input type="password" name="form_pw" value="<?php print stripslashes($form_pw); ?>">

<p>Password (repeat) <? echo $star; ?>:<br>
<input type="password" name="form_pw2" value="<?php print stripslashes($form_pw2); ?>">

<P>Full/Real Name <? echo $star; ?>:<br>
<INPUT size=40 type="text" name="form_realname" value="<?php print stripslashes($form_realname); ?>">

<P>Email Address <? echo $star; ?>:<BR>
<INPUT size=40 type="text" name="form_email" value="<?php print stripslashes($form_email); ?>"><BR>
<? include(util_get_content('account/register_email')); ?>
<P>Timezone:<BR>
<?php echo html_get_timezone_popup ('timezone','GMT'); ?>
<P>

<P><INPUT type="checkbox" name="form_mail_site" value="1" checked>
Receive Email about Site Updates <I>(Very low traffic and includes
security notices. Highly Recommended.)</I>

<P><INPUT type="checkbox" name="form_mail_va" value="1">
Receive additional community mailings. <I>(Low traffic.)</I>

<?php
if ($GLOBALS['sys_user_approval'] == 1) {
    include(util_get_content('account/register_purpose'));
    echo '<textarea wrap="virtual" rows="5" cols="70" name="form_register_purpose"></textarea></p>';
}
?>

<p>
Fields marked with <? echo $star; ?> are mandatory.
</p>
<p><input type="submit" name="Register" value="Register">

</form>
<?php

$HTML->footer(array());
?>
