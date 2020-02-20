<?php
/**
 * Copyright (c) Enalean, 2016-Present. All Rights Reserved.
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

namespace Tuleap\Backend;

use PHPUnit\Framework\TestCase;
use RecursiveIteratorIterator;

final class FileExtensionFilterIteratorTest extends TestCase
{
    public function testItGetsAllTheFiles() : void
    {
        $iterator = $this->getRecurseDirectoryIterator([]);
        $filtered_files = $this->extractFilenames($iterator);
        $expected_files = [
            'dir1',
            'dir11',
            'file11.js',
            'file1.html.php',
            'file1.php',
            'dir2',
            'file2.txt',
            'file0',
            'file0.php'
        ];

        $this->assertEqualsCanonicalizing($expected_files, $filtered_files);
    }

    public function testItFiltersByFileExtension(): void
    {
        $iterator_php_files = $this->getRecurseDirectoryIterator(['php']);
        $filtered_php_files = $this->extractFilenames($iterator_php_files);
        $expected_php_files = [
            'dir1',
            'dir11',
            'file1.html.php',
            'file1.php',
            'dir2',
            'file0.php'
        ];
        $this->assertEqualsCanonicalizing($expected_php_files, $filtered_php_files);

        $iterator_php_js_files = $this->getRecurseDirectoryIterator(['php', 'js']);
        $filtered_php_js_files = $this->extractFilenames($iterator_php_js_files);
        $expected_php_js_files = [
            'dir1',
            'dir11',
            'file11.js',
            'file1.html.php',
            'file1.php',
            'dir2',
            'file0.php'
        ];
        $this->assertEqualsCanonicalizing($expected_php_js_files, $filtered_php_js_files);
    }

    public function testItFiltersByFileWithoutExtension(): void
    {
        $iterator_no_extension_files = $this->getRecurseDirectoryIterator(['']);
        $filtered_no_extension_files = $this->extractFilenames($iterator_no_extension_files);
        $expected_files              = [
            'dir1',
            'dir11',
            'dir2',
            'file0'
        ];
        $this->assertEqualsCanonicalizing($expected_files, $filtered_no_extension_files);
    }

    private function getRecurseDirectoryIterator(array $allowed_extension): RecursiveIteratorIterator
    {
        return new \RecursiveIteratorIterator(
            new FileExtensionFilterIterator(
                new \RecursiveDirectoryIterator(
                    __DIR__ . '/_fixtures/iterator_test',
                    \FilesystemIterator::SKIP_DOTS
                ),
                $allowed_extension
            ),
            \RecursiveIteratorIterator::CHILD_FIRST
        );
    }

    /**
     * @return string[]
     */
    private function extractFilenames(\Iterator $iterator): array
    {
        $filenames = array();
        foreach ($iterator as $path => $file_information) {
            $filenames[] = $file_information->getFilename();
        }
        return $filenames;
    }
}
