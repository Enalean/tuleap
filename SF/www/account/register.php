<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// $Id$

require_once('pre.php');
require_once('proj_email.php');
require_once('account.php');
require_once('timezones.php');
   
$Language->loadLanguageMsg('account/account');

// ###### function register_valid()
// ###### checks for valid register from form post

function register_valid($confirm_hash)	{
    global $HTTP_POST_VARS, $Language;

    if (!$HTTP_POST_VARS['form_loginname']) {
	$GLOBALS['register_error'] = $Language->getText('account_register', 'err_nouser');
	return 0;
    }
    if (!$HTTP_POST_VARS['form_pw']) {
	$GLOBALS['register_error'] = $Language->getText('account_register', 'err_nopasswd');
	return 0;
    }
    if ($HTTP_POST_VARS['timezone'] == 'None') {
	$GLOBALS['register_error'] = $Language->getText('account_register', 'err_notz');
	return 0;
    }
    if (!$HTTP_POST_VARS['form_register_purpose'] && $GLOBALS['sys_user_approval']) {
	$GLOBALS['register_error'] = $Language->getText('account_register', 'err_nopurpose');
	return 0;
    }
    if (!validate_email($HTTP_POST_VARS['form_email'])) {
	$GLOBALS['register_error'] = $Language->getText('account_register', 'err_email');
	return 0;
    }
    if (!account_namevalid($HTTP_POST_VARS['form_loginname'])) {
	$GLOBALS['register_error'] = $Language->getText('account_register', 'err_name');
	return 0;
    }
    if (db_numrows(db_query("SELECT user_id FROM user WHERE "
			    . "user_name LIKE '$HTTP_POST_VARS[form_loginname]'")) > 0) {
	$GLOBALS['register_error'] = $Language->getText('account_register', 'err_exist');
	return 0;
    }
    if ($GLOBALS['sys_auth_type'] == 'ldap') {
        // LDAP authentication
	if (!$HTTP_POST_VARS['ldap_name']) {
            $GLOBALS['register_error'] = $Language->getText('account_register', 'err_no_ldap');
            return 0;
        }
        $ldap_name=$HTTP_POST_VARS['ldap_name'];

        // Check that LDAP password is OK
        $ldap = new LDAP();
        if (!$ldap->authenticate($ldap_name,$HTTP_POST_VARS['form_pw'])) {
            // password is invalid or user does not exist
            $GLOBALS['register_error'] = $GLOBALS['sys_org_name'].' Directory: '.$ldap->getErrorMessage();
            return 0;
        }


        if (db_numrows(("SELECT user_id,user_name FROM user WHERE "
                        . "ldap_name LIKE '$ldap_name'")) >0) { 
            $GLOBALS['register_error'] = $Language->getText('account_register', 'err_exist');
            return 0;
        }

        // You may add LDAP checking in this site-content file (e.g. user suspended, etc.)
        include($Language->getContent('account/register_ldap_check_account'));


    } else {
        if ($HTTP_POST_VARS['form_pw'] != $HTTP_POST_VARS['form_pw2']) {
            $GLOBALS['register_error'] = $Language->getText('account_register', 'err_passwd');
            return 0;
        }
        if (!account_pwvalid($HTTP_POST_VARS['form_pw'])) {
            $GLOBALS['register_error'] = $Language->getText('account_register', 'err_invpasswd');
            return 0;
        }
        $ldap_name='';
    }

    //use sys_lang as default language for each user at register
    
    $lang_code = 
    $result=db_query("INSERT INTO user (user_name,user_pw,unix_pw,windows_pw,ldap_name,realname,register_purpose,email,add_date,"
		     . "status,confirm_hash,mail_siteupdates,mail_va,timezone,language_id) "
		     . "VALUES ('$HTTP_POST_VARS[form_loginname]','"
		     . md5($HTTP_POST_VARS['form_pw']) . "','"
		     . account_genunixpw($HTTP_POST_VARS['form_pw']) . "','"
		     . account_genwinpw($HTTP_POST_VARS['form_pw']) . "',"
		     . "'".$ldap_name."',"
		     . "'".$GLOBALS[form_realname]."',"
		     . "'".$GLOBALS[form_register_purpose]."',"
		     . "'".$GLOBALS[form_email]."',"
		     . time() . ","
		     . "'P','" // status pending
		     . $confirm_hash
		     . "',".($GLOBALS['form_mail_site']?"1":"0")
		     . ",".($GLOBALS['form_mail_va']?"1":"0")
		     . ",'".$GLOBALS['timezone']."'"
		     . ",".$Language->getText('conf','language_id').")");

    if (!$result) {
	exit_error($Language->getText('include_exit', 'error'),db_error());
	return 0;
    } else {
	return db_insertid($result);
    }
}


function display_account_form($register_error)	{
    global $HTTP_POST_VARS, $Language;

    if ($register_error) {
        print "<p><blink><b><span class=\"feedback\">$register_error</span></b></blink>";
    }
    $star = '<span class="highlight"><big>*</big></span>';
    $form_loginname = isset($HTTP_POST_VARS['form_loginname'])?$HTTP_POST_VARS['form_loginname']:'';
    $form_realname  = isset($HTTP_POST_VARS['form_realname'])?$HTTP_POST_VARS['form_realname']:'';
    $form_email     = isset($HTTP_POST_VARS['form_email'])?$HTTP_POST_VARS['form_email']:'';
   
    ?>
        
<form action="/account/register.php" method="post">
<p><?php print $Language->getText('account_register', 'login').'&nbsp;'.$star; ?>:<br>
<input type="text" name="form_loginname" value="<?php print stripslashes($form_loginname); ?>">
<?php print $Language->getText('account_register', 'login_directions'); ?>

<p><?php print $Language->getText('account_register', 'passwd').'&nbsp;'.$star; ?>:<br>
<input type="password" name="form_pw" value="">
<?php print $Language->getText('account_register', 'passwd_directions'); ?>

<p><?php print $Language->getText('account_register', 'passwd2').'&nbsp;'.$star; ?>:<br>
<input type="password" name="form_pw2" value="">
<?php print $Language->getText('account_register', 'passwd2_directions'); ?>

<P><?php print $Language->getText('account_register', 'realname').'&nbsp;'.$star; ?>:<br>
<INPUT size=40 type="text" name="form_realname" value="<?php print stripslashes($form_realname); ?>">
<?php print $Language->getText('account_register', 'realname_directions'); ?>

<P><?php print $Language->getText('account_register', 'email').'&nbsp;'.$star; ?>:<BR>
<INPUT size=40 type="text" name="form_email" value="<?php print stripslashes($form_email); ?>"><BR>
<?php print $Language->getText('account_register', 'email_directions'); ?>

<P><?php print $Language->getText('account_register', 'tz').'&nbsp;'.$star; ?>:<BR>
<?php 
    $timezone = (isset($HTTP_POST_VARS['timezone'])?stripslashes($HTTP_POST_VARS['timezone']):'None');
    echo html_get_timezone_popup ('timezone',$timezone); ?>
<P>

<P><INPUT type="checkbox" name="form_mail_site" value="1" checked>
<?php print $Language->getText('account_register', 'siteupdate'); ?>

<P><INPUT type="checkbox" name="form_mail_va" value="1">
<?php print $Language->getText('account_register', 'communitymail'); ?>

<P>
<?
if ($GLOBALS['sys_user_approval'] == 1) {
    print $Language->getText('account_register', 'purpose').'&nbsp;'.$star.":<br>";
    print $Language->getText('account_register', 'purpose_directions');
    echo '<textarea wrap="virtual" rows="5" cols="70" name="form_register_purpose"></textarea></p>';
}
?>

<p>
<?php print $Language->getText('account_register', 'mandatory', $star); ?>
</p>
<p><input type="submit" name="Register" value="<?php print $Language->getText('account_register', 'btn_register'); ?>">

</form>
<?
}


function display_initial_ldap_account_form() {
    global $HTTP_POST_VARS, $Language;

echo '        
<form action="/account/register.php" method="post">';
print "<p><strong>".$Language->getText('account_register', 'ldap_login')."</strong><br>\n";
echo '
<input type="text" name="ldap_name" value="'.stripslashes($HTTP_POST_VARS['ldap_name']).'">';
echo '<p><strong>'.$Language->getText('account_register', 'passwd').'<strong>:<br>
<input type="password" name="form_pw" value="">';


echo '
<p><input type="submit" name="Register1" value="'.$Language->getText('account_register', 'btn_next').'"> 

</form>';

}

function display_filled_ldap_account_form($register_error) {
    global $HTTP_POST_VARS, $Language;

    if ($GLOBALS['sys_ldap_auth_filter']) {
        $ldap_filter = $GLOBALS['sys_ldap_auth_filter'];
    } else {
        $ldap_filter = "uid=%ldap_name%";
    }
    $ldap_filter = str_replace("%ldap_name%", $HTTP_POST_VARS['ldap_name'], $ldap_filter);

    $ldap = new LDAP();
    $info = $ldap->search($GLOBALS['sys_ldap_dn'],$ldap_filter);
    if (!$info) {
        $feedback = $GLOBALS['sys_org_name'].' Directory: '.$ldap->getErrorMessage();
        print "<p><blink><b><span class=\"feedback\">$feedback</span></b></blink>";
        return;
    } else {

        $form_loginname = isset($HTTP_POST_VARS['form_loginname'])?$HTTP_POST_VARS['form_loginname']:'';
        $form_realname  = isset($HTTP_POST_VARS['form_realname'])?$HTTP_POST_VARS['form_realname']:'';
        $form_email     = isset($HTTP_POST_VARS['form_email'])?$HTTP_POST_VARS['form_email']:'';

        // The following script can set the following variables:
        // $form_loginname, $form_realname, $form_email, $timezone
        
        include($Language->getContent('account/register_ldap_get_data'));
        //$timezone=account_compute_timezone_from_co($info[0]['co'][0]);

    }

    if ($register_error) {
        print "<p><blink><b><span class=\"feedback\">$register_error</span></b></blink>";
    }
    $star = '<span class="highlight"><big>*</big></span>';
    ?>
<form action="/account/register.php" method="post">
<p><?php print $Language->getText('account_register', 'ldap_login'); ?>:<br>
<b><?php print stripslashes($HTTP_POST_VARS['ldap_name']); ?></b>
<input type="hidden" name="ldap_name" value="<?php print stripslashes($HTTP_POST_VARS['ldap_name']);?>">
<p><?php print $Language->getText('account_register', 'ldap_unix_login').' &nbsp; '.$star; ?>:<br>
<input type="text" name="form_loginname" value="<?php print stripslashes($form_loginname); ?>">
<br><?php print print $Language->getText('account_register', 'codex_login_help'); ?>

<p><?php print $Language->getText('account_register', 'passwd2').'&nbsp;'.$star; ?>:<br>
<input type="password" name="form_pw" value="">

<P><?php print $Language->getText('account_register', 'realname').'&nbsp;'.$star; ?>:<br>
<INPUT size=40 type="text" name="form_realname" value="<?php print stripslashes($form_realname); ?>">

<P><?php print $Language->getText('account_register', 'email').'&nbsp;'.$star; ?>:<BR>
<INPUT size=40 type="text" name="form_email" value="<?php print stripslashes($form_email); ?>"><BR>
<?php print print $Language->getText('account_register', 'ldap_email_help'); ?>

<p><?php print $Language->getText('account_register', 'tz').'&nbsp;'.$star; ?>:<BR>
<?php 
    $timezone = (isset($HTTP_POST_VARS['timezone'])?stripslashes($timezone):'None');
    echo html_get_timezone_popup ('timezone',$timezone); ?>
<P>

<P><INPUT type="checkbox" name="form_mail_site" value="1" checked>
<?php print $Language->getText('account_register', 'siteupdate'); ?>

<P><INPUT type="checkbox" name="form_mail_va" value="1">
<?php print $Language->getText('account_register', 'communitymail'); ?>

<P>
<?
    if ($GLOBALS['sys_user_approval'] == 1) {
    print $Language->getText('account_register', 'purpose').'&nbsp;'.$star.":<br>";
    print $Language->getText('account_register', 'purpose_directions');
        echo '<textarea wrap="virtual" rows="5" cols="70" name="form_register_purpose"></textarea></p>';
    }
?>
<p>
<?php print $Language->getText('account_register', 'mandatory', $star); ?>
</p>
<p><input type="submit" name="Register" value="<?php print $Language->getText('account_register', 'btn_register'); ?>">

</form>
<?
}

// ###### first check for valid login, if so, congratulate

if (isset($Register)) {

    $confirm_hash = substr(md5($session_hash . $HTTP_POST_VARS['form_pw'] . time()),0,16);

    if ($new_userid = register_valid($confirm_hash)) {
    
	$HTML->header(array('title'=>$Language->getText('account_register', 'title_confirm')));

	$user_name = user_getname($new_userid);
	if ($GLOBALS['sys_user_approval'] == 0) {
	    send_new_user_email($GLOBALS['form_email'], $confirm_hash);
	    echo '<p><b>'.$Language->getText('account_register', 'title_confirm').'</b>';
	    echo '<p>'.$Language->getText('account_register', 'msg_confirm', array($GLOBALS['sys_name'],$user_name));
	} else {
	    // Registration requires approval - send a mail to site admin and
	    // inform the user that approval is required
	    $href_approval = get_server_url().'/admin/approve_pending_users.php';

	    echo '<p><b>'.$Language->getText('account_register', 'title_approval').'</b>';
	    echo '<p>'.$Language->getText('account_register', 'msg_approval', array($GLOBALS['sys_name'],$user_name,$href_approval));

	    // Send a notification message to the Site administrator
	    list($host,$port) = explode(':',$GLOBALS['sys_default_domain']);
	    $hdrs = 'From: noreply@'.$host."\n";
	    $to = $GLOBALS['sys_email_admin'];
	    $subject = $Language->getText('account_register', 'mail_approval_subject', array($user_name));
	    $body = stripcslashes($Language->getText('account_register', 'mail_approval_body', array($GLOBALS['sys_name'], $user_name, $href_approval)));
	    mail($to,$subject,$body,$hdrs);
	    
	}
	$HTML->footer(array());
	exit;
    }
}

//
// not valid registration, or first time to page
//
$HTML->header(array('title'=>$Language->getText('account_register', 'title') ));

?>
    
<h2><?php print $Language->getText('account_register', 'title').' '.help_button('UserRegistration.html');?></h2>

<?php 

if ($GLOBALS['sys_auth_type'] == 'ldap') {
    // LDAP authentication
    if (!$HTTP_POST_VARS['ldap_name']) {
        display_initial_ldap_account_form();
    } else {
        // Check LDAP password
        $ldap = new LDAP();
        if (!$ldap->authenticate($HTTP_POST_VARS['ldap_name'],$HTTP_POST_VARS['form_pw'])) {
            // password is invalid or user does not exist
            $feedback = $GLOBALS['sys_org_name'].' Directory Authentication: '.$ldap->getErrorMessage();
            print "<p><blink><b><span class=\"feedback\">$feedback</span></b></blink>";
            display_initial_ldap_account_form();
        } else {
            display_filled_ldap_account_form($GLOBALS['register_error']);
        }
    }
 } else {
    $reg_err = isset($GLOBALS['register_error'])?$GLOBALS['register_error']:'';
    display_account_form($reg_err);
 }

$HTML->footer(array());

?>
