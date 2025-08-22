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

declare(strict_types=1);

namespace Tuleap\Tracker\Workflow;

use PFUser;
use Tracker_Rule_List;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\FormElement\Field\ListFields\Bind\BindValueIdCollection;
use Tuleap\Tracker\FormElement\Field\ListField;
use Tuleap\Tracker\Rule\FirstValidValueAccordingToDependenciesRetriever;

class FirstPossibleValueInListRetriever
{
    public function __construct(
        private FirstValidValueAccordingToDependenciesRetriever $first_valid_value_according_to_dependencies_retriever,
        private ValidValuesAccordingToTransitionsRetriever $valid_values_according_to_transitions_retriever,
    ) {
    }

    /**
     * @throws NoPossibleValueException
     */
    public function getFirstPossibleValue(
        Artifact $artifact,
        ListField $field,
        BindValueIdCollection $list_of_values,
        PFUser $user,
    ): int {
        $workflow = $artifact->getWorkflow();

        if (! $workflow) {
            return $list_of_values->getFirstValue();
        }

        $this->valid_values_according_to_transitions_retriever->getValidValuesAccordingToTransitions(
            $artifact,
            $field,
            $list_of_values,
            $workflow,
            $user
        );

        $rules_for_tracker = $this->getRulesForTracker($workflow, $artifact);
        $value             = $this->first_valid_value_according_to_dependencies_retriever->getFirstValidValuesAccordingToDependencies(
            $list_of_values,
            $field,
            $artifact,
            $rules_for_tracker
        );

        if ($value === null) {
            throw new NoPossibleValueException();
        }

        return $value;
    }

    /**
     * @return Tracker_Rule_List[]
     */
    private function getRulesForTracker(\Workflow $workflow, Artifact $artifact): array
    {
        return $workflow->getGlobalRulesManager()->getAllListRulesByTrackerWithOrder(
            $artifact->getTracker()->getId()
        );
    }
}
