<?php
/**
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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

namespace Tuleap\Docman\Upload;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\TestCase;

class DocumentUploadCleanerTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function setUp()
    {
        \ForgeConfig::store();
    }

    public function tearDown()
    {
        \ForgeConfig::restore();
    }

    public function testDanglingDocumentBeingUploadedAreCleaned()
    {
        $dao            = \Mockery::mock(DocumentOngoingUploadDAO::class);
        $path_allocator = new DocumentUploadPathAllocator();

        $tmp_dir = vfsStream::setup();
        \ForgeConfig::set('tmp_dir', $tmp_dir->url());

        $existing_item_id = 10;
        $existing_item_being_uploaded_path = $path_allocator->getPathForItemBeingUploaded($existing_item_id);
        mkdir(dirname($existing_item_being_uploaded_path), 0777, true);
        touch($existing_item_being_uploaded_path);
        $dao->shouldReceive('searchDocumentOngoingUploadItemIDs')->andReturns([$existing_item_id]);
        $non_existing_item_path = $path_allocator->getPathForItemBeingUploaded(999999);
        touch($non_existing_item_path);

        $dao->shouldReceive('deleteUnusableDocuments')->once();

        $cleaner = new DocumentUploadCleaner($path_allocator, $dao);

        $current_time = new \DateTimeImmutable();
        $cleaner->deleteDanglingDocumentToUpload($current_time);
        $this->assertFileExists($existing_item_being_uploaded_path);
        $this->assertFileNotExists($non_existing_item_path);
    }
}
