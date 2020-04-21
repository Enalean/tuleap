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
use Mockery as M;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Planning;
use Planning_Milestone;
use Tracker;

final class TrackerCollectionRetrieverTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /** @var TrackerCollectionRetriever */
    private $retriever;
    /** @var Cardwall_OnTop_ConfigFactory|M\LegacyMockInterface|M\MockInterface */
    private $config_factory;

    protected function setUp(): void
    {
        $this->config_factory = M::mock(Cardwall_OnTop_ConfigFactory::class);
        $this->retriever      = new TrackerCollectionRetriever($this->config_factory);
    }

    public function testGetTrackersForMilestoneReturnsEmptyCollectionWhenNoConfig(): void
    {
        $planning          = M::mock(Planning::class);
        $milestone_tracker = M::mock(Tracker::class);
        $milestone         = $this->mockMilestone($planning, $milestone_tracker);
        $this->config_factory->shouldReceive('getOnTopConfigByPlanning')
            ->with($planning)
            ->once()
            ->andReturnNull();

        $result = $this->retriever->getTrackersForMilestone($milestone);
        $tracker_ids = $result->map(
            function (TaskboardTracker $tracker) {
                return $tracker->getTrackerId();
            }
        );
        $this->assertSame(0, count($tracker_ids));
    }

    public function testGetTrackersForMilestoneReturnsEmptyCollectionWhenNoTrackers(): void
    {
        $planning          = M::mock(Planning::class);
        $milestone_tracker = M::mock(Tracker::class);
        $milestone         = $this->mockMilestone($planning, $milestone_tracker);
        $empty_config      = $this->mockConfig($planning);
        $empty_config->shouldReceive('getTrackers')
            ->once()
            ->andReturn([]);

        $result = $this->retriever->getTrackersForMilestone($milestone);
        $tracker_ids = $result->map(
            function (TaskboardTracker $tracker) {
                return $tracker->getTrackerId();
            }
        );
        $this->assertSame(0, count($tracker_ids));
    }

    public function testGetTrackersForMilestoneReturnsTrackerCollection(): void
    {
        $planning          = M::mock(Planning::class);
        $milestone_tracker = M::mock(Tracker::class);
        $milestone         = $this->mockMilestone($planning, $milestone_tracker);
        $first_tracker     = $this->mockTracker('39');
        $second_tracker    = $this->mockTracker('98');
        $config            = $this->mockConfig($planning);
        $config->shouldReceive('getTrackers')
            ->once()
            ->andReturn([$first_tracker, $second_tracker]);

        $result      = $this->retriever->getTrackersForMilestone($milestone);
        $tracker_ids = $result->map(
            function (TaskboardTracker $tracker) {
                return $tracker->getTrackerId();
            }
        );
        $this->assertEquals(['39', '98'], $tracker_ids);
    }

    /**
     * @return Cardwall_OnTop_Config|M\LegacyMockInterface|M\MockInterface
     */
    private function mockConfig(Planning $planning)
    {
        $config = M::mock(Cardwall_OnTop_Config::class);
        $this->config_factory->shouldReceive('getOnTopConfigByPlanning')
            ->with($planning)
            ->once()
            ->andReturn($config);
        return $config;
    }

    /**
     * @return M\LegacyMockInterface|M\MockInterface|Planning_Milestone
     */
    private function mockMilestone(Planning $planning, Tracker $milestone_tracker)
    {
        $milestone = M::mock(Planning_Milestone::class);
        $milestone
            ->shouldReceive('getPlanning')
            ->andReturn($planning);
        $milestone->shouldReceive('getArtifact')
            ->andReturn(
                M::mock(\Tracker_Artifact::class)->shouldReceive(['getTracker' => $milestone_tracker])
                    ->getMock()
            );
        return $milestone;
    }

    /**
     * @return M\LegacyMockInterface|M\MockInterface|Tracker
     */
    private function mockTracker(string $tracker_id)
    {
        $tracker = M::mock(\Tracker::class);
        $tracker->shouldReceive('getId')->andReturn($tracker_id);
        return $tracker;
    }
}
