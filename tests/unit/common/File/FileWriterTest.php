<?php
/**
 * Copyright (c) Enalean, 2024-Present. All Rights Reserved.
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

namespace Tuleap\File;

use org\bovigo\vfs\vfsStream;
use Tuleap\Test\PHPUnit\TestCase;

final class FileWriterTest extends TestCase
{
    public function testWriteExpectedContent(): void
    {
        $content   = 'Tuleap 🌷';
        $file_path = vfsStream::setup()->url() . '/my_file';

        FileWriter::writeFile($file_path, $content);

        self::assertSame($content, file_get_contents($file_path));
    }

    /**
     * @dataProvider dataProviderPermissions
     */
    public function testCreateFileWithExpectedPermissions(int $chmod): void
    {
        $file_path = vfsStream::setup()->url() . '/my_file';

        FileWriter::writeFile($file_path, 'content', $chmod);

        self::assertSame($chmod, fileperms($file_path) & 0777);
    }

    protected function dataProviderPermissions(): array
    {
        return [
            [0600],
            [0640],
            [0644],
        ];
    }
}
