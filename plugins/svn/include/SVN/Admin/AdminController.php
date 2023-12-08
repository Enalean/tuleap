<?php
/**
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

namespace Tuleap\SVN\Admin;

use CSRFSynchronizerToken;
use Feedback;
use HTTPRequest;
use Psr\Log\LoggerInterface;
use Project;
use Rule_Email;
use Tuleap\SVN\Notifications\CannotAddUgroupsNotificationException;
use Tuleap\SVN\Notifications\CannotAddUsersNotificationException;
use Tuleap\SVN\Notifications\NotificationListBuilder;
use Tuleap\SVN\Notifications\NotificationsEmailsBuilder;
use Tuleap\SVN\Repository\HookConfig;
use Tuleap\SVN\Repository\HookConfigRetriever;
use Tuleap\SVN\Repository\HookConfigUpdator;
use Tuleap\SVNCore\Repository;
use Tuleap\SVN\Repository\RepositoryDeleter;
use Tuleap\SVN\Repository\RepositoryManager;
use Tuleap\SVN\ServiceSvn;
use Tuleap\User\InvalidEntryInAutocompleterCollection;
use Tuleap\User\RequestFromAutocompleter;
use UGroupManager;
use UserManager;
use Valid_Int;
use Valid_String;

class AdminController
{
    private $repository_manager;
    private $mail_header_manager;
    private $mail_notification_manager;
    /**
     * @var NotificationListBuilder
     */
    private $notification_list_builder;
    /**
     * @var NotificationsEmailsBuilder
     */
    private $emails_builder;
    /**
     * @var UserManager
     */
    private $user_manager;
    /**
     * @var UGroupManager
     */
    private $ugroup_manager;
    /**
     * @var HookConfigUpdator
     */
    private $hook_config_updator;
    /**
     * @var HookConfigRetriever
     */
    private $hook_config_retriever;
    /**
     * @var RepositoryDeleter
     */
    private $repository_deleter;

    public function __construct(
        MailHeaderManager $mail_header_manager,
        RepositoryManager $repository_manager,
        MailNotificationManager $mail_notification_manager,
        LoggerInterface $logger,
        NotificationListBuilder $notification_list_builder,
        NotificationsEmailsBuilder $emails_builder,
        UserManager $user_manager,
        UGroupManager $ugroup_manager,
        HookConfigUpdator $hook_config_updator,
        HookConfigRetriever $hook_config_retriever,
        RepositoryDeleter $repository_deleter,
    ) {
        $this->repository_manager        = $repository_manager;
        $this->mail_header_manager       = $mail_header_manager;
        $this->mail_notification_manager = $mail_notification_manager;
        $this->logger                    = $logger;
        $this->notification_list_builder = $notification_list_builder;
        $this->emails_builder            = $emails_builder;
        $this->user_manager              = $user_manager;
        $this->ugroup_manager            = $ugroup_manager;
        $this->hook_config_updator       = $hook_config_updator;
        $this->hook_config_retriever     = $hook_config_retriever;
        $this->repository_deleter        = $repository_deleter;
    }

    private function generateToken(Project $project, Repository $repository)
    {
        return new CSRFSynchronizerToken(SVN_BASE_URL . "/?group_id=" . $project->getid() . '&repo_id=' . $repository->getId() . "&action=display-mail-notification");
    }

    public function displayMailNotification(ServiceSvn $service, HTTPRequest $request)
    {
        $repository = $this->repository_manager->getByIdAndProject($request->get('repo_id'), $request->getProject());

        $token = $this->generateToken($request->getProject(), $repository);

        $mail_header           = $this->mail_header_manager->getByRepository($repository);
        $notifications_details = $this->mail_notification_manager->getByRepository($repository);

        $title = $GLOBALS['Language']->getText('global', 'Administration');

        $service->renderInPageRepositoryAdministration(
            $request,
            $repository->getName() . ' – ' . $title,
            'admin/mail_notification',
            new MailNotificationPresenter(
                $repository,
                $request->getProject(),
                $token,
                $title,
                $mail_header,
                $this->notification_list_builder->getNotificationsPresenter($notifications_details, $this->emails_builder)
            ),
            '',
            $repository,
        );
    }

    public function saveMailHeader(HTTPRequest $request)
    {
        $repository = $this->repository_manager->getByIdAndProject($request->get('repo_id'), $request->getProject());

        $token = $this->generateToken($request->getProject(), $repository);
        $token->check();

        $repo_name = $request->get("form_mailing_header");
        $vHeader   = new Valid_String('form_mailing_header');
        if ($request->valid($vHeader)) {
            $mail_header = new MailHeader($repository, $repo_name);
            try {
                $this->mail_header_manager->create($mail_header);
                $GLOBALS['Response']->addFeedback('info', dgettext('tuleap-svn', 'Header updated successfully'));
            } catch (CannotCreateMailHeaderException $e) {
                $GLOBALS['Response']->addFeedback('error', dgettext('tuleap-svn', 'Header update failed.'));
            }
        } else {
            $GLOBALS['Response']->addFeedback('error', dgettext('tuleap-svn', 'Header update failed.'));
        }

        $GLOBALS['Response']->redirect(SVN_BASE_URL . '/?' . http_build_query(
            [
                'group_id' => $request->getProject()->getid(),
                'repo_id' => $request->get('repo_id'),
                'action' => 'display-mail-notification',
            ]
        ));
    }

    public function saveMailingList(HTTPRequest $request)
    {
        $repository = $this->repository_manager->getByIdAndProject($request->get('repo_id'), $request->getProject());

        $token = $this->generateToken($request->getProject(), $repository);
        $token->check();

        $notification_to_add    = $request->get('notification_add');
        $notification_to_update = $request->get('notification_update');

        if (
            $notification_to_update
            && ! empty($notification_to_update)
        ) {
            $this->updateMailingList($request, $repository, $notification_to_update);
        } else {
            $this->createMailingList($request, $repository, $notification_to_add);
        }
    }

    public function createMailingList(HTTPRequest $request, Repository $repository, $notification_to_add)
    {
        $form_path       = $notification_to_add['path'];
        $valid_path      = new Valid_String($form_path);
        $invalid_entries = new InvalidEntryInAutocompleterCollection();
        $autocompleter   = $this->getAutocompleter($request->getProject(), $invalid_entries, $notification_to_add['emails']);

        $is_path_valid = $request->valid($valid_path) && $form_path !== '';
        $invalid_entries->generateWarningMessageForInvalidEntries();

        if (! $is_path_valid) {
            $this->addFeedbackPathError();
            $this->redirectOnDisplayNotification($request);
            return;
        }

        if ($this->mail_notification_manager->isAnExistingPath($repository, 0, $form_path)) {
            $GLOBALS['Response']->addFeedback(
                Feedback::WARN,
                sprintf(
                    dgettext(
                        'tuleap-svn',
                        "The path '%s' already exists."
                    ),
                    $form_path
                )
            );
            $this->redirectOnDisplayNotification($request);
            return;
        }

        if (! $autocompleter->isNotificationEmpty()) {
            $mail_notification = new MailNotification(
                0,
                $repository,
                $form_path,
                $autocompleter->getEmails(),
                $autocompleter->getUsers(),
                $autocompleter->getUgroups()
            );
            try {
                $this->mail_notification_manager->createWithHistory($mail_notification);
                $GLOBALS['Response']->addFeedback(
                    Feedback::INFO,
                    dgettext('tuleap-svn', 'Email Notification updated successfully')
                );
            } catch (CannotCreateMailHeaderException $e) {
                $GLOBALS['Response']->addFeedback(
                    Feedback::ERROR,
                    dgettext('tuleap-svn', 'Unable to save Notification')
                );
            } catch (CannotAddUsersNotificationException $e) {
                $this->addFeedbackUsersNotAdded($e->getUsersNotAdded());
            } catch (CannotAddUgroupsNotificationException $e) {
                $this->addFeedbackUgroupsNotAdded($e->getUgroupsNotAdded());
            }
        } else {
            $GLOBALS['Response']->addFeedback(
                Feedback::ERROR,
                dgettext('tuleap-svn', 'Unable to save Notification')
            );
        }

        $this->redirectOnDisplayNotification($request);
    }

    public function updateMailingList(HTTPRequest $request, Repository $repository, $notification_to_update)
    {
        $notification_ids = array_keys($notification_to_update);
        $notification_id  = $notification_ids[0];
        $new_path         = $notification_to_update[$notification_id]['path'];
        $emails           = $notification_to_update[$notification_id]['emails'];
        $valid_path       = new Valid_String($new_path);

        $invalid_entries = new InvalidEntryInAutocompleterCollection();
        $autocompleter   = $this->getAutocompleter($request->getProject(), $invalid_entries, $emails);

        $is_path_valid = $request->valid($valid_path) && $new_path !== '';
        $invalid_entries->generateWarningMessageForInvalidEntries();

        if (! $is_path_valid) {
            $this->addFeedbackPathError($request);
            $this->redirectOnDisplayNotification($request);
            return;
        }

        $notification = $this->mail_notification_manager->getByIdAndRepository($repository, $notification_id);

        if (! $notification) {
            $GLOBALS['Response']->addFeedback(
                Feedback::WARN,
                sprintf(
                    dgettext(
                        'tuleap-svn',
                        "Notification to update doesn't exist."
                    ),
                    $new_path
                )
            );
            $this->redirectOnDisplayNotification($request);
            return;
        }

        if (
            $notification->getPath() !== $new_path
            && $this->mail_notification_manager->isAnExistingPath($repository, $notification_id, $new_path)
        ) {
            $GLOBALS['Response']->addFeedback(
                Feedback::WARN,
                sprintf(
                    dgettext(
                        'tuleap-svn',
                        "The path '%s' already exists."
                    ),
                    $new_path
                )
            );
            $this->redirectOnDisplayNotification($request);
            return;
        }

        $email_notification = new MailNotification(
            $notification_id,
            $repository,
            $new_path,
            $autocompleter->getEmails(),
            $autocompleter->getUsers(),
            $autocompleter->getUgroups()
        );
        try {
            if (! $autocompleter->isNotificationEmpty()) {
                $this->mail_notification_manager->update($email_notification);
            } else {
                $this->mail_notification_manager->removeByNotificationId($notification_id);
            }

            $GLOBALS['Response']->addFeedback(
                Feedback::INFO,
                dgettext('tuleap-svn', 'Email Notification updated successfully')
            );
        } catch (CannotCreateMailHeaderException $e) {
            $GLOBALS['Response']->addFeedback(
                Feedback::ERROR,
                dgettext('tuleap-svn', 'Unable to save Notification')
            );
        }

        $this->redirectOnDisplayNotification($request);
    }

    public function deleteMailingList(HTTPRequest $request)
    {
        $repository = $this->repository_manager->getByIdAndProject($request->get('repo_id'), $request->getProject());

        $token = $this->generateToken($request->getProject(), $repository);
        $token->check();

        $valid_notification_remove_id = new Valid_Int('notification_remove_id');
        if ($request->valid($valid_notification_remove_id)) {
            $notification_remove_id = $request->get('notification_remove_id');
            try {
                $notification = $this->mail_notification_manager->getByIdAndRepository(
                    $repository,
                    $notification_remove_id
                );

                $this->mail_notification_manager->removeByPathWithHistory($notification);
                $GLOBALS['Response']->addFeedback(
                    Feedback::INFO,
                    dgettext(
                        'tuleap-svn',
                        'Notification deleted successfully.'
                    )
                );
            } catch (CannotDeleteMailNotificationException $e) {
                $GLOBALS['Response']->addFeedback(
                    Feedback::ERROR,
                    dgettext('tuleap-svn', 'Unable to delete notification list')
                );
            }
        }

        $this->redirectOnDisplayNotification($request);
    }

    public function updateHooksConfig(ServiceSvn $service, HTTPRequest $request)
    {
        $repository  = $this->repository_manager->getByIdAndProject($request->get('repo_id'), $request->getProject());
        $hook_config = [
            HookConfig::MANDATORY_REFERENCE => (bool) $request->get("pre_commit_must_contain_reference"),
            HookConfig::COMMIT_MESSAGE_CAN_CHANGE => (bool) $request->get("allow_commit_message_changes"),
        ];
        $this->hook_config_updator->updateHookConfig($repository, $hook_config);

        $this->displayHooksConfig($service, $request);
    }

    public function displayHooksConfig(ServiceSvn $service, HTTPRequest $request)
    {
        $repository  = $this->repository_manager->getByIdAndProject($request->get('repo_id'), $request->getProject());
        $hook_config = $this->hook_config_retriever->getHookConfig($repository);

        $token = $this->generateToken($request->getProject(), $repository);
        $title = $GLOBALS['Language']->getText('global', 'Administration');

        $service->renderInPageRepositoryAdministration(
            $request,
            $repository->getName() . ' – ' . $title,
            'admin/hooks_config',
            new HooksConfigurationPresenter(
                $repository,
                $request->getProject(),
                $token,
                $title,
                $hook_config->getHookConfig(HookConfig::MANDATORY_REFERENCE),
                $hook_config->getHookConfig(HookConfig::COMMIT_MESSAGE_CAN_CHANGE)
            ),
            '',
            $repository,
        );
    }

    public function displayRepositoryDelete(ServiceSvn $service, HTTPRequest $request)
    {
        $repository = $this->repository_manager->getByIdAndProject($request->get('repo_id'), $request->getProject());
        if (! $repository->canBeDeleted()) {
            $this->redirect($request->getProject()->getID());
        }
        $title = $GLOBALS['Language']->getText('global', 'Administration');

        $token = $this->generateTokenDeletion($request->getProject(), $repository);

        $service->renderInPageRepositoryAdministration(
            $request,
            $repository->getName() . ' – ' . $title,
            'admin/repository_delete',
            new RepositoryDeletePresenter(
                $repository,
                $request->getProject(),
                $title,
                $token
            ),
            '',
            $repository,
        );
    }

    public function deleteRepository(HTTPRequest $request)
    {
        $project       = $request->getProject();
        $project_id    = $project->getID();
        $repository_id = $request->get('repo_id');

        if ($project_id === null || $repository_id === null || $repository_id === false || $project_id === false) {
            $GLOBALS['Response']->addFeedback(Feedback::ERROR, 'actions_params_error');

            return false;
        }

        $repository = $this->repository_manager->getByIdAndProject($repository_id, $project);
        if ($repository !== null) {
            $token = $this->generateTokenDeletion($project, $repository);
            $token->check();

            if ($repository->canBeDeleted()) {
                $this->repository_deleter->queueRepositoryDeletion($repository);

                $GLOBALS['Response']->addFeedback(
                    Feedback::INFO,
                    sprintf(dgettext('tuleap-svn', 'Repository \'%1$s\' will be removed in a few seconds'), $repository->getFullName())
                );
                $GLOBALS['Response']->addFeedback(
                    Feedback::INFO,
                    sprintf(dgettext('tuleap-svn', 'A repository backup of \'%1$s\' will be available in the backup directory %2$s'), $repository->getFullName(), $repository->getSystemBackupPath())
                );
                $GLOBALS['Response']->addFeedback(
                    Feedback::INFO,
                    sprintf(dgettext('tuleap-svn', 'There is an event in queue for repository \'%1$s\' deletion, it will be processed in one minute or two. Please be patient!'), $repository->getFullName())
                );
            } else {
                $this->redirect($project_id);

                return false;
            }
        } else {
            $GLOBALS['Response']->addFeedback(
                Feedback::ERROR,
                dgettext('tuleap-svn', 'The repository does not exist')
            );
        }
        $this->redirect($project_id);
    }

    private function generateTokenDeletion(Project $project, Repository $repository)
    {
        return new CSRFSynchronizerToken(SVN_BASE_URL . '/?' . http_build_query(
            [
                'group_id' => $project->getID(),
                'repo_id' => $repository->getId(),
                'action' => 'delete-repository',
            ]
        ));
    }

    private function redirect($project_id)
    {
        $GLOBALS['Response']->redirect(SVN_BASE_URL . '/?' . http_build_query(
            ['group_id' => $project_id]
        ));
    }

    private function redirectOnDisplayNotification(HTTPRequest $request)
    {
        $GLOBALS['Response']->redirect(SVN_BASE_URL . '/?' . http_build_query(
            [
                'group_id' => $request->getProject()->getid(),
                'repo_id' => $request->get('repo_id'),
                'action' => 'display-mail-notification',
            ]
        ));
    }

    /**
     * @return RequestFromAutocompleter
     */
    private function getAutocompleter(Project $project, InvalidEntryInAutocompleterCollection $invalid_entries, $emails)
    {
        $autocompleter = new RequestFromAutocompleter(
            $invalid_entries,
            new Rule_Email(),
            $this->user_manager,
            $this->ugroup_manager,
            $this->user_manager->getCurrentUser(),
            $project,
            $emails
        );
        return $autocompleter;
    }

    private function addFeedbackUsersNotAdded($users_not_added)
    {
        $GLOBALS['Response']->addFeedback(
            Feedback::WARN,
            sprintf(
                dngettext(
                    'tuleap-svn',
                    "User '%s' couldn't be added.",
                    "Users '%s' couldn't be added.",
                    count($users_not_added)
                ),
                implode("' ,'", $users_not_added)
            )
        );
    }

    private function addFeedbackUgroupsNotAdded($ugroups_not_added)
    {
        $GLOBALS['Response']->addFeedback(
            Feedback::WARN,
            sprintf(
                dngettext(
                    'tuleap-svn',
                    "Group '%s' couldn't be added.",
                    "Groups '%s' couldn't be added.",
                    count($ugroups_not_added)
                ),
                implode("' ,'", $ugroups_not_added)
            )
        );
    }

    private function addFeedbackPathError()
    {
        $GLOBALS['Response']->addFeedback(
            Feedback::ERROR,
            dgettext('tuleap-svn', 'The given path is not valid')
        );
    }
}
