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

namespace Tuleap\Docman\REST\v1\Service;

use Docman_PermissionsManager;
use IPermissionsManagerNG;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use ProjectUGroup;
use UGroupManager;

final class DocmanServicePermissionsForGroupsBuilderTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var IPermissionsManagerNG|\Mockery\MockInterface
     */
    private $permissions_manager;
    /**
     * @var \Mockery\MockInterface|UGroupManager
     */
    private $ugroup_manager;
    /**
     * @var DocmanServicePermissionsForGroupsBuilder
     */
    private $builder;

    protected function setUp(): void
    {
        $this->permissions_manager = Mockery::mock(IPermissionsManagerNG::class);
        $this->ugroup_manager      = Mockery::mock(UGroupManager::class);

        $this->builder = new DocmanServicePermissionsForGroupsBuilder($this->permissions_manager, $this->ugroup_manager);
    }

    public function testRepresentationIsBuiltWhenSomeUGroupsAreAssignedToTheDocumentManagerManagement(): void
    {
        $project    = Mockery::mock(\Project::class);
        $project_id = '102';
        $project->shouldReceive('getID')->andReturn($project_id);

        $ugroup_project_member = new ProjectUGroup([
            'ugroup_id' => ProjectUGroup::PROJECT_MEMBERS,
            'name'      => ProjectUGroup::$normalized_names[ProjectUGroup::PROJECT_MEMBERS],
            'group_id'  => $project_id,
        ]);
        $ugroup_static_id      = 963;
        $ugroup_static         = new ProjectUGroup([
            'ugroup_id' => $ugroup_static_id,
            'name'      => 'My UGroup',
            'group_id'  => $project_id,
        ]);

        $this->permissions_manager->shouldReceive('getAuthorizedUGroupIdsForProject')
            ->with($project, $project_id, Docman_PermissionsManager::PLUGIN_DOCMAN_ADMIN)
            ->andReturn([ProjectUGroup::PROJECT_MEMBERS, $ugroup_static_id]);

        $this->ugroup_manager->shouldReceive('getUGroup')
            ->with($project, ProjectUGroup::PROJECT_MEMBERS)
            ->andReturn($ugroup_project_member);
        $this->ugroup_manager->shouldReceive('getUGroup')
            ->with($project, $ugroup_static_id)
            ->andReturn($ugroup_static);

        $representation = $this->builder->getServicePermissionsForGroupRepresentation($project);
        $this->assertCount(2, $representation->can_admin);
        $this->assertEquals($ugroup_project_member->getNormalizedName(), $representation->can_admin[0]->label);
        $this->assertEquals($ugroup_static->getNormalizedName(), $representation->can_admin[1]->label);
    }

    public function testRepresentationIsBuiltWhenNobodyHasSpecialRightToAdministrateTheDocumentManager(): void
    {
        $project    = Mockery::mock(\Project::class);
        $project_id = '102';
        $project->shouldReceive('getID')->andReturn($project_id);

        $nobody_group = new ProjectUGroup([
            'ugroup_id' => ProjectUGroup::NONE,
            'name'      => ProjectUGroup::$normalized_names[ProjectUGroup::NONE],
            'group_id'  => $project_id,
        ]);

        $this->permissions_manager->shouldReceive('getAuthorizedUGroupIdsForProject')
            ->with($project, $project_id, Docman_PermissionsManager::PLUGIN_DOCMAN_ADMIN)
            ->andReturn([ProjectUGroup::NONE]);

        $this->ugroup_manager->shouldReceive('getUGroup')
            ->with($project, ProjectUGroup::NONE)
            ->andReturn($nobody_group);

        $representation = $this->builder->getServicePermissionsForGroupRepresentation($project);
        $this->assertEmpty($representation->can_admin);
    }
}
