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

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class NewTrackerThatCanBePlannedCollectionTest extends TestCase
{
    private const FIRST_TRACKER_ID  = 1;
    private const SECOND_TRACKER_ID = 63;
    /**
     * @var int[]
     */
    private array $trackers_ids;

    #[\Override]
    protected function setUp(): void
    {
        $this->trackers_ids = [self::FIRST_TRACKER_ID, self::SECOND_TRACKER_ID];
    }

    /**
     * @throws TrackersThatCanBePlannedCannotBeEmptyException
     * @throws \Tuleap\ProgramManagement\Domain\Program\ProgramTrackerException
     */
    private function getCollection(): NewTrackerThatCanBePlannedCollection
    {
        return NewTrackerThatCanBePlannedCollection::fromIds(
            CheckNewPlannableTrackerStub::withValidTracker(),
            $this->trackers_ids,
            ProgramForAdministrationIdentifierBuilder::build()
        );
    }

    public function testItBuildsTrackersThatCanBePlanned(): void
    {
        $collection = $this->getCollection();

        $retrieved_ids = $collection->getTrackerIds();
        self::assertCount(2, $retrieved_ids);
        self::assertContains(self::FIRST_TRACKER_ID, $retrieved_ids);
        self::assertContains(self::SECOND_TRACKER_ID, $retrieved_ids);
    }

    public function testItThrowsAnExceptionWhenTrackerListIsEmpty(): void
    {
        $this->expectException(TrackersThatCanBePlannedCannotBeEmptyException::class);
        $this->trackers_ids = [];
        $this->getCollection();
    }
}
