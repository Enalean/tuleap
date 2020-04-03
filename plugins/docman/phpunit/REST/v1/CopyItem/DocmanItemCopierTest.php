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
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PFUser;
use PHPUnit\Framework\TestCase;
use ProjectManager;
use RuntimeException;
use Tuleap\Docman\ItemType\DoesItemHasExpectedTypeVisitor;
use Tuleap\Docman\Metadata\MetadataFactoryBuilder;
use Tuleap\Docman\Upload\Document\DocumentOngoingUploadRetriever;

final class DocmanItemCopierTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var Docman_ItemFactory|Mockery\MockInterface
     */
    private $item_factory;
    /**
     * @var Docman_PermissionsManager|Mockery\MockInterface
     */
    private $permission_manager;
    /**
     * @var Mockery\MockInterface|MetadataFactoryBuilder
     */
    private $metadata_factory_builder;
    /**
     * @var EventManager|Mockery\MockInterface
     */
    private $event_manager;

    /**
     * @var DocmanItemCopier
     */
    private $item_copier;

    protected function setUp(): void
    {
        $this->item_factory             = Mockery::mock(Docman_ItemFactory::class);
        $this->permission_manager       = Mockery::mock(Docman_PermissionsManager::class);
        $this->metadata_factory_builder = Mockery::mock(MetadataFactoryBuilder::class);
        $this->event_manager            = Mockery::mock(EventManager::class);

        $metadata_factory = Mockery::mock(Docman_MetadataFactory::class);
        $metadata_factory->shouldReceive('getMetadataMapping');
        $this->metadata_factory_builder->shouldReceive('getMetadataFactoryForItem')->andReturn($metadata_factory);

        $this->item_copier = new DocmanItemCopier(
            $this->item_factory,
            new BeforeCopyVisitor(
                new DoesItemHasExpectedTypeVisitor(Docman_Item::class),
                $this->item_factory,
                Mockery::mock(DocumentOngoingUploadRetriever::class)
            ),
            $this->permission_manager,
            $this->metadata_factory_builder,
            $this->event_manager,
            Mockery::mock(ProjectManager::class),
            Mockery::mock(Docman_LinkVersionFactory::class),
            '/'
        );
    }

    public function testAnItemCanBeCopied(): void
    {
        $destination_folder                = Mockery::mock(Docman_Folder::class);
        $copy_item_representation          = new DocmanCopyItemRepresentation();
        $copy_item_representation->item_id = 741;

        $item_to_copy = Mockery::mock(Docman_Item::class);
        $this->item_factory->shouldReceive('getItemFromDb')->andReturn($item_to_copy);

        $destination_folder->shouldReceive('getId')->andReturn(963);
        $item_to_copy->shouldReceive('getId')->andReturn(741);
        $destination_folder->shouldReceive('getGroupId')->andReturn('102');
        $item_to_copy->shouldReceive('getGroupId')->andReturn('102');

        $this->permission_manager->shouldReceive('userCanAccess')->andReturn(true);

        $this->item_factory->shouldReceive('cloneItems')->andReturn([741 => 999]);

        $item_to_copy->shouldReceive('getTitle')->andReturn('Title');
        $item_to_copy->shouldReceive('accept')
            ->with(Mockery::type(BeforeCopyVisitor::class), Mockery::any())
            ->andReturn(new ItemBeingCopiedExpectation('Title'));

        $this->event_manager->shouldReceive('processEvent')->with('send_notifications');

        $representation_copy = $this->item_copier->copyItem(
            new DateTimeImmutable(),
            $destination_folder,
            Mockery::mock(PFUser::class),
            $copy_item_representation
        );

        $this->assertEquals($representation_copy->id, 999);
    }

    public function testCanNotCopyAnItemThatDoesNotExist(): void
    {
        $copy_item_representation          = new DocmanCopyItemRepresentation();
        $copy_item_representation->item_id = 741;

        $this->item_factory->shouldReceive('getItemFromDb')->andReturn(null);

        $this->expectException(RestException::class);
        $this->expectExceptionCode(404);
        $this->item_copier->copyItem(
            new DateTimeImmutable(),
            Mockery::mock(Docman_Folder::class),
            Mockery::mock(PFUser::class),
            $copy_item_representation
        );
    }

    public function testCanNotCopyAnItemTheUserCanNotAccess(): void
    {
        $copy_item_representation          = new DocmanCopyItemRepresentation();
        $copy_item_representation->item_id = 741;

        $item_to_copy = Mockery::mock(Docman_Item::class);
        $item_to_copy->shouldReceive('getId')->andReturn(741);
        $this->item_factory->shouldReceive('getItemFromDb')->andReturn($item_to_copy);

        $this->permission_manager->shouldReceive('userCanAccess')->andReturn(false);

        $this->expectException(RestException::class);
        $this->expectExceptionCode(404);
        $this->item_copier->copyItem(
            new DateTimeImmutable(),
            Mockery::mock(Docman_Folder::class),
            Mockery::mock(PFUser::class),
            $copy_item_representation
        );
    }

    public function testCanNotCopyAnItemInADifferentProjectThanTheDestination(): void
    {
        $copy_item_representation          = new DocmanCopyItemRepresentation();
        $copy_item_representation->item_id = 741;

        $item_to_copy = Mockery::mock(Docman_Item::class);
        $item_to_copy->shouldReceive('getId')->andReturn(741);
        $this->item_factory->shouldReceive('getItemFromDb')->andReturn($item_to_copy);

        $this->permission_manager->shouldReceive('userCanAccess')->andReturn(true);

        $destination_folder = Mockery::mock(Docman_Folder::class);
        $destination_folder->shouldReceive('getId')->andReturn(963);
        $item_to_copy->shouldReceive('getId')->andReturn(741);
        $destination_folder->shouldReceive('getGroupId')->andReturn('103');
        $item_to_copy->shouldReceive('getGroupId')->andReturn('102');

        $this->expectException(RestException::class);
        $this->expectExceptionCode(400);
        $this->item_copier->copyItem(
            new DateTimeImmutable(),
            $destination_folder,
            Mockery::mock(PFUser::class),
            $copy_item_representation
        );
    }

    public function testItemTitleIsUpdatedIfADuplicateExistsInTheDestinationFolder(): void
    {
        $destination_folder                = Mockery::mock(Docman_Folder::class);
        $copy_item_representation          = new DocmanCopyItemRepresentation();
        $copy_item_representation->item_id = 741;

        $item_to_copy = Mockery::mock(Docman_Item::class);
        $this->item_factory->shouldReceive('getItemFromDb')->andReturn($item_to_copy);

        $destination_folder->shouldReceive('getId')->andReturn(963);
        $item_to_copy->shouldReceive('getId')->andReturn(741);
        $destination_folder->shouldReceive('getGroupId')->andReturn('102');
        $item_to_copy->shouldReceive('getGroupId')->andReturn('102');

        $this->permission_manager->shouldReceive('userCanAccess')->andReturn(true);

        $this->item_factory->shouldReceive('cloneItems')->andReturn([741 => 999]);

        $item_to_copy->shouldReceive('getTitle')->andReturn('Title');
        $item_copy_expectation = new ItemBeingCopiedExpectation('Copy of Title');
        $item_to_copy->shouldReceive('accept')
            ->with(Mockery::type(BeforeCopyVisitor::class), Mockery::any())
            ->andReturn($item_copy_expectation);
        $this->item_factory->shouldReceive('update')->withArgs(function (array $row) use ($item_copy_expectation): bool {
            $this->assertEquals($row['title'], $item_copy_expectation->getExpectedTitle());
            return true;
        });

        $this->event_manager->shouldReceive('processEvent')->with('send_notifications');

        $representation_copy = $this->item_copier->copyItem(
            new DateTimeImmutable(),
            $destination_folder,
            Mockery::mock(PFUser::class),
            $copy_item_representation
        );

        $this->assertEquals($representation_copy->id, 999);
    }

    public function testCloneIsInterruptedWhenItemDoesNotAppearToHaveBeenCloned(): void
    {
        $destination_folder                = Mockery::mock(Docman_Folder::class);
        $copy_item_representation          = new DocmanCopyItemRepresentation();
        $copy_item_representation->item_id = 741;

        $item_to_copy = Mockery::mock(Docman_Item::class);
        $this->item_factory->shouldReceive('getItemFromDb')->andReturn($item_to_copy);

        $destination_folder->shouldReceive('getId')->andReturn(963);
        $item_to_copy->shouldReceive('getId')->andReturn(741);
        $destination_folder->shouldReceive('getGroupId')->andReturn('102');
        $item_to_copy->shouldReceive('getGroupId')->andReturn('102');

        $this->permission_manager->shouldReceive('userCanAccess')->andReturn(true);

        $item_to_copy->shouldReceive('getTitle')->andReturn('Title');
        $item_to_copy->shouldReceive('accept')
            ->with(Mockery::type(BeforeCopyVisitor::class), Mockery::any())
            ->andReturn(new ItemBeingCopiedExpectation('Title'));

        $this->item_factory->shouldReceive('cloneItems')->andReturn([]);

        $this->expectException(RuntimeException::class);
        $this->item_copier->copyItem(
            new DateTimeImmutable(),
            $destination_folder,
            Mockery::mock(PFUser::class),
            $copy_item_representation
        );
    }
}
