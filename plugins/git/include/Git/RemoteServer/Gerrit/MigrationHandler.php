<?php
/**
 * Copyright (c) Enalean, 2016. All Rights Reserved.
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

namespace Tuleap\Git\RemoteServer\Gerrit;

use GitRepository;
use GitViews_RepoManagement_Pane_Gerrit;
use Git_SystemEventManager;
use Git_RemoteServer_GerritServerFactory;
use Git_Driver_Gerrit_GerritDriverFactory;
use ProjectHistoryDao;
use Git_Driver_Gerrit;
use Git_RemoteServer_GerritServer;
use Tuleap\Git\Exceptions\RemoteServerDoesNotExistException;
use Tuleap\Git\Exceptions\RepositoryNotMigratedException;
use Tuleap\Git\Exceptions\DeletePluginNotInstalledException;
use Tuleap\Git\Exceptions\RepositoryCannotBeMigratedException;
use PFUser;

class MigrationHandler {

    private $git_system_event_manager;
    private $gerrit_server_factory;
    private $driver_factory;
    private $history_dao;

    public function __construct(
        Git_SystemEventManager $git_system_event_manager,
        Git_RemoteServer_GerritServerFactory $gerrit_server_factory,
        Git_Driver_Gerrit_GerritDriverFactory $driver_factory,
        ProjectHistoryDao $history_dao
    ) {
        $this->git_system_event_manager = $git_system_event_manager;
        $this->gerrit_server_factory    = $gerrit_server_factory;
        $this->driver_factory           = $driver_factory;
        $this->history_dao              = $history_dao;
    }

    public function migrate(GitRepository $repository, $remote_server_id, $gerrit_template_id, PFUser $user) {
        if (! $repository->canMigrateToGerrit()) {
            throw new RepositoryCannotBeMigratedException();
        }

        $this->gerrit_server_factory->getServerById($remote_server_id);
        $this->git_system_event_manager->queueMigrateToGerrit($repository, $remote_server_id, $gerrit_template_id, $user);

        $this->history_dao->groupAddHistory(
            "git_repo_to_gerrit",
            $repository->getName(),
            $repository->getProjectId()
        );
    }

    /**
     * @throws RepositoryNotMigratedException
     * @throws DeletePluginNotInstalledException
     */
    public function disconnect(GitRepository $repository, $disconnect_option) {
        if (! $repository->isMigratedToGerrit()) {
            throw new RepositoryNotMigratedException();
        }

        try {
            $server = $this->getRepositoryServer($repository);
            $driver = $this->driver_factory->getDriver($server);
            $this->checkDisconnectOptionUsable($driver, $server, $disconnect_option);
            $this->disconnectFromGerritWithOption($repository, $driver, $disconnect_option);
        } catch (RemoteServerDoesNotExistException $exception) {
            $this->disconnectFromGerrit($repository);
        }
    }

    private function disconnectFromGerritWithOption(
        GitRepository $repository,
        Git_Driver_Gerrit $driver,
        $disconnect_option
    ) {
        $this->disconnectFromGerrit($repository);

        switch($disconnect_option) {
            case GitViews_RepoManagement_Pane_Gerrit::OPTION_DELETE_GERRIT_PROJECT:
                $this->git_system_event_manager->queueRemoteProjectDeletion($repository, $driver);

                $this->history_dao->groupAddHistory(
                    "git_disconnect_gerrit_delete",
                    $repository->getName(),
                    $repository->getProjectId()
                );

                break;
            case GitViews_RepoManagement_Pane_Gerrit::OPTION_READONLY_GERRIT_PROJECT:
                $this->git_system_event_manager->queueRemoteProjectReadOnly($repository, $driver);

                $this->history_dao->groupAddHistory(
                    "git_disconnect_gerrit_read_only",
                    $repository->getName(),
                    $repository->getProjectId()
                );

                break;
        }
    }

    private function disconnectFromGerrit(GitRepository $repository) {
        $repository->getBackend()->disconnectFromGerrit($repository);
        $this->git_system_event_manager->queueRepositoryUpdate($repository);
    }

    private function getRepositoryServer(GitRepository $repository) {
        $server = $this->gerrit_server_factory->getServerById($repository->getRemoteServerId());
        if (! $server) {
            throw new RemoteServerDoesNotExistException();
        }

        return $server;
    }

    private function checkDisconnectOptionUsable(
        Git_Driver_Gerrit $driver,
        Git_RemoteServer_GerritServer $server,
        $disconnect_option
    ) {
        if (! $driver->isDeletePluginEnabled($server) &&
            $disconnect_option === GitViews_RepoManagement_Pane_Gerrit::OPTION_DELETE_GERRIT_PROJECT
        ) {
            throw new DeletePluginNotInstalledException();
        }
    }

}
