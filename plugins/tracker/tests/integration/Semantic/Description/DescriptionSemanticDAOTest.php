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

namespace Tuleap\Tracker\Semantic\Description;

use Tuleap\DB\DBFactory;
use Tuleap\Test\PHPUnit\TestIntegrationTestCase;
use Tuleap\Tracker\Test\Builders\TrackerDatabaseBuilder;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class DescriptionSemanticDAOTest extends TestIntegrationTestCase
{
    private const TRACKER_ID = 60;
    private const FIELD_ID   = 2404;
    private DescriptionSemanticDAO $dao;

    protected function setUp(): void
    {
        $this->dao = new DescriptionSemanticDAO();
    }

    public function testCRUD(): void
    {
        $this->assertItRetrievesNothing();
        $this->dao->save(self::TRACKER_ID, self::FIELD_ID);

        // Retrieve what we just saved
        self::assertSame(self::FIELD_ID, $this->dao->searchByTrackerId(self::TRACKER_ID)->unwrapOr(0));

        $other_field_id = 3144;
        $this->dao->save(self::TRACKER_ID, $other_field_id);
        self::assertSame($other_field_id, $this->dao->searchByTrackerId(self::TRACKER_ID)->unwrapOr(0));

        $this->dao->deleteForTracker(self::TRACKER_ID);
        // Do not retrieve what we just deleted
        $this->assertItRetrievesNothing();
    }

    private function assertItRetrievesNothing(): void
    {
        self::assertTrue($this->dao->searchByTrackerId(self::TRACKER_ID)->isNothing());
    }

    public function testDuplication(): void
    {
        $this->dao->save(self::TRACKER_ID, self::FIELD_ID);

        $factory       = new TrackerSemanticDescriptionFactory();
        $to_tracker_id = 868;
        $to_field_id   = 9541;
        $factory->duplicate(self::TRACKER_ID, $to_tracker_id, [
            ['from' => self::FIELD_ID, 'to' => $to_field_id],
        ]);

        self::assertSame($to_field_id, $this->dao->searchByTrackerId($to_tracker_id)->unwrapOr(0));
    }

    public function testOperationsOnSeveralTrackers(): void
    {
        $db              = DBFactory::getMainTuleapDBConnection()->getDB();
        $tracker_builder = new TrackerDatabaseBuilder($db);

        $activities_tracker = $tracker_builder->buildTracker(141, 'Activities');
        $tasks_tracker      = $tracker_builder->buildTracker(141, 'Tasks');
        $activities_id      = $activities_tracker->getId();
        $tasks_id           = $tasks_tracker->getId();

        // It finds the two trackers because they do not have description semantic set
        self::assertSame(2, $this->dao->countTrackersWithoutDescriptionSemantic([
            $activities_id,
            $tasks_id,
        ]));
        self::assertEqualsCanonicalizing(
            [$activities_id, $tasks_id],
            $this->dao->getTrackerIdsWithoutDescriptionSemantic([$activities_id, $tasks_id])
        );

        $this->dao->save($activities_id, 4075);
        $this->dao->save($tasks_id, 9398);

        // It finds zero tracker now that they have the description semantic set
        self::assertSame(0, $this->dao->countTrackersWithoutDescriptionSemantic([
            $activities_id,
            $tasks_id,
        ]));
        self::assertEmpty($this->dao->getTrackerIdsWithoutDescriptionSemantic([$activities_id, $tasks_id]));
    }
}
