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
use Docman_PermissionsManager;
use IPermissionsManagerNG;
use Mockery;
use PFUser;
use PHPUnit\Framework\TestCase;
use Project;
use ProjectManager;
use ProjectUGroup;
use UGroupManager;

final class DocmanItemPermissionsForGroupsBuilderTest extends TestCase
{
    use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    /**
     * @var Docman_PermissionsManager|Mockery\MockInterface
     */
    private $docman_permissions_manager;
    /**
     * @var Mockery\MockInterface|ProjectManager
     */
    private $project_manager;
    /**
     * @var IPermissionsManagerNG|Mockery\MockInterface
     */
    private $permissions_manager;
    /**
     * @var Mockery\MockInterface|UGroupManager
     */
    private $ugroup_manager;
    /**
     * @var DocmanItemPermissionsForGroupsBuilder
     */
    private $builder;

    protected function setUp(): void
    {
        $this->docman_permissions_manager = Mockery::mock(Docman_PermissionsManager::class);
        $this->project_manager            = Mockery::mock(ProjectManager::class);
        $this->permissions_manager        = Mockery::mock(IPermissionsManagerNG::class);
        $this->ugroup_manager             = Mockery::mock(UGroupManager::class);

        $this->builder = new DocmanItemPermissionsForGroupsBuilder(
            $this->docman_permissions_manager,
            $this->project_manager,
            $this->permissions_manager,
            $this->ugroup_manager
        );
    }

    public function testDoNotBuildPermissionsRepresentationIfTheUserCanNotManageTheItem(): void
    {
        $this->docman_permissions_manager->shouldReceive('userCanManage')->andReturn(false);

        $item = Mockery::mock(Docman_Item::class);
        $item->shouldReceive('getId')->andReturn(123);

        $this->assertNull($this->builder->getRepresentation(
            Mockery::mock(PFUser::class),
            $item
        ));
    }

    public function testRepresentationHaveTheUGroupsForDifferentTypesOfPermissions(): void
    {
        $this->docman_permissions_manager->shouldReceive('userCanManage')->andReturn(true);

        $project_id = 789;
        $item       = Mockery::mock(Docman_Item::class);
        $item->shouldReceive('getId')->andReturn(123);
        $item->shouldReceive('getGroupId')->andReturn($project_id);

        $project = Mockery::mock(Project::class);
        $project->shouldReceive('getID')->andReturn($project_id);
        $this->project_manager->shouldReceive('getProject')->andReturn($project);

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

        $this->permissions_manager->shouldReceive('getAuthorizedUGroupIdsForProjectWithoutDefaultValues')
            ->with($project, 123, Docman_PermissionsManager::ITEM_PERMISSION_TYPE_READ)
            ->andReturn([]);
        $this->permissions_manager->shouldReceive('getAuthorizedUGroupIdsForProjectWithoutDefaultValues')
            ->with($project, 123, Docman_PermissionsManager::ITEM_PERMISSION_TYPE_WRITE)
            ->andReturn([ProjectUGroup::PROJECT_MEMBERS, $ugroup_static_id]);
        $this->permissions_manager->shouldReceive('getAuthorizedUGroupIdsForProjectWithoutDefaultValues')
            ->with($project, 123, Docman_PermissionsManager::ITEM_PERMISSION_TYPE_MANAGE)
            ->andReturn([]);

        $this->ugroup_manager->shouldReceive('getUGroup')
            ->with($project, ProjectUGroup::PROJECT_MEMBERS)
            ->andReturn($ugroup_project_member);
        $this->ugroup_manager->shouldReceive('getUGroup')
            ->with($project, $ugroup_static_id)
            ->andReturn($ugroup_static);

        $representation = $this->builder->getRepresentation(Mockery::mock(PFUser::class), $item);
        $this->assertEmpty($representation->can_read);
        $this->assertEmpty($representation->can_manage);
        $this->assertCount(2, $representation->can_write);
        $this->assertEquals($ugroup_project_member->getNormalizedName(), $representation->can_write[0]->label);
        $this->assertEquals($ugroup_static->getNormalizedName(), $representation->can_write[1]->label);
    }
}
