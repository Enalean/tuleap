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
use Tracker_FormElement_Field;
use Transition;
use Workflow;

class TransitionRetriever
{
    public function retrieveTransition(
        Workflow $workflow,
        array $fields_data,
        ?Tracker_Artifact_Changeset $previous_changeset = null
    ): ?Transition {
        $workflow_field    = $workflow->getField();
        $workflow_field_id = (int) $workflow->getFieldId();

        $from = $this->getFrom($workflow_field, $previous_changeset);

        if (isset($fields_data[$workflow_field_id])) {
            return $this->getTransitionFromSubmittedData(
                (int) $fields_data[$workflow_field_id],
                $workflow,
                $from
            );
        } elseif (
            $this->isArtifactCreatedWithWorkflowFieldDefaultValue($fields_data, $workflow_field_id)
        ) {
            return $this->getTransitionFromWorkflowFieldDefaultValueAtArtifactCreation(
                $workflow_field,
                $workflow,
                $from
            );
        }

        return null;
    }

    private function getFrom(
        Tracker_FormElement_Field $workflow_field,
        ?Tracker_Artifact_Changeset $previous_changeset
    ): ?int {
        if (! $previous_changeset) {
            return null;
        }

        $previous_changeset_value = $previous_changeset->getValue($workflow_field);
        if (! $previous_changeset_value) {
            return null;
        }

        $previous_value = $previous_changeset_value->getValue();
        if (! $previous_value) {
            return null;
        }

        return (int) current($previous_value);
    }

    private function getTransitionFromSubmittedData(int $workflow_field_value, Workflow $workflow, ?int $from): ?Transition
    {
        return $workflow->getTransition($from, $workflow_field_value);
    }

    private function getTransitionFromWorkflowFieldDefaultValueAtArtifactCreation(
        Tracker_FormElement_Field $workflow_field,
        Workflow $workflow,
        ?int $from
    ): ?Transition {
        $default_value = $workflow_field->getDefaultValue();
        if ($default_value === null) {
            return null;
        }

        $to         = (int) $default_value;
        return $workflow->getTransition($from, $to);
    }

    private function isArtifactCreatedWithWorkflowFieldDefaultValue(array $fields_data, int $workflow_field_id): bool
    {
        return ! isset($fields_data[$workflow_field_id]) &&
            isset($fields_data['request_method_called']) &&
            $fields_data['request_method_called'] === 'submit-artifact';
    }
}
