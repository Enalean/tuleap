<?php
/**
 * Copyright (c) Enalean, 2019. All Rights Reserved.
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

namespace Tuleap\Docman\Upload\Version;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\TestCase;
use Tuleap\Upload\FileBeingUploadedInformation;
use Tuleap\Upload\UploadPathAllocator;

class VersionUploadCleanerTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function testDanglingDocumentBeingUploadedAreCleaned()
    {
        $tmp_dir = vfsStream::setup();

        $dao            = \Mockery::mock(DocumentOnGoingVersionToUploadDAO::class);
        $path_allocator = new UploadPathAllocator($tmp_dir->url() . '/document');

        $existing_version_id = 10;
        $existing_file_information = new FileBeingUploadedInformation($existing_version_id, 'Filename', 10, 0);
        $existing_version_being_uploaded_path = $path_allocator->getPathForItemBeingUploaded($existing_file_information);
        mkdir(dirname($existing_version_being_uploaded_path), 0777, true);
        touch($existing_version_being_uploaded_path);
        $dao->shouldReceive('searchVersionOngoingUploadItemIDs')->andReturns([$existing_version_id]);
        $non_existing_file_information = new FileBeingUploadedInformation(999999, 'Filename', 10, 0);
        $non_existing_item_path = $path_allocator->getPathForItemBeingUploaded($non_existing_file_information);
        touch($non_existing_item_path);

        $dao->shouldReceive('deleteUnusableVersions')->once();

        $cleaner = new VersionUploadCleaner($path_allocator, $dao);

        $current_time = new \DateTimeImmutable();
        $cleaner->deleteDanglingVersionToUpload($current_time);
        $this->assertFileExists($existing_version_being_uploaded_path);
        $this->assertFileDoesNotExist($non_existing_item_path);
    }
}
