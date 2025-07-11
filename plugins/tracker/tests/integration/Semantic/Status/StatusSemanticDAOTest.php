<?php
/**
 * Copyright (c) Enalean, 2025-Present. All Rights Reserved.
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

namespace Tuleap\Tracker\Semantic\Status;

use PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles;
use Tuleap\DB\DBFactory;
use Tuleap\Test\Builders\CoreDatabaseBuilder;
use Tuleap\Test\PHPUnit\TestIntegrationTestCase;
use Tuleap\Tracker\Test\Builders\TrackerDatabaseBuilder;

#[DisableReturnValueGenerationForTestDoubles]
final class StatusSemanticDAOTest extends TestIntegrationTestCase
{
    private StatusSemanticDAO $dao;

    protected function setUp(): void
    {
        $this->dao = new StatusSemanticDAO();
    }

    public function testCRUD(): void
    {
        $db              = DBFactory::getMainTuleapDBConnection()->getDB();
        $core_builder    = new CoreDatabaseBuilder($db);
        $tracker_builder = new TrackerDatabaseBuilder($db);

        $project_id = (int) $core_builder->buildProject('MyProject')->getID();
        $tracker_a  = $tracker_builder->buildTracker($project_id, 'Tracker A')->getId();
        $tracker_b  = $tracker_builder->buildTracker($project_id, 'Tracker B')->getId();
        $field_id   = 7854;

        // At the start, there is nothing
        self::assertTrue($this->dao->searchFieldByTrackerId($tracker_a)->isNothing());
        self::assertTrue($this->dao->searchFieldByTrackerId($tracker_b)->isNothing());
        self::assertEmpty($this->dao->searchOpenValuesByFieldId($field_id));

        // Save for Tracker A
        $open_values = [12, 13, 14, 85];
        $this->dao->save($tracker_a, $field_id, $open_values);
        self::assertSame($field_id, $this->dao->searchFieldByTrackerId($tracker_a)->unwrapOr(null));
        self::assertEqualsCanonicalizing($open_values, $this->dao->searchOpenValuesByFieldId($field_id));
        self::assertTrue($this->dao->searchFieldByTrackerId($tracker_b)->isNothing());

        self::assertSame(1, $this->dao->getNbOfTrackerWithoutSemanticStatusDefined([$tracker_a, $tracker_b]));
        self::assertSame([$tracker_b], $this->dao->getTrackerIdsWithoutSemanticStatusDefined([$tracker_a, $tracker_b]));

        // After delete, there is nothing
        $this->dao->delete($tracker_a);
        self::assertTrue($this->dao->searchFieldByTrackerId($tracker_a)->isNothing());
        self::assertEmpty($this->dao->searchOpenValuesByFieldId($field_id));
    }
}
