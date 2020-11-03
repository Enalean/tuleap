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

declare(strict_types=1);

namespace Tuleap\ScaledAgile\Program\Backlog\ProgramIncrement\Team;

use Mockery as M;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tuleap\ScaledAgile\Adapter\TrackerDataAdapter;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;

final class ProgramIncrementsTrackerCollectionTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function testGetTrackerIdsReturnsTrackerIds(): void
    {
        $first_tracker = TrackerDataAdapter::build(
            TrackerTestBuilder::aTracker()->withId(78)->withProject(new \Project(['group_id' => 103]))->build()
        );
        $second_tracker = TrackerDataAdapter::build(
            TrackerTestBuilder::aTracker()->withId(57)->withProject(new \Project(['group_id' => 104]))->build()
        );

        $collection = new ProgramIncrementsTrackerCollection([$first_tracker, $second_tracker]);
        $ids        = $collection->getTrackerIds();
        $this->assertContains(78, $ids);
        $this->assertContains(57, $ids);
    }

    public function testGetTrackerIdsReturnsEmpty(): void
    {
        $collection = new ProgramIncrementsTrackerCollection([]);
        $this->assertEmpty($collection->getTrackerIds());
    }

    public function testGetMilestoneTrackersReturnTrackers(): void
    {
        $first_tracker = TrackerTestBuilder::aTracker()->withId(78)->withProject(new \Project(['group_id' => 103]))->build();
        $second_tracker = TrackerTestBuilder::aTracker()->withId(57)->withProject(new \Project(['group_id' => 104]))->build();

        $collection = new ProgramIncrementsTrackerCollection([$first_tracker, $second_tracker]);
        $trackers   = $collection->getProgramIncrementTrackers();
        $this->assertContains($first_tracker, $trackers);
        $this->assertContains($second_tracker, $trackers);
    }

    public function testGetMilestoneTrackersReturnsEmpty(): void
    {
        $collection = new ProgramIncrementsTrackerCollection([]);
        $this->assertEmpty($collection->getProgramIncrementTrackers());
    }

    public function testCanUserSubmitAnArtifactInAllTrackersReturnsTrue(): void
    {
        $first_team_tracker = M::mock(\Tracker::class);
        $first_team_tracker->shouldReceive('userCanSubmitArtifact')->andReturnTrue();
        $first_team_tracker->shouldReceive('getGroupId')->andReturn('963');
        $second_team_tracker = M::mock(\Tracker::class);
        $second_team_tracker->shouldReceive('userCanSubmitArtifact')->andReturnTrue();
        $second_team_tracker->shouldReceive('getGroupId')->andReturn('789');

        $user = UserTestBuilder::aUser()->build();

        $collection = new ProgramIncrementsTrackerCollection(
            [$first_team_tracker, $second_team_tracker]
        );
        $this->assertTrue($collection->canUserSubmitAnArtifactInAllTrackers($user));
    }

    public function testCanUserSubmitAnArtifactInAllTrackersReturnsFalse(): void
    {
        $first_team_tracker = M::mock(\Tracker::class);
        $first_team_tracker->shouldReceive('userCanSubmitArtifact')->andReturnTrue();
        $first_team_tracker->shouldReceive('getGroupId')->andReturn('963');
        $second_team_tracker = M::mock(\Tracker::class);
        $second_team_tracker->shouldReceive('userCanSubmitArtifact')->andReturnFalse();
        $second_team_tracker->shouldReceive('getGroupId')->andReturn('789');

        $user = UserTestBuilder::aUser()->build();

        $collection = new ProgramIncrementsTrackerCollection(
            [$first_team_tracker, $second_team_tracker]
        );
        $this->assertFalse($collection->canUserSubmitAnArtifactInAllTrackers($user));
    }
}
