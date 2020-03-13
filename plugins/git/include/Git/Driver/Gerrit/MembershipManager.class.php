<?php
/**
 * Copyright (c) Enalean, 2013. All Rights Reserved.
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

require_once 'MembershipDao.class.php';
require_once 'MembershipCommand/AddUser.class.php';
require_once 'MembershipCommand/RemoveUser.class.php';
require_once 'MembershipCommand/AddBinding.class.php';
require_once 'MembershipCommand/RemoveBinding.class.php';

/**
 * I'm responsible of user group management on gerrit
 * -> create groups
 * -> adding and removing user and groups
 */
class Git_Driver_Gerrit_MembershipManager
{
    private $dao;
    private $driver_factory;
    private $gerrit_server_factory;
    private $logger;
    private $gerrit_user_manager;
    private $ugroup_manager;

    /** @var ProjectManager */
    private $project_manager;

    private $cache_groups = array();

    public function __construct(
        Git_Driver_Gerrit_MembershipDao $dao,
        Git_Driver_Gerrit_GerritDriverFactory $driver_factory,
        Git_Driver_Gerrit_UserAccountManager $gerrit_usermanager,
        Git_RemoteServer_GerritServerFactory $gerrit_server_factory,
        \Psr\Log\LoggerInterface $logger,
        UGroupManager $ugroup_manager,
        ProjectManager $project_manager
    ) {
        $this->dao                    = $dao;
        $this->driver_factory         = $driver_factory;
        $this->gerrit_user_manager    = $gerrit_usermanager;
        $this->gerrit_server_factory  = $gerrit_server_factory;
        $this->logger                 = $logger;
        $this->ugroup_manager         = $ugroup_manager;
        $this->project_manager        = $project_manager;
    }

    /**
     * Add a user into a user group
     *
     */
    public function addUserToGroup(PFUser $user, ProjectUGroup $ugroup)
    {
        $this->updateUserMembership(
            new Git_Driver_Gerrit_MembershipCommand_AddUser($this, $this->driver_factory, $this->gerrit_user_manager, $ugroup, $user)
        );
    }

    public function addUserToAllTheirGroups(PFUser $user)
    {
        $static_user_groups  = $this->ugroup_manager->getByUserId($user);
        $dynamic_user_groups = $this->getDynamicUgroupsForUser($user);

        $user_groups =  array_merge($static_user_groups, $dynamic_user_groups);
        foreach ($user_groups as $ugroup) {
            $this->updateUserMembership(
                new Git_Driver_Gerrit_MembershipCommand_AddUser($this, $this->driver_factory, $this->gerrit_user_manager, $ugroup, $user)
            );
        }
    }

    /**
     * @return ProjectUGroup[]
     */
    private function getDynamicUgroupsForUser(PFUser $user)
    {
        $project_ids = $user->getProjects();
        $ugroups     = array();

        foreach ($project_ids as $group_id) {
            $ugroups[] = new ProjectUGroup(array('ugroup_id' => ProjectUGroup::PROJECT_MEMBERS, 'group_id' => $group_id));

            if ($user->isAdmin($group_id)) {
                $ugroups[] = new ProjectUGroup(array('ugroup_id' => ProjectUGroup::PROJECT_ADMIN, 'group_id' => $group_id));
            }
        }

        return $ugroups;
    }

    /**
     * Remove a user from a user group
     *
     */
    public function removeUserFromGroup(PFUser $user, ProjectUGroup $ugroup)
    {
        $this->updateUserMembership(
            new Git_Driver_Gerrit_MembershipCommand_RemoveUser($this, $this->driver_factory, $this->gerrit_user_manager, $ugroup, $user)
        );
    }

    private function updateUserMembership(Git_Driver_Gerrit_MembershipCommand $command)
    {
        $this->runCommandOnServers(
            $this->gerrit_server_factory->getServersForUGroup($command->getUGroup()),
            $command
        );
    }

    /**
     * Bind $ugroup with $source_ugroup
     *
     */
    public function addUGroupBinding(ProjectUGroup $ugroup, ProjectUGroup $source_ugroup)
    {
        $this->updateUGroupBinding(
            new Git_Driver_Gerrit_MembershipCommand_AddBinding($this, $this->driver_factory, $ugroup, $source_ugroup)
        );
    }

    /**
     * Remove binding set on a user group
     *
     */
    public function removeUGroupBinding(ProjectUGroup $ugroup)
    {
        $this->updateUGroupBinding(
            new Git_Driver_Gerrit_MembershipCommand_RemoveBinding($this, $this->driver_factory, $ugroup)
        );
    }

    private function updateUGroupBinding(Git_Driver_Gerrit_MembershipCommand $command)
    {
        $this->runCommandOnServers(
            $this->gerrit_server_factory->getServersForUGroup($command->getUGroup()),
            $command
        );
    }

    /**
     * Create a user group on all gerrit servers a project use
     *
     */
    public function createGroupOnProjectsServers(ProjectUGroup $ugroup)
    {
        $remote_servers = $this->getServersForProjectAndItsChildren($ugroup->getProject());
        foreach ($remote_servers as $remote_server) {
            $this->createGroupForServer($remote_server, $ugroup);
        }
    }

    private function getServersForProjectAndItsChildren(Project $project)
    {
        $remote_servers = $this->gerrit_server_factory->getServersForProject($project);
        $children       = $this->project_manager->getChildProjects($project->getID());

        foreach ($children as $child) {
            $child_remote_servers = $this->gerrit_server_factory->getServersForProject($child);
            $remote_servers       = array_unique(array_merge($remote_servers, $child_remote_servers));
        }

        return $remote_servers;
    }

    /**
     * Create groups during repository migration
     *
     * @param ProjectUGroup[]                      $ugroups
     * @return ProjectUGroup[]
     */
    public function createArrayOfGroupsForServer(Git_RemoteServer_GerritServer $server, array $ugroups)
    {
        $migrated_ugroups = array();

        $need_flush = false;

        foreach ($ugroups as $ugroup) {
            if ($this->doesGroupExistOnServer($server, $ugroup)) {
                $migrated_ugroups[] = $ugroup;
            } elseif ($this->createGroupForServer($server, $ugroup)) {
                $need_flush = true;
                $migrated_ugroups[] = $ugroup;
            }
        }
        if ($need_flush) {
            $this->invalidGerritGroupsCaches($server);
        }

        return $migrated_ugroups;
    }

    private function invalidGerritGroupsCaches(Git_RemoteServer_GerritServer $server)
    {
        unset($this->cache_groups[$server->getId()]);
        $this->driver_factory->getDriver($server)->flushGerritCacheAccounts($server);
    }

    /**
     * Create a user group
     *
     *
     * @return String Name of group on gerrit server | false in case of error
     */
    public function createGroupForServer(Git_RemoteServer_GerritServer $server, ProjectUGroup $ugroup)
    {
        try {
            if ($this->ugroupCanBeMigrated($ugroup)) {
                $admin_ugroup = $this->getProjectAdminsUGroup($ugroup);
                $this->createProjectAdminsGroup($server, $admin_ugroup);
                return $this->createGroupOnServerWithoutCheckingUGroupValidity($server, $ugroup, $admin_ugroup);
            } else {
                return false;
            }
        } catch (Git_Driver_Gerrit_Exception $e) {
            $this->logger->error($e->getMessage());
        } catch (Exception $e) {
            $this->logger->error('Unknown error: ' . $e->getMessage());
        }
        return false;
    }

    private function createProjectAdminsGroup(Git_RemoteServer_GerritServer $server, ProjectUGroup $admin_ugroup)
    {
        if (! $this->doesGroupExistOnServer($server, $admin_ugroup)) {
            $this->createGroupOnServerWithoutCheckingUGroupValidity($server, $admin_ugroup, $admin_ugroup);
        }
    }

    private function getProjectAdminsUGroup(ProjectUGroup $ugroup)
    {
        return $this->ugroup_manager->getUGroup($ugroup->getProject(), ProjectUGroup::PROJECT_ADMIN);
    }

    private function createGroupOnServerWithoutCheckingUGroupValidity(
        Git_RemoteServer_GerritServer $server,
        ProjectUGroup $ugroup,
        ProjectUGroup $admin_ugroup
    ) {
        $admin_group_name  = $this->getFullyQualifiedUGroupName($admin_ugroup);
        $gerrit_group_name = $this->getFullyQualifiedUGroupName($ugroup);
        $driver            = $this->driver_factory->getDriver($server);

        if (! $driver->doesTheGroupExist($server, $gerrit_group_name)) {
            $driver->createGroup($server, $gerrit_group_name, $admin_group_name);
            $this->dao->addReference($ugroup->getProjectId(), $ugroup->getId(), $server->getId());
        }
        $this->fillGroupWithMembers($ugroup);

        return $gerrit_group_name;
    }

    private function fillGroupWithMembers(ProjectUGroup $ugroup)
    {
        $source_ugroup = $ugroup->getSourceGroup();
        if ($source_ugroup) {
            $this->addUGroupBinding($ugroup, $source_ugroup);
        } else {
            foreach ($ugroup->getMembers() as $user) {
                $this->addUserToGroupWithoutFlush($user, $ugroup);
            }
        }
    }

    protected function addUserToGroupWithoutFlush(PFUser $user, ProjectUGroup $ugroup)
    {
        $command = new Git_Driver_Gerrit_MembershipCommand_AddUser($this, $this->driver_factory, $this->gerrit_user_manager, $ugroup, $user);
        $command->disableAutoFlush();
        $this->updateUserMembership($command);
    }


    /**
     * This should probably be in a dedicated GerritUserGroup object.
     *
     * @return String
     */
    public function getFullyQualifiedUGroupName(ProjectUGroup $ugroup)
    {
        return $ugroup->getProject()->getUnixName() . '/' . $ugroup->getNormalizedName();
    }

    /**
     * Returns true if the user group exists on given server
     *
     * @return bool
     */
    public function doesGroupExistOnServer(Git_RemoteServer_GerritServer $server, ProjectUGroup $ugroup)
    {
        $this->cacheGroupDefinitionForServer($server);
        return isset($this->cache_groups[$server->getId()][$this->getFullyQualifiedUGroupName($ugroup)]);
    }

    /**
     * Returns gerrit user group uuid
     *
     * @param string                        $gerrit_group_name
     * @return String
     * @throws Exception
     */
    public function getGroupUUIDByNameOnServer(Git_RemoteServer_GerritServer $server, $gerrit_group_name)
    {
        $this->cacheGroupDefinitionForServer($server);
        if (isset($this->cache_groups[$server->getId()][$gerrit_group_name])) {
            return $this->cache_groups[$server->getId()][$gerrit_group_name];
        }
        throw new Exception("Group $gerrit_group_name doesn't not exist on server " . $server->getId() . " " . $server->getBaseUrl());
    }

    private function cacheGroupDefinitionForServer(Git_RemoteServer_GerritServer $server)
    {
        if (! isset($this->cache_groups[$server->getId()])) {
            $this->cache_groups[$server->getId()] = $this->driver_factory->getDriver($server)->getAllGroups($server);
        }
    }

    /**
     *
     * @return bool
     */
    private function ugroupCanBeMigrated(ProjectUGroup $ugroup)
    {
         return $ugroup->getId() > ProjectUGroup::NONE ||
            $ugroup->getId() == ProjectUGroup::PROJECT_MEMBERS ||
            $ugroup->getId() == ProjectUGroup::PROJECT_ADMIN;
    }

    private function runCommandOnServers(array $remote_servers, Git_Driver_Gerrit_MembershipCommand $command)
    {
        foreach ($remote_servers as $remote_server) {
            try {
                $command->execute($remote_server);
            } catch (Git_Driver_Gerrit_Exception $e) {
                $this->logger->error($e->getMessage());
            }
        }
    }
}
