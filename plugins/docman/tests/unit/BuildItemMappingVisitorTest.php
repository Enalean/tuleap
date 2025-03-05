<?php
/**
 * Copyright (c) Enalean, 2019 - present. All Rights Reserved.
 * Copyright (c) STMicroelectronics, 2008. All Rights Reserved.
 *
 * Originally written by Manuel Vacelet, 2008
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

use Docman_BuildItemMappingVisitor;
use Docman_Folder;
use Docman_ItemDao;
use Docman_PermissionsManager;
use PFUser;
use TestHelper;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class BuildItemMappingVisitorTest extends TestCase
{
    public function testCompareFolderChildrenOk(): void
    {
        // Src (reference)
        $fld140 = new Docman_Folder(['item_id' => 140, 'title' => 'Project documentation', 'rank' => 0]);
        $fld150 = new Docman_Folder(['item_id' => 150, 'title' => 'Folder 1', 'rank' => -2]);
        $fld135 = new Docman_Folder(['item_id' => 135, 'title' => 'Folder 1', 'rank' => -1]);
        $fld140->addItem($fld150);
        $fld140->addItem($fld135);

        // Dst
        $fld36 = new Docman_Folder(['item_id' => 36, 'title' => 'Folder 1', 'rank' => -4]);
        $fld40 = new Docman_Folder(['item_id' => 40, 'title' => 'Folder 1', 'rank' => -2]);
        $node  = new Docman_Folder();
        $node->addItem($fld40);
        $node->addItem($fld36);

        $itemMappingVisitor = new Docman_BuildItemMappingVisitor(569);
        $nodesOk            = $itemMappingVisitor->compareFolderChildren($fld140, $node);
        self::assertEquals([
            150 => true,
            135 => true,
        ], $nodesOk);
        $itemMapping = $itemMappingVisitor->getItemMapping();
        self::assertEquals([
            150 => 36,
            135 => 40,
        ], $itemMapping);
    }

    /**
     * Same test as above (testCompareFolderChildrenOk) but ranks inversion between item 36 & 40  (here 40 appears befor 36).
     */
    public function testCompareFolderChildrenRankIssue(): void
    {
        // Src (reference)
        $fld140 = new Docman_Folder(['item_id' => 140, 'title' => 'Project documentation', 'rank' => 0]);
        $fld150 = new Docman_Folder(['item_id' => 150, 'title' => 'Folder 1', 'rank' => -2]);
        $fld135 = new Docman_Folder(['item_id' => 135, 'title' => 'Folder 1', 'rank' => -1]);
        $fld140->addItem($fld150);
        $fld140->addItem($fld135);

        // Dst
        $fld36 = new Docman_Folder(['item_id' => 36, 'title' => 'Folder 1', 'rank' => -1]);
        $fld40 = new Docman_Folder(['item_id' => 40, 'title' => 'Folder 1', 'rank' => -8]);
        $node  = new Docman_Folder();
        $node->addItem($fld40);
        $node->addItem($fld36);

        $itemMappingVisitor = new Docman_BuildItemMappingVisitor(569);
        $itemMappingVisitor->compareFolderChildren($fld140, $node);
        $itemMapping = $itemMappingVisitor->getItemMapping();
        self::assertEquals([
            150 => 40,
            135 => 36,
        ], $itemMapping);
    }

    /**
     * Test when there are more items in the source tree (reference) than the destination one.
     */
    public function testCompareFolderChildrenMoreSrcThanDst(): void
    {
        // Src (reference)
        $fld140 = new Docman_Folder(['item_id' => 140, 'title' => 'Project documentation', 'rank' => 0]);
        $fld150 = new Docman_Folder(['item_id' => 150, 'title' => 'Folder 1', 'rank' => -2]);
        $fld135 = new Docman_Folder(['item_id' => 135, 'title' => 'Folder 1', 'rank' => -1]);
        $fld136 = new Docman_Folder(['item_id' => 136, 'title' => 'Folder 1', 'rank' => 0]);
        $fld140->addItem($fld150);
        $fld140->addItem($fld135);
        $fld140->addItem($fld136);

        // Dst
        $fld36 = new Docman_Folder(['item_id' => 36, 'title' => 'Folder 1', 'rank' => -4]);
        $fld40 = new Docman_Folder(['item_id' => 40, 'title' => 'Folder 1', 'rank' => -2]);
        $node  = new Docman_Folder();
        $node->addItem($fld40);
        $node->addItem($fld36);

        $itemMappingVisitor = new Docman_BuildItemMappingVisitor(569);
        $nodesOk            = $itemMappingVisitor->compareFolderChildren($fld140, $node);
        self::assertEquals([
            150 => true,
            135 => true,
        ], $nodesOk);
        $itemMapping = $itemMappingVisitor->getItemMapping();
        self::assertEquals([
            150 => 36,
            135 => 40,
        ], $itemMapping);
    }

    /**
     * Test when there are more items in the destination tree than the source one.
     */
    public function testCompareFolderChildrenMoreDstThanSrc(): void
    {
        // Src (reference)
        $fld140 = new Docman_Folder(['item_id' => 140, 'title' => 'Project documentation', 'rank' => 0]);
        $fld150 = new Docman_Folder(['item_id' => 150, 'title' => 'Folder 1', 'rank' => -2]);
        $fld135 = new Docman_Folder(['item_id' => 135, 'title' => 'Folder 1', 'rank' => -1]);
        $fld140->addItem($fld150);
        $fld140->addItem($fld135);

        // Dst
        $fld36 = new Docman_Folder(['item_id' => 36, 'title' => 'Folder 1', 'rank' => -4]);
        $fld40 = new Docman_Folder(['item_id' => 40, 'title' => 'Folder 1', 'rank' => -2]);
        $fld72 = new Docman_Folder(['item_id' => 72, 'title' => 'Folder 1', 'rank' => 5]);
        $node  = new Docman_Folder();
        $node->addItem($fld40);
        $node->addItem($fld36);
        $node->addItem($fld72);

        $itemMappingVisitor = new Docman_BuildItemMappingVisitor(569);
        $nodesOk            = $itemMappingVisitor->compareFolderChildren($fld140, $node);
        self::assertEquals([
            150 => true,
            135 => true,
        ], $nodesOk);
        $itemMapping = $itemMappingVisitor->getItemMapping();
        self::assertEquals([
            150 => 36,
            135 => 40,
        ], $itemMapping);
    }

    /**
     * Test: We want to find the item id mapping for the tree on the left. We
     *      look for matching values in the tree on the right.
     *      ______________________________
     *     _|________                    _|_________
     * 140 Project doc                35 Project doc
     * |-- 150 Folder 1               |-- 36 Folder 1
     * |   |-- 112 Folder 1.1         |   |-- 37 Folder 1.1
     * |   |   `-- 113 Folder 1.1.1   |   |   `-- 38 Folder 1.1.1
     * |   |       `-- *              |   |       `-- *
     * |   `-- 115 Folder 1.2         |   `-- 39 Toto
     * |       `-- *                  |       `-- *
     * `-- 135 Folder 2               `-- 40 Folder 2
     *     `-- *                          `-- *
     *
     * Here is the tree build by Docman_ItemFactory::findPathToRoot(113,115,135);
     * Project documentation (140)
     * |-- Folder 1 (150)
     * |   |-- Folder 1.1 (112)
     * |   |    `-- Folder 1.1.1 (113)
     * |   `-- Folder 1.2 (115)
     * `-- Folder 2 (135)
     */
    public function testSimpleTree(): void
    {
        // Nodes definition
        $fld140 = new Docman_Folder(['item_id' => 140, 'title' => 'Project documentation']);
        $fld150 = new Docman_Folder(['item_id' => 150, 'title' => 'Folder 1']);
        $fld112 = new Docman_Folder(['item_id' => 112, 'title' => 'Folder 1.1']);
        $fld113 = new Docman_Folder(['item_id' => 113, 'title' => 'Folder 1.1.1']);
        $fld115 = new Docman_Folder(['item_id' => 115, 'title' => 'Folder 1.2']);
        $fld135 = new Docman_Folder(['item_id' => 135, 'title' => 'Folder 2']);

        // Build tree
        $fld140->addItem($fld150);
        $fld140->addItem($fld135);
        $fld150->addItem($fld112);
        $fld150->addItem($fld115);
        $fld112->addItem($fld113);

        // Fake DB results
        $mockDao = $this->createMock(Docman_ItemDao::class);

        // Init
        $mockDar0 = TestHelper::arrayToDar(['item_id' => 35, 'title' => 'Project documentation', 'item_type' => PLUGIN_DOCMAN_ITEM_TYPE_FOLDER, 'rank' => 0]);

        // Children of 35
        $mockDar35 = TestHelper::arrayToDar(
            ['item_id' => 36, 'title' => 'Folder 1', 'item_type' => PLUGIN_DOCMAN_ITEM_TYPE_FOLDER, 'rank' => 1],
            ['item_id' => 40, 'title' => 'Folder 2', 'item_type' => PLUGIN_DOCMAN_ITEM_TYPE_FOLDER, 'rank' => 2],
        );

        // Children of 36
        $mockDar36 = TestHelper::arrayToDar(['item_id' => 37, 'title' => 'Folder 1.1', 'item_type' => PLUGIN_DOCMAN_ITEM_TYPE_FOLDER, 'rank' => -2]);

        // Children of 37
        $mockDar37 = TestHelper::arrayToDar(['item_id' => 38, 'title' => 'Folder 1.1.1', 'item_type' => PLUGIN_DOCMAN_ITEM_TYPE_FOLDER, 'rank' => 0]);

        $mockDao->method('searchByTitle')->willReturnCallback(static fn(array $title, int $group_id, int $parent_id) => match (true) {
            $title === ['Project documentation'] && $group_id === 569 && $parent_id === 0     => $mockDar0,
            $title === ['Folder 1', 'Folder 2'] && $group_id === 569 && $parent_id === 35     => $mockDar35,
            $title === ['Folder 1.1', 'Folder 1.2'] && $group_id === 569 && $parent_id === 36 => $mockDar36,
            $title === ['Folder 1.1.1'] && $group_id === 569 && $parent_id === 37             => $mockDar37,
            default                                                                           => TestHelper::emptyDar(),
        });

        // Permissions mock
        $mockDPM = $this->createMock(Docman_PermissionsManager::class);
        $mockDPM->method('userCanRead')->willReturn(true);
        $mockUser = UserTestBuilder::buildWithDefaults();

        $itemMappingVisitor = $this->createPartialMock(Docman_BuildItemMappingVisitor::class, [
            'getItemDao',
            'getPermissionsManager',
            'getCurrentUser',
        ]);
        // Need to init by hand because of fake constructor.
        $itemMappingVisitor->groupId = 569;

        // Attach mocks
        $itemMappingVisitor->method('getItemDao')->willReturn($mockDao);
        $itemMappingVisitor->method('getPermissionsManager')->willReturn($mockDPM);
        $itemMappingVisitor->method('getCurrentUser')->willReturn($mockUser);

        $fld140->accept($itemMappingVisitor);
        $itemMapping = $itemMappingVisitor->getItemMapping();

        self::assertEquals([
            140 => 35,
            150 => 36,
            112 => 37,
            113 => 38,
            135 => 40,
        ], $itemMapping);
    }

    /**
     * Same example, item 40 is not readable
     */
    public function testSimpleTreePermissionDenied(): void
    {
        // Nodes definition
        $fld140 = new Docman_Folder(['item_id' => 140, 'title' => 'Project documentation']);
        $fld150 = new Docman_Folder(['item_id' => 150, 'title' => 'Folder 1']);
        $fld112 = new Docman_Folder(['item_id' => 112, 'title' => 'Folder 1.1']);
        $fld113 = new Docman_Folder(['item_id' => 113, 'title' => 'Folder 1.1.1']);
        $fld115 = new Docman_Folder(['item_id' => 115, 'title' => 'Folder 1.2']);
        $fld135 = new Docman_Folder(['item_id' => 135, 'title' => 'Folder 2']);

        // Build tree
        $fld140->addItem($fld150);
        $fld140->addItem($fld135);
        $fld150->addItem($fld112);
        $fld150->addItem($fld115);
        $fld112->addItem($fld113);

        // Fake DB results
        $mockDao = $this->createMock(Docman_ItemDao::class);

        // Init
        $mockDar0 = TestHelper::arrayToDar(['item_id' => 35, 'title' => 'Project documentation', 'item_type' => PLUGIN_DOCMAN_ITEM_TYPE_FOLDER, 'rank' => 0]);

        // Children of 35
        $mockDar35 = TestHelper::arrayToDar(
            ['item_id' => 36, 'title' => 'Folder 1', 'item_type' => PLUGIN_DOCMAN_ITEM_TYPE_FOLDER, 'rank' => 1],
            ['item_id' => 40, 'title' => 'Folder 2', 'item_type' => PLUGIN_DOCMAN_ITEM_TYPE_FOLDER, 'rank' => 2],
        );

        // Children of 36
        $mockDar36 = TestHelper::arrayToDar(['item_id' => 37, 'title' => 'Folder 1.1', 'item_type' => PLUGIN_DOCMAN_ITEM_TYPE_FOLDER, 'rank' => -2]);

        // Children of 37
        $mockDar37 = TestHelper::arrayToDar(['item_id' => 38, 'title' => 'Folder 1.1.1', 'item_type' => PLUGIN_DOCMAN_ITEM_TYPE_FOLDER, 'rank' => 0]);

        $mockDao->method('searchByTitle')->willReturnCallback(static fn(array $title, int $group_id, int $parent_id) => match (true) {
            $title === ['Project documentation'] && $group_id === 569 && $parent_id === 0     => $mockDar0,
            $title === ['Folder 1', 'Folder 2'] && $group_id === 569 && $parent_id === 35     => $mockDar35,
            $title === ['Folder 1.1', 'Folder 1.2'] && $group_id === 569 && $parent_id === 36 => $mockDar36,
            $title === ['Folder 1.1.1'] && $group_id === 569 && $parent_id === 37             => $mockDar37,
            default                                                                           => TestHelper::emptyDar(),
        });

        // Permissions mock
        $mockUser = UserTestBuilder::buildWithDefaults();
        $mockDPM  = $this->createMock(Docman_PermissionsManager::class);
        // other items are readable
        $mockDPM->method('userCanRead')->with($mockUser)->willReturnCallback(static fn(PFUser $user, int $item_id) => match ($item_id) {
            35, 36, 37, 38 => true,
            40             => false,
        });

        $itemMappingVisitor = $this->createPartialMock(Docman_BuildItemMappingVisitor::class, [
            'getItemDao',
            'getPermissionsManager',
            'getCurrentUser',
        ]);
        // Need to init by hand because of fake constructor.
        $itemMappingVisitor->groupId = 569;

        // Attach mocks
        $itemMappingVisitor->method('getItemDao')->willReturn($mockDao);
        $itemMappingVisitor->method('getPermissionsManager')->willReturn($mockDPM);
        $itemMappingVisitor->method('getCurrentUser')->willReturn($mockUser);

        $fld140->accept($itemMappingVisitor);
        $itemMapping = $itemMappingVisitor->getItemMapping();

        self::assertEquals([
            140 => 35,
            150 => 36,
            112 => 37,
            113 => 38,
        ], $itemMapping);
    }

    /**
     *
     * 140 Project doc                35 Project doc
     * |-- 150 Folder 1               |-- 36 Folder 1
     * |   |-- 112 Folder 1.1         |   |-- 37 Folder 1.1
     * |       `-- *                  |       `-- *
     * `-- 135 Folder 1               `-- 40 Folder 1
     *     `-- 173 Folder test             `-- 56 Folder test
     */
    public function testSeveralFoldersWithSameName(): void
    {
        // Nodes definition
        $fld140 = new Docman_Folder(['item_id' => 140, 'title' => 'Project documentation', 'rank' => 0]);
        $fld150 = new Docman_Folder(['item_id' => 150, 'title' => 'Folder 1', 'rank' => -2]);
        $fld112 = new Docman_Folder(['item_id' => 112, 'title' => 'Folder 1.1', 'rank' => 0]);
        $fld135 = new Docman_Folder(['item_id' => 135, 'title' => 'Folder 1', 'rank' => -1]);
        $fld173 = new Docman_Folder(['item_id' => 173, 'title' => 'Folder test', 'rank' => 0]);

        // Build tree
        $fld140->addItem($fld135);
        $fld140->addItem($fld150);
        $fld150->addItem($fld112);
        $fld135->addItem($fld173);

        // Fake DB results
        $mockDao = $this->createMock(Docman_ItemDao::class);

        // Init
        $mockDar0 = TestHelper::arrayToDar(['item_id' => 35, 'title' => 'Project documentation', 'item_type' => PLUGIN_DOCMAN_ITEM_TYPE_FOLDER, 'rank' => 0]);

        // Children of 35
        $mockDar35 = TestHelper::arrayToDar(
            ['item_id' => 36, 'title' => 'Folder 1', 'item_type' => PLUGIN_DOCMAN_ITEM_TYPE_FOLDER, 'rank' => 1],
            ['item_id' => 40, 'title' => 'Folder 1', 'item_type' => PLUGIN_DOCMAN_ITEM_TYPE_FOLDER, 'rank' => 2],
        );

        // Children of 36
        $mockDar36 = TestHelper::arrayToDar(['item_id' => 37, 'title' => 'Folder 1.1', 'item_type' => PLUGIN_DOCMAN_ITEM_TYPE_FOLDER, 'rank' => -2]);

        // Children of 40
        $mockDar37 = TestHelper::arrayToDar(['item_id' => 56, 'title' => 'Folder test', 'item_type' => PLUGIN_DOCMAN_ITEM_TYPE_FOLDER, 'rank' => 0]);

        $mockDao->method('searchByTitle')->willReturnCallback(static fn(array $title, int $group_id, int $parent_id) => match (true) {
            $title === ['Project documentation'] && $group_id === 569 && $parent_id === 0     => $mockDar0,
            $title === ['Folder 1', 'Folder 1'] && $group_id === 569 && $parent_id === 35     => $mockDar35,
            $title === ['Folder 1.1'] && $group_id === 569 && $parent_id === 36 => $mockDar36,
            $title === ['Folder test'] && $group_id === 569 && $parent_id === 40              => $mockDar37,
            default                                                                           => TestHelper::emptyDar(),
        });

        // Permissions mock
        $mockDPM = $this->createMock(Docman_PermissionsManager::class);
        $mockDPM->method('userCanRead')->willReturn(true);
        $mockUser = UserTestBuilder::buildWithDefaults();

        $itemMappingVisitor = $this->createPartialMock(Docman_BuildItemMappingVisitor::class, [
            'getItemDao',
            'getPermissionsManager',
            'getCurrentUser',
        ]);
        // Need to init by hand because of fake constructor.
        $itemMappingVisitor->groupId = 569;

        // Attach mocks
        $itemMappingVisitor->method('getItemDao')->willReturn($mockDao);
        $itemMappingVisitor->method('getPermissionsManager')->willReturn($mockDPM);
        $itemMappingVisitor->method('getCurrentUser')->willReturn($mockUser);

        $fld140->accept($itemMappingVisitor);
        $itemMapping = $itemMappingVisitor->getItemMapping();

        self::assertEquals([
            140 => 35,
            150 => 36,
            112 => 37,
            135 => 40,
            173 => 56,
        ], $itemMapping);
    }
}
