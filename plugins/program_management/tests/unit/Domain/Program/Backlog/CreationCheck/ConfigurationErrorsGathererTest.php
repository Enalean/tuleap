<?php
/**
 * Copyright (c) Enalean 2021 -  Present. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
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
 *
 */

declare(strict_types=1);

namespace Tuleap\ProgramManagement\Domain\Program\Backlog\CreationCheck;

use Tuleap\ProgramManagement\Domain\Program\Admin\Configuration\ConfigurationErrorsCollector;
use Tuleap\ProgramManagement\Domain\Program\Plan\BuildProgram;
use Tuleap\ProgramManagement\Domain\Program\SearchTeamsOfProgram;
use Tuleap\ProgramManagement\Domain\RetrieveProjectReference;
use Tuleap\ProgramManagement\Domain\TrackerReference;
use Tuleap\ProgramManagement\Domain\Workspace\UserReference;
use Tuleap\ProgramManagement\Tests\Builder\IterationCreatorCheckerBuilder;
use Tuleap\ProgramManagement\Tests\Builder\ProgramIncrementCreatorCheckerBuilder;
use Tuleap\ProgramManagement\Tests\Stub\BuildProgramStub;
use Tuleap\ProgramManagement\Tests\Stub\ProjectReferenceStub;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveProjectReferenceStub;
use Tuleap\ProgramManagement\Tests\Stub\SearchTeamsOfProgramStub;
use Tuleap\ProgramManagement\Tests\Stub\TrackerReferenceStub;
use Tuleap\ProgramManagement\Tests\Stub\UserReferenceStub;
use Tuleap\ProgramManagement\Tests\Stub\VerifyIsTeamStub;
use Tuleap\Test\PHPUnit\TestCase;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class ConfigurationErrorsGathererTest extends TestCase
{
    private TrackerReference $tracker;
    private UserReference $user_identifier;
    private ConfigurationErrorsGatherer $gatherer;
    private BuildProgram $build_program;
    private SearchTeamsOfProgram $teams_searcher;
    private RetrieveProjectReference $project_builder;

    protected function setUp(): void
    {
        $this->build_program   = BuildProgramStub::stubValidProgram();
        $this->teams_searcher  = SearchTeamsOfProgramStub::withTeamIds(101);
        $this->project_builder = RetrieveProjectReferenceStub::withProjects(ProjectReferenceStub::withId(101));
        $this->tracker         = TrackerReferenceStub::withDefaults();
        $this->user_identifier = UserReferenceStub::withDefaults();

        $this->gatherer = new ConfigurationErrorsGatherer(
            $this->build_program,
            ProgramIncrementCreatorCheckerBuilder::build(),
            IterationCreatorCheckerBuilder::build(),
            $this->teams_searcher,
            $this->project_builder,
        );
    }

    public function testItDoesNothingWhenProjectIsNotAProgram(): void
    {
        $errors_collector = new ConfigurationErrorsCollector(VerifyIsTeamStub::withValidTeam(), false);
        $build_program    = BuildProgramStub::stubInvalidProgram();

        $gatherer = new ConfigurationErrorsGatherer(
            $build_program,
            ProgramIncrementCreatorCheckerBuilder::build(),
            IterationCreatorCheckerBuilder::build(),
            $this->teams_searcher,
            $this->project_builder,
        );

        $gatherer->gatherConfigurationErrors(
            $this->tracker,
            $this->user_identifier,
            $errors_collector
        );

        self::assertFalse($errors_collector->hasError());
    }

    public function testItCollectProgramIncrementErrors(): void
    {
        $errors_collector = new ConfigurationErrorsCollector(VerifyIsTeamStub::withValidTeam(), false);
        $errors_collector->addWorkflowDependencyError(
            $this->tracker,
            ProjectReferenceStub::buildGeneric()
        );

        $this->gatherer->gatherConfigurationErrors(
            $this->tracker,
            $this->user_identifier,
            $errors_collector
        );

        self::assertTrue($errors_collector->hasError());
    }

    public function testItCollectProgramIncrementAndIterationErrors(): void
    {
        $errors_collector = new ConfigurationErrorsCollector(VerifyIsTeamStub::withValidTeam(), true);
        $errors_collector->addWorkflowDependencyError(
            $this->tracker,
            ProjectReferenceStub::buildGeneric()
        );

        $this->gatherer->gatherConfigurationErrors(
            $this->tracker,
            $this->user_identifier,
            $errors_collector
        );

        self::assertTrue($errors_collector->hasError());
    }

    public function testItCollectIterationErrors(): void
    {
        $errors_collector = new ConfigurationErrorsCollector(VerifyIsTeamStub::withValidTeam(), true);

        $this->gatherer->gatherConfigurationErrors(
            $this->tracker,
            $this->user_identifier,
            $errors_collector
        );

        self::assertFalse($errors_collector->hasError());
    }
}
