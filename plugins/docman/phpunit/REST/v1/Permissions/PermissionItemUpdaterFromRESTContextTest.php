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
use Mockery;
use PFUser;
use PHPUnit\Framework\TestCase;
use Tuleap\Docman\Permissions\PermissionItemUpdater;

final class PermissionItemUpdaterFromRESTContextTest extends TestCase
{
    use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    /**
     * @var Mockery\MockInterface|PermissionItemUpdater
     */
    private $permissions_item_updater;
    /**
     * @var Docman_PermissionsManager|Mockery\MockInterface
     */
    private $permissions_manager;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|DocmanItemPermissionsForGroupsSetFactory
     */
    private $permissions_for_groups_set_factory;
    /**
     * @var PermissionItemUpdaterFromRESTContext
     */
    private $permissions_item_updater_rest;

    protected function setUp(): void
    {
        $this->permissions_item_updater           = Mockery::mock(PermissionItemUpdater::class);
        $this->permissions_manager                = Mockery::mock(Docman_PermissionsManager::class);
        $this->permissions_for_groups_set_factory = Mockery::mock(DocmanItemPermissionsForGroupsSetFactory::class);

        $this->permissions_item_updater_rest = new PermissionItemUpdaterFromRESTContext(
            $this->permissions_item_updater,
            $this->permissions_manager,
            $this->permissions_for_groups_set_factory
        );
    }

    public function testPermissionsCanBeUpdated(): void
    {
        $item           = Mockery::mock(Docman_Item::class);
        $representation = new DocmanItemPermissionsForGroupsSetRepresentation();

        $item->shouldReceive('getId')->andReturn(18);
        $this->permissions_manager->shouldReceive('userCanManage')->andReturn(true);
        $this->permissions_for_groups_set_factory->shouldReceive('fromRepresentation')
            ->andReturn(new DocmanItemPermissionsForGroupsSet([]));

        $this->permissions_item_updater->shouldReceive('updateItemPermissions')->once();

        $this->permissions_item_updater_rest->updateItemPermissions(
            $item,
            Mockery::mock(PFUser::class),
            $representation
        );
    }

    public function testPermissionsUpdateOfAFolderIsNotAppliedOnChildrenWhenNotRequested(): void
    {
        $folder = Mockery::mock(Docman_Folder::class);
        $folder->shouldReceive('getId')->andReturn(18);
        $this->permissions_manager->shouldReceive('userCanManage')->andReturn(true);

        $this->permissions_for_groups_set_factory->shouldReceive('fromRepresentation')
            ->andReturn(new DocmanItemPermissionsForGroupsSet([]));

        $representation                                = new DocmanFolderPermissionsForGroupsPUTRepresentation();
        $representation->apply_permissions_on_children = false;

        $this->permissions_item_updater->shouldReceive('updateItemPermissions')->once();

        $this->permissions_item_updater_rest->updateFolderPermissions(
            $folder,
            Mockery::mock(PFUser::class),
            $representation
        );
    }

    public function testPermissionsUpdateOfAFolderIsAppliedOnChildrenWhenRequested(): void
    {
        $folder = Mockery::mock(Docman_Folder::class);
        $folder->shouldReceive('getId')->andReturn(18);
        $this->permissions_manager->shouldReceive('userCanManage')->andReturn(true);
        $this->permissions_for_groups_set_factory->shouldReceive('fromRepresentation')
            ->andReturn(new DocmanItemPermissionsForGroupsSet([]));

        $representation                                = new DocmanFolderPermissionsForGroupsPUTRepresentation();
        $representation->apply_permissions_on_children = true;

        $this->permissions_item_updater->shouldReceive('updateFolderAndChildrenPermissions')->once();

        $this->permissions_item_updater_rest->updateFolderPermissions(
            $folder,
            Mockery::mock(PFUser::class),
            $representation
        );
    }

    public function testUpdateIsRejectedIfTheUserCanNotManageTheItem(): void
    {
        $item = Mockery::mock(Docman_Item::class);
        $item->shouldReceive('getId')->andReturn(78);

        $this->permissions_manager->shouldReceive('userCanManage')->andReturn(false);

        $this->expectException(RestException::class);
        $this->expectExceptionCode(403);

        $this->permissions_item_updater_rest->updateItemPermissions(
            $item,
            Mockery::mock(PFUser::class),
            new DocmanItemPermissionsForGroupsSetRepresentation()
        );
    }

    public function testUpdateItemPermissionsIsRejectedWhenTheUserCanNotManageIt(): void
    {
        $item = Mockery::mock(Docman_Item::class);
        $item->shouldReceive('getId')->andReturn(77);

        $this->permissions_manager->shouldReceive('userCanManage')->andReturn(false);

        $this->expectException(RestException::class);
        $this->expectExceptionCode(403);
        $this->permissions_item_updater_rest->updateItemPermissions(
            $item,
            Mockery::mock(PFUser::class),
            new DocmanItemPermissionsForGroupsSetRepresentation()
        );
    }

    public function testUpdateFolderPermissionsIsRejectedWhenTheUserCanNotManageIt(): void
    {
        $folder = Mockery::mock(Docman_Folder::class);
        $folder->shouldReceive('getId')->andReturn(77);

        $this->permissions_manager->shouldReceive('userCanManage')->andReturn(false);

        $this->expectException(RestException::class);
        $this->expectExceptionCode(403);
        $this->permissions_item_updater_rest->updateFolderPermissions(
            $folder,
            Mockery::mock(PFUser::class),
            new DocmanFolderPermissionsForGroupsPUTRepresentation()
        );
    }
}
