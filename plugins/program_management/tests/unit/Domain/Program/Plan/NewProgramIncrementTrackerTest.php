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

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class NewProgramIncrementTrackerTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private const TRACKER_ID = 28;
    private const LABEL      = 'Releases';
    private const SUB_LABEL  = 'release';
    private CheckNewProgramIncrementTracker $program_increment_checker;

    protected function setUp(): void
    {
        $this->program_increment_checker = CheckNewProgramIncrementTrackerStub::withValidTracker();
    }

    /**
     * @throws \Tuleap\ProgramManagement\Domain\Program\ProgramTrackerException
     */
    private function buildFromChange(): NewProgramIncrementTracker
    {
        return NewProgramIncrementTracker::fromProgramIncrementChange(
            $this->program_increment_checker,
            new PlanProgramIncrementChange(self::TRACKER_ID, self::LABEL, self::SUB_LABEL),
            ProgramForAdministrationIdentifierBuilder::build()
        );
    }

    public function testItBuildsFromPlanChange(): void
    {
        $new_tracker = $this->buildFromChange();
        self::assertSame(self::TRACKER_ID, $new_tracker->id);
        self::assertSame(self::LABEL, $new_tracker->label);
        self::assertSame(self::SUB_LABEL, $new_tracker->sub_label);
    }

    public function testItThrowsAnExceptionWhenTrackerIsNotFound(): void
    {
        $this->program_increment_checker = CheckNewProgramIncrementTrackerStub::withTrackerNotFound();
        $this->expectException(PlanTrackerNotFoundException::class);
        $this->buildFromChange();
    }

    public function testItThrowsWhenTrackerDoesNotBelongToProgram(): void
    {
        $this->program_increment_checker = CheckNewProgramIncrementTrackerStub::withTrackerNotPartOfProgram();
        $this->expectException(PlanTrackerDoesNotBelongToProjectException::class);
        $this->buildFromChange();
    }

    public function testItBuildsFromValidTrackerAndLabels(): void
    {
        $new_tracker = NewProgramIncrementTracker::fromValidTrackerAndLabels(
            new NewConfigurationTrackerIsValidCertificate(
                79,
                ProgramForAdministrationIdentifierBuilder::build()
            ),
            self::LABEL,
            self::SUB_LABEL
        );
        self::assertSame(79, $new_tracker->id);
        self::assertSame(self::LABEL, $new_tracker->label);
        self::assertSame(self::SUB_LABEL, $new_tracker->sub_label);
    }
}
