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
 *
 */

declare(strict_types=1);

namespace Tuleap\FRS\REST\v1;

use Luracast\Restler\RestException;
use Project;
use ProjectUGroup;
use Service;
use Tuleap\FRS\FRSPermission;
use Tuleap\FRS\FRSPermissionFactory;
use Tuleap\FRS\FRSPermissionManager;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;

final class ServiceRepresentationBuilderTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private ServiceRepresentationBuilder $builder;
    private Project $project;
    private \PFUser $frs_admin_user;
    /**
     * @var FRSPermissionManager&\PHPUnit\Framework\MockObject\MockObject
     */
    private $frs_permissions_manager;
    /**
     * @var FRSPermissionFactory&\PHPUnit\Framework\MockObject\MockObject
     */
    private $frs_permissions_factory;
    private int $project_id;
    /**
     * @var \UGroupManager&\PHPUnit\Framework\MockObject\MockObject
     */
    private $ugroup_manager;

    protected function setUp(): void
    {
        $this->project_id              = 120;
        $this->project                 = ProjectTestBuilder::aProject()->withId($this->project_id)->withUsedService(Service::FILE)->build();
        $this->frs_admin_user          = UserTestBuilder::aUser()->build();
        $this->frs_permissions_manager = $this->createMock(FRSPermissionManager::class);
        $this->frs_permissions_factory = $this->createMock(FRSPermissionFactory::class);

        $this->ugroup_manager = $this->createMock(\UGroupManager::class);
        $this->builder        = new ServiceRepresentationBuilder($this->frs_permissions_manager, $this->frs_permissions_factory, $this->ugroup_manager);
    }

    private function mockDefaultIsAdmin(): void
    {
        $this->frs_permissions_manager->method('isAdmin')->with($this->project, $this->frs_admin_user)->willReturn(true);
    }

    private function mockDefaultFRSPermissions(): void
    {
        $this->mockDefaultIsAdmin();

        $this->frs_permissions_factory->method('getFrsUGroupsByPermission')->willReturnMap([
            [$this->project, FRSPermission::FRS_READER, []],
            [$this->project, FRSPermission::FRS_ADMIN, []],
        ]);
    }

    public function testItReturnsEmptyPermissionsForGroupsForNonFRSAdmins(): void
    {
        $this->frs_permissions_factory->method('getFrsUGroupsByPermission')->willReturnMap([
            [$this->project, FRSPermission::FRS_READER, []],
            [$this->project, FRSPermission::FRS_ADMIN, []],
        ]);

        $a_random_user = UserTestBuilder::aUser()->build();
        $this->frs_permissions_manager->method('isAdmin')->with($this->project, $a_random_user)->willReturn(false);

        $representation = $this->builder->getServiceRepresentation($a_random_user, $this->project);
        self::assertNull($representation->permissions_for_groups);
    }

    public function testItReturnsThatNoGroupsHaveTheCanReadPermissions(): void
    {
        $this->mockDefaultFRSPermissions();

        $representation = $this->builder->getServiceRepresentation($this->frs_admin_user, $this->project);

        self::assertNotNull($representation->permissions_for_groups);
        self::assertEmpty($representation->permissions_for_groups->can_read);
    }

    public function testItReturnsGroupsWithCanReadPermissions(): void
    {
        $project_members = new ProjectUGroup([
            'ugroup_id' => (string) ProjectUGroup::PROJECT_MEMBERS,
            'name' => ProjectUGroup::NORMALIZED_NAMES[ProjectUGroup::PROJECT_MEMBERS],
            'group_id' => (string) $this->project_id,
        ]);

        $static_ugroup_id = 345;
        $static_ugroup    = new ProjectUGroup([
            'ugroup_id' => (string) $static_ugroup_id,
            'name' => 'Developers',
            'group_id' => (string) $this->project_id,
        ]);

        $this->ugroup_manager->method('getUGroup')->willReturnMap([
            [$this->project, ProjectUGroup::PROJECT_MEMBERS, $project_members],
            [$this->project, $static_ugroup_id, $static_ugroup],
            [],
        ]);

        $this->frs_permissions_factory->method('getFrsUGroupsByPermission')->willReturnMap([
            [
                $this->project,
                FRSPermission::FRS_READER,
                [
                    new FRSPermission(ProjectUGroup::PROJECT_MEMBERS),
                    new FRSPermission($static_ugroup_id),
                ],
            ],
            [$this->project, FRSPermission::FRS_ADMIN, []],
        ]);

        $this->mockDefaultIsAdmin();

        $representation = $this->builder->getServiceRepresentation($this->frs_admin_user, $this->project);

        self::assertNotNull($representation->permissions_for_groups);
        self::assertCount(2, $representation->permissions_for_groups->can_read);
        self::assertEquals(ProjectUGroup::NORMALIZED_NAMES[ProjectUGroup::PROJECT_MEMBERS], $representation->permissions_for_groups->can_read[0]->short_name);
        self::assertEquals('Developers', $representation->permissions_for_groups->can_read[1]->short_name);
    }

    public function testItReturnsGroupsWithCanAdminPermissions(): void
    {
        $static_ugroup_id = 345;
        $static_ugroup    = new ProjectUGroup([
            'ugroup_id' => (string) $static_ugroup_id,
            'name' => 'Developers',
            'group_id' => (string) $this->project_id,
        ]);
        $this->ugroup_manager->method('getUGroup')->with($this->project, $static_ugroup_id)->willReturn($static_ugroup);

        $this->mockDefaultIsAdmin();

        $this->frs_permissions_factory->method('getFrsUGroupsByPermission')->willReturnMap([
            [$this->project, FRSPermission::FRS_READER, []],
            [$this->project, FRSPermission::FRS_ADMIN, [new FRSPermission($static_ugroup_id)]],
        ]);

        $representation = $this->builder->getServiceRepresentation($this->frs_admin_user, $this->project);

        self::assertNotNull($representation->permissions_for_groups);
        self::assertCount(1, $representation->permissions_for_groups->can_admin);
        self::assertEquals('Developers', $representation->permissions_for_groups->can_admin[0]->short_name);
    }

    public function testItReturnsGroupsWithBothdPermissions(): void
    {
        $project_members = new ProjectUGroup([
            'ugroup_id' => (string) ProjectUGroup::PROJECT_MEMBERS,
            'name' => ProjectUGroup::NORMALIZED_NAMES[ProjectUGroup::PROJECT_MEMBERS],
            'group_id' => (string) $this->project_id,
        ]);

        $static_ugroup_id = 345;
        $static_ugroup    = new ProjectUGroup([
            'ugroup_id' => (string) $static_ugroup_id,
            'name' => 'Developers',
            'group_id' => (string) $this->project_id,
        ]);

        $this->ugroup_manager->method('getUGroup')->willReturnMap([
            [$this->project, ProjectUGroup::PROJECT_MEMBERS, $project_members],
            [$this->project, $static_ugroup_id, $static_ugroup],
            [],
        ]);

        $this->frs_permissions_factory->method('getFrsUGroupsByPermission')->willReturnMap([
            [$this->project, FRSPermission::FRS_READER, [new FRSPermission(ProjectUGroup::PROJECT_MEMBERS)]],
            [$this->project, FRSPermission::FRS_ADMIN, [new FRSPermission($static_ugroup_id)]],
        ]);

        $this->mockDefaultIsAdmin();

        $representation = $this->builder->getServiceRepresentation($this->frs_admin_user, $this->project);

        self::assertNotNull($representation->permissions_for_groups);
        self::assertCount(1, $representation->permissions_for_groups->can_read);
        self::assertEquals(ProjectUGroup::NORMALIZED_NAMES[ProjectUGroup::PROJECT_MEMBERS], $representation->permissions_for_groups->can_read[0]->short_name);
        self::assertCount(1, $representation->permissions_for_groups->can_admin);
        self::assertEquals('Developers', $representation->permissions_for_groups->can_admin[0]->short_name);
    }

    public function testItThrowAnExceptionIfProjectDoesntUsesFiles(): void
    {
        $project = ProjectTestBuilder::aProject()->withId($this->project_id)->withoutServices()->build();

        $this->expectException(RestException::class);

        $this->builder->getServiceRepresentation($this->frs_admin_user, $project);
    }

    public function testItReturnsAnEmptyListWhenNobodyIsSelected(): void
    {
        $nobody = new ProjectUGroup([
            'ugroup_id' => (string) ProjectUGroup::NONE,
            'name' => ProjectUGroup::NORMALIZED_NAMES[ProjectUGroup::NONE],
            'group_id' => (string) $this->project_id,
        ]);
        $this->ugroup_manager->method('getUGroup')->with($this->project, ProjectUGroup::NONE)->willReturn($nobody);

        $this->frs_permissions_factory->method('getFrsUGroupsByPermission')->willReturnMap([
            [$this->project, FRSPermission::FRS_READER, [new FRSPermission((string) ProjectUGroup::NONE)]],
            [$this->project, FRSPermission::FRS_ADMIN, []],
        ]);

        $this->mockDefaultIsAdmin();

        $representation = $this->builder->getServiceRepresentation($this->frs_admin_user, $this->project);

        self::assertNotNull($representation->permissions_for_groups);
        self::assertCount(0, $representation->permissions_for_groups->can_read);
    }
}
