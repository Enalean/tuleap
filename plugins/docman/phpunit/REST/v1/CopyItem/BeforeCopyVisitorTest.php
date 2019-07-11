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
use LogicException;
use Luracast\Restler\RestException;
use Mockery;
use PHPUnit\Framework\TestCase;
use Tuleap\Docman\Upload\Document\DocumentOngoingUploadRetriever;

final class BeforeCopyVisitorTest extends TestCase
{
    use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    public function testOnlyExpectedTypeIsProcessed() : void
    {
        $item_factory = Mockery::mock(Docman_ItemFactory::class);
        $item_factory->shouldReceive('doesTitleCorrespondToExistingFolder')->andReturn(false);
        $item_factory->shouldReceive('doesTitleCorrespondToExistingDocument')->andReturn(false);
        $document_ongoing_upload_retriever = Mockery::mock(DocumentOngoingUploadRetriever::class);
        $document_ongoing_upload_retriever->shouldReceive('isThereAlreadyAnUploadOngoing')->andReturn(false);

        $destination_folder = Mockery::mock(Docman_Folder::class);
        $destination_folder->shouldReceive('getId')->andReturn(147);

        $processable_classes = [
            Docman_Folder::class,
            Docman_Wiki::class,
            Docman_Link::class,
            Docman_File::class,
            Docman_EmbeddedFile::class,
            Docman_Empty::class
        ];

        foreach ($processable_classes as $processed_class) {
            $processed_item = new $processed_class();
            $processed_item->setTitle('Title');
            foreach ($processable_classes as $visitor_accepted_class) {
                $before_copy_visitor = new BeforeCopyVisitor(
                    $visitor_accepted_class,
                    $item_factory,
                    $document_ongoing_upload_retriever
                );

                try {
                    $expectation_for_copy = $processed_item->accept(
                        $before_copy_visitor,
                        ['destination' => $destination_folder, 'current_time' => new DateTimeImmutable()]
                    );
                } catch (RestException $ex) {
                    $this->assertNotEquals($processed_class, $visitor_accepted_class, 'The visitor has rejected a valid item');
                    $this->assertEquals(400, $ex->getCode());
                    continue;
                }

                $this->assertEquals($processed_class, $visitor_accepted_class, 'The visitor has accepted a invalid item');
                $this->assertEquals('Title', $expectation_for_copy->getExpectedTitle());
            }
        }
    }

    public function testProcessingGenericItemIsRejected() : void
    {
        $before_copy_visitor = new BeforeCopyVisitor(
            Docman_Item::class,
            Mockery::mock(Docman_ItemFactory::class),
            Mockery::mock(DocumentOngoingUploadRetriever::class)
        );

        $this->expectException(LogicException::class);
        $before_copy_visitor->visitItem(Mockery::mock(Docman_Item::class));
    }

    public function testDocumentExpectedTitleIsUpdatedInCaseOfConflict() : void
    {
        $item_factory                      = Mockery::mock(Docman_ItemFactory::class);
        $document_ongoing_upload_retriever = Mockery::mock(DocumentOngoingUploadRetriever::class);
        $before_copy_visitor               = new BeforeCopyVisitor(
            Docman_Empty::class,
            $item_factory,
            $document_ongoing_upload_retriever
        );

        $docman_document = new Docman_Empty(['title' => 'Title']);
        $destination     = Mockery::mock(Docman_Folder::class);
        $destination->shouldReceive('getId')->andReturn(456);
        $document_ongoing_upload_retriever->shouldReceive('isThereAlreadyAnUploadOngoing')->andReturn(false);

        $item_factory->shouldReceive('doesTitleCorrespondToExistingDocument')->andReturn(true, true, false);

        $expectation_for_copy = $before_copy_visitor->visitEmpty(
            $docman_document,
            ['destination' => $destination, 'current_time' => new DateTimeImmutable()]
        );

        $this->assertEquals(
            2,
            substr_count(
                $expectation_for_copy->getExpectedTitle(),
                sprintf(dgettext('tuleap-docman', 'Copy of %s'), '')
            )
        );
    }

    public function testFolderExpectedTitleIsUpdatedInCaseOfConflict() : void
    {
        $item_factory                      = Mockery::mock(Docman_ItemFactory::class);
        $before_copy_visitor               = new BeforeCopyVisitor(
            Docman_Folder::class,
            $item_factory,
            Mockery::mock(DocumentOngoingUploadRetriever::class)
        );

        $docman_folder = new Docman_Folder(['title' => 'Title']);
        $destination   = Mockery::mock(Docman_Folder::class);
        $destination->shouldReceive('getId')->andReturn(456);

        $item_factory->shouldReceive('doesTitleCorrespondToExistingFolder')->andReturn(true, true, false);

        $expectation_for_copy = $before_copy_visitor->visitFolder(
            $docman_folder,
            ['destination' => $destination, 'current_time' => new DateTimeImmutable()]
        );

        $this->assertEquals(
            2,
            substr_count(
                $expectation_for_copy->getExpectedTitle(),
                sprintf(dgettext('tuleap-docman', 'Copy of %s'), '')
            )
        );
    }

    public function testCopyOfADocumentIsRejectedIfAnUploadIsAlreadyOngoingWithTheSameTitle() : void
    {
        $document_ongoing_upload_retriever = Mockery::mock(DocumentOngoingUploadRetriever::class);
        $before_copy_visitor               = new BeforeCopyVisitor(
            Docman_Empty::class,
            Mockery::mock(Docman_ItemFactory::class),
            $document_ongoing_upload_retriever
        );

        $docman_document = new Docman_Empty(['title' => 'Title']);
        $destination     = Mockery::mock(Docman_Folder::class);
        $destination->shouldReceive('getId')->andReturn(456);

        $document_ongoing_upload_retriever->shouldReceive('isThereAlreadyAnUploadOngoing')->andReturn(true);

        $this->expectException(RestException::class);
        $this->expectExceptionCode(409);
        $before_copy_visitor->visitEmpty(
            $docman_document,
            ['destination' => $destination, 'current_time' => new DateTimeImmutable()]
        );
    }
}
