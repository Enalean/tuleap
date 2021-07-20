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

use Tuleap\ProgramManagement\Domain\Program\Admin\ProgramForAdministrationIdentifier;
use Tuleap\ProgramManagement\Domain\Program\PlanTrackerNotFoundException;
use Tuleap\ProgramManagement\Stub\RetrieveTrackerStub;
use Tuleap\ProgramManagement\Stub\VerifyIsTeamStub;
use Tuleap\ProgramManagement\Stub\VerifyProjectPermissionStub;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;

final class IterationTrackerTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private ProgramForAdministrationIdentifier $program;

    protected function setUp(): void
    {
        $this->program = ProgramForAdministrationIdentifier::fromProject(
            VerifyIsTeamStub::withNotValidTeam(),
            VerifyProjectPermissionStub::withAdministrator(),
            UserTestBuilder::aUser()->build(),
            ProjectTestBuilder::aProject()->withId(101)->build()
        );
    }

    public function testItThrowsAnExceptionWhenTrackerIsNotFound(): void
    {
        $iteration_representation = new PlanIterationChange(1, null, null);

        $this->expectException(PlanTrackerNotFoundException::class);
        IterationTracker::fromPlanIterationChange(
            RetrieveTrackerStub::buildNullTracker(),
            $iteration_representation,
            $this->program
        );
    }

    public function testItBuildAProgramIncrement(): void
    {
        $iteration_representation = new PlanIterationChange(1, 'Iterations', 'iteration');

        $tracker = IterationTracker::fromPlanIterationChange(
            RetrieveTrackerStub::buildValidTrackerWithProjectId(101),
            $iteration_representation,
            $this->program
        );
        self::assertEquals(1, $tracker->id);
        self::assertEquals('Iterations', $tracker->label);
        self::assertEquals('iteration', $tracker->sub_label);
    }

    public function testItBuildAProgramIncrementWithoutLabels(): void
    {
        $iteration_representation = new PlanIterationChange(1, null, null);

        $tracker = IterationTracker::fromPlanIterationChange(
            RetrieveTrackerStub::buildValidTrackerWithProjectId(101),
            $iteration_representation,
            $this->program
        );
        self::assertEquals(1, $tracker->id);
        self::assertNull($tracker->label);
        self::assertNull($tracker->sub_label);
    }
}
