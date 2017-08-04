<?php
/**
 * Copyright (c) Enalean, 2017. All Rights Reserved.
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

namespace Tuleap\Svn\Repository;

use PFUser;
use ProjectHistoryDao;
use SystemEvent;
use SystemEventManager;
use Tuleap\Svn\AccessControl\AccessFileHistoryCreator;
use Tuleap\Svn\Admin\ImmutableTagCreator;
use Tuleap\Svn\Dao;
use Tuleap\Svn\EventRepository\SystemEvent_SVN_CREATE_REPOSITORY;
use Tuleap\Svn\Repository\Exception\CannotCreateRepositoryException;
use Tuleap\Svn\Repository\Exception\RepositoryNameIsInvalidException;
use Tuleap\Svn\Repository\Exception\UserIsNotSVNAdministratorException;
use Tuleap\Svn\SvnPermissionManager;

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

    public function __construct(
        Dao $dao,
        SystemEventManager $system_event_manager,
        ProjectHistoryDao $history_dao,
        SvnPermissionManager $permissions_manager,
        HookConfigUpdator $hook_config_updator,
        ProjectHistoryFormatter $project_history_formatter,
        ImmutableTagCreator $immutable_tag_creator,
        AccessFileHistoryCreator $access_file_history_creator
    ) {
        $this->dao                         = $dao;
        $this->system_event_manager        = $system_event_manager;
        $this->history_dao                 = $history_dao;
        $this->permissions_manager         = $permissions_manager;
        $this->hook_config_updator         = $hook_config_updator;
        $this->project_history_formatter   = $project_history_formatter;
        $this->immutable_tag_creator       = $immutable_tag_creator;
        $this->access_file_history_creator = $access_file_history_creator;
    }

    /**
     * @return SystemEvent
     *
     * @throws CannotCreateRepositoryException
     * @throws RepositoryNameIsInvalidException
     * @throws UserIsNotSVNAdministratorException
     */
    public function create(Repository $svn_repository, PFUser $user)
    {
        $this->checkUserHasAdministrationPermissions($svn_repository, $user);

        return $this->createWithoutUserAdminCheck($svn_repository, $user);
    }

    /**
     * @return SystemEvent
     *
     * @throws CannotCreateRepositoryException
     * @throws RepositoryNameIsInvalidException
     */
    public function createWithoutUserAdminCheck(Repository $svn_repository, PFUser $committer)
    {
        $svn_repository = $this->createRepository($svn_repository);
        $this->logCreation($svn_repository);

        $initial_repository_layout = array();

        return $this->sendEvent($svn_repository, $committer, $initial_repository_layout);
    }

    /**
     * @return SystemEvent
     */
    private function sendEvent(Repository $svn_repository, PFUser $committer, array $initial_repository_layout)
    {
        $repo_event['system_path']    = $svn_repository->getSystemPath();
        $repo_event['project_id']     = $svn_repository->getProject()->getId();
        $repo_event['name']           = $svn_repository->getProject()->getUnixNameMixedCase() . "/" . $svn_repository->getName();
        $repo_event['repository_id']  = $svn_repository->getId();
        $repo_event['initial_layout'] = $initial_repository_layout;
        $repo_event['user_id']        = $committer->getId();

        return $this->system_event_manager->createEvent(
            'Tuleap\\Svn\\EventRepository\\' . SystemEvent_SVN_CREATE_REPOSITORY::NAME,
            SystemEvent_SVN_CREATE_REPOSITORY::serializeParameters($repo_event),
            SystemEvent::PRIORITY_HIGH
        );
    }

    /**
     * @return SystemEvent
     */
    public function createWithSettings(Repository $repository, PFUser $user, Settings $settings, array $initial_repository_layout)
    {
        $this->checkUserHasAdministrationPermissions($repository, $user);
        $repository = $this->createRepository($repository);

        if ($settings->hasSettings()) {
            $this->addSettingsToRepository($repository, $settings);
            $this->logCreationWithCustomSettings($repository);
        } else {
            $this->logCreation($repository);
        }

        return $this->sendEvent($repository, $user, $initial_repository_layout);
    }

    private function logCreation(Repository $repository)
    {
        $this->history_dao->groupAddHistory(
            'svn_multi_repository_creation',
            $repository->getName(),
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
     * @param Repository $repository
     * @param PFUser    $user
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
     * @param Repository $repository
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
     * @param Repository $svn_repository
     *
     * @return Repository
     * @throws CannotCreateRepositoryException
     */
    private function createRepository(Repository $svn_repository)
    {
        $this->checkRepositoryName($svn_repository);
        $id = $this->dao->create($svn_repository);
        if (! $id) {
            throw new CannotCreateRepositoryException($GLOBALS['Language']->getText('plugin_svn', 'update_error'));
        }

        $svn_repository->setId($id);

        return $svn_repository;
    }

    /**
     * @param Repository $repository
     * @param Settings   $settings
     */
    private function addSettingsToRepository(Repository $repository, Settings $settings)
    {
        $commit_rules = $settings->getCommitRules();
        if ($commit_rules) {
            $this->hook_config_updator->initHookConfiguration($repository, $commit_rules);

            $this->project_history_formatter->addCommitRuleHistory($commit_rules);
        }

        $immutable_tag = $settings->getImmutableTag();
        if ($immutable_tag) {
            $this->immutable_tag_creator->saveWithoutHistory(
                $repository,
                $immutable_tag->getPathsAsString(),
                $immutable_tag->getWhitelistAsString()
            );

            $this->project_history_formatter->addImmutableTagHistory($immutable_tag);
        }

        $access_file = $settings->getAccessFileContent();
        if ($access_file) {
            $this->access_file_history_creator->storeInDB($repository, $access_file, time());

            $this->project_history_formatter->addAccessFileContentHistory($access_file);
        }
    }
}
