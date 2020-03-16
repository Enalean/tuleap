<?php
/**
 * Copyright (c) Enalean, 2016. All Rights Reserved.
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

namespace Tuleap\Git\Permissions;

use GitPermissionsManager;
use GitRepository;
use Git;
use Project;

class PermissionChangesDetector
{

    /**
     * @var FineGrainedRetriever
     */
    private $fine_grained_retriever;

    /**
     * @var GitPermissionsManager
     */
    private $git_permissions_manager;

    public function __construct(
        GitPermissionsManager $git_permissions_manager,
        FineGrainedRetriever $fine_grained_retriever
    ) {
        $this->git_permissions_manager = $git_permissions_manager;
        $this->fine_grained_retriever  = $fine_grained_retriever;
    }

    public function areThereChangesInPermissionsForProject(
        Project $project,
        array $read_ugroup_ids,
        array $write_ugroup_ids,
        array $rewind_ugroup_ids,
        $enable_fine_grained_permissions,
        array $added_branches_permissions,
        array $added_tags_permissions,
        array $updated_permissions
    ) {
        return $this->areThereChangesInFineGrainedPermissionsEnablingForProject($project, $enable_fine_grained_permissions) ||
            $this->areThereChangesInGlobalPermissionsForProject($project, $read_ugroup_ids, $write_ugroup_ids, $rewind_ugroup_ids) ||
            count($added_branches_permissions) > 0 ||
            count($added_tags_permissions) > 0 ||
            count($updated_permissions) > 0;
    }

    public function areThereChangesInPermissionsForRepository(
        GitRepository $repository,
        array $repoAccess,
        $enable_fine_grained_permissions,
        array $added_branches_permissions,
        array $added_tags_permissions,
        array $updated_permissions
    ) {
        return $this->areThereChangesInFineGrainedPermissionsEnablingForRepository($repository, $enable_fine_grained_permissions) ||
            $this->areThereChangesInGlobalPermissionsForRepository($repository, $repoAccess) ||
            count($added_branches_permissions) > 0 ||
            count($added_tags_permissions) > 0 ||
            count($updated_permissions) > 0;
    }

    private function areThereChangesInGlobalPermissionsForRepository(
        GitRepository $repository,
        array $repoAccess
    ) {
        return $repoAccess != $this->git_permissions_manager->getRepositoryGlobalPermissions($repository);
    }

    private function areThereChangesInFineGrainedPermissionsEnablingForRepository(
        GitRepository $repository,
        $enable_fine_grained_permissions
    ) {
        return (bool) $enable_fine_grained_permissions !=
            $this->fine_grained_retriever->doesRepositoryUseFineGrainedPermissions($repository);
    }

    private function areThereChangesInFineGrainedPermissionsEnablingForProject(
        Project $project,
        $enable_fine_grained_permissions
    ) {
        return (bool) $enable_fine_grained_permissions !=
            $this->fine_grained_retriever->doesProjectUseFineGrainedPermissions($project);
    }

    private function areThereChangesInGlobalPermissionsForProject(
        Project $project,
        array $read_ugroup_ids,
        array $write_ugroup_ids,
        array $rewind_ugroup_ids
    ) {
        $all_permissions = $this->buildDefaultPermissions(
            $project,
            $read_ugroup_ids,
            $write_ugroup_ids,
            $rewind_ugroup_ids
        );

        return $all_permissions != $this->git_permissions_manager->getProjectGlobalPermissions($project);
    }

    private function buildDefaultPermissions(
        Project $project,
        array $read_ugroup_ids,
        array $write_ugroup_ids,
        array $rewind_ugroup_ids
    ) {
        $all_permissions = array(
            Git::DEFAULT_PERM_READ  => $read_ugroup_ids,
        );

        if (! $this->fine_grained_retriever->doesProjectUseFineGrainedPermissions($project)) {
            $all_permissions[Git::DEFAULT_PERM_WRITE] = $write_ugroup_ids;
            $all_permissions[Git::DEFAULT_PERM_WPLUS] = $rewind_ugroup_ids;
        }

        return $all_permissions;
    }
}
