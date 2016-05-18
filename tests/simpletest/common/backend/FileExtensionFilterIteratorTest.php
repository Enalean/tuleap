<?php
/**
 * Copyright (c) Enalean, 2016. All Rights Reserved.
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

namespace Tuleap\Backend;

use TuleapTestCase;

class FileExtensionFilterIteratorTest extends TuleapTestCase
{

    public function itGetsAllTheFiles()
    {
        $iterator = $this->getRecurseDirectoryIterator(array());
        $filtered_files = $this->extractFilenames($iterator);
        $expected_files = array(
            'dir1',
            'dir11',
            'file11.js',
            'file1.html.php',
            'file1.php',
            'dir2',
            'file2.txt',
            'file0',
            'file0.php'
        );
        $this->assertTrue($this->isArrayEquals($expected_files, $filtered_files));
    }

    public function itFiltersByFileExtension()
    {
        $iterator_php_files = $this->getRecurseDirectoryIterator(array('php'));
        $filtered_php_files = $this->extractFilenames($iterator_php_files);
        $expected_php_files = array(
            'dir1',
            'dir11',
            'file1.html.php',
            'file1.php',
            'dir2',
            'file0.php'
        );
        $this->assertTrue($this->isArrayEquals($expected_php_files, $filtered_php_files));

        $iterator_php_js_files = $this->getRecurseDirectoryIterator(array('php', 'js'));
        $filtered_php_js_files = $this->extractFilenames($iterator_php_js_files);
        $expected_php_js_files = array(
            'dir1',
            'dir11',
            'file11.js',
            'file1.html.php',
            'file1.php',
            'dir2',
            'file0.php'
        );
        $this->assertTrue($this->isArrayEquals($expected_php_js_files, $filtered_php_js_files));
    }

    public function itFiltersByFileWithoutExtension()
    {
        $iterator_no_extension_files = $this->getRecurseDirectoryIterator(array(''));
        $filtered_no_extension_files = $this->extractFilenames($iterator_no_extension_files);
        $expected_files = array(
            'dir1',
            'dir11',
            'dir2',
            'file0'
        );
        $this->assertTrue($this->isArrayEquals($expected_files, $filtered_no_extension_files));
    }

    /**
     * @return \RecursiveIteratorIterator
     */
    private function getRecurseDirectoryIterator(array $allowed_extension)
    {
        return new \RecursiveIteratorIterator(
            new FileExtensionFilterIterator(
                new \RecursiveDirectoryIterator(
                    dirname(__FILE__) . '/_fixtures/iterator_test',
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
    private function extractFilenames(\Iterator $iterator)
    {
        $filenames = array();
        foreach ($iterator as $path => $file_information) {
            $filenames[] = $file_information->getFilename();
        }
        return $filenames;
    }

    /**
     * @return bool
     */
    private function isArrayEquals(array $array1, array $array2)
    {
        return count($array1) === count($array2) && array_count_values($array1) == array_count_values($array2);
    }
}
