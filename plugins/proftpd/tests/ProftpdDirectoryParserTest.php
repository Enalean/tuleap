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

class ProftpdDirectoryParserTest extends TuleapTestCase {

    public function setUp() {
        parent::setUp();

        $this->expected_item_01 = new ProftpdDirectoryItem(
            '.',
            filetype(dirname(__FILE__).'/_fixtures/sftp_directory/.'),
            filesize(dirname(__FILE__).'/_fixtures/sftp_directory/.'),
            filemtime(dirname(__FILE__).'/_fixtures/sftp_directory/.')
        );

        $this->expected_item_02 = new ProftpdDirectoryItem(
            '..',
            filetype(dirname(__FILE__).'/_fixtures/sftp_directory/..'),
            filesize(dirname(__FILE__).'/_fixtures/sftp_directory/..'),
            filemtime(dirname(__FILE__).'/_fixtures/sftp_directory/..')
        );

        $this->expected_item_03 = new ProftpdDirectoryItem(
            'file01.txt',
            filetype(dirname(__FILE__).'/_fixtures/sftp_directory/file01.txt'),
            filesize(dirname(__FILE__).'/_fixtures/sftp_directory/file01.txt'),
            filemtime(dirname(__FILE__).'/_fixtures/sftp_directory/file01.txt')
        );

        $this->expected_item_04 = new ProftpdDirectoryItem(
            'folder01',
            filetype(dirname(__FILE__).'/_fixtures/sftp_directory/folder01'),
            filesize(dirname(__FILE__).'/_fixtures/sftp_directory/folder01'),
            filemtime(dirname(__FILE__).'/_fixtures/sftp_directory/folder01')
        );

        $this->parser = new ProftpdDirectoryParser();
    }

    public function itReturnsContentOfDirectoryInformation() {
        $path   = dirname(__FILE__).'/_fixtures/sftp_directory';
        $items  = $this->parser->parseDirectory($path);

        $this->assertEqual($items[0], $this->expected_item_01);
        $this->assertEqual($items[1], $this->expected_item_02);
        $this->assertEqual($items[2], $this->expected_item_03);
        $this->assertEqual($items[3], $this->expected_item_04);
    }

    public function itReturnsContentOfDirectoryInformationIfPathEndsBySlash() {
        $path   = dirname(__FILE__).'/_fixtures/sftp_directory/';
        $items  = $this->parser->parseDirectory($path);

        $this->assertEqual($items[0], $this->expected_item_01);
        $this->assertEqual($items[1], $this->expected_item_02);
        $this->assertEqual($items[2], $this->expected_item_03);
        $this->assertEqual($items[3], $this->expected_item_04);
    }

}
?>
