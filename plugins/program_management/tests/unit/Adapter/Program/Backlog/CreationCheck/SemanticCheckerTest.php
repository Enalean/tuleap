<?php
/**
 * Copyright (c) Enalean, 2020 - Present. All Rights Reserved.
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

namespace Tuleap\ProgramManagement\Adapter\Program\Backlog\CreationCheck;

use Tuleap\ProgramManagement\Domain\Program\Admin\Configuration\ConfigurationErrorsCollector;
use Tuleap\ProgramManagement\Domain\Program\Backlog\CreationCheck\CheckStatus;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Team\TeamProjectsCollection;
use Tuleap\ProgramManagement\Domain\Program\Backlog\Source\SourceTrackerCollection;
use Tuleap\ProgramManagement\Domain\Program\Backlog\TrackerCollection;
use Tuleap\ProgramManagement\Domain\TrackerReference;
use Tuleap\ProgramManagement\Tests\Builder\ProgramIdentifierBuilder;
use Tuleap\ProgramManagement\Tests\Stub\BuildProjectStub;
use Tuleap\ProgramManagement\Tests\Stub\TrackerReferenceStub;
use Tuleap\ProgramManagement\Tests\Stub\UserIdentifierStub;
use Tuleap\ProgramManagement\Tests\Stub\SearchTeamsOfProgramStub;
use Tuleap\ProgramManagement\Tests\Stub\RetrievePlanningMilestoneTrackerStub;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveVisibleProgramIncrementTrackerStub;
use Tuleap\Tracker\Semantic\Timeframe\SemanticTimeframeDao;

final class SemanticCheckerTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private SemanticChecker $checker;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&\Tracker_Semantic_TitleDao
     */
    private $title_dao;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&\Tracker_Semantic_DescriptionDao
     */
    private $description_dao;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&SemanticTimeframeDao
     */
    private $timeframe_dao;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&CheckStatus
     */
    private $semantic_status_checker;
    private TrackerReference $program_increment_tracker;
    private TrackerCollection $trackers;
    private SourceTrackerCollection $source_trackers;

    protected function setUp(): void
    {
        $this->program_increment_tracker = TrackerReferenceStub::withDefaults();

        $user_identifier = UserIdentifierStub::buildGenericUser();
        $teams           = TeamProjectsCollection::fromProgramIdentifier(
            SearchTeamsOfProgramStub::buildTeams(101, 102),
            new BuildProjectStub(),
            ProgramIdentifierBuilder::build()
        );

        $retriever             = RetrievePlanningMilestoneTrackerStub::withValidTrackerIds(1024, 2048);
        $this->trackers        = TrackerCollection::buildRootPlanningMilestoneTrackers($retriever, $teams, $user_identifier);
        $this->source_trackers = SourceTrackerCollection::fromProgramAndTeamTrackers(
            RetrieveVisibleProgramIncrementTrackerStub::withValidTracker($this->program_increment_tracker),
            ProgramIdentifierBuilder::build(),
            $this->trackers,
            $user_identifier
        );

        $this->title_dao               = $this->createMock(\Tracker_Semantic_TitleDao::class);
        $this->description_dao         = $this->createMock(\Tracker_Semantic_DescriptionDao::class);
        $this->timeframe_dao           = $this->createMock(SemanticTimeframeDao::class);
        $this->semantic_status_checker = $this->createMock(CheckStatus::class);
        $this->checker                 = new SemanticChecker(
            $this->title_dao,
            $this->description_dao,
            $this->timeframe_dao,
            $this->semantic_status_checker
        );
    }

    public function testItReturnsTrueIfAllChecksAreOk(): void
    {
        $this->title_dao->expects(self::once())
            ->method('getNbOfTrackerWithoutSemanticTitleDefined')
            ->with([1, 1024, 2048])
            ->willReturn(0);
        $this->description_dao->expects(self::once())
            ->method('getNbOfTrackerWithoutSemanticDescriptionDefined')
            ->with([1, 1024, 2048])
            ->willReturn(0);
        $this->semantic_status_checker->expects(self::once())
            ->method('isStatusWellConfigured')
            ->willReturn(true);
        $this->timeframe_dao->expects(self::once())
            ->method('getNbOfTrackersWithoutTimeFrameSemanticDefined')
            ->with([1, 1024, 2048])
            ->willReturn(0);
        $this->timeframe_dao->expects(self::once())
            ->method('areTimeFrameSemanticsUsingSameTypeOfField')
            ->with([1, 1024, 2048])
            ->willReturn(true);

        $configuration_errors = new ConfigurationErrorsCollector(false);
        self::assertTrue(
            $this->checker->areTrackerSemanticsWellConfigured(
                $this->program_increment_tracker,
                $this->source_trackers,
                $configuration_errors
            )
        );
        self::assertCount(0, $configuration_errors->getSemanticErrors());
    }

    public function testItReturnsFalseIfSomethingIsIncorrect(): void
    {
        $this->title_dao->method('getNbOfTrackerWithoutSemanticTitleDefined')
            ->willReturn(1);
        $this->description_dao->method('getNbOfTrackerWithoutSemanticDescriptionDefined')
            ->willReturn(1);
        $this->title_dao->method('getTrackerIdsWithoutSemanticTitleDefined')
            ->willReturn([101]);
        $this->description_dao->method('getTrackerIdsWithoutSemanticDescriptionDefined')
            ->willReturn([101]);
        $this->semantic_status_checker->method('isStatusWellConfigured')
            ->willReturn(false);
        $this->timeframe_dao->method('getNbOfTrackersWithoutTimeFrameSemanticDefined')
            ->willReturn(1);
        $this->timeframe_dao->method('areTimeFrameSemanticsUsingSameTypeOfField')
            ->willReturn(true);

        $configuration_errors = new ConfigurationErrorsCollector(true);
        self::assertFalse(
            $this->checker->areTrackerSemanticsWellConfigured(
                $this->program_increment_tracker,
                $this->source_trackers,
                $configuration_errors
            )
        );

        $collected_errors = $configuration_errors->getSemanticErrors();
        self::assertCount(3, $collected_errors);
        self::assertStringContainsString("Title", $collected_errors[0]->semantic_name);
        self::assertStringContainsString("Description", $collected_errors[1]->semantic_name);
        self::assertStringContainsString("Timeframe", $collected_errors[2]->semantic_name);
    }

    public function testItStopsAtFirstErrorFound(): void
    {
        $this->title_dao->method('getNbOfTrackerWithoutSemanticTitleDefined')
            ->willReturn(1);
        $this->description_dao->method('getNbOfTrackerWithoutSemanticDescriptionDefined')
            ->willReturn(1);
        $this->title_dao->method('getTrackerIdsWithoutSemanticTitleDefined')
            ->willReturn([101]);
        $this->description_dao->method('getTrackerIdsWithoutSemanticDescriptionDefined')
            ->willReturn([101]);
        $this->semantic_status_checker->method('isStatusWellConfigured')
            ->willReturn(false);
        $this->timeframe_dao->method('getNbOfTrackersWithoutTimeFrameSemanticDefined')
            ->willReturn(1);
        $this->timeframe_dao->method('areTimeFrameSemanticsUsingSameTypeOfField')
            ->willReturn(true);

        $configuration_errors = new ConfigurationErrorsCollector(false);
        self::assertFalse(
            $this->checker->areTrackerSemanticsWellConfigured(
                $this->program_increment_tracker,
                $this->source_trackers,
                $configuration_errors
            )
        );

        $collected_errors = $configuration_errors->getSemanticErrors();
        self::assertCount(1, $collected_errors);
        self::assertStringContainsString("Title", $collected_errors[0]->semantic_name);
    }

    public function testItReturnsFalseIfTimeFrameSemanticsDontUseTheSameFieldType(): void
    {
        $this->title_dao->method('getNbOfTrackerWithoutSemanticTitleDefined')
            ->willReturn(0);
        $this->description_dao->method('getNbOfTrackerWithoutSemanticDescriptionDefined')
            ->willReturn(0);
        $this->semantic_status_checker->method('isStatusWellConfigured')
            ->willReturn(true);
        $this->timeframe_dao->method('getNbOfTrackersWithoutTimeFrameSemanticDefined')
            ->willReturn(0);
        $this->timeframe_dao->method('areTimeFrameSemanticsUsingSameTypeOfField')
            ->willReturn(false);

        $configuration_errors = new ConfigurationErrorsCollector(true);
        self::assertFalse(
            $this->checker->areTrackerSemanticsWellConfigured(
                $this->program_increment_tracker,
                $this->source_trackers,
                $configuration_errors
            )
        );
        $collected_errors = $configuration_errors->getSemanticErrors();
        self::assertCount(1, $collected_errors);
        self::assertStringContainsString("Timeframe", $collected_errors[0]->semantic_name);
    }
}
