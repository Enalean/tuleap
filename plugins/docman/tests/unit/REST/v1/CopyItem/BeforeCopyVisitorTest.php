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
use Docman_EmbeddedFile;
use Docman_Empty;
use Docman_File;
use Docman_Folder;
use Docman_Item;
use Docman_ItemFactory;
use Docman_Link;
use Docman_Wiki;
use Generator;
use LogicException;
use Luracast\Restler\RestException;
use Tuleap\Docman\ItemType\DoesItemHasExpectedTypeVisitor;
use Tuleap\Docman\Upload\Document\DocumentOngoingUploadRetriever;
use Tuleap\Test\PHPUnit\TestCase;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class BeforeCopyVisitorTest extends TestCase
{
    private const VISITOR_PROCESSABLE_CLASSES = [
        Docman_Folder::class,
        Docman_Wiki::class,
        Docman_Link::class,
        Docman_File::class,
        Docman_EmbeddedFile::class,
        Docman_Empty::class,
    ];

    #[\PHPUnit\Framework\Attributes\DataProvider('dataProviderProcessableItemClasses')]
    public function testAllExpectedItemTypesCanBeProcessed(string $processed_item_class): void
    {
        $item_factory = $this->createMock(Docman_ItemFactory::class);
        $item_factory->method('doesTitleCorrespondToExistingFolder')->willReturn(false);
        $item_factory->method('doesTitleCorrespondToExistingDocument')->willReturn(false);
        $document_ongoing_upload_retriever = $this->createMock(DocumentOngoingUploadRetriever::class);
        $document_ongoing_upload_retriever->method('isThereAlreadyAnUploadOngoing')->willReturn(false);

        $destination_folder = new Docman_Folder(['item_id' => 147]);

        $before_copy_visitor = new BeforeCopyVisitor(
            new DoesItemHasExpectedTypeVisitor($processed_item_class),
            $item_factory,
            $document_ongoing_upload_retriever
        );
        $processed_item      = new $processed_item_class();
        $processed_item->setTitle('Title');
        $expectation_for_copy = $processed_item->accept(
            $before_copy_visitor,
            ['destination' => $destination_folder, 'current_time' => new DateTimeImmutable()]
        );
        self::assertEquals('Title', $expectation_for_copy->getExpectedTitle());
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('dataProviderProcessableItemClasses')]
    public function testProcessingOfANonExpectedItemTypeIsRejected(string $processed_item_class): void
    {
        $document_ongoing_upload_retriever = $this->createMock(DocumentOngoingUploadRetriever::class);
        $document_ongoing_upload_retriever->method('isThereAlreadyAnUploadOngoing')->willReturn(false);

        $before_copy_visitor = new BeforeCopyVisitor(
            new DoesItemHasExpectedTypeVisitor(Docman_Item::class),
            $this->createMock(Docman_ItemFactory::class),
            $document_ongoing_upload_retriever
        );

        $item = new $processed_item_class();

        self::expectException(RestException::class);
        self::expectExceptionCode(400);
        $item->accept(
            $before_copy_visitor,
            ['destination' => new Docman_Folder(), 'current_time' => new DateTimeImmutable()]
        );
    }

    public static function dataProviderProcessableItemClasses(): ?Generator
    {
        foreach (self::VISITOR_PROCESSABLE_CLASSES as $processable_class) {
            yield [$processable_class];
        }
    }

    public function testProcessingGenericItemIsRejected(): void
    {
        $before_copy_visitor = new BeforeCopyVisitor(
            new DoesItemHasExpectedTypeVisitor(Docman_Item::class),
            $this->createMock(Docman_ItemFactory::class),
            $this->createMock(DocumentOngoingUploadRetriever::class)
        );

        self::expectException(LogicException::class);
        $before_copy_visitor->visitItem(new Docman_Item());
    }

    public function testDocumentExpectedTitleIsUpdatedInCaseOfConflict(): void
    {
        $item_factory                      = $this->createMock(Docman_ItemFactory::class);
        $document_ongoing_upload_retriever = $this->createMock(DocumentOngoingUploadRetriever::class);
        $before_copy_visitor               = new BeforeCopyVisitor(
            new DoesItemHasExpectedTypeVisitor(Docman_Empty::class),
            $item_factory,
            $document_ongoing_upload_retriever
        );

        $docman_document = new Docman_Empty(['title' => 'Title']);
        $destination     = new Docman_Folder(['item_id' => 456]);
        $document_ongoing_upload_retriever->method('isThereAlreadyAnUploadOngoing')->willReturn(false);

        $item_factory->method('doesTitleCorrespondToExistingDocument')->willReturnOnConsecutiveCalls(true, true, false);

        $expectation_for_copy = $before_copy_visitor->visitEmpty(
            $docman_document,
            ['destination' => $destination, 'current_time' => new DateTimeImmutable()]
        );

        self::assertEquals(
            2,
            substr_count(
                $expectation_for_copy->getExpectedTitle(),
                sprintf(dgettext('tuleap-docman', 'Copy of %s'), '')
            )
        );
    }

    public function testFolderExpectedTitleIsUpdatedInCaseOfConflict(): void
    {
        $item_factory        = $this->createMock(Docman_ItemFactory::class);
        $before_copy_visitor = new BeforeCopyVisitor(
            new DoesItemHasExpectedTypeVisitor(Docman_Folder::class),
            $item_factory,
            $this->createMock(DocumentOngoingUploadRetriever::class)
        );

        $docman_folder = new Docman_Folder(['title' => 'Title']);
        $destination   = new Docman_Folder(['item_id' => 456]);

        $item_factory->method('doesTitleCorrespondToExistingFolder')->willReturnOnConsecutiveCalls(true, true, false);

        $expectation_for_copy = $before_copy_visitor->visitFolder(
            $docman_folder,
            ['destination' => $destination, 'current_time' => new DateTimeImmutable()]
        );

        self::assertEquals(
            2,
            substr_count(
                $expectation_for_copy->getExpectedTitle(),
                sprintf(dgettext('tuleap-docman', 'Copy of %s'), '')
            )
        );
    }

    public function testCopyOfADocumentIsRejectedIfAnUploadIsAlreadyOngoingWithTheSameTitle(): void
    {
        $document_ongoing_upload_retriever = $this->createMock(DocumentOngoingUploadRetriever::class);
        $before_copy_visitor               = new BeforeCopyVisitor(
            new DoesItemHasExpectedTypeVisitor(Docman_Empty::class),
            $this->createMock(Docman_ItemFactory::class),
            $document_ongoing_upload_retriever
        );

        $docman_document = new Docman_Empty(['title' => 'Title']);
        $destination     = new Docman_Folder(['item_id' => 456]);

        $document_ongoing_upload_retriever->method('isThereAlreadyAnUploadOngoing')->willReturn(true);

        self::expectException(RestException::class);
        self::expectExceptionCode(409);
        $before_copy_visitor->visitEmpty(
            $docman_document,
            ['destination' => $destination, 'current_time' => new DateTimeImmutable()]
        );
    }
}
