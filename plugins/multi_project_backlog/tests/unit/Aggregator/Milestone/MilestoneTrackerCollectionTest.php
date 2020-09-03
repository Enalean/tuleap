<?php
/**
 * Copyright (c) Enalean, 2020-Present. All Rights Reserved.
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

namespace Tuleap\MultiProjectBacklog\Aggregator\Milestone;

use Mockery as M;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tuleap\Test\Builders\UserTestBuilder;

final class MilestoneTrackerCollectionTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function testGetTrackerIdsReturnsTrackerIds(): void
    {
        $first_tracker = M::mock(\Tracker::class);
        $first_tracker->shouldReceive('getId')->andReturn(78);
        $first_tracker->shouldReceive('getGroupId')->andReturn('103');
        $second_tracker = M::mock(\Tracker::class);
        $second_tracker->shouldReceive('getId')->andReturn(57);
        $second_tracker->shouldReceive('getGroupId')->andReturn('104');

        $collection = new MilestoneTrackerCollection(\Project::buildForTest(), [$first_tracker, $second_tracker]);
        $ids        = $collection->getTrackerIds();
        $this->assertContains(78, $ids);
        $this->assertContains(57, $ids);
    }

    public function testCanGetContributorTrackerIDs(): void
    {
        $first_tracker = M::mock(\Tracker::class);
        $first_tracker->shouldReceive('getId')->andReturn(78);
        $first_tracker->shouldReceive('getGroupId')->andReturn('103');
        $aggregator_project = \Project::buildForTest();
        $second_tracker = M::mock(\Tracker::class);
        $second_tracker->shouldReceive('getId')->andReturn(57);
        $second_tracker->shouldReceive('getGroupId')->andReturn($aggregator_project->getID());

        $collection = new MilestoneTrackerCollection($aggregator_project, [$first_tracker, $second_tracker]);
        $this->assertEquals([78], $collection->getContributorMilestoneTrackerIds());
    }

    public function testGetTrackerIdsReturnsEmpty(): void
    {
        $collection = new MilestoneTrackerCollection(\Project::buildForTest(), []);
        $this->assertEmpty($collection->getTrackerIds());
    }

    public function testGetMilestoneTrackersReturnTrackers(): void
    {
        $first_tracker = M::mock(\Tracker::class);
        $first_tracker->shouldReceive('getGroupId')->andReturn('103');
        $second_tracker = M::mock(\Tracker::class);
        $second_tracker->shouldReceive('getGroupId')->andReturn('104');

        $collection = new MilestoneTrackerCollection(\Project::buildForTest(), [$first_tracker, $second_tracker]);
        $trackers = $collection->getMilestoneTrackers();
        $this->assertContains($first_tracker, $trackers);
        $this->assertContains($second_tracker, $trackers);
    }

    public function testGetContributorMilestoneTrackersReturnTrackers(): void
    {
        $first_tracker = M::mock(\Tracker::class);
        $first_tracker->shouldReceive('getGroupId')->andReturn('103');
        $aggregator_tracker = M::mock(\Tracker::class);
        $aggregator_project = \Project::buildForTest();
        $aggregator_tracker->shouldReceive('getGroupId')->andReturn($aggregator_project->getID());

        $collection = new MilestoneTrackerCollection(\Project::buildForTest(), [$first_tracker, $aggregator_tracker]);
        $trackers = $collection->getContributorMilestoneTrackers();
        $this->assertContains($first_tracker, $trackers);
        $this->assertNotContains($aggregator_tracker, $trackers);
    }

    public function testGetMilestoneTrackersReturnsEmpty(): void
    {
        $collection = new MilestoneTrackerCollection(\Project::buildForTest(), []);
        $this->assertEmpty($collection->getMilestoneTrackers());
    }

    public function testCanUserSubmitAnArtifactInAllContributorTrackersReturnsTrue(): void
    {
        $first_contributor_tracker = M::mock(\Tracker::class);
        $first_contributor_tracker->shouldReceive('userCanSubmitArtifact')->andReturnTrue();
        $first_contributor_tracker->shouldReceive('getGroupId')->andReturn('963');
        $second_contributor_tracker = M::mock(\Tracker::class);
        $second_contributor_tracker->shouldReceive('userCanSubmitArtifact')->andReturnTrue();
        $second_contributor_tracker->shouldReceive('getGroupId')->andReturn('789');
        $aggregator_project = \Project::buildForTest();
        $aggregator_tracker = M::mock(\Tracker::class);
        $aggregator_tracker->shouldReceive('getGroupId')->andReturn($aggregator_project->getID());

        $user = UserTestBuilder::aUser()->build();

        $collection = new MilestoneTrackerCollection($aggregator_project, [$first_contributor_tracker, $aggregator_tracker, $second_contributor_tracker]);
        $this->assertTrue($collection->canUserSubmitAnArtifactInAllContributorTrackers($user));
    }

    public function testCanUserSubmitAnArtifactInAllTrackersReturnsFalse(): void
    {
        $first_contributor_tracker = M::mock(\Tracker::class);
        $first_contributor_tracker->shouldReceive('userCanSubmitArtifact')->andReturnTrue();
        $first_contributor_tracker->shouldReceive('getGroupId')->andReturn('963');
        $second_contributor_tracker = M::mock(\Tracker::class);
        $second_contributor_tracker->shouldReceive('userCanSubmitArtifact')->andReturnFalse();
        $second_contributor_tracker->shouldReceive('getGroupId')->andReturn('789');
        $aggregator_project = \Project::buildForTest();
        $aggregator_tracker = M::mock(\Tracker::class);
        $aggregator_tracker->shouldReceive('getGroupId')->andReturn($aggregator_project->getID());

        $user = UserTestBuilder::aUser()->build();

        $collection = new MilestoneTrackerCollection($aggregator_project, [$first_contributor_tracker, $aggregator_tracker, $second_contributor_tracker]);
        $this->assertFalse($collection->canUserSubmitAnArtifactInAllContributorTrackers($user));
    }
}
