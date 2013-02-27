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

    private $git_repository_factory;
    private $gerrit_server_factory;
    private $gerrit_driver;

    public function __construct(
        GitRepositoryFactory $git_repository_factory,
        Git_Driver_Gerrit $gerrit_driver,
        Git_RemoteServer_GerritServerFactory $gerrit_server_factory
    ) {
        $this->git_repository_factory = $git_repository_factory;
        $this->gerrit_driver          = $gerrit_driver;
        $this->gerrit_server_factory  = $gerrit_server_factory;
    }

    public function updateUserMembership(User $user, Project $project, Git_Driver_Gerrit_MembershipCommand $command) {
        $repositories = $this->getMigratedRepositoriesOfAProject($project);
        foreach ($repositories as $repository) {
            $this->updateUserGerritGroupsAccordingToPermissions($user, $project, $repository, $command);
        }
    }

    private function updateUserGerritGroupsAccordingToPermissions(User $user, Project $project, GitRepository $repository, Git_Driver_Gerrit_MembershipCommand $command) {
        $remote_server   = $this->gerrit_server_factory->getServer($repository);
        $command->process($remote_server, $user, $project, $repository);
        
    }

    private function getMigratedRepositoriesOfAProject(Project $project) {
        $migrated_repositories = array();
        $repositories          = $this->git_repository_factory->getAllRepositories($project);

        foreach ($repositories as $repository) {
            if ($repository->isMigratedToGerrit()) {
                $migrated_repositories[] = $repository;
            }
        }

        return $migrated_repositories;
    }

}
?>
