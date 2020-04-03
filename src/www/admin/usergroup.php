<?php
/**
 * SourceForge: Breaking Down the Barriers to Open Source Development
 * Copyright 1999-2000 (c) VA Linux Systems
 * http://sourceforge.net
 *
 * Copyright (c) Enalean, 2016 - Present. All Rights Reserved.
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
 */

use Tuleap\Layout\IncludeAssets;
use Tuleap\Password\Configuration\PasswordConfigurationDAO;
use Tuleap\Password\Configuration\PasswordConfigurationRetriever;
use Tuleap\User\Admin\RestrictedProjectsUserCounter;
use Tuleap\User\Admin\UserChangePasswordPresenter;
use Tuleap\User\Admin\UserDetailsAccessPresenter;
use Tuleap\User\Admin\UserDetailsFormatter;
use Tuleap\User\Admin\UserDetailsPresenter;
use Tuleap\User\Admin\UserStatusBuilder;
use Tuleap\User\Admin\UserStatusChecker;
use Tuleap\User\ForgeUserGroupPermission\RESTReadOnlyAdmin\RestReadOnlyAdminPermission;
use Tuleap\User\Password\Change\PasswordChanger;
use Tuleap\User\Password\PasswordValidatorPresenter;
use Tuleap\User\SessionManager;

require_once __DIR__ . '/../include/pre.php';
require_once __DIR__ . '/../include/account.php';

$request = HTTPRequest::instance();
$request->checkUserIsSuperUser();

$include_assets = new IncludeAssets(__DIR__ . '/../assets/core', '/assets/core');

$GLOBALS['HTML']->includeFooterJavascriptFile(
    $include_assets->getFileURL('site-admin-user-details.js')
);
$GLOBALS['HTML']->includeFooterJavascriptFile('/scripts/check_pw.js');

$um                  = UserManager::instance();
$em                  = EventManager::instance();
$purifier            = Codendi_HTMLPurifier::instance();
$siteadmin           = new \Tuleap\Admin\AdminPageRenderer();
$user_status_checker = new UserStatusChecker();

$user_id = null;
$user    = null;

// Validate user
$vUserId = new Valid_UInt('user_id');
$vUserId->required();
if ($request->valid($vUserId)) {
    $user_id = $request->get('user_id');
    $user    = $um->getUserById($user_id);
}
if (!$user_id || !$user) {
    $GLOBALS['Response']->addFeedback('error', 'Invalid user');
}

// Validate action
$vAction = new Valid_WhiteList('action', array('update_user', 'update_password'));
$vAction->required();
if ($request->valid($vAction)) {
    $action = $request->get('action');
} else {
    $action = '';
}

$user_administration_csrf = new CSRFSynchronizerToken('/admin/usergroup.php?user_id=' . $user->getId());

if ($request->isPost()) {
    $user_administration_csrf->check();

    if ($action == 'update_user') {
        /*
         * Update the user
         */
        $vDate = new Valid('expiry_date');
        $vDate->addRule(new Rule_Date());
        //$vDate->required();
        if (!$request->valid($vDate)) {
            $GLOBALS['Response']->addFeedback('error', $Language->getText('admin_usergroup', 'data_not_parsed'));
        } else {
            if ($request->existAndNonEmpty('expiry_date')) {
                $date_list = explode('-', $request->get('expiry_date'), 3);
                $unix_expiry_time = mktime(0, 0, 0, $date_list[1], $date_list[2], $date_list[0]);
                if ($user->getExpiryDate() != $unix_expiry_time) {
                    $user->setExpiryDate($unix_expiry_time);
                }
            } else {
                if ($user->getExpiryDate()) {
                    $user->setExpiryDate('');
                }
            }

            $vShell = new Valid_WhiteList('form_shell', $user->getAllUnixShells());
            $vShell->required();
            if ($request->valid($vShell)) {
                $user->setShell($request->get('form_shell'));
            }

            $vEmail = new Valid_Email('email');
            $vEmail->required();
            if ($request->valid($vEmail)) {
                $user->setEmail($request->get('email'));
            }

            $vRealName = new Valid_String('form_realname');
            $vRealName->required();
            if ($request->valid($vRealName)) {
                $user->setRealName($request->get('form_realname'));
            }

            // form_unixstatus must be BEFORE form_status validation because
            // form_status can constraint form_unixstatus
            $vUnixStatus = new Valid_WhiteList('form_unixstatus', $user->getAllUnixStatus());
            $vUnixStatus->required();
            if ($request->valid($vUnixStatus)) {
                $user->setUnixStatus($request->get('form_unixstatus'));
            }

            // New status must be valid AND user account must already be validated
            // There are specific actions done in approve_pending scripts
            $accountActivationEvent = null;
            $vStatus = new Valid_WhiteList('form_status', $user->getAllWorkingStatus());
            $vStatus->required();
            if (
                $request->valid($vStatus)
                && in_array($user->getStatus(), $user->getAllWorkingStatus())
                && $user->getStatus() != $request->get('form_status')
            ) {
                switch ($request->get('form_status')) {
                    case PFUser::STATUS_ACTIVE:
                        $user->setStatus($request->get('form_status'));
                        $accountActivationEvent = 'project_admin_activate_user';
                        break;

                    case PFUser::STATUS_RESTRICTED:
                        if (! $user_status_checker->doesPlatformAllowRestricted()) {
                            $GLOBALS['Response']->addFeedback(
                                Feedback::ERROR,
                                _('Your platform does not allow restricted users.')
                            );
                            $GLOBALS['Response']->redirect('/admin/usergroup.php?user_id=' . $user->getId());
                        } elseif (! $user_status_checker->isRestrictedStatusAllowedForUser($user)) {
                            $GLOBALS['Response']->addFeedback(Feedback::ERROR, _('This user can\'t be restricted.'));
                            $GLOBALS['Response']->redirect('/admin/usergroup.php?user_id=' . $user->getId());
                        } elseif (ForgeConfig::areRestrictedUsersAllowed()) {
                            $user->setStatus($request->get('form_status'));
                            // If the user had a shell, set it to restricted shell
                            if (
                                $user->getShell()
                                && ($user->getShell() != "/bin/false")
                                && ($user->getShell() != "/sbin/nologin")
                            ) {
                                $user->setShell($GLOBALS['codendi_bin_prefix'] . '/cvssh-restricted');
                            }
                            $accountActivationEvent = 'project_admin_activate_user';
                        }
                        break;

                    case PFUser::STATUS_DELETED:
                        $user->setStatus($request->get('form_status'));
                        $user->setUnixStatus($user->getStatus());
                        $accountActivationEvent = 'project_admin_delete_user';
                        break;

                    case PFUser::STATUS_SUSPENDED:
                        $user->setStatus($request->get('form_status'));
                        $user->setUnixStatus($user->getStatus());
                        $accountActivationEvent = 'project_admin_suspend_user';
                        break;
                }
            }

            // Change login name
            if ($user->getUserName() != $request->get('form_user_login_name')) {
                if (SystemEventManager::instance()->canRenameUser($user)) {
                    $vLoginName = new Valid_UserNameFormat('form_user_login_name');
                    $vLoginName->required();
                    if ($request->valid($vLoginName)) {
                        switch ($user->getStatus()) {
                            case PFUser::STATUS_PENDING:
                            case PFUser::STATUS_VALIDATED:
                            case PFUser::STATUS_VALIDATED_RESTRICTED:
                                $user->setUserName($request->get('form_user_login_name'));
                                break;
                            default:
                                $em->processEvent(Event::USER_RENAME, array(
                                    'user_id'  => $user->getId(),
                                    'new_name' => $request->get('form_user_login_name'),
                                    'old_user' => $user));
                                $GLOBALS['Response']->addFeedback('info', $Language->getText('admin_usergroup', 'rename_user_msg', array($user->getUserName(), $request->get('form_user_login_name'))));
                                $GLOBALS['Response']->addFeedback('warning', $Language->getText('admin_usergroup', 'rename_user_warn'));
                        }
                    }
                } else {
                    $GLOBALS['Response']->addFeedback('warning', $Language->getText('admin_usergroup', 'rename_user_already_queued'), CODENDI_PURIFIER_DISABLED);
                }
            }

            if ($GLOBALS['sys_auth_type'] == 'ldap') {
                $vLdapId = new Valid_String('ldap_id');
                $vLdapId->required();
                if ($request->existAndNonEmpty('ldap_id') && $request->valid($vLdapId)) {
                    $user->setLdapId($request->get('ldap_id'));
                } else {
                    $user->setLdapId("");
                }
            }

            // Run the update
            if ($um->updateDb($user)) {
                $GLOBALS['Response']->addFeedback('info', $Language->getText('admin_usergroup', 'success_upd_u'));
                if ($accountActivationEvent) {
                    $em->processEvent($accountActivationEvent, array('user_id' => $user->getId()));
                }
            }

            if ($user->getUnixStatus() != 'N' && !$user->getUnixUid()) {
                $um->assignNextUnixUid($user);
            }
            $GLOBALS['Response']->redirect('/admin/usergroup.php?user_id=' . $user->getId());
        }
    } elseif ($action == 'update_password') {
        if (! $request->existAndNonEmpty('user_id')) {
            $GLOBALS['Response']->addFeedback(Feedback::ERROR, $Language->getText('admin_user_changepw', 'error_userid'));
            $GLOBALS['Response']->redirect('/admin/usergroup.php?user_id=' . $user->getId());
        }
        if (! $request->existAndNonEmpty('form_pw')) {
            $GLOBALS['Response']->addFeedback(Feedback::ERROR, $Language->getText('admin_user_changepw', 'error_nopasswd'));
            $GLOBALS['Response']->redirect('/admin/usergroup.php?user_id=' . $user->getId());
        }
        if ($request->get('form_pw') !== $request->get('form_pw2')) {
            $GLOBALS['Response']->addFeedback(Feedback::ERROR, $Language->getText('admin_user_changepw', 'error_passwd'));
            $GLOBALS['Response']->redirect('/admin/usergroup.php?user_id=' . $user->getId());
        }

        $password_sanity_checker = \Tuleap\Password\PasswordSanityChecker::build();
        if (! $password_sanity_checker->check($request->get('form_pw'))) {
            foreach ($password_sanity_checker->getErrors() as $error) {
                $GLOBALS['Response']->addFeedback(Feedback::ERROR, $error);
            }
            $GLOBALS['Response']->redirect('/admin/usergroup.php?user_id=' . $user->getId());
        }

        $user = $user_manager->getUserById($request->get('user_id'));

        if ($user === null) {
            $GLOBALS['Response']->addFeedback(Feedback::ERROR, $Language->getText('admin_user_changepw', 'error_userid'));
            $GLOBALS['Response']->redirect('/admin/usergroup.php?user_id=' . $user->getId());
        }

        $password_changer = new PasswordChanger(
            $user_manager,
            new SessionManager($user_manager, new SessionDao(), new RandomNumberGenerator()),
            new \Tuleap\User\Password\Reset\Revoker(new \Tuleap\User\Password\Reset\DataAccessObject())
        );
        try {
            $password_changer->changePassword($user, $request->get('form_pw'));
            $GLOBALS['Response']->addFeedback(Feedback::INFO, $Language->getText('admin_user_changepw', 'msg_changed'));
        } catch (Exception $ex) {
            $GLOBALS['Response']->addFeedback(Feedback::ERROR, $Language->getText('admin_user_changepw', 'error_update'));
        }

        $GLOBALS['Response']->redirect('/admin/usergroup.php?user_id=' . $user->getId());
    }
}

$projects = array();
foreach ($user->getGroups() as $project) {
    $projects[] = new Tuleap\User\Admin\UserDetailsProjectPresenter($project, $user->isAdmin($project->getID()));
}

$additional_details = array();
EventManager::instance()->processEvent(
    UserDetailsPresenter::ADDITIONAL_DETAILS,
    array(
        'user'               => $user,
        'additional_details' => &$additional_details
    )
);

$details_formatter = new UserDetailsFormatter(new UserStatusBuilder($user_status_checker));

$additional_password_messages = array();
EventManager::instance()->processEvent(
    'before_admin_change_pw',
    array(
        'additional_password_messages' => &$additional_password_messages
    )
);

$password_configuration_retriever = new PasswordConfigurationRetriever(new PasswordConfigurationDAO());
$password_configuration           = $password_configuration_retriever->getPasswordConfiguration();
$password_strategy                = new PasswordStrategy($password_configuration);
include($GLOBALS['Language']->getContent('account/password_strategy'));
$passwords_validators = array();
foreach ($password_strategy->validators as $key => $v) {
    $passwords_validators[] = new PasswordValidatorPresenter(
        'password_validator_msg_' . $purifier->purify($key),
        $purifier->purify($key, CODENDI_PURIFIER_JS_QUOTE),
        $purifier->purify($v->description())
    );
}

$restricted_projects_user_counter = new RestrictedProjectsUserCounter(new UserGroupDao());

$forge_user_group_permission_manager = new User_ForgeUserGroupPermissionsManager(
    new User_ForgeUserGroupPermissionsDao()
);

$user_has_rest_read_only_administration_delegation = $forge_user_group_permission_manager->doesUserHavePermission(
    $user,
    new RestReadOnlyAdminPermission()
);

$siteadmin->renderAPresenter(
    $Language->getText('admin_usergroup', 'title'),
    ForgeConfig::get('codendi_dir') . '/src/templates/admin/users/',
    'user',
    new UserDetailsPresenter(
        $user,
        $projects,
        new UserDetailsAccessPresenter($user, $um->getUserAccessInfo($user)),
        new UserChangePasswordPresenter(
            $user,
            $user_administration_csrf,
            $additional_password_messages,
            $passwords_validators
        ),
        $user_administration_csrf,
        $additional_details,
        $details_formatter->getMore($user),
        $details_formatter->getShells($user),
        $details_formatter->getStatus($user),
        $restricted_projects_user_counter->getNumberOfProjectsNotAllowingRestrictedTheUserIsMemberOf($user),
        $details_formatter->getUnixStatus($user),
        $user_has_rest_read_only_administration_delegation
    )
);
