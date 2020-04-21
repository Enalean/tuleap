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
use Mockery as M;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PFUser;
use PHPUnit\Framework\TestCase;
use Tracker;
use Tracker_Artifact;
use Tracker_FormElement_Field_Selectbox;
use Tracker_REST_Artifact_ArtifactUpdater;
use Tuleap\GlobalLanguageMock;
use Tuleap\GlobalResponseMock;
use Tuleap\REST\I18NRestException;
use Tuleap\Taskboard\Column\FieldValuesToColumnMapping\EmptyMappedValues;
use Tuleap\Taskboard\Column\FieldValuesToColumnMapping\MappedFieldRetriever;
use Tuleap\Taskboard\Column\FieldValuesToColumnMapping\MappedValues;
use Tuleap\Taskboard\Column\FieldValuesToColumnMapping\MappedValuesRetriever;
use Tuleap\Taskboard\Column\MilestoneTrackerRetriever;
use Tuleap\Taskboard\Tracker\TaskboardTracker;

final class CardMappedFieldUpdaterTest extends TestCase
{
    use MockeryPHPUnitIntegration;
    use GlobalResponseMock;
    use GlobalLanguageMock;

    /** @var CardMappedFieldUpdater */
    private $updater;
    /** @var Cardwall_OnTop_Config_ColumnFactory|M\LegacyMockInterface|M\MockInterface */
    private $column_factory;
    /** @var M\LegacyMockInterface|M\MockInterface|MilestoneTrackerRetriever */
    private $milestone_tracker_retriever;
    /** @var M\LegacyMockInterface|M\MockInterface|AddValidator */
    private $add_validator;
    /** @var M\LegacyMockInterface|M\MockInterface|Tracker_REST_Artifact_ArtifactUpdater */
    private $artifact_updater;
    /** @var M\LegacyMockInterface|M\MockInterface|MappedFieldRetriever */
    private $mapped_field_retriever;
    /** @var M\LegacyMockInterface|M\MockInterface|MappedValuesRetriever */
    private $mapped_values_retriever;

    /** @var M\LegacyMockInterface|M\MockInterface|Tracker_Artifact */
    private $swimlane_artifact;
    /** @var M\LegacyMockInterface|M\MockInterface|Tracker_Artifact */
    private $artifact_to_add;
    /** @var M\LegacyMockInterface|M\MockInterface|PFUser */
    private $current_user;

    protected function setUp(): void
    {
        $this->column_factory              = M::mock(Cardwall_OnTop_Config_ColumnFactory::class);
        $this->milestone_tracker_retriever = M::mock(MilestoneTrackerRetriever::class);
        $this->add_validator               = M::mock(AddValidator::class);
        $this->artifact_updater            = M::mock(Tracker_REST_Artifact_ArtifactUpdater::class);
        $this->mapped_field_retriever      = M::mock(MappedFieldRetriever::class);
        $this->mapped_values_retriever     = M::mock(MappedValuesRetriever::class);
        $this->updater                     = new CardMappedFieldUpdater(
            $this->column_factory,
            $this->milestone_tracker_retriever,
            $this->add_validator,
            $this->artifact_updater,
            $this->mapped_field_retriever,
            $this->mapped_values_retriever
        );

        $this->swimlane_artifact = M::mock(Tracker_Artifact::class);
        $this->artifact_to_add   = M::mock(Tracker_Artifact::class);
        $this->current_user      = M::mock(PFUser::class);
    }

    public function testUpdateCardMappedFieldThrowsWhenColumnCantBeFound(): void
    {
        $this->column_factory->shouldReceive('getColumnById')
            ->with(9)
            ->andReturnNull();

        $this->expectException(RestException::class);
        $this->expectExceptionCode(404);
        $this->update(9);
    }

    public function testUpdateCardMappedFieldThrowsWhenNoMappedFieldForTracker(): void
    {
        $this->artifactsAreValid();
        $done_column       = $this->mockColumn(9);
        $milestone_tracker = M::mock(Tracker::class)
            ->shouldReceive(['getId' => 76])
            ->getMock();
        $this->milestone_tracker_retriever->shouldReceive('getMilestoneTrackerOfColumn')
            ->with($done_column)
            ->andReturn($milestone_tracker);
        $tracker = M::mock(Tracker::class)
            ->shouldReceive(['getName' => 'Tasks'])
            ->getMock();
        $this->artifact_to_add->shouldReceive('getTracker')
            ->andReturn($tracker);
        $this->mapped_field_retriever->shouldReceive('getField')
            ->andReturnNull();

        $this->artifact_updater->shouldNotReceive('update');
        $this->expectException(I18NRestException::class);
        $this->expectExceptionCode(400);
        $this->update(9);
    }

    public function testUpdateCardMappedFieldThrowsWhenUserCantUpdateMappedField(): void
    {
        $this->artifactsAreValid();
        $done_column = $this->mockColumn(9);
        $this->mockCanUserUpdateField($done_column, 789, false);

        $this->artifact_updater->shouldNotReceive('update');
        $this->expectException(I18NRestException::class);
        $this->expectExceptionCode(403);
        $this->update(9);
    }

    public function testUpdateCardMappedFieldThrowsWhenMappedValuesAreEmpty(): void
    {
        $this->artifactsAreValid();
        $done_column = $this->mockColumn(9);
        $this->mockCanUserUpdateField($done_column, 789, true);
        $this->mapped_values_retriever->shouldReceive('getValuesMappedToColumn')
            ->andReturn(new EmptyMappedValues());

        $this->artifact_updater->shouldNotReceive('update');
        $this->expectException(I18NRestException::class);
        $this->expectExceptionCode(400);
        $this->update(9);
    }

    public function testUpdateCardMappedFieldRethrowsInvalidFieldException(): void
    {
        $this->artifactsAreValid();
        $done_column = $this->mockColumn(9);
        $this->mockCanUserUpdateField($done_column, 789, true);
        $this->mapped_values_retriever->shouldReceive('getValuesMappedToColumn')
            ->andReturn(new MappedValues([1024, 2048]));
        $this->artifact_updater->shouldReceive('update')
            ->once()
            ->andThrow(new \Tracker_FormElement_InvalidFieldException());

        $this->expectException(RestException::class);
        $this->expectExceptionCode(400);
        $this->update(9);
    }

    public function testUpdateCardMappedFieldRethrowsInvalidFieldValueException(): void
    {
        $this->artifactsAreValid();
        $done_column = $this->mockColumn(9);
        $this->mockCanUserUpdateField($done_column, 789, true);
        $this->mapped_values_retriever->shouldReceive('getValuesMappedToColumn')
            ->andReturn(new MappedValues([1024, 2048]));
        $this->artifact_updater->shouldReceive('update')
            ->once()
            ->andThrow(new \Tracker_FormElement_InvalidFieldValueException());

        $this->expectException(RestException::class);
        $this->expectExceptionCode(400);
        $this->update(9);
    }

    public function testUpdateCardMappedFieldDoesNotRethrowNoChangeException(): void
    {
        $this->artifactsAreValid();
        $done_column = $this->mockColumn(9);
        $this->mockCanUserUpdateField($done_column, 789, true);
        $this->mapped_values_retriever->shouldReceive('getValuesMappedToColumn')
            ->andReturn(new MappedValues([1024, 2048]));
        $this->artifact_updater->shouldReceive('update')
            ->once()
            ->andThrow(new \Tracker_NoChangeException(40, 'user_story #40'));

        $this->update(9);
    }

    public function testUpdateCardMappedFieldRethrowsTrackerException(): void
    {
        $this->artifactsAreValid();
        $done_column = $this->mockColumn(9);
        $this->mockCanUserUpdateField($done_column, 789, true);
        $this->mapped_values_retriever->shouldReceive('getValuesMappedToColumn')
            ->andReturn(new MappedValues([1024, 2048]));
        $this->artifact_updater->shouldReceive('update')
            ->once()
            ->andThrow(new \Tracker_Exception());

        $this->expectException(RestException::class);
        $this->expectExceptionCode(500);
        $this->update(9);
    }

    public function testUpdateCardMappedFieldUpdatesArtifactWithFirstMappedValue(): void
    {
        $this->artifactsAreValid();
        $done_column = $this->mockColumn(9);
        $this->mockCanUserUpdateField($done_column, 789, true);
        $this->mapped_values_retriever->shouldReceive('getValuesMappedToColumn')
            ->andReturn(new MappedValues([1024, 2048]));
        $this->artifact_updater->shouldReceive('update')
            ->once()
            ->withArgs(
                function (PFUser $user, Tracker_Artifact $art, array $values) {
                    return $user === $this->current_user
                        && $art === $this->artifact_to_add
                        && $values[0]->field_id === 789
                        && $values[0]->bind_value_ids = [1024];
                }
            );

        $this->update(9);
    }

    private function mockCanUserUpdateField(
        Cardwall_Column $done_column,
        int $field_id,
        bool $can_update_mapped_field
    ): void {
        $milestone_tracker = M::mock(Tracker::class)
            ->shouldReceive(['getId' => 76])
            ->getMock();
        $tracker           = M::mock(Tracker::class)
            ->shouldReceive(['getName' => 'Tasks'])
            ->getMock();
        $this->artifact_to_add->shouldReceive('getTracker')
            ->andReturn($tracker);
        $this->milestone_tracker_retriever->shouldReceive('getMilestoneTrackerOfColumn')
            ->with($done_column)
            ->andReturn($milestone_tracker);

        $mapped_field = M::mock(Tracker_FormElement_Field_Selectbox::class);
        $mapped_field->shouldReceive('userCanUpdate')
            ->with($this->current_user)
            ->andReturn($can_update_mapped_field);
        $mapped_field->shouldReceive('getLabel')
            ->andReturn('Status');
        $mapped_field->shouldReceive('getId')
            ->andReturn($field_id);

        $this->mapped_field_retriever->shouldReceive('getField')
            ->withArgs(
                function (TaskboardTracker $arg) use ($milestone_tracker, $tracker) {
                    return $arg->getMilestoneTrackerId() === $milestone_tracker->getId()
                        && $arg->getTracker() === $tracker;
                }
            )
            ->andReturn($mapped_field);
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
        $this->add_validator->shouldReceive('validateArtifacts')
            ->with($this->swimlane_artifact, $this->artifact_to_add, $this->current_user)
            ->once();
    }

    private function mockColumn(int $id): Cardwall_Column
    {
        $done_column = new Cardwall_Column($id, 'Done', 'acid-green');
        $this->column_factory->shouldReceive('getColumnById')
            ->with($id)
            ->andReturn($done_column);
        return $done_column;
    }
}
