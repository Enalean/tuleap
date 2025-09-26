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
use PHPUnit\Framework\MockObject\MockObject;
use ProjectManager;
use ProjectUGroup;
use Tuleap\Docman\Permissions\PermissionItemUpdater;
use Tuleap\Project\REST\UserGroupRetriever;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use UGroupManager;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class DocmanItemPermissionsForGroupsSetFactoryTest extends TestCase
{
    private UGroupManager&MockObject $ugroup_manager;
    private UserGroupRetriever&MockObject $ugroup_retriever;
    private ProjectManager&MockObject $project_manager;
    private DocmanItemPermissionsForGroupsSetFactory $permissions_for_groups_set_factory;

    #[\Override]
    protected function setUp(): void
    {
        $this->ugroup_manager   = $this->createMock(UGroupManager::class);
        $this->ugroup_retriever = $this->createMock(UserGroupRetriever::class);
        $this->project_manager  = $this->createMock(ProjectManager::class);

        $this->permissions_for_groups_set_factory = new DocmanItemPermissionsForGroupsSetFactory(
            $this->ugroup_manager,
            $this->ugroup_retriever,
            $this->project_manager
        );
    }

    public function testTransformationFromRepresentationWithValidData(): void
    {
        $item                                       = new Docman_Item(['item_id' => 18, 'group_id' => 102]);
        $representation                             = new DocmanItemPermissionsForGroupsSetRepresentation();
        $register_users_read_representation         = new MinimalUserGroupRepresentationForUpdate();
        $project_member_write_representation        = new MinimalUserGroupRepresentationForUpdate();
        $user_group_management_representation_1     = new MinimalUserGroupRepresentationForUpdate();
        $user_group_management_representation_2     = new MinimalUserGroupRepresentationForUpdate();
        $register_users_read_representation->id     = (string) ProjectUGroup::REGISTERED;
        $project_member_write_representation->id    = '102_' . ProjectUGroup::PROJECT_MEMBERS;
        $user_group_management_representation_1->id = '136';
        $user_group_management_representation_2->id = '137';
        $representation->can_read                   = [$register_users_read_representation, $project_member_write_representation];
        $representation->can_write                  = [$project_member_write_representation];
        $representation->can_manage                 = [$user_group_management_representation_2, $user_group_management_representation_1];

        $this->project_manager->method('getProject')->willReturn(ProjectTestBuilder::aProject()->build());

        $ugroup_manager_1 = $this->getUGroupMock(136, 102, true);
        $ugroup_manager_2 = $this->getUGroupMock(137, 102, true);
        $project_members  = $this->getUGroupMock(ProjectUGroup::PROJECT_MEMBERS, 102, false);
        $registered_users = $this->getUGroupMock(ProjectUGroup::REGISTERED, null, false);
        $project_u_group  = $this->getUGroupMock(ProjectUGroup::PROJECT_ADMIN, 102, false);
        $this->ugroup_retriever->method('getExistingUserGroup')->willReturnCallback(static fn(string $id) => match ($id) {
            $user_group_management_representation_1->id => $ugroup_manager_1,
            $user_group_management_representation_2->id => $ugroup_manager_2,
            $project_member_write_representation->id    => $project_members,
            $register_users_read_representation->id     => $registered_users,
            '102_' . ProjectUGroup::PROJECT_ADMIN       => $project_u_group,
        });
        $this->ugroup_manager->method('getUGroups')->willReturn([
            $ugroup_manager_1,
            $ugroup_manager_2,
            $project_members,
            $registered_users,
            $project_u_group,
        ]);

        $permissions_set = $this->permissions_for_groups_set_factory->fromRepresentation($item, $representation);

        self::assertEquals(
            [
                ProjectUGroup::REGISTERED      => PermissionItemUpdater::PERMISSION_DEFINITION_READ,
                ProjectUGroup::PROJECT_MEMBERS => PermissionItemUpdater::PERMISSION_DEFINITION_WRITE,
                ProjectUGroup::PROJECT_ADMIN   => PermissionItemUpdater::PERMISSION_DEFINITION_NONE,
                136                            => PermissionItemUpdater::PERMISSION_DEFINITION_MANAGE,
                137                            => PermissionItemUpdater::PERMISSION_DEFINITION_MANAGE,
            ],
            $permissions_set->toPermissionsPerUGroupIDAndTypeArray()
        );
    }

    public function testTransformationFromRepresentationFailsWhenAnUserGroupDoesNotExist(): void
    {
        $item                          = new Docman_Item(['item_id' => 18, 'group_id' => 102]);
        $representation                = new DocmanItemPermissionsForGroupsSetRepresentation();
        $user_group_representation     = new MinimalUserGroupRepresentationForUpdate();
        $user_group_representation->id = '999';
        $representation->can_read      = [$user_group_representation];

        $this->project_manager->method('getProject')->willReturn(ProjectTestBuilder::aProject()->build());

        $this->ugroup_manager->method('getUGroups')->willReturn([]);
        $ugroup_not_found = $this->createMock(ProjectUGroup::class);
        $ugroup_not_found->method('getId')->willReturn(0);
        $this->ugroup_retriever->method('getExistingUserGroup')
            ->with($user_group_representation->id)->willThrowException(new RestException(404));

        $this->expectException(RestException::class);
        $this->expectExceptionCode(400);

        $this->permissions_for_groups_set_factory->fromRepresentation($item, $representation);
    }

    public function testTransformationFromRepresentationFailsWhenAnUserGroupIsFromADifferentProject(): void
    {
        $item                          = new Docman_Item(['item_id' => 18, 'group_id' => 102]);
        $representation                = new DocmanItemPermissionsForGroupsSetRepresentation();
        $user_group_representation     = new MinimalUserGroupRepresentationForUpdate();
        $user_group_representation->id = '103_3';
        $representation->can_write     = [$user_group_representation];

        $this->project_manager->method('getProject')->willReturn(ProjectTestBuilder::aProject()->build());

        $this->ugroup_manager->method('getUGroups')->willReturn([]);
        $ugroup = $this->getUGroupMock(3, 103, false);
        $this->ugroup_retriever->method('getExistingUserGroup')->with($user_group_representation->id)->willReturn($ugroup);

        $this->expectException(RestException::class);
        $this->expectExceptionCode(400);

        $this->permissions_for_groups_set_factory->fromRepresentation($item, $representation);
    }

    public function testTransformationFromRepresentationFailsWhenAnIncorrectUGroupIdentifierIsGiven(): void
    {
        $item                          = new Docman_Item(['item_id' => 77, 'group_id' => 102]);
        $representation                = new DocmanItemPermissionsForGroupsSetRepresentation();
        $user_group_representation     = new MinimalUserGroupRepresentationForUpdate();
        $user_group_representation->id = 'invalid_ugroup_identifier';
        $representation->can_read      = [$user_group_representation];

        $this->project_manager->method('getProject')->willReturn(ProjectTestBuilder::aProject()->build());

        $this->ugroup_manager->method('getUGroups')->willReturn([]);
        $this->ugroup_retriever->method('getExistingUserGroup')
            ->with($user_group_representation->id)->willThrowException(new RestException(400));

        $this->expectException(RestException::class);
        $this->expectExceptionCode(400);

        $this->permissions_for_groups_set_factory->fromRepresentation($item, $representation);
    }

    private function getUGroupMock(int $id, ?int $project_id, bool $is_static): ProjectUGroup
    {
        $ugroup_mock = $this->createMock(ProjectUGroup::class);
        $ugroup_mock->method('getId')->willReturn($id);
        $ugroup_mock->method('isStatic')->willReturn($is_static);
        $ugroup_mock->method('getProjectId')->willReturn($project_id);
        return $ugroup_mock;
    }
}
