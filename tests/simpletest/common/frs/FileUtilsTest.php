<?php
/**
 * Copyright (c) Enalean, 2012 - 2017. All Rights Reserved.
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

require_once('www/file/file_utils.php');

class FileUtils extends TuleapTestCase {

    function testFileUtilsGetSize_1_Mo_File() {
        $file = $this->getTmpDir() . '/File_1_Mo';
        copy(dirname(__FILE__) . '/_fixtures/File_1_Mo_sample', $file);
        $this->assertNotNull(file_utils_get_size($file));
        $this->assertTrue(file_utils_get_size($file) > 0);
        $this->assertTrue(file_utils_get_size($file) > 1000000);
        $this->assertTrue(file_utils_get_size($file) < 2000000);
    }

    function testFileUtilsGetSize_1_Mo_File_with_spaces() {
        $file = $this->getTmpDir() . '/File 1 Mo';
        copy(dirname(__FILE__) . '/_fixtures/File_1_Mo_sample', $file);
        $this->assertNotNull(file_utils_get_size($file));
        $this->assertTrue(file_utils_get_size($file) > 0);
        $this->assertTrue(file_utils_get_size($file) > 1000000);
        $this->assertTrue(file_utils_get_size($file) < 2000000);
    }

    function testFileUtilsGetSize_1_Mo_File_with_spaces_and_quotes() {
        $file = $this->getTmpDir() . '/File "1" Mo';
        copy(dirname(__FILE__) . '/_fixtures/File_1_Mo_sample', $file);
        $this->assertNotNull(file_utils_get_size($file));
        $this->assertTrue(file_utils_get_size($file) > 0);
        $this->assertTrue(file_utils_get_size($file) > 1000000);
        $this->assertTrue(file_utils_get_size($file) < 2000000);
    }

    function testFileUtilsGetSize_1_Mo_File_with_spaces_and_quote() {
        $file = $this->getTmpDir() . '/File "1 Mo';
        copy(dirname(__FILE__) . '/_fixtures/File_1_Mo_sample', $file);
        $this->assertNotNull(file_utils_get_size($file));
        $this->assertTrue(file_utils_get_size($file) > 0);
        $this->assertTrue(file_utils_get_size($file) > 1000000);
        $this->assertTrue(file_utils_get_size($file) < 2000000);
    }

    function testFileUtilsGetSize_1_Mo_File_with_spaces_and_simple_quote() {
        $file = $this->getTmpDir() . "/File '1 Mo";
        copy(dirname(__FILE__) . '/_fixtures/File_1_Mo_sample', $file);
        $this->assertNotNull(file_utils_get_size($file));
        $this->assertTrue(file_utils_get_size($file) > 0);
        $this->assertTrue(file_utils_get_size($file) > 1000000);
        $this->assertTrue(file_utils_get_size($file) < 2000000);
    }
}
