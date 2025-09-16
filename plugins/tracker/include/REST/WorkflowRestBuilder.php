<?php
/**
 * Copyright (c) Enalean 2022 -  Present. All Rights Reserved.
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
 *
 */

namespace Tuleap\Tracker\REST;

use PFUser;
use Transition;
use Workflow;
use Workflow_Transition_Condition_Permissions;

class WorkflowRestBuilder
{
    public function getWorkflowRepresentation(Workflow $workflow, PFUser $user): ?WorkflowRepresentation
    {
        if ($workflow->getField() && ! $workflow->getField()->userCanRead($user)) {
            return null;
        }

        $transitions = [];
        foreach ($workflow->getTransitions() as $transition) {
            $condition_permission = $this->getConditionPermissions($transition);

            if ($this->checkTransitionIsValid($transition) && ($condition_permission->isUserAllowedToSeeTransition($user, $workflow->getTracker()) || $workflow->getTracker()->userIsAdmin($user))) {
                $transitions[] = $this->getWorkflowTransitionRepresentation($transition);
            }
        }

        return new WorkflowRepresentation(
            $workflow,
            $this->getWorkflowRulesRepresentation($workflow),
            $transitions
        );
    }

    public function getWorkflowRulesRepresentation(Workflow $workflow): WorkflowRulesRepresentation
    {
        return new WorkflowRulesRepresentation(
            $this->getListOfWorkflowRuleDateRepresentation($workflow),
            $this->getListOfWorkflowRuleListRepresentation($workflow)
        );
    }

    /** @return WorkflowRuleDateRepresentation[] */
    private function getListOfWorkflowRuleDateRepresentation(Workflow $workflow): array
    {
        $dates = [];
        foreach ($workflow->getGlobalRulesManager()->getAllDateRulesByTrackerId($workflow->getTrackerId()) as $rule) {
            $rule_date_representation = new WorkflowRuleDateRepresentation();
            $rule_date_representation->build(
                $rule->getSourceFieldId(),
                $rule->getTargetFieldId(),
                $rule->getComparator()
            );
            $dates[] = $rule_date_representation;
        }

        return $dates;
    }

    /** @return WorkflowRuleListRepresentation[] */
    private function getListOfWorkflowRuleListRepresentation(Workflow $workflow): array
    {
        $lists = [];
        foreach ($workflow->getGlobalRulesManager()->getAllListRulesByTrackerWithOrder($workflow->getTrackerId()) as $rule) {
            $rule_list_representation = new WorkflowRuleListRepresentation();
            $rule_list_representation->build(
                $rule->getSourceFieldId(),
                $rule->getSourceValue(),
                $rule->getTargetFieldId(),
                $rule->getTargetValue()
            );
            $lists[] = $rule_list_representation;
        }

        return $lists;
    }

    private function getWorkflowTransitionRepresentation(Transition $transition): WorkflowTransitionRepresentation
    {
        $workflow_representation = new WorkflowTransitionRepresentation();
        $workflow_representation->build(
            $transition->getId(),
            $transition->getIdFrom(),
            (int) $transition->getIdTo()
        );

        return $workflow_representation;
    }

    private function checkTransitionIsValid(Transition $transition): bool
    {
        return $transition->getIdTo() !== '';
    }

    /**
     * Protected for testing purpose
     */
    protected function getConditionPermissions(Transition $transition): Workflow_Transition_Condition_Permissions
    {
        return new Workflow_Transition_Condition_Permissions($transition);
    }
}
