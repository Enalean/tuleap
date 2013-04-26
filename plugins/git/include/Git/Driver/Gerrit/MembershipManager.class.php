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

    public function __construct(
        Git_Driver_Gerrit_MembershipDao      $dao,
        Git_Driver_Gerrit                    $driver,
        Git_RemoteServer_GerritServerFactory $gerrit_server_factory,
        Logger                               $logger
    ) {
        $this->dao                    = $dao;
        $this->driver                 = $driver;
        $this->gerrit_server_factory  = $gerrit_server_factory;
        $this->logger                 = $logger;
    }

    /**
     * Add a user into a user group
     *
     * @param PFUser $user
     * @param UGroup $ugroup
     */
    public function addUserToGroup(PFUser $user, UGroup $ugroup) {
        $this->updateUserMembership(
            new Git_Driver_Gerrit_MembershipCommand_AddUser($this, $this->driver, $ugroup, $user)
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
            new Git_Driver_Gerrit_MembershipCommand_RemoveUser($this, $this->driver, $ugroup, $user)
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
                return $this->createGroupOnServerWithoutCheckingUGroupValidity($server, $ugroup);
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

    private function createGroupOnServerWithoutCheckingUGroupValidity(Git_RemoteServer_GerritServer $server, UGroup $ugroup) {
        $gerrit_group_name = $this->getFullyQualifiedUGroupName($ugroup);
        if (! $this->driver->doesTheGroupExist($server, $gerrit_group_name)) {
            $source_ugroup = $ugroup->getSourceGroup();
            /*if ($source_ugroup) {
                $memberlist = array();
            } else {
                $memberlist = $ugroup->getLdapLogins($ugroup->getProject()->getID());
            }*/
            $this->driver->createGroup($server, $gerrit_group_name);
            $this->dao->addReference($ugroup->getProjectId(), $ugroup->getId(), $server->getId());
            if ($source_ugroup) {
                $this->addUGroupBinding($ugroup, $source_ugroup);
            }
        }
        return $gerrit_group_name;
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
