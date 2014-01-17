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

    /** @var Tracker_Workflow_WorkflowUser */
    private $workflow_user;

    /** @var WorkflowBackendLogger */
    private $logger;

    public function __construct(Tracker_Workflow_WorkflowUser $workflow_user, WorkflowBackendLogger $logger) {
        $this->workflow_user = $workflow_user;
        $this->logger        = $logger;
    }

    /**
     * Apply $rule that was triggered by a change on $artifact
     *
     * @param Tracker_Artifact $artifact
     * @param Tracker_Workflow_Trigger_TriggerRule $rule
     */
    public function process(Tracker_Artifact $artifact, Tracker_Workflow_Trigger_TriggerRule $rule) {
        $this->logger->start(__METHOD__, $artifact->getXRef(), $rule->getId());

        $parent = $artifact->getParentWithoutPermissionChecking();
        if ($parent && ! $this->parentAlreadyHasTargetValue($parent, $rule)) {
            $this->logger->debug('Parent '. $parent->getXRef() .' does not have target value…');
            $processor_strategy = $this->getRuleStrategy($artifact, $rule);
            if ($processor_strategy->allPrecondtionsAreMet()) {
                $this->logger->debug('All preconditions are met…');
                $this->updateParent($parent, $artifact, $rule);
            }
        }

        $this->logger->end(__METHOD__, $artifact->getId(), $rule->getId());
    }

    private function updateParent(Tracker_Artifact $parent, Tracker_Artifact $child, Tracker_Workflow_Trigger_TriggerRule $rule) {
        $target = $rule->getTarget();
        try {
            $comment = '<p>'.$GLOBALS['Language']->getText('workflow_trigger_rules_processor', 'parent_update', array(
                'art #'.$child->getId(),
                $child->getLastChangeset()->getUri()
            )).'</p>';
            $comment .= '<p>'.$rule->getAsChangesetComment().'</p>';
            $parent->createNewChangeset(
                $target->getFieldData(),
                $comment,
                $this->workflow_user,
                true,
                Tracker_Artifact_Changeset_Comment::HTML_COMMENT
            );
            $this->logger->debug('Parent successfully updated.');
        } catch (Tracker_Exception $e) {
            $this->logger->debug('Error while updating the parent artifact: '. $e->getMessage());
            $GLOBALS['Response']->addFeedback(
                'error',
                $GLOBALS['Language']->getText('plugin_tracker_common_artifact', 'error_processor_update', array($parent->fetchDirectLinkToArtifact(), $e->getMessage())),
                CODENDI_PURIFIER_DISABLED
            );
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
