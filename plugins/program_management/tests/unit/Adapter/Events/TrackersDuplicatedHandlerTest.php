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

namespace Tuleap\ProgramManagement\Adapter\Events;

use ColinODell\PsrTestLogger\TestLogger;
use Tuleap\Option\Option;
use Tuleap\ProgramManagement\Adapter\Workspace\ProgramServiceIsEnabledCertifier;
use Tuleap\ProgramManagement\Domain\Program\Plan\PlanConfiguration;
use Tuleap\ProgramManagement\Domain\Program\Plan\PlanInheritanceHandler;
use Tuleap\ProgramManagement\ProgramService;
use Tuleap\ProgramManagement\Tests\Builder\ProgramIdentifierBuilder;
use Tuleap\ProgramManagement\Tests\Stub\Program\Plan\RetrievePlanStub;
use Tuleap\Project\MappingRegistry;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\TrackerEventTrackersDuplicated;

final class TrackersDuplicatedHandlerTest extends TestCase
{
    private const SOURCE_PROJECT_ID = 114;
    private RetrievePlanStub $retrieve_plan;
    private TestLogger $logger;
    private \Project $source_project;

    protected function setUp(): void
    {
        $this->logger         = new TestLogger();
        $this->retrieve_plan  = RetrievePlanStub::withPlanConfigurations(
            PlanConfiguration::fromRaw(
                ProgramIdentifierBuilder::buildWithId(self::SOURCE_PROJECT_ID),
                12,
                'Releases',
                'release',
                Option::fromValue(46),
                'Sprints',
                'sprint',
                [65, 53],
                [4, 143]
            )
        );
        $this->source_project = ProjectTestBuilder::aProject()->withId(self::SOURCE_PROJECT_ID)->build();
    }

    private function handle(): void
    {
        $handler = new TrackersDuplicatedHandler(
            new ProgramServiceIsEnabledCertifier(),
            new PlanInheritanceHandler($this->retrieve_plan),
            $this->logger
        );
        $handler->handle(
            new TrackerEventTrackersDuplicated(
                [],
                [],
                [],
                ProjectTestBuilder::aProject()->withId(127)->build(),
                [],
                $this->source_project,
                new MappingRegistry([]),
            )
        );
    }

    public function testItDoesNothingWhenTheSourceProjectIsNotAProgram(): void
    {
        $this->source_project = ProjectTestBuilder::aProject()->withId(self::SOURCE_PROJECT_ID)
            ->withoutServices()
            ->build();
        $this->handle();
        self::assertFalse($this->logger->hasDebugRecords());
    }

    public function testItLogsToDebugWhenTheSourceProjectIsAProgram(): void
    {
        $this->source_project = ProjectTestBuilder::aProject()->withId(self::SOURCE_PROJECT_ID)
            ->withUsedService(ProgramService::SERVICE_SHORTNAME)
            ->build();
        $this->handle();

        self::assertTrue($this->logger->hasDebugThatContains('program id #' . self::SOURCE_PROJECT_ID));
    }
}
