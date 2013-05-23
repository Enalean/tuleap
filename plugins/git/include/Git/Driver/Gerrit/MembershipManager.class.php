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
class Git_Driver_Gerrit_MembershipManager {
    const GROUP_CONTRIBUTORS = 'contributors';
    const GROUP_INTEGRATORS  = 'integrators';
    const GROUP_SUPERMEN     = 'supermen';
    const GROUP_OWNERS       = 'owners';

    public static $GERRIT_GROUPS = array(self::GROUP_CONTRIBUTORS => Git::PERM_READ,
                                         self::GROUP_INTEGRATORS  => Git::PERM_WRITE,
                                         self::GROUP_SUPERMEN     => Git::PERM_WPLUS,
                                         self::GROUP_OWNERS       => Git::SPECIAL_PERM_ADMIN);
    public static $PERMS_TO_GROUPS = array(
        Git::PERM_READ          => self::GROUP_CONTRIBUTORS,
        Git::PERM_WRITE         => self::GROUP_INTEGRATORS,
        Git::PERM_WPLUS         => self::GROUP_SUPERMEN,
        Git::SPECIAL_PERM_ADMIN => self::GROUP_OWNERS,
    );

    private $dao;
    private $driver;
    private $gerrit_server_factory;
    private $logger;
    private $gerrit_user_manager;
    private $ugroup_manager;

    private $cache_groups = array();

    public function __construct(
        Git_Driver_Gerrit_MembershipDao      $dao,
        Git_Driver_Gerrit                    $driver,
        Git_Driver_Gerrit_UserAccountManager $gerrit_usermanager,
        Git_RemoteServer_GerritServerFactory $gerrit_server_factory,
        Logger                               $logger,
        UGroupManager                        $ugroup_manager
    ) {
        $this->dao                    = $dao;
        $this->driver                 = $driver;
        $this->gerrit_user_manager    = $gerrit_usermanager;
        $this->gerrit_server_factory  = $gerrit_server_factory;
        $this->logger                 = $logger;
        $this->ugroup_manager         = $ugroup_manager;
    }

    /**
     * Add a user into a user group
     *
     * @param PFUser $user
     * @param UGroup $ugroup
     */
    public function addUserToGroup(PFUser $user, UGroup $ugroup) {
        $this->updateUserMembership(
            new Git_Driver_Gerrit_MembershipCommand_AddUser($this, $this->driver, $this->gerrit_user_manager, $ugroup, $user)
        );
    }

    /**
     * Remove a user from a user group
     *
     * @param PFUser $user
     * @param UGroup $ugroup
     */
    public function removeUserFromGroup(PFUser $user, UGroup $ugroup) {
        $this->updateUserMembership(
            new Git_Driver_Gerrit_MembershipCommand_RemoveUser($this, $this->driver, $this->gerrit_user_manager, $ugroup, $user)
        );
    }

    private function updateUserMembership(Git_Driver_Gerrit_MembershipCommand $command) {
        $this->runCommandOnServers(
            $this->gerrit_server_factory->getServersForUGroup($command->getUGroup()),
            $command
        );
    }

    /**
     * Bind $ugroup with $source_ugroup
     *
     * @param UGroup $ugroup
     * @param UGroup $source_ugroup
     */
    public function addUGroupBinding(UGroup $ugroup, UGroup $source_ugroup) {
        $this->updateUGroupBinding(
            new Git_Driver_Gerrit_MembershipCommand_AddBinding($this, $this->driver, $ugroup, $source_ugroup)
        );
    }

    /**
     * Remove binding set on a user group
     *
     * @param UGroup $ugroup
     */
    public function removeUGroupBinding(UGroup $ugroup) {
        $this->updateUGroupBinding(
            new Git_Driver_Gerrit_MembershipCommand_RemoveBinding($this, $this->driver, $ugroup)
        );
    }

    private function updateUGroupBinding(Git_Driver_Gerrit_MembershipCommand $command) {
        $this->runCommandOnServers(
            $this->gerrit_server_factory->getServersForUGroup($command->getUGroup()),
            $command
        );
    }

    /**
     * Create a user group on all gerrit servers a project use
     *
     * @param UGroup $ugroup
     */
    public function createGroupOnProjectsServers(UGroup $ugroup) {
        $remote_servers = $this->gerrit_server_factory->getServersForProject($ugroup->getProject());
        foreach ($remote_servers as $remote_server) {
            $this->createGroupForServer($remote_server, $ugroup);
        }
    }

    /**
     * Create groups during repository migration
     *
     * @param Git_RemoteServer_GerritServer $server
     * @param UGroup[]                      $ugroups
     * @return UGroup[]
     */
    public function createArrayOfGroupsForServer(Git_RemoteServer_GerritServer $server, array $ugroups) {
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

    private function invalidGerritGroupsCaches(Git_RemoteServer_GerritServer $server) {
        unset($this->cache_groups[$server->getId()]);
        $this->driver->flushGerritCacheAccounts($server);
    }

    /**
     * Create a user group
     *
     * @param Git_RemoteServer_GerritServer $server
     * @param UGroup $ugroup
     *
     * @return String Name of group on gerrit server | false in case of error
     */
    public function createGroupForServer(Git_RemoteServer_GerritServer $server, UGroup $ugroup) {
        try {
            if ($this->ugroupCanBeMigrated($ugroup)) {
                $admin_ugroup = $this->getProjectAdminsUGroup($ugroup);
                $this->createProjectAdminsGroup($server, $admin_ugroup);
                return $this->createGroupOnServerWithoutCheckingUGroupValidity($server, $ugroup, $admin_ugroup);
            } else {
                return false;
            }
        }
        catch (Git_Driver_Gerrit_RemoteSSHCommandFailure $e) {
            $this->logger->error($e->getMessage());
        }
        catch (Git_Driver_Gerrit_Exception $e) {
            $this->logger->error($e->getMessage());
        }
        catch (Exception $e) {
            $this->logger->error('Unknown error: ' . $e->getMessage());
        }
        return false;
    }

    private function createProjectAdminsGroup(Git_RemoteServer_GerritServer $server, UGroup $admin_ugroup) {
        if ( ! $this->doesGroupExistOnServer($server, $admin_ugroup)) {
            $this->createGroupOnServerWithoutCheckingUGroupValidity($server, $admin_ugroup, $admin_ugroup);
        }
    }

    private function getProjectAdminsUGroup(UGroup $ugroup) {
        return $this->ugroup_manager->getUGroup($ugroup->getProject(), UGroup::PROJECT_ADMIN);
    }

    private function createGroupOnServerWithoutCheckingUGroupValidity(Git_RemoteServer_GerritServer $server, UGroup $ugroup, UGroup $admin_ugroup) {
        $admin_group_name  = $this->getFullyQualifiedUGroupName($admin_ugroup);
        $gerrit_group_name = $this->getFullyQualifiedUGroupName($ugroup);

        if (! $this->driver->doesTheGroupExist($server, $gerrit_group_name)) {
            $this->driver->createGroup($server, $gerrit_group_name, $admin_group_name);
        }
        $this->dao->addReference($ugroup->getProjectId(), $ugroup->getId(), $server->getId());
        $this->fillGroupWithMembers($ugroup);

        return $gerrit_group_name;
    }

    private function fillGroupWithMembers(UGroup $ugroup) {
        $source_ugroup = $ugroup->getSourceGroup();
        if ($source_ugroup) {
            $this->addUGroupBinding($ugroup, $source_ugroup);
        } else {
            foreach ($ugroup->getMembers() as $user) {
                $this->addUserToGroupWithoutFlush($user, $ugroup);
            }
        }
    }

    protected function addUserToGroupWithoutFlush(PFUser $user, UGroup $ugroup) {
        $command = new Git_Driver_Gerrit_MembershipCommand_AddUser($this, $this->driver, $this->gerrit_user_manager, $ugroup, $user);
        $command->disableAutoFlush();
        $this->updateUserMembership($command);
    }


    /**
     * This should probably be in a dedicated GerritUserGroup object.
     *
     * @param UGroup $ugroup
     * @return String
     */
    public function getFullyQualifiedUGroupName(UGroup $ugroup) {
        return $ugroup->getProject()->getUnixName().'/'.$ugroup->getNormalizedName();
    }

    /**
     * Returns true if the user group exists on given server
     *
     * @param Git_RemoteServer_GerritServer $server
     * @param UGroup $ugroup
     * @return Boolean
     */
    public function doesGroupExistOnServer(Git_RemoteServer_GerritServer $server, UGroup $ugroup) {
        $this->cacheGroupDefinitionForServer($server);
        return isset($this->cache_groups[$server->getId()][$this->getFullyQualifiedUGroupName($ugroup)]);
    }

    /**
     * Returns gerrit user group uuid
     *
     * @param Git_RemoteServer_GerritServer $server
     * @param String                        $ugroup
     * @return String
     * @throws Exception
     */
    public function getGroupUUIDByNameOnServer(Git_RemoteServer_GerritServer $server, $gerrit_group_name) {
        $this->cacheGroupDefinitionForServer($server);
        if (isset($this->cache_groups[$server->getId()][$gerrit_group_name])) {
            return $this->cache_groups[$server->getId()][$gerrit_group_name][Git_Driver_Gerrit::INDEX_GROUPS_VERBOSE_UUID];
        }
        throw new Exception("Group $gerrit_group_name doesn't not exist on server ".$server->getId()." ".$server->getHost());
    }

    private function cacheGroupDefinitionForServer(Git_RemoteServer_GerritServer $server) {
        if ( ! isset($this->cache_groups[$server->getId()])) {
            $this->cache_groups[$server->getId()] = array();
            foreach ($this->driver->listGroupsVerbose($server) as $group_line) {
                $group_entry = explode("\t", $group_line);
                $this->cache_groups[$server->getId()][$group_entry[Git_Driver_Gerrit::INDEX_GROUPS_VERBOSE_NAME]] = $group_entry;
            }
        }
    }

    /**
     *
     * @param Ugroup $ugroup
     * @return bool
     */
    private function ugroupCanBeMigrated(Ugroup $ugroup) {
         return $ugroup->getId() > UGroup::NONE ||
            $ugroup->getId() == UGroup::PROJECT_MEMBERS ||
            $ugroup->getId() == UGroup::PROJECT_ADMIN;
    }

    private function runCommandOnServers(array $remote_servers, Git_Driver_Gerrit_MembershipCommand $command) {
        foreach ($remote_servers as $remote_server) {
            try {
                $command->execute($remote_server);
            } catch (Git_Driver_Gerrit_RemoteSSHCommandFailure $e) {
                $this->logger->error($e->getMessage());
            }
        }
    }

}
?>
