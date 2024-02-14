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
use Tuleap\Project\REST\v1\File\ProjectFilePOSTRepresentation;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;

final class FileToUploadCreatorTest extends TestCase
{
    private const SAVED_FILE_ID = 10;

    public function testItReturnsTheFileToUpload(): void
    {
        $result = ( new ProjectFileToUploadCreator(
            SaveFileUploadStub::withASavedFile(self::SAVED_FILE_ID),
        ))->creatFileToUpload(
            new ProjectFilePOSTRepresentation(
                "BRZ",
                1277
            ),
            UserTestBuilder::buildWithId(101),
            new DateTimeImmutable()
        );
        self::assertSame('/uploads/project/file/' . self::SAVED_FILE_ID, $result->getUploadHref());
    }
}
