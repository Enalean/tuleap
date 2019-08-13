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
use Mockery;
use PFUser;
use PHPUnit\Framework\TestCase;
use ProjectManager;
use ProjectUGroup;
use Tuleap\Docman\Permissions\PermissionItemUpdater;
use Tuleap\Project\REST\UserGroupRetriever;
use UGroupManager;

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
     * @var Mockery\MockInterface|UGroupManager
     */
    private $ugroup_manager;
    /**
     * @var Mockery\MockInterface|UserGroupRetriever
     */
    private $ugroup_retriever;
    /**
     * @var Mockery\MockInterface|ProjectManager
     */
    private $project_manager;
    /**
     * @var PermissionItemUpdaterFromRESTContext
     */
    private $permissions_item_updater_rest;

    protected function setUp() : void
    {
        $this->permissions_item_updater = Mockery::mock(PermissionItemUpdater::class);
        $this->permissions_manager      = Mockery::mock(Docman_PermissionsManager::class);
        $this->ugroup_manager           = Mockery::mock(UGroupManager::class);
        $this->ugroup_retriever         = Mockery::mock(UserGroupRetriever::class);
        $this->project_manager          = Mockery::mock(ProjectManager::class);

        $this->permissions_item_updater_rest = new PermissionItemUpdaterFromRESTContext(
            $this->permissions_item_updater,
            $this->permissions_manager,
            $this->ugroup_manager,
            $this->ugroup_retriever,
            $this->project_manager
        );
    }

    public function testPermissionsCanBeUpdated() : void
    {
        $item                                       = Mockery::mock(Docman_Item::class);
        $representation                             = new DocmanItemPermissionsForGroupsPUTRepresentation();
        $register_users_read_representation         = new MinimalUserGroupRepresentationForUpdate();
        $project_member_write_representation        = new MinimalUserGroupRepresentationForUpdate();
        $user_group_management_representation_1     = new MinimalUserGroupRepresentationForUpdate();
        $user_group_management_representation_2     = new MinimalUserGroupRepresentationForUpdate();
        $register_users_read_representation->id     = ProjectUGroup::REGISTERED;
        $project_member_write_representation->id    = '102_' . ProjectUGroup::PROJECT_MEMBERS;
        $user_group_management_representation_1->id = '136';
        $user_group_management_representation_2->id = '137';
        $representation->can_read                   = [$register_users_read_representation, $project_member_write_representation];
        $representation->can_write                  = [$project_member_write_representation];
        $representation->can_manage                 = [
            $user_group_management_representation_2,
            $user_group_management_representation_1
        ];

        $item->shouldReceive('getId')->andReturn(18);
        $item->shouldReceive('getGroupId')->andReturn(102);
        $this->permissions_manager->shouldReceive('userCanManage')->andReturn(true);
        $this->project_manager->shouldReceive('getProject')->andReturn(Mockery::mock(\Project::class));

        $ugroup_manager_1  = $this->getUGroupMock($user_group_management_representation_1->id, 136, 102, true);
        $ugroup_manager_2  = $this->getUGroupMock($user_group_management_representation_2->id, 137, 102, true);
        $project_members   = $this->getUGroupMock($project_member_write_representation->id, ProjectUGroup::PROJECT_MEMBERS, 102, false);
        $registered_users  = $this->getUGroupMock($register_users_read_representation->id, ProjectUGroup::REGISTERED, null, false);

        $this->ugroup_manager->shouldReceive('getUGroups')->andReturn([
            $ugroup_manager_1,
            $ugroup_manager_2,
            $project_members,
            $registered_users,
            $this->getUGroupMock('102_' . ProjectUGroup::PROJECT_ADMIN, ProjectUGroup::PROJECT_ADMIN, 102, false),
        ]);

        $user = Mockery::mock(PFUser::class);
        $this->permissions_item_updater->shouldReceive('updateItemPermissions')
            ->once()
            ->with(
                $item,
                $user,
                [
                    ProjectUGroup::REGISTERED      => PermissionItemUpdater::PERMISSION_DEFINITION_READ,
                    ProjectUGroup::PROJECT_MEMBERS => PermissionItemUpdater::PERMISSION_DEFINITION_WRITE,
                    ProjectUGroup::PROJECT_ADMIN   => PermissionItemUpdater::PERMISSION_DEFINITION_NONE,
                    136                            => PermissionItemUpdater::PERMISSION_DEFINITION_MANAGE,
                    137                            => PermissionItemUpdater::PERMISSION_DEFINITION_MANAGE,
                ]
            );

        $this->permissions_item_updater_rest->updateItemPermissions(
            $item,
            $user,
            $representation
        );
    }

    public function testUpdateIsRejectedWhenAnUserGroupDoesNotExist() : void
    {
        $item                          = Mockery::mock(Docman_Item::class);
        $representation                = new DocmanItemPermissionsForGroupsPUTRepresentation();
        $user_group_representation     = new MinimalUserGroupRepresentationForUpdate();
        $user_group_representation->id = '999';
        $representation->can_read      = [$user_group_representation];

        $item->shouldReceive('getId')->andReturn(18);
        $item->shouldReceive('getGroupId')->andReturn(102);
        $this->permissions_manager->shouldReceive('userCanManage')->andReturn(true);
        $this->project_manager->shouldReceive('getProject')->andReturn(Mockery::mock(\Project::class));

        $this->ugroup_manager->shouldReceive('getUGroups')->andReturn([]);
        $ugroup_not_found = Mockery::mock(ProjectUGroup::class);
        $ugroup_not_found->shouldReceive('getId')->andReturn(0);
        $this->ugroup_retriever->shouldReceive('getExistingUserGroup')
            ->with($user_group_representation->id)->andThrow(new RestException(404));
        $this->expectException(RestException::class);
        $this->expectExceptionCode(400);

        $this->permissions_item_updater_rest->updateItemPermissions(
            $item,
            Mockery::mock(PFUser::class),
            $representation
        );
    }

    public function testUpdateIsRejectedWhenAnUserGroupIsFromADifferentProject() : void
    {
        $item                          = Mockery::mock(Docman_Item::class);
        $representation                = new DocmanItemPermissionsForGroupsPUTRepresentation();
        $user_group_representation     = new MinimalUserGroupRepresentationForUpdate();
        $user_group_representation->id = '103_3';
        $representation->can_write     = [$user_group_representation];

        $item->shouldReceive('getId')->andReturn(18);
        $item->shouldReceive('getGroupId')->andReturn(102);
        $this->permissions_manager->shouldReceive('userCanManage')->andReturn(true);
        $this->project_manager->shouldReceive('getProject')->andReturn(Mockery::mock(\Project::class));

        $this->ugroup_manager->shouldReceive('getUGroups')->andReturn([]);
        $this->getUGroupMock($user_group_representation->id, 3, 103, false);

        $this->expectException(RestException::class);
        $this->expectExceptionCode(400);

        $this->permissions_item_updater_rest->updateItemPermissions(
            $item,
            Mockery::mock(PFUser::class),
            $representation
        );
    }

    public function testUpdateIsRejectedIfTheUserCanNotManageTheItem() : void
    {
        $item = Mockery::mock(Docman_Item::class);
        $item->shouldReceive('getId')->andReturn(78);

        $this->permissions_manager->shouldReceive('userCanManage')->andReturn(false);

        $this->expectException(RestException::class);
        $this->expectExceptionCode(403);

        $this->permissions_item_updater_rest->updateItemPermissions(
            $item,
            Mockery::mock(PFUser::class),
            new DocmanItemPermissionsForGroupsPUTRepresentation()
        );
    }

    public function testUpdateIsRejectedWhenAnIncorrectUGroupIdentifierIsGiven() : void
    {
        $item                          = Mockery::mock(Docman_Item::class);
        $representation                = new DocmanItemPermissionsForGroupsPUTRepresentation();
        $user_group_representation     = new MinimalUserGroupRepresentationForUpdate();
        $user_group_representation->id = 'invalid_ugroup_identifier';
        $representation->can_read      = [$user_group_representation];

        $item->shouldReceive('getId')->andReturn(77);
        $item->shouldReceive('getGroupId')->andReturn(102);
        $this->permissions_manager->shouldReceive('userCanManage')->andReturn(true);
        $this->project_manager->shouldReceive('getProject')->andReturn(Mockery::mock(\Project::class));

        $this->ugroup_manager->shouldReceive('getUGroups')->andReturn([]);
        $this->ugroup_retriever->shouldReceive('getExistingUserGroup')
            ->with($user_group_representation->id)->andThrow(new RestException(400));

        $this->expectException(RestException::class);
        $this->expectExceptionCode(400);

        $this->permissions_item_updater_rest->updateItemPermissions(
            $item,
            Mockery::mock(PFUser::class),
            $representation
        );
    }

    private function getUGroupMock(string $identifier, int $id, ?int $project_id, bool $is_static) : ProjectUGroup
    {
        $ugroup_mock = Mockery::mock(ProjectUGroup::class);
        $ugroup_mock->shouldReceive('getId')->andReturn($id);
        $ugroup_mock->shouldReceive('isStatic')->andReturn($is_static);
        $ugroup_mock->shouldReceive('getProjectId')->andReturn($project_id);
        $this->ugroup_retriever->shouldReceive('getExistingUserGroup')->with($identifier)->andReturn($ugroup_mock);
        return $ugroup_mock;
    }
}
