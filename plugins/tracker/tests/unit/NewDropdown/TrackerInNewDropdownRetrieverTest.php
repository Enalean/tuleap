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

namespace Tuleap\Tracker\NewDropdown;

use PHPUnit\Framework\MockObject\MockObject;
use TrackerFactory;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Tracker\PromotedTrackerDao;
use Tuleap\Tracker\PromotedTrackersRetriever;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class TrackerInNewDropdownRetrieverTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private PromotedTrackerDao&MockObject $dao;
    private TrackerFactory&MockObject $tracker_factory;
    private PromotedTrackersRetriever $retriever;

    protected function setUp(): void
    {
        $this->dao             = $this->createMock(PromotedTrackerDao::class);
        $this->tracker_factory = $this->createMock(TrackerFactory::class);

        $this->retriever = new PromotedTrackersRetriever($this->dao, $this->tracker_factory);
    }

    public function testItReturnsTrackersUserCanSubmit(): void
    {
        $this->dao->method('searchByProjectId')
            ->with(101)
            ->willReturn([
                ['id' => 123],
                ['id' => 124],
                ['id' => 125],
            ]);

        $tracker_123 = TrackerTestBuilder::aTracker()->withId(123)->withUserCanSubmit(true)->build();
        $tracker_124 = TrackerTestBuilder::aTracker()->withId(124)->withUserCanSubmit(false)->build();
        $tracker_125 = TrackerTestBuilder::aTracker()->withId(125)->withUserCanSubmit(true)->build();

        $this->tracker_factory
            ->method('getInstanceFromRow')
            ->willReturnCallback(static fn (array $row) => match ($row) {
                ['id' => 123] => $tracker_123,
                ['id' => 124] => $tracker_124,
                ['id' => 125] => $tracker_125,
            });

        $project = ProjectTestBuilder::aProject()->build();

        $trackers = $this->retriever->getTrackers($this->createMock(\PFUser::class), $project);
        $this->assertContains($tracker_123, $trackers);
        $this->assertNotContains($tracker_124, $trackers);
        $this->assertContains($tracker_125, $trackers);
    }
}
