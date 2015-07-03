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

function register_valid($confirm_hash, array &$errors)	{
    global $Language;

    $request =& HTTPRequest::instance();

    $vLoginName = new Valid_UserNameFormat('form_loginname');
    $vLoginName->required();
    if (!$request->valid($vLoginName)) {
        $errors['form_loginname'] = $Language->getText('account_register', 'err_exist');
        return 0;
    }

    $vRealName = new Valid_RealNameFormat('form_realname');
    $vRealName->required();
    if (!$request->valid($vRealName)) {
        $GLOBALS['Response']->addFeedback('error', $Language->getText('account_register', 'err_realname'));
        $errors['form_realname'] = $Language->getText('account_register', 'err_realname');
        return 0;
    }

    if (!$request->existAndNonEmpty('form_pw')) {
        $GLOBALS['Response']->addFeedback('error', $Language->getText('account_register', 'err_nopasswd'));
        $errors['form_pw'] = $Language->getText('account_register', 'err_nopasswd');
        return 0;
    }
    $tz = $request->get('timezone');
    if (!is_valid_timezone($tz)) {
        $GLOBALS['Response']->addFeedback('error', $Language->getText('account_register', 'err_notz'));
        $errors['timezone'] = $Language->getText('account_register', 'err_notz');
        return 0;
    }
    if (!$request->existAndNonEmpty('form_register_purpose') && ($GLOBALS['sys_user_approval'] && $request->get('page')!="admin_creation")) {
        $GLOBALS['Response']->addFeedback('error', $Language->getText('account_register', 'err_nopurpose'));
        $errors['form_register_purpose'] = $Language->getText('account_register', 'err_nopurpose');
        return 0;
    }
    if (!validate_email($request->get('form_email'))) {
        $GLOBALS['Response']->addFeedback('error', $Language->getText('account_register', 'err_email'));
        $errors['form_email'] = $Language->getText('account_register', 'err_email');
        return 0;
    }

    if ($request->get('page')!="admin_creation" && $request->get('form_pw') != $request->get('form_pw2')) {
        $GLOBALS['Response']->addFeedback('error', $Language->getText('account_register', 'err_passwd'));
        $errors['form_pw'] = $Language->getText('account_register', 'err_passwd');
        return 0;
    }
    if (!account_pwvalid($request->get('form_pw'), $errors)) {
        foreach($errors as $e) {
            $GLOBALS['Response']->addFeedback('error', $e);
        }
        $errors['form_pw'] = 'Error';
        return 0;
    }
    $expiry_date = 0;
    if ($request->exist('form_expiry') && $request->get('form_expiry')!='' && !ereg("[0-9]{4}-[0-9]{1,2}-[0-9]{1,2}", $request->get('form_expiry'))) {
        $GLOBALS['Response']->addFeedback('error',$GLOBALS['Language']->getText('account_register', 'data_not_parsed'));
        $errors['form_expiry'] = $Language->getText('account_register', 'data_not_parsed');
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

/**
 * Function to get errors with its key
 * to display errors for each element
**/
function getFieldError($field_key, array $errors) {
    if(isset($errors[$field_key])) {
        return $errors[$field_key];
    }
    return null;
}


function display_account_form($register_error, array $errors)	{
    global $Language;

    $request =& HTTPRequest::instance();
    $purifier =& Codendi_HTMLPurifier::instance();

    $page = $request->get('page');
    if ($register_error) {
        print "<p><blink><b><span class=\"feedback\">$register_error</span></b></blink>";
    }
    $star = '<span class="highlight"><big>*</big></span>';
    $form_loginname         = $request->exist('form_loginname')?$purifier->purify($request->get('form_loginname')):'';
    $form_loginname_error   = getFieldError('form_loginname', $errors);

    $form_realname          = $request->exist('form_realname')?$purifier->purify($request->get('form_realname')):'';
    $form_realname_error    = getFieldError('form_realname', $errors);

    $form_email             = $request->exist('form_email')?$purifier->purify($request->get('form_email')):'';
    $form_email_error       = getFieldError('form_email', $errors);

    $form_pw                = '';
    $form_pw_error          = getFieldError('form_pw', $errors);

    $form_expiry            = $request->exist('form_expiry')?$purifier->purify($request->get('form_expiry')):'';
    $form_expiry_error      = getFieldError('form_expiry', $errors);

    $form_mail_site         = ! $request->exist('form_mail_site') || $request->get('form_mail_site') == 1;
    $form_mail_site_error   = getFieldError('form_mail_site', $errors);

    $form_restricted        = ForgeConfig::areRestrictedUsersAllowed() && (! $request->exist('form_restricted') || $request->get('form_restricted') == 1);
    $form_restricted_error  = getFieldError('form_restricted', $errors);

    $form_send_email        = $request->get('form_send_email') == 1;
    $form_send_email_error  = getFieldError('form_send_email', $errors);

    if($request->exist('timezone') && is_valid_timezone($request->get('timezone'))) {
        $timezone = $request->get('timezone');
    } else {
        $timezone = false;
    }
    $timezone_error = getFieldError('timezone', $errors);

    $form_register_purpose          = $request->exist('form_register_purpose')?$purifier->purify($request->get('form_register_purpose')):'';
    $form_register_purpose_error    = getFieldError('form_register_purpose', $errors);

    if ($page == "admin_creation") {
        $prefill = new Account_RegisterAdminPrefillValuesPresenter(
            new Account_RegisterField($form_loginname, $form_loginname_error),
            new Account_RegisterField($form_email, $form_email_error),
            new Account_RegisterField($form_pw, $form_pw_error),
            new Account_RegisterField($form_realname, $form_realname_error),
            new Account_RegisterField($form_register_purpose, $form_register_purpose_error),
            new Account_RegisterField($form_mail_site, $form_mail_site_error),
            new Account_RegisterField($timezone, $timezone_error),
            new Account_RegisterField($form_restricted, $form_restricted_error),
            new Account_RegisterField($form_send_email, $form_send_email_error)
        );
        $presenter = new Account_RegisterByAdminPresenter($prefill);
        $template = 'register-admin';
    } else {
        $prefill = new Account_RegisterPrefillValuesPresenter(
            new Account_RegisterField($form_loginname, $form_loginname_error),
            new Account_RegisterField($form_email, $form_email_error),
            new Account_RegisterField($form_pw, $form_pw_error),
            new Account_RegisterField($form_realname, $form_realname_error),
            new Account_RegisterField($form_register_purpose, $form_register_purpose_error),
            new Account_RegisterField($form_mail_site, $form_mail_site_error),
            new Account_RegisterField($timezone, $timezone_error)
        );
        $presenter = new Account_RegisterByUserPresenter($prefill);
        $template = 'register-user';
    }
    $renderer = TemplateRendererFactory::build()->getRenderer(ForgeConfig::get('codendi_dir') .'/src/templates/account/');
    $renderer->renderToPage($template, $presenter);
}

// ###### first check for valid login, if so, congratulate

$request =& HTTPRequest::instance();
$hp =& Codendi_HTMLPurifier::instance();
$errors = array();
if ($request->isPost() && $request->exist('Register')) {

    $page = $request->get('page');

    $confirm_hash = substr(md5($GLOBALS['session_hash'] . $request->get('form_pw') . time()),0,16);

    if ($new_userid = register_valid($confirm_hash, $errors)) {

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
            // Registration requires approval
            // inform the user that approval is required
            $href_approval = get_server_url().'/admin/approve_pending_users.php?page=pending';

            $content .= '<p><b>'.$Language->getText('account_register', 'title_approval').'</b>';
            $content .= '<p>'.$Language->getText('account_register', 'msg_approval', array($GLOBALS['sys_name'],$user_name,$href_approval));
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

$body_class = array('register-page');
if($page == 'admin_creation'){
    $body_class[] = 'admin_register';
}

//
// not valid registration, or first time to page
//
$HTML->includeJavascriptFile('/scripts/check_pw.js.php');
$HTML->includeFooterJavascriptFile('/scripts/mailcheck/mailcheck.min.js');
$HTML->includeFooterJavascriptFile('/scripts/tuleap/mailchecker.js');
$HTML->header(array('title'=>$Language->getText('account_register', 'title'), 'body_class' => $body_class));
?>

<div id="register-background">

<?php
$reg_err = isset($GLOBALS['register_error'])?$GLOBALS['register_error']:'';
display_account_form($reg_err, $errors);
?>

</div>

<?php
$HTML->footer(array());
?>
