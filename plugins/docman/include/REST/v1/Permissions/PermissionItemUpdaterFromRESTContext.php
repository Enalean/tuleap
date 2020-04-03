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

namespace Tuleap\Docman\REST\v1\Permissions;

use Docman_Item;
use Docman_PermissionsManager;
use Luracast\Restler\RestException;
use PFUser;
use Tuleap\Docman\Permissions\PermissionItemUpdater;

final class PermissionItemUpdaterFromRESTContext
{
    /**
     * @var PermissionItemUpdater
     */
    private $permission_item_updater;
    /**
     * @var Docman_PermissionsManager
     */
    private $permissions_manager;
    /**
     * @var DocmanItemPermissionsForGroupsSetFactory
     */
    private $permissions_for_groups_set_factory;

    public function __construct(
        PermissionItemUpdater $permission_item_updater,
        Docman_PermissionsManager $permissions_manager,
        DocmanItemPermissionsForGroupsSetFactory $permissions_for_groups_set_factory
    ) {
        $this->permission_item_updater            = $permission_item_updater;
        $this->permissions_manager                = $permissions_manager;
        $this->permissions_for_groups_set_factory = $permissions_for_groups_set_factory;
    }

    /**
     * @throws RestException
     */
    public function updateItemPermissions(
        Docman_Item $item,
        PFUser $user,
        DocmanItemPermissionsForGroupsSetRepresentation $representation
    ): void {
        if (! $this->permissions_manager->userCanManage($user, $item->getId())) {
            throw new RestException(403);
        }

        $this->permission_item_updater->updateItemPermissions(
            $item,
            $user,
            $this->permissions_for_groups_set_factory->fromRepresentation($item, $representation)->toPermissionsPerUGroupIDAndTypeArray()
        );
    }

    /**
     * @throws RestException
     */
    public function updateFolderPermissions(
        \Docman_Folder $folder,
        PFUser $user,
        DocmanFolderPermissionsForGroupsPUTRepresentation $representation
    ): void {
        if (! $this->permissions_manager->userCanManage($user, $folder->getId())) {
            throw new RestException(403);
        }

        if ($representation->apply_permissions_on_children) {
            $this->permission_item_updater->updateFolderAndChildrenPermissions(
                $folder,
                $user,
                $this->permissions_for_groups_set_factory->fromRepresentation($folder, $representation)->toPermissionsPerUGroupIDAndTypeArray()
            );
        } else {
            $this->permission_item_updater->updateItemPermissions(
                $folder,
                $user,
                $this->permissions_for_groups_set_factory->fromRepresentation($folder, $representation)->toPermissionsPerUGroupIDAndTypeArray()
            );
        }
    }
}
