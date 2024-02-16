<?php
/**
 * Copyright (c) Enalean, 2024-present. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
 *
 *  Tuleap is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  Tuleap is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace Tuleap\Project\Registration\Template\Upload\Tus;

use org\bovigo\vfs\vfsStream;
use Tuleap\Project\Registration\Template\Upload\DeleteFileUploadStub;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Upload\FileBeingUploadedInformation;
use Tuleap\Upload\UploadPathAllocator;

final class ProjectFileUploadCancelerTest extends TestCase
{
    private UploadPathAllocator $path_allocator;
    private DeleteFileUploadStub $dao;
    private ProjectFileUploadCanceler $canceler;

    protected function setUp(): void
    {
        $this->path_allocator = new UploadPathAllocator(vfsStream::setup()->url() . '/project/ongoing-uploads');
        $this->dao            = DeleteFileUploadStub::build();

        $this->canceler = new ProjectFileUploadCanceler(
            $this->path_allocator,
            $this->dao
        );
    }

    public function testFileBeingUploadedIsCleanedWhenTheUploadIsCancelled(): void
    {
        $file_information = new FileBeingUploadedInformation(15, "MX-5", 996, 0);
        $item_path        = $this->path_allocator->getPathForItemBeingUploaded($file_information);

        mkdir(dirname($item_path), 0777, true);
        touch($this->path_allocator->getPathForItemBeingUploaded($file_information));

        self::assertFileExists($item_path);
        $this->canceler->terminateUpload($file_information);
        self::assertFileDoesNotExist($item_path);
        self::assertSame(1, $this->dao->getDeleteByIdMethodCallCount());
    }

    public function testCancellingAnUploadThatHasNotYetStartedDoesNotGiveAWarning(): void
    {
        $file_information = new FileBeingUploadedInformation(15, "MX-5", 996, 0);
        $item_path        = $this->path_allocator->getPathForItemBeingUploaded($file_information);

        self::assertFileDoesNotExist($item_path);
        $this->canceler->terminateUpload($file_information);
        self::assertFileDoesNotExist($item_path);
        self::assertSame(1, $this->dao->getDeleteByIdMethodCallCount());
    }
}
