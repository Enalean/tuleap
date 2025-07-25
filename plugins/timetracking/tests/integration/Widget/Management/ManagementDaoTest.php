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
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestIntegrationTestCase;
use Tuleap\Timetracking\REST\v1\TimetrackingManagement\PredefinedTimePeriod;
use Tuleap\Timetracking\REST\v1\TimetrackingManagement\UserList;

#[DisableReturnValueGenerationForTestDoubles]
final class ManagementDaoTest extends TestIntegrationTestCase
{
    private const PERIOD = PredefinedTimePeriod::LAST_7_DAYS;

    private const ALICE_ID   = 101;
    private const BOB_ID     = 102;
    private const CHARLIE_ID = 103;
    private \PFUser $alice;
    private \PFUser $bob;
    private \PFUser $charlie;

    #[\Override]
    protected function setUp(): void
    {
        $this->alice   = UserTestBuilder::aUser()->withId(self::ALICE_ID)->build();
        $this->bob     = UserTestBuilder::aUser()->withId(self::BOB_ID)->build();
        $this->charlie = UserTestBuilder::aUser()->withId(self::CHARLIE_ID)->build();
    }

    public function testDeletionOfQueryShouldDeleteUsersAsWell(): void
    {
        $dao    = new ManagementDao();
        $query1 = $dao->create(self::PERIOD);
        $query2 = $dao->create(self::PERIOD);
        $dao->saveQueryWithPredefinedTimePeriod($query1, self::PERIOD, new UserList([$this->alice], [], []));
        $dao->saveQueryWithPredefinedTimePeriod($query2, self::PERIOD, new UserList([$this->alice, $this->bob], [], []));

        self::assertNotNull($dao->searchQueryById($query1));
        self::assertNotNull($dao->searchQueryById($query2));
        self::assertSame([self::ALICE_ID], $dao->searchUsersByQueryId($query1));
        self::assertSame([self::ALICE_ID, self::BOB_ID], $dao->searchUsersByQueryId($query2));

        $dao->delete($query2);

        self::assertNotNull($dao->searchQueryById($query1));
        self::assertNull($dao->searchQueryById($query2));
        self::assertSame([self::ALICE_ID], $dao->searchUsersByQueryId($query1));
        self::assertSame([], $dao->searchUsersByQueryId($query2));
    }

    public function testSaveQueryWithPredefinedTimePeriod(): void
    {
        $dao    = new ManagementDao();
        $query1 = $dao->create(self::PERIOD);
        $query2 = $dao->create(self::PERIOD);
        $dao->saveQueryWithPredefinedTimePeriod($query1, self::PERIOD, new UserList([$this->alice], [], []));
        $dao->saveQueryWithPredefinedTimePeriod($query2, self::PERIOD, new UserList([$this->alice, $this->bob], [], []));

        self::assertSame([self::ALICE_ID], $dao->searchUsersByQueryId($query1));
        self::assertSame([self::ALICE_ID, self::BOB_ID], $dao->searchUsersByQueryId($query2));

        $dao->saveQueryWithPredefinedTimePeriod($query1, self::PERIOD, new UserList([$this->bob, $this->charlie], [], []));

        self::assertSame([self::BOB_ID, self::CHARLIE_ID], $dao->searchUsersByQueryId($query1));
        self::assertSame([self::ALICE_ID, self::BOB_ID], $dao->searchUsersByQueryId($query2));
    }

    public function testSaveQueryWithDates(): void
    {
        $now = new \DateTimeImmutable();

        $dao    = new ManagementDao();
        $query1 = $dao->create(self::PERIOD);
        $query2 = $dao->create(self::PERIOD);
        $dao->saveQueryWithDates($query1, $now, $now, new UserList([$this->alice], [], []));
        $dao->saveQueryWithDates($query2, $now, $now, new UserList([$this->alice, $this->bob], [], []));

        self::assertSame([self::ALICE_ID], $dao->searchUsersByQueryId($query1));
        self::assertSame([self::ALICE_ID, self::BOB_ID], $dao->searchUsersByQueryId($query2));

        $dao->saveQueryWithDates($query1, $now, $now, new UserList([$this->bob, $this->charlie], [], []));

        self::assertSame([self::BOB_ID, self::CHARLIE_ID], $dao->searchUsersByQueryId($query1));
        self::assertSame([self::ALICE_ID, self::BOB_ID], $dao->searchUsersByQueryId($query2));
    }
}
