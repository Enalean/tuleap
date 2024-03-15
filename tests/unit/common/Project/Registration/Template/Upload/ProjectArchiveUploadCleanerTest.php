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

namespace Tuleap\Project\Registration\Template\Upload;

use DateTimeImmutable;
use Tuleap\TemporaryTestDirectory;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Upload\UploadPathAllocator;

final class ProjectArchiveUploadCleanerTest extends TestCase
{
    use TemporaryTestDirectory;

    protected function setUp(): void
    {
    }

    public function testDeleteExpiredAndDanglingFiles(): void
    {
        $base_dir =  $this->getTmpDir() . '/project/ongoing-upload';

        $first_file_uploaded_path  = $base_dir . '/1';
        $second_file_uploaded_path = $base_dir . '/2';
        $third_file_uploaded_path  = $base_dir . '/3';

        mkdir($base_dir, 0777, true);
        touch($first_file_uploaded_path);
        touch($second_file_uploaded_path);
        touch($third_file_uploaded_path);

        $delete_unused_file      = DeleteUnusedFilesStub::build();
        $project_archive_cleaner = new ProjectArchiveUploadCleaner(
            new UploadPathAllocator($base_dir),
            SearchFileUploadIdsStub::withFileIds([1, 3]),
            $delete_unused_file
        );

        self::assertFileExists($first_file_uploaded_path);
        self::assertFileExists($second_file_uploaded_path);
        self::assertFileExists($third_file_uploaded_path);

        $project_archive_cleaner->deleteUploadedDanglingProjectArchive(new DateTimeImmutable());

        self::assertFileExists($first_file_uploaded_path);
        self::assertFileDoesNotExist($second_file_uploaded_path);
        self::assertFileExists($third_file_uploaded_path);
        self::assertSame(1, $delete_unused_file->getCallCount());
    }
}
