<?php
/**
 * Copyright (c) Enalean, 2017 - Present. All Rights Reserved.
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

namespace Tuleap\SVN\Repository;

use PFUser;
use ProjectHistoryDao;
use SystemEvent;
use SystemEventManager;
use Tuleap\SVN\AccessControl\AccessFileHistory;
use Tuleap\SVN\AccessControl\AccessFileHistoryCreator;
use Tuleap\SVN\Admin\ImmutableTagCreator;
use Tuleap\SVN\Admin\ImmutableTagListTooBigException;
use Tuleap\SVN\Admin\MailNotificationManager;
use Tuleap\SVN\Dao;
use Tuleap\SVN\Events\SystemEvent_SVN_CREATE_REPOSITORY;
use Tuleap\SVN\Events\SystemEvent_SVN_IMPORT_CORE_REPOSITORY;
use Tuleap\SVN\Repository\Exception\CannotCreateRepositoryException;
use Tuleap\SVN\Repository\Exception\RepositoryNameIsInvalidException;
use Tuleap\SVN\Repository\Exception\UserIsNotSVNAdministratorException;
use Tuleap\SVN\SvnPermissionManager;

class RepositoryCreator
{
    /**
     * @var SvnPermissionManager
     */
    private $permissions_manager;
    /**
     * @var Dao
     */
    private $dao;
    /**
     * @var SystemEventManager
     */
    private $system_event_manager;
    /**
     * @var ProjectHistoryDao
     */
    private $history_dao;
    /**
     * @var HookConfigUpdator
     */
    private $hook_config_updator;
    /**
     * @var ProjectHistoryFormatter
     */
    private $project_history_formatter;
    /**
     * @var ImmutableTagCreator
     */
    private $immutable_tag_creator;
    /**
     * @var AccessFileHistoryCreator
     */
    private $access_file_history_creator;
    /**
     * @var MailNotificationManager
     */
    private $mail_notification_manager;

    public function __construct(
        Dao $dao,
        SystemEventManager $system_event_manager,
        ProjectHistoryDao $history_dao,
        SvnPermissionManager $permissions_manager,
        HookConfigUpdator $hook_config_updator,
        ProjectHistoryFormatter $project_history_formatter,
        ImmutableTagCreator $immutable_tag_creator,
        AccessFileHistoryCreator $access_file_history_creator,
        MailNotificationManager $mail_notification_manager,
    ) {
        $this->dao                         = $dao;
        $this->system_event_manager        = $system_event_manager;
        $this->history_dao                 = $history_dao;
        $this->permissions_manager         = $permissions_manager;
        $this->hook_config_updator         = $hook_config_updator;
        $this->project_history_formatter   = $project_history_formatter;
        $this->immutable_tag_creator       = $immutable_tag_creator;
        $this->access_file_history_creator = $access_file_history_creator;
        $this->mail_notification_manager   = $mail_notification_manager;
    }

    /**
     * @throws CannotCreateRepositoryException
     * @throws UserIsNotSVNAdministratorException
     */
    public function create(Repository $svn_repository, PFUser $user): ?SystemEvent_SVN_CREATE_REPOSITORY
    {
        $this->checkUserHasAdministrationPermissions($svn_repository, $user);
        $copy_from_core = false;

        return $this->createWithoutUserAdminCheck($svn_repository, $user, $copy_from_core);
    }

    /**
     * @throws CannotCreateRepositoryException
     */
    public function createWithoutUserAdminCheck(Repository $svn_repository, PFUser $committer, $copy_from_core): ?SystemEvent_SVN_CREATE_REPOSITORY
    {
        $svn_repository = $this->createRepository($svn_repository);
        $this->logCreation($svn_repository);

        $initial_repository_layout = [];

        return $this->sendEvent($svn_repository, $committer, $initial_repository_layout, $copy_from_core);
    }

    private function sendEvent(
        Repository $svn_repository,
        PFUser $committer,
        array $initial_repository_layout,
        $copy_from_core,
    ): ?SystemEvent_SVN_CREATE_REPOSITORY {
        $repo_event['system_path']    = $svn_repository->getSystemPath();
        $repo_event['project_id']     = $svn_repository->getProject()->getId();
        $repo_event['name']           = $svn_repository->getProject()->getUnixNameMixedCase() . "/" .
            $svn_repository->getName();
        $repo_event['repository_id']  = $svn_repository->getId();
        $repo_event['initial_layout'] = $initial_repository_layout;
        $repo_event['user_id']        = $committer->getId();
        $repo_event['copy_from_core'] = $copy_from_core;

        $event = $this->system_event_manager->createEvent(
            SystemEvent_SVN_CREATE_REPOSITORY::class,
            SystemEvent_SVN_CREATE_REPOSITORY::serializeParameters($repo_event),
            SystemEvent::PRIORITY_HIGH
        );
        assert($event instanceof SystemEvent_SVN_CREATE_REPOSITORY || $event === null);
        return $event;
    }

    /**
     * @throws ImmutableTagListTooBigException
     * @throws CannotCreateRepositoryException
     * @throws UserIsNotSVNAdministratorException
     */
    public function createWithSettings(
        Repository $repository,
        PFUser $user,
        Settings $settings,
        array $initial_repository_layout,
        $copy_from_core,
    ): ?SystemEvent_SVN_CREATE_REPOSITORY {
        $this->checkUserHasAdministrationPermissions($repository, $user);
        $repository = $this->createRepository($repository);

        if ($settings->hasSettings()) {
            $this->addSettingsToRepository($repository, $settings);
            $this->logCreationWithCustomSettings($repository);
        } else {
            $this->logCreation($repository);
        }

        return $this->sendEvent($repository, $user, $initial_repository_layout, $copy_from_core);
    }

    /**
     * @throws CannotCreateRepositoryException
     * @throws UserIsNotSVNAdministratorException
     */
    public function importCoreRepository(Repository $repository, PFUser $user, Settings $settings): void
    {
        $this->checkUserHasAdministrationPermissions($repository, $user);

        $repository = $this->createRepository($repository);
        $this->addSettingsToRepository($repository, $settings);
        $this->logCreationWithCustomSettings($repository);

        SystemEvent_SVN_IMPORT_CORE_REPOSITORY::queueEvent($this->system_event_manager, $repository);
    }

    private function logCreation(Repository $repository)
    {
        $this->history_dao->groupAddHistory(
            'svn_multi_repository_creation',
            "Repository: " . $repository->getName(),
            $repository->getProject()->getID()
        );
    }

    private function logCreationWithCustomSettings(Repository $repository)
    {
        $history = $this->project_history_formatter->getFullHistory($repository);

        $this->history_dao->groupAddHistory(
            'svn_multi_repository_creation_with_full_settings',
            $history,
            $repository->getProject()->getID()
        );
    }

    /**
     *
     * @throws UserIsNotSVNAdministratorException
     */
    private function checkUserHasAdministrationPermissions(Repository $repository, PFUser $user)
    {
        if (! $this->permissions_manager->isAdmin($repository->getProject(), $user)) {
            throw new UserIsNotSVNAdministratorException(
                dgettext('tuleap-svn', "User doesn't have permission to create a repository")
            );
        }
    }

    /**
     *
     * @throws RepositoryNameIsInvalidException
     */
    private function checkRepositoryName(Repository $repository)
    {
        $rule = new RuleName($repository->getProject(), $this->dao);
        if (! $rule->isValid($repository->getName())) {
            throw new RepositoryNameIsInvalidException($rule->getErrorMessage());
        }
    }

    /**
     *
     * @return Repository
     * @throws CannotCreateRepositoryException
     */
    private function createRepository(Repository $svn_repository)
    {
        $this->checkRepositoryName($svn_repository);
        $id = $this->dao->create($svn_repository);
        if (! $id) {
            throw new CannotCreateRepositoryException(dgettext('tuleap-svn', 'Unable to update Repository data'));
        }

        $svn_repository->setId($id);

        return $svn_repository;
    }

    /**
     * @throws \Tuleap\SVN\Admin\ImmutableTagListTooBigException
     */
    private function addSettingsToRepository(Repository $repository, Settings $settings)
    {
        $this->createCommitRules($repository, $settings);

        $this->createImmutableTags($repository, $settings);

        $access_file_history = $settings->getAccessFileHistory();
        if (count($access_file_history) > 0) {
            $this->createAccessAndAVersionOfFileHistoryWithoutCleaningContent($repository, $settings, $access_file_history);
        } else {
            $this->createLastVersionOfFileHistory($repository, $settings);
        }

        $this->createMailNotifications($settings);
    }

    private function createCommitRules(Repository $repository, Settings $settings): void
    {
        $commit_rules = $settings->getCommitRules();
        if ($commit_rules) {
            $this->hook_config_updator->initHookConfiguration($repository, $commit_rules);

            $this->project_history_formatter->addCommitRuleHistory($commit_rules);
        }
    }

    /**
     * @throws \Tuleap\SVN\Admin\ImmutableTagListTooBigException
     */
    private function createImmutableTags(Repository $repository, Settings $settings): void
    {
        $immutable_tag = $settings->getImmutableTag();
        if ($immutable_tag && count($immutable_tag->getPaths()) > 0) {
            $this->immutable_tag_creator->saveWithoutHistory(
                $repository,
                $immutable_tag->getPathsAsString(),
                $immutable_tag->getWhitelistAsString()
            );

            $this->project_history_formatter->addImmutableTagHistory($immutable_tag);
        }
    }

    /**
     * @param AccessFileHistory[]      $access_file_history
     * @throws \Tuleap\SVN\AccessControl\CannotCreateAccessFileHistoryException
     */
    private function createAccessAndAVersionOfFileHistoryWithoutCleaningContent(
        Repository $repository,
        Settings $settings,
        array $access_file_history,
    ) {
        foreach ($access_file_history as $history) {
            if ($settings->isAccessFileAlreadyPurged()) {
                $this->access_file_history_creator->storeInDBWithoutCleaningContent(
                    $repository,
                    $history->getContent(),
                    $history->getVersionDate()
                );
            } else {
                $this->access_file_history_creator->storeInDB(
                    $repository,
                    $history->getContent(),
                    $history->getVersionDate(),
                );
            }
            $this->project_history_formatter->addAccessFileContentHistory($history->getContent());
        }

        $this->access_file_history_creator->useAVersionWithHistoryWithoutUpdateSVNAccessFile(
            $repository,
            $settings->getUsedVersion()
        );
    }

    private function createLastVersionOfFileHistory(Repository $repository, Settings $settings)
    {
        $access_file = $settings->getAccessFileContent();
        if ($access_file) {
            $this->access_file_history_creator->storeInDB($repository, $access_file, time());

            $this->project_history_formatter->addAccessFileContentHistory($access_file);
        }
    }

    private function createMailNotifications(Settings $settings)
    {
        $mail_notifications = $settings->getMailNotification();
        if ($mail_notifications) {
            foreach ($mail_notifications as $notification) {
                $this->mail_notification_manager->create($notification);
            }

            $this->project_history_formatter->addNotificationHistory($mail_notifications);
        }
    }
}
