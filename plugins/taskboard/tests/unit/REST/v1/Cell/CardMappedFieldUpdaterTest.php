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
use Tuleap\Taskboard\Column\FieldValuesToColumnMapping\EmptyMappedValues;
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
use Tuleap\Tracker\Workflow\FirstPossibleValueInListRetriever;
use Tuleap\Tracker\Workflow\NoPossibleValueException;

final class CardMappedFieldUpdaterTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use GlobalResponseMock;
    use GlobalLanguageMock;

    private CardMappedFieldUpdater $updater;
    private MockObject&Cardwall_OnTop_Config_ColumnFactory $column_factory;
    private MockObject&MilestoneTrackerRetriever $milestone_tracker_retriever;
    private MockObject&AddValidator $add_validator;
    private MockObject&ArtifactUpdater $artifact_updater;
    private MockObject&MappedFieldRetriever $mapped_field_retriever;
    private MockObject&MappedValuesRetriever $mapped_values_retriever;

    private Artifact $swimlane_artifact;
    private MockObject&Artifact $artifact_to_add;
    private PFUser $current_user;
    private Tracker_FormElement_Field_Selectbox&MockObject $mapped_field;
    private FirstPossibleValueInListRetriever&MockObject $first_possible_value_retriever;

    protected function setUp(): void
    {
        $this->mapped_field                   = $this->createMock(Tracker_FormElement_Field_Selectbox::class);
        $this->column_factory                 = $this->createMock(Cardwall_OnTop_Config_ColumnFactory::class);
        $this->milestone_tracker_retriever    = $this->createMock(MilestoneTrackerRetriever::class);
        $this->add_validator                  = $this->createMock(AddValidator::class);
        $this->artifact_updater               = $this->createMock(ArtifactUpdater::class);
        $this->mapped_field_retriever         = $this->createMock(MappedFieldRetriever::class);
        $this->mapped_values_retriever        = $this->createMock(MappedValuesRetriever::class);
        $this->first_possible_value_retriever = $this->createMock(FirstPossibleValueInListRetriever::class);
        $this->updater                        = new CardMappedFieldUpdater(
            $this->column_factory,
            $this->milestone_tracker_retriever,
            $this->add_validator,
            $this->artifact_updater,
            $this->mapped_field_retriever,
            $this->mapped_values_retriever,
            $this->first_possible_value_retriever
        );

        $this->swimlane_artifact = ArtifactTestBuilder::anArtifact(1)->build();
        $this->artifact_to_add   = $this->createMock(Artifact::class);
        $this->current_user      = UserTestBuilder::aUser()->build();
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
        $tracker = TrackerTestBuilder::aTracker()->withName('Tasks')->build();
        $this->artifact_to_add->method('getTracker')
            ->willReturn($tracker);
        $this->mapped_field_retriever->method('getField')
            ->willReturn(null);

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
        $this->mapped_values_retriever->method('getValuesMappedToColumn')
            ->willReturn(new EmptyMappedValues());

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
        $this->mapped_values_retriever->method('getValuesMappedToColumn')
            ->willReturn($mapped_values);

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
        $this->mapped_values_retriever->method('getValuesMappedToColumn')
            ->willReturn($mapped_values);
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
        $this->mapped_values_retriever->method('getValuesMappedToColumn')
            ->willReturn($mapped_values);

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
        $this->mapped_values_retriever->method('getValuesMappedToColumn')
            ->willReturn($mapped_values);

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
        $this->mapped_values_retriever->method('getValuesMappedToColumn')
            ->willReturn($mapped_values);

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
        $this->mapped_values_retriever->method('getValuesMappedToColumn')
            ->willReturn($mapped_values);
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
                self::callback(
                    function (array $values): bool {
                        return $values[0]->field_id === 789
                            && $values[0]->bind_value_ids = [1024];
                    }
                ),
            );

        $this->update(9);
    }

    private function mockCanUserUpdateField(
        Cardwall_Column $done_column,
        int $field_id,
        bool $can_update_mapped_field,
    ): void {
        $milestone_tracker = TrackerTestBuilder::aTracker()->withId(76)->build();
        $tracker           = TrackerTestBuilder::aTracker()->withName('Tasks')->build();
        $this->artifact_to_add->method('getTracker')
            ->willReturn($tracker);
        $this->milestone_tracker_retriever->method('getMilestoneTrackerOfColumn')
            ->with($done_column)
            ->willReturn($milestone_tracker);

        $this->mapped_field->method('userCanUpdate')
            ->with($this->current_user)
            ->willReturn($can_update_mapped_field);
        $this->mapped_field->method('getLabel')
            ->willReturn('Status');
        $this->mapped_field->method('getId')
            ->willReturn($field_id);

        $this->mapped_field_retriever->method('getField')
            ->with(self::callback(
                function (TaskboardTracker $arg) use ($milestone_tracker, $tracker): bool {
                    return $arg->getMilestoneTrackerId() === $milestone_tracker->getId()
                        && $arg->getTracker() === $tracker;
                }
            ))
            ->willReturn($this->mapped_field);
    }

    private function update(int $column_id): void
    {
        $this->updater->updateCardMappedField(
            $this->swimlane_artifact,
            $column_id,
            $this->artifact_to_add,
            $this->current_user
        );
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
