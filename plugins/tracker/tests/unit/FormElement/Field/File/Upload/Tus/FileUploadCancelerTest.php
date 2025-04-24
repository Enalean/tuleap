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

namespace Tuleap\Tracker\FormElement\Field\File\Upload\Tus;

use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\FormElement\Field\File\Upload\FileOngoingUploadDao;
use Tuleap\Upload\FileBeingUploadedInformation;
use Tuleap\Upload\PathAllocator;

#[DisableReturnValueGenerationForTestDoubles]
final class FileUploadCancelerTest extends TestCase
{
    public function testDocumentBeingUploadedIsCleanedWhenTheUploadIsCancelled(): void
    {
        $item_path      = vfsStream::setup()->url() . '/file/12';
        $path_allocator = $this->createMock(PathAllocator::class);
        $path_allocator->method('getPathForItemBeingUploaded')->willReturn($item_path);
        $dao = $this->createMock(FileOngoingUploadDao::class);

        $canceler = new FileUploadCanceler($path_allocator, $dao);

        $item_id          = 12;
        $file_information = new FileBeingUploadedInformation($item_id, 'Filename', 123, 0);
        mkdir(dirname($item_path), 0777, true);
        touch($item_path);

        $dao->expects($this->once())->method('deleteByItemID');

        $canceler->terminateUpload($file_information);
        self::assertFileDoesNotExist($item_path);
    }

    public function testCancellingAnUploadThatHasNotYetStartedDoesNotGiveAWarning(): void
    {
        $item_path      = vfsStream::setup()->url() . '/file/12';
        $path_allocator = $this->createMock(PathAllocator::class);
        $path_allocator->method('getPathForItemBeingUploaded')->willReturn($item_path);
        $dao = $this->createMock(FileOngoingUploadDao::class);

        $canceler = new FileUploadCanceler($path_allocator, $dao);

        $item_id          = 12;
        $file_information = new FileBeingUploadedInformation($item_id, 'Filename', 123, 0);

        $dao->expects($this->once())->method('deleteByItemID');

        $canceler->terminateUpload($file_information);
        self::assertFileDoesNotExist($item_path);
    }
}
