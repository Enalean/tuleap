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
use Tracker_Artifact_Changeset;
use Tracker_Artifact_ChangesetValue_List;
use Tracker_FormElement_Field_List_Bind_StaticValue;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\Semantic\Status\SemanticStatusRetriever;

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
        $this->filter                    = new RoadmapTasksOutOfDateFilter(
            $this->semantic_status_retriever,
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
                new \DateTimeImmutable()
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
                new \DateTimeImmutable()
            )
        );
    }

    public function testItDoesNotFilterTasksClosedEarlierThanOneYearAgo(): void
    {
        $this->semantic_status->shouldReceive('getField')->once()->andReturn($this->status_field);
        $this->semantic_status->shouldReceive('isOpen')->with($this->artifact)->once()->andReturn(false);

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
                new \DateTimeImmutable("2021-04-14 08:30")
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
                new \DateTimeImmutable("2021-04-14 08:30")
            )
        );
    }

    public function testItDoesNotFilterTasksReOpenAndReClosedEarlierThanOneYearAgo(): void
    {
        $this->semantic_status->shouldReceive('getField')->once()->andReturn($this->status_field);
        $this->semantic_status->shouldReceive('isOpen')->with($this->artifact)->once()->andReturn(false);

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
                new \DateTimeImmutable("2021-04-14 08:30")
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
                new \DateTimeImmutable("2021-04-14 08:30")
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
}
