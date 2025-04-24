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

namespace Tuleap\Tracker\Workflow\Transition;

use Tracker_Artifact_Changeset;
use Tracker_Artifact_ChangesetValue_List;
use Tracker_FormElement_Field_Selectbox;
use Transition;
use Tuleap\Tracker\Test\Builders\Fields\List\ListStaticValueBuilder;
use Workflow;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class TransitionRetrieverTest extends \Tuleap\Test\PHPUnit\TestCase
{
    /**
     * @var TransitionRetriever
     */
    private $retriever;

    protected function setUp(): void
    {
        parent::setUp();

        $this->retriever = new TransitionRetriever();
    }

    public function testItRetrievesTransitionsFromSubmittedValuesAtArtifactCreation(): void
    {
        $transition = new Transition(
            1,
            2,
            null,
            ListStaticValueBuilder::aStaticValue('on going')->withId(59)->build()
        );

        $fields_data = [
            '98' => '59',
        ];

        $previous_changeset = null;

        $workflow_field = $this->createMock(Tracker_FormElement_Field_Selectbox::class);
        $workflow       = $this->createPartialMock(Workflow::class, ['getField', 'getFieldId', 'getTransitions']);

        $workflow->expects($this->once())->method('getField')
            ->willReturn($workflow_field);

        $workflow->expects($this->once())->method('getFieldId')
            ->willReturn(98);

        $workflow->expects($this->once())->method('getTransitions')
            ->willReturn([$transition]);

        self::assertSame(
            $transition,
            $this->retriever->retrieveTransition(
                $workflow,
                $fields_data,
                $previous_changeset
            )
        );
    }

    public function testItRetrievesTransitionsFromDefaultValuesAtArtifactCreation(): void
    {
        $transition = new Transition(
            1,
            2,
            null,
            ListStaticValueBuilder::aStaticValue('on going')->withId(59)->build()
        );

        $fields_data = [
            'request_method_called' => 'submit-artifact',
        ];

        $previous_changeset = null;

        $workflow_field = $this->createMock(Tracker_FormElement_Field_Selectbox::class);
        $workflow       = $this->createPartialMock(Workflow::class, ['getField', 'getFieldId', 'getTransitions']);

        $workflow->expects($this->once())->method('getField')
            ->willReturn($workflow_field);

        $workflow->expects($this->once())->method('getFieldId')
            ->willReturn(98);

        $workflow->expects($this->once())->method('getTransitions')
            ->willReturn([$transition]);

        $workflow_field->expects($this->once())->method('getDefaultValue')->willReturn('59');

        self::assertSame(
            $transition,
            $this->retriever->retrieveTransition(
                $workflow,
                $fields_data,
                $previous_changeset
            )
        );
    }

    public function testItRetrievesTransitionsFromSubmittedValuesAtArtifactUpdate(): void
    {
        $transition = new Transition(
            1,
            2,
            ListStaticValueBuilder::aStaticValue('to do')->withId(58)->build(),
            ListStaticValueBuilder::aStaticValue('on going')->withId(59)->build()
        );

        $fields_data = [
            '98' => '59',
        ];

        $workflow_field = $this->createMock(Tracker_FormElement_Field_Selectbox::class);
        $workflow       = $this->createPartialMock(Workflow::class, ['getField', 'getFieldId', 'getTransitions']);

        $workflow->expects($this->once())->method('getField')
            ->willReturn($workflow_field);

        $workflow->expects($this->once())->method('getFieldId')
            ->willReturn(98);

        $workflow->expects($this->once())->method('getTransitions')
            ->willReturn([$transition]);

        $previous_changeset_value_list = $this->createMock(Tracker_Artifact_ChangesetValue_List::class);
        $previous_changeset_value_list->method('getValue')->willReturn(['58']);

        $previous_changeset = $this->createMock(Tracker_Artifact_Changeset::class);
        $previous_changeset->expects($this->once())->method('getValue')
            ->with($workflow_field)
            ->willReturn($previous_changeset_value_list);

        self::assertSame(
            $transition,
            $this->retriever->retrieveTransition(
                $workflow,
                $fields_data,
                $previous_changeset
            )
        );
    }

    public function testItDoesNotRetrieveTransitionsFromDefaultValuesAtArtifactUpdate(): void
    {
        $fields_data = [];

        $workflow_field = $this->createMock(Tracker_FormElement_Field_Selectbox::class);
        $workflow       = $this->createPartialMock(Workflow::class, ['getField', 'getFieldId', 'getTransitions']);

        $workflow->expects($this->once())->method('getField')
            ->willReturn($workflow_field);

        $workflow->expects($this->once())->method('getFieldId')
            ->willReturn(98);

        $workflow->expects($this->never())->method('getTransitions');
        $workflow_field->expects($this->never())->method('getDefaultValue');

        $previous_changeset_value_list = $this->createMock(Tracker_Artifact_ChangesetValue_List::class);
        $previous_changeset_value_list->method('getValue')->willReturn(['58']);

        $previous_changeset = $this->createMock(Tracker_Artifact_Changeset::class);
        $previous_changeset->expects($this->once())->method('getValue')
            ->with($workflow_field)
            ->willReturn($previous_changeset_value_list);

        $this->assertNull(
            $this->retriever->retrieveTransition(
                $workflow,
                $fields_data,
                $previous_changeset
            )
        );
    }
}
