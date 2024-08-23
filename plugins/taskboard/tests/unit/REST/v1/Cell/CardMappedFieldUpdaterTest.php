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

namespace Tuleap\Taskboard\REST\v1\Cell;

use Cardwall_Column;
use Cardwall_OnTop_Config_ColumnFactory;
use Luracast\Restler\RestException;
use PFUser;
use PHPUnit\Framework\MockObject\MockObject;
use Tracker_FormElement_Field_Selectbox;
use Tuleap\GlobalLanguageMock;
use Tuleap\GlobalResponseMock;
use Tuleap\REST\I18NRestException;
use Tuleap\Taskboard\Column\FieldValuesToColumnMapping\Freestyle\FreestyleMappedFieldRetriever;
use Tuleap\Taskboard\Column\FieldValuesToColumnMapping\Freestyle\FreestyleMappedFieldValuesRetriever;
use Tuleap\Taskboard\Column\FieldValuesToColumnMapping\Freestyle\SearchMappedFieldStub;
use Tuleap\Taskboard\Column\FieldValuesToColumnMapping\Freestyle\SearchMappedFieldValuesForColumnStub;
use Tuleap\Taskboard\Column\FieldValuesToColumnMapping\Freestyle\VerifyMappingExistsStub;
use Tuleap\Taskboard\Column\FieldValuesToColumnMapping\MappedFieldRetriever;
use Tuleap\Taskboard\Column\FieldValuesToColumnMapping\MappedValues;
use Tuleap\Taskboard\Column\FieldValuesToColumnMapping\MappedValuesRetriever;
use Tuleap\Taskboard\Column\MilestoneTrackerRetriever;
use Tuleap\Taskboard\Tracker\TaskboardTracker;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\REST\Artifact\ArtifactUpdater;
use Tuleap\Tracker\Test\Builders\ArtifactTestBuilder;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;
use Tuleap\Tracker\Test\Stub\Tracker\FormElement\Field\ListFields\RetrieveUsedListFieldStub;
use Tuleap\Tracker\Workflow\FirstPossibleValueInListRetriever;
use Tuleap\Tracker\Workflow\NoPossibleValueException;

final class CardMappedFieldUpdaterTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use GlobalResponseMock;
    use GlobalLanguageMock;

    private MockObject&Cardwall_OnTop_Config_ColumnFactory $column_factory;
    private MockObject&MilestoneTrackerRetriever $milestone_tracker_retriever;
    private MockObject&AddValidator $add_validator;
    private MockObject&ArtifactUpdater $artifact_updater;
    private SearchMappedFieldStub $search_mapped_field;
    private RetrieveUsedListFieldStub $list_field_retriever;
    private SearchMappedFieldValuesForColumnStub $search_values;
    private Artifact $swimlane_artifact;
    private Artifact $artifact_to_add;
    private PFUser $current_user;
    private Tracker_FormElement_Field_Selectbox&MockObject $mapped_field;
    private FirstPossibleValueInListRetriever&MockObject $first_possible_value_retriever;
    private TaskboardTracker $taskboard_tracker;
    private \Tracker $milestone_tracker;

    protected function setUp(): void
    {
        $this->mapped_field                   = $this->createMock(Tracker_FormElement_Field_Selectbox::class);
        $this->column_factory                 = $this->createMock(Cardwall_OnTop_Config_ColumnFactory::class);
        $this->milestone_tracker_retriever    = $this->createMock(MilestoneTrackerRetriever::class);
        $this->add_validator                  = $this->createMock(AddValidator::class);
        $this->artifact_updater               = $this->createMock(ArtifactUpdater::class);
        $this->search_mapped_field            = SearchMappedFieldStub::withNoField();
        $this->list_field_retriever           = RetrieveUsedListFieldStub::withNoField();
        $this->search_values                  = SearchMappedFieldValuesForColumnStub::withNoMappedValue();
        $this->first_possible_value_retriever = $this->createMock(FirstPossibleValueInListRetriever::class);

        $this->swimlane_artifact = ArtifactTestBuilder::anArtifact(1)->build();
        $this->current_user      = UserTestBuilder::aUser()->build();

        $this->milestone_tracker    = TrackerTestBuilder::aTracker()->withId(76)->build();
        $tracker_of_artifact_to_add = TrackerTestBuilder::aTracker()
            ->withId(90)
            ->withName('Tasks')
            ->build();
        $this->artifact_to_add      = ArtifactTestBuilder::anArtifact(481)
            ->inTracker($tracker_of_artifact_to_add)->build();
        $this->taskboard_tracker    = new TaskboardTracker($this->milestone_tracker, $tracker_of_artifact_to_add);
    }

    /**
     * @throws RestException
     * @throws I18NRestException
     */
    private function update(int $column_id): void
    {
        $status_retriever = $this->createStub(\Cardwall_FieldProviders_SemanticStatusFieldRetriever::class);
        $status_retriever->method('getField')->willReturn(null);

        $updater = new CardMappedFieldUpdater(
            $this->column_factory,
            $this->milestone_tracker_retriever,
            $this->add_validator,
            $this->artifact_updater,
            new MappedFieldRetriever(
                $status_retriever,
                new FreestyleMappedFieldRetriever(
                    $this->search_mapped_field,
                    $this->list_field_retriever,
                )
            ),
            new MappedValuesRetriever(
                new FreestyleMappedFieldValuesRetriever(
                    VerifyMappingExistsStub::withMapping(),
                    $this->search_values,
                ),
                $status_retriever
            ),
            $this->first_possible_value_retriever
        );

        $updater->updateCardMappedField(
            $this->swimlane_artifact,
            $column_id,
            $this->artifact_to_add,
            $this->current_user
        );
    }

    public function testUpdateCardMappedFieldThrowsWhenColumnCantBeFound(): void
    {
        $this->column_factory->method('getColumnById')
            ->with(9)
            ->willReturn(null);

        $this->expectException(RestException::class);
        $this->expectExceptionCode(404);
        $this->update(9);
    }

    public function testUpdateCardMappedFieldThrowsWhenNoMappedFieldForTracker(): void
    {
        $this->artifactsAreValid();
        $done_column       = $this->mockColumn(9);
        $milestone_tracker = TrackerTestBuilder::aTracker()->withId(76)->build();
        $this->milestone_tracker_retriever->method('getMilestoneTrackerOfColumn')
            ->with($done_column)
            ->willReturn($milestone_tracker);
        $this->search_mapped_field = SearchMappedFieldStub::withNoField();

        $this->artifact_updater->expects(self::never())->method('update');
        $this->expectException(I18NRestException::class);
        $this->expectExceptionCode(400);
        $this->update(9);
    }

    public function testUpdateCardMappedFieldThrowsWhenUserCantUpdateMappedField(): void
    {
        $this->artifactsAreValid();
        $done_column = $this->mockColumn(9);
        $this->mockCanUserUpdateField($done_column, 789, false);

        $this->artifact_updater->expects(self::never())->method('update');
        $this->expectException(I18NRestException::class);
        $this->expectExceptionCode(403);
        $this->update(9);
    }

    public function testUpdateCardMappedFieldThrowsWhenMappedValuesAreEmpty(): void
    {
        $this->artifactsAreValid();
        $done_column = $this->mockColumn(9);
        $this->mockCanUserUpdateField($done_column, 789, true);
        $this->search_values = SearchMappedFieldValuesForColumnStub::withNoMappedValue();

        $this->artifact_updater->expects(self::never())->method('update');
        $this->expectException(I18NRestException::class);
        $this->expectExceptionCode(400);
        $this->update(9);
    }

    public function testUpdateCardMappedFieldThrowsInvalidFieldException(): void
    {
        $mapped_values = new MappedValues([1024, 2048]);
        $this->artifactsAreValid();
        $done_column = $this->mockColumn(9);
        $this->mockCanUserUpdateField($done_column, 789, true);
        $this->search_values = SearchMappedFieldValuesForColumnStub::withValues($this->taskboard_tracker, $done_column, [1024, 2048]);

        $this->first_possible_value_retriever->method('getFirstPossibleValue')->with(
            $this->artifact_to_add,
            $this->mapped_field,
            $mapped_values,
            $this->current_user,
        )->willReturn(1024);

        $this->artifact_updater->expects(self::once())
            ->method('update')
            ->willThrowException(new \Tracker_FormElement_InvalidFieldException());

        $this->expectException(RestException::class);
        $this->expectExceptionCode(400);
        $this->update(9);
    }

    public function testUpdateCardMappedFieldRethrowsInvalidFieldValueException(): void
    {
        $mapped_values = new MappedValues([1024, 2048]);
        $this->artifactsAreValid();
        $done_column = $this->mockColumn(9);
        $this->mockCanUserUpdateField($done_column, 789, true);
        $this->search_values = SearchMappedFieldValuesForColumnStub::withValues($this->taskboard_tracker, $done_column, [1024, 2048]);
        $this->first_possible_value_retriever->method('getFirstPossibleValue')->with(
            $this->artifact_to_add,
            $this->mapped_field,
            $mapped_values,
            $this->current_user,
        )->willReturn(1024);

        $this->artifact_updater->expects(self::once())
            ->method('update')
            ->willThrowException(new \Tracker_FormElement_InvalidFieldValueException());
        $this->expectException(RestException::class);
        $this->expectExceptionCode(400);
        $this->update(9);
    }

    public function testUpdateCardMappedFieldThrowsExceptionWhenNoPossibleValue(): void
    {
        $mapped_values = new MappedValues([1024, 2048]);
        $this->artifactsAreValid();
        $done_column = $this->mockColumn(9);
        $this->mockCanUserUpdateField($done_column, 789, true);
        $this->search_values = SearchMappedFieldValuesForColumnStub::withValues($this->taskboard_tracker, $done_column, [1024, 2048]);

        $this->first_possible_value_retriever->method('getFirstPossibleValue')->with(
            $this->artifact_to_add,
            $this->mapped_field,
            $mapped_values,
            $this->current_user,
        )->willThrowException(new NoPossibleValueException());

        $this->artifact_updater->expects(self::never())->method('update');

        $this->expectException(I18NRestException::class);
        $this->expectExceptionCode(400);
        $this->update(9);
    }

    public function testUpdateCardMappedFieldDoesNotRethrowNoChangeException(): void
    {
        $mapped_values = new MappedValues([1024, 2048]);

        $this->artifactsAreValid();
        $done_column = $this->mockColumn(9);
        $this->mockCanUserUpdateField($done_column, 789, true);
        $this->search_values = SearchMappedFieldValuesForColumnStub::withValues($this->taskboard_tracker, $done_column, [1024, 2048]);

        $this->first_possible_value_retriever->method('getFirstPossibleValue')->with(
            $this->artifact_to_add,
            $this->mapped_field,
            $mapped_values,
            $this->current_user,
        )->willReturn(1024);

        $this->artifact_updater->expects(self::once())
            ->method('update')
            ->willThrowException(new \Tracker_NoChangeException(40, 'user_story #40'));

        $this->update(9);
    }

    public function testUpdateCardMappedFieldRethrowsTrackerException(): void
    {
        $mapped_values = new MappedValues([1024, 2048]);

        $this->artifactsAreValid();
        $done_column = $this->mockColumn(9);
        $this->mockCanUserUpdateField($done_column, 789, true);
        $this->search_values = SearchMappedFieldValuesForColumnStub::withValues($this->taskboard_tracker, $done_column, [1024, 2048]);

        $this->first_possible_value_retriever->method('getFirstPossibleValue')->with(
            $this->artifact_to_add,
            $this->mapped_field,
            $mapped_values,
            $this->current_user,
        )->willReturn(1024);

        $this->artifact_updater->expects(self::once())
            ->method('update')
            ->willThrowException(new \Tracker_Exception());

        $this->expectException(RestException::class);
        $this->expectExceptionCode(500);
        $this->update(9);
    }

    public function testUpdateCardMappedFieldUpdatesArtifactWithFirstMappedValue(): void
    {
        $mapped_values = new MappedValues([1024, 2048]);

        $this->artifactsAreValid();
        $done_column = $this->mockColumn(9);
        $this->mockCanUserUpdateField($done_column, 789, true);
        $this->search_values = SearchMappedFieldValuesForColumnStub::withValues($this->taskboard_tracker, $done_column, [1024, 2048]);
        $this->first_possible_value_retriever->method('getFirstPossibleValue')->with(
            $this->artifact_to_add,
            $this->mapped_field,
            $mapped_values,
            $this->current_user,
        )->willReturn(1024);
        $this->artifact_updater->expects(self::once())
            ->method('update')
            ->with(
                $this->current_user,
                $this->artifact_to_add,
                self::callback(static fn(array $values): bool =>
                    $values[0]->field_id === 789
                    && $values[0]->bind_value_ids = [1024]),
            );

        $this->update(9);
    }

    private function mockCanUserUpdateField(
        Cardwall_Column $done_column,
        int $field_id,
        bool $can_update_mapped_field,
    ): void {
        $this->milestone_tracker_retriever->method('getMilestoneTrackerOfColumn')
            ->with($done_column)
            ->willReturn($this->milestone_tracker);
        $this->mapped_field->method('userCanUpdate')
            ->with($this->current_user)
            ->willReturn($can_update_mapped_field);
        $this->mapped_field->method('getLabel')->willReturn('Status');
        $this->mapped_field->method('getId')->willReturn($field_id);

        $this->search_mapped_field  = SearchMappedFieldStub::withMappedField($this->taskboard_tracker, $field_id);
        $this->list_field_retriever = RetrieveUsedListFieldStub::withField($this->mapped_field);
    }

    private function artifactsAreValid(): void
    {
        $this->add_validator->expects(self::once())
            ->method('validateArtifacts')
            ->with($this->swimlane_artifact, $this->artifact_to_add, $this->current_user);
    }

    private function mockColumn(int $id): Cardwall_Column
    {
        $done_column = new Cardwall_Column($id, 'Done', 'acid-green');
        $this->column_factory->method('getColumnById')
            ->with($id)
            ->willReturn($done_column);
        return $done_column;
    }
}
