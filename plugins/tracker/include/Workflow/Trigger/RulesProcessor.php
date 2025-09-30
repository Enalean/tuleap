<?php
/**
 * Copyright (c) Enalean, 2013-Present. All Rights Reserved.
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

use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\Artifact\Changeset\Comment\CommentFormatIdentifier;
use Tuleap\Tracker\Workflow\Trigger\Siblings\SiblingsRetriever;
use Tuleap\Tracker\Workflow\WorkflowBackendLogger;

/**
 * Apply the Rule target if all rules conditions are met after artifact change
 */
class Tracker_Workflow_Trigger_RulesProcessor // phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace,Squiz.Classes.ValidClassName.NotPascalCase
{
    /** @var Tracker_Workflow_WorkflowUser */
    private $workflow_user;

    /** @var WorkflowBackendLogger */
    private $logger;

    /**
     * @var array<int, true>
     */
    private $artifacts_visited_in_processing = [];

    /**
     * @var SiblingsRetriever
     */
    private $siblings_retriever;

    public function __construct(
        Tracker_Workflow_WorkflowUser $workflow_user,
        SiblingsRetriever $siblings_retriever,
        WorkflowBackendLogger $logger,
    ) {
        $this->workflow_user      = $workflow_user;
        $this->logger             = $logger;
        $this->siblings_retriever = $siblings_retriever;
    }

    /**
     * Apply $rule that was triggered by a change on $artifact
     *
     */
    public function process(Artifact $artifact, Tracker_Workflow_Trigger_TriggerRule $rule)
    {
        $this->logger->start(__METHOD__, $artifact->getXRef(), $rule->getId());

        $parent = $artifact->getParentWithoutPermissionChecking();

        if ($parent === null) {
            $this->logger->end(__METHOD__, $artifact->getId(), $rule->getId());
            return;
        }

        if (isset($this->artifacts_visited_in_processing[$artifact->getId()])) {
            unset($this->artifacts_visited_in_processing[$artifact->getId()]);
            $this->logger->error('Cycle detected in the hierarchy while processing trigger rules for artifact #' . $artifact->getId());
            $this->logger->end(__METHOD__, $artifact->getId(), $rule->getId());
            return;
        }
        $this->artifacts_visited_in_processing[$artifact->getId()] = true;

        if (! $this->parentAlreadyHasTargetValue($parent, $rule)) {
            $this->logger->debug('Parent ' . $parent->getXRef() . ' does not have target value…');
            $processor_strategy = $this->getRuleStrategy($artifact, $rule);
            if ($processor_strategy->allPrecondtionsAreMet()) {
                $this->logger->debug('All preconditions are met…');
                $this->updateParent($parent, $artifact, $rule);
            }
        }

        unset($this->artifacts_visited_in_processing[$artifact->getId()]);
        $this->logger->end(__METHOD__, $artifact->getId(), $rule->getId());
    }

    private function updateParent(Artifact $parent, Artifact $child, Tracker_Workflow_Trigger_TriggerRule $rule): void
    {
        $rule_parent_target_tracker_id = $rule->getTargetTracker()->getId();
        if ($parent->getTrackerId() !== $rule_parent_target_tracker_id) {
            $this->logger->error(
                'Rule #' . $rule->getId() . ' tries to update the parent artifact #' . $parent->getId() .
                ' but this artifact is not in parent tracker #' . $rule_parent_target_tracker_id . '. This is likely due to' .
                ' an inconsistency in the tracker hierarchy and the defined rules.'
            );
            return;
        }

        $target = $rule->getTarget();
        try {
            $comment  = '<p>' . sprintf(dgettext('tuleap-tracker', 'Automatic change triggered by an update of <a href="%2$s">%1$s</a>.'), 'art #' . $child->getId(), $child->getLastChangeset()->getUri()) . '</p>';
            $comment .= '<p>' . $rule->getAsChangesetComment() . '</p>';
            $parent->createNewChangeset(
                $target->getFieldData(),
                $comment,
                $this->workflow_user,
                true,
                CommentFormatIdentifier::HTML
            );
            $this->logger->debug('Parent successfully updated.');
        } catch (Tracker_Exception $e) {
            $this->logger->debug('Error while updating the parent artifact: ' . $e->getMessage());
            $GLOBALS['Response']->addFeedback(
                'error',
                sprintf(dgettext('tuleap-tracker', 'Error while updating the parent artifact %1$s: %2$s'), $parent->fetchDirectLinkToArtifact(), $e->getMessage()),
                CODENDI_PURIFIER_DISABLED
            );
        }
    }

    private function parentAlreadyHasTargetValue(Artifact $parent, Tracker_Workflow_Trigger_TriggerRule $rule)
    {
        return $rule->getTarget()->isSetForArtifact($parent);
    }

    private function getRuleStrategy(Artifact $artifact, Tracker_Workflow_Trigger_TriggerRule $rule)
    {
        if ($rule->getCondition() == Tracker_Workflow_Trigger_RulesBuilderData::CONDITION_AT_LEAST_ONE) {
            return new Tracker_Workflow_Trigger_RulesProcessor_AtLeastOneStrategy();
        } else {
            return new Tracker_Workflow_Trigger_RulesProcessor_AllOfStrategy(
                $artifact,
                $rule,
                $this->siblings_retriever
            );
        }
    }
}
