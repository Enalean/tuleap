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
use Mockery as M;
use PHPUnit\Framework\TestCase;
use Project;
use ProjectUGroup;
use Tuleap\FRS\FRSPermission;
use Tuleap\FRS\FRSPermissionFactory;
use Tuleap\FRS\FRSPermissionManager;

final class ServiceRepresentationBuilderTest extends TestCase
{
    use M\Adapter\Phpunit\MockeryPHPUnitIntegration;

    /**
     * @var ServiceRepresentationBuilder
     */
    private $builder;
    /**
     * @var M\MockInterface|Project
     */
    private $project;
    /**
     * @var M\MockInterface|\PFUser
     */
    private $frs_admin_user;
    /**
     * @var M\MockInterface|FRSPermissionManager
     */
    private $frs_permissions_manager;
    /**
     * @var M\MockInterface|FRSPermissionFactory
     */
    private $frs_permissions_factory;
    /**
     * @var int
     */
    private $project_id;
    /**
     * @var M\MockInterface|\UGroupManager
     */
    private $ugroup_manager;

    protected function setUp(): void
    {
        $this->project_id = 120;
        $this->project = M::mock(Project::class, ['getID' => (string) $this->project_id]);
        $this->project->shouldReceive('usesFile')->andReturnTrue()->byDefault();
        $this->frs_admin_user = M::mock(\PFUser::class);
        $this->frs_permissions_manager = M::mock(FRSPermissionManager::class);
        $this->frs_permissions_manager->shouldReceive('isAdmin')->with($this->project, $this->frs_admin_user)->andReturnTrue();
        $this->frs_permissions_factory = M::mock(FRSPermissionFactory::class);
        $this->frs_permissions_factory->shouldReceive('getFrsUGroupsByPermission')->with($this->project, FRSPermission::FRS_READER)->andReturn([])->byDefault();
        $this->frs_permissions_factory->shouldReceive('getFrsUGroupsByPermission')->with($this->project, FRSPermission::FRS_ADMIN)->andReturn([])->byDefault();
        $this->ugroup_manager = M::mock(\UGroupManager::class);
        $this->builder = new ServiceRepresentationBuilder($this->frs_permissions_manager, $this->frs_permissions_factory, $this->ugroup_manager);
    }

    public function testItReturnsEmptyPermissionsForGroupsForNonFRSAdmins(): void
    {
        $a_random_user = M::mock(\PFUser::class);
        $this->frs_permissions_manager->shouldReceive('isAdmin')->with($this->project, $a_random_user)->andReturnFalse();

        $representation = $this->builder->getServiceRepresentation($a_random_user, $this->project);
        $this->assertNull($representation->permissions_for_groups);
    }

    public function testItReturnsThatNoGroupsHaveTheCanReadPermissions(): void
    {
        $representation = $this->builder->getServiceRepresentation($this->frs_admin_user, $this->project);
        $this->assertEmpty($representation->permissions_for_groups->can_read);
    }

    public function testItReturnsGroupsWithCanReadPermissions(): void
    {
        $project_members = new ProjectUGroup([
            'ugroup_id' => (string) ProjectUGroup::PROJECT_MEMBERS,
            'name' => ProjectUGroup::$normalized_names[ProjectUGroup::PROJECT_MEMBERS],
            'group_id' => (string) $this->project_id,
        ]);
        $this->ugroup_manager->shouldReceive('getUGroup')->with($this->project, ProjectUGroup::PROJECT_MEMBERS)->andReturn($project_members);

        $static_ugroup_id = 345;
        $static_ugroup = new ProjectUGroup([
            'ugroup_id' => (string) $static_ugroup_id,
            'name' => 'Developers',
            'group_id' => (string) $this->project_id,
        ]);
        $this->ugroup_manager->shouldReceive('getUGroup')->with($this->project, $static_ugroup_id)->andReturn($static_ugroup);

        $this->frs_permissions_factory->shouldReceive('getFrsUGroupsByPermission')->with($this->project, FRSPermission::FRS_READER)->andReturn([
            new FRSPermission(ProjectUGroup::PROJECT_MEMBERS),
            new FRSPermission($static_ugroup_id),
        ]);

        $representation = $this->builder->getServiceRepresentation($this->frs_admin_user, $this->project);
        $this->assertCount(2, $representation->permissions_for_groups->can_read);
        $this->assertEquals(ProjectUGroup::$normalized_names[ProjectUGroup::PROJECT_MEMBERS], $representation->permissions_for_groups->can_read[0]->short_name);
        $this->assertEquals('Developers', $representation->permissions_for_groups->can_read[1]->short_name);
    }

    public function testItReturnsGroupsWithCanAdminPermissions(): void
    {
        $static_ugroup_id = 345;
        $static_ugroup = new ProjectUGroup([
            'ugroup_id' => (string) $static_ugroup_id,
            'name' => 'Developers',
            'group_id' => (string) $this->project_id,
        ]);
        $this->ugroup_manager->shouldReceive('getUGroup')->with($this->project, $static_ugroup_id)->andReturn($static_ugroup);

        $this->frs_permissions_factory->shouldReceive('getFrsUGroupsByPermission')->with($this->project, FRSPermission::FRS_ADMIN)->andReturn([
            new FRSPermission($static_ugroup_id),
        ]);

        $representation = $this->builder->getServiceRepresentation($this->frs_admin_user, $this->project);
        $this->assertCount(1, $representation->permissions_for_groups->can_admin);
        $this->assertEquals('Developers', $representation->permissions_for_groups->can_admin[0]->short_name);
    }

    public function testItReturnsGroupsWithBothdPermissions(): void
    {
        $project_members = new ProjectUGroup([
            'ugroup_id' => (string) ProjectUGroup::PROJECT_MEMBERS,
            'name' => ProjectUGroup::$normalized_names[ProjectUGroup::PROJECT_MEMBERS],
            'group_id' => (string) $this->project_id,
        ]);
        $this->ugroup_manager->shouldReceive('getUGroup')->with($this->project, ProjectUGroup::PROJECT_MEMBERS)->andReturn($project_members);

        $static_ugroup_id = 345;
        $static_ugroup = new ProjectUGroup([
            'ugroup_id' => (string) $static_ugroup_id,
            'name' => 'Developers',
            'group_id' => (string) $this->project_id,
        ]);
        $this->ugroup_manager->shouldReceive('getUGroup')->with($this->project, $static_ugroup_id)->andReturn($static_ugroup);

        $this->frs_permissions_factory->shouldReceive('getFrsUGroupsByPermission')->with($this->project, FRSPermission::FRS_READER)->andReturn([
            new FRSPermission(ProjectUGroup::PROJECT_MEMBERS),
        ]);
        $this->frs_permissions_factory->shouldReceive('getFrsUGroupsByPermission')->with($this->project, FRSPermission::FRS_ADMIN)->andReturn([
            new FRSPermission($static_ugroup_id),
        ]);

        $representation = $this->builder->getServiceRepresentation($this->frs_admin_user, $this->project);
        $this->assertCount(1, $representation->permissions_for_groups->can_read);
        $this->assertEquals(ProjectUGroup::$normalized_names[ProjectUGroup::PROJECT_MEMBERS], $representation->permissions_for_groups->can_read[0]->short_name);

        $this->assertCount(1, $representation->permissions_for_groups->can_admin);
        $this->assertEquals('Developers', $representation->permissions_for_groups->can_admin[0]->short_name);
    }

    public function testItThrowAnExceptionIfProjectDoesntUsesFiles(): void
    {
        $this->project->shouldReceive('usesFile')->andReturnFalse();

        $this->expectException(RestException::class);

        $this->builder->getServiceRepresentation($this->frs_admin_user, $this->project);
    }

    public function testItReturnsAnEmptyListWhenNobodyIsSelected(): void
    {
        $nobody = new ProjectUGroup([
            'ugroup_id' => (string) ProjectUGroup::NONE,
            'name' => ProjectUGroup::$normalized_names[ProjectUGroup::NONE],
            'group_id' => (string) $this->project_id,
        ]);
        $this->ugroup_manager->shouldReceive('getUGroup')->with($this->project, ProjectUGroup::NONE)->andReturn($nobody);

        $this->frs_permissions_factory->shouldReceive('getFrsUGroupsByPermission')->with($this->project, FRSPermission::FRS_READER)->andReturn([
            new FRSPermission((string) ProjectUGroup::NONE),
        ]);

        $representation = $this->builder->getServiceRepresentation($this->frs_admin_user, $this->project);
        $this->assertCount(0, $representation->permissions_for_groups->can_read);
    }
}
