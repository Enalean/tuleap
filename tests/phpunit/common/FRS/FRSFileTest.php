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

class FRSFileTest extends \PHPUnit\Framework\TestCase  // phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace
{

    public function testGetContentWholeFile()
    {
        $file = new FRSFile();
        $file->file_location = dirname(__FILE__) . '/_fixtures/file_sample';
        $file->file_size     = filesize(dirname(__FILE__) . '/_fixtures/file_sample');

        $this->assertSame(file_get_contents(dirname(__FILE__) . '/_fixtures/file_sample'), $file->getContent());
    }

    public function testGetContentWithStartOffset()
    {
        $file = new FRSFile();
        $file->file_location = dirname(__FILE__) . '/_fixtures/file_sample';

        $this->assertSame('"The quick', $file->getContent(0, 10));
    }

    public function testGetContentWithOffsetAndSize()
    {
        $file = new FRSFile();
        $file->file_location = dirname(__FILE__) . '/_fixtures/file_sample';

        $this->assertSame(' brown fox', $file->getContent(10, 10));
    }

    public function testGetContentWithOffsetAndEof()
    {
        $file = new FRSFile();
        $file->file_location = dirname(__FILE__) . '/_fixtures/file_sample';

        $this->assertSame("arts.\n", $file->getContent(380, 10));
    }

    public function testGetContentWholeByOffset()
    {
        $file = new FRSFile();
        $file->file_location = dirname(__FILE__) . '/_fixtures/file_sample';

        $content  = $file->getContent(0, 100);
        $content .= $file->getContent(100, 100);
        $content .= $file->getContent(200, 100);
        $content .= $file->getContent(300, 100);
        $this->assertSame(file_get_contents(dirname(__FILE__) . '/_fixtures/file_sample'), $content);
    }

    public function testGetfilePath()
    {
        $file = new FRSFile();
        $filepath = 'path';
        $file->setFilePath($filepath);
        $filename = 'name';
        $file->setFileName($filename);
        $this->assertEquals($filepath, $file->getFilePath());

        $file->setFilePath(null);
        $this->assertEquals($filename, $file->getFilePath());
    }
}
