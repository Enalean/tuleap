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
require_once (dirname(__FILE__).'/../include/WebDAVUtils.class.php');
require_once (dirname(__FILE__).'/../include/lib/Sabre/DAV/Exception.php');
require_once (dirname(__FILE__).'/../include/lib/Sabre/DAV/Exception/Forbidden.php');
require_once (dirname(__FILE__).'/../include/lib/Sabre/DAV/Exception/Conflict.php');
require_once (dirname(__FILE__).'/../include/lib/Sabre/DAV/Exception/FileNotFound.php');
require_once (dirname(__FILE__).'/../include/lib/Sabre/DAV/INode.php');
require_once (dirname(__FILE__).'/../include/lib/Sabre/DAV/Node.php');
require_once (dirname(__FILE__).'/../include/lib/Sabre/DAV/ICollection.php');
require_once (dirname(__FILE__).'/../include/lib/Sabre/DAV/IDirectory.php');
require_once (dirname(__FILE__).'/../include/lib/Sabre/DAV/Directory.php');
require_once (dirname(__FILE__).'/../include/lib/Sabre/DAV/IFile.php');
require_once (dirname(__FILE__).'/../include/lib/Sabre/DAV/File.php');
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
array('getChildList')
);
Mock::generatePartial(
    'WebDAVDocmanFolder',
    'WebDAVDocmanFolderTestVersion3',
array('getItem', 'getDocmanItemFactory', 'getDocmanPermissionsManager', 'getWebDAVDocmanFolder')
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

/**
 * This is the unit test of WebDAVDocmanFolder
 */
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
     * Testing when the folder have no childrens
     */
    function testGetChildListNoChildrens() {
        $webDAVDocmanFolder = new WebDAVDocmanFolderTestVersion($this);
        $dif = new MockDocman_ItemFactory();
        $dif->setReturnValue('getAllChildrenFromParent', array());
        $webDAVDocmanFolder->setReturnValue('getDocmanItemFactory', $dif);
        $this->assertEqual($webDAVDocmanFolder->getChildList(), array());
    }

    /**
     * Testing when the User can't access/read the child node
     */
    function testGetChildListUserCanNotAccess() {
        $webDAVDocmanFolder = new WebDAVDocmanFolderTestVersion($this);
        $item = new MockDocman_Item();
        $dif = new MockDocman_ItemFactory();
        $dif->setReturnValue('getAllChildrenFromParent', array($item));
        $dpm = new MockDocman_PermissionsManager();
        $dpm->setReturnValue('userCanAccess', false);
        $webDAVDocmanFolder->setReturnValue('getDocmanItemFactory', $dif);
        $webDAVDocmanFolder->setReturnValue('getDocmanPermissionsManager', $dpm);
        $this->assertEqual($webDAVDocmanFolder->getChildList(), array());
    }

    /**
     * Testing when the folder contain a duplicate name
     */
    function testGetChildListDuplicateName() {
        $webDAVDocmanFolder = new WebDAVDocmanFolderTestVersion($this);
        $item1 = new TestDocmanFolder();
        $item2 = new TestDocmanFolder();
        $dif = new MockDocman_ItemFactory();
        $dif->setReturnValue('getAllChildrenFromParent', array($item1, $item2));
        $dpm = new MockDocman_PermissionsManager();
        $dpm->setReturnValue('userCanAccess', true);
        $webDAVDocmanFolder->setReturnValue('getDocmanItemFactory', $dif);
        $webDAVDocmanFolder->setReturnValue('getDocmanPermissionsManager', $dpm);
        $children = $webDAVDocmanFolder->getChildList();
        $this->assertTrue(isset($children['SameName']));
        $this->assertEqual(sizeof($children), 1);
    }

    /**
     * Testing when the folder contain some items
     */
    function testGetChildListFilled() {
        $webDAVDocmanFolder = new WebDAVDocmanFolderTestVersion($this);
        $item1 = new TestDocmanFolder();
        $item2 = new TestDocmanFolder2();
        $dif = new MockDocman_ItemFactory();
        $dif->setReturnValue('getAllChildrenFromParent', array($item1, $item2));
        $dpm = new MockDocman_PermissionsManager();
        $dpm->setReturnValue('userCanAccess', true);
        $webDAVDocmanFolder->setReturnValue('getDocmanItemFactory', $dif);
        $webDAVDocmanFolder->setReturnValue('getDocmanPermissionsManager', $dpm);
        $this->assertNoErrors();
        $children = $webDAVDocmanFolder->getChildList();
        $this->assertTrue(isset($children['SameName']));
        $this->assertTrue(isset($children['AnotherName']));
        $this->assertEqual(sizeof($children), 2);
    }

    /**
     * Testing when the folder have no childrens
     */
    function testGetChildrenNoChildrens() {
        $webDAVDocmanFolder = new WebDAVDocmanFolderTestVersion2($this);
        $webDAVDocmanFolder->setReturnValue('getChildList', array());
        $this->assertEqual($webDAVDocmanFolder->getChildren(), array());
    }

    /**
     * Testing when the folder contain a duplicate name
     */
    function testGetChildrenDuplicateName() {
        $webDAVDocmanFolder = new WebDAVDocmanFolderTestVersion2($this);
        $webDAVDocmanFolder->setReturnValue('getChildList', array('SomeName' => 'duplicate'));
        $this->assertEqual($webDAVDocmanFolder->getChildren(), array());
    }

    /**
     * Testing when the folder contain some items
     */
    function testGetChildrenFilled() {
        $webDAVDocmanFolder = new WebDAVDocmanFolderTestVersion($this);
        $item1 = new TestDocmanFolder();
        $item2 = new TestDocmanFolder2();
        $dif = new MockDocman_ItemFactory();
        $dif->setReturnValue('getAllChildrenFromParent', array($item1, $item2));
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
     * Testing when the folder have no childrens
     */
    function testGetChildNotFound() {
        $webDAVDocmanFolder = new WebDAVDocmanFolderTestVersion($this);
        $dif = new MockDocman_ItemFactory();
        $dif->setReturnValue('getAllChildrenFromParent', array());
        $webDAVDocmanFolder->setReturnValue('getDocmanItemFactory', $dif);
        $this->expectException('Sabre_DAV_Exception_FileNotFound');
        $webDAVDocmanFolder->getChild('whatever');
    }

    /**
     * Testing when the item is duplicated
     */
    function testGetChildDuplicated() {
        $webDAVDocmanFolder = new WebDAVDocmanFolderTestVersion3($this);
        $item1 = new TestDocmanFolder();
        $item2 = new TestDocmanFolder();
        $dif = new MockDocman_ItemFactory();
        $dif->setReturnValue('getAllChildrenFromParent', array($item1, $item2));
        $dpm = new MockDocman_PermissionsManager();
        $dpm->setReturnValue('userCanAccess', true);
        $webDAVDocmanFolder->setReturnValue('getDocmanItemFactory', $dif);
        $webDAVDocmanFolder->setReturnValue('getDocmanPermissionsManager', $dpm);
        $webDAVDocmanFolder->setReturnValue('getWebDAVDocmanFolder', $item1);
        $this->expectException('Sabre_DAV_Exception_Conflict');
        $webDAVDocmanFolder->getChild('SameName');
    }

    /**
     * Testing when the item is obsolete and user is not docman admin
     */
    function testGetChildObsoleteUserNotAdmin() {
        $webDAVDocmanFolder = new WebDAVDocmanFolderTestVersion3($this);
        $item = new MockDocman_Item();
        $item->setReturnValue('isObsolete', true);
        $item->setReturnValue('getTitle', 'SomeName');
        $folder = new WebDAVDocmanFolderTestVersion3($this);
        $folder->setReturnValue('getItem', $item);
        $dif = new MockDocman_ItemFactory();
        $dif->setReturnValue('getAllChildrenFromParent', array($item));
        $dpm = new MockDocman_PermissionsManager();
        $dpm->setReturnValue('userCanAccess', true);
        $dpm->setReturnValue('userCanAdmin', false);
        $webDAVDocmanFolder->setReturnValue('getDocmanItemFactory', $dif);
        $webDAVDocmanFolder->setReturnValue('getDocmanPermissionsManager', $dpm);
        $webDAVDocmanFolder->setReturnValue('getWebDAVDocmanFolder', $folder);
        $this->expectException('Sabre_DAV_Exception_Forbidden');
        $webDAVDocmanFolder->getChild('SomeName');
    }

    /**
     * Testing when the item is obsolete and user is docman admin
     */
    function testGetChildObsoleteUserIsAdmin() {
        $webDAVDocmanFolder = new WebDAVDocmanFolderTestVersion3($this);
        $item = new MockDocman_Item();
        $item->setReturnValue('isObsolete', true);
        $item->setReturnValue('getTitle', 'SomeName');
        $folder = new WebDAVDocmanFolderTestVersion3($this);
        $folder->setReturnValue('getItem', $item);
        $dif = new MockDocman_ItemFactory();
        $dif->setReturnValue('getAllChildrenFromParent', array($item));
        $dpm = new MockDocman_PermissionsManager();
        $dpm->setReturnValue('userCanAccess', true);
        $dpm->setReturnValue('userCanAdmin', true);
        $webDAVDocmanFolder->setReturnValue('getDocmanItemFactory', $dif);
        $webDAVDocmanFolder->setReturnValue('getDocmanPermissionsManager', $dpm);
        $webDAVDocmanFolder->setReturnValue('getWebDAVDocmanFolder', $folder);
        $this->assertNoErrors();
        $this->assertEqual($webDAVDocmanFolder->getChild('SomeName'), $folder);
    }

    /**
     * Testing when the folder have childrens
     */
    function testGetChildSuccess() {
        $webDAVDocmanFolder = new WebDAVDocmanFolderTestVersion3($this);
        $item = new MockDocman_Item();
        $item->setReturnValue('isObsolete', false);
        $item->setReturnValue('getTitle', 'SomeName');
        $folder = new WebDAVDocmanFolderTestVersion3($this);
        $folder->setReturnValue('getItem', $item);
        $dif = new MockDocman_ItemFactory();
        $dif->setReturnValue('getAllChildrenFromParent', array($item));
        $dpm = new MockDocman_PermissionsManager();
        $dpm->setReturnValue('userCanAccess', true);
        $webDAVDocmanFolder->setReturnValue('getDocmanItemFactory', $dif);
        $webDAVDocmanFolder->setReturnValue('getDocmanPermissionsManager', $dpm);
        $webDAVDocmanFolder->setReturnValue('getWebDAVDocmanFolder', $folder);
        $this->assertNoErrors();
        $this->assertEqual($webDAVDocmanFolder->getChild('SomeName'), $folder);
    }

}

?>