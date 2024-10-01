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
use Tuleap\ProgramManagement\Domain\Program\Plan\Inheritance\PlanInheritanceHandler;
use Tuleap\ProgramManagement\Domain\Program\Plan\PlanConfiguration;
use Tuleap\ProgramManagement\ProgramService;
use Tuleap\ProgramManagement\Tests\Builder\ProgramIdentifierBuilder;
use Tuleap\ProgramManagement\Tests\Stub\Program\Plan\RetrievePlanConfigurationStub;
use Tuleap\Project\MappingRegistry;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\TrackerEventTrackersDuplicated;

final class TrackersDuplicatedHandlerTest extends TestCase
{
    private const SOURCE_PROJECT_ID                         = 114;
    private const NEW_PROJECT_ID                            = 127;
    private const SOURCE_PROGRAM_INCREMENT_TRACKER_ID       = 12;
    private const NEW_PROGRAM_INCREMENT_TRACKER_ID          = 164;
    private const NEW_ITERATION_TRACKER_ID                  = 183;
    private const FIRST_NEW_TRACKER_ID_THAT_CAN_BE_PLANNED  = 741;
    private const SECOND_NEW_TRACKER_ID_THAT_CAN_BE_PLANNED = 742;
    private RetrievePlanConfigurationStub $retrieve_plan;
    private TestLogger $logger;
    private \Project $source_project;
    private \Project $new_project;
    private MappingRegistry $mapping_registry;

    protected function setUp(): void
    {
        $source_iteration_tracker_id                  = 46;
        $first_source_tracker_id_that_can_be_planned  = 65;
        $second_source_tracker_id_that_can_be_planned = 53;

        $this->logger           = new TestLogger();
        $this->retrieve_plan    = RetrievePlanConfigurationStub::withPlanConfigurations(
            PlanConfiguration::fromRaw(
                ProgramIdentifierBuilder::buildWithId(self::SOURCE_PROJECT_ID),
                self::SOURCE_PROGRAM_INCREMENT_TRACKER_ID,
                'Releases',
                'release',
                Option::fromValue($source_iteration_tracker_id),
                'Sprints',
                'sprint',
                [$first_source_tracker_id_that_can_be_planned, $second_source_tracker_id_that_can_be_planned],
                [4, 143]
            )
        );
        $this->source_project   = ProjectTestBuilder::aProject()->withId(self::SOURCE_PROJECT_ID)
            ->withUsedService(ProgramService::SERVICE_SHORTNAME)
            ->build();
        $this->new_project      = ProjectTestBuilder::aProject()->withId(self::NEW_PROJECT_ID)
            ->withUsedService(ProgramService::SERVICE_SHORTNAME)
            ->build();
        $this->mapping_registry = new MappingRegistry([]);
        $this->mapping_registry->setCustomMapping(\TrackerFactory::TRACKER_MAPPING_KEY, [
            self::SOURCE_PROGRAM_INCREMENT_TRACKER_ID     => self::NEW_PROGRAM_INCREMENT_TRACKER_ID,
            $source_iteration_tracker_id                  => self::NEW_ITERATION_TRACKER_ID,
            $first_source_tracker_id_that_can_be_planned  => self::FIRST_NEW_TRACKER_ID_THAT_CAN_BE_PLANNED,
            $second_source_tracker_id_that_can_be_planned => self::SECOND_NEW_TRACKER_ID_THAT_CAN_BE_PLANNED,
        ]);
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
                UserTestBuilder::buildWithDefaults(),
                [],
                [],
                [],
                $this->new_project,
                [],
                $this->source_project,
                $this->mapping_registry,
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

    public function testItDoesNothingWhenTheEventHasNoTrackerMapping(): void
    {
        $this->mapping_registry = new MappingRegistry([]);
        $this->handle();
        self::assertFalse($this->logger->hasDebugRecords());
    }

    public function testItDoesNothingWhenTheNewProjectDoesNotInheritProgramServiceEnabled(): void
    {
        $this->new_project = ProjectTestBuilder::aProject()->withId(self::NEW_PROJECT_ID)
            ->withoutServices()
            ->build();
        $this->handle();
        self::assertFalse($this->logger->hasDebugRecords());
    }

    public function testItLogsToDebugWhenTheTrackersMappingHasNoEntryForTheConfiguredProgramIncrementTracker(): void
    {
        $this->mapping_registry->setCustomMapping(\TrackerFactory::TRACKER_MAPPING_KEY, []);
        $this->handle();
        self::assertTrue(
            $this->logger->hasDebugThatContains(
                sprintf(
                    'Could not find mapping for source Program Increment tracker #%d while inheriting from Program #%d to new Program #%d',
                    self::SOURCE_PROGRAM_INCREMENT_TRACKER_ID,
                    self::SOURCE_PROJECT_ID,
                    self::NEW_PROJECT_ID
                )
            )
        );
    }

    public function testItLogsToDebugWhenTheEventIsHandled(): void
    {
        $this->handle();
        self::assertTrue($this->logger->hasDebugThatContains('new program id #' . self::NEW_PROJECT_ID));
        self::assertTrue(
            $this->logger->hasDebugThatContains(
                'new program increment tracker id #' . self::NEW_PROGRAM_INCREMENT_TRACKER_ID
            )
        );
        self::assertTrue(
            $this->logger->hasDebugThatContains('new iteration tracker id #' . self::NEW_ITERATION_TRACKER_ID)
        );
        self::assertTrue(
            $this->logger->hasDebugThatContains(
                'tracker ids that can be planned ' . \Psl\Json\encode(
                    [self::FIRST_NEW_TRACKER_ID_THAT_CAN_BE_PLANNED, self::SECOND_NEW_TRACKER_ID_THAT_CAN_BE_PLANNED]
                )
            )
        );
    }
}
