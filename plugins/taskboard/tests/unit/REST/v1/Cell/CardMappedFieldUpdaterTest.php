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

use Luracast\Restler\RestException;
use PFUser;
use PHPUnit\Framework\MockObject\MockObject;
use Tracker_FormElement_Field_Selectbox;
use Tuleap\Cardwall\OnTop\Config\ColumnCollection;
use Tuleap\Cardwall\OnTop\Config\ColumnFactory;
use Tuleap\Cardwall\Test\Builders\ColumnTestBuilder;
use Tuleap\GlobalResponseMock;
use Tuleap\REST\I18NRestException;
use Tuleap\Taskboard\Column\CardColumnFinder;
use Tuleap\Taskboard\Column\FieldValuesToColumnMapping\ArtifactMappedFieldValueRetriever;
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
use Tuleap\Tracker\Test\Builders\ChangesetTestBuilder;
use Tuleap\Tracker\Test\Builders\ChangesetValueListTestBuilder;
use Tuleap\Tracker\Test\Builders\Fields\List\ListStaticValueBuilder;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;
use Tuleap\Tracker\Test\Stub\FormElement\Field\ListFields\RetrieveUsedListFieldStub;
use Tuleap\Tracker\Workflow\FirstPossibleValueInListRetriever;
use Tuleap\Tracker\Workflow\NoPossibleValueException;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class CardMappedFieldUpdaterTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use GlobalResponseMock;

    private const MAPPED_FIELD_ID        = 789;
    private const DONE_COLUMN_ID         = 9;
    private const DONE_BIND_VALUE_ID     = 1024;
    private const FINISHED_BIND_VALUE_ID = 2048;
    private MockObject&ColumnFactory $column_factory;
    private MockObject&MilestoneTrackerRetriever $milestone_tracker_retriever;
    private MockObject&AddValidator $add_validator;
    private MockObject&ArtifactUpdater $artifact_updater;
    private SearchMappedFieldStub $search_mapped_field;
    private RetrieveUsedListFieldStub $list_field_retriever;
    private SearchMappedFieldValuesForColumnStub $search_values;
    private Artifact $swimlane_artifact;
    private Artifact $artifact_to_add;
    private PFUser $current_user;
    private Tracker_FormElement_Field_Selectbox&MockObject $mapped_list_field;
    private FirstPossibleValueInListRetriever&MockObject $first_possible_value_retriever;
    private TaskboardTracker $taskboard_tracker;
    private \Cardwall_Column $done_column;
    private \Tuleap\Tracker\Tracker $tasks_tracker;

    protected function setUp(): void
    {
        $this->mapped_list_field = $this->createMock(Tracker_FormElement_Field_Selectbox::class);
        $this->mapped_list_field->method('getId')->willReturn(self::MAPPED_FIELD_ID);

        $this->column_factory = $this->createMock(ColumnFactory::class);
        $todo_column          = ColumnTestBuilder::aColumn()->withId(8)->build();
        $this->done_column    = ColumnTestBuilder::aColumn()->withId(self::DONE_COLUMN_ID)->build();
        $this->column_factory->method('getDashboardColumns')->willReturn(
            new ColumnCollection([$todo_column, $this->done_column])
        );

        $this->add_validator                  = $this->createMock(AddValidator::class);
        $this->artifact_updater               = $this->createMock(ArtifactUpdater::class);
        $this->search_mapped_field            = SearchMappedFieldStub::withNoField();
        $this->list_field_retriever           = RetrieveUsedListFieldStub::withNoField();
        $this->first_possible_value_retriever = $this->createMock(FirstPossibleValueInListRetriever::class);

        $this->swimlane_artifact = ArtifactTestBuilder::anArtifact(1)->build();
        $this->current_user      = UserTestBuilder::aUser()->build();

        $milestone_tracker = TrackerTestBuilder::aTracker()->withId(76)->build();

        $this->milestone_tracker_retriever = $this->createMock(MilestoneTrackerRetriever::class);
        $this->milestone_tracker_retriever->method('getMilestoneTrackerOfColumn')
            ->with($this->done_column)
            ->willReturn($milestone_tracker);

        $last_changeset = ChangesetTestBuilder::aChangeset(934)->build();
        ChangesetValueListTestBuilder::aListOfValue(34460, $last_changeset, $this->mapped_list_field)
            ->withValues([ListStaticValueBuilder::aStaticValue('To Do')->build()])
            ->build();

        $this->tasks_tracker     = TrackerTestBuilder::aTracker()->withId(90)->withName('Tasks')->build();
        $this->artifact_to_add   = ArtifactTestBuilder::anArtifact(481)
            ->inTracker($this->tasks_tracker)
            ->withChangesets($last_changeset)
            ->build();
        $this->taskboard_tracker = new TaskboardTracker($milestone_tracker, $this->tasks_tracker);

        $this->search_values = SearchMappedFieldValuesForColumnStub::withValues(
            $this->taskboard_tracker,
            $this->done_column,
            [self::DONE_BIND_VALUE_ID, self::FINISHED_BIND_VALUE_ID]
        );

        $this->first_possible_value_retriever->method('getFirstPossibleValue')->with(
            $this->artifact_to_add,
            $this->mapped_list_field,
            new MappedValues([self::DONE_BIND_VALUE_ID, self::FINISHED_BIND_VALUE_ID]),
            $this->current_user,
        )->willReturn(self::DONE_BIND_VALUE_ID);
    }

    /**
     * @throws RestException
     * @throws I18NRestException
     */
    private function update(): void
    {
        $status_retriever = $this->createStub(\Cardwall_FieldProviders_SemanticStatusFieldRetriever::class);
        $status_retriever->method('getField')->willReturn(null);

        $mapped_field_retriever  = new MappedFieldRetriever(
            $status_retriever,
            new FreestyleMappedFieldRetriever(
                $this->search_mapped_field,
                $this->list_field_retriever,
            )
        );
        $mapped_values_retriever = new MappedValuesRetriever(
            new FreestyleMappedFieldValuesRetriever(
                VerifyMappingExistsStub::withMapping(),
                $this->search_values,
            ),
            $status_retriever
        );
        $updater                 = new CardMappedFieldUpdater(
            $this->column_factory,
            $this->milestone_tracker_retriever,
            $this->add_validator,
            $mapped_field_retriever,
            $mapped_values_retriever,
            $this->first_possible_value_retriever,
            new CardColumnFinder(
                new ArtifactMappedFieldValueRetriever($mapped_field_retriever),
                $this->column_factory,
                $mapped_values_retriever
            ),
            $this->artifact_updater,
        );

        $updater->updateCardMappedField(
            $this->swimlane_artifact,
            self::DONE_COLUMN_ID,
            $this->artifact_to_add,
            $this->current_user
        );
    }

    public function testUpdateCardMappedFieldThrowsWhenColumnCantBeFound(): void
    {
        $this->column_factory->method('getColumnById')
            ->with(self::DONE_COLUMN_ID)
            ->willReturn(null);

        $this->expectException(RestException::class);
        $this->expectExceptionCode(404);
        $this->update();
    }

    public function testUpdateCardMappedFieldThrowsWhenNoMappedFieldForTracker(): void
    {
        $this->artifactsAreValid();
        $this->columnIsFound();
        $milestone_tracker = TrackerTestBuilder::aTracker()->withId(76)->build();
        $this->milestone_tracker_retriever->method('getMilestoneTrackerOfColumn')
            ->with($this->done_column)
            ->willReturn($milestone_tracker);
        $this->search_mapped_field = SearchMappedFieldStub::withNoField();

        $this->artifact_updater->expects($this->never())->method('update');
        $this->expectException(I18NRestException::class);
        $this->expectExceptionCode(400);
        $this->update();
    }

    public function testUpdateCardMappedFieldThrowsWhenUserCantUpdateMappedField(): void
    {
        $this->artifactsAreValid();
        $this->columnIsFound();
        $this->mockCanUserUpdateField(false);

        $this->artifact_updater->expects($this->never())->method('update');
        $this->expectException(I18NRestException::class);
        $this->expectExceptionCode(403);
        $this->update();
    }

    public function testUpdateCardMappedFieldThrowsWhenMappedValuesAreEmpty(): void
    {
        $this->artifactsAreValid();
        $this->columnIsFound();
        $this->mockCanUserUpdateField(true);
        $this->search_values = SearchMappedFieldValuesForColumnStub::withNoMappedValue();

        $this->artifact_updater->expects($this->never())->method('update');
        $this->expectException(I18NRestException::class);
        $this->expectExceptionCode(400);
        $this->update();
    }

    public function testUpdateCardMappedFieldThrowsInvalidFieldException(): void
    {
        $this->artifactsAreValid();
        $this->columnIsFound();
        $this->mockCanUserUpdateField(true);

        $this->artifact_updater->expects($this->once())
            ->method('update')
            ->willThrowException(new \Tracker_FormElement_InvalidFieldException());

        $this->expectException(RestException::class);
        $this->expectExceptionCode(400);
        $this->update();
    }

    public function testUpdateCardMappedFieldRethrowsInvalidFieldValueException(): void
    {
        $this->artifactsAreValid();
        $this->columnIsFound();
        $this->mockCanUserUpdateField(true);

        $this->artifact_updater->expects($this->once())
            ->method('update')
            ->willThrowException(new \Tracker_FormElement_InvalidFieldValueException());
        $this->expectException(RestException::class);
        $this->expectExceptionCode(400);
        $this->update();
    }

    public function testUpdateCardMappedFieldThrowsExceptionWhenNoPossibleValue(): void
    {
        $this->artifactsAreValid();
        $this->columnIsFound();
        $this->mockCanUserUpdateField(true);

        $this->first_possible_value_retriever = $this->createMock(FirstPossibleValueInListRetriever::class);
        $this->first_possible_value_retriever->method('getFirstPossibleValue')->willThrowException(
            new NoPossibleValueException()
        );

        $this->artifact_updater->expects($this->never())->method('update');

        $this->expectException(I18NRestException::class);
        $this->expectExceptionCode(400);
        $this->update();
    }

    public function testUpdateCardMappedFieldDoesNotRethrowNoChangeException(): void
    {
        $this->artifactsAreValid();
        $this->columnIsFound();
        $this->mockCanUserUpdateField(true);

        $this->artifact_updater->expects($this->once())
            ->method('update')
            ->willThrowException(new \Tracker_NoChangeException(40, 'user_story #40'));

        $this->update();
    }

    public function testUpdateCardMappedFieldRethrowsTrackerException(): void
    {
        $this->artifactsAreValid();
        $this->columnIsFound();
        $this->mockCanUserUpdateField(true);

        $this->artifact_updater->expects($this->once())
            ->method('update')
            ->willThrowException(new \Tracker_Exception());

        $this->expectException(RestException::class);
        $this->expectExceptionCode(500);
        $this->update();
    }

    public function testUpdateCardMappedFieldUpdatesArtifactWithFirstMappedValue(): void
    {
        $this->artifactsAreValid();
        $this->columnIsFound();
        $this->mockCanUserUpdateField(true);

        $this->artifact_updater->expects($this->once())
            ->method('update')
            ->with(
                $this->current_user,
                $this->artifact_to_add,
                self::callback(static fn(array $values): bool => $values[0]->field_id === self::MAPPED_FIELD_ID
                    && $values[0]->bind_value_ids = [self::DONE_BIND_VALUE_ID]),
            );

        $this->update();
    }

    public function testUpdateCardMappedFieldDoesNotUpdateCardArtifactWhenItIsAlreadyInTheGivenColumn(): void
    {
        $last_changeset = ChangesetTestBuilder::aChangeset(360)->build();
        ChangesetValueListTestBuilder::aListOfValue(59544, $last_changeset, $this->mapped_list_field)
            ->withValues([ListStaticValueBuilder::aStaticValue('Done')->withId(self::DONE_BIND_VALUE_ID)->build()])
            ->build();

        $this->artifact_to_add = ArtifactTestBuilder::anArtifact(700)
            ->inTracker($this->tasks_tracker)
            ->withChangesets($last_changeset)
            ->build();

        $this->artifactsAreValid();
        $this->columnIsFound();
        $this->mockCanUserUpdateField(true);

        $this->artifact_updater->expects($this->never())->method('update');
        $this->update();
    }

    private function mockCanUserUpdateField(
        bool $can_update_mapped_field,
    ): void {
        $this->mapped_list_field->method('userCanRead')->willReturn(true);
        $this->mapped_list_field->method('userCanUpdate')
            ->with($this->current_user)
            ->willReturn($can_update_mapped_field);
        $this->mapped_list_field->method('getLabel')->willReturn('Status');

        $this->search_mapped_field  = SearchMappedFieldStub::withMappedField(
            $this->taskboard_tracker,
            self::MAPPED_FIELD_ID
        );
        $this->list_field_retriever = RetrieveUsedListFieldStub::withField($this->mapped_list_field);
    }

    private function artifactsAreValid(): void
    {
        $this->add_validator->expects($this->once())
            ->method('validateArtifacts')
            ->with($this->swimlane_artifact, $this->artifact_to_add, $this->current_user);
    }

    private function columnIsFound(): void
    {
        $this->column_factory->method('getColumnById')
            ->with(self::DONE_COLUMN_ID)
            ->willReturn($this->done_column);
    }
}
