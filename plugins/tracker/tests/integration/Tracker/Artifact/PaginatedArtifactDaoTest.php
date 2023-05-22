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
    private int $stories_id;
    private int $tasks_id;
    private int $bugs_id;

    protected function setUp(): void
    {
        $db = DBFactory::getMainTuleapDBConnection()->getDB();

        $this->stories_id = (int) $db->insertReturnId('tracker', []);
        $this->tasks_id   = (int) $db->insertReturnId('tracker', []);
        $this->bugs_id    = (int) $db->insertReturnId('tracker', []);

        $db->run("INSERT INTO tracker_artifact(id, tracker_id) VALUES (1, ?)", $this->stories_id);
        $db->run("INSERT INTO tracker_artifact(id, tracker_id) VALUES (2, ?)", $this->tasks_id);
        $db->run("INSERT INTO tracker_artifact(id, tracker_id) VALUES (3, ?)", $this->bugs_id);
        $db->run("INSERT INTO tracker_artifact(id, tracker_id) VALUES (4, ?)", $this->stories_id);
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
        $results = $dao->searchPaginatedByListOfTrackerIds([$this->stories_id, $this->tasks_id], 1, 0);

        self::assertEquals(1, $results->artifact_rows[0]['id']);
        self::assertEquals(3, $results->total_size);
    }

    public function testSearchPaginatedByListOfArtifactIds(): void
    {
        $dao     = new PaginatedArtifactDao();
        $results = $dao->searchPaginatedByListOfArtifactIds([1, 4], 1, 0);

        self::assertEquals(1, $results->artifact_rows[0]['id']);
        self::assertEquals(2, $results->total_size);
    }
}
