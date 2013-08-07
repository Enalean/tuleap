<?php
/**
 * Copyright (c) Enalean, 2013. All Rights Reserved.
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

/**
 * Apply the Rule target if all rules conditions are met after artifact change
 */
class Tracker_Workflow_Trigger_RulesProcessor {

    /**
     * Apply $rule that was triggered by a change on $artifact
     *
     * @param PFUser $user
     * @param Tracker_Artifact $artifact
     * @param Tracker_Workflow_Trigger_TriggerRule $rule
     */
    public function process(PFUser $user, Tracker_Artifact $artifact, Tracker_Workflow_Trigger_TriggerRule $rule) {
        $parent = $artifact->getParentWithoutPermissionChecking();
        if (! $this->parentAlreadyHasTargetValue($parent, $rule)) {
            $processor_strategy = $this->getRuleStrategy($artifact, $rule);
            if ($processor_strategy->allPrecondtionsAreMet()) {
                $target = $rule->getTarget();
                $parent->createNewChangeset($target->getFieldData(), '', $user, '', true);
            }
        }
    }

    private function parentAlreadyHasTargetValue(Tracker_Artifact $parent, Tracker_Workflow_Trigger_TriggerRule $rule) {
        return $rule->getTarget()->isSetForArtifact($parent);
    }

    private function getRuleStrategy(Tracker_Artifact $artifact, Tracker_Workflow_Trigger_TriggerRule $rule) {
        if ($rule->getCondition() == Tracker_Workflow_Trigger_RulesBuilderData::CONDITION_AT_LEAST_ONE) {
            return new Tracker_Workflow_Trigger_RulesProcessor_AtLeastOneStrategy();
        } else {
            return new Tracker_Workflow_Trigger_RulesProcessor_AllOfStrategy($artifact, $rule);
        }
    }
}

?>
