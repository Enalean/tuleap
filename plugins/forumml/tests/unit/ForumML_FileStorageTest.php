<?php
/**
 * Copyright (c) Enalean, 2012 - Present. All Rights Reserved.
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

namespace Tuleap\ForumML;

use ForumML_FileStorage;
use org\bovigo\vfs\vfsStream;

include __DIR__ . '/bootstrap.php';

// phpcs:ignore Squiz.Classes.ValidClassName.NotCamelCaps
final class ForumML_FileStorageTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private string $path;
    private string $name_pattern;
    private ForumML_FileStorage $file_storage;

    protected function setUp(): void
    {
        parent::setUp();

        $this->path         = vfsStream::setup()->url();
        $this->name_pattern = "`[^a-z0-9_-]`i";

        $this->file_storage = new ForumML_FileStorage($this->path);
    }

    public function testForumMLFileStorage(): void
    {
        self::assertNotNull($this->file_storage->root);
        self::assertIsString($this->file_storage->root);
        self::assertSame($this->path, $this->file_storage->root);
    }

    // case 1: an attachment file whose name has more than 64 characters
    public function testGetPathFileNameWithMoreThan64Char(): void
    {
        $name1 = "a string with more than 64 characters, which is the limit allowed for ForumML attachments";
        $list1 = "gpig-interest";
        $date1 = "2007_10_24";
        $type1 = "store";

        // check returned path
        $path1 = $this->file_storage->_getPath($name1, $list1, $date1, $type1);
        self::assertNotNull($path1);
        self::assertIsString($path1);

        // check filename length is restricted to 64 characters
        $path_array1 = explode("/", $path1);
        $fname1      = end($path_array1);
        self::assertNotEquals($name1, $fname1);
        self::assertSame(63, strlen($fname1));
        // check other path components
        $flist1 = $path_array1[3];
        self::assertEquals($flist1, $list1);
        $fdate1 = $path_array1[4];
        self::assertEquals($fdate1, $date1);
        // check regexp
        self::assertMatchesRegularExpression($this->name_pattern, $name1);
    }

    // case 2: an attachment file whose name has less than 64 characters
    public function testGetPathFileNameWithLessThan64Char(): void
    {
        $name2 = "filename less than 64 chars";
        $list1 = "gpig-interest";
        $date1 = "2007_10_24";
        $type1 = "store";

        $path2 = $this->file_storage->_getPath($name2, $list1, $date1, $type1);
        self::assertNotNull($path2);
        self::assertIsString($path2);
        $path_array2 = explode("/", $path2);
        $fname2      = end($path_array2);
        self::assertEquals("filename_less_than_64_chars", $fname2);
        self::assertNotEquals(64, strlen($fname2));
        // check path components
        $flist2 = $path_array2[3];
        self::assertEquals($flist2, $list1);
        $fdate2 = $path_array2[4];
        self::assertEquals($fdate2, $date1);
        // check regexp
        self::assertMatchesRegularExpression($this->name_pattern, $name2);
    }

    // case 3: attachment filename with only alphanumeric characters
    public function testGetPathFileNameWithAlphaNumCharsOnly(): void
    {
        $name3 = "Cx2008-requirements";
        $list1 = "gpig-interest";
        $date1 = "2007_10_24";
        $type1 = "store";

        $path3 = $this->file_storage->_getPath($name3, $list1, $date1, $type1);
        self::assertNotNull($path3);
        self::assertIsString($path3);
        self::assertDoesNotMatchRegularExpression($this->name_pattern, $name3);
    }

    // case 4: attachment filename is an empty string
    public function testGetPathFileNameEmpty(): void
    {
        $name4 = "";
        $list1 = "gpig-interest";
        $date1 = "2007_10_24";
        $type1 = "store";

        $path4 = $this->file_storage->_getPath($name4, $list1, $date1, $type1);
        self::assertNotNull($path4);
        self::assertIsString($path4);
        $path_array4 = explode("/", $path4);
        $fname4      = $path_array4[count($path_array4) - 1];
        self::assertMatchesRegularExpression('/^attachment.*/', $fname4);
    }

    // case 5: same attachment name submitted 2 times same day for same list
    public function testGetPathWithSameFileName(): void
    {
        $file_storage       = $this->createPartialMock(ForumML_FileStorage::class, ['fileExists']);
        $file_storage->root = $this->path;

        $list = "gpig-interest";
        $date = "2007_10_24";
        $type = "store";
        $name = 'Screenshot.jpg';

        $file_storage->method('fileExists')->willReturnOnConsecutiveCalls(false, true, false);
        // First file stored that day
        $path1 = $file_storage->_getPath($name, $list, $date, $type);

        // Second file with same name
        $path2 = $file_storage->_getPath($name, $list, $date, $type);

        self::assertNotEquals($path1, $path2);
    }
}
