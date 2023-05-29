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

use FRSRelease;
use ProjectUGroup;
use Tuleap\FRS\FRSPermissionManager;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;

final class ReleasePermissionsForGroupsBuilderTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private int $project_id;
    private \Project $a_project;
    private \PFUser $an_frs_admin;
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
    private ReleasePermissionsForGroupsBuilder $builder;
    private FRSRelease $a_release;
    private string $a_release_id;

    public function setUp(): void
    {
        $this->project_id   = 350;
        $this->a_project    = ProjectTestBuilder::aProject()->withId($this->project_id)->withPublicName('foo')->build();
        $this->a_release_id = '34';
        $this->a_release    = new FRSRelease(['release_id' => $this->a_release_id]);
        $this->a_release->setProject($this->a_project);

        $this->an_frs_admin            = UserTestBuilder::aUser()->build();
        $this->permissions_manager     = $this->createMock(\IPermissionsManagerNG::class);
        $this->ugroup_manager          = $this->createMock(\UGroupManager::class);
        $this->frs_permissions_manager = $this->createMock(FRSPermissionManager::class);

        $this->builder = new ReleasePermissionsForGroupsBuilder($this->frs_permissions_manager, $this->permissions_manager, $this->ugroup_manager);
    }

    public function testItReturnsNullForNonFRSAdmins(): void
    {
        $a_random_user = UserTestBuilder::aUser()->build();
        $this->frs_permissions_manager->method('isAdmin')->with($this->a_project, $a_random_user)->willReturn(false);
        $this->permissions_manager->method('getAuthorizedUGroupIdsForProject')->willReturn([]);

        self::assertNull($this->builder->getRepresentation($a_random_user, $this->a_release));
    }

    public function testItReturnsAnEmptyRepresentation(): void
    {
        $this->permissions_manager->method('getAuthorizedUGroupIdsForProject')->willReturn([]);
        $this->frs_permissions_manager->method('isAdmin')->with($this->a_project, $this->an_frs_admin)->willReturn(true);

        $representation = $this->builder->getRepresentation($this->an_frs_admin, $this->a_release);

        self::assertNotNull($representation);
        self::assertEmpty($representation->can_read);
    }

    public function testItReturnsTheListOfUGroupsThatCanReadTheRelease(): void
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
            ->with($this->a_project, $this->a_release_id, FRSRelease::PERM_READ)
            ->willReturn([ProjectUGroup::PROJECT_MEMBERS, $static_ugroup_id]);

        $this->frs_permissions_manager->method('isAdmin')->with($this->a_project, $this->an_frs_admin)->willReturn(true);

        $representation = $this->builder->getRepresentation($this->an_frs_admin, $this->a_release);

        self::assertNotNull($representation);
        self::assertCount(2, $representation->can_read);
        self::assertEquals(ProjectUGroup::NORMALIZED_NAMES[ProjectUGroup::PROJECT_MEMBERS], $representation->can_read[0]->short_name);
        self::assertEquals('Developers', $representation->can_read[1]->short_name);
    }
}
