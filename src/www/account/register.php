<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// 

require_once('pre.php');
require_once('proj_email.php');
require_once('account.php');
require_once('timezones.php');

require_once('common/mail/Mail.class.php');
require_once('common/include/HTTPRequest.class.php');

$Language->loadLanguageMsg('account/account');

// ###### function register_valid()
// ###### checks for valid register from form post

function register_valid($confirm_hash)	{
    global $HTTP_POST_VARS, $Language;

    $request =& HTTPRequest:: instance();
    
    if (!$HTTP_POST_VARS['form_loginname']) {
	$GLOBALS['Response']->addFeedback('error', $Language->getText('account_register', 'err_nouser'));
	return 0;
    }
    if (!$HTTP_POST_VARS['form_pw']) {
	$GLOBALS['Response']->addFeedback('error', $Language->getText('account_register', 'err_nopasswd'));
	return 0;
    }
    if ($HTTP_POST_VARS['timezone'] == 'None') {
	$GLOBALS['Response']->addFeedback('error', $Language->getText('account_register', 'err_notz'));
	return 0;
    }
    if (!$HTTP_POST_VARS['form_register_purpose'] && $GLOBALS['sys_user_approval']) {
	$GLOBALS['Response']->addFeedback('error', $Language->getText('account_register', 'err_nopurpose'));
	return 0;
    }
    if (!validate_email($HTTP_POST_VARS['form_email'])) {
	$GLOBALS['Response']->addFeedback('error', $Language->getText('account_register', 'err_email'));
	return 0;
    }
    if (!account_namevalid($HTTP_POST_VARS['form_loginname'])) {
	$GLOBALS['Response']->addFeedback('error', $Language->getText('account_register', 'err_name'));
	return 0;
    }
    if (db_numrows(db_query("SELECT user_id FROM user WHERE "
			    . "user_name LIKE '$HTTP_POST_VARS[form_loginname]'")) > 0) {
	$GLOBALS['Response']->addFeedback('error', $Language->getText('account_register', 'err_exist'));
	return 0;
    }
    if ($HTTP_POST_VARS['form_pw'] != $HTTP_POST_VARS['form_pw2']) {
        $GLOBALS['Response']->addFeedback('error', $Language->getText('account_register', 'err_passwd'));
        return 0;
    }
    if (!account_pwvalid($HTTP_POST_VARS['form_pw'], $errors)) {
        foreach($errors as $e) {
            $GLOBALS['Response']->addFeedback('error', $e);
        }
        return 0;
    }

    //use sys_lang as default language for each user at register
    $res = account_create($HTTP_POST_VARS['form_loginname']
                          ,$HTTP_POST_VARS['form_pw']
                          ,''
                          ,$request->get('form_realname')
                          ,$GLOBALS['form_register_purpose']
                          ,$request->get('form_email')
                          ,'P'
                          ,$confirm_hash
                          ,$GLOBALS['form_mail_site']
                          ,$GLOBALS['form_mail_va']
                          ,$GLOBALS['timezone']
                          ,$Language->getText('conf','language_id')
                          ,account_nextuid()
                          ,'A');
    return $res;
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
<?php user_display_choose_password(); ?>
<P><?php print $Language->getText('account_register', 'realname').'&nbsp;'.$star; ?>:<br>
<INPUT size=40 type="text" name="form_realname" value="<?php print htmlentities($form_realname, ENT_QUOTES); ?>">
<?php print $Language->getText('account_register', 'realname_directions'); ?>

<P><?php print $Language->getText('account_register', 'email').'&nbsp;'.$star; ?>:<BR>
<INPUT size=40 type="text" name="form_email" value="<?php print htmlentities($form_email, ENT_QUOTES); ?>"><BR>
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

// ###### first check for valid login, if so, congratulate

if (isset($Register)) {

    $request =& HTTPRequest:: instance();
    
    $confirm_hash = substr(md5($session_hash . $HTTP_POST_VARS['form_pw'] . time()),0,16);

    if ($new_userid = register_valid($confirm_hash)) {
    
        $user_name = user_getname($new_userid);
        $content = '';
        if ($GLOBALS['sys_user_approval'] == 0) {
            if (!send_new_user_email($request->get('form_email'), $confirm_hash)) {
                $GLOBALS['feedback'] .= "<p>".$GLOBALS['Language']->getText('global', 'mail_failed', array($GLOBALS['sys_email_admin']))."</p>";
            }
            $content .= '<p><b>'.$Language->getText('account_register', 'title_confirm').'</b>';
            $content .= '<p>'.$Language->getText('account_register', 'msg_confirm', array($GLOBALS['sys_name'],$user_name));
        } else {
            // Registration requires approval - send a mail to site admin and
            // inform the user that approval is required
            $href_approval = get_server_url().'/admin/approve_pending_users.php?page=pending';
    
            $content .= '<p><b>'.$Language->getText('account_register', 'title_approval').'</b>';
            $content .= '<p>'.$Language->getText('account_register', 'msg_approval', array($GLOBALS['sys_name'],$user_name,$href_approval));
    
            // Send a notification message to the Site administrator
            $from = $GLOBALS['sys_noreply'];
            $to = $GLOBALS['sys_email_admin'];
            $subject = $Language->getText('account_register', 'mail_approval_subject', array($user_name));
            $body = stripcslashes($Language->getText('account_register', 'mail_approval_body', array($GLOBALS['sys_name'], $user_name, $href_approval)));
            
            $mail = new Mail();
            $mail->setSubject($subject);
            $mail->setFrom($from);
            $mail->setTo($to);
            $mail->setBody($body);
            if (!$mail->send()) {
                $GLOBALS['feedback'] .= "<p>".$GLOBALS['Language']->getText('global', 'mail_failed', array($GLOBALS['sys_email_admin']))."</p>";
            }
            
        }
        site_header(array('title'=>$Language->getText('account_register', 'title_confirm')));
        echo $content;
        site_footer(array());
        exit;
    }
}

require_once('common/event/EventManager.class.php');
$em =& EventManager::instance();
$em->processEvent('before_register', array());

//
// not valid registration, or first time to page
//
$HTML->includeJavascriptFile('/scripts/prototype/prototype.js');
$HTML->includeJavascriptFile('/scripts/check_pw.js.php');
$HTML->header(array('title'=>$Language->getText('account_register', 'title') ));

?>
    
<h2><?php print $Language->getText('account_register', 'title').' '.help_button('UserRegistration.html');?></h2>

<?php 

$reg_err = isset($GLOBALS['register_error'])?$GLOBALS['register_error']:'';
display_account_form($reg_err);

$HTML->footer(array());

?>
