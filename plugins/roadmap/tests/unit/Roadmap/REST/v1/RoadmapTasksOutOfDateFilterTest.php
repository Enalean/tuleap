<?php
/**
 * Copyright (c) Enalean, 2021 - present. All Rights Reserved.
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

namespace Tuleap\Roadmap\REST\v1;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use TimePeriodWithoutWeekEnd;
use Tracker_Artifact_Changeset;
use Tracker_Artifact_ChangesetValue_List;
use Tracker_FormElement_Field_List_Bind_StaticValue;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\Semantic\Status\SemanticStatusRetriever;
use Tuleap\Tracker\Semantic\Timeframe\TimeframeBuilder;

class RoadmapTasksOutOfDateFilterTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var RoadmapTasksOutOfDateFilter
     */
    private $filter;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|\Tracker_Semantic_Status
     */
    private $semantic_status;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|Artifact
     */
    private $artifact;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|\Tracker_FormElement_Field_List
     */
    private $status_field;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|\Tracker
     */
    private $tracker;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|SemanticStatusRetriever
     */
    private $semantic_status_retriever;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|LoggerInterface
     */
    private $logger;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|TimeframeBuilder
     */
    private $timeframe_builder;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|\PFUser
     */
    private $user;

    private const TODO_VALUE_ID     = 128;
    private const ON_GOING_VALUE_ID = 129;
    private const DONE_VALUE_ID     = 130;

    protected function setUp(): void
    {
        $this->semantic_status           = \Mockery::mock(\Tracker_Semantic_Status::class);
        $this->semantic_status_retriever = \Mockery::mock(SemanticStatusRetriever::class);
        $this->artifact                  = \Mockery::mock(Artifact::class, ['getId' => 150]);
        $this->status_field              = \Mockery::mock(\Tracker_FormElement_Field_List::class);
        $this->tracker                   = \Mockery::mock(\Tracker::class);
        $this->logger                    = \Mockery::mock(LoggerInterface::class);
        $this->timeframe_builder         = \Mockery::mock(TimeframeBuilder::class);
        $this->user                      = \Mockery::mock(\PFUser::class);
        $this->filter                    = new RoadmapTasksOutOfDateFilter(
            $this->semantic_status_retriever,
            $this->timeframe_builder,
            $this->logger
        );

        $this->status_field->shouldReceive('getId')->andReturn(365);
        $this->semantic_status->shouldReceive('getOpenValues')->andReturn([self::TODO_VALUE_ID, self::ON_GOING_VALUE_ID]);
        $this->semantic_status_retriever->shouldReceive('retrieveSemantic')
            ->with($this->tracker)
            ->andReturn($this->semantic_status);
    }

    public function testItDoesNotFilterWhenTrackerHasNoStatusSemanticDefined(): void
    {
        $this->semantic_status->shouldReceive('getField')->once()->andReturn(null);

        $this->assertEquals(
            [$this->artifact],
            $this->filter->filterOutOfDateArtifacts(
                [$this->artifact],
                $this->tracker,
                new \DateTimeImmutable(),
                $this->user
            )
        );
    }

    public function testItDoesNotFilterOpenTasks(): void
    {
        $this->semantic_status->shouldReceive('getField')->once()->andReturn($this->status_field);
        $this->semantic_status->shouldReceive('isOpen')->with($this->artifact)->once()->andReturn(true);

        $this->assertEquals(
            [$this->artifact],
            $this->filter->filterOutOfDateArtifacts(
                [$this->artifact],
                $this->tracker,
                new \DateTimeImmutable(),
                $this->user
            )
        );
    }

    public function testItDoesNotFilterTasksClosedEarlierThanOneYearAgoWithNoEndDate(): void
    {
        $this->semantic_status->shouldReceive('getField')->once()->andReturn($this->status_field);
        $this->semantic_status->shouldReceive('isOpen')->with($this->artifact)->once()->andReturn(false);
        $this->timeframe_builder->shouldReceive('buildTimePeriodWithoutWeekendForArtifactForREST')
            ->with($this->artifact, $this->user)
            ->andReturn(
                $this->getTimePeriodWithoutWeekend("2021-01-01", null)
            );

        $this->artifact->shouldReceive('getChangesets')->once()->andReturn([
            $this->buildChangeset(1, "2021-04-13 15:30", true, self::TODO_VALUE_ID),
            $this->buildChangeset(2, "2021-04-13 16:30", true, self::ON_GOING_VALUE_ID),
            $this->buildChangeset(3, "2021-04-13 17:30", true, self::DONE_VALUE_ID), // Closed in this changeset
            $this->buildChangeset(4, "2021-04-13 18:30", false, self::DONE_VALUE_ID),
            $this->buildChangeset(5, "2021-04-13 19:30", false, self::DONE_VALUE_ID),
            $this->buildChangeset(6, "2021-04-13 20:30", false, self::DONE_VALUE_ID),
        ]);

        $this->assertEquals(
            [$this->artifact],
            $this->filter->filterOutOfDateArtifacts(
                [$this->artifact],
                $this->tracker,
                new \DateTimeImmutable("2021-04-14 08:30"),
                $this->user
            )
        );
    }

    public function testItFiltersTasksClosedLaterThanOneYearAgo(): void
    {
        $this->semantic_status->shouldReceive('getField')->once()->andReturn($this->status_field);
        $this->semantic_status->shouldReceive('isOpen')->with($this->artifact)->once()->andReturn(false);

        $this->artifact->shouldReceive('getChangesets')->once()->andReturn([
            $this->buildChangeset(1, "2018-04-13 15:30", true, self::TODO_VALUE_ID),
            $this->buildChangeset(2, "2018-04-13 16:30", true, self::ON_GOING_VALUE_ID),
            $this->buildChangeset(3, "2018-04-13 17:30", true, self::DONE_VALUE_ID), // Closed in this changeset
            $this->buildChangeset(4, "2021-04-13 18:30", false, self::DONE_VALUE_ID),
            $this->buildChangeset(5, "2021-04-13 19:30", false, self::DONE_VALUE_ID),
            $this->buildChangeset(6, "2021-04-13 20:30", false, self::DONE_VALUE_ID),
        ]);

        $this->assertEmpty(
            $this->filter->filterOutOfDateArtifacts(
                [$this->artifact],
                $this->tracker,
                new \DateTimeImmutable("2021-04-14 08:30"),
                $this->user
            )
        );
    }

    public function testItDoesNotFilterTasksReOpenAndReClosedEarlierThanOneYearAgo(): void
    {
        $this->semantic_status->shouldReceive('getField')->once()->andReturn($this->status_field);
        $this->semantic_status->shouldReceive('isOpen')->with($this->artifact)->once()->andReturn(false);
        $this->timeframe_builder->shouldReceive('buildTimePeriodWithoutWeekendForArtifactForREST')
            ->with($this->artifact, $this->user)
            ->andReturn(
                $this->getTimePeriodWithoutWeekend("2021-01-01", null)
            );

        $this->artifact->shouldReceive('getChangesets')->once()->andReturn([
            $this->buildChangeset(1, "2018-04-13 15:30", true, self::TODO_VALUE_ID),
            $this->buildChangeset(2, "2018-04-13 16:30", true, self::ON_GOING_VALUE_ID),
            $this->buildChangeset(3, "2018-04-13 17:30", true, self::DONE_VALUE_ID),      // Closed in this changeset
            $this->buildChangeset(4, "2021-04-13 18:30", true, self::ON_GOING_VALUE_ID),  // Re-open in this one
            $this->buildChangeset(5, "2021-04-13 19:30", false, self::ON_GOING_VALUE_ID),
            $this->buildChangeset(6, "2021-04-13 20:30", true, self::DONE_VALUE_ID),      // Closed again
            $this->buildChangeset(6, "2021-04-13 20:31", false, self::DONE_VALUE_ID)
        ]);

        $this->assertEquals(
            [$this->artifact],
            $this->filter->filterOutOfDateArtifacts(
                [$this->artifact],
                $this->tracker,
                new \DateTimeImmutable("2021-04-14 08:30"),
                $this->user
            )
        );
    }

    public function testItFiltersClosedArtifactWhenChangesetCantBeFound(): void
    {
        $this->semantic_status->shouldReceive('getField')->once()->andReturn($this->status_field);
        $this->semantic_status->shouldReceive('isOpen')->with($this->artifact)->once()->andReturn(false);

        $this->artifact->shouldReceive('getChangesets')->once()->andReturn([
            $this->buildChangeset(1, "2018-04-13 15:30", true, self::TODO_VALUE_ID),
            $this->buildChangeset(2, "2018-04-13 16:30", true, self::ON_GOING_VALUE_ID),
            $this->buildChangeset(3, "2018-04-13 17:30", true, self::ON_GOING_VALUE_ID)
        ]);

        $this->logger->shouldReceive('error')->once();
        $this->assertEmpty(
            $this->filter->filterOutOfDateArtifacts(
                [$this->artifact],
                $this->tracker,
                new \DateTimeImmutable("2021-04-14 08:30"),
                $this->user
            )
        );
    }

    /**
     * @testWith ["2020-01-15", 0]
     *           ["2021-01-15", 1]
     */
    public function testItFilterArtifactsThatAreClosedWhoseEndDateIsLaterThanOneYearAgo($end_string_date, $expected_number_of_artifacts): void
    {
        $now_string_date = "2021-04-14";

        $this->timeframe_builder->shouldReceive('buildTimePeriodWithoutWeekendForArtifactForREST')
            ->with($this->artifact, $this->user)
            ->andReturn(
                $this->getTimePeriodWithoutWeekend("2020-01-01", $end_string_date)
            );

        $this->semantic_status->shouldReceive('getField')->once()->andReturn($this->status_field);
        $this->semantic_status->shouldReceive('isOpen')->with($this->artifact)->once()->andReturn(false);

        $this->artifact->shouldReceive('getChangesets')->once()->andReturn([
            $this->buildChangeset(1, "2021-04-13 15:30", true, self::TODO_VALUE_ID),
            $this->buildChangeset(2, "2021-04-13 16:30", true, self::ON_GOING_VALUE_ID),
            $this->buildChangeset(3, "2021-04-13 17:30", true, self::DONE_VALUE_ID) // Closed in this changeset
        ]);

        $this->assertCount(
            $expected_number_of_artifacts,
            $this->filter->filterOutOfDateArtifacts(
                [$this->artifact],
                $this->tracker,
                new \DateTimeImmutable($now_string_date),
                $this->user,
            )
        );
    }

    private function buildChangeset(
        int $changeset_id,
        string $date_string,
        bool $has_changed,
        int $status_field_value_id
    ): Tracker_Artifact_Changeset {
        $submitted_on = new \DateTimeImmutable($date_string);
        $changeset    = new Tracker_Artifact_Changeset(
            $changeset_id,
            $this->artifact,
            104,
            $submitted_on->getTimestamp(),
            ''
        );

        $changeset->setFieldValue(
            $this->status_field,
            new Tracker_Artifact_ChangesetValue_List(
                365,
                $changeset,
                $this->status_field,
                $has_changed,
                [
                    new Tracker_FormElement_Field_List_Bind_StaticValue(
                        $status_field_value_id,
                        '',
                        '',
                        '',
                        false
                    )
                ]
            )
        );

        return $changeset;
    }

    private function getTimePeriodWithoutWeekend(string $start_date_string, ?string $end_date_string): TimePeriodWithoutWeekEnd
    {
        $end = $end_date_string !== null ? (new \DateTimeImmutable($end_date_string))->getTimestamp() : null;
        return TimePeriodWithoutWeekEnd::buildFromEndDate(
            (new \DateTimeImmutable($start_date_string))->getTimestamp(),
            $end,
            new NullLogger()
        );
    }
}
