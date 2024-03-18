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

namespace Tuleap\Docman\REST\v1\MoveItem;

use DateTimeImmutable;
use Docman_EmbeddedFile;
use Docman_Empty;
use Docman_File;
use Docman_Folder;
use Docman_Item;
use Docman_ItemFactory;
use Docman_Link;
use Docman_Wiki;
use Luracast\Restler\RestException;
use Mockery;
use Tuleap\Docman\ItemType\DoesItemHasExpectedTypeVisitor;
use Tuleap\Docman\Upload\Document\DocumentOngoingUploadRetriever;

final class BeforeMoveVisitorTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    /**
     * @dataProvider dataProviderProcessableDocumentClasses
     */
    public function testAllExpectedDocumentTypesCanBeProcessed(string $processed_document_class): void
    {
        $this->expectNotToPerformAssertions();

        $item_factory = Mockery::mock(Docman_ItemFactory::class);
        $item_factory->shouldReceive('doesTitleCorrespondToExistingDocument')->andReturn(false);
        $item_factory->shouldReceive('isMoveable')->andReturn(true);
        $document_ongoing_upload_retriever = Mockery::mock(DocumentOngoingUploadRetriever::class);
        $document_ongoing_upload_retriever->shouldReceive('isThereAlreadyAnUploadOngoing')->andReturn(false);

        $before_move_visitor = new BeforeMoveVisitor(
            new DoesItemHasExpectedTypeVisitor($processed_document_class),
            $item_factory,
            $document_ongoing_upload_retriever
        );

        $destination_folder = Mockery::mock(Docman_Folder::class);
        $destination_folder->shouldReceive('getId')->andReturn(147);

        $document = new $processed_document_class();
        $document->setTitle('Title');
        $document->accept(
            $before_move_visitor,
            ['destination' => $destination_folder, 'current_time' => new DateTimeImmutable()]
        );
    }

    /**
     * @dataProvider dataProviderProcessableItemClasses
     */
    public function testProcessingOfANonExpectedItemTypeIsRejected(string $processed_item_class): void
    {
        $before_move_visitor = new BeforeMoveVisitor(
            new DoesItemHasExpectedTypeVisitor(Docman_Item::class),
            Mockery::mock(Docman_ItemFactory::class),
            Mockery::mock(DocumentOngoingUploadRetriever::class)
        );

        $item = new $processed_item_class();

        $this->expectException(RestException::class);
        $this->expectExceptionCode(400);
        $item->accept(
            $before_move_visitor,
            ['destination' => Mockery::mock(Docman_Folder::class), 'current_time' => new DateTimeImmutable()]
        );
    }

    /**
     * @dataProvider dataProviderProcessableItemClasses
     */
    public function testProcessingOfAnItemIsRejectedIfItIsNotMovable(string $processed_item_class): void
    {
        $item_factory = Mockery::mock(Docman_ItemFactory::class);
        $item_factory->shouldReceive('isMoveable')->andReturn(false);

        $before_move_visitor = new BeforeMoveVisitor(
            new DoesItemHasExpectedTypeVisitor($processed_item_class),
            $item_factory,
            Mockery::mock(DocumentOngoingUploadRetriever::class)
        );

        $item = new $processed_item_class();

        $this->expectException(RestException::class);
        $this->expectExceptionCode(400);
        $item->accept(
            $before_move_visitor,
            ['destination' => Mockery::mock(Docman_Folder::class), 'current_time' => new DateTimeImmutable()]
        );
    }

    /**
     * @dataProvider dataProviderProcessableDocumentClasses
     */
    public function testProcessingOfADocumentIsRejectedWhenTheNameIsAlreadyUsedInTheDestinationFolder(string $processed_document_class): void
    {
        $item_factory = Mockery::mock(Docman_ItemFactory::class);
        $item_factory->shouldReceive('isMoveable')->andReturn(true);
        $item_factory->shouldReceive('doesTitleCorrespondToExistingDocument')->andReturn(true);

        $before_move_visitor = new BeforeMoveVisitor(
            new DoesItemHasExpectedTypeVisitor($processed_document_class),
            $item_factory,
            Mockery::mock(DocumentOngoingUploadRetriever::class)
        );

        $document = new $processed_document_class();
        $document->setTitle('Title');

        $destination_folder = Mockery::mock(Docman_Folder::class);
        $destination_folder->shouldReceive('getId')->andReturn(147);

        $this->expectException(RestException::class);
        $this->expectExceptionCode(400);
        $document->accept(
            $before_move_visitor,
            ['destination' => $destination_folder, 'current_time' => new DateTimeImmutable()]
        );
    }

    /**
     * @dataProvider dataProviderProcessableDocumentClasses
     */
    public function testProcessingOfADocumentIsRejectedWhenTheNameIsUsedByAnOngoingUploadInTheDestinationFolder(string $processed_document_class): void
    {
        $item_factory = Mockery::mock(Docman_ItemFactory::class);
        $item_factory->shouldReceive('isMoveable')->andReturn(true);
        $item_factory->shouldReceive('doesTitleCorrespondToExistingDocument')->andReturn(false);
        $document_ongoing_upload_retriever = Mockery::mock(DocumentOngoingUploadRetriever::class);
        $document_ongoing_upload_retriever->shouldReceive('isThereAlreadyAnUploadOngoing')->andReturn(true);

        $before_move_visitor = new BeforeMoveVisitor(
            new DoesItemHasExpectedTypeVisitor($processed_document_class),
            $item_factory,
            $document_ongoing_upload_retriever
        );

        $document = new $processed_document_class();
        $document->setTitle('Title');

        $destination_folder = Mockery::mock(Docman_Folder::class);
        $destination_folder->shouldReceive('getId')->andReturn(147);

        $this->expectException(RestException::class);
        $this->expectExceptionCode(409);
        $document->accept(
            $before_move_visitor,
            ['destination' => $destination_folder, 'current_time' => new DateTimeImmutable()]
        );
    }

    public function testProcessingOfAFolderIsRejectedWhenTheNameIsAlreadyUsedByAnotherFolderInTheDestinationFolder(): void
    {
        $item_factory = Mockery::mock(Docman_ItemFactory::class);
        $item_factory->shouldReceive('isMoveable')->andReturn(true);
        $item_factory->shouldReceive('doesTitleCorrespondToExistingFolder')->andReturn(true);

        $before_move_visitor = new BeforeMoveVisitor(
            new DoesItemHasExpectedTypeVisitor(Docman_Folder::class),
            $item_factory,
            Mockery::mock(DocumentOngoingUploadRetriever::class)
        );

        $folder_to_move = new Docman_Folder();
        $folder_to_move->setTitle('Title');

        $destination_folder = Mockery::mock(Docman_Folder::class);
        $destination_folder->shouldReceive('getId')->andReturn(147);

        $this->expectException(RestException::class);
        $this->expectExceptionCode(400);
        $folder_to_move->accept(
            $before_move_visitor,
            ['destination' => $destination_folder, 'current_time' => new DateTimeImmutable()]
        );
    }

    public function testProcessingOfAFolderIsRejectedWhenTheDestinationFolderIsItself(): void
    {
        $item_factory = Mockery::mock(Docman_ItemFactory::class);
        $item_factory->shouldReceive('isMoveable')->andReturn(true);
        $item_factory->shouldReceive('doesTitleCorrespondToExistingFolder')->andReturn(false);

        $before_move_visitor = new BeforeMoveVisitor(
            new DoesItemHasExpectedTypeVisitor(Docman_Folder::class),
            $item_factory,
            Mockery::mock(DocumentOngoingUploadRetriever::class)
        );

        $folder_to_move = new Docman_Folder();
        $folder_to_move->setTitle('Title');
        $folder_to_move->setId(147);

        $destination_folder = Mockery::mock(Docman_Folder::class);
        $destination_folder->shouldReceive('getId')->andReturn(147);

        $this->expectException(RestException::class);
        $this->expectExceptionCode(400);
        $folder_to_move->accept(
            $before_move_visitor,
            ['destination' => $destination_folder, 'current_time' => new DateTimeImmutable()]
        );
    }

    public function testProcessingOfAFolderIsRejectedWhenTheDestinationFolderIsOneOfTheChild(): void
    {
        $item_factory = Mockery::mock(Docman_ItemFactory::class);
        $item_factory->shouldReceive('isMoveable')->andReturn(true);
        $item_factory->shouldReceive('doesTitleCorrespondToExistingFolder')->andReturn(false);
        $item_factory->shouldReceive('isInSubtree')->andReturn(true);

        $before_move_visitor = new BeforeMoveVisitor(
            new DoesItemHasExpectedTypeVisitor(Docman_Folder::class),
            $item_factory,
            Mockery::mock(DocumentOngoingUploadRetriever::class)
        );

        $folder_to_move = new Docman_Folder();
        $folder_to_move->setTitle('Title');

        $destination_folder = Mockery::mock(Docman_Folder::class);
        $destination_folder->shouldReceive('getId')->andReturn(147);

        $this->expectException(RestException::class);
        $this->expectExceptionCode(400);
        $folder_to_move->accept(
            $before_move_visitor,
            ['destination' => $destination_folder, 'current_time' => new DateTimeImmutable()]
        );
    }

    public static function dataProviderProcessableDocumentClasses(): array
    {
        return [
            [Docman_Wiki::class],
            [Docman_Link::class],
            [Docman_File::class],
            [Docman_EmbeddedFile::class],
            [Docman_Empty::class],
        ];
    }

    public static function dataProviderProcessableItemClasses(): ?\Generator
    {
        yield [Docman_Folder::class];
        yield from self::dataProviderProcessableDocumentClasses();
    }
}
