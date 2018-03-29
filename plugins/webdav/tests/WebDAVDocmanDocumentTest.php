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

require_once 'bootstrap.php';

Mock::generate('BaseLanguage');
Mock::generate('WebDAVUtils');
Mock::generate('Docman_Item');
Mock::generate('Docman_ItemFactory');
Mock::generate('Docman_PermissionsManager');
Mock::generatePartial(
    'WebDAVDocmanDocument',
    'WebDAVDocmanDocumentTestVersion',
array('getItem', 'getUtils', 'getProject')
);
Mock::generate('EventManager');
Mock::generate('Project');

/**
 * This is the unit test of WebDAVDocmanDocument
 */
class WebDAVDocmanDocumentTest extends TuleapTestCase {

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
        

        $webDAVDocmanDocument->setName('newName');
    }
}
