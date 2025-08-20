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

use Docman_Folder;
use Docman_Item;
use Docman_PermissionsManager;
use Luracast\Restler\RestException;
use PHPUnit\Framework\MockObject\MockObject;
use Tuleap\Docman\Permissions\PermissionItemUpdater;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class PermissionItemUpdaterFromRESTContextTest extends TestCase
{
    private PermissionItemUpdater&MockObject $permissions_item_updater;
    private Docman_PermissionsManager&MockObject $permissions_manager;
    private DocmanItemPermissionsForGroupsSetFactory&MockObject $permissions_for_groups_set_factory;
    private PermissionItemUpdaterFromRESTContext $permissions_item_updater_rest;

    protected function setUp(): void
    {
        $this->permissions_item_updater           = $this->createMock(PermissionItemUpdater::class);
        $this->permissions_manager                = $this->createMock(Docman_PermissionsManager::class);
        $this->permissions_for_groups_set_factory = $this->createMock(DocmanItemPermissionsForGroupsSetFactory::class);

        $this->permissions_item_updater_rest = new PermissionItemUpdaterFromRESTContext(
            $this->permissions_item_updater,
            $this->permissions_manager,
            $this->permissions_for_groups_set_factory
        );
    }

    public function testPermissionsCanBeUpdated(): void
    {
        $item           = new Docman_Item(['item_id' => 18]);
        $representation = new DocmanItemPermissionsForGroupsSetRepresentation();

        $this->permissions_manager->method('userCanManage')->willReturn(true);
        $this->permissions_for_groups_set_factory->method('fromRepresentation')
            ->willReturn(new DocmanItemPermissionsForGroupsSet([]));

        $this->permissions_item_updater->expects($this->once())->method('updateItemPermissions');

        $this->permissions_item_updater_rest->updateItemPermissions(
            $item,
            UserTestBuilder::buildWithDefaults(),
            $representation
        );
    }

    public function testPermissionsUpdateOfAFolderIsNotAppliedOnChildrenWhenNotRequested(): void
    {
        $folder = new Docman_Folder(['item_id' => 18]);
        $this->permissions_manager->method('userCanManage')->willReturn(true);

        $this->permissions_for_groups_set_factory->method('fromRepresentation')
            ->willReturn(new DocmanItemPermissionsForGroupsSet([]));

        $representation                                = new DocmanFolderPermissionsForGroupsPUTRepresentation();
        $representation->apply_permissions_on_children = false;

        $this->permissions_item_updater->expects($this->once())->method('updateItemPermissions');

        $this->permissions_item_updater_rest->updateFolderPermissions(
            $folder,
            UserTestBuilder::buildWithDefaults(),
            $representation
        );
    }

    public function testPermissionsUpdateOfAFolderIsAppliedOnChildrenWhenRequested(): void
    {
        $folder = new Docman_Folder(['item_id' => 18]);
        $this->permissions_manager->method('userCanManage')->willReturn(true);
        $this->permissions_for_groups_set_factory->method('fromRepresentation')
            ->willReturn(new DocmanItemPermissionsForGroupsSet([]));

        $representation                                = new DocmanFolderPermissionsForGroupsPUTRepresentation();
        $representation->apply_permissions_on_children = true;

        $this->permissions_item_updater->expects($this->once())->method('updateFolderAndChildrenPermissions');

        $this->permissions_item_updater_rest->updateFolderPermissions(
            $folder,
            UserTestBuilder::buildWithDefaults(),
            $representation
        );
    }

    public function testUpdateIsRejectedIfTheUserCanNotManageTheItem(): void
    {
        $item = new Docman_Item(['item_id' => 78]);

        $this->permissions_manager->method('userCanManage')->willReturn(false);

        $this->expectException(RestException::class);
        $this->expectExceptionCode(403);

        $this->permissions_item_updater_rest->updateItemPermissions(
            $item,
            UserTestBuilder::buildWithDefaults(),
            new DocmanItemPermissionsForGroupsSetRepresentation()
        );
    }

    public function testUpdateItemPermissionsIsRejectedWhenTheUserCanNotManageIt(): void
    {
        $item = new Docman_Item(['item_id' => 77]);

        $this->permissions_manager->method('userCanManage')->willReturn(false);

        $this->expectException(RestException::class);
        $this->expectExceptionCode(403);
        $this->permissions_item_updater_rest->updateItemPermissions(
            $item,
            UserTestBuilder::buildWithDefaults(),
            new DocmanItemPermissionsForGroupsSetRepresentation()
        );
    }

    public function testUpdateFolderPermissionsIsRejectedWhenTheUserCanNotManageIt(): void
    {
        $folder = new Docman_Folder(['item_id' => 77]);

        $this->permissions_manager->method('userCanManage')->willReturn(false);

        $this->expectException(RestException::class);
        $this->expectExceptionCode(403);
        $this->permissions_item_updater_rest->updateFolderPermissions(
            $folder,
            UserTestBuilder::buildWithDefaults(),
            new DocmanFolderPermissionsForGroupsPUTRepresentation()
        );
    }
}
