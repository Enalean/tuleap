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

use Tuleap\ProgramManagement\Domain\Program\PlanTrackerNotFoundException;
use Tuleap\ProgramManagement\Stub\BuildTrackerStub;

final class IterationTrackerTest extends \Tuleap\Test\PHPUnit\TestCase
{
    public function testItThrowsAnExceptionWhenTrackerIsNotFound(): void
    {
        $iteration_representation = new PlanIterationChange(1, null, null);

        $this->expectException(PlanTrackerNotFoundException::class);
        IterationTracker::fromPlanIterationChange(BuildTrackerStub::buildTrackerNotValid(), $iteration_representation, 101);
    }

    public function testItBuildAProgramIncrement(): void
    {
        $iteration_representation = new PlanIterationChange(1, "Iterations", "iteration");

        $tracker = IterationTracker::fromPlanIterationChange(BuildTrackerStub::buildValidTracker(), $iteration_representation, 101);
        self::assertEquals(1, $tracker->id);
        self::assertEquals("Iterations", $tracker->label);
        self::assertEquals("iteration", $tracker->sub_label);
    }

    public function testItBuildAProgramIncrementWithoutLabels(): void
    {
        $iteration_representation = new PlanIterationChange(1, null, null);

        $tracker = IterationTracker::fromPlanIterationChange(BuildTrackerStub::buildValidTracker(), $iteration_representation, 101);
        self::assertEquals(1, $tracker->id);
        self::assertNull($tracker->label);
        self::assertNull($tracker->sub_label);
    }
}
