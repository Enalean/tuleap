<?php
/**
 * Copyright (c) Enalean, 2022-Present. All Rights Reserved.
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

namespace Tuleap\ProgramManagement\Adapter\Program\Plan;

use Tuleap\ProgramManagement\Domain\Program\PlanTrackerDoesNotBelongToProjectException;
use Tuleap\ProgramManagement\Domain\Program\PlanTrackerNotFoundException;
use Tuleap\ProgramManagement\Tests\Builder\ProgramForAdministrationIdentifierBuilder;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveFullTrackerFromIdStub;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class TrackerConfigurationCheckerTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private const PROGRAM_ID = 197;

    private static function checkProgramIncrementIsValid(RetrieveFullTrackerFromIdStub $tracker_retriever): void
    {
        $checker = new TrackerConfigurationChecker($tracker_retriever);
        $checker->checkProgramIncrementTrackerIsValid(
            90,
            ProgramForAdministrationIdentifierBuilder::buildWithId(self::PROGRAM_ID)
        );
    }

    /**
     * @throws PlanTrackerDoesNotBelongToProjectException
     * @throws PlanTrackerNotFoundException
     */
    private static function checkIterationIsValid(RetrieveFullTrackerFromIdStub $tracker_retriever): void
    {
        $checker = new TrackerConfigurationChecker($tracker_retriever);
        $checker->checkIterationTrackerIsValid(
            62,
            ProgramForAdministrationIdentifierBuilder::buildWithId(self::PROGRAM_ID)
        );
    }

    private static function checkPlannableTrackerIsValid(RetrieveFullTrackerFromIdStub $tracker_retriever): void
    {
        $checker = new TrackerConfigurationChecker($tracker_retriever);
        $checker->checkPlannableTrackerIsValid(
            5,
            ProgramForAdministrationIdentifierBuilder::buildWithId(self::PROGRAM_ID)
        );
    }

    public static function dataProviderMethodUnderTest(): array
    {
        return [
            'Iteration'         => [[self::class, 'checkIterationIsValid']],
            'Program Increment' => [[self::class, 'checkProgramIncrementIsValid']],
            'Plannable tracker' => [[self::class, 'checkPlannableTrackerIsValid']],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('dataProviderMethodUnderTest')]
    public function testItReturnsVoidWhenTrackerIsValid(callable $method_under_test): void
    {
        $project = ProjectTestBuilder::aProject()->withId(self::PROGRAM_ID)->build();
        $tracker = TrackerTestBuilder::aTracker()->withProject($project)->build();

        $this->expectNotToPerformAssertions();
        $method_under_test(RetrieveFullTrackerFromIdStub::withTracker($tracker));
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('dataProviderMethodUnderTest')]
    public function testItThrowsWhenGivenTrackerCannotBeFound(callable $method_under_test): void
    {
        $this->expectException(PlanTrackerNotFoundException::class);
        $method_under_test(RetrieveFullTrackerFromIdStub::withNoTracker());
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('dataProviderMethodUnderTest')]
    public function testItThrowsWhenGivenTrackerIsNotFromGivenProgram(callable $method_under_test): void
    {
        $project = ProjectTestBuilder::aProject()->withId(106)->build();
        $tracker = TrackerTestBuilder::aTracker()->withProject($project)->build();

        $this->expectException(PlanTrackerDoesNotBelongToProjectException::class);
        $method_under_test(RetrieveFullTrackerFromIdStub::withTracker($tracker));
    }
}
