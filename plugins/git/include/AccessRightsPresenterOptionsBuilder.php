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
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

namespace Tuleap\Git;

use GitRepository;
use ProjectUGroup;
use Git;
use User_ForgeUserGroupFactory;
use PermissionsManager;
use Project;
use Tuleap\Git\Permissions\FineGrainedPermission;
use Tuleap\Git\Permissions\DefaultFineGrainedPermission;

class AccessRightsPresenterOptionsBuilder
{

    /**
     * @var User_ForgeUserGroupFactory
     */
    private $user_group_factory;

    /**
     * @var PermissionsManager
     */
    private $permissions_manager;

    public function __construct(
        User_ForgeUserGroupFactory $user_group_factory,
        PermissionsManager $permissions_manager
    ) {
        $this->user_group_factory  = $user_group_factory;
        $this->permissions_manager = $permissions_manager;
    }

    public function getOptions(Project $project, GitRepository $repository, $permission)
    {
        $selected_values = $this->permissions_manager->getAuthorizedUGroupIdsForProject(
            $project,
            $repository->getId(),
            $permission
        );

        return $this->buildOptions($project, $selected_values, $permission);
    }

    public function getDefaultOptions(Project $project, $permission)
    {
        $selected_values = $this->permissions_manager->getAuthorizedUGroupIdsForProject(
            $project,
            $project->getID(),
            $permission
        );

        return $this->buildOptions($project, $selected_values, $permission);
    }

    public function getAllOptions(Project $project)
    {
        $selected_values = array();

        return $this->buildOptions($project, $selected_values, '');
    }

    public function getWriteOptionsForFineGrainedPermissions(
        FineGrainedPermission $permission,
        Project $project
    ) {
        $selected_values = array();
        foreach ($permission->getWritersUgroup() as $writer) {
            $selected_values[] = $writer->getId();
        }

        return $this->buildOptions($project, $selected_values, '');
    }

    public function getRewindOptionsForFineGrainedPermissions(
        FineGrainedPermission $permission,
        Project $project
    ) {
        $selected_values = array();
        foreach ($permission->getRewindersUgroup() as $rewinder) {
            $selected_values[] = $rewinder->getId();
        }

        return $this->buildOptions($project, $selected_values, '');
    }

    public function getWriteOptionsForDefaultFineGrainedPermissions(
        DefaultFineGrainedPermission $permission,
        Project $project
    ) {
        $selected_values = array();
        foreach ($permission->getWritersUgroup() as $writer) {
            $selected_values[] = $writer->getId();
        }

        return $this->buildOptions($project, $selected_values, '');
    }

    public function getRewindOptionsForDefaultFineGrainedPermissions(
        DefaultFineGrainedPermission $permission,
        Project $project
    ) {
        $selected_values = array();
        foreach ($permission->getRewindersUgroup() as $rewinder) {
            $selected_values[] = $rewinder->getId();
        }

        return $this->buildOptions($project, $selected_values, '');
    }

    private function buildOptions(Project $project, array $selected_values, $permission)
    {
        $user_groups = $this->user_group_factory->getAllForProject($project);
        $options     = array();

        foreach ($user_groups as $ugroup) {
            if ($ugroup->getId() == ProjectUGroup::ANONYMOUS &&
                ($permission !== Git::PERM_READ && $permission !== Git::DEFAULT_PERM_READ)
            ) {
                continue;
            }

            $selected  = in_array($ugroup->getId(), $selected_values) ? 'selected="selected"' : '';
            $options[] = array(
                'value'    => $ugroup->getId(),
                'label'    => $ugroup->getName(),
                'selected' => $selected
            );
        }

        return $options;
    }
}
