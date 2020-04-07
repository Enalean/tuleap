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
use Mockery;
use PFUser;
use PHPUnit\Framework\TestCase;
use Tuleap\Docman\REST\v1\ItemRepresentation;
use Tuleap\Docman\REST\v1\ItemRepresentationBuilder;

final class DocmanServiceRepresentationBuilderTest extends TestCase
{
    use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    /**
     * @var Mockery\MockInterface|ItemRepresentationBuilder
     */
    private $item_representation_builder;
    /**
     * @var Docman_PermissionsManager|Mockery\MockInterface
     */
    private $docman_permissions_manager;
    /**
     * @var Mockery\MockInterface|DocmanServicePermissionsForGroupsBuilder
     */
    private $service_permissions_for_group_builder;
    /**
     * @var DocmanServiceRepresentationBuilder
     */
    private $builder;

    protected function setUp(): void
    {
        $this->item_representation_builder           = Mockery::mock(ItemRepresentationBuilder::class);
        $this->docman_permissions_manager            = Mockery::mock(Docman_PermissionsManager::class);
        $this->service_permissions_for_group_builder = Mockery::mock(DocmanServicePermissionsForGroupsBuilder::class);

        $this->builder = new DocmanServiceRepresentationBuilder(
            $this->item_representation_builder,
            $this->docman_permissions_manager,
            $this->service_permissions_for_group_builder
        );
    }

    public function testServiceRepresentationHasAllTheInformationWhenTheUserCanAdministrateTheDocumentManager(): void
    {
        $project = Mockery::mock(\Project::class);
        $project->shouldReceive('usesService')->andReturn(true);

        $root_item_representation = new ItemRepresentation();
        $this->item_representation_builder->shouldReceive('buildRootId')->andReturn($root_item_representation);

        $this->docman_permissions_manager->shouldReceive('userCanAdmin')->andReturn(true);

        $permissions_for_groups_representation = DocmanServicePermissionsForGroupsRepresentation::build([]);
        $this->service_permissions_for_group_builder->shouldReceive('getServicePermissionsForGroupRepresentation')
            ->andReturn($permissions_for_groups_representation);

        $representation = $this->builder->getServiceRepresentation($project, Mockery::mock(PFUser::class));
        $this->assertEquals($root_item_representation, $representation->root_item);
        $this->assertEquals($permissions_for_groups_representation, $representation->permissions_for_groups);
    }

    public function testServiceRepresentationCanNoBeGeneratedWhenTheProjectDoesNotUseTheDocumentManager(): void
    {
        $project = Mockery::mock(\Project::class);
        $project->shouldReceive('usesService')->andReturn(false);

        $this->assertNull($this->builder->getServiceRepresentation($project, Mockery::mock(PFUser::class)));
    }

    public function testServiceRepresentationIsEmptyIfTheRootItemCanNotBeAccessed(): void
    {
        $project = Mockery::mock(\Project::class);
        $project->shouldReceive('usesService')->andReturn(true);

        $this->item_representation_builder->shouldReceive('buildRootId')->andReturn(null);

        $representation = $this->builder->getServiceRepresentation($project, Mockery::mock(PFUser::class));
        $this->assertNull($representation->root_item);
        $this->assertNull($representation->permissions_for_groups);
    }

    public function testServiceRepresentationOnlyHaveInformationAboutTheRootItemifTheUserCanNotAdministrateTheDocumentManager(): void
    {
        $project = Mockery::mock(\Project::class);
        $project->shouldReceive('usesService')->andReturn(true);

        $root_item_representation = new ItemRepresentation();
        $this->item_representation_builder->shouldReceive('buildRootId')->andReturn($root_item_representation);

        $this->docman_permissions_manager->shouldReceive('userCanAdmin')->andReturn(false);

        $representation = $this->builder->getServiceRepresentation($project, Mockery::mock(PFUser::class));
        $this->assertEquals($root_item_representation, $representation->root_item);
        $this->assertNull($representation->permissions_for_groups);
    }
}
