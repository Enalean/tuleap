<?php
/**
 * Copyright (c) Enalean, 2012 - 2018. All Rights Reserved.
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

class Git_RemoteServer_GerritServerFactory
{

    /** @var Git_RemoteServer_Dao */
    private $dao;

    /** @var GitDao */
    private $git_dao;

    /** @var Git_SystemEventManager */
    private $system_event_manager;

    /** @var ProjectManager */
    private $project_manager;

    public function __construct(Git_RemoteServer_Dao $dao, GitDao $git_dao, Git_SystemEventManager $system_event_manager, ProjectManager $project_manager)
    {
        $this->dao     = $dao;
        $this->git_dao = $git_dao;
        $this->system_event_manager = $system_event_manager;
        $this->project_manager = $project_manager;
    }

    /**
     *
     * @return Git_RemoteServer_GerritServer
     */
    public function getServer(GitRepository $repository)
    {
        $id  = $repository->getRemoteServerId();
        return $this->getServerById($id);
    }

    /**
     *
     * @param int $id
     * @return Git_RemoteServer_GerritServer
     * @throws Git_RemoteServer_NotFoundException
     */
    public function getServerById($id)
    {
        $row = $this->dao->getById($id);
        if ($row) {
            return $this->instantiateFromRow($row);
        }
        throw new Git_RemoteServer_NotFoundException($id);
    }

    /**
     * @return Git_RemoteServer_GerritServer[]
     */
    public function getServers()
    {
        $servers = array();
        foreach ($this->dao->searchAll() as $row) {
            $servers[$row['id']] = $this->instantiateFromRow($row);
        }

        return $this->getOrderedServerList($servers);
    }

    public function getAllServersNoSort()
    {
        foreach ($this->dao->searchAll() as $row) {
            yield $this->instantiateFromRow($row);
        }
    }

    /**
     * @return Git_RemoteServer_GerritServer[]
     */
    public function getUnrestrictedServers()
    {
        $servers = array();
        foreach ($this->dao->searchAllUnrestricted() as $row) {
            $servers[$row['id']] = $this->instantiateFromRow($row);
        }

        return $this->getOrderedServerList($servers);
    }

    /**
     * @return Git_RemoteServer_GerritServer[]
     */
    public function getAvailableServersForProject(Project $project)
    {
        $servers = array();
        foreach ($this->dao->searchAvailableServersForProject($project->getID()) as $row) {
            $servers[$row['id']] = $this->instantiateFromRow($row);
        }

        return $this->getOrderedServerList($servers);
    }

    private function getOrderedServerList(array $servers)
    {
        ksort($servers);
        return $servers;
    }

    /**
     * @return Git_RemoteServer_GerritServer[]
     */
    public function getServersForProject(Project $project)
    {
        $servers = array();
        foreach ($this->dao->searchAllByProjectId($project->getID()) as $row) {
            $servers[$row['id']] = $this->instantiateFromRow($row);
        }
        foreach ($this->project_manager->getChildProjects($project->getID()) as $child) {
            // don't use array_merge, it will nuke the keys
            $servers = $servers + $this->getServersForProject($child);
        }
        return $servers;
    }

    public function getServersForUGroup(ProjectUGroup $ugroup)
    {
        $servers = array();
        foreach ($this->dao->searchAllByUGroupId($ugroup->getProjectId(), $ugroup->getId()) as $row) {
            $servers[$row['id']] = $this->instantiateFromRow($row);
        }
        return $servers;
    }

    public function save(Git_RemoteServer_GerritServer $server)
    {
        $id = $this->dao->save(
            $server->getId(),
            $server->getHost(),
            $server->getSSHPort(),
            $server->getHTTPPort(),
            $server->getLogin(),
            $server->getIdentityFile(),
            $server->getReplicationKey(),
            $server->usesSSL(),
            $server->getGerritVersion(),
            $server->getHTTPPassword()
        );
        if ($server->getId() == 0) {
            $server->setId($id);
        }
        $this->system_event_manager->queueGerritReplicationKeyUpdate($server);
    }

    public function updateReplicationPassword(Git_RemoteServer_GerritServer $server)
    {
        $this->dao->updateReplicationPassword($server->getId(), $server->getReplicationPassword());
    }

    public function delete(Git_RemoteServer_GerritServer $server)
    {
        if (! $this->isServerUsed($server)) {
            $this->dao->delete($server->getId());
            $this->system_event_manager->queueGerritReplicationKeyUpdate($server);
        }
    }

    /**
     *
     * @return bool
     */
    public function isServerUsed(Git_RemoteServer_GerritServer $server)
    {
        return $this->git_dao->isRemoteServerUsed($server->getId());
    }

    /**
     * @return bool
     */
    public function isServerUsedInProject(Git_RemoteServer_GerritServer $server, $project_id)
    {
        return $this->git_dao->isRemoteServerUsedInProject($server->getId(), $project_id);
    }

    /**
     *
     * @return Git_RemoteServer_GerritServer[]
     */
    public function getRemoteServersForUser(PFUser $user)
    {
        $gerrit_servers = [];

        foreach ($this->dao->searchAllRemoteServersForUserId($user->getId()) as $row) {
            $gerrit_servers[] = $this->instantiateFromRow($row);
        }

        return $gerrit_servers;
    }

    /**
     *
     * @param array $row
     * @return Git_RemoteServer_GerritServer
     */
    public function instantiateFromRow(array $row)
    {
        return new Git_RemoteServer_GerritServer(
            $row['id'],
            $row['host'],
            $row['ssh_port'],
            $row['http_port'],
            $row['login'],
            $row['identity_file'],
            $row['ssh_key'],
            $row['use_ssl'],
            $row['gerrit_version'],
            $row['http_password'],
            $row['replication_password']
        );
    }

    /**
     * Check if gerrit is enabled and has remotes set up.
     * @return bool
     */
    public function hasRemotesSetUp()
    {
        return count($this->dao->searchAll()) > 0;
    }
}
