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

namespace Tuleap\Timetracking\Widget\Management;

use PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles;
use Tuleap\Test\PHPUnit\TestIntegrationTestCase;
use Tuleap\Timetracking\REST\v1\TimetrackingManagement\PredefinedTimePeriod;
use Tuleap\Timetracking\REST\v1\TimetrackingManagement\UserList;

#[DisableReturnValueGenerationForTestDoubles]
final class ManagementDaoTest extends TestIntegrationTestCase
{
    private const PERIOD = PredefinedTimePeriod::LAST_7_DAYS;

    public function testDeletionOfQueryShouldDeleteUsersAsWell(): void
    {
        $dao    = new ManagementDao();
        $query1 = $dao->create(self::PERIOD);
        $query2 = $dao->create(self::PERIOD);
        $dao->saveQueryWithPredefinedTimePeriod($query1, self::PERIOD, new UserList([101]));
        $dao->saveQueryWithPredefinedTimePeriod($query2, self::PERIOD, new UserList([101, 102]));

        self::assertNotNull($dao->searchQueryById($query1));
        self::assertNotNull($dao->searchQueryById($query2));
        self::assertSame([101], $dao->searchUsersByQueryId($query1));
        self::assertSame([101, 102], $dao->searchUsersByQueryId($query2));

        $dao->delete($query2);

        self::assertNotNull($dao->searchQueryById($query1));
        self::assertNull($dao->searchQueryById($query2));
        self::assertSame([101], $dao->searchUsersByQueryId($query1));
        self::assertSame([], $dao->searchUsersByQueryId($query2));
    }

    public function testSaveQueryWithPredefinedTimePeriod(): void
    {
        $dao    = new ManagementDao();
        $query1 = $dao->create(self::PERIOD);
        $query2 = $dao->create(self::PERIOD);
        $dao->saveQueryWithPredefinedTimePeriod($query1, self::PERIOD, new UserList([101]));
        $dao->saveQueryWithPredefinedTimePeriod($query2, self::PERIOD, new UserList([101, 102]));

        self::assertSame([101], $dao->searchUsersByQueryId($query1));
        self::assertSame([101, 102], $dao->searchUsersByQueryId($query2));

        $dao->saveQueryWithPredefinedTimePeriod($query1, self::PERIOD, new UserList([102, 103]));

        self::assertSame([102, 103], $dao->searchUsersByQueryId($query1));
        self::assertSame([101, 102], $dao->searchUsersByQueryId($query2));
    }

    public function testSaveQueryWithDates(): void
    {
        $now = new \DateTimeImmutable();

        $dao    = new ManagementDao();
        $query1 = $dao->create(self::PERIOD);
        $query2 = $dao->create(self::PERIOD);
        $dao->saveQueryWithDates($query1, $now, $now, new UserList([101]));
        $dao->saveQueryWithDates($query2, $now, $now, new UserList([101, 102]));

        self::assertSame([101], $dao->searchUsersByQueryId($query1));
        self::assertSame([101, 102], $dao->searchUsersByQueryId($query2));

        $dao->saveQueryWithDates($query1, $now, $now, new UserList([102, 103]));

        self::assertSame([102, 103], $dao->searchUsersByQueryId($query1));
        self::assertSame([101, 102], $dao->searchUsersByQueryId($query2));
    }
}
