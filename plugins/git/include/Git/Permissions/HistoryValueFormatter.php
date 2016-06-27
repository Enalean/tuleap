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

use GitRepository;
use PermissionsManager;
use Git;
use UGroupManager;
use User_ForgeUGroup;
use ProjectUGroup;
use Project;

class HistoryValueFormatter
{

    /**
     * @var UGroupManager
     */
    private $ugroup_manager;

    /**
     * @var PermissionsManager
     */
    private $permissions_manager;

    public function __construct(
        PermissionsManager $permissions_manager,
        UGroupManager      $ugroup_manager
    ) {
        $this->permissions_manager = $permissions_manager;
        $this->ugroup_manager      = $ugroup_manager;
    }

    public function formatValueForProject(Project $project)
    {
        $value = $this->getReadersForProject($project);
        $value .= $this->getWritersForProject($project);
        $value .= $this->getRewindersForProject($project);

        return trim($value);
    }

    public function formatValueForRepository(GitRepository $repository)
    {
        $value = $this->getReadersForRepository($repository);

        if (! $repository->isMigratedToGerrit()) {
            $value .= $this->getWritersForRepository($repository);
            $value .= $this->getRewindersForRepository($repository);
        }

        return trim($value);
    }

    private function getReadersForProject(Project $project)
    {
        return $this->formatProjectPermission($project, Git::DEFAULT_PERM_READ, 'Read');
    }

    private function getWritersForProject(Project $project)
    {
        return $this->formatProjectPermission($project, Git::DEFAULT_PERM_WRITE, 'Write');
    }

    private function getRewindersForProject(Project $project)
    {
        return $this->formatProjectPermission($project, Git::DEFAULT_PERM_WPLUS, 'Rewind');
    }

    private function getReadersForRepository(GitRepository $repository)
    {
        return $this->formatRepositoryPermission($repository, Git::PERM_READ, 'Read');
    }

    private function getWritersForRepository(GitRepository $repository)
    {
        return $this->formatRepositoryPermission($repository, Git::PERM_WRITE, 'Write');
    }

    private function getRewindersForRepository(GitRepository $repository)
    {
        return $this->formatRepositoryPermission($repository, Git::PERM_WPLUS, 'Rewind');
    }

    private function formatProjectPermission(Project $project, $permission_type, $key)
    {
        $value = '';
        $ugroups = $this->getUgroupsForPermissionForProject($project, $permission_type);
        if ($ugroups) {
            $value .= $this->formatUgroups($ugroups, $key);
        }

        return $value;
    }

    private function formatRepositoryPermission(GitRepository $repository, $permission_type, $key)
    {
        $value = '';
        $ugroups = $this->getUgroupsForPermissionForRepository($repository, $permission_type);
        if ($ugroups) {
            $value .= $this->formatUgroups($ugroups, $key);
        }

        return $value;
    }

    private function formatUgroups(array $ugroups, $key)
    {
        $value = "$key: ";
        $value .= implode(', ', array_map(array($this, 'extractUgroupName'), $ugroups));
        $value .= PHP_EOL;

        return $value;
    }

    private function extractUgroupName(ProjectUGroup $ugroup)
    {
        return User_ForgeUGroup::getUserGroupDisplayName($ugroup->getName());
    }

    /**
     * @return array
     */
    private function getUgroupsForPermissionForRepository(GitRepository $repository, $permission_type)
    {
        $ugroup_ids = $this->permissions_manager->getAuthorizedUGroupIdsForProject(
            $repository->getProject(),
            $repository->getId(),
            $permission_type
        );

        return $this->getUgroups($repository->getProject(), $ugroup_ids);
    }

    private function getUgroups(Project $project, array $ugroup_ids)
    {
        $project_ugroups = $this->ugroup_manager->getUgroupsById($project);

        $ugroups = array();
        foreach ($ugroup_ids as $ugroup_id) {
            $ugroups[] = $project_ugroups[$ugroup_id];
        }

        return $ugroups;
    }

    /**
     * @return array
     */
    private function getUgroupsForPermissionForProject(Project $project, $permission_type)
    {
        $ugroup_ids = $this->permissions_manager->getAuthorizedUGroupIdsForProject(
            $project,
            $project->getID(),
            $permission_type
        );

        return $this->getUgroups($project, $ugroup_ids);
    }
}
