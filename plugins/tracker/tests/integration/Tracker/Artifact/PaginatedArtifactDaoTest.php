<?php
/**
 * Copyright (c) Enalean, 2023 - Present. All Rights Reserved.
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

namespace Tuleap\Tracker\Artifact;

use Tuleap\DB\DBFactory;
use Tuleap\Test\PHPUnit\TestCase;

final class PaginatedArtifactDaoTest extends TestCase
{
    protected function setUp(): void
    {
        $db = DBFactory::getMainTuleapDBConnection()->getDB();

        $db->run("INSERT INTO tracker(id) VALUES (101)");
        $db->run("INSERT INTO tracker(id) VALUES (102)");
        $db->run("INSERT INTO tracker(id) VALUES (103)");

        $db->run("INSERT INTO tracker_artifact(id, tracker_id) VALUES (1, 101)");
        $db->run("INSERT INTO tracker_artifact(id, tracker_id) VALUES (2, 102)");
        $db->run("INSERT INTO tracker_artifact(id, tracker_id) VALUES (3, 103)");
        $db->run("INSERT INTO tracker_artifact(id, tracker_id) VALUES (4, 101)");
    }

    protected function tearDown(): void
    {
        $db = DBFactory::getMainTuleapDBConnection()->getDB();
        $db->run("DELETE FROM tracker_artifact");
        $db->run("DELETE FROM tracker");
    }

    public function testSearchPaginatedByListOfTrackerIds(): void
    {
        $dao     = new PaginatedArtifactDao();
        $results = $dao->searchPaginatedByListOfTrackerIds([101, 102], 1, 0);

        self::assertEquals(1, $results[0]['id']);
        self::assertEquals(3, $dao->foundRows());
    }

    public function testSearchPaginatedByListOfArtifactIds(): void
    {
        $dao     = new PaginatedArtifactDao();
        $results = $dao->searchPaginatedByListOfArtifactIds([1, 4], 1, 0);

        self::assertEquals(1, $results[0]['id']);
        self::assertEquals(2, $dao->foundRows());
    }
}
