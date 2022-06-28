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
use Tuleap\ProgramManagement\Domain\Program\ProgramIdentifier;
use Tuleap\ProgramManagement\Domain\TrackerReference;
use Tuleap\ProgramManagement\Domain\Workspace\UserReference;
use Tuleap\ProgramManagement\Tests\Builder\IterationCreatorCheckerBuilder;
use Tuleap\ProgramManagement\Tests\Builder\ProgramIdentifierBuilder;
use Tuleap\ProgramManagement\Tests\Builder\ProgramIncrementCreatorCheckerBuilder;
use Tuleap\ProgramManagement\Tests\Stub\BuildProgramStub;
use Tuleap\ProgramManagement\Tests\Stub\ProjectReferenceStub;
use Tuleap\ProgramManagement\Tests\Stub\RetrievePlannableTrackersStub;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveProjectReferenceStub;
use Tuleap\ProgramManagement\Tests\Stub\SearchTeamsOfProgramStub;
use Tuleap\ProgramManagement\Tests\Stub\TrackerReferenceStub;
use Tuleap\ProgramManagement\Tests\Stub\UserReferenceStub;
use Tuleap\ProgramManagement\Tests\Stub\VerifyIsTeamStub;
use Tuleap\ProgramManagement\Tests\Stub\VerifyTrackerSemanticsStub;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;

final class ConfigurationErrorPresenterBuilderTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private TrackerReference $program_tracker;
    private ProgramIdentifier $program_identifier;
    private UserReference $user_identifier;
    private VerifyTrackerSemanticsStub $verify_tracker_semantics;
    /**
     * @var \PHPUnit\Framework\MockObject\Stub&\TrackerFactory
     */
    private $tracker_factory;
    private \Tracker $tracker;

    protected function setUp(): void
    {
        $this->tracker_factory          = $this->createStub(\TrackerFactory::class);
        $this->program_identifier       = ProgramIdentifierBuilder::build();
        $this->user_identifier          = UserReferenceStub::withDefaults();
        $this->program_tracker          = TrackerReferenceStub::withDefaults();
        $this->verify_tracker_semantics = VerifyTrackerSemanticsStub::withAllSemantics();
        $this->tracker                  = TrackerTestBuilder::aTracker()
            ->withId(1)
            ->withName('Tracker')
            ->withProject(new \Project(['group_id' => 101, 'group_name' => 'A project']))
            ->build();
    }

    private function getErrorBuilder(): ConfigurationErrorPresenterBuilder
    {
        return new ConfigurationErrorPresenterBuilder(
            new ConfigurationErrorsGatherer(
                BuildProgramStub::stubValidProgram(),
                ProgramIncrementCreatorCheckerBuilder::build(),
                IterationCreatorCheckerBuilder::build(),
                SearchTeamsOfProgramStub::withTeamIds(162),
                RetrieveProjectReferenceStub::withProjects(ProjectReferenceStub::withId(162))
            ),
            RetrievePlannableTrackersStub::buildIds(1),
            $this->verify_tracker_semantics,
            $this->tracker_factory
        );
    }

    public function testItBuildsProgramIncrementErrorPresenter(): void
    {
        $error_collector = new ConfigurationErrorsCollector(VerifyIsTeamStub::withValidTeam(), false);
        $error_collector->addWorkflowDependencyError(
            $this->program_tracker,
            ProjectReferenceStub::buildGeneric()
        );
        $this->getErrorBuilder()->buildProgramIncrementErrorPresenter(
            $this->program_tracker,
            $this->program_identifier,
            $this->user_identifier,
            $error_collector
        );

        self::assertTrue($error_collector->hasError());
    }

    public function testItReturnsFalseWhenNoProgram(): void
    {
        $error_collector = new ConfigurationErrorsCollector(VerifyIsTeamStub::withValidTeam(), false);
        $this->getErrorBuilder()->buildProgramIncrementErrorPresenter(
            $this->program_tracker,
            null,
            $this->user_identifier,
            $error_collector
        );

        self::assertFalse($error_collector->hasError());
    }

    public function testItBuildsIterationErrorPresenter(): void
    {
        $error_collector = new ConfigurationErrorsCollector(VerifyIsTeamStub::withValidTeam(), true);
        $error_collector->addWorkflowDependencyError(
            $this->program_tracker,
            ProjectReferenceStub::buildGeneric()
        );
        $this->getErrorBuilder()->buildIterationErrorPresenter(
            $this->program_tracker,
            $this->user_identifier,
            $error_collector
        );

        self::assertTrue($error_collector->hasError());
    }

    public function testItReturnsFalseWhenNoTrackerFound(): void
    {
        $error_collector = new ConfigurationErrorsCollector(VerifyIsTeamStub::withValidTeam(), true);
        $this->getErrorBuilder()->buildIterationErrorPresenter(
            null,
            $this->user_identifier,
            $error_collector
        );

        self::assertFalse($error_collector->hasError());
    }

    public function testItCollectTitleSemanticErrorForPlannableTrackers(): void
    {
        $program_identifier             = ProgramIdentifierBuilder::build();
        $error_collector                = new ConfigurationErrorsCollector(VerifyIsTeamStub::withValidTeam(), true);
        $this->verify_tracker_semantics = VerifyTrackerSemanticsStub::withoutTitleSemantic();

        $this->tracker_factory->method('getTrackerById')->willReturn($this->tracker);

        $this->getErrorBuilder()->buildPlannableErrorPresenter($program_identifier, $error_collector);
        self::assertCount(1, $error_collector->getSemanticErrors());
    }

    public function testItCollectStatusSemanticErrorForPlannableTrackers(): void
    {
        $program_identifier             = ProgramIdentifierBuilder::build();
        $error_collector                = new ConfigurationErrorsCollector(VerifyIsTeamStub::withValidTeam(), true);
        $this->verify_tracker_semantics = VerifyTrackerSemanticsStub::withoutStatusSemantic();
        $this->tracker_factory->method('getTrackerById')->willReturn($this->tracker);

        $this->getErrorBuilder()->buildPlannableErrorPresenter($program_identifier, $error_collector);
        self::assertCount(1, $error_collector->getSemanticErrors());
    }

    public function testPlannableTrackersDoesNotHaveError(): void
    {
        $program_identifier             = ProgramIdentifierBuilder::build();
        $error_collector                = new ConfigurationErrorsCollector(VerifyIsTeamStub::withValidTeam(), true);
        $this->verify_tracker_semantics = VerifyTrackerSemanticsStub::withAllSemantics();

        $this->getErrorBuilder()->buildPlannableErrorPresenter($program_identifier, $error_collector);
        self::assertCount(0, $error_collector->getSemanticErrors());
    }
}
