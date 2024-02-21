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

use Tuleap\Project\Registration\Template\Upload\DeleteFileUploadStub;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Upload\FileBeingUploadedInformation;
use Tuleap\Upload\UploadPathAllocator;

final class ProjectFileUploadFinisherTest extends TestCase
{
    use \Tuleap\TemporaryTestDirectory;

    private string $base_path;
    private UploadPathAllocator $path_allocator;
    private DeleteFileUploadStub $file_upload_dao;
    private ProjectFileUploadFinisher $finisher;


    protected function setUp(): void
    {
        $this->base_path       = $this->getTmpDir();
        $this->path_allocator  = new UploadPathAllocator($this->base_path);
        $this->file_upload_dao = DeleteFileUploadStub::build();
        $this->finisher        = new ProjectFileUploadFinisher(
            $this->file_upload_dao,
            $this->path_allocator
        );
    }

    public function testItDeletesTheSavedFileInDBWhenTheUploadIsFinished(): void
    {
        $file_information = new FileBeingUploadedInformation(15, "test.zip", 996, 0);
        $item_path        = $this->path_allocator->getPathForItemBeingUploaded($file_information);

        copy(__DIR__ . "/_fixtures/test.zip", $item_path);

        $this->finisher->finishUpload($file_information);
        self::assertSame(1, $this->file_upload_dao->getDeleteByIdMethodCallCount());
    }

    public function testItThrowsWhenProvidedFileIsNotAnArchive(): void
    {
        $file_information = new FileBeingUploadedInformation(15, "filename.md", 996, 0);
        $item_path        = $this->path_allocator->getPathForItemBeingUploaded($file_information);
        file_put_contents($item_path, "#test");

        $this->expectException(FileIsNotAnArchiveException::class);
        $this->finisher->finishUpload($file_information);
    }
}
