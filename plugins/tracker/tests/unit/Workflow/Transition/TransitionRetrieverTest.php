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

use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tracker_Artifact_Changeset;
use Tracker_Artifact_ChangesetValue_List;
use Tracker_FormElement_Field_List_Bind_StaticValue;
use Tracker_FormElement_Field_Selectbox;
use Transition;
use Workflow;

final class TransitionRetrieverTest extends TestCase
{
    use MockeryPHPUnitIntegration;

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
            new Tracker_FormElement_Field_List_Bind_StaticValue(
                59,
                'on going',
                '',
                1,
                false
            )
        );

        $fields_data = [
            '98' => '59'
        ];

        $previous_changeset = null;

        $workflow_field = Mockery::mock(Tracker_FormElement_Field_Selectbox::class);
        $workflow = Mockery::mock(Workflow::class)
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();

        $workflow->shouldReceive('getField')
            ->once()
            ->andReturn($workflow_field);

        $workflow->shouldReceive('getFieldId')
            ->once()
            ->andReturn(98);

        $workflow->shouldReceive('getTransitions')
            ->once()
            ->andReturn([$transition]);

        $this->assertSame(
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
            new Tracker_FormElement_Field_List_Bind_StaticValue(
                59,
                'on going',
                '',
                1,
                false
            )
        );

        $fields_data = [
            'request_method_called' => 'submit-artifact'
        ];

        $previous_changeset = null;

        $workflow_field = Mockery::mock(Tracker_FormElement_Field_Selectbox::class);
        $workflow = Mockery::mock(Workflow::class)
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();

        $workflow->shouldReceive('getField')
            ->once()
            ->andReturn($workflow_field);

        $workflow->shouldReceive('getFieldId')
            ->once()
            ->andReturn(98);

        $workflow->shouldReceive('getTransitions')
            ->once()
            ->andReturn([$transition]);

        $workflow_field->shouldReceive('getDefaultValue')->once()->andReturn('59');

        $this->assertSame(
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
            new Tracker_FormElement_Field_List_Bind_StaticValue(
                58,
                'todo',
                '',
                1,
                false
            ),
            new Tracker_FormElement_Field_List_Bind_StaticValue(
                59,
                'on going',
                '',
                2,
                false
            )
        );

        $fields_data = [
            '98' => '59'
        ];

        $workflow_field = Mockery::mock(Tracker_FormElement_Field_Selectbox::class);
        $workflow = Mockery::mock(Workflow::class)
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();

        $workflow->shouldReceive('getField')
            ->once()
            ->andReturn($workflow_field);

        $workflow->shouldReceive('getFieldId')
            ->once()
            ->andReturn(98);

        $workflow->shouldReceive('getTransitions')
            ->once()
            ->andReturn([$transition]);

        $previous_changeset_value_list = Mockery::mock(Tracker_Artifact_ChangesetValue_List::class);
        $previous_changeset_value_list->shouldReceive('getValue')->andReturn(['58']);

        $previous_changeset = Mockery::mock(Tracker_Artifact_Changeset::class);
        $previous_changeset->shouldReceive('getValue')
            ->with($workflow_field)
            ->once()
            ->andReturn($previous_changeset_value_list);

        $this->assertSame(
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

        $workflow_field = Mockery::mock(Tracker_FormElement_Field_Selectbox::class);
        $workflow = Mockery::mock(Workflow::class)
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();

        $workflow->shouldReceive('getField')
            ->once()
            ->andReturn($workflow_field);

        $workflow->shouldReceive('getFieldId')
            ->once()
            ->andReturn(98);

        $workflow->shouldNotReceive('getTransitions');
        $workflow_field->shouldNotReceive('getDefaultValue');

        $previous_changeset_value_list = Mockery::mock(Tracker_Artifact_ChangesetValue_List::class);
        $previous_changeset_value_list->shouldReceive('getValue')->andReturn(['58']);

        $previous_changeset = Mockery::mock(Tracker_Artifact_Changeset::class);
        $previous_changeset->shouldReceive('getValue')
            ->with($workflow_field)
            ->once()
            ->andReturn($previous_changeset_value_list);

        $this->assertNull(
            $this->retriever->retrieveTransition(
                $workflow,
                $fields_data,
                $previous_changeset
            )
        );
    }
}
