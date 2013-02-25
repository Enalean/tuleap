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
require_once (dirname(__FILE__).'/../../../src/common/project/Project.class.php');
Mock::generate('Project');
require_once(dirname(__FILE__).'/../../../src/common/include/Error.class.php');
require_once ('requirements.php');
require_once (dirname(__FILE__).'/../include/FS/WebDAVFRSFile.class.php');
Mock::generate('WebDAVFRSFile');
require_once (dirname(__FILE__).'/../include/FS/WebDAVFRSRelease.class.php');
Mock::generate('WebDAVFRSRelease');
require_once (dirname(__FILE__).'/../include/FS/WebDAVDocmanFolder.class.php');
Mock::generate('WebDAVDocmanFolder');
require_once (dirname(__FILE__).'/../include/FS/WebDAVDocmanDocument.class.php');
Mock::generate('WebDAVDocmanDocument');
require_once (dirname(__FILE__).'/../../docman/include/Docman_Item.class.php');
Mock::generate('Docman_Item');
require_once (dirname(__FILE__).'/../../docman/include/Docman_PermissionsManager.class.php');
Mock::generate('Docman_PermissionsManager');
require_once (dirname(__FILE__).'/../../docman/include/Docman_ItemFactory.class.php');
Mock::generate('Docman_ItemFactory');
require_once (dirname(__FILE__).'/../include/FS/WebDAVFRSRelease.class.php');
require_once (dirname(__FILE__).'/../include/FS/WebDAVFRSPackage.class.php');
require_once(dirname(__FILE__).'/../include/WebDAVTree.class.php');
require_once(dirname(__FILE__).'/../include/WebDAVUtils.class.php');
Mock::generate('WebDAVUtils');
Mock::generate('PFUser');

/**
 * This is the unit test of WebDAVTree
 */
class TestTree extends WebDAVTree {
    function __construct() {
        
    }

    function getNodeForPath($path) {
        return new MockWebDAVFRSFile();
    }
}
Mock::generatePartial('TestTree', 'TestTreeTestVersion', array('canBeMoved', 'getNodeForPath', 'getUtils'));

class TestFile extends WebDAVFRSFile {
    function __construct() {
        
    }

    function getProject() {
        $project = new MockProject();
        $project->setReturnValue('getGroupId', 1);
        return $project;
    }
}

class TestRelease extends WebDAVFRSRelease {
    function __construct() {
        
    }

    function getProject() {
        $project = new MockProject();
        $project->setReturnValue('getGroupId', 1);
        return $project;
    }
}

class TestRelease2 extends WebDAVFRSRelease {
    function __construct() {
        
    }

    function getProject() {
        $project = new MockProject();
        $project->setReturnValue('getGroupId', 2);
        return $project;
    }
}

class TestPackage extends WebDAVFRSPackage {
    function __construct() {
        
    }

    function getProject() {
        $project = new MockProject();
        $project->setReturnValue('getGroupId', 1);
        return $project;
    }
}

class TestFolder extends WebDAVDocmanFolder {
    function __construct() {
        
    }

    function setItem($item) {
        $this->item = $item;
    }

    function getItem() {
        return $this->item;
    }
}

class TestDocmanFile extends WebDAVDocmanFile {
    function __construct() {
        
    }

    function setItem($item) {
        $this->item = $item;
    }

    function getItem() {
        return $this->item;
    }
}

class WebDAVTreeTest extends UnitTestCase {


    function setUp() {

        $GLOBALS['Language'] = new MockBaseLanguage($this);

    }

    function tearDown() {

        unset($GLOBALS['Language']);

    }

    function testCanBeMovedFailNotMovable() {
        $source = null;
        $destination = null;
        $tree = new TestTree();

        $this->assertEqual($tree->canBeMoved($source, $destination), false);
    }

    function testCanBeMovedFailSourceNotReleaseDestinationPackage() {
        $source = null;
        $destination = new TestPackage();
        $tree = new TestTree();

        $this->assertEqual($tree->canBeMoved($source, $destination), false);
    }

    function testCanBeMovedFailSourceNotFileDestinationRelease() {
        $source = null;
        $destination = new TestRelease();
        $tree = new TestTree();

        $this->assertEqual($tree->canBeMoved($source, $destination), false);
    }

    function testCanBeMovedFailSourceReleaseDestinationNotPackage() {
        $source = new TestRelease();
        $destination = null;
        $tree = new TestTree();

        $this->assertEqual($tree->canBeMoved($source, $destination), false);
    }

    function testCanBeMovedFailSourceFileDestinationNotRelease() {
        $source = new TestFile();
        $destination = null;
        $tree = new TestTree();

        $this->assertEqual($tree->canBeMoved($source, $destination), false);
    }

    function testCanBeMovedFailSourceReleaseDestinationPackageNotSameProject() {
        $source = new TestRelease2();
        $destination = new TestPackage();
        $tree = new TestTree();

        $this->assertEqual($tree->canBeMoved($source, $destination), false);
    }

    function testCanBeMovedFailSourceFileDestinationReleaseNotSameProject() {
        $source = new TestFile();
        $destination = new TestRelease2();
        $tree = new TestTree();

        $this->assertEqual($tree->canBeMoved($source, $destination), false);
    }

    function testCanBeMovedSucceedeSourceReleaseDestinationPackage() {
        $source = new TestRelease();
        $destination = new TestPackage();
        $tree = new TestTree();

        $this->assertEqual($tree->canBeMoved($source, $destination), true);
    }

    function testCanBeMovedSucceedeSourceFileDestinationRelease() {
        $source = new TestFile();
        $destination = new TestRelease();
        $tree = new TestTree();

        $this->assertEqual($tree->canBeMoved($source, $destination), true);
    }

    function testMoveOnlyRename() {
        $node = new MockWebDAVFRSRelease();
        $tree = new TestTreeTestVersion($this);
        $tree->setReturnValue('canBeMoved', true);
        $tree->setReturnValue('getNodeForPath', $node);
        $utils = new MockWebDAVUtils();
        $utils->setReturnValue('isWriteEnabled', true);
        $tree->setReturnValue('getUtils', $utils);

        $node->expectOnce('setName');
        $node->expectNever('move');
        
        $tree->move('project1/package1/release1', 'project1/package1/release2');
    }

    function testMoveCanNotMove() {
        $node = new MockWebDAVFRSRelease();
        $tree = new TestTreeTestVersion($this);
        $tree->setReturnValue('canBeMoved', false);
        $tree->setReturnValue('getNodeForPath', $node);
        $utils = new MockWebDAVUtils();
        $utils->setReturnValue('isWriteEnabled', true);
        $tree->setReturnValue('getUtils', $utils);

        $node->expectNever('setName');
        $node->expectNever('move');
        $this->expectException('Sabre_DAV_Exception_MethodNotAllowed');

        $tree->move('project1/package1/release1', 'project1/package2/release2');
    }

    function testMoveSucceed() {
        $node = new MockWebDAVFRSRelease();
        $tree = new TestTreeTestVersion($this);
        $tree->setReturnValue('canBeMoved', true);
        $tree->setReturnValue('getNodeForPath', $node);
        $utils = new MockWebDAVUtils();
        $utils->setReturnValue('isWriteEnabled', true);
        $tree->setReturnValue('getUtils', $utils);

        $node->expectNever('setName');
        //$node->expectOnce('move');
        $this->expectException('Sabre_DAV_Exception_MethodNotAllowed');

        $tree->move('project1/package1/release1', 'project1/package2/release2');
    }

    function testCopyNoWriteEnabled() {
        $tree = new TestTreeTestVersion();
        $utils = new MockWebDAVUtils();
        $utils->setReturnValue('isWriteEnabled', false);
        $tree->setReturnValue('getUtils', $utils);

        $this->expectException('Sabre_DAV_Exception_MethodNotAllowed');
        $tree->copy('source', 'destination/item');
    }

    /**
     * Fail when destination is not a docman folder
     */
    function testCopyWrongDestination() {
        $tree = new TestTreeTestVersion();
        $utils = new MockWebDAVUtils();
        $utils->setReturnValue('isWriteEnabled', true);
        $tree->setReturnValue('getUtils', $utils);
        $destination = new TestRelease();
        $tree->setReturnValue('getNodeForPath', $destination, array('destination'));
        $source = new TestFolder();
        $tree->setReturnValue('getNodeForPath', $source, array('destination'));

        $this->expectException('Sabre_DAV_Exception_MethodNotAllowed');
        $tree->copy('source', 'destination/item');
    }

    /**
     * Fail when source is not a docman folder
     */
    function testCopyWrongSource() {
        $tree = new TestTreeTestVersion();
        $utils = new MockWebDAVUtils();
        $utils->setReturnValue('isWriteEnabled', true);
        $tree->setReturnValue('getUtils', $utils);
        $destination = new TestFolder();
        $tree->setReturnValue('getNodeForPath', $destination, array('destination'));
        $source = new TestRelease();
        $tree->setReturnValue('getNodeForPath', $source, array('destination'));

        $this->expectException('Sabre_DAV_Exception_MethodNotAllowed');
        $tree->copy('source', 'destination/item');
    }

    function testCopyNotTheSameProject() {
        $tree = new TestTreeTestVersion();
        $utils = new MockWebDAVUtils();
        $utils->setReturnValue('isWriteEnabled', true);
        $tree->setReturnValue('getUtils', $utils);
        $destination = new TestFolder();
        $tree->setReturnValue('getNodeForPath', $destination, array('destination'));
        $source = new TestFolder();
        $tree->setReturnValue('getNodeForPath', $source, array('source'));
        $sourceItem = new MockDocman_Item();
        $sourceItem->setReturnValue('getGroupId', 1);
        $source->setItem($sourceItem);
        $destinationItem = new MockDocman_Item();
        $destinationItem->setReturnValue('getGroupId', 2);
        $destination->setItem($destinationItem);

        $this->expectException('Sabre_DAV_Exception_MethodNotAllowed');
        $tree->copy('source', 'destination/item');
    }

    function testCopyNoReadOnSource() {
        $tree = new TestTreeTestVersion();
        $utils = new MockWebDAVUtils();
        $utils->setReturnValue('isWriteEnabled', true);
        $tree->setReturnValue('getUtils', $utils);
        $destination = new TestFolder();
        $tree->setReturnValue('getNodeForPath', $destination, array('destination'));
        $source = new TestFolder();
        $tree->setReturnValue('getNodeForPath', $source, array('source'));
        $sourceItem = new MockDocman_Item();
        $sourceItem->setReturnValue('getGroupId', 1);
        $source->setItem($sourceItem);
        $destinationItem = new MockDocman_Item();
        $destinationItem->setReturnValue('getGroupId', 1);
        $destination->setItem($destinationItem);
        $dpm = new MockDocman_PermissionsManager();
        $dpm->setReturnValue('userCanAccess', false);
        $dpm->setReturnValue('userCanWrite', true);
        $utils->setReturnValue('getDocmanPermissionsManager', $dpm);

        $this->expectException('Sabre_DAV_Exception_MethodNotAllowed');
        $tree->copy('source', 'destination/item');
    }

    function testCopyNoWriteOnDestination() {
        $tree = new TestTreeTestVersion();
        $utils = new MockWebDAVUtils();
        $utils->setReturnValue('isWriteEnabled', true);
        $tree->setReturnValue('getUtils', $utils);
        $destination = new TestFolder();
        $tree->setReturnValue('getNodeForPath', $destination, array('destination'));
        $source = new TestFolder();
        $tree->setReturnValue('getNodeForPath', $source, array('source'));
        $sourceItem = new MockDocman_Item();
        $sourceItem->setReturnValue('getGroupId', 1);
        $source->setItem($sourceItem);
        $destinationItem = new MockDocman_Item();
        $destinationItem->setReturnValue('getGroupId', 1);
        $destination->setItem($destinationItem);
        $dpm = new MockDocman_PermissionsManager();
        $dpm->setReturnValue('userCanAccess', true);
        $dpm->setReturnValue('userCanWrite', false);
        $utils->setReturnValue('getDocmanPermissionsManager', $dpm);

        $this->expectException('Sabre_DAV_Exception_MethodNotAllowed');
        $tree->copy('source', 'destination/item');
    }

    function testCopySucceede() {
        $tree = new TestTreeTestVersion();
        $utils = new MockWebDAVUtils();
        $utils->setReturnValue('isWriteEnabled', true);
        $tree->setReturnValue('getUtils', $utils);
        $destination = new TestFolder();
        $tree->setReturnValue('getNodeForPath', $destination, array('destination'));
        $source = new TestFolder();
        $tree->setReturnValue('getNodeForPath', $source, array('source'));
        $sourceItem = new MockDocman_Item();
        $sourceItem->setReturnValue('getGroupId', 1);
        $source->setItem($sourceItem);
        $destinationItem = new MockDocman_Item();
        $destinationItem->setReturnValue('getGroupId', 1);
        $destination->setItem($destinationItem);
        $dpm = new MockDocman_PermissionsManager();
        $dpm->setReturnValue('userCanAccess', true);
        $dpm->setReturnValue('userCanWrite', true);
        $utils->setReturnValue('getDocmanPermissionsManager', $dpm);
        $dif = new MockDocman_ItemFactory();
        $utils->setReturnValue('getDocmanItemFactory', $dif);

        //$this->assertNoErrors();
        $this->expectException('Sabre_DAV_Exception_MethodNotAllowed');
        $tree->copy('source', 'destination/item');
    }

    function testMoveDocmanSucceed() {
        $tree = new TestTreeTestVersion();
        $utils = new MockWebDAVUtils();
        $utils->setReturnValue('isWriteEnabled', true);
        $tree->setReturnValue('getUtils', $utils);
        $destination = new TestFolder();
        $tree->setReturnValue('getNodeForPath', $destination, array('destination'));
        $source = new TestFolder();
        $tree->setReturnValue('getNodeForPath', $source, array('source'));
        $sourceItem = new MockDocman_Item();
        $sourceItem->setReturnValue('getGroupId', 1);
        $sourceItem->setReturnValue('getId', 128);
        $source->setItem($sourceItem);
        $user = mock('PFUser');
        $destinationItem = new MockDocman_Item();
        $destinationItem->setReturnValue('getGroupId', 1);
        $destinationItem->setReturnValue('getId', 256);
        $destination->setItem($destinationItem);
        $dpm = new MockDocman_PermissionsManager();
        $dpm->setReturnValue('userCanAccess', true);
        $dpm->setReturnValue('userCanWrite', true);
        $dpm->setReturnValue('currentUserCanWriteSubItems', true);
        $utils->setReturnValue('getDocmanPermissionsManager', $dpm);
        $dif = new MockDocman_ItemFactory();
        $utils->setReturnValue('getDocmanItemFactory', $dif);

        //$dif->expectOnce('setNewParent', array(128, 256, 'beginning'));
        //$sourceItem->expectOnce('fireEvent', array('plugin_docman_event_move', $source->getUser(), $destinationItem));

        //$this->assertNoErrors();
        $this->expectException('Sabre_DAV_Exception_MethodNotAllowed');
        $tree->move('source', 'destination/item');
    }

    function testMoveDocmanNoWriteOnSubItems() {
        $tree = new TestTreeTestVersion();
        $utils = new MockWebDAVUtils();
        $utils->setReturnValue('isWriteEnabled', true);
        $tree->setReturnValue('getUtils', $utils);
        $destination = new TestFolder();
        $tree->setReturnValue('getNodeForPath', $destination, array('destination'));
        $source = new TestFolder();
        $tree->setReturnValue('getNodeForPath', $source, array('source'));
        $sourceItem = new MockDocman_Item();
        $sourceItem->setReturnValue('getGroupId', 1);
        $source->setItem($sourceItem);
        $destinationItem = new MockDocman_Item();
        $destinationItem->setReturnValue('getGroupId', 1);
        $destination->setItem($destinationItem);
        $dpm = new MockDocman_PermissionsManager();
        $dpm->setReturnValue('userCanAccess', true);
        $dpm->setReturnValue('userCanWrite', true);
        $dpm->setReturnValue('currentUserCanWriteSubItems', false);
        $utils->setReturnValue('getDocmanPermissionsManager', $dpm);
        $dif = new MockDocman_ItemFactory();
        $utils->setReturnValue('getDocmanItemFactory', $dif);

        $dif->expectNever('setNewParent');
        $sourceItem->expectNever('fireEvent');

        $this->expectException('Sabre_DAV_Exception_MethodNotAllowed');
        $tree->move('source', 'destination/item');
    }

    function testMoveDocmanNoWriteEnabled() {
        $tree = new TestTreeTestVersion();
        $utils = new MockWebDAVUtils();
        $utils->setReturnValue('isWriteEnabled', false);
        $tree->setReturnValue('getUtils', $utils);

        $this->expectException('Sabre_DAV_Exception_MethodNotAllowed');
        $tree->move('source', 'destination/item');
    }

    function testMoveDocmanNotTheSameProject() {
        $tree = new TestTreeTestVersion();
        $utils = new MockWebDAVUtils();
        $utils->setReturnValue('isWriteEnabled', true);
        $tree->setReturnValue('getUtils', $utils);
        $destination = new TestFolder();
        $tree->setReturnValue('getNodeForPath', $destination, array('destination'));
        $source = new TestFolder();
        $tree->setReturnValue('getNodeForPath', $source, array('source'));
        $sourceItem = new MockDocman_Item();
        $sourceItem->setReturnValue('getGroupId', 1);
        $source->setItem($sourceItem);
        $destinationItem = new MockDocman_Item();
        $destinationItem->setReturnValue('getGroupId', 11);
        $destination->setItem($destinationItem);

        $this->expectException('Sabre_DAV_Exception_MethodNotAllowed');
        $tree->move('source', 'destination/item');
    }

    function testMoveDocmanNoReadOnSource() {
        $tree = new TestTreeTestVersion();
        $utils = new MockWebDAVUtils();
        $utils->setReturnValue('isWriteEnabled', true);
        $tree->setReturnValue('getUtils', $utils);
        $destination = new TestFolder();
        $tree->setReturnValue('getNodeForPath', $destination, array('destination'));
        $source = new TestFolder();
        $tree->setReturnValue('getNodeForPath', $source, array('source'));
        $sourceItem = new MockDocman_Item();
        $sourceItem->setReturnValue('getGroupId', 1);
        $source->setItem($sourceItem);
        $destinationItem = new MockDocman_Item();
        $destinationItem->setReturnValue('getGroupId', 1);
        $destination->setItem($destinationItem);
        $dpm = new MockDocman_PermissionsManager();
        $dpm->setReturnValue('userCanAccess', false);
        $dpm->setReturnValue('userCanWrite', true);
        $utils->setReturnValue('getDocmanPermissionsManager', $dpm);
        $dpm->expectNever('currentUserCanWriteSubItems');

        $this->expectException('Sabre_DAV_Exception_MethodNotAllowed');
        $tree->move('source', 'destination/item');
    }

    function testMoveDocmanNoWriteOnDestination() {
        $tree = new TestTreeTestVersion();
        $utils = new MockWebDAVUtils();
        $utils->setReturnValue('isWriteEnabled', true);
        $tree->setReturnValue('getUtils', $utils);
        $destination = new TestFolder();
        $tree->setReturnValue('getNodeForPath', $destination, array('destination'));
        $source = new TestFolder();
        $tree->setReturnValue('getNodeForPath', $source, array('source'));
        $sourceItem = new MockDocman_Item();
        $sourceItem->setReturnValue('getGroupId', 1);
        $source->setItem($sourceItem);
        $destinationItem = new MockDocman_Item();
        $destinationItem->setReturnValue('getGroupId', 1);
        $destination->setItem($destinationItem);
        $dpm = new MockDocman_PermissionsManager();
        $dpm->setReturnValue('userCanAccess', true);
        $dpm->setReturnValue('userCanWrite', false);
        $utils->setReturnValue('getDocmanPermissionsManager', $dpm);
        $dpm->expectNever('currentUserCanWriteSubItems');

        $this->expectException('Sabre_DAV_Exception_MethodNotAllowed');
        $tree->move('source', 'destination/item');
    }

    function testMoveDocmanWrongDestinationItemType() {
        $tree = new TestTreeTestVersion();
        $utils = new MockWebDAVUtils();
        $utils->setReturnValue('isWriteEnabled', true);
        $tree->setReturnValue('getUtils', $utils);
        $destination = new TestDocmanFile();
        $tree->setReturnValue('getNodeForPath', $destination, array('destination'));
        $source = new TestFolder();
        $tree->setReturnValue('getNodeForPath', $source, array('source'));
        $sourceItem = new MockDocman_Item();
        $source->setItem($sourceItem);
        $destinationItem = new MockDocman_Item();
        $destination->setItem($destinationItem);
        $dpm = new MockDocman_PermissionsManager();

        $this->expectException('Sabre_DAV_Exception_MethodNotAllowed');
        $tree->move('source', 'destination/item');
    }
}
?>