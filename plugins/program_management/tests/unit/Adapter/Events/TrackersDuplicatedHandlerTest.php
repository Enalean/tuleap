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
use Tuleap\ProgramManagement\Domain\Program\Plan\Inheritance\PlanConfigurationMapper;
use Tuleap\ProgramManagement\Domain\Program\Plan\Inheritance\PlanInheritanceHandler;
use Tuleap\ProgramManagement\Domain\Program\Plan\PlanConfiguration;
use Tuleap\ProgramManagement\ProgramService;
use Tuleap\ProgramManagement\Tests\Builder\ProgramIdentifierBuilder;
use Tuleap\ProgramManagement\Tests\Stub\Program\Plan\RetrievePlanConfigurationStub;
use Tuleap\ProgramManagement\Tests\Stub\SaveNewPlanConfigurationStub;
use Tuleap\Project\MappingRegistry;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\TrackerEventTrackersDuplicated;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class TrackersDuplicatedHandlerTest extends TestCase
{
    private const SOURCE_PROJECT_ID                   = 114;
    private const NEW_PROJECT_ID                      = 127;
    private const SOURCE_PROGRAM_INCREMENT_TRACKER_ID = 12;
    private RetrievePlanConfigurationStub $retrieve_plan;
    private SaveNewPlanConfigurationStub $save_new_plan;
    private TestLogger $logger;
    private \Project $source_project;
    private \Project $new_project;
    private MappingRegistry $mapping_registry;

    #[\Override]
    protected function setUp(): void
    {
        $source_iteration_tracker_id                  = 46;
        $first_source_tracker_id_that_can_be_planned  = 65;
        $second_source_tracker_id_that_can_be_planned = 53;
        $source_user_group_id_granted_plan_permission = 143;

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
                [\ProjectUGroup::PROJECT_ADMIN, $source_user_group_id_granted_plan_permission]
            )
        );
        $this->save_new_plan    = SaveNewPlanConfigurationStub::withCount();
        $this->source_project   = ProjectTestBuilder::aProject()->withId(self::SOURCE_PROJECT_ID)
            ->withUsedService(ProgramService::SERVICE_SHORTNAME)
            ->build();
        $this->new_project      = ProjectTestBuilder::aProject()->withId(self::NEW_PROJECT_ID)
            ->withUsedService(ProgramService::SERVICE_SHORTNAME)
            ->build();
        $this->mapping_registry = new MappingRegistry([$source_user_group_id_granted_plan_permission => 191]);
        $this->mapping_registry->setCustomMapping(\TrackerFactory::TRACKER_MAPPING_KEY, [
            self::SOURCE_PROGRAM_INCREMENT_TRACKER_ID     => 164,
            $source_iteration_tracker_id                  => 183,
            $first_source_tracker_id_that_can_be_planned  => 741,
            $second_source_tracker_id_that_can_be_planned => 742,
        ]);
    }

    private function handle(): void
    {
        $handler = new TrackersDuplicatedHandler(
            new ProgramServiceIsEnabledCertifier(),
            new PlanInheritanceHandler(
                $this->retrieve_plan,
                new PlanConfigurationMapper(),
                $this->save_new_plan,
            ),
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
        self::assertSame(0, $this->save_new_plan->getCallCount());
    }

    public function testItDoesNothingWhenTheEventHasNoTrackerMapping(): void
    {
        $this->mapping_registry = new MappingRegistry([]);
        $this->handle();
        self::assertSame(0, $this->save_new_plan->getCallCount());
    }

    public function testItDoesNothingWhenTheNewProjectDoesNotInheritProgramServiceEnabled(): void
    {
        $this->new_project = ProjectTestBuilder::aProject()->withId(self::NEW_PROJECT_ID)
            ->withoutServices()
            ->build();
        $this->handle();
        self::assertSame(0, $this->save_new_plan->getCallCount());
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

    public function testItHandlesTheEvent(): void
    {
        $this->handle();
        self::assertSame(1, $this->save_new_plan->getCallCount());
    }
}
