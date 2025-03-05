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

declare(strict_types=1);

namespace Tuleap\Docman;

use Docman_File;
use Docman_Folder;
use Docman_Item;
use Docman_ItemDao;
use Docman_ItemFactory;
use Docman_LockFactory;
use Docman_Version;
use Docman_VersionFactory;
use EventManager;
use TestHelper;
use Tuleap\Docman\Notifications\UgroupsToNotifyDao;
use Tuleap\Docman\Notifications\UsersToNotifyDao;
use Tuleap\Document\Tests\Stubs\RecentlyVisited\DeleteVisitByItemIdStub;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use UserManager;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class ItemFactoryTest extends TestCase
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
    public function testConnectOrphansToParentsStep1(): void
    {
        $fld113 = new Docman_Folder(['item_id' => 113, 'parent_id' => 112, 'title' => 'Folder 113']);

        $itemFactory = new Docman_ItemFactory(0);

        $itemList    = [113 => $fld113];
        $orphans     = [113 => 113];
        $wantedItems = [];
        $rootId      = $itemFactory->connectOrphansToParents($itemList, $orphans, $wantedItems);
        self::assertEquals([112], $wantedItems);
        self::assertEquals([113 => 113], $orphans);
        self::assertEquals([113 => $fld113], $itemList);
        self::assertFalse($rootId);
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

        $itemList    = [112 => $fld112, 113 => $fld113];
        $orphans     = [112 => 112, 113 => 113];
        $wantedItems = [];
        $rootId      = $itemFactory->connectOrphansToParents($itemList, $orphans, $wantedItems);
        self::assertEquals([150], $wantedItems);
        self::assertEquals([112 => 112], $orphans);
        self::assertEquals([112 => $c_fld112, 113 => $c_fld113], $itemList);
        self::assertFalse($rootId);
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
        $itemList    = [150 => $fld150, 112 => $fld112, 113 => $fld113];
        $orphans     = [150 => 150, 112 => 112];
        $wantedItems = [];
        $rootId      = $itemFactory->connectOrphansToParents($itemList, $orphans, $wantedItems);
        self::assertEquals([140], $wantedItems);
        self::assertEquals([150 => 150], $orphans);
        self::assertEquals([150 => $c_fld150, 112 => $c_fld112, 113 => $c_fld113], $itemList);
        self::assertFalse($rootId);
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
        $itemList    = [140 => $fld140, 150 => $fld150, 112 => $fld112, 113 => $fld113];
        $orphans     = [140 => 140, 150 => 150];
        $wantedItems = [];
        $rootId      = $itemFactory->connectOrphansToParents($itemList, $orphans, $wantedItems);
        self::assertEquals([], $wantedItems);
        self::assertEquals([], $orphans);
        self::assertEquals([140 => $c_fld140, 150 => $c_fld150, 112 => $c_fld112, 113 => $c_fld113], $itemList);
        self::assertEquals(140, $rootId);
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
        $itemList    = [150 => false, 112 => $fld112, 113 => $fld113];
        $orphans     = [150 => 150, 112 => 112];
        $wantedItems = [];
        $rootId      = $itemFactory->connectOrphansToParents($itemList, $orphans, $wantedItems);
        self::assertEquals([], $wantedItems);
        self::assertEquals([150 => 150, 112 => 112], $orphans);
        self::assertEquals([150 => false, 112 => $c_fld112, 113 => $c_fld113], $itemList);
        self::assertFalse($rootId);
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
        $itemFactory = $this->createPartialMock(Docman_ItemFactory::class, [
            'getItemFromDb',
            'isRoot',
        ]);

        $fld112 = new Docman_Folder(['parent_id' => 111]);
        $fld113 = new Docman_Folder(['parent_id' => 112]);

        $itemFactory->method('getItemFromDb')->willReturnCallback(static fn($id) => match ($id) {
            112 => $fld112,
            113 => $fld113,
        });
        $itemFactory->method('isRoot')->willReturnCallback(static fn(Docman_Item $item) => match ($item) {
            $fld112, $fld113 => false,
        });

        self::assertTrue($itemFactory->isInSubTree(113, 111));
    }

    public function testIsInSubTreeFalse(): void
    {
        $itemFactory = $this->createPartialMock(Docman_ItemFactory::class, [
            'getItemFromDb',
            'isRoot',
        ]);

        $fld110 = new Docman_Folder(['parent_id' => 100]);
        $fld111 = new Docman_Folder(['parent_id' => 110]);
        $fld112 = new Docman_Folder(['parent_id' => 111]);
        $fld113 = new Docman_Folder(['parent_id' => 112]);

        $itemFactory->method('getItemFromDb')->willReturnMap([
            110 => [$fld110],
            111 => [$fld111],
            112 => [$fld112],
        ]);
        $itemFactory->method('isRoot')->willReturnCallback(static fn(Docman_Item $item) => match ($item) {
            $fld110                   => true,
            $fld111, $fld112, $fld113 => false,
        });

        self::assertFalse($itemFactory->isInSubTree(112, 113));
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
        $itemFactory = $this->createPartialMock(Docman_ItemFactory::class, [
            'getItemFromDb',
            'isRoot',
        ]);

        $fld110 = new Docman_Folder(['parent_id' => 0]);

        $itemFactory->expects(self::once())->method('getItemFromDb')->with(110)->willReturn($fld110);
        $itemFactory->expects(self::once())->method('isRoot')->with($fld110)->willReturn(true);

        self::assertFalse($itemFactory->isInSubTree(110, 113));
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
        $itemFactory = $this->createPartialMock(Docman_ItemFactory::class, [
            'getItemFromDb',
            'isRoot',
        ]);

        $fld110 = new Docman_Folder(['item_id' => 110, 'parent_id' => 0]);
        $fld111 = new Docman_Folder(['item_id' => 111, 'parent_id' => 110]);
        $fld112 = new Docman_Folder(['item_id' => 112, 'parent_id' => 111]);
        $fld113 = new Docman_Folder(['item_id' => 113, 'parent_id' => 112]);

        $itemFactory->method('getItemFromDb')->willReturnCallback(static fn($id) => match ($id) {
            113 => $fld113,
            112 => $fld112,
            111 => $fld111,
            110 => $fld110,
        });
        $itemFactory->method('isRoot')->willReturnCallback(static fn(Docman_Item $item) => match ($item) {
            $fld113, $fld112, $fld111 => false,
            $fld110                   => true,
        });

        self::assertEquals([111 => true, 110 => true], $itemFactory->getParents(112));
    }

    public function testGetParentsForRoot(): void
    {
        $itemFactory = $this->createPartialMock(Docman_ItemFactory::class, [
            'getItemFromDb',
            'isRoot',
        ]);

        $fld110 = new Docman_Folder(['parent_id' => 0]);

        $itemFactory->expects(self::once())->method('getItemFromDb')->with(110)->willReturn($fld110);
        $itemFactory->expects(self::once())->method('isRoot')->with($fld110)->willReturn(true);

        self::assertEquals([], $itemFactory->getParents(110));
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
    public function testBuildTreeFromLeavesMultipleStep1(): void
    {
        $fld113 = new Docman_Folder(['item_id' => 113, 'parent_id' => 112, 'title' => 'Folder 113']);
        $fld115 = new Docman_Folder(['item_id' => 115, 'parent_id' => 150, 'title' => 'Folder 115']);
        $fld135 = new Docman_Folder(['item_id' => 135, 'parent_id' => 140, 'title' => 'Folder 135']);

        $itemFactory = new Docman_ItemFactory(0);

        $itemList    = [
            113 => $fld113,
            115 => $fld115,
            135 => $fld135,
        ];
        $orphans     = [
            113 => 113,
            115 => 115,
            135 => 135,
        ];
        $wantedItems = [];
        $rootId      = $itemFactory->connectOrphansToParents($itemList, $orphans, $wantedItems);
        self::assertEquals([112, 150, 140], $wantedItems);
        self::assertEquals([113 => 113, 115 => 115, 135 => 135], $orphans);
        self::assertEquals([113 => $fld113, 115 => $fld115, 135 => $fld135], $itemList);
        self::assertFalse($rootId);
    }

    public function testBuildTreeFromLeavesMultipleStep2(): void
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

        $itemList = [
            113 => $fld113,
            115 => $fld115,
            150 => $fld150,
            140 => $fld140,
            135 => $fld135,
            112 => $fld112,
        ];
        // It's not very clean but the orphan order is very important to make
        // the test pass. To avoid the pain to develop a tree comparator, we rely
        // on the array/object comparison of SimpleTest. The bad news comes with
        // PrioritizeList because it store a mapping between it's elements and
        // the priorities. While the final result will always be the same
        // (items ordered by priority) the internal status of the mapping may
        // differ. And this internal difference will break tests :/
        $orphans     = [
            140 => 140,
            150 => 150,
            112 => 112,
            113 => 113,
            115 => 115,
            135 => 135,
        ];
        $wantedItems = [];
        $rootId      = $itemFactory->connectOrphansToParents($itemList, $orphans, $wantedItems);
        self::assertEquals([], $wantedItems);
        self::assertEquals([], $orphans);
        self::assertEquals([
            113 => $c_fld113,
            115 => $c_fld115,
            135 => $c_fld135,
            112 => $c_fld112,
            140 => $c_fld140,
            150 => $c_fld150,
        ], $itemList);
        self::assertEquals(140, $rootId);
    }

    public function testPurgeDeletedItemsWithNoItems(): void
    {
        $itemFactory = $this->createPartialMock(Docman_ItemFactory::class, [
            '_getItemDao',
            'purgeDeletedItem',
        ]);

        $dao = $this->createMock(Docman_ItemDao::class);
        $dao->method('listItemsToPurge')->willReturn(TestHelper::emptyDar());

        $itemFactory->method('_getItemDao')->willReturn($dao);
        $itemFactory->expects(self::never())->method('purgeDeletedItem');

        self::assertTrue($itemFactory->PurgeDeletedItems(1234567890));
    }

    public function testPurgeDeletedItems(): void
    {
        $itemFactory = $this->createPartialMock(Docman_ItemFactory::class, [
            '_getItemDao',
            'purgeDeletedItem',
        ]);

        $dao = $this->createMock(Docman_ItemDao::class);
        $dao->method('listItemsToPurge')->willReturn(TestHelper::arrayToDar([
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
            'obsolescenceDate' => null,
        ]));
        $itemFactory->method('_getItemDao')->willReturn($dao);

        $itemFactory->expects(self::once())->method('purgeDeletedItem');

        self::assertTrue($itemFactory->PurgeDeletedItems(1234567890));
    }

    public function testRestoreDeletedItemNonFile(): void
    {
        $itemFactory = $this->createPartialMock(Docman_ItemFactory::class, [
            '_getItemDao',
            '_getUserManager',
            '_getEventManager',
        ]);

        $item = new Docman_Folder(['item_id' => 112, 'group_id' => 114]);

        $dao = $this->createMock(Docman_ItemDao::class);
        $dao->expects(self::once())->method('restore')->with(112)->willReturn(true);
        $itemFactory->method('_getItemDao')->willReturn($dao);

        // Event
        $user = UserTestBuilder::buildWithDefaults();
        $um   = $this->createMock(UserManager::class);
        $um->method('getCurrentUser')->willReturn($user);
        $itemFactory->method('_getUserManager')->willReturn($um);
        $em = $this->createMock(EventManager::class);
        $em->method('processEvent')->with('plugin_docman_event_restore', ['group_id' => 114, 'item' => $item, 'user' => $user]);
        $itemFactory->method('_getEventManager')->willReturn($em);

        self::assertTrue($itemFactory->restore($item));
    }

    public function testRestoreDeletedItemFile(): void
    {
        $itemFactory = $this->createPartialMock(Docman_ItemFactory::class, [
            '_getItemDao',
            '_getUserManager',
            '_getEventManager',
            '_getVersionFactory',
            'getItemTypeForItem',
        ]);

        $item = new Docman_File(['item_id' => 112, 'group_id' => 114]);
        $itemFactory->method('getItemTypeForItem')->willReturn(PLUGIN_DOCMAN_ITEM_TYPE_FILE);

        $dao = $this->createMock(Docman_ItemDao::class);
        $dao->expects(self::once())->method('restore')->with(112)->willReturn(true);
        $itemFactory->method('_getItemDao')->willReturn($dao);

        $v1 = new Docman_Version();
        $v2 = new Docman_Version();

        $versionFactory = $this->createMock(Docman_VersionFactory::class);
        $versionFactory->method('listVersionsToPurgeForItem')->with($item)->willReturn([$v1, $v2]);
        $matcher = $this->exactly(2);
        $versionFactory->expects($matcher)->method('restore')->willReturnCallback(function (...$parameters) use ($matcher, $v1, $v2) {
            if ($matcher->numberOfInvocations() === 1) {
                self::assertSame($v1, $parameters[0]);
            }
            if ($matcher->numberOfInvocations() === 2) {
                self::assertSame($v2, $parameters[0]);
            }
            return true;
        });
        $itemFactory->method('_getVersionFactory')->willReturn($versionFactory);

        // Event
        $user = UserTestBuilder::buildWithDefaults();
        $um   = $this->createMock(UserManager::class);
        $um->method('getCurrentUser')->willReturn($user);
        $itemFactory->method('_getUserManager')->willReturn($um);
        $em = $this->createMock(EventManager::class);
        $em->method('processEvent')->with('plugin_docman_event_restore', ['group_id' => 114, 'item' => $item, 'user' => $user]);
        $itemFactory->method('_getEventManager')->willReturn($em);

        self::assertTrue($itemFactory->restore($item));
    }

    public function testRestoreDeletedItemFileWithoutRestorableVersions(): void
    {
        $itemFactory = $this->createPartialMock(Docman_ItemFactory::class, [
            'getItemTypeForItem',
            '_getItemDao',
            '_getVersionFactory',
            '_getEventManager',
        ]);

        $item = new Docman_File(['item_id' => 112, 'group_id' => 114]);
        $itemFactory->method('getItemTypeForItem')->willReturn(PLUGIN_DOCMAN_ITEM_TYPE_FILE);

        $dao = $this->createMock(Docman_ItemDao::class);
        $dao->expects(self::never())->method('restore');
        $itemFactory->method('_getItemDao')->willReturn($dao);

        $versionFactory = $this->createMock(Docman_VersionFactory::class);
        $versionFactory->method('listVersionsToPurgeForItem')->with($item)->willReturn(false);
        $versionFactory->expects(self::never())->method('restore');
        $itemFactory->method('_getVersionFactory')->willReturn($versionFactory);

        // Event
        $itemFactory->expects(self::never())->method('_getEventManager');

        self::assertFalse($itemFactory->restore($item));
    }

    public function testRestoreDeletedItemFileWithSomeVersionRestoreFailure(): void
    {
        $itemFactory = $this->createPartialMock(Docman_ItemFactory::class, [
            'getItemTypeForItem',
            '_getItemDao',
            '_getVersionFactory',
            '_getEventManager',
            '_getUserManager',
        ]);

        $item = new Docman_File(['item_id' => 112, 'group_id' => 114]);
        $itemFactory->method('getItemTypeForItem')->willReturn(PLUGIN_DOCMAN_ITEM_TYPE_FILE);

        $dao = $this->createMock(Docman_ItemDao::class);
        $dao->expects(self::once())->method('restore')->with(112)->willReturn(true);
        $itemFactory->method('_getItemDao')->willReturn($dao);

        $v1 = new Docman_Version();
        $v2 = new Docman_Version();

        $versionFactory = $this->createMock(Docman_VersionFactory::class);
        $versionFactory->method('listVersionsToPurgeForItem')->with($item)->willReturn([$v1, $v2]);
        $matcher = $this->exactly(2);
        $versionFactory->expects($matcher)->method('restore')->willReturnCallback(function (...$parameters) use ($matcher, $v1, $v2) {
            if ($matcher->numberOfInvocations() === 1) {
                self::assertSame($v1, $parameters[0]);
                return true;
            }
            if ($matcher->numberOfInvocations() === 2) {
                self::assertSame($v2, $parameters[0]);
                return false;
            }
        });
        $itemFactory->method('_getVersionFactory')->willReturn($versionFactory);

        // Event
        $user = UserTestBuilder::buildWithDefaults();
        $um   = $this->createMock(UserManager::class);
        $um->method('getCurrentUser')->willReturn($user);
        $itemFactory->method('_getUserManager')->willReturn($um);
        $em = $this->createMock(EventManager::class);
        $em->method('processEvent')->with('plugin_docman_event_restore', ['group_id' => 114, 'item' => $item, 'user' => $user]);
        $itemFactory->method('_getEventManager')->willReturn($em);

        self::assertTrue($itemFactory->restore($item));
    }

    public function testRestoreDeletedItemFileWithAllVersionRestoreFailure(): void
    {
        $itemFactory = $this->createPartialMock(Docman_ItemFactory::class, [
            'getItemTypeForItem',
            '_getItemDao',
            '_getVersionFactory',
            '_getEventManager',
        ]);

        $item = new Docman_File(['item_id' => 112, 'group_id' => 114]);
        $itemFactory->method('getItemTypeForItem')->willReturn(PLUGIN_DOCMAN_ITEM_TYPE_FILE);

        $dao = $this->createMock(Docman_ItemDao::class);
        $dao->expects(self::never())->method('restore');
        $itemFactory->method('_getItemDao')->willReturn($dao);

        $v1 = new Docman_Version();
        $v2 = new Docman_Version();

        $versionFactory = $this->createMock(Docman_VersionFactory::class);
        $versionFactory->method('listVersionsToPurgeForItem')->with($item)->willReturn([$v1, $v2]);
        $matcher = $this->exactly(2);
        $versionFactory->expects($matcher)->method('restore')->willReturnCallback(function (...$parameters) use ($matcher, $v1, $v2) {
            if ($matcher->numberOfInvocations() === 1) {
                self::assertSame($v1, $parameters[0]);
            }
            if ($matcher->numberOfInvocations() === 2) {
                self::assertSame($v2, $parameters[0]);
            }
            return false;
        });
        $itemFactory->method('_getVersionFactory')->willReturn($versionFactory);

        // Event
        $itemFactory->expects(self::never())->method('_getEventManager');

        self::assertFalse($itemFactory->restore($item));
    }

    public function testItDeletesNotificationsAndVisitsWhenDeletingItem(): void
    {
        $lock_factory          = $this->createMock(Docman_LockFactory::class);
        $item_dao              = $this->createMock(Docman_ItemDao::class);
        $ugroups_to_notify_dao = $this->createMock(UgroupsToNotifyDao::class);
        $users_to_notify_dao   = $this->createMock(UsersToNotifyDao::class);
        $user_manager          = $this->createMock(UserManager::class);

        $visit = DeleteVisitByItemIdStub::build();

        $item_id = 183;
        $item    = new Docman_File(['item_id' => $item_id]);

        $item_factory = $this->createPartialMock(Docman_ItemFactory::class, [
            'getUgroupsToNotifyDao',
            'getUsersToNotifyDao',
            'getItemFromDb',
            '_getUserManager',
            'getLockFactory',
            '_getItemDao',
            'getRecentlyVisitedDao',
        ]);
        $item_factory->method('getUgroupsToNotifyDao')->willReturn($ugroups_to_notify_dao);
        $ugroups_to_notify_dao->expects(self::once())->method('deleteByItemId')->with($item_id);
        $item_factory->method('getUsersToNotifyDao')->willReturn($users_to_notify_dao);
        $users_to_notify_dao->expects(self::once())->method('deleteByItemId')->with($item_id);
        $item_factory->method('getItemFromDb')->willReturn(null);
        $user_manager->method('getCurrentUser')->willReturn(UserTestBuilder::buildWithDefaults());
        $item_factory->method('_getUserManager')->willReturn($user_manager);
        $item_factory->method('getLockFactory')->willReturn($lock_factory);
        $item_factory->method('_getItemDao')->willReturn($item_dao);
        $item_factory->method('getRecentlyVisitedDao')->willReturn($visit);
        $lock_factory->method('itemIsLocked');
        $item_dao->method('deleteCutPreferenceForAllUsers');
        $item_dao->method('deleteCopyPreferenceForAllUsers');
        $item_dao->method('updateFromRow');
        $item_dao->method('storeDeletedItem');

        $item_factory->delete($item);

        self::assertTrue($visit->isDeleted());
    }
}
