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

namespace Tuleap\ScaledAgile\Program\Backlog\CreationCheck;

use Mockery as M;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tuleap\ScaledAgile\Program\Backlog\ProgramIncrement\Source\SourceTrackerCollection;
use Tuleap\ScaledAgile\Program\PlanningConfiguration\PlanningData;
use Tuleap\ScaledAgile\ProjectData;
use Tuleap\ScaledAgile\TrackerData;
use Tuleap\ScaledAgile\Adapter\TrackerDataAdapter;
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
        $this->semantic_status_checker = M::mock(StatusSemanticChecker::class);
        $this->checker                 = new SemanticChecker(
            $this->title_dao,
            $this->description_dao,
            $this->timeframe_dao,
            $this->semantic_status_checker,
        );
    }

    public function testItReturnsTrueIfAllChecksAreOk(): void
    {
        $top_planning_tracker = TrackerTestBuilder::aTracker()->withId(104)->build();
        $program_milestone    = new PlanningData(
            TrackerDataAdapter::build($top_planning_tracker),
            1,
            'Release Planning',
            [],
            new ProjectData(1, "my_project", "My project")
        );

        $first_tracker                = $this->buildTestTracker(1024);
        $second_tracker               = $this->buildTestTracker(2048);
        $milestone_tracker_collection = new SourceTrackerCollection([$first_tracker, $second_tracker]);

        $this->title_dao->shouldReceive('getNbOfTrackerWithoutSemanticTitleDefined')
            ->once()
            ->with([1024, 2048])
            ->andReturn(0);
        $this->description_dao->shouldReceive('getNbOfTrackerWithoutSemanticDescriptionDefined')
            ->once()
            ->with([1024, 2048])
            ->andReturn(0);
        $this->semantic_status_checker->shouldReceive('areSemanticStatusWellConfigured')
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

        $this->assertTrue(
            $this->checker->areTrackerSemanticsWellConfigured($program_milestone, $milestone_tracker_collection)
        );
    }

    public function testItReturnsFalseIfOneMilestoneTrackerDoesNotHaveTitleSemantic(): void
    {
        $top_planning_tracker = TrackerTestBuilder::aTracker()->withId(104)->build();
        $program_milestone    = new PlanningData(
            TrackerDataAdapter::build($top_planning_tracker),
            1,
            'Release Planning',
            [],
            new ProjectData(1, "my_project", "My project")
        );

        $first_tracker                = $this->buildTestTracker(1024);
        $second_tracker               = $this->buildTestTracker(2048);
        $milestone_tracker_collection = new SourceTrackerCollection([$first_tracker, $second_tracker]);

        $this->title_dao->shouldReceive('getNbOfTrackerWithoutSemanticTitleDefined')
            ->andReturn(1);

        $this->assertFalse(
            $this->checker->areTrackerSemanticsWellConfigured($program_milestone, $milestone_tracker_collection)
        );
    }

    public function testItReturnsFalseIfOneMilestoneTrackerDoesNotHaveDescriptionSemantic(): void
    {
        $top_planning_tracker = TrackerTestBuilder::aTracker()->withId(104)->build();
        $program_milestone    = new PlanningData(
            TrackerDataAdapter::build($top_planning_tracker),
            1,
            'Release Planning',
            [],
            new ProjectData(1, "my_project", "My project")
        );

        $first_tracker                = $this->buildTestTracker(1024);
        $second_tracker               = $this->buildTestTracker(2048);
        $milestone_tracker_collection = new SourceTrackerCollection([$first_tracker, $second_tracker]);

        $this->title_dao->shouldReceive('getNbOfTrackerWithoutSemanticTitleDefined')
            ->andReturn(0);
        $this->description_dao->shouldReceive('getNbOfTrackerWithoutSemanticDescriptionDefined')
            ->andReturn(1);

        $this->assertFalse(
            $this->checker->areTrackerSemanticsWellConfigured($program_milestone, $milestone_tracker_collection)
        );
    }

    public function testItReturnsFalseIfOneMilestoneTrackerDoesNotHaveTimeFrameSemantic(): void
    {
        $top_planning_tracker = TrackerTestBuilder::aTracker()->withId(104)->build();
        $program_milestone    = new PlanningData(
            TrackerDataAdapter::build($top_planning_tracker),
            1,
            'Release Planning',
            [],
            new ProjectData(1, "my_project", "My project")
        );

        $first_tracker                = $this->buildTestTracker(1024);
        $second_tracker               = $this->buildTestTracker(2048);
        $milestone_tracker_collection = new SourceTrackerCollection([$first_tracker, $second_tracker]);

        $this->title_dao->shouldReceive('getNbOfTrackerWithoutSemanticTitleDefined')
            ->andReturn(0);
        $this->description_dao->shouldReceive('getNbOfTrackerWithoutSemanticDescriptionDefined')
            ->andReturn(0);
        $this->semantic_status_checker->shouldReceive('areSemanticStatusWellConfigured')
            ->andReturnTrue();
        $this->timeframe_dao->shouldReceive('getNbOfTrackersWithoutTimeFrameSemanticDefined')
            ->andReturn(1);

        $this->assertFalse(
            $this->checker->areTrackerSemanticsWellConfigured($program_milestone, $milestone_tracker_collection)
        );
    }

    public function testItReturnsFalseIfTimeFrameSemanticsDontUseTheSameFieldType(): void
    {
        $top_planning_tracker = TrackerTestBuilder::aTracker()->withId(104)->build();
        $program_milestone    = new PlanningData(
            TrackerDataAdapter::build($top_planning_tracker),
            1,
            'Release Planning',
            [],
            new ProjectData(1, "my_project", "My project")
        );

        $first_tracker                = $this->buildTestTracker(1024);
        $second_tracker               = $this->buildTestTracker(2048);
        $milestone_tracker_collection = new SourceTrackerCollection([$first_tracker, $second_tracker]);

        $this->title_dao->shouldReceive('getNbOfTrackerWithoutSemanticTitleDefined')
            ->andReturn(0);
        $this->description_dao->shouldReceive('getNbOfTrackerWithoutSemanticDescriptionDefined')
            ->andReturn(0);
        $this->semantic_status_checker->shouldReceive('areSemanticStatusWellConfigured')
            ->andReturnTrue();
        $this->timeframe_dao->shouldReceive('getNbOfTrackersWithoutTimeFrameSemanticDefined')
            ->andReturn(0);
        $this->timeframe_dao->shouldReceive('areTimeFrameSemanticsUsingSameTypeOfField')
            ->andReturnFalse();

        $this->assertFalse(
            $this->checker->areTrackerSemanticsWellConfigured($program_milestone, $milestone_tracker_collection)
        );
    }

    public function testItReturnsFalseIfOneStatusSemanticIsNotWellConfigured(): void
    {
        $top_planning_tracker = TrackerTestBuilder::aTracker()->withId(104)->build();
        $program_milestone    = new PlanningData(
            TrackerDataAdapter::build($top_planning_tracker),
            1,
            'Release Planning',
            [],
            new ProjectData(1, "my_project", "My project")
        );

        $first_tracker                = $this->buildTestTracker(1024);
        $second_tracker               = $this->buildTestTracker(2048);
        $milestone_tracker_collection = new SourceTrackerCollection([$first_tracker, $second_tracker]);

        $this->title_dao->shouldReceive('getNbOfTrackerWithoutSemanticTitleDefined')
            ->andReturn(0);
        $this->description_dao->shouldReceive('getNbOfTrackerWithoutSemanticDescriptionDefined')
            ->andReturn(0);
        $this->timeframe_dao->shouldReceive('getNbOfTrackersWithoutTimeFrameSemanticDefined')
            ->andReturn(0);
        $this->timeframe_dao->shouldReceive('areTimeFrameSemanticsUsingSameTypeOfField')
            ->andReturnTrue();
        $this->semantic_status_checker->shouldReceive('areSemanticStatusWellConfigured')
            ->once()
            ->andReturnFalse();

        $this->assertFalse(
            $this->checker->areTrackerSemanticsWellConfigured($program_milestone, $milestone_tracker_collection)
        );
    }

    private function buildTestTracker(int $tracker_id): TrackerData
    {
        return TrackerDataAdapter::build(
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
