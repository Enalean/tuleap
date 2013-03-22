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

    private $git_repository_factory;
    private $gerrit_server_factory;

    public function __construct(
        GitRepositoryFactory $git_repository_factory,
        Git_RemoteServer_GerritServerFactory $gerrit_server_factory
    ) {
        $this->git_repository_factory = $git_repository_factory;
        $this->gerrit_server_factory  = $gerrit_server_factory;
    }

    public function updateUserMembership(PFUser $user, UGroup $ugroup, Project $project, Git_Driver_Gerrit_MembershipCommand $command) {
        if ($user->getLdapId()) {

            //if ($ugroup->getId() == UGroup::PROJECT_ADMIN) {
            $remote_servers = $this->gerrit_server_factory->getServersForProject($project);
            //} else {
            //    $repositories = $this->git_repository_factory->getGerritRepositoriesWithPermissionsForUGroup($project, $ugroup, $user);
            //}
            //foreach ($repositories as $repository_with_permissions) {
            foreach ($remote_servers as $remote_server) {
                $this->updateUserGerritGroupsAccordingToPermissions($user, $project, $remote_server, $ugroup, $command);
            }
        }
    }

    private function updateUserGerritGroupsAccordingToPermissions(PFUser $user, Project $project, Git_RemoteServer_GerritServer $remote_server, UGroup $ugroup, Git_Driver_Gerrit_MembershipCommand $command) {
        $command->execute($remote_server, $user, $project, $ugroup);
    }
}
?>
