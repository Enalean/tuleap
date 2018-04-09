<?php
/**
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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

namespace Tuleap\Velocity\Semantic;

use Mockery;
use PHPUnit\Framework\TestCase;
use Tracker;

require_once __DIR__ . '/../../bootstrap.php';

class VelocitySemanticCheckerTest extends TestCase
{
    use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    public function testSemanticAreNotCorrectlySetWhenTrackerIsATopBacklogAndChildrenTrackersHaveMissingVelocitySemanticAndBacklogTrackersHasMissingRequiredSemantics()
    {
        $children_trackers = [Mockery::mock(Tracker::class)];

        $children_trackers_collection = Mockery::mock(ChildrenRequiredTrackerCollection::class);
        $children_trackers_collection->shouldReceive('getChildrenTrackers')->andReturn($children_trackers);
        $children_trackers_collection->shouldReceive('hasAtLeastOneChildrenWithVelocitySemanticForBacklogTrackers')->andReturn(false);

        $backlog_trackers_collection = Mockery::mock(BacklogRequiredTrackerCollection::class);
        $backlog_trackers_collection->shouldReceive('areAllBacklogTrackersMisconfigured')->andReturn(true);

        $checker = new VelocitySemanticChecker();
        $this->assertFalse(
            $checker->hasAtLeastOneTrackerCorrectlyConfigured(
                $backlog_trackers_collection,
                $children_trackers_collection
            )
        );
    }

    public function testSemanticAreCorrectlySetWhenTrackerIsATopBacklogAndChildrenTrackersHaveMissingVelocitySemanticAndBacklogTrackersAreCorrectlySet()
    {
        $children_trackers = [Mockery::mock(Tracker::class)];

        $children_trackers_collection = Mockery::mock(ChildrenRequiredTrackerCollection::class);
        $children_trackers_collection->shouldReceive('getChildrenTrackers')->andReturn($children_trackers);
        $children_trackers_collection->shouldReceive('hasAtLeastOneChildrenWithVelocitySemanticForBacklogTrackers')->andReturn(false);

        $backlog_trackers_collection = Mockery::mock(BacklogRequiredTrackerCollection::class);
        $backlog_trackers_collection->shouldReceive('areAllBacklogTrackersMisconfigured')->andReturn(false);

        $checker = new VelocitySemanticChecker();
        $this->assertTrue(
            $checker->hasAtLeastOneTrackerCorrectlyConfigured(
                $backlog_trackers_collection,
                $children_trackers_collection
            )
        );
    }

    public function testSemanticAreCorrectlySetWhenTrackerIsATopBacklogAndChildrenVelocitySemanticAreCorrectlySetAndBacklogTrackersAreCorrectlySet()
    {
        $children_trackers = [Mockery::mock(Tracker::class)];

        $children_trackers_collection = Mockery::mock(ChildrenRequiredTrackerCollection::class);
        $children_trackers_collection->shouldReceive('getChildrenTrackers')->andReturn($children_trackers);
        $children_trackers_collection->shouldReceive('hasAtLeastOneChildrenWithVelocitySemanticForBacklogTrackers')->andReturn(true);

        $backlog_trackers_collection = Mockery::mock(BacklogRequiredTrackerCollection::class);
        $backlog_trackers_collection->shouldReceive('areAllBacklogTrackersMisconfigured')->andReturn(false);

        $checker = new VelocitySemanticChecker();
        $this->assertTrue(
            $checker->hasAtLeastOneTrackerCorrectlyConfigured(
                $backlog_trackers_collection,
                $children_trackers_collection
            )
        );
    }

    public function testSemanticAreNotCorrectlySetWhenTrackerIsATopBacklogAndChildrenVelocitySemanticAreNotCorrectlySetAndBacklogTrackersAreCorrectlySet()
    {
        $children_trackers = [Mockery::mock(Tracker::class)];

        $children_trackers_collection = Mockery::mock(ChildrenRequiredTrackerCollection::class);
        $children_trackers_collection->shouldReceive('getChildrenTrackers')->andReturn($children_trackers);
        $children_trackers_collection->shouldReceive('hasAtLeastOneChildrenWithVelocitySemanticForBacklogTrackers')->andReturn(false);

        $backlog_trackers_collection = Mockery::mock(BacklogRequiredTrackerCollection::class);
        $backlog_trackers_collection->shouldReceive('areAllBacklogTrackersMisconfigured')->andReturn(true);

        $checker                                          = new VelocitySemanticChecker();
        $this->assertFalse(
            $checker->hasAtLeastOneTrackerCorrectlyConfigured(
                $backlog_trackers_collection,
                $children_trackers_collection
            )
        );
    }

    public function testSemanticAreNotCorrectlySetWhenTrackerIsNotATopBacklogAndBacklogTrackersAreNotCorrectlySet()
    {
        $children_trackers_collection = Mockery::mock(ChildrenRequiredTrackerCollection::class);
        $children_trackers_collection->shouldReceive('getChildrenTrackers')->andReturn([]);
        $children_trackers_collection->shouldReceive('hasAtLeastOneChildrenWithVelocitySemanticForBacklogTrackers')->andReturn(false);

        $backlog_trackers_collection = Mockery::mock(BacklogRequiredTrackerCollection::class);
        $backlog_trackers_collection->shouldReceive('areAllBacklogTrackersMisconfigured')->andReturn(true);

        $checker = new VelocitySemanticChecker();
        $this->assertFalse(
            $checker->hasAtLeastOneTrackerCorrectlyConfigured(
                $backlog_trackers_collection,
                $children_trackers_collection
            )
        );
    }

    public function testSemanticAreCorrectlySetWhenTrackerIsNotATopBacklogAndBacklogTrackersAreCorrectlySet()
    {
        $children_trackers_collection = Mockery::mock(ChildrenRequiredTrackerCollection::class);
        $children_trackers_collection->shouldReceive('getChildrenTrackers')->andReturn([]);
        $children_trackers_collection->shouldReceive('hasAtLeastOneChildrenWithVelocitySemanticForBacklogTrackers')->andReturn(false);

        $backlog_trackers_collection = Mockery::mock(BacklogRequiredTrackerCollection::class);
        $backlog_trackers_collection->shouldReceive('areAllBacklogTrackersMisconfigured')->andReturn(false);

        $checker                                          = new VelocitySemanticChecker();
        $this->assertTrue(
            $checker->hasAtLeastOneTrackerCorrectlyConfigured(
                $backlog_trackers_collection,
                $children_trackers_collection
            )
        );
    }
}
