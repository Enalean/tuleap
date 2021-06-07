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

namespace Tuleap\ProgramManagement\Adapter\Program\Backlog\ProgramIncrement;

use Tuleap\ProgramManagement\Domain\Program\Backlog\Plan\BuildPlanProgramIncrementConfiguration;
use Tuleap\ProgramManagement\Domain\Program\ProgramIdentifier;
use Tuleap\ProgramManagement\Domain\ProgramTracker;
use Tuleap\ProgramManagement\Stub\BuildProgramStub;
use Tuleap\ProgramManagement\Stub\RetrieveProgramIncrementLabelsStub;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;

final class ProgramIncrementTrackerConfigurationBuilderTest extends \Tuleap\Test\PHPUnit\TestCase
{
    public function testItBuildsAProgramIncrementTrackerConfiguration(): void
    {
        $tracker                   = TrackerTestBuilder::aTracker()->withId(101)->build();
        $program_increment_tracker = new ProgramTracker($tracker);
        $plan_builder              = $this->createMock(BuildPlanProgramIncrementConfiguration::class);
        $plan_builder->method('buildProgramIncrementTrackerFromProgram')
            ->willReturn($program_increment_tracker);

        $user    = UserTestBuilder::aUser()->build();
        $project = ProgramIdentifier::fromId(BuildProgramStub::stubValidProgram(), 101, $user);

        $builder       = new ProgramIncrementTrackerConfigurationBuilder(
            $plan_builder,
            RetrieveProgramIncrementLabelsStub::buildLabels('Program Increments', 'program increment')
        );
        $configuration = $builder->build($project, $user);
        self::assertSame(101, $configuration->getProgramIncrementTrackerId());
        self::assertFalse($configuration->canCreateProgramIncrement());
        self::assertSame('Program Increments', $configuration->getProgramIncrementLabel());
        self::assertSame('program increment', $configuration->getProgramIncrementSubLabel());
    }
}
