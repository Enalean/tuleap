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

use Tuleap\Git\Permissions\FineGrainedDao;
use Tuleap\Git\Permissions\FineGrainedRetriever;

/**
 * This class manages permissions for the Git service
 */
class GitPermissionsManager
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
     * @var Git_SystemEventManager
     */
    private $git_system_event_manager;

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
        Git_SystemEventManager $git_system_event_manager,
        FineGrainedDao $fine_grained_dao,
        FineGrainedRetriever $fine_grained_retriever
    ) {
        $this->permissions_manager          = PermissionsManager::instance();
        $this->git_permission_dao           = $git_permission_dao;
        $this->git_system_event_manager     = $git_system_event_manager;
        $this->fine_grained_dao             = $fine_grained_dao;
        $this->fine_grained_retriever       = $fine_grained_retriever;
    }

    public function userIsGitAdmin(PFUser $user, Project $project)
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

    public function updateProjectAccess(Project $project, $old_access, $new_access)
    {
        if ($new_access === Project::ACCESS_PRIVATE || $new_access === Project::ACCESS_PRIVATE_WO_RESTRICTED) {
            $this->git_permission_dao->disableAnonymousRegisteredAuthenticated($project->getID());
            $this->fine_grained_dao->disableAnonymousRegisteredAuthenticated($project->getID());
            $this->git_system_event_manager->queueProjectsConfigurationUpdate(array($project->getID()));
        }
        if ($new_access === Project::ACCESS_PUBLIC && $old_access === Project::ACCESS_PUBLIC_UNRESTRICTED) {
            $this->git_permission_dao->disableAuthenticated($project->getID());
            $this->fine_grained_dao->disableAuthenticated($project->getID());
            $this->git_system_event_manager->queueProjectsConfigurationUpdate(array($project->getID()));
        }
    }

    public function updateSiteAccess($old_value, $new_value)
    {
        if ($old_value == ForgeAccess::ANONYMOUS) {
            $project_ids = $this->queueProjectsConfigurationUpdate($this->git_permission_dao->getAllProjectsWithAnonymousRepositories());
            if (count($project_ids)) {
                $this->git_permission_dao->updateAllAnonymousAccessToRegistered();
                $this->fine_grained_dao->updateAllAnonymousAccessToRegistered();
            }
        }
        if ($old_value == ForgeAccess::RESTRICTED) {
            $project_ids = $this->queueProjectsConfigurationUpdate($this->git_permission_dao->getAllProjectsWithUnrestrictedRepositories());
            if (count($project_ids)) {
                $this->git_permission_dao->updateAllAuthenticatedAccessToRegistered();
                $this->fine_grained_dao->updateAllAuthenticatedAccessToRegistered();
            }
        }
    }

    private function queueProjectsConfigurationUpdate(array $dar)
    {
        $projects_ids = array();
        if (count($dar) > 0) {
            foreach ($dar as $row) {
                $projects_ids[] = $row['group_id'];
            }
            $this->git_system_event_manager->queueProjectsConfigurationUpdate($projects_ids);
        }
        return $projects_ids;
    }

    /**
     * @return array
     */
    public function getDefaultPermissions(Project $project)
    {
        return array(
            Git::PERM_READ  => $this->getDefaultPermission($project, Git::DEFAULT_PERM_READ),
            Git::PERM_WRITE => $this->getDefaultPermission($project, Git::DEFAULT_PERM_WRITE),
            Git::PERM_WPLUS => $this->getDefaultPermission($project, Git::DEFAULT_PERM_WPLUS),
        );
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
        $permissions =  array(
            Git::PERM_READ => $this->getGlobalPermission($repository, Git::PERM_READ)
        );

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
        $permissions =  array(
            Git::DEFAULT_PERM_READ => $this->getDefaultPermission($project, Git::DEFAULT_PERM_READ)
        );

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
