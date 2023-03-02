<?php
/**
 * Copyright (c) Enalean, 2018 - Present. All Rights Reserved.
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

use Tracker;

class VelocitySemanticCheckerTest extends \Tuleap\Test\PHPUnit\TestCase
{
    public function testSemanticAreNotCorrectlySetWhenTrackerIsATopBacklogAndChildrenTrackersHaveMissingVelocitySemanticAndBacklogTrackersHasMissingRequiredSemantics()
    {
        $children_trackers = [$this->createMock(Tracker::class)];

        $children_trackers_collection = $this->createMock(ChildrenRequiredTrackerCollection::class);
        $children_trackers_collection->method('getChildrenTrackers')->willReturn($children_trackers);
        $children_trackers_collection->method('hasAtLeastOneChildrenWithVelocitySemanticForBacklogTrackers')->willReturn(false);

        $backlog_trackers_collection = $this->createMock(BacklogRequiredTrackerCollection::class);
        $backlog_trackers_collection->method('areAllBacklogTrackersMisconfigured')->willReturn(true);

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
        $children_trackers = [$this->createMock(Tracker::class)];

        $children_trackers_collection = $this->createMock(ChildrenRequiredTrackerCollection::class);
        $children_trackers_collection->method('getChildrenTrackers')->willReturn($children_trackers);
        $children_trackers_collection->method('hasAtLeastOneChildrenWithVelocitySemanticForBacklogTrackers')->willReturn(false);

        $backlog_trackers_collection = $this->createMock(BacklogRequiredTrackerCollection::class);
        $backlog_trackers_collection->method('areAllBacklogTrackersMisconfigured')->willReturn(false);

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
        $children_trackers = [$this->createMock(Tracker::class)];

        $children_trackers_collection = $this->createMock(ChildrenRequiredTrackerCollection::class);
        $children_trackers_collection->method('getChildrenTrackers')->willReturn($children_trackers);
        $children_trackers_collection->method('hasAtLeastOneChildrenWithVelocitySemanticForBacklogTrackers')->willReturn(true);

        $backlog_trackers_collection = $this->createMock(BacklogRequiredTrackerCollection::class);
        $backlog_trackers_collection->method('areAllBacklogTrackersMisconfigured')->willReturn(false);

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
        $children_trackers = [$this->createMock(Tracker::class)];

        $children_trackers_collection = $this->createMock(ChildrenRequiredTrackerCollection::class);
        $children_trackers_collection->method('getChildrenTrackers')->willReturn($children_trackers);
        $children_trackers_collection->method('hasAtLeastOneChildrenWithVelocitySemanticForBacklogTrackers')->willReturn(false);

        $backlog_trackers_collection = $this->createMock(BacklogRequiredTrackerCollection::class);
        $backlog_trackers_collection->method('areAllBacklogTrackersMisconfigured')->willReturn(true);

        $checker = new VelocitySemanticChecker();
        $this->assertFalse(
            $checker->hasAtLeastOneTrackerCorrectlyConfigured(
                $backlog_trackers_collection,
                $children_trackers_collection
            )
        );
    }

    public function testSemanticAreNotCorrectlySetWhenTrackerIsNotATopBacklogAndBacklogTrackersAreNotCorrectlySet()
    {
        $children_trackers_collection = $this->createMock(ChildrenRequiredTrackerCollection::class);
        $children_trackers_collection->method('getChildrenTrackers')->willReturn([]);
        $children_trackers_collection->method('hasAtLeastOneChildrenWithVelocitySemanticForBacklogTrackers')->willReturn(false);

        $backlog_trackers_collection = $this->createMock(BacklogRequiredTrackerCollection::class);
        $backlog_trackers_collection->method('areAllBacklogTrackersMisconfigured')->willReturn(true);

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
        $children_trackers_collection = $this->createMock(ChildrenRequiredTrackerCollection::class);
        $children_trackers_collection->method('getChildrenTrackers')->willReturn([]);
        $children_trackers_collection->method('hasAtLeastOneChildrenWithVelocitySemanticForBacklogTrackers')->willReturn(false);

        $backlog_trackers_collection = $this->createMock(BacklogRequiredTrackerCollection::class);
        $backlog_trackers_collection->method('areAllBacklogTrackersMisconfigured')->willReturn(false);

        $checker = new VelocitySemanticChecker();
        $this->assertTrue(
            $checker->hasAtLeastOneTrackerCorrectlyConfigured(
                $backlog_trackers_collection,
                $children_trackers_collection
            )
        );
    }
}
