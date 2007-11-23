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
    global $Language;

    $request =& HTTPRequest::instance();
    
    if (!$request->exist('form_loginname')) {
	$GLOBALS['Response']->addFeedback('error', $Language->getText('account_register', 'err_nouser'));
	return 0;
    }
    if (!$request->exist('form_pw')) {
	$GLOBALS['Response']->addFeedback('error', $Language->getText('account_register', 'err_nopasswd'));
	return 0;
    }
    $tz = $request->get('timezone');
    if (!is_valid_timezone($tz) ||
        $tz == 'None') {
	$GLOBALS['Response']->addFeedback('error', $Language->getText('account_register', 'err_notz'));
	return 0;
    }
    if (!$request->exist('form_register_purpose') && $GLOBALS['sys_user_approval']) {
	$GLOBALS['Response']->addFeedback('error', $Language->getText('account_register', 'err_nopurpose'));
	return 0;
    }
    if (!validate_email($request->get('form_email'))) {
	$GLOBALS['Response']->addFeedback('error', $Language->getText('account_register', 'err_email'));
	return 0;
    }
    if (!account_namevalid($request->get('form_loginname'))) {
	$GLOBALS['Response']->addFeedback('error', $Language->getText('account_register', 'err_name'));
	return 0;
    }
    if (db_numrows(db_query("SELECT user_id FROM user WHERE "
			    . "user_name LIKE '".db_es($request->get('form_loginname'))."'")) > 0) {
	$GLOBALS['Response']->addFeedback('error', $Language->getText('account_register', 'err_exist'));
	return 0;
    }
    if ($request->get('form_pw') != $request->get('form_pw2')) {
        $GLOBALS['Response']->addFeedback('error', $Language->getText('account_register', 'err_passwd'));
        return 0;
    }
    if (!account_pwvalid($request->get('form_pw'), $errors)) {
        foreach($errors as $e) {
            $GLOBALS['Response']->addFeedback('error', $e);
        }
        return 0;
    }

    // Escape HTML injections in some parameters
    // Note: this is not the right way to do, we should record them as is and
    // escape on display but due to legacy, it's much more secure to escape now.
    $purifier =& CodeX_HTMLPurifier::instance();

    // Escape realname
    $realname = $purifier->purify($request->get('form_realname'), CODEX_PURIFIER_STRIP_HTML);
    // Escape register purpose
    $register_purpose = $purifier->purify($request->get('form_register_purpose'));

    //use sys_lang as default language for each user at register
    $res = account_create($request->get('form_loginname')
                          ,$request->get('form_pw')
                          ,''
                          ,$realname
                          ,$register_purpose
                          ,$request->get('form_email')
                          ,'P'
                          ,$confirm_hash
                          ,$request->get('form_mail_site')
                          ,$request->get('form_mail_va')
                          ,$tz
                          ,$Language->getText('conf','language_id')
                          ,account_nextuid()
                          ,'A');
    return $res;
}


function display_account_form($register_error)	{
    global $Language;

    $request =& HTTPRequest::instance();
    $purifier =& CodeX_HTMLPurifier::instance();

    if ($register_error) {
        print "<p><blink><b><span class=\"feedback\">$register_error</span></b></blink>";
    }
    $star = '<span class="highlight"><big>*</big></span>';
    $form_loginname = $request->exist('form_loginname')?$purifier->purify($request->get('form_loginname')):'';
    $form_realname  = $request->exist('form_realname')?$purifier->purify($request->get('form_realname')):'';
    $form_email     = $request->exist('form_email')?$purifier->purify($request->get('form_email')):'';
    if($request->exist('timezone') && is_valid_timezone($request->get('timezone'))) {
        $timezone = $request->get('timezone');
    } else {
        $timezone = 'None';
    }

    $form_register_purpose = $request->exist('form_register_purpose')?$purifier->purify($request->get('form_register_purpose')):'';

    ?>
        
<form action="/account/register.php" method="post">
<p><?php print $Language->getText('account_register', 'login').'&nbsp;'.$star; ?>:<br>
<input type="text" name="form_loginname" value="<?php echo $form_loginname; ?>">
<?php print $Language->getText('account_register', 'login_directions'); ?>
<?php user_display_choose_password(); ?>
<P><?php print $Language->getText('account_register', 'realname').'&nbsp;'.$star; ?>:<br>
<INPUT size=40 type="text" name="form_realname" value="<?php echo $form_realname; ?>">
<?php print $Language->getText('account_register', 'realname_directions'); ?>

<P><?php print $Language->getText('account_register', 'email').'&nbsp;'.$star; ?>:<BR>
<INPUT size=40 type="text" name="form_email" value="<?php echo $form_email; ?>"><BR>
<?php print $Language->getText('account_register', 'email_directions'); ?>

<P><?php print $Language->getText('account_register', 'tz').'&nbsp;'.$star; ?>:<BR>
<?php 
    echo html_get_timezone_popup ('timezone',$timezone); ?>
<P>

<P><INPUT type="checkbox" name="form_mail_site" value="1" checked="checked">
<?php print $Language->getText('account_register', 'siteupdate'); ?>

<P><INPUT type="checkbox" name="form_mail_va" value="1">
<?php print $Language->getText('account_register', 'communitymail'); ?>

<P>
<?
if ($GLOBALS['sys_user_approval'] == 1) {
    print $Language->getText('account_register', 'purpose').'&nbsp;'.$star.":<br>";
    print $Language->getText('account_register', 'purpose_directions');
    echo '<textarea wrap="virtual" rows="5" cols="70" name="form_register_purpose">'.$form_register_purpose.'</textarea></p>';
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

$request =& HTTPRequest::instance();

if ($request->isPost() && $request->exist('Register')) {

    $confirm_hash = substr(md5($GLOBALS['session_hash'] . $request->get('form_pw') . time()),0,16);

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
