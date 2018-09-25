<?php
//
// Copyright (c) Enalean, 2015-2018. All Rights Reserved.
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
//
//
// adduser.php - All the forms and functions to manage unix users
//

// Add user to an existing project
function account_add_user_to_group ($group_id, &$user_unix_name)
{
    $um = UserManager::instance();
    $user = $um->findUser($user_unix_name);
    if ($user) {
        $send_notifications = true;
        $check_user_status  = true;
        return account_add_user_obj_to_group($group_id, $user, $check_user_status, $send_notifications);
    } else {
        //user doesn't exist
        $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('include_account', 'user_not_exist'));
        return false;
    }
}

/**
 * Add a new user into a given project
 *
 * @param Integer $group_id Project id
 * @param PFUser $user User to add
 * @param bool $check_user_status
 * @return bool
 */

function account_add_user_obj_to_group ($group_id, PFUser $user, $check_user_status, $send_notifications)
{
    //user was found but if it's a pending account adding
    //is not allowed
    if ($check_user_status && !$user->isActive() && !$user->isRestricted()) {
        $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('include_account', 'account_notactive', $user->getUserName()));
        return false;
    }

        //if not already a member, add it
    $res_member = db_query("SELECT user_id FROM user_group WHERE user_id=".$user->getId()." AND group_id='".db_ei($group_id)."'");
    if (db_numrows($res_member) < 1) {
        //not already a member
        db_query("INSERT INTO user_group (user_id,group_id) VALUES (".db_ei($user->getId()).",".db_ei($group_id).")");


        //if no unix account, give them a unix_uid
        if ($user->getUnixStatus() == 'N' || !$user->getUnixUid()) {
            $user->setUnixStatus('A');
            $um = UserManager::instance();
            $um->assignNextUnixUid($user);
            $um->updateDb($user);
        }

        // Raise an event
        $em = EventManager::instance();
        $em->processEvent('project_admin_add_user', array(
                'group_id'       => $group_id,
                'user_id'        => $user->getId(),
                'user_unix_name' => $user->getUserName(),
        ));

        $GLOBALS['Response']->addFeedback('info', $GLOBALS['Language']->getText('include_account', 'user_added'));
        if ($send_notifications) {
            account_send_add_user_to_group_email($group_id, $user->getId());
        }
        group_add_history('added_user', $user->getUserName(), $group_id, array($user->getUserName()));

        return true;
    } else {
        $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('include_account', 'user_already_member'));
    }
    return false;
}

/**
 * Warn user she has been added to a project
 *
 * @param Integer $group_id id of the project
 * @param Integer $user_id  id of the user
 *
 * @return Boolean true if the mail was sent false otherwise
 */
function account_send_add_user_to_group_email($group_id,$user_id) {
  global $Language;
    $base_url = get_server_url();

    // Get email address
    $res = db_query("SELECT email FROM user WHERE user_id=".db_ei($user_id));
    if (db_numrows($res) > 0) {
        $email_address = db_result($res,0,'email');
        if (!$email_address) {
            $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('global', 'no_mail_for_account'));
            return false;
        }
        $res2 = db_query("SELECT group_name,unix_group_name FROM groups WHERE group_id=".db_ei($group_id));
        if (db_numrows($res2) > 0) {
            $group_name = db_result($res2,0,'group_name');
            $unix_group_name = db_result($res2,0,'unix_group_name');
            // $message is defined in the content file
            include($Language->getContent('include/add_user_to_group_email'));

            $mail = new Codendi_Mail();
            $mail->setTo($email_address);
            $mail->setFrom($GLOBALS['sys_noreply']);
            $mail->setSubject($Language->getText('include_account','welcome',array($GLOBALS['sys_name'],$group_name)));
            $mail->setBodyText($message);
            $result = $mail->send();
            if (!$result) {
                $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('global', 'mail_failed', array($GLOBALS['sys_email_admin'])), CODENDI_PURIFIER_DISABLED);
            }
            return $result;
        }
    }
    return false;
}

// Generate a valid Unix login name from the email address.
function account_make_login_from_email($email) {
    $pattern = "/^(.*)@.*$/";
    $replacement = "$1";
    $name=preg_replace($pattern, $replacement, $email);
    $name = substr($name, 0, 32);
    $name = strtr($name, ".:;,?%^*(){}[]<>+=$", "___________________");
    $name = strtr($name, "�a��e�u�", "aaeeeuuc");
    return strtolower($name);
}

/**
 * Check username validity. DEPRECATED
 *
 * @deprecated
 * @see Valid_UserNameFormat
 * @param String $name
 * @return Integer
 */
function account_namevalid($name, $key = '') {
    $rule = new Rule_UserName();
    if (!$rule->isValid($name)) {
        $GLOBALS['register_error'] = $rule->getErrorMessage();
        return 0;
    }
    return 1;
}

/**
 * Check groupname validity. DEPRECATED
 *
 * @deprecated
 * @see Rule_ProjectName
 * @param String $name
 * @return Integer
 */
function account_groupnamevalid($name) {
    $rule = new Rule_ProjectName();
    if (!$rule->isValid($name)) {
        $GLOBALS['register_error'] = $rule->getErrorMessage();
        return 0;
    }
    return 1;
}


// print out shell selects
function account_shellselects($current) {
    if (!$current) {
        $current = '/sbin/nologin';
    }
    foreach (PFUser::getAllUnixShells() as $shell) {
        $selected = '';
        if ($current == $shell) {
            $selected = ' selected="selected"';
        }
        echo '<option value="'.$shell.'"'.$selected.'>'.$shell.'</option>'.PHP_EOL;
    }
}
// Set user password (Unix, Web)
function account_create($loginname=''
                        ,$pw=''
                        ,$ldap_id=''
                        ,$realname=''
                        ,$register_purpose=''
                        ,$email=''
                        ,$status='P'
                        ,$confirm_hash=''
                        ,$mail_site=0
                        ,$mail_va=0
                        ,$timezone='GMT'
                        ,$lang_id='en_US'
                        ,$unix_status='N'
                        ,$expiry_date=0
                        ) {
    $um   = UserManager::instance();
    $user = new PFUser();
    $user->setUserName($loginname);
    $user->setRealName($realname);
    $user->setPassword($pw);
    $user->setLdapId($ldap_id);
    $user->setRegisterPurpose($register_purpose);
    $user->setEmail($email);
    $user->setStatus($status);
    $user->setConfirmHash($confirm_hash);
    $user->setMailSiteUpdates($mail_site);
    $user->setMailVA($mail_va);
    $user->setTimezone($timezone);
    $user->setLanguageID($lang_id);
    $user->setUnixStatus($unix_status);
    $user->setExpiryDate($expiry_date);

    $u = $um->createAccount($user);
    if ($u) {
        return $u->getId();
    } else {
        return $u;
    }
}
function account_create_mypage($user_id) {
    $um   = UserManager::instance();
    return $um->accountCreateMyPage($user_id);
}

function account_redirect_after_login($return_to) {
    global $pv;

    $event_manager = EventManager::instance();
    $event_manager->processEvent('account_redirect_after_login', array('return_to' => &$return_to));

    if($return_to) {
        $returnToToken = parse_url($return_to);
        if(preg_match('{/my(/|/index.php|)}i', $returnToToken['path'])) {
            $url = '/my/index.php';
        }
        else {
            $url = '/my/redirect.php';
        }
    } else {
        if (isset($pv) && $pv == 2) {
            $url = '/my/index.php?pv=2';
	    } else {
            $url = '/my/index.php';
        }
    }

    $url_redirect = new URLRedirect($event_manager);
    $GLOBALS['Response']->redirect($url_redirect->makeReturnToUrl($url, $return_to));
}
