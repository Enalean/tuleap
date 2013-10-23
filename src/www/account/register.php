<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
//

header("Cache-Control: no-cache, no-store, must-revalidate");

require_once('pre.php');
require_once('proj_email.php');
require_once('account.php');
require_once('timezones.php');

require_once('common/mail/Mail.class.php');
require_once('common/include/HTTPRequest.class.php');
$GLOBALS['HTML']->includeCalendarScripts();
$request =& HTTPRequest:: instance();
$page = $request->get('page');
// ###### function register_valid()
// ###### checks for valid register from form post
if($page == "admin_creation"){
   session_require(array('group'=>'1','admin_flags'=>'A'));
}

function register_valid($confirm_hash)	{
    global $Language;

    $request =& HTTPRequest::instance();


    $vLoginName = new Valid_UserNameFormat('form_loginname');
    $vLoginName->required();
    if (!$request->valid($vLoginName)) {
        return 0;
    }

    $vRealName = new Valid_RealNameFormat('form_realname');
    $vRealName->required();
    if (!$request->valid($vRealName)) {
        $GLOBALS['Response']->addFeedback('error', $Language->getText('account_register', 'err_realname'));
        return 0;
    }

    if (!$request->existAndNonEmpty('form_pw')) {
	$GLOBALS['Response']->addFeedback('error', $Language->getText('account_register', 'err_nopasswd'));
	return 0;
    }
    $tz = $request->get('timezone');
    if (!is_valid_timezone($tz) ||
        $tz == 'None') {
	$GLOBALS['Response']->addFeedback('error', $Language->getText('account_register', 'err_notz'));
	return 0;
    }
    if (!$request->existAndNonEmpty('form_register_purpose') && ($GLOBALS['sys_user_approval'] && $request->get('page')!="admin_creation")) {
	$GLOBALS['Response']->addFeedback('error', $Language->getText('account_register', 'err_nopurpose'));
	return 0;
    }
    if (!validate_email($request->get('form_email'))) {
	$GLOBALS['Response']->addFeedback('error', $Language->getText('account_register', 'err_email'));
	return 0;
    }

    if ($request->get('page')!="admin_creation" && $request->get('form_pw') != $request->get('form_pw2')) {
        $GLOBALS['Response']->addFeedback('error', $Language->getText('account_register', 'err_passwd'));
        return 0;
    }
    if (!account_pwvalid($request->get('form_pw'), $errors)) {
        foreach($errors as $e) {
            $GLOBALS['Response']->addFeedback('error', $e);
        }
        return 0;
    }
    $expiry_date = 0;
    if ($request->exist('form_expiry') && $request->get('form_expiry')!='' && !ereg("[0-9]{4}-[0-9]{1,2}-[0-9]{1,2}", $request->get('form_expiry'))) {
        $GLOBALS['Response']->addFeedback('error',$GLOBALS['Language']->getText('account_register', 'data_not_parsed'));
        return 0;
    }
    $vDate = new Valid_String();
    $vDate->required();
    if ($request->exist('form_expiry') && $vDate->validate($request->get('form_expiry'))) {
        $date_list = split("-", $request->get('form_expiry'), 3);
        $unix_expiry_time = mktime(0, 0, 0, $date_list[1], $date_list[2], $date_list[0]);
        $expiry_date = $unix_expiry_time;

    }

    $status = 'P';
    if($request->get('page')== "admin_creation"){
        if($request->get('form_restricted')){
           $status = 'R';
        } else{
           $status = 'A';
        }
    }

    //use sys_lang as default language for each user at register
    $res = account_create($request->get('form_loginname')
                          ,$request->get('form_pw')
                          ,''
                          ,$request->get('form_realname')
                          ,$request->get('form_register_purpose')
                          ,$request->get('form_email')
                          ,$status
                          ,$confirm_hash
                          ,$request->get('form_mail_site')
                          ,$request->get('form_mail_va')
                          ,$tz
                          ,UserManager::instance()->getCurrentUser()->getLocale()
                          ,'A',$expiry_date);


    return $res;
}


function display_account_form($register_error)	{
    global $Language;

    $request =& HTTPRequest::instance();
    $purifier =& Codendi_HTMLPurifier::instance();

    $page = $request->get('page');

    if ($register_error) {
        print "<p><blink><b><span class=\"feedback\">$register_error</span></b></blink>";
    }
    $star = '<span class="highlight"><big>*</big></span>';
    $form_loginname = $request->exist('form_loginname')?$purifier->purify($request->get('form_loginname')):'';
    $form_realname  = $request->exist('form_realname')?$purifier->purify($request->get('form_realname')):'';
    $form_email     = $request->exist('form_email')?$purifier->purify($request->get('form_email')):'';
    $form_expiry     = $request->exist('form_expiry')?$purifier->purify($request->get('form_expiry')):'';
    if($request->exist('timezone') && is_valid_timezone($request->get('timezone'))) {
        $timezone = $request->get('timezone');
    } else {
        $timezone = 'None';
    }

    $form_register_purpose = $request->exist('form_register_purpose')?$purifier->purify($request->get('form_register_purpose')):'';

    ?>
<?php if($page == "admin_creation"){ ?>
    <form action="/admin/register_admin.php?page=admin_creation" name="new_user" method="post">
<?php } else { ?>
    <form action="/account/register.php" method="post">
<?php }?>
<p><?php print $Language->getText('account_register', 'login').'&nbsp;'.$star; ?>:<br>
<input type="text" name="form_loginname" value="<?php echo $form_loginname; ?>" required="required">
<?php print $Language->getText('account_register', 'login_directions'); ?>
<?php user_display_choose_password($page); ?>
<P><?php print $Language->getText('account_register', 'realname').'&nbsp;'.$star; ?>:<br>
<INPUT size=40 type="text" name="form_realname" value="<?php echo $form_realname; ?>" required="required">
<?php print $Language->getText('account_register', 'realname_directions'); ?>
<P><?php print $Language->getText('account_register', 'email').'&nbsp;'.$star; ?>:<BR>
<INPUT size=40 type="text" name="form_email" value="<?php echo $form_email; ?>" required="required"><BR>
<?php print $Language->getText('account_register', 'email_directions'); ?>
<?php if($page == "admin_creation"){ ?>
    <P><?php print $Language->getText('account_register', 'expiry_date')?>:<BR>
    <?php echo $GLOBALS['HTML']->getDatePicker("form_expiry", "form_expiry", $form_expiry); ?>
    <BR>
    <?php print $Language->getText('account_register', 'expiry_date_directions'); ?>
<?php } ?>
<P><?php print $Language->getText('account_register', 'tz').'&nbsp;'.$star; ?>:<BR>
<?php
    echo html_get_timezone_popup ('timezone',$timezone); ?>
<p>
<label class="checkbox">
<?php
if($request->isPost() && $request->exist('Register') && !($request->get('form_mail_site')==1)){

	echo '<INPUT type="checkbox" name="form_mail_site" value="1" > ';

}else{

	echo '<INPUT type="checkbox" name="form_mail_site" value="1" checked> ';

}
print $Language->getText('account_register', 'siteupdate') .'</label>';

echo '<label class="checkbox">';
if($request->isPost() && $request->exist('Register') && ($request->get('form_mail_va')==1)){

	echo '<INPUT type="checkbox" name="form_mail_va" value="1" checked> ';

}else{

	echo '<INPUT type="checkbox" name="form_mail_va" value="1" > ';

}
print $Language->getText('account_register', 'communitymail') . '</label>';

?>

<P>
<?
if ($GLOBALS['sys_user_approval'] == 1 || $page == "admin_creation") {
    print $Language->getText('account_register', 'purpose');
    if($page != "admin_creation") {
        print '&nbsp;'.$star;
        print ":<br>";
        print $Language->getText('account_register', 'purpose_directions');
    } else{
        print ":<br>";
        print $Language->getText('account_register', 'purpose_directions_admin');
    }
    echo '<textarea wrap="virtual" rows="5" cols="70" style="width:auto;" name="form_register_purpose">'.$form_register_purpose.'</textarea></p>';
}
?>

<p>
<?php print $Language->getText('account_register', 'mandatory', $star); ?>
</p>
<?php

if ($page == "admin_creation" && $GLOBALS['sys_allow_restricted_users'] == 1) {

    echo '<label class="checkbox">';
    if($request->isPost() && $request->exist('Register') && !($request->get('form_restricted')==1)){
        echo '<INPUT type="checkbox" name="form_restricted" value="1" > ';
    } else {
        echo '<INPUT type="checkbox" name="form_restricted" value="1" checked> ';
    }
    print $Language->getText('account_register', 'restricted_user') . '</label>';
}

if ($page == "admin_creation") {
    echo '<label class="checkbox">';
    if ($request->isPost() && $request->exist('Register') && ($request->get('form_send_email')==1)){
        echo '<INPUT type="checkbox" name="form_send_email" value="1" checked> ';
    } else {
        echo '<INPUT type="checkbox" name="form_send_email" value="1" > ';
    }
    print $Language->getText('account_register', 'send_email') . '</label>';
}
?>

<P>
<p><input type="submit" name="Register" class="btn btn-primary" value="<?php if($page != "admin_creation") print $Language->getText('account_register', 'btn_register');
else print $Language->getText('account_register', 'btn_activate');?>">
<?php
if($page !== "admin_creation") {
    include $Language->getContent('account/user_legal');
}

?>
</form>
<?
}

// ###### first check for valid login, if so, congratulate

$request =& HTTPRequest::instance();
$hp =& Codendi_HTMLPurifier::instance();
if ($request->isPost() && $request->exist('Register')) {


    $page = $request->get('page');

    $confirm_hash = substr(md5($GLOBALS['session_hash'] . $request->get('form_pw') . time()),0,16);

    if ($new_userid = register_valid($confirm_hash)) {

        $user_name = user_getname($new_userid);
        $content = '';
        $admin_creation = false;
        $password='';
        if($page == 'admin_creation'){
            $admin_creation = true;
            $password = $request->get('form_pw');
            $login = $request->get('form_loginname');
            if($request->get('form_send_email')){
                //send an email to the user with th login and password
                $from = $GLOBALS['sys_noreply'];
                $to = $request->get('form_email');
                $subject = $Language->getText('account_register', 'welcome_email_title', $GLOBALS['sys_name']);

                include($Language->getContent('account/new_account_email'));

                $mail = new Mail();
                $mail->setSubject($subject);
                $mail->setFrom($from);
                $mail->setTo($to,true); // Don't invalidate address
                $mail->setBody($body);
                if (!$mail->send()) {
                    $GLOBALS['feedback'] .= "<p>".$GLOBALS['Language']->getText('global', 'mail_failed', array($GLOBALS['sys_email_admin']))."</p>";
                }
            }
        }
        if ($GLOBALS['sys_user_approval'] == 0 || $admin_creation) {
            if(!$admin_creation) {
                if (!send_new_user_email($request->get('form_email'), $confirm_hash, $user_name)) {
                    $GLOBALS['feedback'] .= "<p>".$GLOBALS['Language']->getText('global', 'mail_failed', array($GLOBALS['sys_email_admin']))."</p>";
                }
            } else {

            }
            $content .= '<p><b>'.$Language->getText('account_register', 'title_confirm').'</b>';
            if($admin_creation){
                    if($request->get('form_send_email')){
                        $content .= '<p>'.$Language->getText('account_register', 'msg_confirm_admin', array($request->get('form_realname'),$GLOBALS['sys_name'], $request->get('form_loginname'), $request->get('form_pw')));
                    }else {
                        $content .= '<p>'.$Language->getText('account_register', 'msg_confirm_no_email', array($request->get('form_realname'),$GLOBALS['sys_name'], $request->get('form_loginname'), $request->get('form_pw')));
                    }
            }else{
                $content .= '<p>'.$Language->getText('account_register', 'msg_confirm', array($GLOBALS['sys_name'],$user_name));
            }

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
            $mail->setTo($to,true); // Don't invalidate address
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

if($page != 'admin_creation'){
   require_once('common/event/EventManager.class.php');
    $em =& EventManager::instance();
    $em->processEvent('before_register', array());
}


//
// not valid registration, or first time to page
//
$HTML->includeJavascriptFile('/scripts/check_pw.js.php');
$HTML->header(array('title'=>$Language->getText('account_register', 'title') ));
?>


<h2><?php print $Language->getText('account_register', 'title').' ';
if($page != 'admin_creation'){
    print help_button('citizen.html#user-registration');
}
?></h2>
<?php

$reg_err = isset($GLOBALS['register_error'])?$GLOBALS['register_error']:'';
display_account_form($reg_err);

$HTML->footer(array());

?>
