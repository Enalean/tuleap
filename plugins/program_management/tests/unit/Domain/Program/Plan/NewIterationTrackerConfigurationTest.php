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
use Tuleap\ProgramManagement\Tests\Stub\CheckNewIterationTrackerStub;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class NewIterationTrackerConfigurationTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private const ITERATION_TRACKER_ID = 84;
    private const LABEL                = 'Cycles';
    private const SUB_LABEL            = 'cycle';
    private CheckNewIterationTrackerStub $iteration_checker;
    private PlanIterationChange $iteration_change;

    protected function setUp(): void
    {
        $this->iteration_checker = CheckNewIterationTrackerStub::withValidTracker();
        $this->iteration_change  = new PlanIterationChange(self::ITERATION_TRACKER_ID, self::LABEL, self::SUB_LABEL);
    }

    /**
     * @throws \Tuleap\ProgramManagement\Domain\Program\ProgramTrackerException
     */
    private function buildFromChange(): NewIterationTrackerConfiguration
    {
        return NewIterationTrackerConfiguration::fromPlanIterationChange(
            $this->iteration_checker,
            $this->iteration_change,
            ProgramForAdministrationIdentifierBuilder::build()
        );
    }

    public function testItBuildsFromIterationChange(): void
    {
        $new_configuration = $this->buildFromChange();
        self::assertSame(self::ITERATION_TRACKER_ID, $new_configuration->id);
        self::assertSame(self::LABEL, $new_configuration->label);
        self::assertSame(self::SUB_LABEL, $new_configuration->sub_label);
    }

    public function testItThrowsAnExceptionWhenTrackerIsNotFound(): void
    {
        $this->iteration_checker = CheckNewIterationTrackerStub::withTrackerNotFound();
        $this->expectException(PlanTrackerNotFoundException::class);
        $this->buildFromChange();
    }

    public function testItThrowsWhenTrackerDoesNotBelongToProgram(): void
    {
        $this->iteration_checker = CheckNewIterationTrackerStub::withTrackerNotPartOfProgram();
        $this->expectException(PlanTrackerDoesNotBelongToProjectException::class);
        $this->buildFromChange();
    }

    public function testItBuildsWithNullLabels(): void
    {
        $this->iteration_change = new PlanIterationChange(self::ITERATION_TRACKER_ID, null, null);
        $new_configuration      = $this->buildFromChange();
        self::assertSame(self::ITERATION_TRACKER_ID, $new_configuration->id);
        self::assertNull($new_configuration->label);
        self::assertNull($new_configuration->sub_label);
    }

    public function testItEnforcesNullLabelsWhenGivenEmptyStrings(): void
    {
        $this->iteration_change = new PlanIterationChange(self::ITERATION_TRACKER_ID, '', '');
        $new_configuration      = $this->buildFromChange();
        self::assertSame(self::ITERATION_TRACKER_ID, $new_configuration->id);
        self::assertNull($new_configuration->label);
        self::assertNull($new_configuration->sub_label);
    }

    public function testItBuildsFromValidTrackerAndLabels(): void
    {
        $iteration = NewIterationTrackerConfiguration::fromValidTrackerAndLabels(
            new NewConfigurationTrackerIsValidCertificate(99, ProgramForAdministrationIdentifierBuilder::build()),
            self::LABEL,
            self::SUB_LABEL
        );
        self::assertSame(99, $iteration->id);
        self::assertSame(self::LABEL, $iteration->label);
        self::assertSame(self::SUB_LABEL, $iteration->sub_label);
    }

    public function testItEnforcesNullLabelsWhenGivenEmptyStringsAndValidTracker(): void
    {
        $iteration = NewIterationTrackerConfiguration::fromValidTrackerAndLabels(
            new NewConfigurationTrackerIsValidCertificate(self::ITERATION_TRACKER_ID, ProgramForAdministrationIdentifierBuilder::build()),
            '',
            ''
        );
        self::assertSame(self::ITERATION_TRACKER_ID, $iteration->id);
        self::assertNull($iteration->label);
        self::assertNull($iteration->sub_label);
    }
}
