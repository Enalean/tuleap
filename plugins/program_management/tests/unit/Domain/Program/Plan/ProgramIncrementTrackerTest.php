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

use Exception;
use PHPUnit\Framework\TestCase;
use Tuleap\ProgramManagement\Domain\Program\PlanTrackerNotFoundException;

class ProgramIncrementTrackerTest extends TestCase
{
    public function testItThrowsAnExceptionWhenTrackerIsNotFound(): void
    {
        $this->expectException(PlanTrackerNotFoundException::class);
        ProgramIncrementTracker::buildProgramIncrementTracker($this->getStubBuildTracker(false), 1, 101);
    }

    public function testItBuildAProgramIncrement(): void
    {
        $tracker = ProgramIncrementTracker::buildProgramIncrementTracker($this->getStubBuildTracker(), 1, 101);
        self::assertEquals(1, $tracker->getId());
    }

    private function getStubBuildTracker(bool $return_tracker = true): BuildTracker
    {
        return new class ($return_tracker) implements BuildTracker {

            /* @var bool */
            private $return_tracker;

            public function __construct(bool $return_tracker)
            {
                $this->return_tracker = $return_tracker;
            }

            public function buildPlannableTrackerList(array $plannable_trackers_id, int $project_id): array
            {
                throw new Exception("Should not be called.");
            }

            public function checkTrackerIsValid(int $tracker_id, int $project_id): void
            {
                if (! $this->return_tracker) {
                    throw new PlanTrackerNotFoundException($tracker_id);
                }
            }
        };
    }
}
