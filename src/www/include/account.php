<?php
/**
 * Copyright (c) Enalean, 2015 - Present. All Rights Reserved.
 * Copyright 1999-2000 (c) The SourceForge Crew
 *
 * This file is a part of Tuleap.
 *
 * Tuleap is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Tuleap is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 *
 */

use Tuleap\Cryptography\ConcealedString;
use Tuleap\InviteBuddy\AccountCreationFeedback;
use Tuleap\User\Account\RedirectAfterLogin;

// adduser.php - All the forms and functions to manage unix users
// Add user to an existing project
function account_add_user_to_group($group_id, &$user_unix_name)
{
    $um = UserManager::instance();
    $user = $um->findUser($user_unix_name);
    if ($user) {
        $project = ProjectManager::instance()->getProject($group_id);
        if (! $project || $project->isError()) {
            return false;
        }
        $project_member_adder = \Tuleap\Project\UGroups\Membership\DynamicUGroups\ProjectMemberAdderWithStatusCheckAndNotifications::build();
        $project_member_adder->addProjectMember($user, $project);
        return true;
    } else {
        //user doesn't exist
        $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('include_account', 'user_not_exist'));
        return false;
    }
}

// Generate a valid Unix login name from the email address.
function account_make_login_from_email($email)
{
    $pattern = "/^(.*)@.*$/";
    $replacement = "$1";
    $name = preg_replace($pattern, $replacement, $email);
    $name = substr($name, 0, 32);
    $name = strtr($name, ".:;,?%^*(){}[]<>+=$", "___________________");
    $name = strtr($name, "�a��e�u�", "aaeeeuuc");
    return strtolower($name);
}

// Set user password (Unix, Web)
function account_create(string $loginname, ?ConcealedString $pw, $ldap_id = '', $realname = '', $register_purpose = '', $email = '', $status = 'P', $confirm_hash = '', $mail_site = 0, $mail_va = 0, $timezone = 'GMT', $lang_id = 'en_US', $unix_status = 'N', $expiry_date = 0)
{
    $um   = UserManager::instance();
    $user = new PFUser();
    $user->setUserName($loginname);
    $user->setRealName($realname);
    if ($pw !== null) {
        $user->setPassword($pw);
    }
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
        $account_creation_feedback = new AccountCreationFeedback(
            new \Tuleap\InviteBuddy\InvitationDao(),
            $um,
            new \Tuleap\InviteBuddy\AccountCreationFeedbackEmailNotifier(),
            BackendLogger::getDefaultLogger(),
        );
        $account_creation_feedback->accountHasJustBeenCreated($u);

        return $u->getId();
    } else {
        return $u;
    }
}

function account_redirect_after_login(PFUser $user, string $return_to): void
{
    global $pv;

    $event_manager = EventManager::instance();
    $redirect_after_login = $event_manager->dispatch(new RedirectAfterLogin($user, $return_to, isset($pv) && $pv == 2));
    assert($redirect_after_login instanceof RedirectAfterLogin);
    $return_to = $redirect_after_login->getReturnTo();

    if ($return_to) {
        $returnToToken = parse_url($return_to);
        if (preg_match('{/my(/|/index.php|)}i', $returnToToken['path'] ?? '')) {
            $url = '/my/index.php';
        } else {
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
