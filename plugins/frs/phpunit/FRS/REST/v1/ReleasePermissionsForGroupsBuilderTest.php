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
use Mockery as M;
use PHPUnit\Framework\TestCase;
use ProjectUGroup;
use Tuleap\FRS\FRSPermissionManager;

final class ReleasePermissionsForGroupsBuilderTest extends TestCase
{
    use M\Adapter\Phpunit\MockeryPHPUnitIntegration;

    /**
     * @var int
     */
    private $project_id;
    /**
     * @var M\MockInterface|\Project
     */
    private $a_project;
    /**
     * @var M\MockInterface|\PFUser
     */
    private $an_frs_admin;
    /**
     * @var \IPermissionsManagerNG|M\MockInterface
     */
    private $permissions_manager;
    /**
     * @var M\MockInterface|\UGroupManager
     */
    private $ugroup_manager;
    /**
     * @var M\MockInterface|FRSPermissionManager
     */
    private $frs_permissions_manager;
    /**
     * @var ReleasePermissionsForGroupsBuilder
     */
    private $builder;
    /**
     * @var FRSRelease
     */
    private $a_release;
    /**
     * @var string
     */
    private $a_release_id;

    public function setUp(): void
    {
        $this->project_id = 350;
        $this->a_project = M::mock(\Project::class, ['getID' => (string) $this->project_id, 'getPublicName' => 'foo']);
        $this->a_release_id = '34';
        $this->a_release = new FRSRelease(['release_id' => $this->a_release_id]);
        $this->a_release->setProject($this->a_project);
        $this->an_frs_admin = M::mock(\PFUser::class);
        $this->permissions_manager = M::mock(\IPermissionsManagerNG::class);
        $this->permissions_manager->shouldReceive('getAuthorizedUGroupIdsForProject')->andReturn([])->byDefault();
        $this->ugroup_manager = M::mock(\UGroupManager::class);
        $this->frs_permissions_manager = M::mock(FRSPermissionManager::class);
        $this->frs_permissions_manager->shouldReceive('isAdmin')->with($this->a_project, $this->an_frs_admin)->andReturnTrue();

        $this->builder = new ReleasePermissionsForGroupsBuilder($this->frs_permissions_manager, $this->permissions_manager, $this->ugroup_manager);
    }

    public function testItReturnsNullForNonFRSAdmins(): void
    {
        $a_random_user = M::mock(\PFUser::class);
        $this->frs_permissions_manager->shouldReceive('isAdmin')->with($this->a_project, $a_random_user)->andReturnFalse();

        $this->assertNull($this->builder->getRepresentation($a_random_user, $this->a_release));
    }

    public function testItReturnsAnEmptyRepresentation(): void
    {
        $representation = $this->builder->getRepresentation($this->an_frs_admin, $this->a_release);
        $this->assertEmpty($representation->can_read);
    }

    public function testItReturnsTheListOfUGroupsThatCanReadTheRelease(): void
    {
        $project_members = new ProjectUGroup([
            'ugroup_id' => ProjectUGroup::PROJECT_MEMBERS,
            'name' => ProjectUGroup::$normalized_names[ProjectUGroup::PROJECT_MEMBERS],
            'group_id' => $this->project_id,
        ]);
        $this->ugroup_manager->shouldReceive('getUGroup')->with($this->a_project, ProjectUGroup::PROJECT_MEMBERS)->andReturn($project_members);

        $static_ugroup_id = 345;
        $static_ugroup = new ProjectUGroup([
            'ugroup_id' => $static_ugroup_id,
            'name' => 'Developers',
            'group_id' => $this->project_id,
        ]);
        $this->ugroup_manager->shouldReceive('getUGroup')->with($this->a_project, $static_ugroup_id)->andReturn($static_ugroup);

        $this->permissions_manager->shouldReceive('getAuthorizedUGroupIdsForProject')
            ->with($this->a_project, $this->a_release_id, FRSRelease::PERM_READ)
            ->andReturn([ProjectUGroup::PROJECT_MEMBERS, $static_ugroup_id]);

        $representation = $this->builder->getRepresentation($this->an_frs_admin, $this->a_release);
        $this->assertCount(2, $representation->can_read);

        $this->assertEquals(ProjectUGroup::$normalized_names[ProjectUGroup::PROJECT_MEMBERS], $representation->can_read[0]->short_name);
        $this->assertEquals('Developers', $representation->can_read[1]->short_name);
    }
}
