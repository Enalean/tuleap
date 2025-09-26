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
use PHPUnit\Framework\MockObject\MockObject;
use Project;
use ProjectUGroup;
use Tuleap\Project\ProjectByIDFactory;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Test\Stubs\ProjectByIDFactoryStub;
use UGroupManager;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class DocmanItemPermissionsForGroupsBuilderTest extends TestCase
{
    private Docman_PermissionsManager&MockObject $docman_permissions_manager;
    private IPermissionsManagerNG&MockObject $permissions_manager;
    private UGroupManager&MockObject $ugroup_manager;

    #[\Override]
    protected function setUp(): void
    {
        $this->docman_permissions_manager = $this->createMock(Docman_PermissionsManager::class);
        $this->permissions_manager        = $this->createMock(IPermissionsManagerNG::class);
        $this->ugroup_manager             = $this->createMock(UGroupManager::class);
    }

    private function getBuilder(ProjectByIDFactory $project_manager): DocmanItemPermissionsForGroupsBuilder
    {
        return new DocmanItemPermissionsForGroupsBuilder(
            $this->docman_permissions_manager,
            $project_manager,
            $this->permissions_manager,
            $this->ugroup_manager
        );
    }

    public function testDoNotBuildPermissionsRepresentationIfTheUserCanNotManageTheItem(): void
    {
        $this->docman_permissions_manager->method('userCanManage')->willReturn(false);

        self::assertNull($this->getBuilder(ProjectByIDFactoryStub::buildWithoutProject())->getRepresentation(
            UserTestBuilder::buildWithDefaults(),
            new Docman_Item(['item_id' => 123])
        ));
    }

    public function testRepresentationHaveTheUGroupsForDifferentTypesOfPermissions(): void
    {
        $this->docman_permissions_manager->method('userCanManage')->willReturn(true);

        $project_id = 789;
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

        $this->permissions_manager->method('getAuthorizedUGroupIdsForProjectWithoutDefaultValues')
            ->willReturnCallback(static fn(Project $project, int $object_id, string $type) => match ($type) {
                Docman_PermissionsManager::ITEM_PERMISSION_TYPE_MANAGE,
                Docman_PermissionsManager::ITEM_PERMISSION_TYPE_READ  => [],
                Docman_PermissionsManager::ITEM_PERMISSION_TYPE_WRITE => [ProjectUGroup::PROJECT_MEMBERS, $ugroup_static_id],
            });

        $this->ugroup_manager->method('getUGroup')
            ->willReturnCallback(static fn(Project $project, int $ugroup_id) => match ($ugroup_id) {
                ProjectUGroup::PROJECT_MEMBERS => $ugroup_project_member,
                $ugroup_static_id              => $ugroup_static,
            });

        $representation = $this
            ->getBuilder(ProjectByIDFactoryStub::buildWith($project))
            ->getRepresentation(
                UserTestBuilder::buildWithDefaults(),
                new Docman_Item(['item_id' => 123, 'group_id' => $project_id]),
            );
        self::assertEmpty($representation->can_read);
        self::assertEmpty($representation->can_manage);
        self::assertCount(2, $representation->can_write);
        self::assertEquals($ugroup_project_member->getNormalizedName(), $representation->can_write[0]->label);
        self::assertEquals($ugroup_static->getNormalizedName(), $representation->can_write[1]->label);
    }
}
