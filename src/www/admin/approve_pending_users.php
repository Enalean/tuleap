<?php
/**
 * Copyright (c) Enalean, 2015-Present. All Rights Reserved.
 * Copyright 1999-2000 (c) The SourceForge Crew
 *
 * SourceForge: Breaking Down the Barriers to Open Source Development
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

use Tuleap\Authentication\SplitToken\SplitTokenVerificationStringHasher;
use Tuleap\Instrument\Prometheus\Prometheus;
use Tuleap\InviteBuddy\Admin\InvitedByPresenterBuilder;
use Tuleap\InviteBuddy\InvitationDao;
use Tuleap\InviteBuddy\InvitationInstrumentation;
use Tuleap\InviteBuddy\InviteBuddyConfiguration;
use Tuleap\InviteBuddy\ProjectMemberAccordingToInvitationAdder;
use Tuleap\Language\LocaleSwitcher;
use Tuleap\Project\UGroups\Membership\DynamicUGroups\ProjectMemberAdderWithStatusCheckAndNotifications;

require_once __DIR__ . '/../include/pre.php';
require_once __DIR__ . '/../include/account.php';
require_once __DIR__ . '/../include/proj_email.php';
require_once __DIR__ . '/admin_utils.php';

$request = HTTPRequest::instance();
$request->checkUserIsSuperUser();

$include_assets = new \Tuleap\Layout\IncludeAssets(__DIR__ . '/../../scripts/site-admin/frontend-assets', '/assets/core/site-admin');
$GLOBALS['HTML']->addJavascriptAsset(new \Tuleap\Layout\JavascriptAsset($include_assets, 'site-admin-pending-users.js'));

define('ADMIN_APPROVE_PENDING_PAGE_PENDING', 'pending');
define('ADMIN_APPROVE_PENDING_PAGE_VALIDATED', 'validated');

$hp            = Codendi_HTMLPurifier::instance();
$action_select = '';
$status        = '';
$users_array   = [];
if ($request->exist('action_select')) {
    $action_select = $request->get('action_select');
}
if ($request->exist('status')) {
    $status = $request->get('status');
}
if ($request->exist('list_of_users')) {
    $users_array = array_filter(array_map('intval', explode(",", $request->get('list_of_users'))));
}

$valid_page  = new Valid_WhiteList('page', [ADMIN_APPROVE_PENDING_PAGE_PENDING, ADMIN_APPROVE_PENDING_PAGE_VALIDATED]);
$page        = $request->getValidated('page', $valid_page, '');
$csrf_token  = new CSRFSynchronizerToken('/admin/approve_pending_users.php?page=' . $page);
$expiry_date = 0;
if ($request->exist('form_expiry') && $request->get('form_expiry') != '' && ! preg_match("/[0-9]{4}-[0-9]{1,2}-[0-9]{1,2}/", $request->get('form_expiry'))) {
    $feedback .= ' ' . $Language->getText('admin_approve_pending_users', 'data_not_parsed');
} else {
    $vDate = new Valid_String();
    if ($request->exist('form_expiry') && $request->get('form_expiry') != '' && $vDate->validate($request->get('form_expiry'))) {
        $date_list        = explode("-", $request->get('form_expiry'), 3);
        $unix_expiry_time = mktime(0, 0, 0, $date_list[1], $date_list[2], $date_list[0]);
        $expiry_date      = $unix_expiry_time;
    }

    if (($action_select == 'activate')) {
        $csrf_token->check();

        $shell = "";
        if ($status == 'restricted') {
            $newstatus = 'R';
            $shell     = ",shell='" . ForgeConfig::get('codendi_bin_prefix') . "/cvssh-restricted'";
        } else {
            $newstatus = 'A';
        }

        $users_ids = db_ei_implode($users_array);
        // update the user status flag to active
        db_query("UPDATE user SET expiry_date='" . $expiry_date . "', status='" . $newstatus . "'" . $shell .
                 ", approved_by='" . db_ei(UserManager::instance()->getCurrentUser()->getId()) . "'" .
             " WHERE user_id IN ($users_ids)");

        $em = EventManager::instance();
        foreach ($users_array as $user_id) {
            $em->processEvent('project_admin_activate_user', ['user_id' => $user_id]);
        }

        // Now send the user verification emails
        $res_user = db_query("SELECT email, confirm_hash, user_name FROM user "
                 . " WHERE user_id IN ($users_ids)");

         // Send a notification message to the user when account is activated by the Site Administrator
        $base_url = \Tuleap\ServerHostname::HTTPSUrl();
        while ($row_user = db_fetch_array($res_user)) {
            if (! send_approval_new_user_email($row_user['email'], $row_user['user_name'])) {
                 $GLOBALS['Response']->addFeedback(
                     Feedback::ERROR,
                     $GLOBALS['Language']->getText('global', 'mail_failed', [ForgeConfig::get('sys_email_admin')])
                 );
            }
               usleep(250000);
        }

        $logger                     = \BackendLogger::getDefaultLogger();
        $user_manager               = \UserManager::instance();
        $project_manager            = ProjectManager::instance();
        $invitation_instrumentation = new InvitationInstrumentation(Prometheus::instance());
        $invitation_dao             = new InvitationDao(
            new SplitTokenVerificationStringHasher(),
            $invitation_instrumentation
        );

        $project_member_adder = new ProjectMemberAccordingToInvitationAdder(
            $user_manager,
            $project_manager,
            ProjectMemberAdderWithStatusCheckAndNotifications::build(),
            $invitation_instrumentation,
            $logger,
            new \Tuleap\InviteBuddy\InvitationEmailNotifier(new LocaleSwitcher()),
            new ProjectHistoryDao(),
        );

        foreach ($users_array as $user_id) {
            foreach ($invitation_dao->searchByCreatedUserId($user_id) as $invitation) {
                if ($invitation->to_user_id) {
                    continue;
                }

                $just_created_user = $user_manager->getUserById($user_id);
                if (! $just_created_user) {
                    continue;
                }

                $project_member_adder->addUserToProjectAccordingToInvitation(
                    $just_created_user,
                    $invitation,
                );
            }
        }

        if (count($users_array) > 1) {
            $GLOBALS['Response']->addFeedback(
                Feedback::INFO,
                $Language->getText('admin_approve_pending_users', 'users_activated_success')
            );
        } else {
            $GLOBALS['Response']->addFeedback(
                Feedback::INFO,
                $Language->getText('admin_approve_pending_users', 'user_activated_success')
            );
        }
        $GLOBALS['Response']->redirect('/admin/approve_pending_users.php?' . http_build_query(['page' => $page]));
    } elseif ($action_select == 'validate') {
        $csrf_token->check();
        if ($status == 'restricted') {
            $newstatus = 'W';
        } else {
            $newstatus = 'V';
        }

        $invitation_dao           = new InvitationDao(
            new SplitTokenVerificationStringHasher(),
            new InvitationInstrumentation(Prometheus::instance())
        );
        $nb_asked_to_be_validated = count($users_array);

        $users_array = array_reduce(
            $users_array,
            static function (array $to_be_validated_user_ids, int $user_id) use ($invitation_dao): array {
                if (! $invitation_dao->hasUsedAnInvitationToRegister($user_id)) {
                    $to_be_validated_user_ids[] = $user_id;
                }

                return $to_be_validated_user_ids;
            },
            []
        );
        if (empty($users_array)) {
            if ($nb_asked_to_be_validated === 1) {
                $GLOBALS['Response']->addFeedback(Feedback::INFO, _("The user doesn't need to be validated"));
            } else {
                $GLOBALS['Response']->addFeedback(Feedback::INFO, _("All users don't need to be validated"));
            }
            $GLOBALS['Response']->redirect('/admin/approve_pending_users.php?' . http_build_query(['page' => $page]));
        }

        // update the user status flag to active
        db_query("UPDATE user SET expiry_date='" . $expiry_date . "', status='" . $newstatus . "'" .
                 ", approved_by='" . db_ei(UserManager::instance()->getCurrentUser()->getId()) . "'" .
                 " WHERE user_id IN (" . db_ei_implode($users_array) . ")");

        // Now send the user verification emails
        $res_user = db_query("SELECT email, confirm_hash, user_name FROM user "
                 . " WHERE user_id IN (" . db_ei_implode($users_array) . ")");

        $confirmation_hash_email_sender = new \Tuleap\User\Account\Register\ConfirmationHashEmailSender(
            new \TuleapRegisterMail(
                new \MailPresenterFactory(),
                TemplateRendererFactory::build()
                    ->getRenderer(\ForgeConfig::get('codendi_dir') . '/src/templates/mail/'),
                $user_manager,
                new LocaleSwitcher(),
                "mail"
            ),
            \Tuleap\ServerHostname::HTTPSUrl(),
        );
        while ($row_user = db_fetch_array($res_user)) {
            if (! $confirmation_hash_email_sender->sendConfirmationHashEmail($row_user['email'], $row_user['user_name'], $row_user['confirm_hash'])) {
                    $GLOBALS['Response']->addFeedback(
                        Feedback::ERROR,
                        $GLOBALS['Language']->getText('global', 'mail_failed', [ForgeConfig::get('sys_email_admin')])
                    );
            }
            usleep(250000);
        }

        if (count($users_array) > 1) {
            $GLOBALS['Response']->addFeedback(
                Feedback::INFO,
                $Language->getText('admin_approve_pending_users', 'users_validated_success')
            );
        } else {
            $GLOBALS['Response']->addFeedback(
                Feedback::INFO,
                $Language->getText('admin_approve_pending_users', 'user_validated_success')
            );
        }
        $GLOBALS['Response']->redirect('/admin/approve_pending_users.php?' . http_build_query(['page' => $page]));
    } elseif ($action_select == 'delete') {
        $csrf_token->check();
        db_query("UPDATE user SET status='D', approved_by='" . db_ei(UserManager::instance()->getCurrentUser()->getId()) . "'" .
                 " WHERE user_id IN (" . db_ei_implode($users_array) . ")");
        $em = EventManager::instance();
        foreach ($users_array as $user_id) {
            $em->processEvent('project_admin_delete_user', ['user_id' => $user_id]);
        }

        if (count($users_array) > 1) {
            $GLOBALS['Response']->addFeedback(
                Feedback::INFO,
                $Language->getText('admin_approve_pending_users', 'users_deleted_success')
            );
        } else {
            $GLOBALS['Response']->addFeedback(
                Feedback::INFO,
                $Language->getText('admin_approve_pending_users', 'user_deleted_success')
            );
        }
        $GLOBALS['Response']->redirect('/admin/approve_pending_users.php?' . http_build_query(['page' => $page]));
    } elseif ($action_select === 'resend_email') {
        $csrf_token->check();
        $user_manager   = UserManager::instance();
        $invitation_dao = new InvitationDao(
            new SplitTokenVerificationStringHasher(),
            new InvitationInstrumentation(Prometheus::instance())
        );
        foreach ($users_array as $user_id) {
            $user = $user_manager->getUserById($user_id);
            if ($user === null) {
                continue;
            }
            if ($invitation_dao->hasUsedAnInvitationToRegister((int) $user->getId())) {
                continue;
            }
            if (
                $user->getStatus() !== PFUser::STATUS_PENDING && $user->getStatus() !== PFUser::STATUS_VALIDATED &&
                $user->getStatus() !== PFUser::STATUS_VALIDATED_RESTRICTED
            ) {
                continue;
            }


            $confirmation_hash_email_sender = new \Tuleap\User\Account\Register\ConfirmationHashEmailSender(
                new \TuleapRegisterMail(
                    new \MailPresenterFactory(),
                    TemplateRendererFactory::build()
                        ->getRenderer(\ForgeConfig::get('codendi_dir') . '/src/templates/mail/'),
                    $user_manager,
                    new LocaleSwitcher(),
                    "mail"
                ),
                \Tuleap\ServerHostname::HTTPSUrl(),
            );
            $is_mail_sent                   = $confirmation_hash_email_sender->sendConfirmationHashEmail(
                $user->getEmail(),
                $user->getUserName(),
                $user->getConfirmHash()
            );

            if ($is_mail_sent) {
                $GLOBALS['Response']->addFeedback(
                    Feedback::INFO,
                    $Language->getText(
                        'admin_approve_pending_users',
                        'resend_mail_success',
                        [$user->getEmail()]
                    )
                );
            } else {
                $GLOBALS['Response']->addFeedback(
                    Feedback::INFO,
                    $Language->getText('admin_approve_pending_users', 'resend_mail_error', [$user->getEmail()])
                );
            }
        }
        $GLOBALS['Response']->redirect('/admin/approve_pending_users.php?' . http_build_query(['page' => $page]));
    }
}

$users_rows = [];
$dao        = new \Tuleap\User\Admin\PendingUsersDao();
// No action - First time in this script
// Show the list of pending user waiting for approval
if ($page === ADMIN_APPROVE_PENDING_PAGE_PENDING) {
    if (ForgeConfig::getInt(User_UserStatusManager::CONFIG_USER_REGISTRATION_APPROVAL) === 0) {
        $users_rows = $dao->searchPendingAndValidatedUsers();
        $msg        = $Language->getText('admin_approve_pending_users', 'no_pending');
    } else {
        $users_rows = $dao->searchPendingUsers();
        $msg        = $Language->getText('admin_approve_pending_users', 'no_pending_validated');
    }
} elseif ($page === ADMIN_APPROVE_PENDING_PAGE_VALIDATED) {
    $users_rows = $dao->searchValidatedUsers();
    $msg        = $Language->getText('admin_approve_pending_users', 'no_validated');
}

$user_manager    = UserManager::instance();
$project_manager = ProjectManager::instance();
$current_user    = $user_manager->getCurrentUser();

$invite_buddy_configuration = new InviteBuddyConfiguration(EventManager::instance());
$invited_by_builder         = new InvitedByPresenterBuilder(
    new InvitationDao(
        new SplitTokenVerificationStringHasher(),
        new InvitationInstrumentation(Prometheus::instance())
    ),
    $user_manager,
    $project_manager,
);


$users = [];
foreach ($users_rows as $row) {
    $user = $user_manager->getUserById($row['user_id']);
    if (! $user) {
        continue;
    }

    $invited_by = $invite_buddy_configuration->isFeatureEnabled()
        ? $invited_by_builder->getInvitedByPresenter($user, $request->getCurrentUser())
        : null;

    $is_email_already_validated = $invited_by && $invited_by->has_used_an_invitation_to_register;

    $users[] = new Tuleap\User\Admin\PendingUserPresenter(
        $row['user_id'],
        $row['user_name'],
        $row['realname'],
        $row['email'],
        $row['add_date'],
        $row['register_purpose'],
        $row['expiry_date'],
        $row['status'],
        $invited_by,
        $is_email_already_validated,
    );
}

$title = $GLOBALS['Language']->getText('admin_approve_pending_users', 'title');
if ($page === ADMIN_APPROVE_PENDING_PAGE_VALIDATED) {
    $title = $GLOBALS['Language']->getText('admin_approve_pending_users', 'title_validated');
}

if (count($users) === 0) {
    $siteadmin = new Tuleap\Admin\AdminPageRenderer();
    $siteadmin->renderAPresenter(
        $title,
        ForgeConfig::get('codendi_dir') . '/src/templates/admin/users/',
        'no-pending',
        [
            'title'   => $title,
            'msg'     => $msg,
            'go_back' => $GLOBALS['Language']->getText('admin_approve_pending_users', 'go_back'),
        ]
    );
} else {
    $siteadmin = new Tuleap\Admin\AdminPageRenderer();
    $siteadmin->renderAPresenter(
        $title,
        ForgeConfig::get('codendi_dir') . '/src/templates/admin/users/',
        'pending',
        new Tuleap\User\Admin\PendingUsersCollectionPresenter($title, $users, $page, $csrf_token)
    );
}
