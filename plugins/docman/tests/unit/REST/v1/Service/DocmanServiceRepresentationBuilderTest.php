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
use PHPUnit\Framework\MockObject\MockObject;
use Project;
use Tuleap\Docman\REST\v1\ItemRepresentation;
use Tuleap\Docman\REST\v1\ItemRepresentationBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class DocmanServiceRepresentationBuilderTest extends TestCase
{
    private ItemRepresentationBuilder&MockObject $item_representation_builder;
    private Docman_PermissionsManager&MockObject $docman_permissions_manager;
    private DocmanServicePermissionsForGroupsBuilder&MockObject $service_permissions_for_group_builder;
    private DocmanServiceRepresentationBuilder $builder;

    protected function setUp(): void
    {
        $this->item_representation_builder           = $this->createMock(ItemRepresentationBuilder::class);
        $this->docman_permissions_manager            = $this->createMock(Docman_PermissionsManager::class);
        $this->service_permissions_for_group_builder = $this->createMock(DocmanServicePermissionsForGroupsBuilder::class);

        $this->builder = new DocmanServiceRepresentationBuilder(
            $this->item_representation_builder,
            $this->docman_permissions_manager,
            $this->service_permissions_for_group_builder
        );
    }

    public function testServiceRepresentationHasAllTheInformationWhenTheUserCanAdministrateTheDocumentManager(): void
    {
        $project = $this->createMock(Project::class);
        $project->method('usesService')->willReturn(true);

        $root_item_representation = $this->createMock(ItemRepresentation::class);
        $this->item_representation_builder->method('buildRootId')->willReturn($root_item_representation);

        $this->docman_permissions_manager->method('userCanAdmin')->willReturn(true);

        $permissions_for_groups_representation = DocmanServicePermissionsForGroupsRepresentation::build([]);
        $this->service_permissions_for_group_builder->method('getServicePermissionsForGroupRepresentation')
            ->willReturn($permissions_for_groups_representation);

        $representation = $this->builder->getServiceRepresentation($project, UserTestBuilder::buildWithDefaults());
        self::assertEquals($root_item_representation, $representation->root_item);
        self::assertEquals($permissions_for_groups_representation, $representation->permissions_for_groups);
    }

    public function testServiceRepresentationCanNoBeGeneratedWhenTheProjectDoesNotUseTheDocumentManager(): void
    {
        $project = $this->createMock(Project::class);
        $project->method('usesService')->willReturn(false);

        self::assertNull($this->builder->getServiceRepresentation($project, UserTestBuilder::buildWithDefaults()));
    }

    public function testServiceRepresentationIsEmptyIfTheRootItemCanNotBeAccessed(): void
    {
        $project = $this->createMock(Project::class);
        $project->method('usesService')->willReturn(true);

        $this->item_representation_builder->method('buildRootId')->willReturn(null);

        $representation = $this->builder->getServiceRepresentation($project, UserTestBuilder::buildWithDefaults());
        self::assertNull($representation->root_item);
        self::assertNull($representation->permissions_for_groups);
    }

    public function testServiceRepresentationOnlyHaveInformationAboutTheRootItemifTheUserCanNotAdministrateTheDocumentManager(): void
    {
        $project = $this->createMock(Project::class);
        $project->method('usesService')->willReturn(true);

        $root_item_representation = $this->createMock(ItemRepresentation::class);
        $this->item_representation_builder->method('buildRootId')->willReturn($root_item_representation);

        $this->docman_permissions_manager->method('userCanAdmin')->willReturn(false);

        $representation = $this->builder->getServiceRepresentation($project, UserTestBuilder::buildWithDefaults());
        self::assertEquals($root_item_representation, $representation->root_item);
        self::assertNull($representation->permissions_for_groups);
    }
}
