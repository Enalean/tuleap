<?php
/**
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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

namespace Tuleap\Git\Repository;

use EventManager;
use Git_Backend_Gitolite;
use Git_Mirror_MirrorDataMapper;
use GitPermissionsManager;
use GitRepositoryFactory;
use GitRepositoryManager;
use PFUser;
use Project;
use ProjectHistoryDao;
use Tuleap\Git\CIToken\Manager;
use Tuleap\Git\Events\AfterRepositoryCreated;
use Tuleap\Git\Permissions\FineGrainedPermissionReplicator;
use Tuleap\Git\Permissions\HistoryValueFormatter;

class RepositoryCreator
{
    /**
     * @var GitRepositoryFactory
     */
    private $factory;
    /**
     * @var Git_Backend_Gitolite
     */
    private $backend_gitolite;
    /**
     * @var Git_Mirror_MirrorDataMapper
     */
    private $mirror_data_mapper;
    /**
     * @var GitRepositoryManager
     */
    private $manager;
    /**
     * @var GitPermissionsManager
     */
    private $git_permissions_manager;
    /**
     * @var FineGrainedPermissionReplicator
     */
    private $fine_grained_replicator;
    /**
     * @var ProjectHistoryDao
     */
    private $history_dao;
    /**
     * @var HistoryValueFormatter
     */
    private $history_value_formatter;
    /**
     * @var Manager
     */
    private $ci_token_manager;
    /**
     * @var EventManager
     */
    private $event_manager;

    public function __construct(
        GitRepositoryFactory $factory,
        Git_Backend_Gitolite $backend_gitolite,
        Git_Mirror_MirrorDataMapper $mirror_data_mapper,
        GitRepositoryManager $manager,
        GitPermissionsManager $git_permissions_manager,
        FineGrainedPermissionReplicator $fine_grained_replicator,
        ProjectHistoryDao $history_dao,
        HistoryValueFormatter $history_value_formatter,
        Manager $ci_token_manager,
        EventManager $event_manager
    ) {
        $this->factory                 = $factory;
        $this->backend_gitolite        = $backend_gitolite;
        $this->mirror_data_mapper      = $mirror_data_mapper;
        $this->manager                 = $manager;
        $this->git_permissions_manager = $git_permissions_manager;
        $this->fine_grained_replicator = $fine_grained_replicator;
        $this->history_dao             = $history_dao;
        $this->history_value_formatter = $history_value_formatter;
        $this->ci_token_manager        = $ci_token_manager;
        $this->event_manager           = $event_manager;
    }

    /**
     * @param         $repository_name
     *
     * @return \GitRepository
     * @throws GitRepositoryNameIsInvalidException
     * @throws \GitDaoException
     * @throws \GitRepositoryAlreadyExistsException
     */
    public function create(Project $project, PFUser $creator, $repository_name)
    {
        $repository = $this->factory->buildRepository(
            $project,
            $repository_name,
            $creator,
            $this->backend_gitolite
        );

        $default_mirrors = $this->mirror_data_mapper->getDefaultMirrorIdsForProject($project);
        if (! $default_mirrors) {
            $default_mirrors = [];
        }

        $this->manager->create($repository, $this->backend_gitolite, $default_mirrors);

        $this->backend_gitolite->savePermissions(
            $repository,
            $this->git_permissions_manager->getDefaultPermissions($project)
        );

        $this->fine_grained_replicator->replicateDefaultRegexpUsage($repository);
        $this->fine_grained_replicator->replicateDefaultPermissions(
            $repository
        );

        $event = new AfterRepositoryCreated($repository);
        $this->event_manager->processEvent($event);

        $this->history_dao->groupAddHistory(
            "git_repo_create",
            $repository->getName(),
            $project->getID()
        );

        $this->history_dao->groupAddHistory(
            'perm_granted_for_git_repository',
            $this->history_value_formatter->formatValueForRepository($repository),
            $project->getID(),
            [$repository->getName()]
        );

        $this->ci_token_manager->generateNewTokenForRepository($repository);

        return $repository;
    }
}
