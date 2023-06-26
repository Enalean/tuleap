<?php
/**
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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

namespace Tuleap\Taskboard\Tracker;

use Cardwall_OnTop_Config;
use Cardwall_OnTop_ConfigFactory;
use PHPUnit\Framework\MockObject\MockObject;
use Planning;
use Planning_Milestone;
use Tracker;
use Tuleap\Tracker\Test\Builders\ArtifactTestBuilder;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;

final class TrackerCollectionRetrieverTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private TrackerCollectionRetriever $retriever;
    private Cardwall_OnTop_ConfigFactory&MockObject $config_factory;

    protected function setUp(): void
    {
        $this->config_factory = $this->createMock(Cardwall_OnTop_ConfigFactory::class);
        $this->retriever      = new TrackerCollectionRetriever($this->config_factory);
    }

    public function testGetTrackersForMilestoneReturnsEmptyCollectionWhenNoConfig(): void
    {
        $planning          = $this->createMock(Planning::class);
        $milestone_tracker = TrackerTestBuilder::aTracker()->build();
        $milestone         = $this->mockMilestone($planning, $milestone_tracker);
        $this->config_factory
            ->expects(self::once())
            ->method('getOnTopConfigByPlanning')
            ->with($planning)
            ->willReturn(null);

        $result      = $this->retriever->getTrackersForMilestone($milestone);
        $tracker_ids = $result->map(
            function (TaskboardTracker $tracker) {
                return $tracker->getTrackerId();
            }
        );
        self::assertCount(0, $tracker_ids);
    }

    public function testGetTrackersForMilestoneReturnsEmptyCollectionWhenNoTrackers(): void
    {
        $planning          = $this->createMock(Planning::class);
        $milestone_tracker = $this->buildTracker(1254);
        $milestone         = $this->mockMilestone($planning, $milestone_tracker);
        $empty_config      = $this->mockConfig($planning);
        $empty_config
            ->expects(self::once())
            ->method('getTrackers')
            ->willReturn([]);

        $result      = $this->retriever->getTrackersForMilestone($milestone);
        $tracker_ids = $result->map(
            function (TaskboardTracker $tracker) {
                return $tracker->getTrackerId();
            }
        );
        self::assertSame(0, count($tracker_ids));
    }

    public function testGetTrackersForMilestoneReturnsTrackerCollection(): void
    {
        $planning          = $this->createMock(Planning::class);
        $milestone_tracker = $this->buildTracker(1254);
        $milestone         = $this->mockMilestone($planning, $milestone_tracker);
        $first_tracker     = $this->buildTracker(39);
        $second_tracker    = $this->buildTracker(98);
        $config            = $this->mockConfig($planning);
        $config
            ->expects(self::once())
            ->method('getTrackers')
            ->willReturn([$first_tracker, $second_tracker]);

        $result      = $this->retriever->getTrackersForMilestone($milestone);
        $tracker_ids = $result->map(
            function (TaskboardTracker $tracker) {
                return $tracker->getTrackerId();
            }
        );
        self::assertEquals(['39', '98'], $tracker_ids);
    }

    private function mockConfig(Planning $planning): Cardwall_OnTop_Config&MockObject
    {
        $config = $this->createMock(Cardwall_OnTop_Config::class);
        $this->config_factory
            ->expects(self::once())
            ->method('getOnTopConfigByPlanning')
            ->with($planning)
            ->willReturn($config);

        return $config;
    }

    private function mockMilestone(Planning $planning, Tracker $milestone_tracker): MockObject&Planning_Milestone
    {
        $milestone = $this->createMock(Planning_Milestone::class);
        $milestone
            ->method('getPlanning')
            ->willReturn($planning);
        $milestone->method('getArtifact')
            ->willReturn(ArtifactTestBuilder::anArtifact(1)->inTracker($milestone_tracker)->build());
        return $milestone;
    }

    private function buildTracker(int $tracker_id): Tracker
    {
        return TrackerTestBuilder::aTracker()->withId($tracker_id)->build();
    }
}
