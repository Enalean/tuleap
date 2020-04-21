<?php
/**
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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
 *
 */

require_once __DIR__ . '/../../../../src/www/file/file_utils.php';

class FileUtilsTest extends \PHPUnit\Framework\TestCase // phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace
{
    use \Tuleap\TemporaryTestDirectory;

    /**
     * @dataProvider getFileNames
     */
    public function testFileUtilsGetSize(string $filename): void
    {
        $file = $this->getTmpDir() . '/' . $filename;
        copy(__DIR__ . '/_fixtures/File_1_Mo_sample', $file);
        $this->assertNotNull(file_utils_get_size($file));
        $this->assertTrue(file_utils_get_size($file) > 0);
        $this->assertTrue(file_utils_get_size($file) > 1000000);
        $this->assertTrue(file_utils_get_size($file) < 2000000);
        unlink($file);
    }

    public function getFileNames(): array
    {
        return [
            ['File_1_Mo'],
            ['File 1 Mo'],
            ['File "1" Mo'],
            ['File "1 Mo'],
            ["File '1 Mo"],
        ];
    }
}
