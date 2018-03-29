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

require_once 'bootstrap.php';

Mock::generate('BaseLanguage');
Mock::generate('PFUser');
Mock::generate('Project');
Mock::generate('WebDAVUtils');
Mock::generate('Docman_Version');
Mock::generate('Docman_VersionFactory');
Mock::generate('Docman_File');
Mock::generate('Docman_Item');
Mock::generate('Docman_ItemFactory');
Mock::generate('Docman_PermissionsManager');
Mock::generate('Docman_FileStorage');
Mock::generatePartial(
    'WebDAVDocmanFile',
    'WebDAVDocmanFileTestVersion',
array('getSize', 'getMaxFileSize', 'getItem', 'getUser', 'logDownload', 'download', 'getUtils', 'getProject')
);

/**
 * This is the unit test of WebDAVDocmanFile
 */
class WebDAVDocmanFileTest extends TuleapTestCase {

    /**
     * Test when the file doesn't exist on the filesystem
     */
    function testGetNotFound() {
        $webDAVDocmanFile = new WebDAVDocmanFileTestVersion($this);

        $version = new MockDocman_Version();
        $version->setReturnValue('getPath', dirname(__FILE__).'/_fixtures/nonExistant');
        $item = new MockDocman_File();
        $item->setReturnValue('getCurrentVersion', $version);
        $webDAVDocmanFile->setReturnValue('getItem', $item);

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
        $item->setReturnValue('getCurrentVersion', $version);
        $webDAVDocmanFile->setReturnValue('getItem', $item);

        $this->expectException('Sabre_DAV_Exception_RequestedRangeNotSatisfiable');
        $webDAVDocmanFile->get();
    }

    /**
     * Test when the file download succeede
     */
    function testGetSucceede() {
        $webDAVDocmanFile = new WebDAVDocmanFileTestVersion($this);

        $webDAVDocmanFile->setReturnValue('getSize', 1);
        $webDAVDocmanFile->setReturnValue('getMaxFileSize', 1);

        $version = new MockDocman_Version();
        $version->setReturnValue('getPath', dirname(__FILE__).'/_fixtures/test.txt');
        $item = new MockDocman_File();
        $item->setReturnValue('getCurrentVersion', $version);
        $webDAVDocmanFile->setReturnValue('getItem', $item);

        $webDAVDocmanFile->get();
    }

    function testPutNoWriteEnabled() {
        $webDAVDocmanFile = new WebDAVDocmanFileTestVersion();
        $utils = new MockWebDAVUtils();
        $utils->setReturnValue('isWriteEnabled', false);
        $webDAVDocmanFile->setReturnValue('getUtils', $utils);

        $this->expectException('Sabre_DAV_Exception_Forbidden');
        $data = fopen(dirname(__FILE__).'/_fixtures/test.txt', 'r');
        $webDAVDocmanFile->put($data);
    }

    function testPutBigFile() {
        $webDAVDocmanFile = new WebDAVDocmanFileTestVersion();
        
        $utils = new MockWebDAVUtils();
        $utils->setReturnValue('isWriteEnabled', true);
        $utils->expectNever('processDocmanRequest');
        $webDAVDocmanFile->setReturnValue('getUtils', $utils);
        
        $project = new MockProject();
        $webDAVDocmanFile->setReturnValue('getProject', $project);
        
        $item = new MockDocman_Item();
        $webDAVDocmanFile->setReturnValue('getItem', $item);
        
        $webDAVDocmanFile->setReturnValue('getMaxFileSize', 20);

        $this->expectException('Sabre_DAV_Exception_RequestedRangeNotSatisfiable');
        $data = fopen(dirname(__FILE__).'/_fixtures/test.txt', 'r');
        $webDAVDocmanFile->put($data);
    }

    function testPutSucceed() {
        $webDAVDocmanFile = new WebDAVDocmanFileTestVersion();
        
        $utils = new MockWebDAVUtils();
        $utils->setReturnValue('isWriteEnabled', true);
        $utils->expectOnce('processDocmanRequest');
        $webDAVDocmanFile->setReturnValue('getUtils', $utils);
        
        $project = new MockProject();
        $webDAVDocmanFile->setReturnValue('getProject', $project);
        
        $item = new MockDocman_Item();
        $webDAVDocmanFile->setReturnValue('getItem', $item);
        
        $webDAVDocmanFile->setReturnValue('getMaxFileSize', 4096);

        $data = fopen(dirname(__FILE__).'/_fixtures/test.txt', 'r');
        $webDAVDocmanFile->put($data);
    }

    function testSetNameFile() {
        $webDAVDocmanFile = new WebDAVDocmanFileTestVersion();
        $item = new Docman_File();
        $webDAVDocmanFile->setReturnValue('getItem', $item);
        $this->expectException('Sabre_DAV_Exception_MethodNotAllowed');
        $webDAVDocmanFile->setName('newName');
    }

    function testSetNameEmbeddedFile() {
        $webDAVDocmanFile = new WebDAVDocmanDocumentTestVersion();

        $utils = new MockWebDAVUtils();
        $utils->setReturnValue('isWriteEnabled', true);
        $utils->expectOnce('processDocmanRequest');
        $webDAVDocmanFile->setReturnValue('getUtils', $utils);
        
        $project = new MockProject();
        $webDAVDocmanFile->setReturnValue('getProject', $project);
        
        $item = new MockDocman_Item();
        $webDAVDocmanFile->setReturnValue('getItem', $item);

        $webDAVDocmanFile->setName('newName');
    }
}
