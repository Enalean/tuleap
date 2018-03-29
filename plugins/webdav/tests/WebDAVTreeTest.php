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
Mock::generate('Project');
Mock::generate('WebDAVFRSFile');
Mock::generate('WebDAVFRSRelease');
Mock::generate('WebDAVDocmanFolder');
Mock::generate('WebDAVDocmanDocument');
Mock::generate('Docman_Item');
Mock::generate('Docman_PermissionsManager');
Mock::generate('Docman_ItemFactory');
Mock::generate('WebDAVUtils');
Mock::generate('PFUser');

/**
 * This is the unit test of WebDAVTree
 */
class TestTree extends WebDAVTree {

    function getNodeForPath($path) {
        return new MockWebDAVFRSFile();
    }
}
Mock::generatePartial('TestTree', 'TestTreeTestVersion', array('canBeMoved', 'getNodeForPath', 'getUtils'));

class TestFile extends WebDAVFRSFile {

    function getProject() {
        $project = new MockProject();
        $project->setReturnValue('getGroupId', 1);
        return $project;
    }
}

class TestRelease extends WebDAVFRSRelease {

    function getProject() {
        $project = new MockProject();
        $project->setReturnValue('getGroupId', 1);
        return $project;
    }
}

class TestRelease2 extends WebDAVFRSRelease {

    function getProject() {
        $project = new MockProject();
        $project->setReturnValue('getGroupId', 2);
        return $project;
    }
}

class TestPackage extends WebDAVFRSPackage {

    function getProject() {
        $project = new MockProject();
        $project->setReturnValue('getGroupId', 1);
        return $project;
    }
}

class TestFolder extends WebDAVDocmanFolder {

    function setItem($item) {
        $this->item = $item;
    }

    function getItem() {
        return $this->item;
    }
}

class TestDocmanFile extends WebDAVDocmanFile {

    function setItem($item) {
        $this->item = $item;
    }

    function getItem() {
        return $this->item;
    }
}

class WebDAVTreeTest extends TuleapTestCase {

    private $user;
    private $project;
    private $package;
    private $release;
    private $file;
    private $docman_folder;
    private $docman_document;

    public function setUp()
    {
        parent::setUp();

        $this->user            = mock('PFUser');
        $this->project         = stub('Project')->getID()->returns(101);
        $this->package         = mock('FRSPackage');
        $this->release         = mock('FRSRelease');
        $this->file            = mock('FRSFile');
        $this->docman_folder   = mock('Docman_Folder');
        $this->docman_document = mock('Docman_Document');

        $docman_item_factory = stub('Docman_ItemFactory')->getItemFromDb()->returns(mock('Docman_Item'));
        Docman_ItemFactory::setInstance(101, $docman_item_factory);
    }

    public function tearDown()
    {
        unset($GLOBALS['Language']);
        Docman_ItemFactory::clearInstance(101);
        parent::tearDown();
    }

    function testCanBeMovedFailNotMovable() {
        $source = null;
        $destination = null;
        $tree = new TestTree($this->user, $this->project, $this->package, 0);

        $this->assertEqual($tree->canBeMoved($source, $destination), false);
    }

    function testCanBeMovedFailSourceNotReleaseDestinationPackage() {
        $source = null;
        $destination = new TestPackage($this->user, $this->project, $this->package, 0);
        $tree = new TestTree();

        $this->assertEqual($tree->canBeMoved($source, $destination), false);
    }

    function testCanBeMovedFailSourceNotFileDestinationRelease() {
        $source = null;
        $destination = new TestRelease($this->user, $this->project, $this->package, $this->release, 0);
        $tree = new TestTree();

        $this->assertEqual($tree->canBeMoved($source, $destination), false);
    }

    function testCanBeMovedFailSourceReleaseDestinationNotPackage() {
        $source = new TestRelease($this->user, $this->project, $this->package, $this->release, 0);
        $destination = null;
        $tree = new TestTree();

        $this->assertEqual($tree->canBeMoved($source, $destination), false);
    }

    function testCanBeMovedFailSourceFileDestinationNotRelease() {
        $source = new TestFile($this->user, $this->project, $this->package, $this->release, 0);
        $destination = null;
        $tree = new TestTree();

        $this->assertEqual($tree->canBeMoved($source, $destination), false);
    }

    function testCanBeMovedFailSourceReleaseDestinationPackageNotSameProject() {
        $source = new TestRelease2($this->user, $this->project, $this->package, $this->release, 0);
        $destination = new TestPackage($this->user, $this->project, $this->package, 0);
        $tree = new TestTree();

        $this->assertEqual($tree->canBeMoved($source, $destination), false);
    }

    function testCanBeMovedFailSourceFileDestinationReleaseNotSameProject() {
        $source = new TestFile($this->user, $this->project, $this->package, $this->release, 0);
        $destination = new TestRelease2($this->user, $this->project, $this->package, $this->release, 0);
        $tree = new TestTree();

        $this->assertEqual($tree->canBeMoved($source, $destination), false);
    }

    function testCanBeMovedSucceedeSourceReleaseDestinationPackage() {
        $source = new TestRelease($this->user, $this->project, $this->package, $this->release, 0);
        $destination = new TestPackage($this->user, $this->project, $this->package, 0);
        $tree = new TestTree();

        $this->assertEqual($tree->canBeMoved($source, $destination), true);
    }

    function testCanBeMovedSucceedeSourceFileDestinationRelease() {
        $source = new TestFile($this->user, $this->project, $this->package, $this->release, $this->file);
        $destination = new TestRelease($this->user, $this->project, $this->package, $this->release, 0);
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
        $destination = new TestRelease($this->user, $this->project, $this->package, $this->release, 0);
        $tree->setReturnValue('getNodeForPath', $destination, array('destination'));
        $source = new TestFolder($this->user, $this->project, $this->docman_folder, 0);
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
        $destination = new TestFolder($this->user, $this->project, $this->docman_folder, 0);
        $tree->setReturnValue('getNodeForPath', $destination, array('destination'));
        $source = new TestRelease($this->user, $this->project, $this->package, $this->release, 0);
        $tree->setReturnValue('getNodeForPath', $source, array('destination'));

        $this->expectException('Sabre_DAV_Exception_MethodNotAllowed');
        $tree->copy('source', 'destination/item');
    }

    function testCopyNotTheSameProject() {
        $tree = new TestTreeTestVersion();
        $utils = new MockWebDAVUtils();
        $utils->setReturnValue('isWriteEnabled', true);
        $tree->setReturnValue('getUtils', $utils);
        $destination = new TestFolder($this->user, $this->project, $this->docman_folder, 0);
        $tree->setReturnValue('getNodeForPath', $destination, array('destination'));
        $source = new TestFolder($this->user, $this->project, $this->docman_folder, 0);
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
        $destination = new TestFolder($this->user, $this->project, $this->docman_folder, 0);
        $tree->setReturnValue('getNodeForPath', $destination, array('destination'));
        $source = new TestFolder($this->user, $this->project, $this->docman_folder, 0);
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
        $destination = new TestFolder($this->user, $this->project, $this->docman_folder, 0);
        $tree->setReturnValue('getNodeForPath', $destination, array('destination'));
        $source = new TestFolder($this->user, $this->project, $this->docman_folder, 0);
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
        $destination = new TestFolder($this->user, $this->project, $this->docman_folder, 0);
        $tree->setReturnValue('getNodeForPath', $destination, array('destination'));
        $source = new TestFolder($this->user, $this->project, $this->docman_folder, 0);
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
        $destination = new TestFolder($this->user, $this->project, $this->docman_folder, 0);
        $tree->setReturnValue('getNodeForPath', $destination, array('destination'));
        $source = new TestFolder($this->user, $this->project, $this->docman_folder, 0);
        $tree->setReturnValue('getNodeForPath', $source, array('source'));
        $sourceItem = new MockDocman_Item();
        $sourceItem->setReturnValue('getGroupId', 1);
        $sourceItem->setReturnValue('getId', 128);
        $source->setItem($sourceItem);
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
        $destination = new TestFolder($this->user, $this->project, $this->docman_folder, 0);
        $tree->setReturnValue('getNodeForPath', $destination, array('destination'));
        $source = new TestFolder($this->user, $this->project, $this->docman_folder, 0);
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
        $destination = new TestFolder($this->user, $this->project, $this->docman_folder, 0);
        $tree->setReturnValue('getNodeForPath', $destination, array('destination'));
        $source = new TestFolder($this->user, $this->project, $this->docman_folder, 0);
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
        $destination = new TestFolder($this->user, $this->project, $this->docman_folder, 0);
        $tree->setReturnValue('getNodeForPath', $destination, array('destination'));
        $source = new TestFolder($this->user, $this->project, $this->docman_folder, 0);
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
        $destination = new TestFolder($this->user, $this->project, $this->docman_folder, 0);
        $tree->setReturnValue('getNodeForPath', $destination, array('destination'));
        $source = new TestFolder($this->user, $this->project, $this->docman_folder, 0);
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
        $destination = new TestDocmanFile($this->user, $this->project, $this->docman_document, 0);
        $tree->setReturnValue('getNodeForPath', $destination, array('destination'));
        $source = new TestFolder($this->user, $this->project, $this->docman_folder, 0);
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
