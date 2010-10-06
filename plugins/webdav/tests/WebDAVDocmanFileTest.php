<?php
/**
 * Copyright (c) STMicroelectronics, 2010. All Rights Reserved.
 *
 * This file is a part of Codendi.
 *
 * Codendi is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Codendi is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Codendi. If not, see <http://www.gnu.org/licenses/>.
 */

require_once (dirname(__FILE__).'/../../../src/common/language/BaseLanguage.class.php');
Mock::generate('BaseLanguage');
require_once (dirname(__FILE__).'/../include/lib/Sabre/DAV/Exception.php');
require_once (dirname(__FILE__).'/../include/lib/Sabre/DAV/Exception/RequestedRangeNotSatisfiable.php');
require_once (dirname(__FILE__).'/../include/lib/Sabre/DAV/Exception/FileNotFound.php');
require_once (dirname(__FILE__).'/../include/lib/Sabre/DAV/INode.php');
require_once (dirname(__FILE__).'/../include/lib/Sabre/DAV/Node.php');
require_once (dirname(__FILE__).'/../include/lib/Sabre/DAV/IFile.php');
require_once (dirname(__FILE__).'/../include/lib/Sabre/DAV/File.php');
require_once (dirname(__FILE__).'/../../docman/include/Docman_Version.class.php');
Mock::generate('Docman_Version');
require_once (dirname(__FILE__).'/../../docman/include/Docman_File.class.php');
Mock::generate('Docman_File');
require_once (dirname(__FILE__).'/../../docman/include/Docman_ItemFactory.class.php');
Mock::generate('Docman_ItemFactory');
require_once (dirname(__FILE__).'/../include/FS/WebDAVDocmanFile.class.php');
Mock::generatePartial(
    'WebDAVDocmanFile',
    'WebDAVDocmanFileTestVersion',
array('getItemFactory', 'getSize', 'getMaxFileSize', 'getDocument', 'logDownload', 'download')
);

/**
 * This is the unit test of WebDAVDocmanFile
 */
class WebDAVDocmanFileTest extends UnitTestCase {

    /**
     * Constructor of the test. Can be ommitted.
     * Usefull to set the name of the test
     */
    function WebDAVDocmanFileTest($name = 'WebDAVDocmanFileTest') {
        $this->UnitTestCase($name);
    }

    function setUp() {
        $GLOBALS['Language'] = new MockBaseLanguage($this);
    }

    function tearDown() {
        unset($GLOBALS['Language']);
    }

    /**
     * Test when the file doesn't exist on the filesystem
     */
    function testGetNotFound() {
        $webDAVDocmanFile = new WebDAVDocmanFileTestVersion($this);
        $version = new MockDocman_Version();
        $version->setReturnValue('getPath', dirname(__FILE__).'/_fixtures/nonExistant');
        $item = new MockDocman_File();
        $item->setReturnValue('getCurrentVersion', $version);
        $dif = new MockDocman_ItemFactory();
        $dif->setReturnValue('getItemFromDb', $item);
        $webDAVDocmanFile->setReturnValue('getItemFactory', $dif);
        $webDAVDocmanFile->setReturnValue('getDocument', $item);
        $this->expectException('Sabre_DAV_Exception_FileNotFound');
        $webDAVDocmanFile->get();
    }

    /**
     * Test when the file is too big
     */
    function testGetBigFile() {
        $webDAVDocmanFile = new WebDAVDocmanFileTestVersion($this);
        $webDAVDocmanFile->setReturnValue('getSize', 2);
        $webDAVDocmanFile->setReturnValue('getMaxFileSize', 1);
        $version = new MockDocman_Version();
        $version->setReturnValue('getPath', dirname(__FILE__).'/_fixtures/test.txt');
        $item = new MockDocman_File();
        $item->setReturnValue('getId', 0);
        $item->setReturnValue('getCurrentVersion', $version);
        $dif = new MockDocman_ItemFactory();
        $dif->setReturnValue('getItemFromDb', $item);
        $webDAVDocmanFile->setReturnValue('getItemFactory', $dif);
        $webDAVDocmanFile->setReturnValue('getDocument', $item);
        $this->expectException('Sabre_DAV_Exception_RequestedRangeNotSatisfiable');
        $webDAVDocmanFile->get();
    }

    /**
     * Test when the file download succeede
     */
    function testGetSuccess() {
        $webDAVDocmanFile = new WebDAVDocmanFileTestVersion($this);
        $webDAVDocmanFile->setReturnValue('getSize', 1);
        $webDAVDocmanFile->setReturnValue('getMaxFileSize', 1);
        $version = new MockDocman_Version();
        $version->setReturnValue('getPath', dirname(__FILE__).'/_fixtures/test.txt');
        $item = new MockDocman_File();
        $item->setReturnValue('getId', 0);
        $item->setReturnValue('getCurrentVersion', $version);
        $dif = new MockDocman_ItemFactory();
        $dif->setReturnValue('getItemFromDb', $item);
        $webDAVDocmanFile->setReturnValue('getItemFactory', $dif);
        $webDAVDocmanFile->setReturnValue('getDocument', $item);
        $this->assertNoErrors();
        $webDAVDocmanFile->get();
    }

}

?>