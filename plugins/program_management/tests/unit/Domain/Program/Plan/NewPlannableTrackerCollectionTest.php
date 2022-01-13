<?php
/**
 * Copyright (c) Enalean, 2021 - Present. All Rights Reserved.
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

namespace Tuleap\ProgramManagement\Domain\Program\Plan;

use Tuleap\ProgramManagement\Tests\Builder\ProgramForAdministrationIdentifierBuilder;
use Tuleap\ProgramManagement\Tests\Stub\CheckNewPlannableTrackerStub;
use Tuleap\Test\PHPUnit\TestCase;

final class NewPlannableTrackerCollectionTest extends TestCase
{
    private const FIRST_TRACKER_ID  = 1;
    private const SECOND_TRACKER_ID = 63;
    /**
     * @var int[]
     */
    private array $trackers_ids;

    protected function setUp(): void
    {
        $this->trackers_ids = [self::FIRST_TRACKER_ID, self::SECOND_TRACKER_ID];
    }

    /**
     * @throws PlannableTrackerCannotBeEmptyException
     * @throws \Tuleap\ProgramManagement\Domain\Program\ProgramTrackerException
     */
    private function getCollection(): NewPlannableTrackerCollection
    {
        return NewPlannableTrackerCollection::fromIds(
            CheckNewPlannableTrackerStub::withValidTracker(),
            $this->trackers_ids,
            ProgramForAdministrationIdentifierBuilder::build()
        );
    }

    public function testItBuildsPlannableTrackers(): void
    {
        $collection = $this->getCollection();

        self::assertCount(2, $collection->trackers);
        $tracker_ids = array_map(
            static fn(NewPlannableTracker $tracker) => $tracker->getId(),
            $collection->trackers
        );
        self::assertContains(self::FIRST_TRACKER_ID, $tracker_ids);
        self::assertContains(self::SECOND_TRACKER_ID, $tracker_ids);
    }

    public function testItThrowsAnExceptionWhenTrackerListIsEmpty(): void
    {
        $this->expectException(PlannableTrackerCannotBeEmptyException::class);
        $this->trackers_ids = [];
        $this->getCollection();
    }
}
