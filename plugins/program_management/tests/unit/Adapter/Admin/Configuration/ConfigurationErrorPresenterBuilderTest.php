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

namespace Tuleap\ProgramManagement\Adapter\Program\Admin\Configuration;

use Tuleap\ProgramManagement\Domain\Program\Admin\Configuration\ConfigurationErrorsCollector;
use Tuleap\ProgramManagement\Domain\Program\Backlog\CreationCheck\ConfigurationErrorsGatherer;
use Tuleap\ProgramManagement\Domain\Program\Backlog\CreationCheck\IterationCreatorChecker;
use Tuleap\ProgramManagement\Domain\Program\Backlog\CreationCheck\ProgramIncrementCreatorChecker;
use Tuleap\ProgramManagement\Domain\ProgramTracker;
use Tuleap\ProgramManagement\Tests\Builder\ProgramIdentifierBuilder;
use Tuleap\ProgramManagement\Tests\Builder\ProgramTrackerBuilder;
use Tuleap\ProgramManagement\Tests\Builder\ProjectReferenceBuilder;
use Tuleap\ProgramManagement\Tests\Builder\TrackerReferenceBuilder;
use Tuleap\ProgramManagement\Tests\Stub\BuildProgramStub;
use Tuleap\ProgramManagement\Tests\Stub\BuildProjectStub;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveUserStub;
use Tuleap\ProgramManagement\Tests\Stub\SearchTeamsOfProgramStub;
use Tuleap\Test\Builders\UserTestBuilder;

final class ConfigurationErrorPresenterBuilderTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private \PFUser $user;
    private ConfigurationErrorPresenterBuilder $configuration_error_builder;
    private \PHPUnit\Framework\MockObject\Stub|ProgramIncrementCreatorChecker $program_increment_checker;
    private \PHPUnit\Framework\MockObject\Stub|IterationCreatorChecker $iteration_checker;
    private ?ProgramTracker $program_tracker;
    private \Tuleap\ProgramManagement\Domain\Program\ProgramIdentifier $program_identifier;

    protected function setUp(): void
    {
        $this->user                      = UserTestBuilder::aUser()->build();
        $this->program_increment_checker = $this->createStub(ProgramIncrementCreatorChecker::class);
        $this->iteration_checker         = $this->createStub(IterationCreatorChecker::class);
        $this->program_identifier        = ProgramIdentifierBuilder::build();
        $this->program_tracker           = ProgramTrackerBuilder::buildWithId(1);

        $this->configuration_error_builder = new ConfigurationErrorPresenterBuilder(
            new ConfigurationErrorsGatherer(
                BuildProgramStub::stubValidProgram(),
                $this->program_increment_checker,
                $this->iteration_checker,
                SearchTeamsOfProgramStub::buildTeams(),
                new BuildProjectStub(),
                RetrieveUserStub::withUser($this->user)
            )
        );
    }

    public function testItBuildsProgramIncrementErrorPresenter(): void
    {
        $this->program_increment_checker->expects(self::once())->method('canCreateAProgramIncrement');

        $error_collector = new ConfigurationErrorsCollector(false);
        $error_collector->addWorkflowDependencyError(TrackerReferenceBuilder::buildWithId(1), ProjectReferenceBuilder::buildGeneric());
        $this->configuration_error_builder->buildProgramIncrementErrorPresenter(
            $this->program_tracker,
            $this->program_identifier,
            $this->user,
            $error_collector
        );

        self::assertTrue($error_collector->hasError());
    }

    public function testItReturnsFalseWhenNoProgram(): void
    {
        $error_collector = new ConfigurationErrorsCollector(false);
        $this->configuration_error_builder->buildProgramIncrementErrorPresenter(
            $this->program_tracker,
            null,
            $this->user,
            $error_collector
        );

        self::assertFalse($error_collector->hasError());
    }

    public function testItBuildsIterationErrorPresenter(): void
    {
        $this->program_increment_checker->method('canCreateAProgramIncrement');
        $this->iteration_checker->method('canCreateAnIteration');

        $error_collector = new ConfigurationErrorsCollector(true);
        $error_collector->addWorkflowDependencyError(TrackerReferenceBuilder::buildWithId(1), ProjectReferenceBuilder::buildGeneric());
        $this->configuration_error_builder->buildIterationErrorPresenter(
            $this->program_tracker,
            $this->user,
            $error_collector
        );

        self::assertTrue($error_collector->hasError());
    }

    public function testItReturnsFalseWhenNoTrackerFound(): void
    {
        $error_collector = new ConfigurationErrorsCollector(true);
        $this->configuration_error_builder->buildIterationErrorPresenter(
            null,
            $this->user,
            $error_collector
        );

        self::assertFalse($error_collector->hasError());
    }
}
