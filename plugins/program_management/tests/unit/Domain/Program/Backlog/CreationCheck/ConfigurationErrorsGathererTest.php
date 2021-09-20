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

use Tuleap\ProgramManagement\Domain\BuildProject;
use Tuleap\ProgramManagement\Domain\Program\Admin\Configuration\ConfigurationErrorsCollector;
use Tuleap\ProgramManagement\Domain\Program\Plan\BuildProgram;
use Tuleap\ProgramManagement\Domain\Program\SearchTeamsOfProgram;
use Tuleap\ProgramManagement\Domain\ProgramTracker;
use Tuleap\ProgramManagement\Domain\Workspace\RetrieveUser;
use Tuleap\ProgramManagement\Domain\Workspace\UserIdentifier;
use Tuleap\ProgramManagement\Tests\Builder\ProjectReferenceBuilder;
use Tuleap\ProgramManagement\Tests\Builder\TrackerReferenceBuilder;
use Tuleap\ProgramManagement\Tests\Stub\BuildProgramStub;
use Tuleap\ProgramManagement\Tests\Stub\BuildProjectStub;
use Tuleap\ProgramManagement\Tests\Stub\ProgramTrackerStub;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveUserStub;
use Tuleap\ProgramManagement\Tests\Stub\SearchTeamsOfProgramStub;
use Tuleap\ProgramManagement\Tests\Stub\UserIdentifierStub;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;

final class ConfigurationErrorsGathererTest extends TestCase
{
    /**
     * @var \PHPUnit\Framework\MockObject\Stub&ProgramIncrementCreatorChecker
     */
    private $program_increment_checker;
    /**
     * @var \PHPUnit\Framework\MockObject\Stub&IterationCreatorChecker
     */
    private $iteration_checker;
    private ProgramTracker $program_tracker;
    private UserIdentifier $user_identifier;
    private ConfigurationErrorsGatherer $gatherer;
    private BuildProgram $build_program;
    private SearchTeamsOfProgram $teams_searcher;
    private BuildProject $project_builder;
    private RetrieveUser $retrieve_user;

    protected function setUp(): void
    {
        $this->program_increment_checker = $this->createStub(ProgramIncrementCreatorChecker::class);
        $this->iteration_checker         = $this->createStub(IterationCreatorChecker::class);
        $this->build_program             = BuildProgramStub::stubValidProgram();
        $this->teams_searcher            = SearchTeamsOfProgramStub::buildTeams(1);
        $this->project_builder           = new BuildProjectStub();
        $this->retrieve_user             = RetrieveUserStub::withUser(UserTestBuilder::aUser()->build());
        $this->program_tracker           = ProgramTrackerStub::withDefaults();
        $this->user_identifier           = UserIdentifierStub::buildGenericUser();

        $this->gatherer = new ConfigurationErrorsGatherer(
            $this->build_program,
            $this->program_increment_checker,
            $this->iteration_checker,
            $this->teams_searcher,
            $this->project_builder,
            $this->retrieve_user
        );
    }

    public function testItDoesNothingWhenProjectIsNotAProgram(): void
    {
        $errors_collector = new ConfigurationErrorsCollector(false);
        $build_program    = BuildProgramStub::stubInvalidProgram();

        $gatherer = new ConfigurationErrorsGatherer(
            $build_program,
            $this->program_increment_checker,
            $this->iteration_checker,
            $this->teams_searcher,
            $this->project_builder,
            $this->retrieve_user
        );

        $gatherer->gatherConfigurationErrors(
            $this->program_tracker,
            $this->user_identifier,
            $errors_collector
        );

        self::assertFalse($errors_collector->hasError());
    }

    public function testItCollectProgramIncrementErrors(): void
    {
        $errors_collector = new ConfigurationErrorsCollector(false);
        $errors_collector->addWorkflowDependencyError(TrackerReferenceBuilder::buildWithId(1), ProjectReferenceBuilder::buildGeneric());

        $this->program_increment_checker->expects(self::once())->method('canCreateAProgramIncrement');
        $this->iteration_checker->expects(self::never())->method('canCreateAnIteration');
        $this->gatherer->gatherConfigurationErrors(
            $this->program_tracker,
            $this->user_identifier,
            $errors_collector
        );

        self::assertTrue($errors_collector->hasError());
    }

    public function testItCollectProgramIncrementAndIterationErrors(): void
    {
        $errors_collector = new ConfigurationErrorsCollector(true);
        $errors_collector->addWorkflowDependencyError(TrackerReferenceBuilder::buildWithId(1), ProjectReferenceBuilder::buildGeneric());

        $this->program_increment_checker->expects(self::once())->method('canCreateAProgramIncrement');
        $this->iteration_checker->expects(self::once())->method('canCreateAnIteration');
        $this->gatherer->gatherConfigurationErrors(
            $this->program_tracker,
            $this->user_identifier,
            $errors_collector
        );

        self::assertTrue($errors_collector->hasError());
    }

    public function testItCollectIterationErrors(): void
    {
        $errors_collector = new ConfigurationErrorsCollector(true);

        $this->program_increment_checker->expects(self::once())->method('canCreateAProgramIncrement');
        $this->iteration_checker->expects(self::once())->method('canCreateAnIteration');
        $this->gatherer->gatherConfigurationErrors(
            $this->program_tracker,
            $this->user_identifier,
            $errors_collector
        );

        self::assertFalse($errors_collector->hasError());
    }
}
