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
use PHPUnit\Framework\MockObject\MockObject;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class MetadataRecursiveUpdatorTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private \Docman_MetadataFactory|MockObject $metadata_factory;
    private \Docman_PermissionsManager|MockObject $permissions_manager;
    private \Docman_MetadataValueFactory|MockObject $metadata_value_factory;
    private \ReferenceManager|MockObject $reference_manager;
    private MetadataRecursiveUpdator $updator;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->metadata_factory       = $this->createMock(\Docman_MetadataFactory::class);
        $this->permissions_manager    = $this->createMock(\Docman_PermissionsManager::class);
        $this->metadata_value_factory = $this->createMock(\Docman_MetadataValueFactory::class);
        $this->reference_manager      = $this->createMock(\ReferenceManager::class);

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
        $this->metadata_factory->method('getInheritableMdLabelArray')->willReturn([]);
        $this->updator->updateRecursiveMetadataOnFolder($collection, 3, 102);

        $this->permissions_manager->expects($this->never())->method('currentUserCanWriteSubItems');
    }

    public function testItDoNothingIfUserDoesNotHaveWritePermission(): void
    {
        $collection = ItemImpactedByMetadataChangeCollection::buildFromLegacy(['field_1'], ['field_1' => 'value']);
        $this->metadata_factory->method('getInheritableMdLabelArray')->willReturn(['field_1']);
        $this->permissions_manager->method('currentUserCanWriteSubItems')->willReturn(false);

        $this->updator->updateRecursiveMetadataOnFolder($collection, 3, 102);

        $this->permissions_manager->expects($this->never())->method('getSubItemsWritableVisitor');
    }

    public function testItThrowAnExceptionIsFolderIsEmpty(): void
    {
        $collection = ItemImpactedByMetadataChangeCollection::buildFromLegacy(['field_1'], ['field_1' => 'value']);
        $this->metadata_factory->method('getInheritableMdLabelArray')->willReturn(['field_1']);
        $this->permissions_manager->method('currentUserCanWriteSubItems')->willReturn(true);
        $visitor = $this->createMock(Docman_SubItemsWritableVisitor::class);
        $this->permissions_manager->method('getSubItemsWritableVisitor')->willReturn($visitor);
        $visitor->method('getFolderIdList')->willReturn([3]);

        $this->expectException(NoItemToRecurseException::class);
        $this->updator->updateRecursiveMetadataOnFolder($collection, 3, 102);
    }

    public function testItUpdateSubItemsForField(): void
    {
        $collection = ItemImpactedByMetadataChangeCollection::buildFromLegacy(['field_1'], ['field_1' => 'value']);

        $this->metadata_factory->method('getInheritableMdLabelArray')->willReturn(['field_1']);
        $this->permissions_manager->method('currentUserCanWriteSubItems')->willReturn(true);
        $visitor = $this->createMock(Docman_SubItemsWritableVisitor::class);
        $this->permissions_manager->method('getSubItemsWritableVisitor')->willReturn($visitor);
        $visitor->method('getItemIdList')->willReturn([1, 2, 3]);

        $this->metadata_value_factory->expects($this->once())->method('massUpdateFromRow');
        $this->reference_manager->expects($this->atLeast(1))->method('extractCrossRef');

        $this->updator->updateRecursiveMetadataOnFolderAndItems($collection, 3, 102);
    }

    public function testItUpdateSubItemsForFolder(): void
    {
        $collection = ItemImpactedByMetadataChangeCollection::buildFromLegacy(['field_1'], ['field_1' => 'value']);

        $this->metadata_factory->method('getInheritableMdLabelArray')->willReturn(['field_1']);
        $this->permissions_manager->method('currentUserCanWriteSubItems')->willReturn(true);
        $visitor = $this->createMock(Docman_SubItemsWritableVisitor::class);
        $this->permissions_manager->method('getSubItemsWritableVisitor')->willReturn($visitor);
        $visitor->method('getFolderIdList')->willReturn([5, 5]);

        $this->metadata_value_factory->expects($this->once())->method('massUpdateFromRow');
        $this->reference_manager->expects($this->atLeast(1))->method('extractCrossRef');

        $this->updator->updateRecursiveMetadataOnFolder($collection, 3, 102);
    }

    public function testItDoesNothingForListValues(): void
    {
        $collection = ItemImpactedByMetadataChangeCollection::buildFromLegacy(['field_1'], ['field_1' => [0 => 'value']]);

        $this->metadata_factory->method('getInheritableMdLabelArray')->willReturn(['field_1']);
        $this->permissions_manager->method('currentUserCanWriteSubItems')->willReturn(true);
        $visitor = $this->createMock(Docman_SubItemsWritableVisitor::class);
        $this->permissions_manager->method('getSubItemsWritableVisitor')->willReturn($visitor);
        $visitor->method('getFolderIdList')->willReturn([5, 5]);

        $this->metadata_value_factory->expects($this->once())->method('massUpdateFromRow');
        $this->reference_manager->expects($this->never())->method('extractCrossRef');

        $this->updator->updateRecursiveMetadataOnFolder($collection, 3, 102);
    }
}
