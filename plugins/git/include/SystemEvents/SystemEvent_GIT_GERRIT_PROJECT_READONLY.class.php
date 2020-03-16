<?php
/**
 * Copyright Enalean (c) 2011, 2012, 2013, 2014. All rights reserved.
 *
 * Tuleap and Enalean names and logos are registrated trademarks owned by
 * Enalean SAS. All other trademarks or names are properties of their respective
 * owners.
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

class SystemEvent_GIT_GERRIT_PROJECT_READONLY extends SystemEvent
{

    public const NAME = 'GIT_GERRIT_PROJECT_READONLY';

    /**
     * @var GitRepositoryFactory
     */
    private $repository_factory;

    /**
     * @var Git_RemoteServer_GerritServerFactory
     */
    private $server_factory;

    /**
     * @var Git_Driver_Gerrit_GerritDriverFactory
     */
    private $driver_factory;

    public function injectDependencies(
        GitRepositoryFactory $repository_factory,
        Git_RemoteServer_GerritServerFactory $gerrit_server_factory,
        Git_Driver_Gerrit_GerritDriverFactory $driver_factory
    ) {
        $this->repository_factory = $repository_factory;
        $this->server_factory     = $gerrit_server_factory;
        $this->driver_factory     = $driver_factory;
    }

    public function process()
    {
        $parameters   = $this->getParametersAsArray();

        if (! empty($parameters[0])) {
            $repository_id = (int) $parameters[0];
        } else {
            $this->error('Missing argument repository id');
            return false;
        }

        if (! empty($parameters[1])) {
            $remote_server_id = (int) $parameters[1];
        } else {
            $this->error('Missing argument remote server id');
            return false;
        }

        $repository = $this->repository_factory->getRepositoryById($repository_id);
        if (! $repository) {
            $this->error('Failed to find repository ' . $repository_id);
            return false;
        }

        $server  = $this->server_factory->getServerById($remote_server_id);
        if (! $server) {
            $this->error('Failed to find server ' . $remote_server_id);
            return false;
        }

        $project = $repository->getProject();
        if (! $project) {
            $this->error('Failed to find project ' . $repository->getProject());
            return false;
        }

        return $this->makeGerritProjectReadOnly($repository, $server, $project);
    }

    private function makeGerritProjectReadOnly(
        GitRepository $repository,
        Git_RemoteServer_GerritServer $server,
        Project $project
    ) {
        try {
            $this->driver_factory->getDriver($server)->makeGerritProjectReadOnly($server, $project->getUnixName() . '/' . $repository->getName());
        } catch (Exception $e) {
            $this->error($e->getMessage() . $e->getTraceAsString());
            return false;
        }

        $this->done();
        return true;
    }

    public function verbalizeParameters($with_link)
    {
        return $this->parameters;
    }
}
