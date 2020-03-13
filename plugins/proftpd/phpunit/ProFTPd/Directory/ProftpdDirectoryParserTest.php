<?php
/**
 * Copyright (c) Enalean, 2014. All Rights Reserved.
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

namespace Tuleap\ProFTPd\Directory;

require_once __DIR__ . '/../../bootstrap.php';

class DirectoryParserTest extends \PHPUnit\Framework\TestCase
{

    public function setUp() : void
    {
        parent::setUp();

        $this->expected_item_01 = new DirectoryItem(
            '.',
            filetype(realpath(dirname(__FILE__) . '/_fixtures/sftp_directory/.')),
            filesize(realpath(dirname(__FILE__) . '/_fixtures/sftp_directory/.')),
            filemtime(realpath(dirname(__FILE__) . '/_fixtures/sftp_directory/.'))
        );

        $this->expected_item_02 = new DirectoryItem(
            '..',
            filetype(realpath(dirname(__FILE__) . '/_fixtures/sftp_directory/..')),
            filesize(realpath(dirname(__FILE__) . '/_fixtures/sftp_directory/..')),
            filemtime(realpath(dirname(__FILE__) . '/_fixtures/sftp_directory/..'))
        );

        $this->expected_item_03 = new DirectoryItem(
            'file01.txt',
            filetype(realpath(dirname(__FILE__) . '/_fixtures/sftp_directory/file01.txt')),
            filesize(realpath(dirname(__FILE__) . '/_fixtures/sftp_directory/file01.txt')),
            filemtime(realpath(dirname(__FILE__) . '/_fixtures/sftp_directory/file01.txt'))
        );

        $this->expected_item_04 = new DirectoryItem(
            'folder01',
            filetype(realpath(dirname(__FILE__) . '/_fixtures/sftp_directory/folder01')),
            filesize(realpath(dirname(__FILE__) . '/_fixtures/sftp_directory/folder01')),
            filemtime(realpath(dirname(__FILE__) . '/_fixtures/sftp_directory/folder01'))
        );

        $this->expected_item_05 = new DirectoryItem(
            'folder9',
            filetype(realpath(dirname(__FILE__) . '/_fixtures/sftp_directory/folder9')),
            filesize(realpath(dirname(__FILE__) . '/_fixtures/sftp_directory/folder9')),
            filemtime(realpath(dirname(__FILE__) . '/_fixtures/sftp_directory/folder9'))
        );

        $this->expected_item_06 = new DirectoryItem(
            'folder10',
            filetype(realpath(dirname(__FILE__) . '/_fixtures/sftp_directory/folder10')),
            filesize(realpath(dirname(__FILE__) . '/_fixtures/sftp_directory/folder10')),
            filemtime(realpath(dirname(__FILE__) . '/_fixtures/sftp_directory/folder10'))
        );

        $this->parser = new DirectoryParser(realpath(dirname(__FILE__) . '/_fixtures'));
    }

    public function testItReturnsContentOfDirectoryInformation()
    {
        $path   = 'sftp_directory';
        $items  = $this->parser->parseDirectory($path, false);

        $folders = $items->getFolders();
        $files   = $items->getFiles();

        $this->assertCount(4, $folders);
        $this->assertCount(1, $files);

        $this->assertInstanceOf('\Tuleap\ProFTPd\Directory\DirectoryItem', $folders[0]);
        $this->assertInstanceOf('\Tuleap\ProFTPd\Directory\DirectoryItem', $folders[1]);
        $this->assertInstanceOf('\Tuleap\ProFTPd\Directory\DirectoryItem', $folders[2]);
        $this->assertInstanceOf('\Tuleap\ProFTPd\Directory\DirectoryItem', $files[0]);
    }

    public function testItReturnsContentOfDirectoryInformationIfPathEndsBySlash()
    {
        $path   = 'sftp_directory';
        $items  = $this->parser->parseDirectory($path, false);

        $folders = $items->getFolders();
        $files   = $items->getFiles();

        $this->assertCount(4, $folders);
        $this->assertCount(1, $files);

        $this->assertInstanceOf('\Tuleap\ProFTPd\Directory\DirectoryItem', $folders[0]);
        $this->assertInstanceOf('\Tuleap\ProFTPd\Directory\DirectoryItem', $files[0]);
    }

    public function testItDoesNotReturnDotFoldersWhenAskedNotTo()
    {
        $path   = 'sftp_directory';
        $items  = $this->parser->parseDirectory($path, true);

        foreach ($items->getFolders() as $folder) {
            $this->assertFalse($folder->getName() == '..');
            $this->assertFalse($folder->getName() == '.');
        }
    }

    public function testItDoesReturnDotDotFolder()
    {
        $path   = 'sftp_directory';
        $items  = $this->parser->parseDirectory($path, false);

        $dotdot_exists = false;
        foreach ($items->getFolders() as $folder) {
            if ($folder->getName() == '..') {
                $dotdot_exists = true;
            }

            $this->assertFalse($folder->getName() == '.');
        }

        $this->assertTrue($dotdot_exists);
    }

    public function testItReturnsFilesAndFoldersInANaturalOrder()
    {
        $path   = 'sftp_directory';
        $items  = $this->parser->parseDirectory($path, false);

        $folders = $items->getFolders();

        $folder0 = $folders[0];
        $folder1 = $folders[1];
        $folder2 = $folders[2];
        $folder3 = $folders[3];

        $this->assertEquals('..', $folder0->getName());
        $this->assertEquals('folder01', $folder1->getName());
        $this->assertEquals('folder9', $folder2->getName());
        $this->assertEquals('folder10', $folder3->getName());
    }

    public function testItReturnsContentOfSubDirectoryInformation()
    {
        $path   = 'sftp_directory/folder01';
        $items  = $this->parser->parseDirectory($path, false);

        $folders = $items->getFolders();
        $files   = $items->getFiles();

        $this->assertCount(1, $folders);
        $this->assertCount(1, $files);

        $this->assertInstanceOf('\Tuleap\ProFTPd\Directory\DirectoryItem', $files[0]);
    }
}
