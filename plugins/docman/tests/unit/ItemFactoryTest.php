<?php
/**
 * Copyright (c) Enalean, 2017 - present. All Rights Reserved.
 * Copyright (c) STMicroelectronics, 2010. All Rights Reserved.
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
 * along with Tuleap. If not, see http://www.gnu.org/licenses/.
 *
 */

use Mockery as M;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

//phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace,Squiz.Classes.ValidClassName.NotCamelCaps
class Docman_ItemFactoryTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * 140
     * `-- 150
     *     `-- 112
     *         `-- 113
     *             `-- *
     *
     * Find path to root for 113
     */
    public function testConnectOrphansToParentsStep1(): void
    {
        $fld113 = new Docman_Folder(['item_id' => 113, 'parent_id' => 112, 'title' => 'Folder 113']);

        $itemFactory = new Docman_ItemFactory(0);

        $itemList = [113 => $fld113];
        $orphans = [113 => 113];
        $wantedItems = [];
        $rootId = $itemFactory->connectOrphansToParents($itemList, $orphans, $wantedItems);
        $this->assertEquals([112], $wantedItems);
        $this->assertEquals([113 => 113], $orphans);
        $this->assertEquals([113 => $fld113], $itemList);
        $this->assertFalse($rootId);
    }

    public function testconnectOrphansToParentsStep2(): void
    {
        $fld112 = new Docman_Folder(['item_id' => 112, 'parent_id' => 150, 'title' => 'Folder 112']);
        $fld113 = new Docman_Folder(['item_id' => 113, 'parent_id' => 112, 'title' => 'Folder 113']);

        // Expected item List
        //@php5: clone
        $c_fld112 = $fld112;
        $c_fld113 = $fld113;

        $c_fld112->addItem($c_fld113);

        $itemFactory = new Docman_ItemFactory(0);

        $itemList = [112 => $fld112, 113 => $fld113];
        $orphans  = [112 => 112, 113 => 113];
        $wantedItems = [];
        $rootId = $itemFactory->connectOrphansToParents($itemList, $orphans, $wantedItems);
        $this->assertEquals([150], $wantedItems);
        $this->assertEquals([112 => 112], $orphans);
        $this->assertEquals([112 => $c_fld112, 113 => $c_fld113], $itemList);
        $this->assertFalse($rootId);
    }

    public function testconnectOrphansToParentsStep3(): void
    {
        $fld150 = new Docman_Folder(['item_id' => 150, 'parent_id' => 140, 'title' => 'Folder 150']);
        $fld112 = new Docman_Folder(['item_id' => 112, 'parent_id' => 150, 'title' => 'Folder 112']);
        $fld113 = new Docman_Folder(['item_id' => 113, 'parent_id' => 112, 'title' => 'Folder 113']);

        // Expected item List
        //@php5: clone
        $c_fld112 = $fld112;
        $c_fld113 = $fld113;
        $c_fld150 = $fld150;
        $c_fld112->addItem($c_fld113);
        $c_fld150->addItem($c_fld112);

        $itemFactory = new Docman_ItemFactory(0);

        $fld112->addItem($fld113);
        $itemList = [150 => $fld150, 112 => $fld112, 113 => $fld113];
        $orphans  = [150 => 150, 112 => 112];
        $wantedItems = [];
        $rootId = $itemFactory->connectOrphansToParents($itemList, $orphans, $wantedItems);
        $this->assertEquals([140], $wantedItems);
        $this->assertEquals([150 => 150], $orphans);
        $this->assertEquals([150 => $c_fld150, 112 => $c_fld112, 113 => $c_fld113], $itemList);
        $this->assertFalse($rootId);
    }

    public function testconnectOrphansToParentsStep4(): void
    {
        $fld140 = new Docman_Folder(['item_id' => 140, 'parent_id' => 0, 'title' => 'Project documentation']);
        $fld150 = new Docman_Folder(['item_id' => 150, 'parent_id' => 140, 'title' => 'Folder 150']);
        $fld112 = new Docman_Folder(['item_id' => 112, 'parent_id' => 150, 'title' => 'Folder 112']);
        $fld113 = new Docman_Folder(['item_id' => 113, 'parent_id' => 112, 'title' => 'Folder 113']);

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
        $itemList = [140 => $fld140, 150 => $fld150, 112 => $fld112, 113 => $fld113];
        $orphans  = [140 => 140, 150 => 150];
        $wantedItems = [];
        $rootId = $itemFactory->connectOrphansToParents($itemList, $orphans, $wantedItems);
        $this->assertEquals([], $wantedItems);
        $this->assertEquals([], $orphans);
        $this->assertEquals([140 => $c_fld140, 150 => $c_fld150, 112 => $c_fld112, 113 => $c_fld113], $itemList);
        $this->assertEquals(140, $rootId);
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
    public function testconnectOrphansToParentsStep3PermissionDenied(): void
    {
        $fld112 = new Docman_Folder(['item_id' => 112, 'parent_id' => 150, 'title' => 'Folder 112']);
        $fld113 = new Docman_Folder(['item_id' => 113, 'parent_id' => 112, 'title' => 'Folder 113']);

        // Expected item List
        //@php5: clone
        $c_fld112 = $fld112;
        $c_fld113 = $fld113;
        $c_fld112->addItem($c_fld113);

        $itemFactory = new Docman_ItemFactory(0);

        $fld112->addItem($fld113);
        $itemList = [150 => false, 112 => $fld112, 113 => $fld113];
        $orphans  = [150 => 150, 112 => 112];
        $wantedItems = [];
        $rootId = $itemFactory->connectOrphansToParents($itemList, $orphans, $wantedItems);
        $this->assertEquals([], $wantedItems);
        $this->assertEquals([150 => 150, 112 => 112], $orphans);
        $this->assertEquals([150 => false, 112 => $c_fld112, 113 => $c_fld113], $itemList);
        $this->assertFalse($rootId);
    }

    /**
     * 100
     * |-- 110
     *     |-- 111
     *         |-- 112
     *             |-- 113
     */
    public function testIsInSubTreeSuccess(): void
    {
        $itemFactory = \Mockery::mock(\Docman_ItemFactory::class)->makePartial()->shouldAllowMockingProtectedMethods();

        $fld110 = \Mockery::spy(\Docman_Folder::class);

        $fld111 = \Mockery::spy(\Docman_Folder::class);

        $fld112 = \Mockery::spy(\Docman_Folder::class);

        $fld113 = \Mockery::spy(\Docman_Folder::class);

        $itemFactory->shouldReceive('getItemFromDb')->with(113)->andReturns($fld113);
        $itemFactory->shouldReceive('getItemFromDb')->with(112)->andReturns($fld112);

        $itemFactory->shouldReceive('isRoot')->with($fld113)->andReturns(false);
        $itemFactory->shouldReceive('isRoot')->with($fld112)->andReturns(false);

        $fld110->shouldReceive('getParentId')->never()->andReturns(100);
        $fld111->shouldReceive('getParentId')->never()->andReturns(110);
        $fld112->shouldReceive('getParentId')->once()->andReturns(111);
        $fld113->shouldReceive('getParentId')->once()->andReturns(112);

        $this->assertTrue($itemFactory->isInSubTree(113, 111));
    }

    public function testIsInSubTreeFalse(): void
    {
        $itemFactory = \Mockery::mock(\Docman_ItemFactory::class)->makePartial()->shouldAllowMockingProtectedMethods();

        $fld110 = \Mockery::spy(\Docman_Folder::class);

        $fld111 = \Mockery::spy(\Docman_Folder::class);

        $fld112 = \Mockery::spy(\Docman_Folder::class);

        $fld113 = \Mockery::spy(\Docman_Folder::class);

        $itemFactory->shouldReceive('getItemFromDb')->with(112)->andReturns($fld112);
        $itemFactory->shouldReceive('getItemFromDb')->with(111)->andReturns($fld111);

        $itemFactory->shouldReceive('isRoot')->with($fld113)->andReturns(false);
        $itemFactory->shouldReceive('isRoot')->with($fld112)->andReturns(false);
        $itemFactory->shouldReceive('isRoot')->with($fld111)->andReturns(false);

        $itemFactory->shouldReceive('getItemFromDb')->with(110)->andReturns($fld110)->once();
        $itemFactory->shouldReceive('isRoot')->with($fld110)->andReturns(true)->once();
        $fld110->shouldReceive('getParentId')->never()->andReturns(100);
        $fld111->shouldReceive('getParentId')->once()->andReturns(110);
        $fld112->shouldReceive('getParentId')->once()->andReturns(111);
        $fld113->shouldReceive('getParentId')->never()->andReturns(112);

        $this->assertFalse($itemFactory->isInSubTree(112, 113));
    }

    /**
     * 100
     * |-- 110
     *     |-- 111
     *         |-- 112
     *             |-- 113
     */
    public function testIsInSubTreeFailWithRootItem(): void
    {
        $itemFactory = \Mockery::mock(\Docman_ItemFactory::class)->makePartial()->shouldAllowMockingProtectedMethods();

        $fld110 = \Mockery::spy(\Docman_Folder::class);

        $itemFactory->shouldReceive('getItemFromDb')->with(110)->once()->andReturns($fld110);
        $itemFactory->shouldReceive('isRoot')->with($fld110)->once()->andReturns(true);
        $fld110->shouldReceive('getParentId')->never()->andReturns(0);

        $this->assertFalse($itemFactory->isInSubTree(110, 113));
    }

    /**
     * 100
     * |-- 110
     *     |-- 111
     *         |-- 112
     *             |-- 113
     */
    public function testGetParents(): void
    {
        $itemFactory = \Mockery::mock(\Docman_ItemFactory::class)->makePartial()->shouldAllowMockingProtectedMethods();

        $fld110 = \Mockery::spy(\Docman_Folder::class);

        $fld111 = \Mockery::spy(\Docman_Folder::class);

        $fld112 = \Mockery::spy(\Docman_Folder::class);

        $fld113 = \Mockery::spy(\Docman_Folder::class);

        $itemFactory->shouldReceive('getItemFromDb')->with(113)->andReturns($fld113);
        $itemFactory->shouldReceive('getItemFromDb')->with(112)->andReturns($fld112);
        $itemFactory->shouldReceive('getItemFromDb')->with(111)->andReturns($fld111);

        $itemFactory->shouldReceive('isRoot')->with($fld113)->andReturns(false);
        $itemFactory->shouldReceive('isRoot')->with($fld112)->andReturns(false);
        $itemFactory->shouldReceive('isRoot')->with($fld111)->andReturns(false);

        $itemFactory->shouldReceive('getItemFromDb')->with(110)->andReturns($fld110)->once();
        $itemFactory->shouldReceive('isRoot')->with($fld110)->andReturns(true)->once();
        $fld110->shouldReceive('getParentId')->never()->andReturns(0);
        $fld111->shouldReceive('getParentId')->once()->andReturns(110);
        $fld112->shouldReceive('getParentId')->once()->andReturns(111);
        $fld113->shouldReceive('getParentId')->never()->andReturns(112);

        $this->assertEquals([111 => true, 110 => true], $itemFactory->getParents(112));
    }

    public function testGetParentsForRoot()
    {
        $itemFactory = \Mockery::mock(\Docman_ItemFactory::class)->makePartial()->shouldAllowMockingProtectedMethods();

        $fld110 = \Mockery::spy(\Docman_Folder::class);

        $itemFactory->shouldReceive('getItemFromDb')->with(110)->once()->andReturns($fld110);
        $itemFactory->shouldReceive('isRoot')->with($fld110)->once()->andReturns(true);
        $fld110->shouldReceive('getParentId')->never()->andReturns(0);

        $this->assertEquals([], $itemFactory->getParents(110));
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
    public function testBuildTreeFromLeavesMultipleStep1()
    {
        $fld113 = new Docman_Folder(['item_id' => 113, 'parent_id' => 112, 'title' => 'Folder 113']);
        $fld115 = new Docman_Folder(['item_id' => 115, 'parent_id' => 150, 'title' => 'Folder 115']);
        $fld135 = new Docman_Folder(['item_id' => 135, 'parent_id' => 140, 'title' => 'Folder 135']);

        $itemFactory = new Docman_ItemFactory(0);

        $itemList = [113 => $fld113,
                          115 => $fld115,
                          135 => $fld135];
        $orphans = [113 => 113,
                         115 => 115,
                         135 => 135];
        $wantedItems = [];
        $rootId = $itemFactory->connectOrphansToParents($itemList, $orphans, $wantedItems);
        $this->assertEquals([112, 150, 140], $wantedItems);
        $this->assertEquals([113 => 113, 115 => 115, 135 => 135], $orphans);
        $this->assertEquals([113 => $fld113, 115 => $fld115, 135 => $fld135], $itemList);
        $this->assertFalse($rootId);
    }


    public function testBuildTreeFromLeavesMultipleStep2()
    {
        $fld140 = new Docman_Folder(['item_id' => 140, 'parent_id' => 0, 'title' => 'Project documentation', 'rank' => 0]);
        $fld150 = new Docman_Folder(['item_id' => 150, 'parent_id' => 140, 'title' => 'Folder 150', 'rank' => -2]);
        $fld112 = new Docman_Folder(['item_id' => 112, 'parent_id' => 150, 'title' => 'Folder 112', 'rank' => -2]);
        $fld113 = new Docman_Folder(['item_id' => 113, 'parent_id' => 112, 'title' => 'Folder 113', 'rank' => 0]);
        $fld115 = new Docman_Folder(['item_id' => 115, 'parent_id' => 150, 'title' => 'Folder 115', 'rank' => -1]);
        $fld135 = new Docman_Folder(['item_id' => 135, 'parent_id' => 140, 'title' => 'Folder 135', 'rank' => -1]);

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

        $itemList = [113 => $fld113,
                          115 => $fld115,
                          150 => $fld150,
                          140 => $fld140,
                          135 => $fld135,
                          112 => $fld112];
        // It's not very clean but the orphan order is very important to make
        // the test pass. To avoid the pain to develop a tree comparator, we rely
        // on the array/object comparison of SimpleTest. The bad news comes with
        // PrioritizeList because it store a mapping between it's elements and
        // the priorities. While the final result will always be the same
        // (items ordered by priority) the internal status of the mapping may
        // differ. And this internal difference will break tests :/
        $orphans = [140 => 140,
                         150 => 150,
                         112 => 112,
                         113 => 113,
                         115 => 115,
                         135 => 135];
        $wantedItems = [];
        $rootId = $itemFactory->connectOrphansToParents($itemList, $orphans, $wantedItems);
        $this->assertEquals([], $wantedItems);
        $this->assertEquals([], $orphans);
        $this->assertEquals(
            [
                113 => $c_fld113,
                115 => $c_fld115,
                135 => $c_fld135,
                112 => $c_fld112,
                140 => $c_fld140,
                150 => $c_fld150
            ],
            $itemList
        );
        $this->assertEquals(140, $rootId);
    }

    public function testPurgeDeletedItemsWithNoItems(): void
    {
        $itemFactory = \Mockery::mock(\Docman_ItemFactory::class)->makePartial()->shouldAllowMockingProtectedMethods();

        $dao = \Mockery::spy(\Docman_ItemDao::class);
        $dao->shouldReceive('listItemsToPurge')->andReturns(\TestHelper::emptyDar());

        $itemFactory->shouldReceive('_getItemDao')->andReturns($dao);

        $itemFactory->shouldReceive('purgeDeletedItem')->never();

        $this->assertTrue($itemFactory->PurgeDeletedItems(1234567890));
    }

    public function testPurgeDeletedItems(): void
    {
        $itemFactory = \Mockery::mock(\Docman_ItemFactory::class)->makePartial()->shouldAllowMockingProtectedMethods();

        $dao = \Mockery::spy(\Docman_ItemDao::class);
        $dao->shouldReceive('listItemsToPurge')->andReturns(\TestHelper::arrayToDar([
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
        ]));
        $itemFactory->shouldReceive('_getItemDao')->andReturns($dao);

        $itemFactory->shouldReceive('purgeDeletedItem')->once();

        $this->assertTrue($itemFactory->PurgeDeletedItems(1234567890));
    }

    public function testRestoreDeletedItemNonFile(): void
    {
        $itemFactory = \Mockery::mock(\Docman_ItemFactory::class)->makePartial()->shouldAllowMockingProtectedMethods();

        $item = \Mockery::spy(\Docman_Folder::class);
        $item->shouldReceive('getId')->andReturns(112);
        $item->shouldReceive('getGroupId')->andReturns(114);

        $dao = \Mockery::spy(\Docman_ItemDao::class);
        $dao->shouldReceive('restore')->with(112)->once()->andReturns(true);
        $itemFactory->shouldReceive('_getItemDao')->andReturns($dao);

        // Event
        $user = \Mockery::spy(\PFUser::class);
        $um   = \Mockery::spy(\UserManager::class);
        $um->shouldReceive('getCurrentUser')->andReturns($user);
        $itemFactory->shouldReceive('_getUserManager')->andReturns($um);
        $em = \Mockery::mock(EventManager::class);
        $em->shouldReceive('processEvent')->with('plugin_docman_event_restore', ['group_id' => 114, 'item' => $item, 'user' => $user]);
        $itemFactory->shouldReceive('_getEventManager')->andReturns($em);

        $this->assertTrue($itemFactory->restore($item));
    }

    public function testRestoreDeletedItemFile(): void
    {
        $itemFactory = \Mockery::mock(\Docman_ItemFactory::class)->makePartial()->shouldAllowMockingProtectedMethods();

        $item = \Mockery::spy(\Docman_File::class);
        $item->shouldReceive('getId')->andReturns(112);
        $item->shouldReceive('getGroupId')->andReturns(114);
        $itemFactory->shouldReceive('getItemTypeForItem')->andReturns(PLUGIN_DOCMAN_ITEM_TYPE_FILE);

        $dao = \Mockery::spy(\Docman_ItemDao::class);
        $dao->shouldReceive('restore')->with(112)->once()->andReturns(true);
        $itemFactory->shouldReceive('_getItemDao')->andReturns($dao);

        $v1 = \Mockery::spy(\Docman_Version::class);
        $v2 = \Mockery::spy(\Docman_Version::class);

        $versionFactory = M::mock(Docman_VersionFactory::class);
        $versionFactory->shouldReceive('listVersionsToPurgeForItem')->with($item)->andReturn([$v1, $v2]);
        $versionFactory->shouldReceive('restore')->with($v1)->andReturn(true)->ordered();
        $versionFactory->shouldReceive('restore')->with($v2)->andReturn(true)->ordered();
        $itemFactory->shouldReceive('_getVersionFactory')->andReturns($versionFactory);

        // Event
        $user = \Mockery::spy(\PFUser::class);
        $um   = \Mockery::spy(\UserManager::class);
        $um->shouldReceive('getCurrentUser')->andReturns($user);
        $itemFactory->shouldReceive('_getUserManager')->andReturns($um);
        $em = \Mockery::mock(EventManager::class);
        $em->shouldReceive('processEvent')->with('plugin_docman_event_restore', ['group_id' => 114, 'item' => $item, 'user' => $user]);
        $itemFactory->shouldReceive('_getEventManager')->andReturns($em);

        $this->assertTrue($itemFactory->restore($item));
    }

    public function testRestoreDeletedItemFileWithoutRestorableVersions(): void
    {
        $itemFactory = \Mockery::mock(\Docman_ItemFactory::class)->makePartial()->shouldAllowMockingProtectedMethods();

        $item = \Mockery::spy(\Docman_File::class);
        $item->shouldReceive('getId')->andReturns(112);
        $item->shouldReceive('getGroupId')->andReturns(114);
        $itemFactory->shouldReceive('getItemTypeForItem')->andReturns(PLUGIN_DOCMAN_ITEM_TYPE_FILE);

        $dao = \Mockery::spy(\Docman_ItemDao::class);
        $dao->shouldReceive('restore')->never();
        $itemFactory->shouldReceive('_getItemDao')->andReturns($dao);

        $versionFactory = M::mock(Docman_VersionFactory::class);
        $versionFactory->shouldReceive('listVersionsToPurgeForItem')->with($item)->andReturn(false);
        $versionFactory->shouldNotReceive('restore');
        $itemFactory->shouldReceive('_getVersionFactory')->andReturns($versionFactory);

        // Event
        $itemFactory->shouldReceive('_getEventManager')->never();

        $this->assertFalse($itemFactory->restore($item));
    }

    public function testRestoreDeletedItemFileWithSomeVersionRestoreFailure(): void
    {
        $itemFactory = \Mockery::mock(\Docman_ItemFactory::class)->makePartial()->shouldAllowMockingProtectedMethods();

        $item = \Mockery::spy(\Docman_File::class);
        $item->shouldReceive('getId')->andReturns(112);
        $item->shouldReceive('getGroupId')->andReturns(114);
        $itemFactory->shouldReceive('getItemTypeForItem')->andReturns(PLUGIN_DOCMAN_ITEM_TYPE_FILE);

        $dao = \Mockery::spy(\Docman_ItemDao::class);
        $dao->shouldReceive('restore')->with(112)->once()->andReturns(true);
        $itemFactory->shouldReceive('_getItemDao')->andReturns($dao);

        $v1 = \Mockery::spy(\Docman_Version::class);
        $v2 = \Mockery::spy(\Docman_Version::class);

        $versionFactory = M::mock(Docman_VersionFactory::class);
        $versionFactory->shouldReceive('listVersionsToPurgeForItem')->with($item)->andReturn([$v1, $v2]);
        $versionFactory->shouldReceive('restore')->with($v1)->andReturn(true)->ordered();
        $versionFactory->shouldReceive('restore')->with($v2)->andReturn(false)->ordered();
        $itemFactory->shouldReceive('_getVersionFactory')->andReturns($versionFactory);

        // Event
        $user = \Mockery::spy(\PFUser::class);
        $um   = \Mockery::spy(\UserManager::class);
        $um->shouldReceive('getCurrentUser')->andReturns($user);
        $itemFactory->shouldReceive('_getUserManager')->andReturns($um);
        $em = \Mockery::mock(EventManager::class);
        $em->shouldReceive('processEvent')->with('plugin_docman_event_restore', ['group_id' => 114, 'item' => $item, 'user' => $user]);
        $itemFactory->shouldReceive('_getEventManager')->andReturns($em);

        $this->assertTrue($itemFactory->restore($item));
    }

    public function testRestoreDeletedItemFileWithAllVersionRestoreFailure(): void
    {
        $itemFactory = \Mockery::mock(\Docman_ItemFactory::class)->makePartial()->shouldAllowMockingProtectedMethods();

        $item = \Mockery::spy(\Docman_File::class);
        $item->shouldReceive('getId')->andReturns(112);
        $item->shouldReceive('getGroupId')->andReturns(114);
        $itemFactory->shouldReceive('getItemTypeForItem')->andReturns(PLUGIN_DOCMAN_ITEM_TYPE_FILE);

        $dao = \Mockery::spy(\Docman_ItemDao::class);
        $dao->shouldReceive('restore')->never();
        $itemFactory->shouldReceive('_getItemDao')->andReturns($dao);

        $v1 = \Mockery::spy(\Docman_Version::class);
        $v2 = \Mockery::spy(\Docman_Version::class);

        $versionFactory = M::mock(Docman_VersionFactory::class);
        $versionFactory->shouldReceive('listVersionsToPurgeForItem')->with($item)->andReturn([$v1, $v2]);
        $versionFactory->shouldReceive('restore')->with($v1)->andReturn(false)->ordered();
        $versionFactory->shouldReceive('restore')->with($v2)->andReturn(false)->ordered();
        $itemFactory->shouldReceive('_getVersionFactory')->andReturns($versionFactory);

        // Event
        $itemFactory->shouldReceive('_getEventManager')->never();

        $this->assertFalse($itemFactory->restore($item));
    }

    public function itDeletesNotificationsWhenDeletingItem(): void
    {
        $lock_factory          = \Mockery::spy(\Docman_LockFactory::class);
        $item_dao              = \Mockery::spy(\Docman_ItemDao::class);
        $ugroups_to_notify_dao = \Mockery::spy(\Tuleap\Docman\Notifications\UgroupsToNotifyDao::class);
        $users_to_notify_dao   = \Mockery::spy(\Tuleap\Docman\Notifications\UsersToNotifyDao::class);

        $item_id = 183;
        $item    = \Mockery::spy(\Docman_File::class);
        $item->shouldReceive('getId')->andReturns($item_id);

        $item_factory = \Mockery::mock(\Docman_ItemFactory::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $item_factory->shouldReceive('getUgroupsToNotifyDao')->andReturns($ugroups_to_notify_dao);
        $ugroups_to_notify_dao->shouldReceive('deleteByItemId')->with($item_id)->once();
        $item_factory->shouldReceive('getUsersToNotifyDao')->andReturns($users_to_notify_dao);
        $users_to_notify_dao->shouldReceive('deleteByItemId')->with($item_id)->once();
        $item_factory->shouldReceive('getItemFromDb')->andReturns(null);
        $item_factory->shouldReceive('_getUserManager')->andReturns(\Mockery::spy(\UserManager::class));
        $item_factory->shouldReceive('getLockFactory')->andReturns($lock_factory);
        $item_factory->shouldReceive('_getItemDao')->andReturns($item_dao);

        $item_factory->delete($item);
    }
}
