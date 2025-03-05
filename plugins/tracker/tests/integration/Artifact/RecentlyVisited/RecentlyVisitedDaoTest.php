<?php
/**
 * Copyright (c) Enalean, 2023-Present. All Rights Reserved.
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


namespace Tuleap\Tracker\Artifact\RecentlyVisited;

use Tuleap\Test\PHPUnit\TestIntegrationTestCase;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class RecentlyVisitedDaoTest extends TestIntegrationTestCase
{
    public function testDeleteOldEntriesPerUser(): void
    {
        $dao = new RecentlyVisitedDao();
        $i   = 1;
        while ($i <= 60) {
            $dao->save(102, $i, $i);
            $dao->save(103, $i, $i);
            $i++;
        }
        self::assertCount(60, $dao->searchVisitByUserId(102, 100));
        self::assertCount(60, $dao->searchVisitByUserId(103, 100));

        $dao->deleteOldVisits();

        self::assertCount(30, $dao->searchVisitByUserId(102, 30));
        self::assertCount(30, $dao->searchVisitByUserId(103, 30));
    }
}
