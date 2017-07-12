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

    public function __construct(
        Dao $dao,
        SystemEventManager $system_event_manager,
        ProjectHistoryDao $history_dao,
        SvnPermissionManager $permissions_manager,
        HookConfigUpdator $hook_config_updator,
        ProjectHistoryFormatter $project_history_formatter
    ) {
        $this->dao                       = $dao;
        $this->system_event_manager      = $system_event_manager;
        $this->history_dao               = $history_dao;
        $this->permissions_manager       = $permissions_manager;
        $this->hook_config_updator       = $hook_config_updator;
        $this->project_history_formatter = $project_history_formatter;
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

        return $this->createWithoutUserAdminCheck($svn_repository);
    }

    /**
     * @return SystemEvent
     *
     * @throws CannotCreateRepositoryException
     * @throws RepositoryNameIsInvalidException
     */
    public function createWithoutUserAdminCheck(Repository $svn_repository)
    {
        $svn_repository = $this->createRepository($svn_repository);
        $this->logCreation($svn_repository);

        return $this->sendEvent($svn_repository);
    }

    /**
     * @return SystemEvent
     */
    private function sendEvent(Repository $svn_repository)
    {
        $repo_event['system_path'] = $svn_repository->getSystemPath();
        $repo_event['project_id']  = $svn_repository->getProject()->getId();
        $repo_event['name']        = $svn_repository->getProject()->getUnixNameMixedCase() .
            "/" . $svn_repository->getName();

        return $this->system_event_manager->createEvent(
            'Tuleap\\Svn\\EventRepository\\' . SystemEvent_SVN_CREATE_REPOSITORY::NAME,
            implode(SystemEvent::PARAMETER_SEPARATOR, $repo_event),
            SystemEvent::PRIORITY_HIGH
        );
    }

    /**
     * @return SystemEvent
     */
    public function createWithSettings(Repository $repository, PFUser $user, array $commit_rules)
    {
        $this->checkUserHasAdministrationPermissions($repository, $user);
        $repository = $this->createRepository($repository);

        if ($commit_rules) {
            $this->hook_config_updator->initHookConfiguration($repository, $commit_rules);
            $this->logCreationWithCustomSettings($repository, $commit_rules);
        } else {
            $this->logCreation($repository);
        }

        return $this->sendEvent($repository);
    }

    private function logCreation(Repository $repository)
    {
        $this->history_dao->groupAddHistory(
            'svn_multi_repository_creation',
            $repository->getName(),
            $repository->getProject()->getID()
        );
    }

    private function logCreationWithCustomSettings(Repository $repository, array $commit_rules)
    {
        $history = $this->project_history_formatter->getFullHistory($repository, $commit_rules);

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
}
