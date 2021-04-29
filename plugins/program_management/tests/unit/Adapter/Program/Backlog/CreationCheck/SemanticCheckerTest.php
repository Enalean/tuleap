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

use Mockery as M;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tuleap\ProgramManagement\Domain\Program\Backlog\CreationCheck\CheckStatus;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Team\ProgramIncrementsTrackerCollection;
use Tuleap\ProgramManagement\Domain\ProgramTracker;
use Tuleap\Tracker\Semantic\Timeframe\SemanticTimeframeDao;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;
use Tuleap\Tracker\TrackerColor;

final class SemanticCheckerTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var SemanticChecker
     */
    private $checker;
    /**
     * @var M\LegacyMockInterface|M\MockInterface|\Tracker_Semantic_TitleDao
     */
    private $title_dao;
    /**
     * @var M\LegacyMockInterface|M\MockInterface|\Tracker_Semantic_DescriptionDao
     */
    private $description_dao;
    /**
     * @var M\LegacyMockInterface|M\MockInterface|StatusSemanticChecker
     */
    private $semantic_status_checker;
    /**
     * @var M\LegacyMockInterface|M\MockInterface|SemanticTimeframeDao
     */
    private $timeframe_dao;

    protected function setUp(): void
    {
        $this->title_dao               = M::mock(\Tracker_Semantic_TitleDao::class);
        $this->description_dao         = M::mock(\Tracker_Semantic_DescriptionDao::class);
        $this->timeframe_dao           = M::mock(SemanticTimeframeDao::class);
        $this->semantic_status_checker = M::mock(CheckStatus::class);
        $this->checker                 = new SemanticChecker(
            $this->title_dao,
            $this->description_dao,
            $this->timeframe_dao,
            $this->semantic_status_checker,
        );
    }

    public function testItReturnsTrueIfAllChecksAreOk(): void
    {
        $tracker                   = TrackerTestBuilder::aTracker()->withId(104)->build();
        $program_increment_tracker = new ProgramTracker($tracker);

        $first_tracker                = $this->buildTestTracker(1024);
        $second_tracker               = $this->buildTestTracker(2048);
        $milestone_tracker_collection = new ProgramIncrementsTrackerCollection([$first_tracker, $second_tracker]);

        $this->title_dao->shouldReceive('getNbOfTrackerWithoutSemanticTitleDefined')
            ->once()
            ->with([1024, 2048])
            ->andReturn(0);
        $this->description_dao->shouldReceive('getNbOfTrackerWithoutSemanticDescriptionDefined')
            ->once()
            ->with([1024, 2048])
            ->andReturn(0);
        $this->semantic_status_checker->shouldReceive('isStatusWellConfigured')
            ->once()
            ->andReturnTrue();
        $this->timeframe_dao->shouldReceive('getNbOfTrackersWithoutTimeFrameSemanticDefined')
            ->once()
            ->with([1024, 2048])
            ->andReturn(0);
        $this->timeframe_dao->shouldReceive('areTimeFrameSemanticsUsingSameTypeOfField')
            ->once()
            ->with([1024, 2048])
            ->andReturnTrue();

        self::assertTrue(
            $this->checker->areTrackerSemanticsWellConfigured($program_increment_tracker, $milestone_tracker_collection)
        );
    }

    public function testItReturnsFalseIfOneMilestoneTrackerDoesNotHaveTitleSemantic(): void
    {
        $tracker                   = TrackerTestBuilder::aTracker()->withId(104)->build();
        $program_increment_tracker = new ProgramTracker($tracker);

        $first_tracker                = $this->buildTestTracker(1024);
        $second_tracker               = $this->buildTestTracker(2048);
        $milestone_tracker_collection = new ProgramIncrementsTrackerCollection([$first_tracker, $second_tracker]);

        $this->title_dao->shouldReceive('getNbOfTrackerWithoutSemanticTitleDefined')
            ->andReturn(1);

        self::assertFalse(
            $this->checker->areTrackerSemanticsWellConfigured($program_increment_tracker, $milestone_tracker_collection)
        );
    }

    public function testItReturnsFalseIfOneMilestoneTrackerDoesNotHaveDescriptionSemantic(): void
    {
        $tracker                   = TrackerTestBuilder::aTracker()->withId(104)->build();
        $program_increment_tracker = new ProgramTracker($tracker);

        $first_tracker                = $this->buildTestTracker(1024);
        $second_tracker               = $this->buildTestTracker(2048);
        $milestone_tracker_collection = new ProgramIncrementsTrackerCollection([$first_tracker, $second_tracker]);

        $this->title_dao->shouldReceive('getNbOfTrackerWithoutSemanticTitleDefined')
            ->andReturn(0);
        $this->description_dao->shouldReceive('getNbOfTrackerWithoutSemanticDescriptionDefined')
            ->andReturn(1);

        self::assertFalse(
            $this->checker->areTrackerSemanticsWellConfigured($program_increment_tracker, $milestone_tracker_collection)
        );
    }

    public function testItReturnsFalseIfOneMilestoneTrackerDoesNotHaveTimeFrameSemantic(): void
    {
        $tracker                      = TrackerTestBuilder::aTracker()->withId(104)->build();
        $program_increment_tracker    = new ProgramTracker($tracker);
        $first_tracker                = $this->buildTestTracker(1024);
        $second_tracker               = $this->buildTestTracker(2048);
        $milestone_tracker_collection = new ProgramIncrementsTrackerCollection([$first_tracker, $second_tracker]);

        $this->title_dao->shouldReceive('getNbOfTrackerWithoutSemanticTitleDefined')
            ->andReturn(0);
        $this->description_dao->shouldReceive('getNbOfTrackerWithoutSemanticDescriptionDefined')
            ->andReturn(0);
        $this->semantic_status_checker->shouldReceive('isStatusWellConfigured')
            ->andReturnTrue();
        $this->timeframe_dao->shouldReceive('getNbOfTrackersWithoutTimeFrameSemanticDefined')
            ->andReturn(1);

        self::assertFalse(
            $this->checker->areTrackerSemanticsWellConfigured($program_increment_tracker, $milestone_tracker_collection)
        );
    }

    public function testItReturnsFalseIfTimeFrameSemanticsDontUseTheSameFieldType(): void
    {
        $tracker                   = TrackerTestBuilder::aTracker()->withId(104)->build();
        $program_increment_tracker = new ProgramTracker($tracker);

        $first_tracker                = $this->buildTestTracker(1024);
        $second_tracker               = $this->buildTestTracker(2048);
        $milestone_tracker_collection = new ProgramIncrementsTrackerCollection([$first_tracker, $second_tracker]);

        $this->title_dao->shouldReceive('getNbOfTrackerWithoutSemanticTitleDefined')
            ->andReturn(0);
        $this->description_dao->shouldReceive('getNbOfTrackerWithoutSemanticDescriptionDefined')
            ->andReturn(0);
        $this->semantic_status_checker->shouldReceive('isStatusWellConfigured')
            ->andReturnTrue();
        $this->timeframe_dao->shouldReceive('getNbOfTrackersWithoutTimeFrameSemanticDefined')
            ->andReturn(0);
        $this->timeframe_dao->shouldReceive('areTimeFrameSemanticsUsingSameTypeOfField')
            ->andReturnFalse();

        self::assertFalse(
            $this->checker->areTrackerSemanticsWellConfigured($program_increment_tracker, $milestone_tracker_collection)
        );
    }

    public function testItReturnsFalseIfOneStatusSemanticIsNotWellConfigured(): void
    {
        $tracker                   = TrackerTestBuilder::aTracker()->withId(104)->build();
        $program_increment_tracker = new ProgramTracker($tracker);

        $first_tracker                = $this->buildTestTracker(1024);
        $second_tracker               = $this->buildTestTracker(2048);
        $milestone_tracker_collection = new ProgramIncrementsTrackerCollection([$first_tracker, $second_tracker]);

        $this->title_dao->shouldReceive('getNbOfTrackerWithoutSemanticTitleDefined')
            ->andReturn(0);
        $this->description_dao->shouldReceive('getNbOfTrackerWithoutSemanticDescriptionDefined')
            ->andReturn(0);
        $this->timeframe_dao->shouldReceive('getNbOfTrackersWithoutTimeFrameSemanticDefined')
            ->andReturn(0);
        $this->timeframe_dao->shouldReceive('areTimeFrameSemanticsUsingSameTypeOfField')
            ->andReturnTrue();
        $this->semantic_status_checker->shouldReceive('isStatusWellConfigured')
            ->once()
            ->andReturnFalse();

        self::assertFalse(
            $this->checker->areTrackerSemanticsWellConfigured($program_increment_tracker, $milestone_tracker_collection)
        );
    }

    private function buildTestTracker(int $tracker_id): ProgramTracker
    {
        return new ProgramTracker(
            new \Tracker(
                $tracker_id,
                null,
                'Irrelevant',
                'Irrelevant',
                'irrelevant',
                false,
                null,
                null,
                null,
                null,
                true,
                false,
                \Tracker::NOTIFICATIONS_LEVEL_DEFAULT,
                TrackerColor::default(),
                false
            )
        );
    }
}
