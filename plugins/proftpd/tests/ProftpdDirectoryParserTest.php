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

require_once 'bootstrap.php';

class Proftpd_Directory_DirectoryParserTest extends TuleapTestCase {

    public function setUp() {
        parent::setUp();

        $this->expected_item_01 = new Proftpd_Directory_DirectoryItem(
            '.',
            filetype(realpath(dirname(__FILE__).'/_fixtures/sftp_directory/.')),
            filesize(realpath(dirname(__FILE__).'/_fixtures/sftp_directory/.')),
            filemtime(realpath(dirname(__FILE__).'/_fixtures/sftp_directory/.'))
        );

        $this->expected_item_02 = new Proftpd_Directory_DirectoryItem(
            '..',
            filetype(realpath(dirname(__FILE__).'/_fixtures/sftp_directory/..')),
            filesize(realpath(dirname(__FILE__).'/_fixtures/sftp_directory/..')),
            filemtime(realpath(dirname(__FILE__).'/_fixtures/sftp_directory/..'))
        );

        $this->expected_item_03 = new Proftpd_Directory_DirectoryItem(
            'file01.txt',
            filetype(realpath(dirname(__FILE__).'/_fixtures/sftp_directory/file01.txt')),
            filesize(realpath(dirname(__FILE__).'/_fixtures/sftp_directory/file01.txt')),
            filemtime(realpath(dirname(__FILE__).'/_fixtures/sftp_directory/file01.txt'))
        );

        $this->expected_item_04 = new Proftpd_Directory_DirectoryItem(
            'folder01',
            filetype(realpath(dirname(__FILE__).'/_fixtures/sftp_directory/folder01')),
            filesize(realpath(dirname(__FILE__).'/_fixtures/sftp_directory/folder01')),
            filemtime(realpath(dirname(__FILE__).'/_fixtures/sftp_directory/folder01'))
        );

        $this->expected_item_05 = new Proftpd_Directory_DirectoryItem(
            'folder9',
            filetype(realpath(dirname(__FILE__).'/_fixtures/sftp_directory/folder9')),
            filesize(realpath(dirname(__FILE__).'/_fixtures/sftp_directory/folder9')),
            filemtime(realpath(dirname(__FILE__).'/_fixtures/sftp_directory/folder9'))
        );

        $this->expected_item_06 = new Proftpd_Directory_DirectoryItem(
            'folder10',
            filetype(realpath(dirname(__FILE__).'/_fixtures/sftp_directory/folder10')),
            filesize(realpath(dirname(__FILE__).'/_fixtures/sftp_directory/folder10')),
            filemtime(realpath(dirname(__FILE__).'/_fixtures/sftp_directory/folder10'))
        );

        $this->parser = new Proftpd_Directory_DirectoryParser();
    }

    public function itReturnsContentOfDirectoryInformation() {
        $path   = realpath(dirname(__FILE__).'/_fixtures/sftp_directory');
        $items  = $this->parser->parseDirectory($path);

        $folders = $items->getFolders();
        $files   = $items->getFiles();

        $this->assertCount($folders, 3);
        $this->assertCount($files, 1);

        $this->assertIsA($folders[0], 'Proftpd_Directory_DirectoryItem');
        $this->assertIsA($folders[1], 'Proftpd_Directory_DirectoryItem');
        $this->assertIsA($folders[2], 'Proftpd_Directory_DirectoryItem');
        $this->assertIsA($files[0], 'Proftpd_Directory_DirectoryItem');
    }

    public function itReturnsContentOfDirectoryInformationIfPathEndsBySlash() {
        $path   = realpath(dirname(__FILE__).'/_fixtures/sftp_directory/');
        $items  = $this->parser->parseDirectory($path);

        $folders = $items->getFolders();
        $files   = $items->getFiles();

        $this->assertCount($folders, 3);
        $this->assertCount($files, 1);

        $this->assertIsA($folders[0], 'Proftpd_Directory_DirectoryItem');
        $this->assertIsA($files[0], 'Proftpd_Directory_DirectoryItem');
    }

    public function itDoesNotReturnDotFolders() {
        $path   = realpath(dirname(__FILE__).'/_fixtures/sftp_directory');
        $items  = $this->parser->parseDirectory($path);

        foreach ($items->getFolders() as $folder) {
            $this->assertFalse($folder->getName() == '..');
            $this->assertFalse($folder->getName() == '.');
        }
    }

    public function itReturnsFilesAndFoldersInANaturalOrder() {
        $path   = realpath(dirname(__FILE__).'/_fixtures/sftp_directory');
        $items  = $this->parser->parseDirectory($path);

        $folders = $items->getFolders();

        $folder1 = $folders[0];
        $folder2 = $folders[1];
        $folder3 = $folders[2];

        $this->assertEqual('folder01', $folder1->getName());
        $this->assertEqual('folder9',  $folder2->getName());
        $this->assertEqual('folder10', $folder3->getName());
    }

    public function itReturnsContentOfSubDirectoryInformation() {
        $path   = realpath(dirname(__FILE__).'/_fixtures/sftp_directory/folder01');
        $items  = $this->parser->parseDirectory($path);

        $folders = $items->getFolders();
        $files   = $items->getFiles();

        $this->assertCount($folders, 0);
        $this->assertCount($files, 1);

        $this->assertIsA($files[0], 'Proftpd_Directory_DirectoryItem');
    }

}
?>
