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

use Tuleap\Tracker\REST\CompleteTrackerRepresentation;
use Tuleap\Tracker\REST\FormElementRepresentationsBuilder;
use Tuleap\Tracker\REST\StructureElementRepresentation;
use Tuleap\Tracker\REST\Tracker\PermissionsRepresentationBuilder;
use Tuleap\Tracker\REST\WorkflowRepresentation;
use Tuleap\Tracker\REST\WorkflowTransitionRepresentation;
use Tuleap\Tracker\REST\WorkflowRulesRepresentation;
use Tuleap\Tracker\REST\WorkflowRuleDateRepresentation;
use Tuleap\Tracker\REST\WorkflowRuleListRepresentation;

class Tracker_REST_TrackerRestBuilder
{
    /** @var Tracker_FormElementFactory */
    private $formelement_factory;

    /**
     * @var FormElementRepresentationsBuilder
     */
    private $form_element_representations_builder;
    /**
     * @var PermissionsRepresentationBuilder
     */
    private $permissions_representation_builder;

    public function __construct(
        Tracker_FormElementFactory $formelement_factory,
        FormElementRepresentationsBuilder $form_element_representations_builder,
        PermissionsRepresentationBuilder $permissions_representation_builder
    ) {
        $this->formelement_factory                  = $formelement_factory;
        $this->form_element_representations_builder = $form_element_representations_builder;
        $this->permissions_representation_builder   = $permissions_representation_builder;
    }

    public function getTrackerRepresentationInTrackerContext(PFUser $user, Tracker $tracker) : CompleteTrackerRepresentation
    {
        return $this->buildTrackerRepresentation(
            $user,
            $tracker,
            $this->form_element_representations_builder->buildRepresentationsInTrackerContext(
                $tracker,
                $user
            )
        );
    }

    public function getTrackerRepresentationInArtifactContext(PFUser $user, Tracker_Artifact $artifact) : CompleteTrackerRepresentation
    {
        $tracker = $artifact->getTracker();

        return $this->buildTrackerRepresentation(
            $user,
            $tracker,
            $this->form_element_representations_builder->buildRepresentationsInArtifactContext(
                $artifact,
                $user
            )
        );
    }

    private function buildTrackerRepresentation(PFUser $user, Tracker $tracker, array $rest_fields): CompleteTrackerRepresentation
    {
        $semantic_manager = $this->getSemanticManager($tracker);
        $tracker_representation = new CompleteTrackerRepresentation();
        $tracker_representation->build(
            $tracker,
            $rest_fields,
            $this->getStructureRepresentation($tracker),
            $semantic_manager->exportToREST($user),
            $this->getWorkflowRepresentation($tracker->getWorkflow(), $user, $tracker->getGroupId()),
            $this->permissions_representation_builder->getPermissionsRepresentation($tracker, $user)
        );

        return $tracker_representation;
    }

    /**
     * This is for tests
     * I know it's crappy but there is no clean alternative as semantic manager
     * requires a tracker as a parameter (I cannot pass it as constructor argument
     * because the tracker is an argument of the method, not the class).
     *
     * @return Tracker_SemanticManager
     */
    protected function getSemanticManager(Tracker $tracker)
    {
        return new Tracker_SemanticManager($tracker);
    }

    private function getStructureRepresentation(Tracker $tracker)
    {
        $structure_element_representations = array();
        $form_elements                     = $this->formelement_factory->getUsedFormElementForTracker($tracker);

        if ($form_elements) {
            foreach ($form_elements as $form_element) {
                $structure_element_representation = new StructureElementRepresentation();
                $structure_element_representation->build($form_element);

                $structure_element_representations[] = $structure_element_representation;
            }
        }

        return $structure_element_representations;
    }

    /**
     * @return Tuleap\Tracker\REST\WorkflowRepresentation | null
     */
    private function getWorkflowRepresentation(Workflow $workflow, PFUser $user, $project_id)
    {
        if ($workflow->getField() && ! $workflow->getField()->userCanRead($user)) {
            return;
        }

        $transitions = array();
        foreach ($workflow->getTransitions() as $transition) {
            $condition_permission = new Workflow_Transition_Condition_Permissions($transition);

            if ($condition_permission->isUserAllowedToSeeTransition($user, $workflow->getTracker())) {
                $transitions[] = $this->getWorkflowTransitionRepresentation($transition);
            }
        }

        $workflow_representation = new WorkflowRepresentation();
        $workflow_representation->build(
            $workflow,
            $this->getWorkflowRulesRepresentation($workflow),
            $transitions
        );

        return $workflow_representation;
    }

    /**
     *
     * @return WorkflowRulesRepresentation
     */
    public function getWorkflowRulesRepresentation(Workflow $workflow)
    {
        $workflow_representation = new WorkflowRulesRepresentation();
        $workflow_representation->build(
            $this->getListOfWorkflowRuleDateRepresentation($workflow),
            $this->getListOfWorkflowRuleListRepresentation($workflow)
        );

        return $workflow_representation;
    }

    /** @return Tuleap\Tracker\REST\WorkflowRuleListRepresentation[] */
    private function getListOfWorkflowRuleDateRepresentation(Workflow $workflow)
    {
        $rules_manager = $workflow->getGlobalRulesManager();
        $dates = array();
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

    /** @return Tuleap\Tracker\REST\WorkflowRuleListRepresentation[] */
    private function getListOfWorkflowRuleListRepresentation(Workflow $workflow)
    {
        $lists = array();
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

    /**
     *
     *
     * @return Tuleap\Tracker\REST\WorkflowTransitionRepresentation
     */
    private function getWorkflowTransitionRepresentation(Transition $transition)
    {
        $workflow_representation = new WorkflowTransitionRepresentation();
        $workflow_representation->build(
            $transition->getId(),
            $transition->getIdFrom(),
            $transition->getIdTo()
        );

        return $workflow_representation;
    }
}
