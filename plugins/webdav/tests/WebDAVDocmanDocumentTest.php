<?php
/**
 * Copyright (c) STMicroelectronics 2011. All rights reserved
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

require_once (dirname(__FILE__).'/../../../src/common/language/BaseLanguage.class.php');
Mock::generate('BaseLanguage');
require_once ('requirements.php');
require_once (dirname(__FILE__).'/../include/WebDAVUtils.class.php');
Mock::generate('WebDAVUtils');
require_once (dirname(__FILE__).'/../../docman/include/Docman_Item.class.php');
Mock::generate('Docman_Item');
require_once (dirname(__FILE__).'/../../docman/include/Docman_ItemFactory.class.php');
Mock::generate('Docman_ItemFactory');
require_once (dirname(__FILE__).'/../../docman/include/Docman_PermissionsManager.class.php');
Mock::generate('Docman_PermissionsManager');
require_once (dirname(__FILE__).'/../include/FS/WebDAVDocmanDocument.class.php');
Mock::generatePartial(
    'WebDAVDocmanDocument',
    'WebDAVDocmanDocumentTestVersion',
array('getItem', 'getUtils', 'getProject')
);
Mock::generate('EventManager');

/**
 * This is the unit test of WebDAVDocmanDocument
 */
class WebDAVDocmanDocumentTest extends UnitTestCase {

    function setUp() {
        $GLOBALS['Language'] = new MockBaseLanguage($this);
    }

    function tearDown() {
        unset($GLOBALS['Language']);
    }

    function testDeleteNoWriteEnabled() {
        $webDAVDocmanDocument = new WebDAVDocmanDocumentTestVersion();
        $utils = new MockWebDAVUtils();
        $utils->setReturnValue('isWriteEnabled', false);
        $webDAVDocmanDocument->setReturnValue('getUtils', $utils);

        $this->expectException('Sabre_DAV_Exception_Forbidden');
        $webDAVDocmanDocument->delete();
    }

    function testDeleteSuccess() {
        $webDAVDocmanDocument = new WebDAVDocmanDocumentTestVersion();

        $utils = new MockWebDAVUtils();
        $utils->setReturnValue('isWriteEnabled', true);
        $utils->expectOnce('processDocmanRequest');
        $webDAVDocmanDocument->setReturnValue('getUtils', $utils);
        
        $project = new MockProject();
        $webDAVDocmanDocument->setReturnValue('getProject', $project);
        
        $item = new MockDocman_Item();
        $webDAVDocmanDocument->setReturnValue('getItem', $item);
        
        $this->assertNoErrors();
        $webDAVDocmanDocument->delete();
    }

    function testSetNameNoWriteEnabled() {
        $webDAVDocmanDocument = new WebDAVDocmanDocumentTestVersion();
        $utils = new MockWebDAVUtils();
        $utils->setReturnValue('isWriteEnabled', false);
        $webDAVDocmanDocument->setReturnValue('getUtils', $utils);

        $this->expectException('Sabre_DAV_Exception_MethodNotAllowed');
        $webDAVDocmanDocument->setName('newName');
    }

    function testSetNameSuccess() {
        $webDAVDocmanDocument = new WebDAVDocmanDocumentTestVersion();

        $utils = new MockWebDAVUtils();
        $utils->setReturnValue('isWriteEnabled', true);
        $utils->expectOnce('processDocmanRequest');
        $webDAVDocmanDocument->setReturnValue('getUtils', $utils);
        
        $project = new MockProject();
        $webDAVDocmanDocument->setReturnValue('getProject', $project);
        
        $item = new MockDocman_Item();
        $webDAVDocmanDocument->setReturnValue('getItem', $item);
        

        $this->assertNoErrors();
        $webDAVDocmanDocument->setName('newName');
    }

}

?>