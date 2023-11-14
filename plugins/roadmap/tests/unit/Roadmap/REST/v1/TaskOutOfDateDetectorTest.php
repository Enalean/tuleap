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

use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Tracker_Artifact_Changeset;
use Tracker_Artifact_ChangesetValue_List;
use Tracker_FormElement_Field_List_Bind_StaticValue;
use Tuleap\Date\DatePeriodWithoutWeekEnd;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\Semantic\Status\SemanticStatusRetriever;
use Tuleap\Tracker\Semantic\Timeframe\SemanticTimeframe;
use Tuleap\Tracker\Semantic\Timeframe\SemanticTimeframeBuilder;
use Tuleap\Tracker\Semantic\Timeframe\TimeframeWithEndDate;
use Tuleap\Tracker\Test\Builders\ArtifactTestBuilder;

final class TaskOutOfDateDetectorTest extends TestCase
{
    private TaskOutOfDateDetector $detector;
    private MockObject&\Tracker_Semantic_Status $semantic_status;
    private Artifact $artifact;
    private \Tracker_FormElement_Field_List&MockObject $status_field;
    private LoggerInterface&MockObject $logger;
    private \PFUser $user;
    private MockObject&TimeframeWithEndDate $timeframe_calculator;
    private TrackersWithUnreadableStatusCollection $trackers_with_unreadable_status_collection;

    private const TODO_VALUE_ID     = 128;
    private const ON_GOING_VALUE_ID = 129;
    private const DONE_VALUE_ID     = 130;

    protected function setUp(): void
    {
        $this->artifact = ArtifactTestBuilder::anArtifact(150)->build();

        $this->status_field = $this->createMock(\Tracker_FormElement_Field_List::class);
        $this->logger       = $this->createMock(LoggerInterface::class);
        $this->user         = UserTestBuilder::aUser()->build();

        $this->status_field->method('getId')->willReturn(365);

        $this->semantic_status = $this->createMock(\Tracker_Semantic_Status::class);
        $this->semantic_status->method('getOpenValues')->willReturn([self::TODO_VALUE_ID, self::ON_GOING_VALUE_ID]);

        $this->timeframe_calculator = $this->createMock(TimeframeWithEndDate::class);
        $semantic_timeframe         = $this->createMock(SemanticTimeframe::class);

        $semantic_timeframe->method('getTimeframeCalculator')->willReturn($this->timeframe_calculator);

        $semantic_status_retriever = $this->createMock(SemanticStatusRetriever::class);
        $semantic_status_retriever->method('retrieveSemantic')
            ->with($this->artifact->getTracker())
            ->willReturn($this->semantic_status);

        $semantic_timeframe_builder = $this->createMock(SemanticTimeframeBuilder::class);
        $semantic_timeframe_builder->method('getSemantic')
            ->with($this->artifact->getTracker())
            ->willReturn($semantic_timeframe);

        $this->detector = new TaskOutOfDateDetector(
            $semantic_status_retriever,
            $semantic_timeframe_builder,
            $this->logger
        );

        $this->trackers_with_unreadable_status_collection = new TrackersWithUnreadableStatusCollection($this->logger);
    }

    public function testItReturnsFalseWhenTrackerHasNoStatusSemanticDefined(): void
    {
        $this->semantic_status->expects(self::once())->method('getField')->willReturn(null);

        self::assertFalse(
            $this->detector->isArtifactOutOfDate(
                $this->artifact,
                new \DateTimeImmutable(),
                $this->user,
                $this->trackers_with_unreadable_status_collection,
            )
        );
    }

    public function testItReturnsFalseForOpenTasks(): void
    {
        $this->semantic_status->expects(self::once())->method('getField')->willReturn($this->status_field);
        $this->semantic_status->expects(self::once())->method('isOpen')->with($this->artifact)->willReturn(true);

        self::assertFalse(
            $this->detector->isArtifactOutOfDate(
                $this->artifact,
                new \DateTimeImmutable(),
                $this->user,
                $this->trackers_with_unreadable_status_collection,
            )
        );
    }

    public function testItReturnsFalseForTasksClosedEarlierThanOneYearAgoWithNoEndDate(): void
    {
        $this->status_field
            ->expects(self::once())
            ->method('userCanRead')
            ->with($this->user)
            ->willReturn(true);

        $this->semantic_status->expects(self::once())->method('getField')->willReturn($this->status_field);
        $this->semantic_status->expects(self::once())->method('isOpen')->with($this->artifact)->willReturn(false);
        $this->timeframe_calculator->method('buildDatePeriodWithoutWeekendForArtifactForREST')
            ->with($this->artifact, $this->user, $this->logger)
            ->willReturn(
                $this->getDatePeriodWithoutWeekend("2021-01-01", null)
            );

        $this->artifact->setChangesets(
            [
                $this->buildChangeset(1, "2021-04-13 15:30", true, self::TODO_VALUE_ID),
                $this->buildChangeset(2, "2021-04-13 16:30", true, self::ON_GOING_VALUE_ID),
                $this->buildChangeset(3, "2021-04-13 17:30", true, self::DONE_VALUE_ID), // Closed in this changeset
                $this->buildChangeset(4, "2021-04-13 18:30", false, self::DONE_VALUE_ID),
                $this->buildChangeset(5, "2021-04-13 19:30", false, self::DONE_VALUE_ID),
                $this->buildChangeset(6, "2021-04-13 20:30", false, self::DONE_VALUE_ID),
            ]
        );

        self::assertFalse(
            $this->detector->isArtifactOutOfDate(
                $this->artifact,
                new \DateTimeImmutable("2021-04-14 08:30"),
                $this->user,
                $this->trackers_with_unreadable_status_collection,
            )
        );
    }

    public function testItReturnsTrueForTasksClosedLaterThanOneYearAgo(): void
    {
        $this->status_field
            ->expects(self::once())
            ->method('userCanRead')
            ->with($this->user)
            ->willReturn(true);

        $this->semantic_status->expects(self::once())->method('getField')->willReturn($this->status_field);
        $this->semantic_status->expects(self::once())->method('isOpen')->with($this->artifact)->willReturn(false);

        $this->artifact->setChangesets(
            [
                $this->buildChangeset(1, "2018-04-13 15:30", true, self::TODO_VALUE_ID),
                $this->buildChangeset(2, "2018-04-13 16:30", true, self::ON_GOING_VALUE_ID),
                $this->buildChangeset(3, "2018-04-13 17:30", true, self::DONE_VALUE_ID), // Closed in this changeset
                $this->buildChangeset(4, "2021-04-13 18:30", false, self::DONE_VALUE_ID),
                $this->buildChangeset(5, "2021-04-13 19:30", false, self::DONE_VALUE_ID),
                $this->buildChangeset(6, "2021-04-13 20:30", false, self::DONE_VALUE_ID),
            ]
        );

        self::assertTrue(
            $this->detector->isArtifactOutOfDate(
                $this->artifact,
                new \DateTimeImmutable("2021-04-14 08:30"),
                $this->user,
                $this->trackers_with_unreadable_status_collection,
            )
        );
    }

    public function testItReturnsFalseForTasksReOpenAndReClosedEarlierThanOneYearAgo(): void
    {
        $this->status_field
            ->expects(self::once())
            ->method('userCanRead')
            ->with($this->user)
            ->willReturn(true);

        $this->semantic_status->expects(self::once())->method('getField')->willReturn($this->status_field);
        $this->semantic_status->expects(self::once())->method('isOpen')->with($this->artifact)->willReturn(false);
        $this->timeframe_calculator->method('buildDatePeriodWithoutWeekendForArtifactForREST')
            ->with($this->artifact, $this->user, $this->logger)
            ->willReturn(
                $this->getDatePeriodWithoutWeekend("2021-01-01", null)
            );

        $this->artifact->setChangesets(
            [
                $this->buildChangeset(1, "2018-04-13 15:30", true, self::TODO_VALUE_ID),
                $this->buildChangeset(2, "2018-04-13 16:30", true, self::ON_GOING_VALUE_ID),
                $this->buildChangeset(3, "2018-04-13 17:30", true, self::DONE_VALUE_ID),
                // Closed in this changeset
                $this->buildChangeset(4, "2021-04-13 18:30", true, self::ON_GOING_VALUE_ID),
                // Re-open in this one
                $this->buildChangeset(5, "2021-04-13 19:30", false, self::ON_GOING_VALUE_ID),
                $this->buildChangeset(6, "2021-04-13 20:30", true, self::DONE_VALUE_ID),
                // Closed again
                $this->buildChangeset(6, "2021-04-13 20:31", false, self::DONE_VALUE_ID),
            ]
        );

        self::assertFalse(
            $this->detector->isArtifactOutOfDate(
                $this->artifact,
                new \DateTimeImmutable("2021-04-14 08:30"),
                $this->user,
                $this->trackers_with_unreadable_status_collection,
            )
        );
    }

    public function testItReturnsTrueForTasksWithoutStatus(): void
    {
        $this->status_field
            ->expects(self::once())
            ->method('userCanRead')
            ->with($this->user)
            ->willReturn(true);

        $this->semantic_status->expects(self::once())->method('getField')->willReturn($this->status_field);
        $this->semantic_status->expects(self::once())->method('isOpen')->with($this->artifact)->willReturn(false);
        $this->timeframe_calculator->method('buildDatePeriodWithoutWeekendForArtifactForREST')
            ->with($this->artifact, $this->user, $this->logger)
            ->willReturn(
                $this->getDatePeriodWithoutWeekend("2021-01-01", null)
            );

        $submitted_on = new \DateTimeImmutable("2021-04-13 15:30");
        $changeset    = new Tracker_Artifact_Changeset(
            1,
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
                true,
                []
            )
        );

        $this->artifact->setChangesets([$changeset]);

        $this->logger->expects(self::once())->method('error');
        self::assertTrue(
            $this->detector->isArtifactOutOfDate(
                $this->artifact,
                new \DateTimeImmutable("2021-04-14 08:30"),
                $this->user,
                $this->trackers_with_unreadable_status_collection,
            )
        );
    }

    public function testItReturnsTrueForTasksWithUnreadableStatus(): void
    {
        $this->status_field
            ->expects(self::once())
            ->method('userCanRead')
            ->with($this->user)
            ->willReturn(false);

        $this->semantic_status->expects(self::once())->method('getField')->willReturn($this->status_field);
        $this->semantic_status->expects(self::once())->method('isOpen')->with($this->artifact)->willReturn(false);
        $this->timeframe_calculator->method('buildDatePeriodWithoutWeekendForArtifactForREST')
            ->with($this->artifact, $this->user, $this->logger)
            ->willReturn(
                $this->getDatePeriodWithoutWeekend("2021-01-01", null)
            );

        $submitted_on = new \DateTimeImmutable("2021-04-13 15:30");
        $changeset    = new Tracker_Artifact_Changeset(
            1,
            $this->artifact,
            104,
            $submitted_on->getTimestamp(),
            ''
        );

        $this->artifact->setChangesets(
            [
                $this->buildChangeset(1, "2018-04-13 15:30", true, self::TODO_VALUE_ID),
                $this->buildChangeset(2, "2018-04-13 16:30", true, self::ON_GOING_VALUE_ID),
                $this->buildChangeset(3, "2018-04-13 17:30", true, self::ON_GOING_VALUE_ID),
            ]
        );

        $changeset->setFieldValue(
            $this->status_field,
            new Tracker_Artifact_ChangesetValue_List(
                365,
                $changeset,
                $this->status_field,
                true,
                []
            )
        );

        $this->artifact->setChangesets([$changeset]);

        self::assertTrue(
            $this->detector->isArtifactOutOfDate(
                $this->artifact,
                new \DateTimeImmutable("2021-04-14 08:30"),
                $this->user,
                $this->trackers_with_unreadable_status_collection,
            )
        );

        $this->logger->expects(self::once())->method('info');
        $this->trackers_with_unreadable_status_collection->informLoggerIfWeHaveTrackersWithUnreadableStatus();
    }

    public function testItReturnsTrueForClosedArtifactWhenChangesetCantBeFound(): void
    {
        $this->status_field
            ->expects(self::once())
            ->method('userCanRead')
            ->with($this->user)
            ->willReturn(true);

        $this->semantic_status->expects(self::once())->method('getField')->willReturn($this->status_field);
        $this->semantic_status->expects(self::once())->method('isOpen')->with($this->artifact)->willReturn(false);

        $this->artifact->setChangesets(
            [
                $this->buildChangeset(1, "2018-04-13 15:30", true, self::TODO_VALUE_ID),
                $this->buildChangeset(2, "2018-04-13 16:30", true, self::ON_GOING_VALUE_ID),
                $this->buildChangeset(3, "2018-04-13 17:30", true, self::ON_GOING_VALUE_ID),
            ]
        );

        $this->logger->expects(self::once())->method('error');
        self::assertTrue(
            $this->detector->isArtifactOutOfDate(
                $this->artifact,
                new \DateTimeImmutable("2021-04-14 08:30"),
                $this->user,
                $this->trackers_with_unreadable_status_collection,
            )
        );
    }

    /**
     * @testWith ["2020-01-15", true]
     *           ["2021-01-15", false]
     *           ["2023-01-15", false]
     */
    public function testItReturnsTrueForArtifactsThatAreClosedWhoseEndDateIsLaterThanOneYearAgo(
        string $end_string_date,
        bool $expected_out_of_date,
    ): void {
        $this->status_field
            ->expects(self::once())
            ->method('userCanRead')
            ->with($this->user)
            ->willReturn(true);

        $now_string_date = "2021-04-14";

        $this->timeframe_calculator->method('buildDatePeriodWithoutWeekendForArtifactForREST')
            ->with($this->artifact, $this->user, $this->logger)
            ->willReturn(
                $this->getDatePeriodWithoutWeekend("2020-01-01", $end_string_date)
            );

        $this->semantic_status->expects(self::once())->method('getField')->willReturn($this->status_field);
        $this->semantic_status->expects(self::once())->method('isOpen')->with($this->artifact)->willReturn(false);

        $this->artifact->setChangesets(
            [
                $this->buildChangeset(1, "2021-04-13 15:30", true, self::TODO_VALUE_ID),
                $this->buildChangeset(2, "2021-04-13 16:30", true, self::ON_GOING_VALUE_ID),
                $this->buildChangeset(3, "2021-04-13 17:30", true, self::DONE_VALUE_ID), // Closed in this changeset
            ]
        );

        self::assertEquals(
            $expected_out_of_date,
            $this->detector->isArtifactOutOfDate(
                $this->artifact,
                new \DateTimeImmutable($now_string_date),
                $this->user,
                $this->trackers_with_unreadable_status_collection,
            )
        );
    }

    private function buildChangeset(
        int $changeset_id,
        string $date_string,
        bool $has_changed,
        int $status_field_value_id,
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
                    ),
                ]
            )
        );

        return $changeset;
    }

    private function getDatePeriodWithoutWeekend(
        string $start_date_string,
        ?string $end_date_string,
    ): DatePeriodWithoutWeekEnd {
        $end = $end_date_string !== null ? (new \DateTimeImmutable($end_date_string))->getTimestamp() : null;

        return DatePeriodWithoutWeekEnd::buildFromEndDate(
            (new \DateTimeImmutable($start_date_string))->getTimestamp(),
            $end,
            new NullLogger()
        );
    }
}
