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

use Luracast\Restler\RestException;
use PFUser;
use Tracker_Artifact;
use Tracker_FormElementFactory;
use Tuleap\TestManagement\Campaign\Execution\ExecutionDao;
use Tuleap\TestManagement\Step\Execution\Field\StepExecution;
use Tuleap\TestManagement\Step\Execution\StepResult;
use Tuleap\TestManagement\Step\Step;
use Tuleap\Tracker\REST\v1\ArtifactValuesRepresentation;

class StepsResultsChangesBuilder
{
    /**
     * @var Tracker_FormElementFactory
     */
    private $form_element_factory;
    /**
     * @var ExecutionDao
     */
    private $execution_dao;
    /**
     * @var TestStatusAccordingToStepsStatusChangesBuilder
     */
    private $test_status_changes_builder;

    public function __construct(
        Tracker_FormElementFactory $form_element_factory,
        ExecutionDao $execution_dao,
        TestStatusAccordingToStepsStatusChangesBuilder $test_status_changes_builder
    ) {
        $this->form_element_factory        = $form_element_factory;
        $this->execution_dao               = $execution_dao;
        $this->test_status_changes_builder = $test_status_changes_builder;
    }

    /**
     * @param StepResultRepresentation[] $submitted_steps_results
     *
     * @return ArtifactValuesRepresentation[]
     * @throws RestException
     */
    public function getStepsChanges(
        array $submitted_steps_results,
        Tracker_Artifact $execution_artifact,
        Tracker_Artifact $definition_artifact,
        PFUser $user
    ) {
        $execution_field = $this->getExecutionField($execution_artifact, $user);
        if (! $execution_field) {
            throw new RestException(
                400,
                'The execution tracker is misconfigured: missing Step execution field'
            );
        }

        $definition_field = $this->getDefinitionField($definition_artifact, $user);
        if (! $definition_field) {
            throw new RestException(
                400,
                'The definition tracker is misconfigured: missing Step definition field'
            );
        }

        $definition_changeset = $this->getDefinitionChangeset($execution_artifact, $definition_artifact);
        if (! $definition_changeset) {
            throw new RestException(
                400,
                "There isn't any steps defined in the test"
            );
        }

        $changeset_value = $definition_artifact->getValue($definition_field, $definition_changeset);
        /** @var Step[] $steps_defined_in_test */
        $steps_defined_in_test = $changeset_value ? $changeset_value->getValue() : [];
        if (count($steps_defined_in_test) === 0) {
            throw new RestException(
                400,
                "There isn't any steps defined in the test"
            );
        }

        $submitted_steps_results = $this->getSubmittedStepsResultsIndexedById($submitted_steps_results);
        assert($execution_field instanceof StepExecution);
        $existing_steps_results  = $this->getExistingStepsResultsIndexedById($execution_artifact, $execution_field);

        $steps_changes = [];
        foreach ($steps_defined_in_test as $step) {
            $id = $step->getId();
            if (isset($existing_steps_results[$id])) {
                $steps_changes[$id] = $existing_steps_results[$id]->getStatus();
            }
            if (isset($submitted_steps_results[$id])) {
                $steps_changes[$id] = $submitted_steps_results[$id];
            }
        }

        $value_representation           = new ArtifactValuesRepresentation();
        $value_representation->field_id = (int) $execution_field->getId();
        $value_representation->value    = [
            StepExecution::UPDATE_VALUE_KEY => $steps_changes
        ];

        $changes = [$value_representation];

        $this->enforceTestStatusAccordingToStepsStatus(
            $execution_artifact,
            $user,
            $changes,
            $steps_defined_in_test,
            $steps_changes
        );

        return $changes;
    }

    /**
     * @param StepResultRepresentation[] $submitted_steps_results
     *
     * @return array
     */
    private function getSubmittedStepsResultsIndexedById(array $submitted_steps_results)
    {
        $indexed_by_id = [];
        foreach ($submitted_steps_results as $step) {
            $indexed_by_id[$step->step_id] = $step->status;
        }

        return $indexed_by_id;
    }

    /**
     *
     * @return StepResult[]
     */
    private function getExistingStepsResultsIndexedById(
        Tracker_Artifact $execution_artifact,
        StepExecution $execution_field
    ) {
        $changeset_value = $execution_artifact->getValue($execution_field);
        if (! $changeset_value) {
            return [];
        }

        $indexed_by_id = [];
        foreach ($changeset_value->getValue() as $step_result) {
            \assert($step_result instanceof StepResult);
            $id                 = $step_result->getStep()->getId();
            $indexed_by_id[$id] = $step_result;
        }

        return $indexed_by_id;
    }

    private function getDefinitionField(Tracker_Artifact $definition_artifact, PFUser $user): ?\Tracker_FormElement_Field
    {
        return $this->form_element_factory->getUsedFieldByNameForUser(
            $definition_artifact->getTrackerId(),
            DefinitionRepresentation::FIELD_STEPS,
            $user
        );
    }

    private function getExecutionField(Tracker_Artifact $execution_artifact, PFUser $user): ?\Tracker_FormElement_Field
    {
        return $this->form_element_factory->getUsedFieldByNameForUser(
            $execution_artifact->getTrackerId(),
            ExecutionRepresentation::FIELD_STEPS_RESULTS,
            $user
        );
    }

    private function getStatusField(Tracker_Artifact $execution_artifact, PFUser $user): ?\Tracker_FormElement_Field
    {
        return $this->form_element_factory->getUsedFieldByNameForUser(
            $execution_artifact->getTrackerId(),
            ExecutionRepresentation::FIELD_STATUS,
            $user
        );
    }

    private function getDefinitionChangeset(Tracker_Artifact $execution_artifact, Tracker_Artifact $definition_artifact): ?\Tracker_Artifact_Changeset
    {
        $rows = $this->execution_dao->searchDefinitionsChangesetIdsForExecution([$execution_artifact->getId()]);
        if ($rows) {
            $definition_changeset = $definition_artifact->getChangeset($rows[0]['definition_changeset_id']);
        } else {
            $definition_changeset = $definition_artifact->getLastChangeset();
        }

        return $definition_changeset;
    }

    private function enforceTestStatusAccordingToStepsStatus(
        Tracker_Artifact $execution_artifact,
        PFUser $user,
        array &$changes,
        array $steps_defined_in_test,
        array $steps_changes
    ): void {
        $status_field = $this->getStatusField($execution_artifact, $user);
        if (! $status_field) {
            return;
        }
        assert($status_field instanceof \Tracker_FormElement_Field_List);

        $this->test_status_changes_builder->enforceTestStatusAccordingToStepsStatus(
            $status_field,
            $changes,
            $steps_defined_in_test,
            $steps_changes
        );
    }
}
