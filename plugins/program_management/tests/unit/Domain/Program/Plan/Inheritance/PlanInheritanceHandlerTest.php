<?php
/**
 * Copyright (c) Enalean, 2024-Present. All Rights Reserved.
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

namespace Tuleap\ProgramManagement\Domain\Program\Plan\Inheritance;

use Tuleap\NeverThrow\Err;
use Tuleap\NeverThrow\Fault;
use Tuleap\NeverThrow\Ok;
use Tuleap\NeverThrow\Result;
use Tuleap\Option\Option;
use Tuleap\ProgramManagement\Domain\Program\Plan\NewPlanConfiguration;
use Tuleap\ProgramManagement\Domain\Program\Plan\PlanConfiguration;
use Tuleap\ProgramManagement\Tests\Builder\ProgramForAdministrationIdentifierBuilder;
use Tuleap\ProgramManagement\Tests\Builder\ProgramIdentifierBuilder;
use Tuleap\ProgramManagement\Tests\Stub\Program\Plan\RetrievePlanConfigurationStub;
use Tuleap\Test\PHPUnit\TestCase;

final class PlanInheritanceHandlerTest extends TestCase
{
    private const SOURCE_PROGRAM_INCREMENT_TRACKER_ID = 37;
    private const SOURCE_ITERATION_TRACKER_ID         = 35;
    private const NEW_PROGRAM_ID                      = 227;
    /** @var array<int, int> */
    private array $tracker_mapping;
    /** @var Option<int> */
    private Option $source_iteration_tracker_id;

    protected function setUp(): void
    {
        $this->tracker_mapping             = [
            self::SOURCE_PROGRAM_INCREMENT_TRACKER_ID => 97,
            self::SOURCE_ITERATION_TRACKER_ID         => 89,
        ];
        $this->source_iteration_tracker_id = Option::fromValue(self::SOURCE_ITERATION_TRACKER_ID);
    }

    /** @return Ok<NewPlanConfiguration> | Err<Fault> */
    private function handle(): Ok|Err
    {
        $source_program = ProgramIdentifierBuilder::buildWithId(135);
        $handler        = new PlanInheritanceHandler(
            RetrievePlanConfigurationStub::withPlanConfigurations(
                PlanConfiguration::fromRaw(
                    $source_program,
                    self::SOURCE_PROGRAM_INCREMENT_TRACKER_ID,
                    'Releases',
                    'release',
                    $this->source_iteration_tracker_id,
                    'Cycles',
                    'cycle',
                    [],
                    []
                )
            )
        );
        return $handler->handle(
            new ProgramInheritanceMapping(
                $source_program,
                ProgramForAdministrationIdentifierBuilder::buildWithId(self::NEW_PROGRAM_ID),
                $this->tracker_mapping
            )
        );
    }

    public function testItReturnsErrWhenProgramIncrementTrackerIsNotFoundInMapping(): void
    {
        $this->tracker_mapping = [];

        $result = $this->handle();

        self::assertTrue(Result::isErr($result));
        self::assertInstanceOf(ProgramIncrementTrackerNotFoundInMappingFault::class, $result->error);
    }

    public function testItDoesNotMapEmptyConfigurationForIterations(): void
    {
        $this->source_iteration_tracker_id = Option::nothing(\Psl\Type\int());

        $result = $this->handle();

        self::assertTrue(Result::isOk($result));
        self::assertTrue($result->value->iteration_tracker->isNothing());
    }

    public function testItDoesNotMapIterationConfigurationWhenTrackerIsNotFoundInMapping(): void
    {
        $this->source_iteration_tracker_id = Option::fromValue(70);

        $result = $this->handle();

        self::assertTrue(Result::isOk($result));
        self::assertTrue($result->value->iteration_tracker->isNothing());
    }

    public function testItMapsConfigurationAndReturnsIt(): void
    {
        $this->tracker_mapping[self::SOURCE_PROGRAM_INCREMENT_TRACKER_ID] = 88;
        $this->tracker_mapping[self::SOURCE_ITERATION_TRACKER_ID]         = 123;

        $result = $this->handle();

        self::assertTrue(Result::isOk($result));
        self::assertSame(self::NEW_PROGRAM_ID, $result->value->program->id);
        self::assertSame(88, $result->value->program_increment_tracker->id);
        self::assertSame('Releases', $result->value->program_increment_tracker->label);
        self::assertSame('release', $result->value->program_increment_tracker->sub_label);
        $iteration = $result->value->iteration_tracker->unwrapOr(null);
        self::assertSame(123, $iteration?->id);
        self::assertSame('Cycles', $iteration?->label);
        self::assertSame('cycle', $iteration?->sub_label);
    }
}
