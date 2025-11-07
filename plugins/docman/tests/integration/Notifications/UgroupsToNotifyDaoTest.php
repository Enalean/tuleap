<?php
/**
 * Copyright (c) Enalean, 2025 - Present. All Rights Reserved.
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

declare(strict_types=1);

namespace Tuleap\Docman\Notifications;

use Tuleap\DB\Compat\Legacy2018\LegacyDataAccessResultInterface;
use Tuleap\DB\DBFactory;
use Tuleap\Docman\Test\Builders\DocmanDatabaseBuilder;
use Tuleap\Test\Builders\CoreDatabaseBuilder;
use Tuleap\Test\PHPUnit\TestIntegrationTestCase;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class UgroupsToNotifyDaoTest extends TestIntegrationTestCase
{
    private const int PROJECT_ID = 102;
    private const int ITEM_ID    = 52;
    private const int UGROUP_ID  = 3;
    private const string TYPE    = PLUGIN_DOCMAN_NOTIFICATION;
    private UgroupsToNotifyDao $dao;

    #[\Override]
    protected function setUp(): void
    {
        $this->dao = new UgroupsToNotifyDao();
    }

    public function testCRUD(): void
    {
        // Test create
        $this->assertItRetrievesNothing();
        $this->dao->create(self::ITEM_ID, self::UGROUP_ID, self::TYPE);

        // Test searchUGroupByUGroupIdAndItemIdAndType
        $dar = $this->dao->searchUGroupByUGroupIdAndItemIdAndType(self::ITEM_ID, self::UGROUP_ID, self::TYPE);
        self::assertInstanceOf(LegacyDataAccessResultInterface::class, $dar);
        self::assertSame(1, $dar->rowCount());
        $row = $dar->getRow();
        self::assertNotFalse($row);
        self::assertSame((string) self::ITEM_ID, $row['item_id']);
        self::assertSame((string) self::UGROUP_ID, $row['ugroup_id']);
        self::assertSame(self::TYPE, $row['type']);
        self::assertFalse($dar->getRow());

        // Test delete
        $this->dao->delete(self::ITEM_ID, self::UGROUP_ID, self::TYPE);
        $this->assertItRetrievesNothing();
    }

    public function testOperationsWithUgroups(): void
    {
        $db             = DBFactory::getMainTuleapDBConnection()->getDB();
        $core_builder   = new CoreDatabaseBuilder($db);
        $docman_builder = new DocmanDatabaseBuilder($db);

        $user_group_A_id = $core_builder->buildStaticUserGroup(self::PROJECT_ID, 'A');
        $user_group_B_id = $core_builder->buildStaticUserGroup(self::PROJECT_ID, 'B');
        $docman_builder->buildItem(self::ITEM_ID, self::PROJECT_ID);

        $this->assertItRetrievesNothing();
        $this->dao->create(self::ITEM_ID, $user_group_A_id, self::TYPE);
        $this->dao->create(self::ITEM_ID, $user_group_B_id, self::TYPE);

        // Test searchUgroupsByItemIdAndType
        $dar = $this->dao->searchUgroupsByItemIdAndType(self::ITEM_ID, self::TYPE);
        self::assertInstanceOf(LegacyDataAccessResultInterface::class, $dar);
        self::assertSame(2, $dar->rowCount());
        $row = $dar->getRow();
        self::assertNotFalse($row);
        self::assertSame((string) $user_group_A_id, $row['ugroup_id']);
        self::assertSame('A', $row['name']);
        self::assertSame((string) self::PROJECT_ID, $row['group_id']);

        // Test deleteByUgroupId
        $this->dao->deleteByUgroupId(self::PROJECT_ID, $user_group_A_id);
        $dar = $this->dao->searchUgroupsByItemIdAndType(self::ITEM_ID, self::TYPE);
        self::assertInstanceOf(LegacyDataAccessResultInterface::class, $dar);
        self::assertSame(1, $dar->rowCount());
        $row = $dar->getRow();
        self::assertNotFalse($row);
        self::assertSame((string) $user_group_B_id, $row['ugroup_id']);
        self::assertSame('B', $row['name']);
        self::assertSame((string) self::PROJECT_ID, $row['group_id']);

        // Test deleteByItemId
        $this->dao->deleteByItemId(self::ITEM_ID);
        $this->assertItRetrievesNothing();
    }

    private function assertItRetrievesNothing(): void
    {
        $dar = $this->dao->searchUgroupsByItemIdAndType(self::ITEM_ID, self::TYPE);
        self::assertCount(0, $dar);
    }
}
