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

namespace Tuleap\FRS\Upload;

use org\bovigo\vfs\vfsStream;
use Tuleap\ForgeConfigSandbox;
use Tuleap\Upload\FileBeingUploadedInformation;

final class FileUploadCleanerTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use ForgeConfigSandbox;

    public function testDanglingFilesBeingUploadedAreCleaned(): void
    {
        $dao            = $this->createMock(FileOngoingUploadDao::class);
        $path_allocator = new UploadPathAllocator();

        $tmp_dir = vfsStream::setup();
        \ForgeConfig::set('tmp_dir', $tmp_dir->url());

        $existing_item_id                  = 10;
        $existing_file_information         = new FileBeingUploadedInformation($existing_item_id, 'Filename', 10, 0);
        $existing_item_being_uploaded_path = $path_allocator->getPathForItemBeingUploaded($existing_file_information);
        mkdir(dirname($existing_item_being_uploaded_path), 0777, true);
        touch($existing_item_being_uploaded_path);
        $dao->method('searchFileOngoingUploadIds')->willReturn([$existing_item_id]);
        $non_existing_file_information = new FileBeingUploadedInformation(999999, 'Filename', 10, 0);
        $non_existing_item_path        = $path_allocator->getPathForItemBeingUploaded($non_existing_file_information);
        mkdir(dirname($non_existing_item_path), 0777, true);
        touch($non_existing_item_path);

        $dao->expects(self::once())->method('deleteUnusableFiles');

        $cleaner = new FileUploadCleaner($path_allocator, $dao);

        $current_time = new \DateTimeImmutable();
        $cleaner->deleteDanglingFilesToUpload($current_time);

        self::assertFileExists($existing_item_being_uploaded_path);
        self::assertFileDoesNotExist($non_existing_item_path);
        self::assertFileDoesNotExist(dirname($non_existing_item_path));
    }
}
