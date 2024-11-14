<?php
/**
 * Copyright (c) Enalean, 2023 - present. All Rights Reserved.
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

namespace Tuleap\Tracker\REST\v1;

use Tracker;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;
use Tuleap\Tracker\Test\Stub\RetrieveTrackersByProjectIdUserCanAdministrateStub;
use Tuleap\Tracker\Test\Stub\RetrieveTrackersByProjectIdUserCanViewStub;

final class ProjectTrackersRetrieverTest extends TestCase
{
    /**
     * @var Tracker[]
     */
    private array $project_trackers;
    /**
     * @var Tracker[]
     */
    private array $trackers_user_can_administrate;

    private ProjectTrackersRetriever $retriever;

    protected function setUp(): void
    {
        $this->project_trackers = [
            TrackerTestBuilder::aTracker()->withId(1)->build(),
            TrackerTestBuilder::aTracker()->withId(2)->build(),
        ];

        $this->trackers_user_can_administrate = [
            TrackerTestBuilder::aTracker()->withId(3)->build(),
        ];

        $this->retriever = new ProjectTrackersRetriever(
            RetrieveTrackersByProjectIdUserCanViewStub::withTrackers(...$this->project_trackers),
            RetrieveTrackersByProjectIdUserCanAdministrateStub::withTrackers(...$this->trackers_user_can_administrate)
        );
    }

    public function testItReturnsAllTheTrackersWhenWeDoNotFilterOnAdministrationPermissions(): void
    {
        $trackers = $this->retriever->getFilteredProjectTrackers(
            ProjectTestBuilder::aProject()->build(),
            UserTestBuilder::aUser()->build(),
            false
        );

        self::assertCount(2, $trackers);
        self::assertSame([1, 2], array_map(static fn(Tracker $tracker) => $tracker->getId(), $trackers));
    }

    public function testItReturnsOnlyTrackersUserCanAdministrate(): void
    {
        $trackers = $this->retriever->getFilteredProjectTrackers(
            ProjectTestBuilder::aProject()->build(),
            UserTestBuilder::aUser()->build(),
            true
        );

        self::assertCount(1, $trackers);
        self::assertSame(3, $trackers[0]->getId());
    }
}
