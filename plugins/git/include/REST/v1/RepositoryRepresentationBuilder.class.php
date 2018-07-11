<?php
/**
 * Copyright (c) Enalean, 2014 - 2018. All Rights Reserved.
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

namespace Tuleap\Git\REST\v1;

use DateTime;
use Git_LogDao;
use Git_RemoteServer_GerritServerFactory;
use Git_RemoteServer_NotFoundException;
use GitPermissionsManager;
use GitRepository;
use PFUser;
use Tuleap\Git\Repository\AdditionalInformationRepresentationRetriever;

class RepositoryRepresentationBuilder
{

    /**
     * @var Git_RemoteServer_GerritServerFactory
     */
    private $gerrit_server_factory;

    /**
     * @var GitPermissionsManager
     */
    private $permissions_manger;

    /**
     * @var Git_LogDao
     */
    private $log_dao;

    /**
     * @var \EventManager
     */
    private $event_manager;

    /**
     * @var array
     */
    private $last_push_date;
    private $remote_server;

    public function __construct(
        GitPermissionsManager $permissions_manger,
        Git_RemoteServer_GerritServerFactory $gerrit_server_factory,
        Git_LogDao $log_dao,
        \EventManager $event_manager
    ) {
        $this->permissions_manger    = $permissions_manger;
        $this->gerrit_server_factory = $gerrit_server_factory;
        $this->log_dao               = $log_dao;
        $this->event_manager         = $event_manager;
    }

    /**
     * @param PFUser $user
     * @param GitRepository[] $repositories
     * @param $fields
     * @return \Generator
     */
    public function buildWithList(PFUser $user, array $repositories, $fields)
    {
        if (count($repositories) > 0) {
            $this->cacheRepositoriesMetadata($repositories);
            foreach ($repositories as $repository) {
                yield $this->build($user, $repository, $fields);
            }
        }
    }

    private function cacheRepositoriesMetadata(array $repositories)
    {
        $repo_ids = array_map(
            function (GitRepository $repository) {
                $this->last_push_date[$repository->getId()] = -1;
                $this->remote_server[$repository->getRemoteServerId()] = -1;
                return $repository->getId();
            },
            $repositories
        );

        $this->cacheGerritServers();
        $this->cacheLastAccessDates($repo_ids);
    }

    private function cacheGerritServers()
    {
        if (count($this->remote_server) > 0) {
            foreach ($this->gerrit_server_factory->getServers() as $remote) {
                $this->remote_server[$remote->getId()] = $remote;
            }
        }
    }

    private function cacheLastAccessDates(array $repo_ids)
    {
        foreach ($this->log_dao->getLastPushForRepositories($repo_ids) as $row) {
            $this->last_push_date[$row['repository_id']] = $row['push_date'];
        }
    }

    /**
     *
     * @param PFUser $user
     * @param GitRepository $repository
     * @param string $fields
     *
     * @return GitRepositoryRepresentation
     */
    public function build(PFUser $user, GitRepository $repository, $fields)
    {
        $server_representation = $this->getGerritServerRepresentation($repository);

        $additional_information = new AdditionalInformationRepresentationRetriever($repository);
        $this->event_manager->processEvent($additional_information);

        $repository_representation = new GitRepositoryRepresentation();
        $last_update_date          = $this->getLastUpdateDate($repository);
        $repository_representation->build(
            $repository,
            $server_representation,
            $last_update_date,
            $additional_information->getAdditionalInformation()
        );

        if ($fields == GitRepositoryRepresentation::FIELDS_ALL && $this->permissions_manger->userIsGitAdmin(
            $user,
            $repository->getProject()
        )) {
            $permission_representation = new GitRepositoryPermissionRepresentation();
            $permission_representation->build($repository);

            $repository_representation->permissions = $permission_representation;
        }

        return $repository_representation;
    }

    /**
     * @return GerritServerRepresentation | null
     */
    private function getGerritServerRepresentation(GitRepository $repository) {
        $remote_server_id = $repository->getRemoteServerId();
        if (! $remote_server_id) {
            return null;
        }

        if ($this->remote_server[$remote_server_id]) {
            $server_representation = new GerritServerRepresentation();
            $server_representation->build($this->remote_server[$remote_server_id]);
            return $server_representation;
        }

        try {
            $server = $this->gerrit_server_factory->getServerById($remote_server_id);
            $server_representation = new GerritServerRepresentation();
            $server_representation->build($server);

            return $server_representation;
        } catch (Git_RemoteServer_NotFoundException $ex) {
            return null;
        }
    }

    /**
     * @param GitRepository $repository
     * @return string
     */
    private function getLastUpdateDate(GitRepository $repository)
    {
        if (! isset($this->last_push_date[$repository->getId()])) {
            $row = $this->log_dao->getLastPushForRepository($repository->getId());
            $this->last_push_date[$repository->getId()] = $row['push_date'];
        }
        if (! $this->last_push_date[$repository->getId()] || $this->last_push_date[$repository->getId()] === -1) {
            $creation_date = new DateTime($repository->getCreationDate());
            return $creation_date->getTimestamp();
        }
        return $this->last_push_date[$repository->getId()];
    }
}
