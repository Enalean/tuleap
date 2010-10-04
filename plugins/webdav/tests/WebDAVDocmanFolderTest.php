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
/**
 * This is the unit test of WebDAVDocmanFolder
 */

require_once (dirname(__FILE__).'/../../../src/common/language/BaseLanguage.class.php');
Mock::generate('BaseLanguage');
require_once (dirname(__FILE__).'/../include/lib/Sabre/DAV/Exception.php');
require_once (dirname(__FILE__).'/../include/lib/Sabre/DAV/Exception/Conflict.php');
require_once (dirname(__FILE__).'/../include/lib/Sabre/DAV/Exception/FileNotFound.php');
require_once (dirname(__FILE__).'/../include/lib/Sabre/DAV/INode.php');
require_once (dirname(__FILE__).'/../include/lib/Sabre/DAV/Node.php');
require_once (dirname(__FILE__).'/../include/lib/Sabre/DAV/ICollection.php');
require_once (dirname(__FILE__).'/../include/lib/Sabre/DAV/IDirectory.php');
require_once (dirname(__FILE__).'/../include/lib/Sabre/DAV/Directory.php');
require_once (dirname(__FILE__).'/../../docman/include/Docman_Item.class.php');
Mock::generate('Docman_Item');
//require_once (dirname(__FILE__).'/../../docman/include/Docman_Folder.class.php');
//Mock::generate('Docman_Folder');
require_once (dirname(__FILE__).'/../../docman/include/Docman_ItemFactory.class.php');
Mock::generate('Docman_ItemFactory');
require_once (dirname(__FILE__).'/../../docman/include/Docman_PermissionsManager.class.php');
Mock::generate('Docman_PermissionsManager');
require_once (dirname(__FILE__).'/../include/FS/WebDAVDocmanFolder.class.php');
Mock::generatePartial(
    'WebDAVDocmanFolder',
    'WebDAVDocmanFolderTestVersion',
array('getDocmanItemFactory', 'getDocmanPermissionsManager')
);
Mock::generatePartial(
    'WebDAVDocmanFolder',
    'WebDAVDocmanFolderTestVersion2',
array('getDocmanItemFactory', 'getDocmanPermissionsManager', 'getWebDAVDocmanFolder')
);

class TestDocmanFolder extends Docman_Folder {

    function getTitle() {
        return 'SameName';
    }

}

class TestDocmanFolder2 extends Docman_Folder {

    function getTitle() {
        return 'AnotherName';
    }

}

class MockDocmanFolder extends Docman_Folder {
    function getID() {
        return 0;
    }

    function getTitle() {
        return 'someName';
    }
}

class WebDAVDocmanFolderTest extends UnitTestCase {

    /**
     * Constructor of the test. Can be ommitted.
     * Usefull to set the name of the test
     */
    function WebDAVDocmanFolderTest($name = 'WebDAVDocmanFolderTest') {
        $this->UnitTestCase($name);
    }

    function setUp() {
        $GLOBALS['Language'] = new MockBaseLanguage($this);
    }

    function tearDown() {
        unset($GLOBALS['Language']);
    }

    /**
     * Testing when The folder have no childrens
     */
    function testGetChildrenNoChildrens() {
        $webDAVDocmanFolder = new WebDAVDocmanFolderTestVersion($this);
        $dif = new MockDocman_ItemFactory();
        $dif->setReturnValue('getChildrenFromParent', array());
        $webDAVDocmanFolder->setReturnValue('getDocmanItemFactory', $dif);
        $this->assertEqual($webDAVDocmanFolder->getChildren(), array());
    }

    /**
     * Testing when The User can't access/read the child node
     */
    function testGetChildrenUserCanNotAccess() {
        $webDAVDocmanFolder = new WebDAVDocmanFolderTestVersion($this);
        $item = new MockDocman_Item();
        $dif = new MockDocman_ItemFactory();
        $dif->setReturnValue('getChildrenFromParent', array($item));
        $dpm = new MockDocman_PermissionsManager();
        $dpm->setReturnValue('userCanAccess', false);
        $webDAVDocmanFolder->setReturnValue('getDocmanItemFactory', $dif);
        $webDAVDocmanFolder->setReturnValue('getDocmanPermissionsManager', $dpm);
        $this->assertEqual($webDAVDocmanFolder->getChildren(), array());
    }

    /**
     * Testing when The folder contain a duplicate name
     */
    function testGetChildrenDuplicateName() {
        $webDAVDocmanFolder = new WebDAVDocmanFolderTestVersion($this);
        // TODO : make this an item after adding documents
        $item1 = new TestDocmanFolder();
        // TODO : make this an item after adding documents
        $item2 = new TestDocmanFolder();
        $dif = new MockDocman_ItemFactory();
        $dif->setReturnValue('getChildrenFromParent', array($item1, $item2));
        $dpm = new MockDocman_PermissionsManager();
        $dpm->setReturnValue('userCanAccess', true);
        $webDAVDocmanFolder->setReturnValue('getDocmanItemFactory', $dif);
        $webDAVDocmanFolder->setReturnValue('getDocmanPermissionsManager', $dpm);
        $this->assertEqual($webDAVDocmanFolder->getChildren(), array());
    }

    /**
     * Testing when The folder contain some items
     */
    function testGetChildrenSuccess() {
        $webDAVDocmanFolder = new WebDAVDocmanFolderTestVersion($this);
        // TODO : make this different items after adding documents
        $item1 = new TestDocmanFolder();
        $item2 = new TestDocmanFolder2();
        $dif = new MockDocman_ItemFactory();
        $dif->setReturnValue('getChildrenFromParent', array($item1, $item2));
        $dpm = new MockDocman_PermissionsManager();
        $dpm->setReturnValue('userCanAccess', true);
        $webDAVDocmanFolder->setReturnValue('getDocmanItemFactory', $dif);
        $webDAVDocmanFolder->setReturnValue('getDocmanPermissionsManager', $dpm);
        $this->assertNoErrors();
        $children = $webDAVDocmanFolder->getChildren();
        $this->assertTrue(isset($children['SameName']));
        $this->assertTrue(isset($children['AnotherName']));
        $this->assertEqual(sizeof($children), 2);
    }

    /**
     * Testing when The folder have no childrens
     */
    function testGetChildNotFound() {
        $webDAVDocmanFolder = new WebDAVDocmanFolderTestVersion($this);
        $dif = new MockDocman_ItemFactory();
        $dif->setReturnValue('getChildrenFromParent', array());
        $webDAVDocmanFolder->setReturnValue('getDocmanItemFactory', $dif);
        $this->expectException('Sabre_DAV_Exception_FileNotFound');
        $webDAVDocmanFolder->getChild('whatever');
    }

    /**
     * Testing when The folder have childrens
     */
    function testGetChildSuccess() {
        $webDAVDocmanFolder = new WebDAVDocmanFolderTestVersion2($this);
        // TODO : make this an item after adding documents
        $item = new MockDocmanFolder();
        $dif = new MockDocman_ItemFactory();
        $dif->setReturnValue('getChildrenFromParent', array($item));
        $dpm = new MockDocman_PermissionsManager();
        $dpm->setReturnValue('userCanAccess', true);
        $webDAVDocmanFolder->setReturnValue('getDocmanItemFactory', $dif);
        $webDAVDocmanFolder->setReturnValue('getDocmanPermissionsManager', $dpm);
        $webDAVDocmanFolder->setReturnValue('getWebDAVDocmanFolder', $item);
        $this->assertNoErrors();
        $this->assertEqual($webDAVDocmanFolder->getChild('someName'), $item);
    }

}

?>