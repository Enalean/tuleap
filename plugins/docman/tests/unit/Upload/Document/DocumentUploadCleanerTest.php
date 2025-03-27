<?php
/**
 * Copyright (c) Enalean, 2019 - Present. All Rights Reserved.
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

namespace Tuleap\Docman\Upload\Document;

use DateTimeImmutable;
use org\bovigo\vfs\vfsStream;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Upload\FileBeingUploadedInformation;
use Tuleap\Upload\UploadPathAllocator;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class DocumentUploadCleanerTest extends TestCase
{
    public function testDanglingDocumentBeingUploadedAreCleaned(): void
    {
        $tmp_dir = vfsStream::setup();

        $dao            = $this->createMock(DocumentOngoingUploadDAO::class);
        $path_allocator = new UploadPathAllocator($tmp_dir->url() . '/document');

        $existing_item_id                  = 10;
        $existing_file_information         = new FileBeingUploadedInformation($existing_item_id, 'Filename', 10, 0);
        $existing_item_being_uploaded_path = $path_allocator->getPathForItemBeingUploaded($existing_file_information);
        mkdir(dirname($existing_item_being_uploaded_path), 0777, true);
        touch($existing_item_being_uploaded_path);
        $dao->method('searchDocumentOngoingUploadItemIDs')->willReturn([$existing_item_id]);
        $non_existing_file_information = new FileBeingUploadedInformation(999999, 'Filename', 10, 0);
        $non_existing_item_path        = $path_allocator->getPathForItemBeingUploaded($non_existing_file_information);
        touch($non_existing_item_path);

        $dao->expects($this->once())->method('deleteUnusableDocuments');

        $cleaner = new DocumentUploadCleaner($path_allocator, $dao);

        $current_time = new DateTimeImmutable();
        $cleaner->deleteDanglingDocumentToUpload($current_time);
        self::assertFileExists($existing_item_being_uploaded_path);
        self::assertFileDoesNotExist($non_existing_item_path);
    }
}
