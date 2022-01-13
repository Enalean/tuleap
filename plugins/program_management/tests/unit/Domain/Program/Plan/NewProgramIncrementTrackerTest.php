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
 * along with Tuleap. If not, see http://www.gnu.org/licenses/.
 */

declare(strict_types=1);

namespace Tuleap\ProgramManagement\Domain\Program\Plan;

use Tuleap\ProgramManagement\Domain\Program\PlanTrackerDoesNotBelongToProjectException;
use Tuleap\ProgramManagement\Domain\Program\PlanTrackerNotFoundException;
use Tuleap\ProgramManagement\Tests\Builder\ProgramForAdministrationIdentifierBuilder;
use Tuleap\ProgramManagement\Tests\Stub\CheckNewProgramIncrementTrackerStub;

final class NewProgramIncrementTrackerTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private const TRACKER_ID = 28;
    private CheckNewProgramIncrementTracker $program_increment_checker;

    protected function setUp(): void
    {
        $this->program_increment_checker = CheckNewProgramIncrementTrackerStub::withValidTracker();
    }

    /**
     * @throws \Tuleap\ProgramManagement\Domain\Program\ProgramTrackerException
     */
    private function getNewProgramIncrementTracker(): NewProgramIncrementTracker
    {
        return NewProgramIncrementTracker::fromId(
            $this->program_increment_checker,
            self::TRACKER_ID,
            ProgramForAdministrationIdentifierBuilder::build()
        );
    }

    public function testItBuildsFromId(): void
    {
        $new_tracker = $this->getNewProgramIncrementTracker();
        self::assertSame(self::TRACKER_ID, $new_tracker->getId());
    }

    public function testItThrowsAnExceptionWhenTrackerIsNotFound(): void
    {
        $this->program_increment_checker = CheckNewProgramIncrementTrackerStub::withTrackerNotFound();
        $this->expectException(PlanTrackerNotFoundException::class);
        $this->getNewProgramIncrementTracker();
    }

    public function testItThrowsWhenTrackerDoesNotBelongToProgram(): void
    {
        $this->program_increment_checker = CheckNewProgramIncrementTrackerStub::withTrackerNotPartOfProgram();
        $this->expectException(PlanTrackerDoesNotBelongToProjectException::class);
        $this->getNewProgramIncrementTracker();
    }
}
