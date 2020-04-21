<?php
/**
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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

namespace Tuleap\TestManagement\REST\v1;

require_once __DIR__ . '/../../../bootstrap.php';

use Luracast\Restler\RestException;
use PFUser;
use PHPUnit\Framework\TestCase;
use Tracker_Artifact;
use Tracker_Artifact_Changeset;
use Tracker_FormElement_Field_List;
use Tracker_FormElementFactory;
use Tuleap\TestManagement\Campaign\Execution\ExecutionDao;
use Tuleap\TestManagement\Step\Definition\Field\StepDefinition;
use Tuleap\TestManagement\Step\Definition\Field\StepDefinitionChangesetValue;
use Tuleap\TestManagement\Step\Execution\Field\StepExecution;
use Tuleap\TestManagement\Step\Execution\Field\StepExecutionChangesetValue;
use Tuleap\TestManagement\Step\Execution\StepResult;
use Tuleap\TestManagement\Step\Step;
use Tuleap\Tracker\REST\v1\ArtifactValuesRepresentation;

class StepsResultsChangesBuilderTest extends TestCase
{
    private $form_element_factory;
    private $execution_dao;
    /** @var StepsResultsChangesBuilder */
    private $builder;
    private $execution_artifact;
    private $definition_artifact;
    private $user;
    private $execution_field;
    private $definition_field;

    private $execution_tracker_id = 123;
    private $definition_tracker_id = 124;
    private $definition_changeset;
    private $definition_changeset_value;

    private $test_status_builder;
    private $execution_status_field;

    public function setUp(): void
    {
        $this->user                       = $this->createMock(PFUser::class);
        $this->execution_artifact         = $this->createMock(Tracker_Artifact::class);
        $this->definition_artifact        = $this->createMock(Tracker_Artifact::class);
        $this->form_element_factory       = $this->createMock(Tracker_FormElementFactory::class);
        $this->execution_dao              = $this->createMock(ExecutionDao::class);
        $this->execution_field            = $this->createMock(StepExecution::class);
        $this->execution_status_field     = $this->createMock(Tracker_FormElement_Field_List::class);
        $this->definition_field           = $this->createMock(StepDefinition::class);
        $this->definition_changeset       = $this->createMock(Tracker_Artifact_Changeset::class);
        $this->definition_changeset_value = $this->createMock(StepDefinitionChangesetValue::class);
        $this->test_status_builder        = $this->createMock(TestStatusAccordingToStepsStatusChangesBuilder::class);
        $this->builder                    = new StepsResultsChangesBuilder(
            $this->form_element_factory,
            $this->execution_dao,
            $this->test_status_builder
        );

        $this->execution_artifact->method('getTrackerId')->willReturn($this->execution_tracker_id);
        $this->definition_artifact->method('getTrackerId')->willReturn($this->definition_tracker_id);
    }

    public function testItReturnsAnArrayThatCanBeUsedToChangeStepsResults()
    {
        $map = [
            [$this->execution_tracker_id, 'steps_results', $this->user, $this->execution_field],
            [$this->definition_tracker_id, 'steps', $this->user, $this->definition_field]
        ];

        $this->form_element_factory->method('getUsedFieldByNameForUser')->will($this->returnValueMap($map));

        $this->execution_dao->method('searchDefinitionsChangesetIdsForExecution')->willReturn([]);

        $this->definition_artifact->method('getLastChangeset')->willReturn($this->definition_changeset);

        $value_map = [
            [$this->definition_field, $this->definition_changeset, $this->definition_changeset_value]
        ];
        $this->definition_artifact->method('getValue')->will($this->returnValueMap($value_map));

        $step1 = $this->getStep(1);
        $step2 = $this->getStep(2);
        $this->definition_changeset_value->method('getValue')->willReturn([$step1, $step2]);

        $submitted_steps_results = [
            $this->getStepResultRepresentation(1, 'passed'),
            $this->getStepResultRepresentation(2, 'blocked'),
        ];

        $value_representation           = new ArtifactValuesRepresentation();
        $value_representation->field_id = $this->execution_field->getId();
        $value_representation->value    = [
            'steps_results' => [
                1 => 'passed',
                2 => 'blocked'
            ]
        ];

        $expected = [$value_representation];
        $this->assertEquals($expected, $this->getChanges($submitted_steps_results));
    }

    public function testThatTestStatusBuilderIsCalled()
    {
        $map = [
            [$this->execution_tracker_id, 'steps_results', $this->user, $this->execution_field],
            [$this->execution_tracker_id, 'status', $this->user, $this->execution_status_field],
            [$this->definition_tracker_id, 'steps', $this->user, $this->definition_field]
        ];

        $this->form_element_factory->method('getUsedFieldByNameForUser')->will($this->returnValueMap($map));

        $this->execution_dao->method('searchDefinitionsChangesetIdsForExecution')->willReturn([]);

        $this->definition_artifact->method('getLastChangeset')->willReturn($this->definition_changeset);

        $value_map = [
            [$this->definition_field, $this->definition_changeset, $this->definition_changeset_value]
        ];
        $this->definition_artifact->method('getValue')->will($this->returnValueMap($value_map));

        $step1 = $this->getStep(1);
        $step2 = $this->getStep(2);
        $this->definition_changeset_value->method('getValue')->willReturn([$step1, $step2]);

        $submitted_steps_results = [
            $this->getStepResultRepresentation(1, 'passed'),
            $this->getStepResultRepresentation(2, 'blocked'),
        ];

        $this->test_status_builder->expects($this->once())->method('enforceTestStatusAccordingToStepsStatus');

        $this->getChanges($submitted_steps_results);
    }

    public function testItIgnoresSubmittedStepsThatAreNotPartOfDefinition()
    {
        $map = [
            [$this->execution_tracker_id, 'steps_results', $this->user, $this->execution_field],
            [$this->definition_tracker_id, 'steps', $this->user, $this->definition_field]
        ];

        $this->form_element_factory->method('getUsedFieldByNameForUser')->will($this->returnValueMap($map));

        $this->execution_dao->method('searchDefinitionsChangesetIdsForExecution')->willReturn([]);

        $this->definition_artifact->method('getLastChangeset')->willReturn($this->definition_changeset);

        $value_map = [
            [$this->definition_field, $this->definition_changeset, $this->definition_changeset_value]
        ];
        $this->definition_artifact->method('getValue')->will($this->returnValueMap($value_map));

        $step1 = $this->getStep(1);
        $step2 = $this->getStep(2);
        $this->definition_changeset_value->method('getValue')->willReturn([$step1, $step2]);

        $submitted_steps_results = [
            $this->getStepResultRepresentation(1, 'passed'),
            $this->getStepResultRepresentation(2, 'blocked'),
            $this->getStepResultRepresentation(3, 'blocked'),
        ];

        $value_representation           = new ArtifactValuesRepresentation();
        $value_representation->field_id = $this->execution_field->getId();
        $value_representation->value    = [
            'steps_results' => [
                1 => 'passed',
                2 => 'blocked'
            ]
        ];

        $expected = [$value_representation];
        $this->assertEquals($expected, $this->getChanges($submitted_steps_results));
    }

    public function testItReuseThePreviousStatusIfStepsIsNotPresentInSubmittedValues()
    {
        $map = [
            [$this->execution_tracker_id, 'steps_results', $this->user, $this->execution_field],
            [$this->definition_tracker_id, 'steps', $this->user, $this->definition_field]
        ];

        $this->form_element_factory->method('getUsedFieldByNameForUser')->will($this->returnValueMap($map));

        $this->execution_dao->method('searchDefinitionsChangesetIdsForExecution')->willReturn([]);

        $this->definition_artifact->method('getLastChangeset')->willReturn($this->definition_changeset);

        $value_map = [
            [$this->definition_field, $this->definition_changeset, $this->definition_changeset_value]
        ];
        $this->definition_artifact->method('getValue')->will($this->returnValueMap($value_map));

        $step1 = $this->getStep(1);
        $step2 = $this->getStep(2);
        $this->definition_changeset_value->method('getValue')->willReturn([$step1, $step2]);

        $submitted_steps_results = [
            $this->getStepResultRepresentation(2, 'blocked'),
        ];

        $this->setUpExistingStepResult($step1, 'passed');

        $value_representation           = new ArtifactValuesRepresentation();
        $value_representation->field_id = $this->execution_field->getId();
        $value_representation->value    = [
            'steps_results' => [
                1 => 'passed',
                2 => 'blocked'
            ]
        ];

        $expected = [$value_representation];
        $this->assertEquals($expected, $this->getChanges($submitted_steps_results));
    }

    public function testItRaisesExceptionIfThereIsNoExecutionField()
    {
        $map = [
            [$this->execution_tracker_id, 'steps_results', $this->user, null],
            [$this->definition_tracker_id, 'steps', $this->user, $this->definition_field]
        ];

        $this->form_element_factory->method('getUsedFieldByNameForUser')->will($this->returnValueMap($map));

        $this->expectException(RestException::class);

        $submitted_steps_results = [];
        $this->getChanges($submitted_steps_results);
    }

    public function testItRaisesExceptionIfThereIsNoDefinitionField()
    {
        $map = [
            [$this->execution_tracker_id, 'steps_results', $this->user, $this->execution_field],
            [$this->definition_tracker_id, 'steps', $this->user, null]
        ];

        $this->form_element_factory->method('getUsedFieldByNameForUser')->will($this->returnValueMap($map));

        $this->expectException(RestException::class);

        $submitted_steps_results = [];
        $this->getChanges($submitted_steps_results);
    }

    public function testItGetsASpecificDefinitionChangeset()
    {
        $map = [
            [$this->execution_tracker_id, 'steps_results', $this->user, $this->execution_field],
            [$this->definition_tracker_id, 'steps', $this->user, $this->definition_field]
        ];

        $this->form_element_factory->method('getUsedFieldByNameForUser')->will($this->returnValueMap($map));

        $this->execution_dao
            ->method('searchDefinitionsChangesetIdsForExecution')
            ->willReturn(
                [
                    ['definition_changeset_id' => 1001]
                ]
            );
        $this->definition_artifact->method('getChangeset')->willReturn($this->definition_changeset);
        $value_map = [
            [$this->definition_field, $this->definition_changeset, $this->definition_changeset_value]
        ];
        $this->definition_artifact->method('getValue')->will($this->returnValueMap($value_map));
        $step1 = $this->getStep(1);
        $step2 = $this->getStep(2);
        $this->definition_changeset_value->method('getValue')->willReturn([$step1, $step2]);

        $this->definition_artifact->expects($this->never())->method('getLastChangeset');
        $this->definition_artifact->expects($this->once())->method('getChangeset')->with($this->equalTo(1001));

        $submitted_steps_results = [];
        $this->getChanges($submitted_steps_results);
    }

    public function testItGetsTheLastChangesetIfTheExecutionIsNotLinkedToASpecificDefinitionChangeset()
    {
        $map = [
            [$this->execution_tracker_id, 'steps_results', $this->user, $this->execution_field],
            [$this->definition_tracker_id, 'steps', $this->user, $this->definition_field]
        ];

        $this->form_element_factory->method('getUsedFieldByNameForUser')->will($this->returnValueMap($map));

        $this->execution_dao->method('searchDefinitionsChangesetIdsForExecution')->willReturn([]);
        $this->definition_artifact->method('getLastChangeset')->willReturn($this->definition_changeset);
        $value_map = [
            [$this->definition_field, $this->definition_changeset, $this->definition_changeset_value]
        ];
        $this->definition_artifact->method('getValue')->will($this->returnValueMap($value_map));
        $step1 = $this->getStep(1);
        $step2 = $this->getStep(2);
        $this->definition_changeset_value->method('getValue')->willReturn([$step1, $step2]);

        $this->definition_artifact->expects($this->once())->method('getLastChangeset');
        $this->definition_artifact->expects($this->never())->method('getChangeset');

        $submitted_steps_results = [];
        $this->getChanges($submitted_steps_results);
    }

    public function testItRaisesExceptionIfThereIsntAnyStepsInDefinitionChangeset()
    {
        $map = [
            [$this->execution_tracker_id, 'steps_results', $this->user, $this->execution_field],
            [$this->definition_tracker_id, 'steps', $this->user, $this->definition_field]
        ];

        $this->form_element_factory->method('getUsedFieldByNameForUser')->will($this->returnValueMap($map));

        $this->execution_dao->method('searchDefinitionsChangesetIdsForExecution')->willReturn([]);

        $this->definition_artifact->method('getLastChangeset')->willReturn(null);

        $this->expectException(RestException::class);

        $submitted_steps_results = [];
        $this->getChanges($submitted_steps_results);
    }

    public function testItRaisesExceptionIfThereIsNoStepsDefinedInDefinitionChangesetValue()
    {
        $map = [
            [$this->execution_tracker_id, 'steps_results', $this->user, $this->execution_field],
            [$this->definition_tracker_id, 'steps', $this->user, $this->definition_field]
        ];

        $this->form_element_factory->method('getUsedFieldByNameForUser')->will($this->returnValueMap($map));

        $this->execution_dao->method('searchDefinitionsChangesetIdsForExecution')->willReturn([]);

        $this->definition_artifact->method('getLastChangeset')->willReturn($this->definition_changeset);

        $value_map = [
            [$this->definition_field, $this->definition_changeset, null]
        ];
        $this->definition_artifact->method('getValue')->will($this->returnValueMap($value_map));

        $this->expectException(RestException::class);

        $submitted_steps_results = [];
        $this->getChanges($submitted_steps_results);
    }

    /**
     * @param $submitted_steps_results
     *
     * @return \Tuleap\Tracker\REST\v1\ArtifactValuesRepresentation[]
     * @throws \Luracast\Restler\RestException
     */
    private function getChanges($submitted_steps_results)
    {
        return $this->builder->getStepsChanges(
            $submitted_steps_results,
            $this->execution_artifact,
            $this->definition_artifact,
            $this->user
        );
    }

    /**
     * @param $id
     * @param $status
     *
     * @return StepResultRepresentation
     */
    private function getStepResultRepresentation($id, $status)
    {
        $step_result          = new StepResultRepresentation();
        $step_result->step_id = $id;
        $step_result->status  = $status;

        return $step_result;
    }

    /**
     * @param $id
     *
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    private function getStep($id)
    {
        $step = $this->createMock(Step::class);
        $step->method('getId')->willReturn($id);

        return $step;
    }

    /**
     * @param $step1
     * @param $status
     */
    private function setUpExistingStepResult($step1, $status)
    {
        $existing_step_result = $this->createMock(StepResult::class);
        $existing_step_result->method('getStep')->willReturn($step1);
        $existing_step_result->method('getStatus')->willReturn($status);
        $existing_steps_changeset_value = $this->createMock(StepExecutionChangesetValue::class);
        $existing_steps_changeset_value->method('getValue')->willReturn([$existing_step_result]);
        $this->execution_artifact->method('getValue')->willReturn($existing_steps_changeset_value);
    }
}
