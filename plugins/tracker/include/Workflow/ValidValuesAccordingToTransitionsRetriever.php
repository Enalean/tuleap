<?php
/**
 * Copyright (c) Enalean, 2022-Present. All Rights Reserved.
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

namespace Tuleap\Tracker\Workflow;

use PFUser;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\FormElement\Field\ListFields\Bind\BindValueIdCollection;
use Workflow;
use Workflow_Transition_ConditionFactory;

class ValidValuesAccordingToTransitionsRetriever
{
    public function __construct(private Workflow_Transition_ConditionFactory $condition_factory)
    {
    }

    public function getValidValuesAccordingToTransitions(
        Artifact $artifact,
        \Tuleap\Tracker\FormElement\Field\ListField $field,
        BindValueIdCollection $list_of_values,
        Workflow $workflow,
        PFUser $user,
    ): void {
        $changeset_value      = $artifact->getValue($field);
        $field_artifact_value = $changeset_value ? (int) $changeset_value->getValue()[0] : null;

        $linked_field_artifact_value = $field->getListValueById($field_artifact_value);

        if (! $linked_field_artifact_value || ! $workflow->isUsed()) {
            return;
        }

        foreach ($list_of_values->getValueIds() as $value) {
            $transition = $workflow->getTransition($field_artifact_value, $value);
            if (! $transition) {
                $list_of_values->removeValue($value);
                continue;
            }

            $condition = $this->condition_factory->getPermissionsCondition($transition);
            if (! $condition->isUserAllowedToSeeTransition($user, $artifact->getTracker())) {
                $list_of_values->removeValue($value);
            }
        }
    }
}
