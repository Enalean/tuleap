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

namespace Tuleap\FRS\Upload\Tus;

use org\bovigo\vfs\vfsStream;
use Tuleap\ForgeConfigSandbox;
use Tuleap\FRS\Upload\FileOngoingUploadDao;
use Tuleap\FRS\Upload\UploadPathAllocator;
use Tuleap\Upload\FileBeingUploadedInformation;

class FileUploadCancelerTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use ForgeConfigSandbox;

    public function testDocumentBeingUploadedIsCleanedWhenTheUploadIsCancelled(): void
    {
        \ForgeConfig::set('tmp_dir', vfsStream::setup()->url());
        $path_allocator = new UploadPathAllocator();
        $dao            = $this->createMock(FileOngoingUploadDao::class);

        $canceler = new FileUploadCanceler($path_allocator, $dao);

        $item_id          = 12;
        $file_information = new FileBeingUploadedInformation($item_id, 'Filename', 123, 0);
        $item_path        = $path_allocator->getPathForItemBeingUploaded($file_information);
        mkdir(dirname($item_path), 0777, true);
        touch($path_allocator->getPathForItemBeingUploaded($file_information));

        $dao->expects(self::once())->method('deleteByItemID');

        $canceler->terminateUpload($file_information);
        self::assertFileDoesNotExist($item_path);
    }

    public function testCancellingAnUploadThatHasNotYetStartedDoesNotGiveAWarning(): void
    {
        \ForgeConfig::set('tmp_dir', vfsStream::setup()->url());
        $path_allocator = new UploadPathAllocator();
        $dao            = $this->createMock(FileOngoingUploadDao::class);

        $canceler = new FileUploadCanceler($path_allocator, $dao);

        $item_id          = 12;
        $file_information = new FileBeingUploadedInformation($item_id, 'Filename', 123, 0);
        $item_path        = $path_allocator->getPathForItemBeingUploaded($file_information);

        $dao->expects(self::once())->method('deleteByItemID');

        $canceler->terminateUpload($file_information);
        self::assertFileDoesNotExist($item_path);
    }
}
