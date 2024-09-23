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
use Tuleap\ProgramManagement\Domain\Program\Plan\NewProgramIncrementTracker;
use Tuleap\ProgramManagement\Domain\Program\Plan\PlanConfiguration;
use Tuleap\ProgramManagement\Tests\Builder\ProgramForAdministrationIdentifierBuilder;
use Tuleap\ProgramManagement\Tests\Builder\ProgramIdentifierBuilder;
use Tuleap\ProgramManagement\Tests\Stub\Program\Plan\RetrievePlanConfigurationStub;
use Tuleap\Test\PHPUnit\TestCase;

final class PlanInheritanceHandlerTest extends TestCase
{
    private const SOURCE_PROGRAM_INCREMENT_TRACKER_ID = 37;
    /** @var array<int, int> */
    private array $tracker_mapping;

    protected function setUp(): void
    {
        $this->tracker_mapping = [];
    }

    /** @return Ok<NewProgramIncrementTracker> | Err<Fault> */
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
                    Option::nothing(\Psl\Type\int()),
                    null,
                    null,
                    [],
                    []
                )
            )
        );
        return $handler->handle(
            new ProgramInheritanceMapping(
                $source_program,
                ProgramForAdministrationIdentifierBuilder::buildWithId(227),
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

    public function testItMapsProgramIncrementTrackerAndReturnsIt(): void
    {
        $this->tracker_mapping[self::SOURCE_PROGRAM_INCREMENT_TRACKER_ID] = 88;

        $result = $this->handle();

        self::assertTrue(Result::isOk($result));
        self::assertSame(88, $result->value->id);
        self::assertSame('Releases', $result->value->label);
        self::assertSame('release', $result->value->sub_label);
    }
}
