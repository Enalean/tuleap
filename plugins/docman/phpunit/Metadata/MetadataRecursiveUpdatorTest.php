<?php
/**
 * Copyright (c) Enalean, 2019 - present. All Rights Reserved.
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

namespace Tuleap\Docman\Metadata;

use Docman_SubItemsWritableVisitor;
use Mockery;
use PHPUnit\Framework\TestCase;

class MetadataRecursiveUpdatorTest extends TestCase
{
    use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    /**
     * @var MetadataRecursiveUpdator
     */
    private $updator;
    /**
     * @var Mockery\MockInterface|\ReferenceManager
     */
    private $reference_manager;
    /**
     * @var \Docman_MetadataValueFactory|Mockery\MockInterface
     */
    private $metadata_value_factory;
    /**
     * @var \Docman_PermissionsManager|Mockery\MockInterface
     */
    private $permissions_manager;

    /**
     * @var \Docman_MetadataFactory|Mockery\MockInterface
     */
    private $metadata_factory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->metadata_factory       = Mockery::mock(\Docman_MetadataFactory::class);
        $this->permissions_manager    = Mockery::mock(\Docman_PermissionsManager::class);
        $this->metadata_value_factory = Mockery::mock(\Docman_MetadataValueFactory::class);
        $this->reference_manager      = Mockery::mock(\ReferenceManager::class);

        $this->updator = new MetadataRecursiveUpdator(
            $this->metadata_factory,
            $this->permissions_manager,
            $this->metadata_value_factory,
            $this->reference_manager
        );
    }

    public function testItDoNothingIfFieldIsNotInheritable(): void
    {
        $collection = ItemImpactedByMetadataChangeCollection::buildFromLegacy(['field_1'], ['field_1' => 'value']);
        $this->metadata_factory->shouldReceive('getInheritableMdLabelArray')->andReturn([]);
        $this->updator->updateRecursiveMetadataOnFolder($collection, 3, 102);

        $this->permissions_manager->shouldReceive('currentUserCanWriteSubItems')->never();
    }

    public function testItDoNothingIfUserDoesNotHaveWritePermission(): void
    {
        $collection = ItemImpactedByMetadataChangeCollection::buildFromLegacy(['field_1'], ['field_1' => 'value']);
        $this->metadata_factory->shouldReceive('getInheritableMdLabelArray')->andReturn(['field_1']);
        $this->permissions_manager->shouldReceive('currentUserCanWriteSubItems')->andReturn(false);

        $this->updator->updateRecursiveMetadataOnFolder($collection, 3, 102);

        $this->permissions_manager->shouldReceive('getSubItemsWritableVisitor')->never();
    }

    public function testItThrowAnExceptionIsFolderIsEmpty(): void
    {
        $collection = ItemImpactedByMetadataChangeCollection::buildFromLegacy(['field_1'], ['field_1' => 'value']);
        $this->metadata_factory->shouldReceive('getInheritableMdLabelArray')->andReturn(['field_1']);
        $this->permissions_manager->shouldReceive('currentUserCanWriteSubItems')->andReturn(true);
        $visitor = Mockery::mock(Docman_SubItemsWritableVisitor::class);
        $this->permissions_manager->shouldReceive('getSubItemsWritableVisitor')->andReturn($visitor);
        $visitor->shouldReceive('getFolderIdList')->andReturn([3]);

        $this->expectException(NoItemToRecurseException::class);
        $this->updator->updateRecursiveMetadataOnFolder($collection, 3, 102);
    }

    public function testItUpdateSubItemsForField(): void
    {
        $collection = ItemImpactedByMetadataChangeCollection::buildFromLegacy(['field_1'], ['field_1' => 'value']);

        $this->metadata_factory->shouldReceive('getInheritableMdLabelArray')->andReturn(['field_1']);
        $this->permissions_manager->shouldReceive('currentUserCanWriteSubItems')->andReturn(true);
        $visitor = Mockery::mock(Docman_SubItemsWritableVisitor::class);
        $this->permissions_manager->shouldReceive('getSubItemsWritableVisitor')->andReturn($visitor);
        $visitor->shouldReceive('getItemIdList')->andReturn([1, 2, 3]);

        $this->metadata_value_factory->shouldReceive('massUpdateFromRow');
        $this->reference_manager->shouldReceive('extractCrossRef');

        $this->updator->updateRecursiveMetadataOnFolderAndItems($collection, 3, 102);
    }

    public function testItUpdateSubItemsForFolder(): void
    {
        $collection = ItemImpactedByMetadataChangeCollection::buildFromLegacy(['field_1'], ['field_1' => 'value']);

        $this->metadata_factory->shouldReceive('getInheritableMdLabelArray')->andReturn(['field_1']);
        $this->permissions_manager->shouldReceive('currentUserCanWriteSubItems')->andReturn(true);
        $visitor = Mockery::mock(Docman_SubItemsWritableVisitor::class);
        $this->permissions_manager->shouldReceive('getSubItemsWritableVisitor')->andReturn($visitor);
        $visitor->shouldReceive('getFolderIdList')->andReturn([5, 5]);

        $this->metadata_value_factory->shouldReceive('massUpdateFromRow');
        $this->reference_manager->shouldReceive('extractCrossRef');

        $this->updator->updateRecursiveMetadataOnFolder($collection, 3, 102);
    }
}
