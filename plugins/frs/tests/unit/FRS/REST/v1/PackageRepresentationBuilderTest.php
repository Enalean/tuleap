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

use FRSPackage;
use ProjectUGroup;
use Tuleap\FRS\FRSPermissionManager;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;

final class PackageRepresentationBuilderTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private PackageRepresentationBuilder $builder;
    private \Project $a_project;
    private \PFUser $an_frs_admin;
    private FRSPackage $a_package;
    private int $package_id;
    private int $project_id;
    /**
     * @var \IPermissionsManagerNG&\PHPUnit\Framework\MockObject\MockObject
     */
    private $permissions_manager;
    /**
     * @var \UGroupManager&\PHPUnit\Framework\MockObject\MockObject
     */
    private $ugroup_manager;
    /**
     * @var FRSPermissionManager&\PHPUnit\Framework\MockObject\MockObject
     */
    private $frs_permissions_manager;

    protected function setUp(): void
    {
        $this->package_id              = 12;
        $this->project_id              = 350;
        $this->a_project               = ProjectTestBuilder::aProject()->withId($this->project_id)->build();
        $this->an_frs_admin            = UserTestBuilder::aUser()->withId(101)->build();
        $this->a_package               = new FRSPackage(['package_id' => $this->package_id]);
        $this->permissions_manager     = $this->createMock(\IPermissionsManagerNG::class);
        $this->ugroup_manager          = $this->createMock(\UGroupManager::class);
        $this->frs_permissions_manager = $this->createMock(FRSPermissionManager::class);

        $this->builder = new PackageRepresentationBuilder($this->permissions_manager, $this->ugroup_manager, $this->frs_permissions_manager);
    }

    public function testItReturnsThePackageId(): void
    {
        $this->permissions_manager->method('getAuthorizedUGroupIdsForProject')->willReturn([]);
        $this->frs_permissions_manager->method('isAdmin')->with($this->a_project, $this->an_frs_admin)->willReturn(true);

        $representation = $this->builder->getPackageForUser($this->an_frs_admin, $this->a_package, $this->a_project);
        self::assertEquals($this->package_id, $representation->id);
    }

    public function testItReturnsTheProject(): void
    {
        $this->permissions_manager->method('getAuthorizedUGroupIdsForProject')->willReturn([]);
        $this->frs_permissions_manager->method('isAdmin')->with($this->a_project, $this->an_frs_admin)->willReturn(true);

        $representation = $this->builder->getPackageForUser($this->an_frs_admin, $this->a_package, $this->a_project);
        self::assertEquals($this->project_id, $representation->project->id);
    }

    public function testRandomUserCannotReadPermissionsForGroups(): void
    {
        $this->permissions_manager->method('getAuthorizedUGroupIdsForProject')->willReturn([]);

        $a_random_user = UserTestBuilder::aUser()->withId(102)->build();
        $this->frs_permissions_manager->method('isAdmin')->willReturnMap([
            [$this->a_project, $a_random_user, false],
            [$this->a_project, $this->an_frs_admin, true],
        ]);

        $representation = $this->builder->getPackageForUser($a_random_user, $this->a_package, $this->a_project);

        self::assertNull($representation->permissions_for_groups);
    }

    public function testItReturnsThePermissionsForGroups(): void
    {
        $project_members = new ProjectUGroup([
            'ugroup_id' => ProjectUGroup::PROJECT_MEMBERS,
            'name' => ProjectUGroup::NORMALIZED_NAMES[ProjectUGroup::PROJECT_MEMBERS],
            'group_id' => $this->project_id,
        ]);

        $static_ugroup_id = 345;
        $static_ugroup    = new ProjectUGroup([
            'ugroup_id' => $static_ugroup_id,
            'name' => 'Developers',
            'group_id' => $this->project_id,
        ]);

        $this->ugroup_manager->method('getUGroup')->willReturnMap([
            [$this->a_project, $static_ugroup_id, $static_ugroup],
            [$this->a_project, ProjectUGroup::PROJECT_MEMBERS, $project_members],
        ]);

        $this->permissions_manager->method('getAuthorizedUGroupIdsForProject')
            ->with($this->a_project, $this->package_id, FRSPackage::PERM_READ)
            ->willReturn([ProjectUGroup::PROJECT_MEMBERS, $static_ugroup_id]);

        $this->frs_permissions_manager->method('isAdmin')->with($this->a_project, $this->an_frs_admin)->willReturn(true);

        $representation = $this->builder->getPackageForUser($this->an_frs_admin, $this->a_package, $this->a_project);

        self::assertNotNull($representation->permissions_for_groups);
        self::assertCount(2, $representation->permissions_for_groups->can_read);
        self::assertEquals(ProjectUGroup::NORMALIZED_NAMES[ProjectUGroup::PROJECT_MEMBERS], $representation->permissions_for_groups->can_read[0]->short_name);
        self::assertEquals('Developers', $representation->permissions_for_groups->can_read[1]->short_name);
    }
}
