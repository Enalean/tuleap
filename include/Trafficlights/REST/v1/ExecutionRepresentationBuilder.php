<?php
/**
 * Copyright (c) Enalean, 2014. All Rights Reserved.
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

namespace Tuleap\Trafficlights\REST\v1;

use Tuleap\Trafficlights\ConfigConformanceValidator;
use Tuleap\User\REST\UserRepresentation;
use Tracker_Artifact;
use Tracker_Artifact_PaginatedArtifacts;
use PFUser;
use Tracker_FormElementFactory;
use UserManager;

class ExecutionRepresentationBuilder {

    /**
     * @var UserManager
     */
    private $user_manager;

    /**
     * @var AssignedToRepresentationBuilder
     */
    private $assigned_to_representation_builder;

    /**
     * @var Tracker_FormElementFactory
     */
    private $tracker_form_element_factory;

    /**
     * @var ConfigConformanceValidator
     */
    private $conformance_validator;

    public function __construct(
        UserManager $user_manager,
        Tracker_FormElementFactory $tracker_form_element_factory,
        ConfigConformanceValidator $conformance_validator,
        AssignedToRepresentationBuilder $assigned_to_representation_builder
    ) {
        $this->user_manager                       = $user_manager;
        $this->tracker_form_element_factory       = $tracker_form_element_factory;
        $this->conformance_validator              = $conformance_validator;
        $this->assigned_to_representation_builder = $assigned_to_representation_builder;
    }

    /**
     * @return \Tuleap\Trafficlights\REST\v1\ExecutionRepresentation
     */
    public function getAllExecutionsRepresentationsForCampaign(PFUser $user, Tracker_Artifact $artifact) {
        $executions = $this->getExecutionsForCampaign($user, $artifact);

        return $this->getListOfRepresentations($user, $executions);
    }

    /**
     * @return \Tuleap\Trafficlights\REST\v1\SlicedExecutionRepresentations
     */
    public function getPaginatedExecutionsRepresentationsForCampaign(
        PFUser $user,
        Tracker_Artifact $artifact,
        $limit,
        $offset
    ) {
        $executions      = $this->getSlicedExecutionsForCampaign($user, $artifact, $limit, $offset);
        $representations = $this->getListOfRepresentations($user, $executions->getArtifacts());

        return new SlicedExecutionRepresentations($representations, $executions->getTotalSize());
    }

    /**
     * @return \Tuleap\Trafficlights\REST\v1\ExecutionRepresentation
     */
    public function getExecutionRepresentation(
        PFUser $user,
        Tracker_Artifact $execution
    ) {
        $previous_result_representation = $this->getPreviousResultRepresentationForExecution($user, $execution);
        $definition_representation      = $this->getDefinitionRepresentationForExecution($user, $execution);
        $execution_representation       = new ExecutionRepresentation();
        $execution_representation->build(
            $execution->getId(),
            $execution->getStatus(),
            $this->getExecutionEnvironment($user, $execution),
            $this->getExecutionResult($user, $execution),
            $execution->getLastUpdateDate(),
            $this->assigned_to_representation_builder->getAssignedToRepresentationForExecution($user, $execution),
            $previous_result_representation,
            $definition_representation,
            $this->getExecutionTime($user, $execution)
        );

        return $execution_representation;
    }

    private function getListOfRepresentations(PFUser $user, array $executions) {
        $executions_representations = array();

        foreach($executions as $execution) {
            $previous_result_representation = $this->getPreviousResultRepresentationForExecution($user, $execution);
            $definition_representation      = $this->getDefinitionRepresentationForExecution($user, $execution);
            $execution_representation       = new ExecutionRepresentation();
            $execution_representation->build(
                $execution->getId(),
                $execution->getStatus(),
                $this->getExecutionEnvironment($user, $execution),
                $this->getExecutionResult($user, $execution),
                $execution->getLastUpdateDate(),
                $this->assigned_to_representation_builder->getAssignedToRepresentationForExecution($user, $execution),
                $previous_result_representation,
                $definition_representation,
                $this->getExecutionTime($user, $execution)
            );

            $executions_representations[] = $execution_representation;
        }

        return $executions_representations;
    }

    /**
     * @return Tracker_Artifact_PaginatedArtifacts
     */
    private function getSlicedExecutionsForCampaign(
        PFUser $user,
        Tracker_Artifact $campaign_artifact,
        $limit,
        $offset
    ) {
        $artifact_links = $campaign_artifact->getSlicedLinkedArtifacts($user, $limit, $offset);
        $executions     = $artifact_links->getArtifacts();
        $this->removeArtifactsThatAreNotExecution($executions, $campaign_artifact);

        return new Tracker_Artifact_PaginatedArtifacts($executions, $artifact_links->getTotalSize());
    }

    /**
     * @return Tracker_Artifact[]
     */
    public function getExecutionsForCampaign(PFUser $user, Tracker_Artifact $campaign_artifact) {
        $executions = $campaign_artifact->getLinkedArtifacts($user);

        $this->removeArtifactsThatAreNotExecution($executions, $campaign_artifact);

        return $executions;
    }

    private function removeArtifactsThatAreNotExecution(array &$artifacts, Tracker_Artifact $campaign_artifact) {
        foreach ($artifacts as $key => $child_artifact) {
            if (! $this->conformance_validator->isArtifactAnExecution($child_artifact)) {
                unset($artifacts[$key]);
            }
        }
    }

    private function getDefinitionRepresentationForExecution(PFUser $user, Tracker_Artifact $execution) {
        $art_links = $execution->getLinkedArtifacts($user);
        foreach ($art_links as $art_link) {
            if ($this->conformance_validator->isArtifactAnExecutionOfDefinition($execution, $art_link)) {
                $definition_representation = new DefinitionRepresentation();
                $definition_representation->build($art_link, $this->tracker_form_element_factory, $user);
                return $definition_representation;
            }
        }

        return null;
    }

    private function getExecutionResult(PFUser $user, Tracker_Artifact $execution) {
        $results_field = $this->tracker_form_element_factory->getUsedFieldByNameForUser($execution->getTrackerId(), ExecutionRepresentation::FIELD_RESULTS, $user);

        $changeset_value = $execution->getValue($results_field);
        if (! $changeset_value) {
            return '';
        }

        return $changeset_value->getText();
    }

    private function getExecutionTime(PFUser $user, Tracker_Artifact $execution) {
        $results_field = $this->tracker_form_element_factory->getUsedFieldByNameForUser($execution->getTrackerId(), ExecutionRepresentation::FIELD_TIME, $user);
        if (! $results_field) {
            return '';
        }

        $changeset_value = $execution->getValue($results_field);
        if (! $changeset_value) {
            return '';
        }

        return $changeset_value->getValue();
    }

    private function getExecutionEnvironment(PFUser $user, Tracker_Artifact $execution) {
        $results_field = $this->tracker_form_element_factory->getUsedFieldByNameForUser($execution->getTrackerId(), ExecutionRepresentation::FIELD_ENVIRONMENT, $user);

        $changeset_value = $execution->getValue($results_field);
        if (! $changeset_value) {
            return '';
        }

        $first_value = array_pop($changeset_value->getListValues());
        if (! $first_value) {
            return '';
        }

        return $first_value->getLabel();
    }

    private function getPreviousResultRepresentationForExecution(
        PFUser $user,
        Tracker_Artifact $execution
    ) {
        $last_changeset = $execution->getLastChangeset();
        if (! $last_changeset) {
            return null;
        }

        $submitted_by = $this->user_manager->getUserById($last_changeset->getSubmittedBy());
        $user_representation = new UserRepresentation();
        $user_representation->build($submitted_by);

        $previous_result_representation = new PreviousResultRepresentation();
        $previous_result_representation->build(
            $last_changeset->getSubmittedOn(),
            $user_representation,
            $execution->getStatusForChangeset($last_changeset),
            $this->getExecutionResult($user, $execution)
        );

        return $previous_result_representation;
    }
}
