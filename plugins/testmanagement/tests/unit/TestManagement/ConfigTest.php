<?php
/**
 * Copyright (c) Enalean, 2020 - present. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
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
 *
 */

declare(strict_types=1);

namespace Tuleap\TestManagement;

use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;
use Tuleap\Tracker\Test\Stub\RetrieveTrackerStub;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class ConfigTest extends \Tuleap\Test\PHPUnit\TestCase
{
    public function testItReturnsFalseIfTrackerIdIsNotFoundInProperty(): void
    {
        $project = ProjectTestBuilder::aProject()->withId(101)->build();

        $dao = $this->createMock(Dao::class);
        $dao->method('searchByProjectId')->with(101)->willReturn(\TestHelper::arrayToDar([]));

        $config = new Config($dao, RetrieveTrackerStub::withoutTracker());
        $this->assertFalse($config->getCampaignTrackerId($project));
    }

    public function testItReturnsFalseIfTrackerIsDeleted(): void
    {
        $project = ProjectTestBuilder::aProject()->withId(101)->build();

        $properties = [
            'project_id' => 101,
            'campaign_tracker_id' => 10,
            'test_definition_tracker_id' => 11,
            'test_execution_tracker_id' => 12,
            'issue_tracker_id' => 13,
        ];
        $dar        = \TestHelper::arrayToDar($properties);
        $dao        = $this->createMock(Dao::class);
        $dao->method('searchByProjectId')->with(101)->willReturn($dar);

        $tracker = TrackerTestBuilder::aTracker()->withDeletionDate(1234567890)->build();

        $config = new Config($dao, RetrieveTrackerStub::withTracker($tracker));
        $this->assertFalse($config->getCampaignTrackerId($project));
    }

    public function testItReturnsTheTrackerId(): void
    {
        $project = ProjectTestBuilder::aProject()->withId(101)->build();

        $properties = [
            'project_id' => 101,
            'campaign_tracker_id' => 10,
            'test_definition_tracker_id' => 11,
            'test_execution_tracker_id' => 12,
            'issue_tracker_id' => 13,
        ];
        $dar        = \TestHelper::arrayToDar($properties);
        $dao        = $this->createMock(Dao::class);
        $dao->method('searchByProjectId')->with(101)->willReturn($dar);

        $tracker = TrackerTestBuilder::aTracker()->withId(10)->build();

        $config = new Config($dao, RetrieveTrackerStub::withTracker($tracker));
        $this->assertEquals(10, $config->getCampaignTrackerId($project));
    }

    public function testItDoesNotReturnTheTrackerIdIfTrackerIsDeleted(): void
    {
        $project = ProjectTestBuilder::aProject()->withId(101)->build();

        $properties = [
            'project_id' => 101,
            'campaign_tracker_id' => 10,
            'test_definition_tracker_id' => 11,
            'test_execution_tracker_id' => 12,
            'issue_tracker_id' => 13,
        ];
        $dar        = \TestHelper::arrayToDar($properties);
        $dao        = $this->createMock(Dao::class);
        $dao->method('searchByProjectId')->with(101)->willReturn($dar);

        $tracker = TrackerTestBuilder::aTracker()->withId(10)->withDeletionDate(1234567890)->build();

        $config = new Config($dao, RetrieveTrackerStub::withTracker($tracker));
        $this->assertFalse($config->getCampaignTrackerId($project));
    }
}
