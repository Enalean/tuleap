<?php
/**
 * Copyright (c) Enalean, 2014 - Present. All Rights Reserved.
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

use Tuleap\Git\AsynchronousEvents\RefreshGitoliteProjectConfigurationTask;
use Tuleap\Git\Permissions\FineGrainedDao;
use Tuleap\Git\Permissions\FineGrainedRetriever;
use Tuleap\Git\Permissions\VerifyUserIsGitAdministrator;

/**
 * This class manages permissions for the Git service
 */
class GitPermissionsManager implements VerifyUserIsGitAdministrator
{
    /**
     * @var FineGrainedRetriever
     */
    private $fine_grained_retriever;

    /**
     * @var FineGrainedDao
     */
    private $fine_grained_dao;

    /**
     * @var Git_PermissionsDao
     */
    private $git_permission_dao;

    /**
     * @var PermissionsManager
     */
    private $permissions_manager;

    public function __construct(
        Git_PermissionsDao $git_permission_dao,
        private readonly \Tuleap\Queue\EnqueueTaskInterface $enqueuer,
        FineGrainedDao $fine_grained_dao,
        FineGrainedRetriever $fine_grained_retriever,
    ) {
        $this->permissions_manager    = PermissionsManager::instance();
        $this->git_permission_dao     = $git_permission_dao;
        $this->fine_grained_dao       = $fine_grained_dao;
        $this->fine_grained_retriever = $fine_grained_retriever;
    }

    #[\Override]
    public function userIsGitAdmin(PFUser $user, Project $project): bool
    {
        $database_result = $this->getCurrentGitAdminPermissionsForProject($project);

        if (db_numrows($database_result) < 1) {
            $database_result = $this->getDefaultGitAdminPermissions();
        }

        $has_permission = false;
        while (! $has_permission && ($row = db_fetch_array($database_result))) {
            $has_permission = ugroup_user_is_member($user->getId(), $row['ugroup_id'], $project->getID());
        }

        return $has_permission;
    }

    /**
     * @param Project $project
     * Return a DB list of ugroup_ids authorized to access the given object
     */
    private function getCurrentGitAdminPermissionsForProject(Project $project)
    {
        return permission_db_authorized_ugroups(Git::PERM_ADMIN, $project->getID());
    }

    private function getDefaultGitAdminPermissions()
    {
        /** @psalm-suppress DeprecatedFunction */
        return permission_db_get_defaults(Git::PERM_ADMIN);
    }

    public function getCurrentGitAdminUgroups($project_id)
    {
        return $this->permissions_manager->getAuthorizedUgroupIds($project_id, Git::PERM_ADMIN);
    }

    public function updateProjectAccess(Project $project, $old_access, $new_access): void
    {
        if ($new_access === Project::ACCESS_PRIVATE || $new_access === Project::ACCESS_PRIVATE_WO_RESTRICTED) {
            $this->git_permission_dao->disableAnonymousRegisteredAuthenticated($project->getID());
            $this->fine_grained_dao->disableAnonymousRegisteredAuthenticated($project->getID());
            $this->enqueuer->enqueue(RefreshGitoliteProjectConfigurationTask::fromProject($project));
        }
        if ($new_access === Project::ACCESS_PUBLIC && $old_access === Project::ACCESS_PUBLIC_UNRESTRICTED) {
            $this->git_permission_dao->disableAuthenticated($project->getID());
            $this->fine_grained_dao->disableAuthenticated($project->getID());
            $this->enqueuer->enqueue(RefreshGitoliteProjectConfigurationTask::fromProject($project));
        }
    }

    public function updateSiteAccess($old_value, $new_value): void
    {
        if ($old_value == ForgeAccess::ANONYMOUS) {
            $project_rows = $this->git_permission_dao->getAllProjectsWithAnonymousRepositories();
            if (count($project_rows) === 0) {
                return;
            }
            $this->git_permission_dao->updateAllAnonymousAccessToRegistered();
            $this->fine_grained_dao->updateAllAnonymousAccessToRegistered();

            foreach ($project_rows as $project_row) {
                $this->enqueuer->enqueue(new RefreshGitoliteProjectConfigurationTask((int) $project_row['group_id']));
            }
        }
        if ($old_value == ForgeAccess::RESTRICTED) {
            $project_rows = $this->git_permission_dao->getAllProjectsWithUnrestrictedRepositories();
            if (count($project_rows) === 0) {
                return;
            }
            $this->git_permission_dao->updateAllAuthenticatedAccessToRegistered();
            $this->fine_grained_dao->updateAllAuthenticatedAccessToRegistered();

            foreach ($project_rows as $project_row) {
                $this->enqueuer->enqueue(new RefreshGitoliteProjectConfigurationTask((int) $project_row['group_id']));
            }
        }
    }

    /**
     * @return array
     */
    public function getDefaultPermissions(Project $project)
    {
        return [
            Git::PERM_READ  => $this->getDefaultPermission($project, Git::DEFAULT_PERM_READ),
            Git::PERM_WRITE => $this->getDefaultPermission($project, Git::DEFAULT_PERM_WRITE),
            Git::PERM_WPLUS => $this->getDefaultPermission($project, Git::DEFAULT_PERM_WPLUS),
        ];
    }

    private function getDefaultPermission(Project $project, $permission_name)
    {
        return $this->permissions_manager->getAuthorizedUGroupIdsForProject(
            $project,
            $project->getID(),
            $permission_name
        );
    }

    /**
     * @return array
     */
    public function getRepositoryGlobalPermissions(GitRepository $repository)
    {
        $permissions =  [
            Git::PERM_READ => $this->getGlobalPermission($repository, Git::PERM_READ),
        ];

        if (
            ! $repository->isMigratedToGerrit() &&
            ! $this->fine_grained_retriever->doesRepositoryUseFineGrainedPermissions($repository)
        ) {
            $permissions[Git::PERM_WRITE] = $this->getGlobalPermission($repository, Git::PERM_WRITE);
            $permissions[Git::PERM_WPLUS] = $this->getGlobalPermission($repository, Git::PERM_WPLUS);
        }

        return $permissions;
    }

    /**
     * @return array
     */
    public function getProjectGlobalPermissions(Project $project)
    {
        $permissions =  [
            Git::DEFAULT_PERM_READ => $this->getDefaultPermission($project, Git::DEFAULT_PERM_READ),
        ];

        if (! $this->fine_grained_retriever->doesProjectUseFineGrainedPermissions($project)) {
            $permissions[Git::DEFAULT_PERM_WRITE] = $this->getDefaultPermission($project, Git::DEFAULT_PERM_WRITE);
            $permissions[Git::DEFAULT_PERM_WPLUS] = $this->getDefaultPermission($project, Git::DEFAULT_PERM_WPLUS);
        }

        return $permissions;
    }

    private function getGlobalPermission(GitRepository $repository, $permission_name)
    {
        return $this->permissions_manager->getAuthorizedUGroupIdsForProject(
            $repository->getProject(),
            $repository->getId(),
            $permission_name
        );
    }
}
