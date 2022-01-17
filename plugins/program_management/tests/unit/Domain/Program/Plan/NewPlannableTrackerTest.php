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

use Tuleap\ProgramManagement\Domain\Program\PlanTrackerDoesNotBelongToProjectException;
use Tuleap\ProgramManagement\Domain\Program\PlanTrackerNotFoundException;
use Tuleap\ProgramManagement\Tests\Builder\ProgramForAdministrationIdentifierBuilder;
use Tuleap\ProgramManagement\Tests\Stub\CheckNewPlannableTrackerStub;

final class NewPlannableTrackerTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private const TRACKER_ID = 17;
    private CheckNewPlannableTrackerStub $tracker_checker;

    protected function setUp(): void
    {
        $this->tracker_checker = CheckNewPlannableTrackerStub::withValidTracker();
    }

    private function getNewPlannableTracker(): NewPlannableTracker
    {
        return NewPlannableTracker::fromId(
            $this->tracker_checker,
            self::TRACKER_ID,
            ProgramForAdministrationIdentifierBuilder::build()
        );
    }

    public function testItBuildsFromId(): void
    {
        $new_tracker = $this->getNewPlannableTracker();
        self::assertSame(self::TRACKER_ID, $new_tracker->getId());
    }

    public function testItThrowsAnExceptionWhenTrackerIsNotFound(): void
    {
        $this->tracker_checker = CheckNewPlannableTrackerStub::withTrackerNotFound();
        $this->expectException(PlanTrackerNotFoundException::class);
        $this->getNewPlannableTracker();
    }

    public function testItThrowsWhenTrackerDoesNotBelongToProgram(): void
    {
        $this->tracker_checker = CheckNewPlannableTrackerStub::withTrackerNotPartOfProgram();
        $this->expectException(PlanTrackerDoesNotBelongToProjectException::class);
        $this->getNewPlannableTracker();
    }
}
