<?php
/**
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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

namespace Tuleap\Taskboard\Column\FieldValuesToColumnMapping;

use PHPUnit\Framework\MockObject\MockObject;
use Tuleap\AgileDashboard\Test\Builders\PlanningBuilder;
use Tuleap\Cardwall\Test\Builders\ColumnTestBuilder;
use Tuleap\Taskboard\Column\FieldValuesToColumnMapping\Freestyle\FreestyleMappedFieldRetriever;
use Tuleap\Taskboard\Column\FieldValuesToColumnMapping\Freestyle\FreestyleMappedFieldValuesRetriever;
use Tuleap\Taskboard\Column\FieldValuesToColumnMapping\Freestyle\SearchMappedFieldStub;
use Tuleap\Taskboard\Column\FieldValuesToColumnMapping\Freestyle\SearchMappedFieldValuesForColumnStub;
use Tuleap\Taskboard\Column\FieldValuesToColumnMapping\Freestyle\VerifyMappingExistsStub;
use Tuleap\Taskboard\Tracker\TaskboardTracker;
use Tuleap\Taskboard\Tracker\TrackerCollection;
use Tuleap\Taskboard\Tracker\TrackerCollectionRetriever;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Tracker\Test\Builders\ArtifactTestBuilder;
use Tuleap\Tracker\Test\Builders\Fields\ListFieldBuilder;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;
use Tuleap\Tracker\Test\Stub\FormElement\Field\ListFields\RetrieveUsedListFieldStub;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class TrackerMappingPresenterBuilderTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private const FIRST_MAPPED_FIELD_ID   = 3086;
    private const SECOND_MAPPED_FIELD_ID  = 4597;
    private const USER_STORIES_TRACKER_ID = 76;
    private const TASKS_TRACKER_ID        = 83;
    private SearchMappedFieldStub $search_mapped_field;
    private RetrieveUsedListFieldStub $field_retriever;
    private MockObject&TrackerCollectionRetriever $trackers_retriever;
    private SearchMappedFieldValuesForColumnStub $search_values;
    private \Planning_Milestone $milestone;
    private \Cardwall_Column $ongoing_column;

    protected function setUp(): void
    {
        $this->search_mapped_field = SearchMappedFieldStub::withNoField();
        $this->field_retriever     = RetrieveUsedListFieldStub::withNoField();
        $this->trackers_retriever  = $this->createMock(TrackerCollectionRetriever::class);
        $this->search_values       = SearchMappedFieldValuesForColumnStub::withNoMappedValue();

        $project_id           = 174;
        $this->milestone      = new \Planning_ArtifactMilestone(
            ProjectTestBuilder::aProject()->withId($project_id)->build(),
            PlanningBuilder::aPlanning($project_id)->build(),
            ArtifactTestBuilder::anArtifact(645)->build()
        );
        $this->ongoing_column = ColumnTestBuilder::aColumn()->withId(25)->build();
    }

    /** @return TrackerMappingPresenter[] */
    private function buildMappings(): array
    {
        $status_retriever = $this->createStub(\Cardwall_FieldProviders_SemanticStatusFieldRetriever::class);

        $builder = new TrackerMappingPresenterBuilder(
            $this->trackers_retriever,
            new MappedFieldRetriever(
                $status_retriever,
                new FreestyleMappedFieldRetriever(
                    $this->search_mapped_field,
                    $this->field_retriever
                )
            ),
            new MappedValuesRetriever(
                new FreestyleMappedFieldValuesRetriever(
                    VerifyMappingExistsStub::withMapping(),
                    $this->search_values
                ),
                $status_retriever
            )
        );
        return $builder->buildMappings($this->milestone, $this->ongoing_column);
    }

    public function testNoTrackers(): void
    {
        $this->trackers_retriever->expects(self::once())
            ->method('getTrackersForMilestone')
            ->with($this->milestone)
            ->willReturn(new TrackerCollection([]));

        self::assertSame([], $this->buildMappings());
    }

    public function testNoValuesForTracker(): void
    {
        $taskboard_tracker = new TaskboardTracker(
            TrackerTestBuilder::aTracker()->withId(79)->build(),
            TrackerTestBuilder::aTracker()->withId(self::USER_STORIES_TRACKER_ID)->build()
        );
        $this->trackers_retriever->expects(self::once())
            ->method('getTrackersForMilestone')
            ->with($this->milestone)
            ->willReturn(new TrackerCollection([$taskboard_tracker]));
        $this->field_retriever     = RetrieveUsedListFieldStub::withField(
            ListFieldBuilder::aListField(self::FIRST_MAPPED_FIELD_ID)->build()
        );
        $this->search_mapped_field = SearchMappedFieldStub::withMappedField(
            $taskboard_tracker,
            self::FIRST_MAPPED_FIELD_ID
        );
        $this->search_values       = SearchMappedFieldValuesForColumnStub::withNoMappedValue();

        $expected_empty_mapping = new TrackerMappingPresenter(
            self::USER_STORIES_TRACKER_ID,
            self::FIRST_MAPPED_FIELD_ID,
            []
        );
        self::assertEquals([$expected_empty_mapping], $this->buildMappings());
    }

    public function testBuildMappingsReturnsMappingsForGivenColumn(): void
    {
        $taskboard_tracker = new TaskboardTracker(
            TrackerTestBuilder::aTracker()->withId(79)->build(),
            TrackerTestBuilder::aTracker()->withId(self::USER_STORIES_TRACKER_ID)->build()
        );
        $this->trackers_retriever->expects(self::once())
            ->method('getTrackersForMilestone')
            ->with($this->milestone)
            ->willReturn(new TrackerCollection([$taskboard_tracker]));
        $this->field_retriever     = RetrieveUsedListFieldStub::withField(
            ListFieldBuilder::aListField(self::FIRST_MAPPED_FIELD_ID)->build()
        );
        $this->search_mapped_field = SearchMappedFieldStub::withMappedField(
            $taskboard_tracker,
            self::FIRST_MAPPED_FIELD_ID
        );
        $this->search_values       = SearchMappedFieldValuesForColumnStub::withValues(
            $taskboard_tracker,
            $this->ongoing_column,
            [1674]
        );

        $expected_mapping = new TrackerMappingPresenter(
            self::USER_STORIES_TRACKER_ID,
            self::FIRST_MAPPED_FIELD_ID,
            [new ListFieldValuePresenter(1674)]
        );
        self::assertEquals([$expected_mapping], $this->buildMappings());
    }

    public function testBuildMappingsMultipleTrackers(): void
    {
        $milestone_tracker        = TrackerTestBuilder::aTracker()->withId(79)->build();
        $first_taskboard_tracker  = new TaskboardTracker(
            $milestone_tracker,
            TrackerTestBuilder::aTracker()->withId(self::USER_STORIES_TRACKER_ID)->build()
        );
        $second_taskboard_tracker = new TaskboardTracker(
            $milestone_tracker,
            TrackerTestBuilder::aTracker()->withId(self::TASKS_TRACKER_ID)->build()
        );
        $this->trackers_retriever->expects(self::once())
            ->method('getTrackersForMilestone')
            ->with($this->milestone)
            ->willReturn(new TrackerCollection([$first_taskboard_tracker, $second_taskboard_tracker]));

        $this->search_mapped_field = SearchMappedFieldStub::withMappedFields(
            [$first_taskboard_tracker, self::FIRST_MAPPED_FIELD_ID],
            [$second_taskboard_tracker, self::SECOND_MAPPED_FIELD_ID]
        );
        $this->field_retriever     = RetrieveUsedListFieldStub::withFields(
            ListFieldBuilder::aListField(self::FIRST_MAPPED_FIELD_ID)->build(),
            ListFieldBuilder::aListField(self::SECOND_MAPPED_FIELD_ID)->build()
        );
        $this->search_values       = SearchMappedFieldValuesForColumnStub::withMappings(
            [$first_taskboard_tracker, $this->ongoing_column, [1674]],
            [$second_taskboard_tracker, $this->ongoing_column, [1857, 1858]]
        );

        $expected_first_mapping  = new TrackerMappingPresenter(
            self::USER_STORIES_TRACKER_ID,
            self::FIRST_MAPPED_FIELD_ID,
            [new ListFieldValuePresenter(1674)]
        );
        $expected_second_mapping = new TrackerMappingPresenter(
            self::TASKS_TRACKER_ID,
            self::SECOND_MAPPED_FIELD_ID,
            [new ListFieldValuePresenter(1857), new ListFieldValuePresenter(1858)]
        );
        self::assertEquals([$expected_first_mapping, $expected_second_mapping], $this->buildMappings());
    }
}
