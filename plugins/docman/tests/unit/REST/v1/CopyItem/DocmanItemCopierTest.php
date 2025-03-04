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

namespace Tuleap\Docman\REST\v1\CopyItem;

use DateTimeImmutable;
use Docman_Folder;
use Docman_Item;
use Docman_ItemFactory;
use Docman_LinkVersionFactory;
use Docman_MetadataFactory;
use Docman_PermissionsManager;
use EventManager;
use Luracast\Restler\RestException;
use PHPUnit\Framework\MockObject\MockObject;
use ProjectManager;
use RuntimeException;
use Tuleap\Docman\ItemType\DoesItemHasExpectedTypeVisitor;
use Tuleap\Docman\Metadata\MetadataFactoryBuilder;
use Tuleap\Docman\Upload\Document\DocumentOngoingUploadRetriever;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class DocmanItemCopierTest extends TestCase
{
    private Docman_ItemFactory&MockObject $item_factory;
    private Docman_PermissionsManager&MockObject $permission_manager;
    private EventManager&MockObject $event_manager;
    private DocmanItemCopier $item_copier;

    protected function setUp(): void
    {
        $this->item_factory       = $this->createMock(Docman_ItemFactory::class);
        $this->permission_manager = $this->createMock(Docman_PermissionsManager::class);
        $metadata_factory_builder = $this->createMock(MetadataFactoryBuilder::class);
        $this->event_manager      = $this->createMock(EventManager::class);

        $metadata_factory = $this->createMock(Docman_MetadataFactory::class);
        $metadata_factory->method('getMetadataMapping');
        $metadata_factory_builder->method('getMetadataFactoryForItem')->willReturn($metadata_factory);

        $this->item_copier = new DocmanItemCopier(
            $this->item_factory,
            new BeforeCopyVisitor(
                new DoesItemHasExpectedTypeVisitor(Docman_Item::class),
                $this->item_factory,
                $this->createMock(DocumentOngoingUploadRetriever::class)
            ),
            $this->permission_manager,
            $metadata_factory_builder,
            $this->event_manager,
            $this->createMock(ProjectManager::class),
            $this->createMock(Docman_LinkVersionFactory::class),
            '/',
        );
    }

    public function testAnItemCanBeCopied(): void
    {
        $destination_folder                = new Docman_Folder(['item_id' => 963, 'group_id' => 102]);
        $copy_item_representation          = new DocmanCopyItemRepresentation();
        $copy_item_representation->item_id = 741;

        $item_to_copy = $this->createMock(Docman_Item::class);
        $item_to_copy->method('getId')->willReturn(741);
        $item_to_copy->method('getGroupId')->willReturn(102);
        $this->item_factory->method('getItemFromDb')->willReturn($item_to_copy);

        $this->permission_manager->method('userCanAccess')->willReturn(true);

        $this->item_factory->method('cloneItems')->willReturn([741 => 999]);

        $item_to_copy->method('getTitle')->willReturn('Title');
        $item_to_copy->method('accept')
            ->with(self::isInstanceOf(BeforeCopyVisitor::class), self::anything())
            ->willReturn(new ItemBeingCopiedExpectation('Title'));

        $this->event_manager->method('processEvent')->with('send_notifications');

        $representation_copy = $this->item_copier->copyItem(
            new DateTimeImmutable(),
            $destination_folder,
            UserTestBuilder::buildWithDefaults(),
            $copy_item_representation
        );

        self::assertEquals(999, $representation_copy->id);
    }

    public function testCanNotCopyAnItemThatDoesNotExist(): void
    {
        $copy_item_representation          = new DocmanCopyItemRepresentation();
        $copy_item_representation->item_id = 741;

        $this->item_factory->method('getItemFromDb')->willReturn(null);

        self::expectException(RestException::class);
        self::expectExceptionCode(404);
        $this->item_copier->copyItem(
            new DateTimeImmutable(),
            new Docman_Folder(),
            UserTestBuilder::buildWithDefaults(),
            $copy_item_representation
        );
    }

    public function testCanNotCopyAnItemTheUserCanNotAccess(): void
    {
        $copy_item_representation          = new DocmanCopyItemRepresentation();
        $copy_item_representation->item_id = 741;

        $item_to_copy = new Docman_Item(['item_id' => 741]);
        $this->item_factory->method('getItemFromDb')->willReturn($item_to_copy);

        $this->permission_manager->method('userCanAccess')->willReturn(false);

        self::expectException(RestException::class);
        self::expectExceptionCode(404);
        $this->item_copier->copyItem(
            new DateTimeImmutable(),
            new Docman_Folder(),
            UserTestBuilder::buildWithDefaults(),
            $copy_item_representation
        );
    }

    public function testCanNotCopyAnItemInADifferentProjectThanTheDestination(): void
    {
        $copy_item_representation          = new DocmanCopyItemRepresentation();
        $copy_item_representation->item_id = 741;

        $item_to_copy = new Docman_Item(['item_id' => 741, 'group_id' => 102]);
        $this->item_factory->method('getItemFromDb')->willReturn($item_to_copy);

        $this->permission_manager->method('userCanAccess')->willReturn(true);

        $destination_folder = new Docman_Folder(['item_id' => 963, 'group_id' => 103]);

        self::expectException(RestException::class);
        self::expectExceptionCode(400);
        $this->item_copier->copyItem(
            new DateTimeImmutable(),
            $destination_folder,
            UserTestBuilder::buildWithDefaults(),
            $copy_item_representation
        );
    }

    public function testItemTitleIsUpdatedIfADuplicateExistsInTheDestinationFolder(): void
    {
        $destination_folder                = new Docman_Folder(['item_id' => 963, 'group_id' => 102]);
        $copy_item_representation          = new DocmanCopyItemRepresentation();
        $copy_item_representation->item_id = 741;

        $item_to_copy = $this->createMock(Docman_Item::class);
        $this->item_factory->method('getItemFromDb')->willReturn($item_to_copy);

        $item_to_copy->method('getId')->willReturn(741);
        $item_to_copy->method('getGroupId')->willReturn(102);

        $this->permission_manager->method('userCanAccess')->willReturn(true);

        $this->item_factory->method('cloneItems')->willReturn([741 => 999]);

        $item_to_copy->method('getTitle')->willReturn('Title');
        $item_copy_expectation = new ItemBeingCopiedExpectation('Copy of Title');
        $item_to_copy->method('accept')
            ->with(self::isInstanceOf(BeforeCopyVisitor::class), self::anything())
            ->willReturn($item_copy_expectation);
        $this->item_factory->method('update')
            ->with(self::callback(static fn(array $row) => $row['title'] === $item_copy_expectation->getExpectedTitle()));

        $this->event_manager->method('processEvent')->with('send_notifications');

        $representation_copy = $this->item_copier->copyItem(
            new DateTimeImmutable(),
            $destination_folder,
            UserTestBuilder::buildWithDefaults(),
            $copy_item_representation
        );

        self::assertEquals(999, $representation_copy->id);
    }

    public function testCloneIsInterruptedWhenItemDoesNotAppearToHaveBeenCloned(): void
    {
        $destination_folder                = new Docman_Folder(['item_id' => 963, 'group_id' => 102]);
        $copy_item_representation          = new DocmanCopyItemRepresentation();
        $copy_item_representation->item_id = 741;

        $item_to_copy = $this->createMock(Docman_Item::class);
        $this->item_factory->method('getItemFromDb')->willReturn($item_to_copy);

        $item_to_copy->method('getId')->willReturn(741);
        $item_to_copy->method('getGroupId')->willReturn(102);

        $this->permission_manager->method('userCanAccess')->willReturn(true);

        $item_to_copy->method('getTitle')->willReturn('Title');
        $item_to_copy->method('accept')
            ->with(self::isInstanceOf(BeforeCopyVisitor::class), self::anything())
            ->willReturn(new ItemBeingCopiedExpectation('Title'));

        $this->item_factory->method('cloneItems')->willReturn([]);

        self::expectException(RuntimeException::class);
        $this->item_copier->copyItem(
            new DateTimeImmutable(),
            $destination_folder,
            UserTestBuilder::buildWithDefaults(),
            $copy_item_representation
        );
    }
}
