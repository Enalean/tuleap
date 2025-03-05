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
use PHPUnit\Framework\MockObject\MockObject;
use Project;
use ProjectUGroup;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use UGroupManager;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class DocmanServicePermissionsForGroupsBuilderTest extends TestCase
{
    private IPermissionsManagerNG&MockObject $permissions_manager;
    private UGroupManager&MockObject $ugroup_manager;
    private DocmanServicePermissionsForGroupsBuilder $builder;

    protected function setUp(): void
    {
        $this->permissions_manager = $this->createMock(IPermissionsManagerNG::class);
        $this->ugroup_manager      = $this->createMock(UGroupManager::class);

        $this->builder = new DocmanServicePermissionsForGroupsBuilder($this->permissions_manager, $this->ugroup_manager);
    }

    public function testRepresentationIsBuiltWhenSomeUGroupsAreAssignedToTheDocumentManagerManagement(): void
    {
        $project_id = 102;
        $project    = ProjectTestBuilder::aProject()->withId($project_id)->build();

        $ugroup_project_member = new ProjectUGroup([
            'ugroup_id' => ProjectUGroup::PROJECT_MEMBERS,
            'name'      => ProjectUGroup::NORMALIZED_NAMES[ProjectUGroup::PROJECT_MEMBERS],
            'group_id'  => $project_id,
        ]);
        $ugroup_static_id      = 963;
        $ugroup_static         = new ProjectUGroup([
            'ugroup_id' => $ugroup_static_id,
            'name'      => 'My UGroup',
            'group_id'  => $project_id,
        ]);

        $this->permissions_manager->method('getAuthorizedUGroupIdsForProject')
            ->with($project, $project_id, Docman_PermissionsManager::PLUGIN_DOCMAN_ADMIN)
            ->willReturn([ProjectUGroup::PROJECT_MEMBERS, $ugroup_static_id]);

        $this->ugroup_manager->method('getUGroup')->willReturnCallback(static fn(Project $project, int $id) => match ($id) {
            ProjectUGroup::PROJECT_MEMBERS => $ugroup_project_member,
            $ugroup_static_id              => $ugroup_static,
        });

        $representation = $this->builder->getServicePermissionsForGroupRepresentation($project);
        self::assertCount(2, $representation->can_admin);
        self::assertEquals($ugroup_project_member->getNormalizedName(), $representation->can_admin[0]->label);
        self::assertEquals($ugroup_static->getNormalizedName(), $representation->can_admin[1]->label);
    }

    public function testRepresentationIsBuiltWhenNobodyHasSpecialRightToAdministrateTheDocumentManager(): void
    {
        $project_id = 102;
        $project    = ProjectTestBuilder::aProject()->withId($project_id)->build();

        $nobody_group = new ProjectUGroup([
            'ugroup_id' => ProjectUGroup::NONE,
            'name'      => ProjectUGroup::NORMALIZED_NAMES[ProjectUGroup::NONE],
            'group_id'  => $project_id,
        ]);

        $this->permissions_manager->method('getAuthorizedUGroupIdsForProject')
            ->with($project, $project_id, Docman_PermissionsManager::PLUGIN_DOCMAN_ADMIN)
            ->willReturn([ProjectUGroup::NONE]);

        $this->ugroup_manager->method('getUGroup')
            ->with($project, ProjectUGroup::NONE)
            ->willReturn($nobody_group);

        $representation = $this->builder->getServicePermissionsForGroupRepresentation($project);
        self::assertEmpty($representation->can_admin);
    }
}
