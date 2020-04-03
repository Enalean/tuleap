<?php
/**
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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

declare(strict_types=1);

namespace Tuleap\Docman\REST\v1\Permissions;

use Docman_Item;
use Luracast\Restler\RestException;
use ProjectManager;
use ProjectUGroup;
use Tuleap\Docman\Permissions\PermissionItemUpdater;
use Tuleap\Project\REST\UserGroupRetriever;
use UGroupManager;

class DocmanItemPermissionsForGroupsSetFactory
{
    /**
     * @var UGroupManager
     */
    private $ugroup_manager;
    /**
     * @var UserGroupRetriever
     */
    private $ugroup_retriever;
    /**
     * @var ProjectManager
     */
    private $project_manager;

    public function __construct(
        UGroupManager $ugroup_manager,
        UserGroupRetriever $ugroup_retriever,
        ProjectManager $project_manager
    ) {
        $this->ugroup_manager          = $ugroup_manager;
        $this->ugroup_retriever        = $ugroup_retriever;
        $this->project_manager         = $project_manager;
    }

    /**
     * @throws RestException
     */
    public function fromRepresentation(
        Docman_Item $item,
        DocmanItemPermissionsForGroupsSetRepresentation $representation
    ): DocmanItemPermissionsForGroupsSet {
        $permissions = array_replace(
            $this->getNonePermissionsForAllUGroupID($item),
            $this->getPermissionsPerUGroupID($item, $representation->can_read, PermissionItemUpdater::PERMISSION_DEFINITION_READ),
            $this->getPermissionsPerUGroupID($item, $representation->can_write, PermissionItemUpdater::PERMISSION_DEFINITION_WRITE),
            $this->getPermissionsPerUGroupID($item, $representation->can_manage, PermissionItemUpdater::PERMISSION_DEFINITION_MANAGE),
        );

        return new DocmanItemPermissionsForGroupsSet($permissions);
    }

    private function getNonePermissionsForAllUGroupID(Docman_Item $item): array
    {
        $project = $this->project_manager->getProject($item->getGroupId());
        $ugroups = $this->ugroup_manager->getUGroups($project);

        $all_ugroups_id_with_none_permissions = [];
        foreach ($ugroups as $ugroup) {
            $all_ugroups_id_with_none_permissions[$ugroup->getId()] = PermissionItemUpdater::PERMISSION_DEFINITION_NONE;
        }
        return $all_ugroups_id_with_none_permissions;
    }

    /**
     * @param MinimalUserGroupRepresentationForUpdate[] $user_group_representations
     * @throws RestException
     */
    private function getPermissionsPerUGroupID(
        Docman_Item $item,
        array $user_group_representations,
        int $permission_type
    ): array {
        $permissions_per_ugroup_id = [];
        $user_groups               = $this->getUserGroups($item, $user_group_representations);
        foreach ($user_groups as $user_group) {
            $permissions_per_ugroup_id[$user_group->getId()] = $permission_type;
        }
        return $permissions_per_ugroup_id;
    }

    /**
     * @param MinimalUserGroupRepresentationForUpdate[] $user_group_representations
     * @return ProjectUGroup[]
     * @throws RestException
     */
    private function getUserGroups(Docman_Item $item, array $user_group_representations): array
    {
        $user_groups = [];
        foreach ($user_group_representations as $user_group_representation) {
            $user_groups[] = $this->getUserGroup($item, $user_group_representation);
        }
        return $user_groups;
    }

    /**
     * @throws RestException
     */
    private function getUserGroup(
        Docman_Item $item,
        MinimalUserGroupRepresentationForUpdate $user_group_representation
    ): ProjectUGroup {
        $identifier      = $user_group_representation->id;
        $item_project_id = $item->getGroupId();

        try {
            $ugroup = $this->ugroup_retriever->getExistingUserGroup($identifier);
        } catch (RestException $ex) {
            if ($ex->getCode() === 404) {
                throw $this->getExceptionUserGroupNoFoundInProject($identifier, $item_project_id);
            }
            throw $ex;
        }

        $item_project_id   = $item->getGroupId();
        $ugroup_project_id = $ugroup->getProjectId();
        if ($ugroup_project_id !== null && (int) $ugroup_project_id !== $item_project_id) {
            throw $this->getExceptionUserGroupNoFoundInProject($identifier, $item_project_id);
        }

        return $ugroup;
    }

    private function getExceptionUserGroupNoFoundInProject(string $ugroup_identifier, int $project_id): RestException
    {
        return new RestException(
            400,
            "No user group exist for the identifier $ugroup_identifier in project #$project_id"
        );
    }
}
