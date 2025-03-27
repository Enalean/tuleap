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

use org\bovigo\vfs\vfsStream;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tus\TusFileInformation;
use Tuleap\Upload\UploadPathAllocator;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class DocumentUploadCancelerTest extends TestCase
{
    public function testDocumentBeingUploadedIsCleanedWhenTheUploadIsCancelled(): void
    {
        $path_allocator = new UploadPathAllocator(vfsStream::setup()->url() . '/document');
        $dao            = $this->createMock(DocumentOngoingUploadDAO::class);

        $canceler = new DocumentUploadCanceler($path_allocator, $dao);

        $file_information = $this->createMock(TusFileInformation::class);
        $item_id          = 12;
        $file_information->method('getID')->willReturn($item_id);
        $item_path = $path_allocator->getPathForItemBeingUploaded($file_information);
        mkdir(dirname($item_path), 0777, true);
        touch($path_allocator->getPathForItemBeingUploaded($file_information));

        $dao->expects($this->once())->method('deleteByItemID');

        $canceler->terminateUpload($file_information);
        self::assertFileDoesNotExist($item_path);
    }

    public function testCancellingAnUploadThatHasNotYetStartedDoesNotGiveAWarning(): void
    {
        $path_allocator = new UploadPathAllocator(vfsStream::setup()->url() . '/document');
        $dao            = $this->createMock(DocumentOngoingUploadDAO::class);

        $canceler = new DocumentUploadCanceler($path_allocator, $dao);

        $file_information = $this->createMock(TusFileInformation::class);
        $item_id          = 12;
        $file_information->method('getID')->willReturn($item_id);
        $item_path = $path_allocator->getPathForItemBeingUploaded($file_information);

        $dao->expects($this->once())->method('deleteByItemID');

        $canceler->terminateUpload($file_information);
        self::assertFileDoesNotExist($item_path);
    }
}
