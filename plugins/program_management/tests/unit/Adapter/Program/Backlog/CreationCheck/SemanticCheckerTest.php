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

use Psr\Log\Test\TestLogger;
use Tuleap\ProgramManagement\Domain\Program\Backlog\CreationCheck\CheckStatus;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Team\TeamProjectsCollection;
use Tuleap\ProgramManagement\Domain\Program\Backlog\TrackerCollection;
use Tuleap\ProgramManagement\Domain\ProgramTracker;
use Tuleap\ProgramManagement\Domain\Project;
use Tuleap\ProgramManagement\Stub\RetrievePlanningMilestoneTrackerStub;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Tracker\Semantic\Timeframe\SemanticTimeframeDao;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;

final class SemanticCheckerTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private SemanticChecker $checker;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|\Tracker_Semantic_TitleDao
     */
    private $title_dao;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|\Tracker_Semantic_DescriptionDao
     */
    private $description_dao;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|SemanticTimeframeDao
     */
    private $timeframe_dao;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|CheckStatus
     */
    private $semantic_status_checker;
    private TestLogger $logger;

    protected function setUp(): void
    {
        $this->title_dao               = $this->createMock(\Tracker_Semantic_TitleDao::class);
        $this->description_dao         = $this->createMock(\Tracker_Semantic_DescriptionDao::class);
        $this->timeframe_dao           = $this->createMock(SemanticTimeframeDao::class);
        $this->semantic_status_checker = $this->createMock(CheckStatus::class);
        $this->logger                  = new TestLogger();
        $this->checker                 = new SemanticChecker(
            $this->title_dao,
            $this->description_dao,
            $this->timeframe_dao,
            $this->semantic_status_checker,
            $this->logger
        );
    }

    public function testItReturnsTrueIfAllChecksAreOk(): void
    {
        $tracker                   = TrackerTestBuilder::aTracker()->withId(104)->build();
        $program_increment_tracker = new ProgramTracker($tracker);

        $first_team  = new Project(101, 'team_blue', 'Team Blue');
        $second_team = new Project(102, 'team_red', 'Team Red');
        $teams       = new TeamProjectsCollection([$first_team, $second_team]);
        $retriever   = RetrievePlanningMilestoneTrackerStub::withValidTrackerIds(1024, 2048);
        $user        = UserTestBuilder::aUser()->build();
        $trackers    = TrackerCollection::buildRootPlanningMilestoneTrackers($retriever, $teams, $user);

        $this->title_dao->expects(self::once())
            ->method('getNbOfTrackerWithoutSemanticTitleDefined')
            ->with([1024, 2048])
            ->willReturn(0);
        $this->description_dao->expects(self::once())
            ->method('getNbOfTrackerWithoutSemanticDescriptionDefined')
            ->with([1024, 2048])
            ->willReturn(0);
        $this->semantic_status_checker->expects(self::once())
            ->method('isStatusWellConfigured')
            ->willReturn(true);
        $this->timeframe_dao->expects(self::once())
            ->method('getNbOfTrackersWithoutTimeFrameSemanticDefined')
            ->with([1024, 2048])
            ->willReturn(0);
        $this->timeframe_dao->expects(self::once())
            ->method('areTimeFrameSemanticsUsingSameTypeOfField')
            ->with([1024, 2048])
            ->willReturn(true);

        self::assertTrue(
            $this->checker->areTrackerSemanticsWellConfigured($program_increment_tracker, $trackers)
        );
        self::assertFalse($this->logger->hasErrorRecords());
    }

    public function testItReturnsFalseIfOneMilestoneTrackerDoesNotHaveTitleSemantic(): void
    {
        $tracker                   = TrackerTestBuilder::aTracker()->withId(104)->build();
        $program_increment_tracker = new ProgramTracker($tracker);

        $first_team  = new Project(101, 'team_blue', 'Team Blue');
        $second_team = new Project(102, 'team_red', 'Team Red');
        $teams       = new TeamProjectsCollection([$first_team, $second_team]);
        $retriever   = RetrievePlanningMilestoneTrackerStub::withValidTrackerIds(1024, 2048);
        $user        = UserTestBuilder::aUser()->build();
        $trackers    = TrackerCollection::buildRootPlanningMilestoneTrackers($retriever, $teams, $user);

        $this->title_dao->method('getNbOfTrackerWithoutSemanticTitleDefined')
            ->willReturn(1);

        self::assertFalse(
            $this->checker->areTrackerSemanticsWellConfigured($program_increment_tracker, $trackers)
        );
        self::assertTrue($this->logger->hasRecords('error'));
    }

    public function testItReturnsFalseIfOneMilestoneTrackerDoesNotHaveDescriptionSemantic(): void
    {
        $tracker                   = TrackerTestBuilder::aTracker()->withId(104)->build();
        $program_increment_tracker = new ProgramTracker($tracker);

        $first_team  = new Project(101, 'team_blue', 'Team Blue');
        $second_team = new Project(102, 'team_red', 'Team Red');
        $teams       = new TeamProjectsCollection([$first_team, $second_team]);
        $retriever   = RetrievePlanningMilestoneTrackerStub::withValidTrackerIds(1024, 2048);
        $user        = UserTestBuilder::aUser()->build();
        $trackers    = TrackerCollection::buildRootPlanningMilestoneTrackers($retriever, $teams, $user);

        $this->title_dao->method('getNbOfTrackerWithoutSemanticTitleDefined')
            ->willReturn(0);
        $this->description_dao->method('getNbOfTrackerWithoutSemanticDescriptionDefined')
            ->willReturn(1);

        self::assertFalse(
            $this->checker->areTrackerSemanticsWellConfigured($program_increment_tracker, $trackers)
        );
        self::assertTrue($this->logger->hasErrorThatContains('Description'));
    }

    public function testItReturnsFalseIfOneMilestoneTrackerDoesNotHaveTimeFrameSemantic(): void
    {
        $tracker                   = TrackerTestBuilder::aTracker()->withId(104)->build();
        $program_increment_tracker = new ProgramTracker($tracker);

        $first_team  = new Project(101, 'team_blue', 'Team Blue');
        $second_team = new Project(102, 'team_red', 'Team Red');
        $teams       = new TeamProjectsCollection([$first_team, $second_team]);
        $retriever   = RetrievePlanningMilestoneTrackerStub::withValidTrackerIds(1024, 2048);
        $user        = UserTestBuilder::aUser()->build();
        $trackers    = TrackerCollection::buildRootPlanningMilestoneTrackers($retriever, $teams, $user);

        $this->title_dao->method('getNbOfTrackerWithoutSemanticTitleDefined')
            ->willReturn(0);
        $this->description_dao->method('getNbOfTrackerWithoutSemanticDescriptionDefined')
            ->willReturn(0);
        $this->semantic_status_checker->method('isStatusWellConfigured')
            ->willReturn(true);
        $this->timeframe_dao->method('getNbOfTrackersWithoutTimeFrameSemanticDefined')
            ->willReturn(1);

        self::assertFalse(
            $this->checker->areTrackerSemanticsWellConfigured($program_increment_tracker, $trackers)
        );
        self::assertTrue($this->logger->hasErrorThatContains('Timeframe'));
    }

    public function testItReturnsFalseIfTimeFrameSemanticsDontUseTheSameFieldType(): void
    {
        $tracker                   = TrackerTestBuilder::aTracker()->withId(104)->build();
        $program_increment_tracker = new ProgramTracker($tracker);

        $first_team  = new Project(101, 'team_blue', 'Team Blue');
        $second_team = new Project(102, 'team_red', 'Team Red');
        $teams       = new TeamProjectsCollection([$first_team, $second_team]);
        $retriever   = RetrievePlanningMilestoneTrackerStub::withValidTrackerIds(1024, 2048);
        $user        = UserTestBuilder::aUser()->build();
        $trackers    = TrackerCollection::buildRootPlanningMilestoneTrackers($retriever, $teams, $user);

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

        self::assertFalse(
            $this->checker->areTrackerSemanticsWellConfigured($program_increment_tracker, $trackers)
        );
        self::assertTrue($this->logger->hasErrorThatContains('Timeframe'));
    }

    public function testItReturnsFalseIfOneStatusSemanticIsNotWellConfigured(): void
    {
        $tracker                   = TrackerTestBuilder::aTracker()->withId(104)->build();
        $program_increment_tracker = new ProgramTracker($tracker);

        $first_team  = new Project(101, 'team_blue', 'Team Blue');
        $second_team = new Project(102, 'team_red', 'Team Red');
        $teams       = new TeamProjectsCollection([$first_team, $second_team]);
        $retriever   = RetrievePlanningMilestoneTrackerStub::withValidTrackerIds(1024, 2048);
        $user        = UserTestBuilder::aUser()->build();
        $trackers    = TrackerCollection::buildRootPlanningMilestoneTrackers($retriever, $teams, $user);

        $this->title_dao->method('getNbOfTrackerWithoutSemanticTitleDefined')
            ->willReturn(0);
        $this->description_dao->method('getNbOfTrackerWithoutSemanticDescriptionDefined')
            ->willReturn(0);
        $this->timeframe_dao->method('getNbOfTrackersWithoutTimeFrameSemanticDefined')
            ->willReturn(0);
        $this->timeframe_dao->method('areTimeFrameSemanticsUsingSameTypeOfField')
            ->willReturn(true);
        $this->semantic_status_checker->expects(self::once())
            ->method('isStatusWellConfigured')
            ->willReturn(false);

        self::assertFalse(
            $this->checker->areTrackerSemanticsWellConfigured($program_increment_tracker, $trackers)
        );
        self::assertTrue($this->logger->hasErrorThatContains('Status'));
    }
}
