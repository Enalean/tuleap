<?php
/**
 * Copyright (c) Enalean, 2017 - Present. All Rights Reserved.
 * Copyright (c) STMicroelectronics, 2006. All Rights Reserved.
 *
 * Originally written by Sabri LABBENE, 2007.
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

use Mockery as M;

require_once 'bootstrap.php';

Mock::generate('Docman_ItemDao');
Mock::generate('Docman_Folder');
Mock::generate('Docman_File');
Mock::generate('Docman_Version');
Mock::generate('PFUser');
Mock::generate('UserManager');
Mock::generatePartial('Docman_ItemFactory', 'Docman_ItemFactoryTestVersion', array('_getItemDao', 'purgeDeletedItem','getItemFromDb','isRoot'));
Mock::generatePartial('Docman_ItemFactory', 'Docman_ItemFactoryTestRestore', array('_getItemDao', '_getVersionFactory', 'getItemTypeForItem', '_getUserManager', '_getEventManager'));

class Docman_ItemFactoryTest extends TuleapTestCase
{
    /**
     * 140
     * `-- 150
     *     `-- 112
     *         `-- 113
     *             `-- *
     *
     * Find path to root for 113
     */
    function testconnectOrphansToParentsStep1()
    {
        $fld113 = new Docman_Folder(array('item_id' => 113, 'parent_id' => 112,'title' => 'Folder 113'));

        $itemFactory = new Docman_ItemFactory(0);

        $itemList = array(113 => $fld113);
        $orphans = array(113 => 113);
        $wantedItems = array();
        $rootId = $itemFactory->connectOrphansToParents($itemList, $orphans, $wantedItems);
        $this->assertEqual($wantedItems, array(112));
        $this->assertEqual($orphans, array(113 => 113));
        $this->assertEqual($itemList, array(113 => $fld113));
        $this->assertFalse($rootId);
    }

    function testconnectOrphansToParentsStep2()
    {
        $fld112 = new Docman_Folder(array('item_id' => 112, 'parent_id' => 150,'title' => 'Folder 112'));
        $fld113 = new Docman_Folder(array('item_id' => 113, 'parent_id' => 112,'title' => 'Folder 113'));

        // Expected item List
        //@php5: clone
        $c_fld112 = $fld112;
        $c_fld113 = $fld113;

        $c_fld112->addItem($c_fld113);

        $itemFactory = new Docman_ItemFactory(0);

        $itemList = array(112 => $fld112, 113 => $fld113);
        $orphans  = array(112 => 112, 113 => 113);
        $wantedItems = array();
        $rootId = $itemFactory->connectOrphansToParents($itemList, $orphans, $wantedItems);
        $this->assertEqual($wantedItems, array(150));
        $this->assertEqual($orphans, array(112 => 112));
        $this->assertEqual($itemList, array(112 => $c_fld112, 113 => $c_fld113));
        $this->assertFalse($rootId);
    }

    function testconnectOrphansToParentsStep3()
    {
        $fld150 = new Docman_Folder(array('item_id' => 150, 'parent_id' => 140,'title' => 'Folder 150'));
        $fld112 = new Docman_Folder(array('item_id' => 112, 'parent_id' => 150,'title' => 'Folder 112'));
        $fld113 = new Docman_Folder(array('item_id' => 113, 'parent_id' => 112,'title' => 'Folder 113'));

        // Expected item List
        //@php5: clone
        $c_fld112 = $fld112;
        $c_fld113 = $fld113;
        $c_fld150 = $fld150;
        $c_fld112->addItem($c_fld113);
        $c_fld150->addItem($c_fld112);

        $itemFactory = new Docman_ItemFactory(0);

        $fld112->addItem($fld113);
        $itemList = array(150 => $fld150, 112 => $fld112, 113 => $fld113);
        $orphans  = array(150 => 150, 112 => 112);
        $wantedItems = array();
        $rootId = $itemFactory->connectOrphansToParents($itemList, $orphans, $wantedItems);
        $this->assertEqual($wantedItems, array(140));
        $this->assertEqual($orphans, array(150 => 150));
        $this->assertEqual($itemList, array(150 => $c_fld150, 112 => $c_fld112, 113 => $c_fld113));
        $this->assertFalse($rootId);
    }

    function testconnectOrphansToParentsStep4()
    {
        $fld140 = new Docman_Folder(array('item_id' => 140, 'parent_id' => 0, 'title' => 'Project documentation'));
        $fld150 = new Docman_Folder(array('item_id' => 150, 'parent_id' => 140,'title' => 'Folder 150'));
        $fld112 = new Docman_Folder(array('item_id' => 112, 'parent_id' => 150,'title' => 'Folder 112'));
        $fld113 = new Docman_Folder(array('item_id' => 113, 'parent_id' => 112,'title' => 'Folder 113'));

        // Expected item List
        //@php5: clone
        $c_fld112 = $fld112;
        $c_fld113 = $fld113;
        $c_fld150 = $fld150;
        $c_fld140 = $fld140;
        $c_fld112->addItem($c_fld113);
        $c_fld150->addItem($c_fld112);
        $c_fld140->addItem($c_fld150);

        $itemFactory = new Docman_ItemFactory(0);

        $fld112->addItem($fld113);
        $fld150->addItem($fld112);
        $itemList = array(140 => $fld140, 150 => $fld150, 112 => $fld112, 113 => $fld113);
        $orphans  = array(140 => 140, 150 => 150);
        $wantedItems = array();
        $rootId = $itemFactory->connectOrphansToParents($itemList, $orphans, $wantedItems);
        $this->assertEqual($wantedItems, array());
        $this->assertEqual($orphans, array());
        $this->assertEqual($itemList, array(140 => $c_fld140, 150 => $c_fld150, 112 => $c_fld112, 113 => $c_fld113));
        $this->assertEqual($rootId, 140);
    }

    /**
     * 140
     * `-- 150 (unreadable)
     *     `-- 112
     *         `-- 113
     *             `-- *
     *
     * Find path to root for 113.
     * Correspond to testconnectOrphansToParentsStep3.
     * but item 150 is to readable by user.
     */
    function testconnectOrphansToParentsStep3PermissionDenied()
    {
        $fld112 = new Docman_Folder(array('item_id' => 112, 'parent_id' => 150,'title' => 'Folder 112'));
        $fld113 = new Docman_Folder(array('item_id' => 113, 'parent_id' => 112,'title' => 'Folder 113'));

        // Expected item List
        //@php5: clone
        $c_fld112 = $fld112;
        $c_fld113 = $fld113;
        $c_fld112->addItem($c_fld113);

        $itemFactory = new Docman_ItemFactory(0);

        $fld112->addItem($fld113);
        $itemList = array(150 => false, 112 => $fld112, 113 => $fld113);
        $orphans  = array(150 => 150, 112 => 112);
        $wantedItems = array();
        $rootId = $itemFactory->connectOrphansToParents($itemList, $orphans, $wantedItems);
        $this->assertEqual($wantedItems, array());
        $this->assertEqual($orphans, array(150 => 150, 112 => 112));
        $this->assertEqual($itemList, array(150 => false, 112 => $c_fld112, 113 => $c_fld113));
        $this->assertFalse($rootId);
    }

    /**
     * 100
     * |-- 110
     *     |-- 111
     *         |-- 112
     *             |-- 113
     */
    function testIsInSubTreeSuccess()
    {
        $itemFactory = new Docman_ItemFactoryTestVersion($this);

        $fld110 = new MockDocman_Folder($this);
        $fld110->setReturnValue('getParentId', 100);

        $fld111 = new MockDocman_Folder($this);
        $fld111->setReturnValue('getParentId', 110);

        $fld112 = new MockDocman_Folder($this);
        $fld112->setReturnValue('getParentId', 111);

        $fld113 = new MockDocman_Folder($this);
        $fld113->setReturnValue('getParentId', 112);

        $itemFactory->setReturnValue('getItemFromDb', $fld113, array(113));
        $itemFactory->setReturnValue('getItemFromDb', $fld112, array(112));
        $itemFactory->setReturnValue('getItemFromDb', $fld111, array(111));
        $itemFactory->setReturnValue('getItemFromDb', $fld110, array(110));

        $itemFactory->setReturnValue('isRoot', false, array($fld113));
        $itemFactory->setReturnValue('isRoot', false, array($fld112));
        $itemFactory->setReturnValue('isRoot', false, array($fld111));
        $itemFactory->setReturnValue('isRoot', true, array($fld110));

        $itemFactory->expectCallCount('getItemFromDb', 2);
        $itemFactory->expectCallCount('isRoot', 2);
        $fld110->expectNever('getParentId');
        $fld111->expectNever('getParentId');
        $fld112->expectOnce('getParentId');
        $fld113->expectOnce('getParentId');

        $this->assertTrue($itemFactory->isInSubTree(113, 111));
    }

    function testIsInSubTreeFalse()
    {
        $itemFactory = new Docman_ItemFactoryTestVersion($this);

        $fld110 = new MockDocman_Folder($this);
        $fld110->setReturnValue('getParentId', 100);

        $fld111 = new MockDocman_Folder($this);
        $fld111->setReturnValue('getParentId', 110);

        $fld112 = new MockDocman_Folder($this);
        $fld112->setReturnValue('getParentId', 111);

        $fld113 = new MockDocman_Folder($this);
        $fld113->setReturnValue('getParentId', 112);

        $itemFactory->setReturnValue('getItemFromDb', $fld113, array(113));
        $itemFactory->setReturnValue('getItemFromDb', $fld112, array(112));
        $itemFactory->setReturnValue('getItemFromDb', $fld111, array(111));
        $itemFactory->setReturnValue('getItemFromDb', $fld110, array(110));

        $itemFactory->setReturnValue('isRoot', false, array($fld113));
        $itemFactory->setReturnValue('isRoot', false, array($fld112));
        $itemFactory->setReturnValue('isRoot', false, array($fld111));
        $itemFactory->setReturnValue('isRoot', true, array($fld110));

        $itemFactory->expectCallCount('getItemFromDb', 3);
        $itemFactory->expectCallCount('isRoot', 3);
        $fld110->expectNever('getParentId');
        $fld111->expectOnce('getParentId');
        $fld112->expectOnce('getParentId');
        $fld113->expectNever('getParentId');

        $this->assertFalse($itemFactory->isInSubTree(112, 113));
    }

    /**
     * 100
     * |-- 110
     *     |-- 111
     *         |-- 112
     *             |-- 113
     */
    function testIsInSubTreeFailWithRootItem()
    {
        $itemFactory = new Docman_ItemFactoryTestVersion($this);

        $fld110 = new MockDocman_Folder($this);
        $fld110->setReturnValue('getParentId', 0);

        $itemFactory->setReturnValue('getItemFromDb', $fld110, array(110));

        $itemFactory->setReturnValue('isRoot', true, array($fld110));

        $itemFactory->expectOnce('getItemFromDb');
        $itemFactory->expectOnce('isRoot');
        $fld110->expectNever('getParentId');

        $this->assertFalse($itemFactory->isInSubTree(110, 113));
    }

    /**
     * 100
     * |-- 110
     *     |-- 111
     *         |-- 112
     *             |-- 113
     */
    function testGetParents()
    {
        $itemFactory = new Docman_ItemFactoryTestVersion();

        $fld110 = new MockDocman_Folder();
        $fld110->setReturnValue('getParentId', 0);

        $fld111 = new MockDocman_Folder();
        $fld111->setReturnValue('getParentId', 110);

        $fld112 = new MockDocman_Folder();
        $fld112->setReturnValue('getParentId', 111);

        $fld113 = new MockDocman_Folder();
        $fld113->setReturnValue('getParentId', 112);

        $itemFactory->setReturnValue('getItemFromDb', $fld113, array(113));
        $itemFactory->setReturnValue('getItemFromDb', $fld112, array(112));
        $itemFactory->setReturnValue('getItemFromDb', $fld111, array(111));
        $itemFactory->setReturnValue('getItemFromDb', $fld110, array(110));

        $itemFactory->setReturnValue('isRoot', false, array($fld113));
        $itemFactory->setReturnValue('isRoot', false, array($fld112));
        $itemFactory->setReturnValue('isRoot', false, array($fld111));
        $itemFactory->setReturnValue('isRoot', true, array($fld110));

        $itemFactory->expectCallCount('getItemFromDb', 3);
        $itemFactory->expectCallCount('isRoot', 3);
        $fld110->expectNever('getParentId');
        $fld111->expectOnce('getParentId');
        $fld112->expectOnce('getParentId');
        $fld113->expectNever('getParentId');

        $this->assertEqual(array(111 => true, 110 => true), $itemFactory->getParents(112));
    }

    function testGetParentsForRoot()
    {
        $itemFactory = new Docman_ItemFactoryTestVersion();

        $fld110 = new MockDocman_Folder();
        $fld110->setReturnValue('getParentId', 0);
        $itemFactory->setReturnValue('getItemFromDb', $fld110, array(110));
        $itemFactory->setReturnValue('isRoot', true, array($fld110));

        $itemFactory->expectOnce('getItemFromDb');
        $itemFactory->expectOnce('isRoot');
        $fld110->expectNever('getParentId');

        $this->assertEqual(array(), $itemFactory->getParents(110));
    }

    /**
     * 140
     * |-- 150
     * |   |-- 112
     * |   |   `-- 113
     * |   |       `-- *
     * |   `-- 115
     * |       `-- *
     * `-- 135
     *     `-- *
     *
     * Find path to root for 113, 115 & 135
     */
    function testBuildTreeFromLeavesMultipleStep1()
    {
        $fld113 = new Docman_Folder(array('item_id' => 113, 'parent_id' => 112,'title' => 'Folder 113'));
        $fld115 = new Docman_Folder(array('item_id' => 115, 'parent_id' => 150,'title' => 'Folder 115'));
        $fld135 = new Docman_Folder(array('item_id' => 135, 'parent_id' => 140,'title' => 'Folder 135'));

        $itemFactory = new Docman_ItemFactory(0);

        $itemList = array(113 => $fld113,
                          115 => $fld115,
                          135 => $fld135);
        $orphans = array(113 => 113,
                         115 => 115,
                         135 => 135);
        $wantedItems = array();
        $rootId = $itemFactory->connectOrphansToParents($itemList, $orphans, $wantedItems);
        $this->assertEqual($wantedItems, array(112, 150, 140));
        $this->assertEqual($orphans, array(113 => 113, 115 => 115, 135 => 135));
        $this->assertEqual($itemList, array(113 => $fld113, 115 => $fld115, 135 => $fld135));
        $this->assertFalse($rootId);
    }


    function testBuildTreeFromLeavesMultipleStep2()
    {
        $fld140 = new Docman_Folder(array('item_id' => 140, 'parent_id' => 0, 'title' => 'Project documentation', 'rank' => 0));
        $fld150 = new Docman_Folder(array('item_id' => 150, 'parent_id' => 140,'title' => 'Folder 150', 'rank' => -2));
        $fld112 = new Docman_Folder(array('item_id' => 112, 'parent_id' => 150,'title' => 'Folder 112', 'rank' => -2));
        $fld113 = new Docman_Folder(array('item_id' => 113, 'parent_id' => 112,'title' => 'Folder 113', 'rank' => 0));
        $fld115 = new Docman_Folder(array('item_id' => 115, 'parent_id' => 150,'title' => 'Folder 115', 'rank' => -1));
        $fld135 = new Docman_Folder(array('item_id' => 135, 'parent_id' => 140,'title' => 'Folder 135', 'rank' => -1));

        // Expected item List
        //@php5: clone
        $c_fld112 = $fld112;
        $c_fld113 = $fld113;
        $c_fld150 = $fld150;
        $c_fld140 = $fld140;
        $c_fld115 = $fld115;
        $c_fld135 = $fld135;
        $c_fld140->addItem($c_fld150);
        $c_fld150->addItem($c_fld112);
        $c_fld112->addItem($c_fld113);
        $c_fld150->addItem($c_fld115);
        $c_fld140->addItem($c_fld135);

        $itemFactory = new Docman_ItemFactory(0);

        $itemList = array(113 => $fld113,
                          115 => $fld115,
                          150 => $fld150,
                          140 => $fld140,
                          135 => $fld135,
                          112 => $fld112);
        // It's not very clean but the orphan order is very important to make
        // the test pass. To avoid the pain to develop a tree comparator, we rely
        // on the array/object comparison of SimpleTest. The bad news comes with
        // PrioritizeList because it store a mapping between it's elements and
        // the priorities. While the final result will always be the same
        // (items ordered by priority) the internal status of the mapping may
        // differ. And this internal difference will break tests :/
        $orphans = array(140 => 140,
                         150 => 150,
                         112 => 112,
                         113 => 113,
                         115 => 115,
                         135 => 135);
        $wantedItems = array();
        $rootId = $itemFactory->connectOrphansToParents($itemList, $orphans, $wantedItems);
        $this->assertEqual($wantedItems, array());
        $this->assertEqual($orphans, array());
        $this->assertEqual($itemList, array(113 => $c_fld113, 115 => $c_fld115, 135 => $c_fld135, 112 => $c_fld112, 140 => $c_fld140, 150 => $c_fld150));
        $this->assertEqual($rootId, 140);
    }

    function testPurgeDeletedItemsWithNoItems()
    {
        $itemFactory = new Docman_ItemFactoryTestVersion($this);

        $dao = mock('Docman_ItemDao');
        expect($dao)->listItemsToPurge()->once();
        stub($dao)->listItemsToPurge()->returnsEmptyDar();
        $dao->expectOnce('listItemsToPurge');

        $itemFactory->setReturnValue('_getItemDao', $dao);

        $itemFactory->expectNever('purgeDeletedItem');

        $this->assertTrue($itemFactory->PurgeDeletedItems(1234567890));
    }

    function testPurgeDeletedItems()
    {
        $itemFactory = new Docman_ItemFactoryTestVersion($this);

        $dao = mock('Docman_ItemDao');
        expect($dao)->listItemsToPurge()->once();
        stub($dao)->listItemsToPurge()->returnsDar(
            array(
                'id'               => null,
                'title'            => null,
                'description'      => null,
                'createDate'       => null,
                'updateDate'       => null,
                'deleteDate'       => null,
                'rank'             => null,
                'parentId'         => null,
                'groupId'          => null,
                'ownerId'          => null,
                'status'           => null,
                'obsolescenceDate' => null
            )
        );
        $itemFactory->setReturnValue('_getItemDao', $dao);

        $itemFactory->expectOnce('purgeDeletedItem');

        $this->assertTrue($itemFactory->PurgeDeletedItems(1234567890));
    }

    function testRestoreDeletedItemNonFile()
    {
        $itemFactory = new Docman_ItemFactoryTestRestore($this);

        $item = new MockDocman_Folder($this);
        $item->setReturnValue('getId', 112);
        $item->setReturnValue('getGroupId', 114);

        $dao = new MockDocman_ItemDao($this);
        $dao->expectOnce('restore', array(112));
        $dao->setReturnValue('restore', true);
        $itemFactory->setReturnValue('_getItemDao', $dao);

        // Event
        $user = mock('PFUser');
        $um   = new MockUserManager($this);
        $um->setReturnValue('getCurrentUser', $user);
        $itemFactory->setReturnValue('_getUserManager', $um);
        $em = \Mockery::mock(EventManager::class);
        $em->shouldReceive('processEvent')->with('plugin_docman_event_restore', array('group_id' => 114, 'item' => $item, 'user' => $user));
        $itemFactory->setReturnValue('_getEventManager', $em);

        $this->assertTrue($itemFactory->restore($item));
    }

    function testRestoreDeletedItemFile()
    {
        $itemFactory = new Docman_ItemFactoryTestRestore($this);

        $item = new MockDocman_File($this);
        $item->setReturnValue('getId', 112);
        $item->setReturnValue('getGroupId', 114);
        $itemFactory->setReturnValue('getItemTypeForItem', PLUGIN_DOCMAN_ITEM_TYPE_FILE);

        $dao = new MockDocman_ItemDao($this);
        $dao->expectOnce('restore', array(112));
        $dao->setReturnValue('restore', true);
        $itemFactory->setReturnValue('_getItemDao', $dao);

        $v1 = new MockDocman_Version($this);
        $v2 = new MockDocman_Version($this);

        $versionFactory = M::mock(Docman_VersionFactory::class);
        $versionFactory->shouldReceive('listVersionsToPurgeForItem')->with($item)->andReturn([$v1, $v2]);
        $versionFactory->shouldReceive('restore')->with($v1)->andReturn(true)->ordered();
        $versionFactory->shouldReceive('restore')->with($v2)->andReturn(true)->ordered();
        $itemFactory->setReturnValue('_getVersionFactory', $versionFactory);

        // Event
        $user = mock('PFUser');
        $um   = new MockUserManager($this);
        $um->setReturnValue('getCurrentUser', $user);
        $itemFactory->setReturnValue('_getUserManager', $um);
        $em = \Mockery::mock(EventManager::class);
        $em->shouldReceive('processEvent')->with('plugin_docman_event_restore', array('group_id' => 114, 'item' => $item, 'user' => $user));
        $itemFactory->setReturnValue('_getEventManager', $em);

        $this->assertTrue($itemFactory->restore($item));
    }

    function testRestoreDeletedItemFileWithoutRestorableVersions()
    {
        $itemFactory = new Docman_ItemFactoryTestRestore($this);

        $item = new MockDocman_File($this);
        $item->setReturnValue('getId', 112);
        $item->setReturnValue('getGroupId', 114);
        $itemFactory->setReturnValue('getItemTypeForItem', PLUGIN_DOCMAN_ITEM_TYPE_FILE);

        $dao = new MockDocman_ItemDao($this);
        $dao->expectNever('restore');
        $itemFactory->setReturnValue('_getItemDao', $dao);

        $versionFactory = M::mock(Docman_VersionFactory::class);
        $versionFactory->shouldReceive('listVersionsToPurgeForItem')->with($item)->andReturn(false);
        $versionFactory->shouldNotReceive('restore');
        $itemFactory->setReturnValue('_getVersionFactory', $versionFactory);

        // Event
        $itemFactory->expectNever('_getEventManager');

        $this->assertFalse($itemFactory->restore($item));
    }

    function testRestoreDeletedItemFileWithSomeVersionRestoreFailure()
    {
        $itemFactory = new Docman_ItemFactoryTestRestore($this);

        $item = new MockDocman_File($this);
        $item->setReturnValue('getId', 112);
        $item->setReturnValue('getGroupId', 114);
        $itemFactory->setReturnValue('getItemTypeForItem', PLUGIN_DOCMAN_ITEM_TYPE_FILE);

        $dao = new MockDocman_ItemDao($this);
        $dao->expectOnce('restore', array(112));
        $dao->setReturnValue('restore', true);
        $itemFactory->setReturnValue('_getItemDao', $dao);

        $v1 = new MockDocman_Version($this);
        $v2 = new MockDocman_Version($this);

        $versionFactory = M::mock(Docman_VersionFactory::class);
        $versionFactory->shouldReceive('listVersionsToPurgeForItem')->with($item)->andReturn([$v1, $v2]);
        $versionFactory->shouldReceive('restore')->with($v1)->andReturn(true)->ordered();
        $versionFactory->shouldReceive('restore')->with($v2)->andReturn(false)->ordered();
        $itemFactory->setReturnValue('_getVersionFactory', $versionFactory);

        // Event
        $user = mock('PFUser');
        $um   = new MockUserManager($this);
        $um->setReturnValue('getCurrentUser', $user);
        $itemFactory->setReturnValue('_getUserManager', $um);
        $em = \Mockery::mock(EventManager::class);
        $em->shouldReceive('processEvent')->with('plugin_docman_event_restore', array('group_id' => 114, 'item' => $item, 'user' => $user));
        $itemFactory->setReturnValue('_getEventManager', $em);

        $this->assertTrue($itemFactory->restore($item));
    }

    function testRestoreDeletedItemFileWithAllVersionRestoreFailure()
    {
        $itemFactory = new Docman_ItemFactoryTestRestore($this);

        $item = new MockDocman_File($this);
        $item->setReturnValue('getId', 112);
        $item->setReturnValue('getGroupId', 114);
        $itemFactory->setReturnValue('getItemTypeForItem', PLUGIN_DOCMAN_ITEM_TYPE_FILE);

        $dao = new MockDocman_ItemDao($this);
        $dao->expectNever('restore');
        $itemFactory->setReturnValue('_getItemDao', $dao);

        $v1 = new MockDocman_Version($this);
        $v2 = new MockDocman_Version($this);

        $versionFactory = M::mock(Docman_VersionFactory::class);
        $versionFactory->shouldReceive('listVersionsToPurgeForItem')->with($item)->andReturn([$v1, $v2]);
        $versionFactory->shouldReceive('restore')->with($v1)->andReturn(false)->ordered();
        $versionFactory->shouldReceive('restore')->with($v2)->andReturn(false)->ordered();
        $itemFactory->setReturnValue('_getVersionFactory', $versionFactory);

        // Event
        $itemFactory->expectNever('_getEventManager');

        $this->assertFalse($itemFactory->restore($item));
    }

    public function itDeletesNotificationsWhenDeletingItem()
    {
        $lock_factory          = mock('Docman_LockFactory');
        $item_dao              = mock('Docman_ItemDao');
        $ugroups_to_notify_dao = mock('Tuleap\Docman\Notifications\UgroupsToNotifyDao');
        $users_to_notify_dao   = mock('Tuleap\Docman\Notifications\UsersToNotifyDao');

        $item_id = 183;
        $item    = new MockDocman_File($this);
        $item->setReturnValue('getId', $item_id);

        $item_factory = partial_mock(
            'Docman_ItemFactory',
            array(
                'getUgroupsToNotifyDao',
                'getUsersToNotifyDao',
                'getItemFromDb',
                '_getUserManager',
                'getLockFactory',
                'delCutPreferenceForAllUsers',
                'delCopyPreferenceForAllUsers',
                '_getItemDao'
            ),
            array(0)
        );
        $item_factory->setReturnValue('getUgroupsToNotifyDao', $ugroups_to_notify_dao);
        $ugroups_to_notify_dao->expectOnce('deleteByItemId', array($item_id));
        $item_factory->setReturnValue('getUsersToNotifyDao', $users_to_notify_dao);
        $users_to_notify_dao->expectOnce('deleteByItemId', array($item_id));
        $item_factory->setReturnValue('getItemFromDb', null);
        $item_factory->setReturnValue('_getUserManager', new MockUserManager($this));
        $item_factory->setReturnValue('getLockFactory', $lock_factory);
        $item_factory->setReturnValue('_getItemDao', $item_dao);

        $item_factory->delete($item);
    }
}
