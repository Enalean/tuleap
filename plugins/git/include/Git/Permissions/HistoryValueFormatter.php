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
use Tuleap\User\UserGroup\NameTranslator;
use UGroupManager;
use ProjectUGroup;
use Project;

class HistoryValueFormatter
{

    /**
     * @var FineGrainedPermissionFactory
     */
    private $fine_grained_factory;

    /**
     * @var DefaultFineGrainedPermissionFactory
     */
    private $default_fine_grained_factory;

    /**
     * @var FineGrainedRetriever
     */
    private $fine_grained_retriever;

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
        UGroupManager $ugroup_manager,
        FineGrainedRetriever $fine_grained_retriever,
        DefaultFineGrainedPermissionFactory $default_fine_grained_factory,
        FineGrainedPermissionFactory $fine_grained_factory
    ) {
        $this->permissions_manager          = $permissions_manager;
        $this->ugroup_manager               = $ugroup_manager;
        $this->fine_grained_retriever       = $fine_grained_retriever;
        $this->default_fine_grained_factory = $default_fine_grained_factory;
        $this->fine_grained_factory         = $fine_grained_factory;
    }

    public function formatValueForProject(Project $project)
    {
        $value = $this->getReadersForProject($project);

        if (! $this->fine_grained_retriever->doesProjectUseFineGrainedPermissions($project)) {
            $value .= $this->getWritersForProject($project);
            $value .= $this->getRewindersForProject($project);
        } else {
            $value .= $this->getFineGrainedForProject($project);
        }

        return trim($value);
    }

    public function formatValueForRepository(GitRepository $repository)
    {
        $value = $this->getReadersForRepository($repository);

        if (
            ! $repository->isMigratedToGerrit() &&
            ! $this->fine_grained_retriever->doesRepositoryUseFineGrainedPermissions($repository)
        ) {
            $value .= $this->getWritersForRepository($repository);
            $value .= $this->getRewindersForRepository($repository);
        } elseif ($this->fine_grained_retriever->doesRepositoryUseFineGrainedPermissions($repository)) {
            $value .= $this->getFineGrainedForRepository($repository);
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
        $value .= implode(
            ', ',
            array_map(
                static function (ProjectUGroup $ugroup) {
                    return NameTranslator::getUserGroupDisplayName($ugroup->getName());
                },
                $ugroups
            )
        );
        $value .= PHP_EOL;

        return $value;
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

    private function getFineGrainedForProject($project)
    {
        $fine_grained_permissions = $this->default_fine_grained_factory->getBranchesFineGrainedPermissionsForProject($project) +
            $this->default_fine_grained_factory->getTagsFineGrainedPermissionsForProject($project);

        return $this->formatProjectFineGrainedPermission($fine_grained_permissions);
    }

    private function formatProjectFineGrainedPermission(array $fine_grained_permissions)
    {
        $value = '';
        foreach ($fine_grained_permissions as $permission) {
            $value .= $this->formatProjectFineGrainedPermissionWriters($permission);
            $value .= $this->formatProjectFineGrainedPermissionRewinders($permission);
        }

        return $value;
    }

    private function formatProjectFineGrainedPermissionWriters(DefaultFineGrainedPermission $permission)
    {
        if (count($permission->getWritersUgroup()) === 0) {
            return '';
        }

        return $permission->getPattern() . ' ' . $this->formatUgroups($permission->getWritersUgroup(), 'Write');
    }

    private function formatProjectFineGrainedPermissionRewinders(DefaultFineGrainedPermission $permission)
    {
        if (count($permission->getRewindersUgroup()) === 0) {
            return '';
        }

        return $permission->getPattern() . ' ' . $this->formatUgroups($permission->getRewindersUgroup(), 'Rewind');
    }

    private function getFineGrainedForRepository(GitRepository $repository)
    {
        $fine_grained_permissions = $this->fine_grained_factory->getBranchesFineGrainedPermissionsForRepository($repository) +
            $this->fine_grained_factory->getTagsFineGrainedPermissionsForRepository($repository);

        return $this->formatRepositoryFineGrainedPermission($fine_grained_permissions);
    }

    private function formatRepositoryFineGrainedPermission(array $fine_grained_permissions)
    {
        $value = '';
        foreach ($fine_grained_permissions as $permission) {
            $value .= $this->formatRepositoryFineGrainedPermissionWriters($permission);
            $value .= $this->formatRepositoryFineGrainedPermissionRewinders($permission);
        }

        return $value;
    }

    private function formatRepositoryFineGrainedPermissionWriters(FineGrainedPermission $permission)
    {
        if (count($permission->getWritersUgroup()) === 0) {
            return '';
        }

        return $permission->getPattern() . ' ' . $this->formatUgroups($permission->getWritersUgroup(), 'Write');
    }

    private function formatRepositoryFineGrainedPermissionRewinders(FineGrainedPermission $permission)
    {
        if (count($permission->getRewindersUgroup()) === 0) {
            return '';
        }

        return $permission->getPattern() . ' ' . $this->formatUgroups($permission->getRewindersUgroup(), 'Rewind');
    }
}
