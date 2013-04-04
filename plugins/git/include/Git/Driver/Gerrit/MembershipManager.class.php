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
    private $git_repository_factory;
    private $gerrit_server_factory;
    private $logger;

    public function __construct(
        Git_Driver_Gerrit_MembershipDao      $dao,
        GitRepositoryFactory $git_repository_factory,
        Git_RemoteServer_GerritServerFactory $gerrit_server_factory,
        Logger                               $logger
    ) {
        $this->dao                    = $dao;
        $this->git_repository_factory = $git_repository_factory;
        $this->gerrit_server_factory  = $gerrit_server_factory;
        $this->logger                 = $logger;
    }

    public function updateUserMembership(PFUser $user, UGroup $ugroup, Project $project, Git_Driver_Gerrit_MembershipCommand $command) {
        if (! $user->getLdapId()) {
            return;
        }

        $remote_servers = $this->gerrit_server_factory->getServersForUGroup($ugroup);
        foreach ($remote_servers as $remote_server) {
            try {
                $command->execute($remote_server, $user, $project, $ugroup);
            } catch (Git_Driver_Gerrit_RemoteSSHCommandFailure $e) {
                $this->logger->error($e->getMessage());
            }
        }
    }

    public function updateUGroupBinding(Git_Driver_Gerrit $driver, UGroup $ugroup, UGroup $source_ugroup = null) {
        $remote_servers = $this->gerrit_server_factory->getServersForUGroup($ugroup);
        foreach ($remote_servers as $remote_server) {
            $group_name = $this->getFullyQualifiedUGroupName($ugroup);
            if ($source_ugroup) {
                $included_group_name = $this->createGroupForServer($remote_server, $driver, $source_ugroup);
                $driver->addIncludedGroup($remote_server, $group_name, $included_group_name);
            } else {
                $driver->removeAllIncludedGroups($remote_server, $group_name);
            }
        }
    }

    public function createGroupOnProjectsServers(Git_Driver_Gerrit $driver, UGroup $ugroup) {
        $group_name = '';
        $remote_servers    = $this->gerrit_server_factory->getServersForProject($ugroup->getProject());
        foreach ($remote_servers as $remote_server) {
            $group_name = $this->createGroupForServer($remote_server, $driver, $ugroup);
        }
        return $group_name;
    }

    public function createGroupForServer(Git_RemoteServer_GerritServer $server, Git_Driver_Gerrit $driver, UGroup $ugroup) {
        try {
            if ($this->UGroupCanBeMigrated($ugroup)) {
                $gerrit_group_name = $this->getFullyQualifiedUGroupName($ugroup);
                if (!$driver->doesTheGroupExist($server, $gerrit_group_name)) {
                    $driver->createGroup($server, $gerrit_group_name, $ugroup->getLdapMembersIds($ugroup->getProject()->getID()));
                    $this->dao->addReference($ugroup->getProjectId(), $ugroup->getId(), $server->getId());
                }
                return $gerrit_group_name;
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

    /**
     * This should probably be in a dedicated GerritUserGroup object.
     *
     * @param UGroup $ugroup
     * @return String
     */
    private function getFullyQualifiedUGroupName(UGroup $ugroup) {
        return $ugroup->getProject()->getUnixName().'/'.$ugroup->getNormalizedName();
    }

     /**
     *
     * @param Ugroup $ugroup
     * @return bool
     */
    private function UGroupCanBeMigrated(Ugroup $ugroup) {
         return $ugroup->getId() > UGroup::NONE ||
            $ugroup->getId() == UGroup::PROJECT_MEMBERS ||
            $ugroup->getId() == UGroup::PROJECT_ADMIN;
    }
}
?>
