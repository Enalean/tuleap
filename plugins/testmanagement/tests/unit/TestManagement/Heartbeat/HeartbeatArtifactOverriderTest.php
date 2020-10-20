<?php
/**
 * Copyright (c) Enalean, 2020 - Present. All Rights Reserved.
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

namespace Tuleap\TestManagement\Heartbeat;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Project;
use Tuleap\TestManagement\Config;
use Tuleap\Tracker\Artifact\Heartbeat\OverrideArtifactsInFavourOfAnOther;

final class HeartbeatArtifactOverriderTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|\Tracker_Artifact
     */
    private $artifact;

    /**
     * @var HeartbeatArtifactOverrider
     */
    private $artifact_overrider;

    /**
     * @var OverrideArtifactsInFavourOfAnOther
     */
    private $event;

    protected function setUp(): void
    {
        $user    = \Mockery::mock(\PFUser::class);
        $project = \Mockery::mock(Project::class);

        $this->artifact = \Mockery::mock(\Tracker_Artifact::class);
        $this->artifact->shouldReceive('getId')->andReturn(10);
        $artifact_list  = [$this->artifact];
        $this->event    = new OverrideArtifactsInFavourOfAnOther($artifact_list, $user, $project);

        $this->artifact_overrider = new HeartbeatArtifactOverrider();
    }

    public function testItDoesNotOverrideArtifactWhenTrackerIsNotATestExecution(): void
    {
        $campaign_tracker_id       = 100;
        $test_execution_tracker_id = 200;
        $config                    = \Mockery::mock(Config::class);
        $config->shouldReceive('getCampaignTrackerId')->andReturn($campaign_tracker_id);
        $config->shouldReceive('getTestExecutionTrackerId')->andReturn($test_execution_tracker_id);

        $this->artifact->shouldReceive('getTrackerId')->andReturn(1000);

        $this->artifact_overrider->overrideArtifacts($config, $this->event);

        $this->assertEquals([10 => $this->artifact], $this->event->getOverriddenArtifacts());
    }

    public function testItRemovesTestExecutionArtifact(): void
    {
        $campaign_tracker_id       = 100;
        $test_execution_tracker_id = 200;
        $config                    = \Mockery::mock(Config::class);
        $config->shouldReceive('getCampaignTrackerId')->andReturn($campaign_tracker_id);
        $config->shouldReceive('getTestExecutionTrackerId')->andReturn($test_execution_tracker_id);

        $this->artifact->shouldReceive('getTrackerId')->andReturn($test_execution_tracker_id);

        $this->artifact_overrider->overrideArtifacts($config, $this->event);

        $this->assertEquals([], $this->event->getOverriddenArtifacts());
    }

    public function testItRemovesTestCampaignArtifact(): void
    {
        $campaign_tracker_id       = 100;
        $test_execution_tracker_id = 200;
        $config                    = \Mockery::mock(Config::class);
        $config->shouldReceive('getCampaignTrackerId')->andReturn($campaign_tracker_id);
        $config->shouldReceive('getTestExecutionTrackerId')->andReturn($test_execution_tracker_id);

        $this->artifact->shouldReceive('getTrackerId')->andReturn($campaign_tracker_id);

        $this->artifact_overrider->overrideArtifacts($config, $this->event);

        $this->assertEquals([], $this->event->getOverriddenArtifacts());
    }
}
