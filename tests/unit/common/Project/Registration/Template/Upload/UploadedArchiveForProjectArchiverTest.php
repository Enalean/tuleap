<?php
/**
 * Copyright (c) Enalean, 2024 - Present. All Rights Reserved.
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

namespace Tuleap\Project\Registration\Template\Upload;

use org\bovigo\vfs\vfsStream;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;

final class UploadedArchiveForProjectArchiverTest extends TestCase
{
    public function testArchive(): void
    {
        $data_dir = vfsStream::setup()->url() . '/var/lib/tuleap';

        $uploaded_archive_path = __DIR__ . '/Tus/_fixtures/test.zip';

        $archiver    = new UploadedArchiveForProjectArchiver($data_dir);
        $destination = $archiver->archive(
            ProjectTestBuilder::aProject()->withId(1001)->build(),
            $uploaded_archive_path
        );

        self::assertFileExists($destination);
        self::assertFileExists($uploaded_archive_path);
        self::assertFileEquals($destination, $uploaded_archive_path);
    }
}
