<?php
/**
 * Copyright (c) Enalean, 2014-2017. All Rights Reserved.
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

use PFUser;
use Tracker_Artifact;
use Tracker_Artifact_PaginatedArtifacts;
use Tracker_ArtifactFactory;
use Tracker_FormElementFactory;
use Tuleap\TestManagement\ArtifactDao;
use Tuleap\TestManagement\ConfigConformanceValidator;
use Tuleap\Tracker\REST\MinimalTrackerRepresentation;
use Tuleap\User\REST\UserRepresentation;
use UserManager;

class ExecutionRepresentationBuilder
{

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

    /**
     * @var ArtifactDao
     */
    private $artifact_dao;

    /**
     * @var Tracker_ArtifactFactory
     */
    private $artifact_factory;
    /**
     * @var RequirementRetriever
     */
    private $requirement_retriever;

    public function __construct(
        UserManager $user_manager,
        Tracker_FormElementFactory $tracker_form_element_factory,
        ConfigConformanceValidator $conformance_validator,
        AssignedToRepresentationBuilder $assigned_to_representation_builder,
        ArtifactDao $artifact_dao,
        Tracker_ArtifactFactory $artifact_factory,
        RequirementRetriever $requirement_retriever
    ) {
        $this->user_manager                       = $user_manager;
        $this->tracker_form_element_factory       = $tracker_form_element_factory;
        $this->conformance_validator              = $conformance_validator;
        $this->assigned_to_representation_builder = $assigned_to_representation_builder;
        $this->artifact_dao                       = $artifact_dao;
        $this->artifact_factory                   = $artifact_factory;
        $this->requirement_retriever              = $requirement_retriever;
    }

    /**
     * @return \Tuleap\TestManagement\REST\v1\SlicedExecutionRepresentations
     */
    public function getPaginatedExecutionsRepresentationsForCampaign(
        PFUser $user,
        Tracker_Artifact $artifact,
        $execution_tracker_id,
        $limit,
        $offset
    ) {
        $executions      = $this->getSlicedExecutionsForCampaign($artifact, $user, $execution_tracker_id, $limit, $offset);
        $representations = $this->getListOfRepresentations($user, $executions->getArtifacts());

        return new SlicedExecutionRepresentations($representations, $executions->getTotalSize());
    }

    /**
     * @return \Tuleap\TestManagement\REST\v1\ExecutionRepresentation
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
            $this->getExecutionResult($user, $execution),
            $execution->getLastUpdateDate(),
            $this->assigned_to_representation_builder->getAssignedToRepresentationForExecution($user, $execution),
            $previous_result_representation,
            $definition_representation,
            $this->getLinkedBugsRepresentationForExecution($user, $execution),
            $this->getExecutionTime($user, $execution)
        );

        return $execution_representation;
    }

    private function getListOfRepresentations(PFUser $user, array $executions) {
        $executions_representations = array();

        foreach($executions as $execution) {
            $executions_representations[] = $this->getExecutionRepresentation($user, $execution);
        }

        return $executions_representations;
    }

    /**
     * @return Tracker_Artifact_PaginatedArtifacts
     */
    private function getSlicedExecutionsForCampaign(
        Tracker_Artifact $campaign_artifact,
        PFUser $user,
        $execution_tracker_id,
        $limit,
        $offset
    ) {
        $artifact_links_data = $this->artifact_dao->searchPaginatedExecutionArtifactsForCampaign(
            $campaign_artifact->getId(),
            $execution_tracker_id,
            $limit,
            $offset
        );

        $total_size = (int) $this->artifact_dao->foundRows();
        $executions = array();

        foreach ($artifact_links_data as $row) {
            $artifact = $this->artifact_factory->getInstanceFromRow($row);

            if ($artifact->userCanView($user)) {
                $executions[] = $artifact;
            }
        }

        return new Tracker_Artifact_PaginatedArtifacts($executions, $total_size);
    }

    private function getDefinitionRepresentationForExecution(PFUser $user, Tracker_Artifact $execution)
    {
        $art_links = $execution->getLinkedArtifacts($user);
        foreach ($art_links as $art_link) {
            if ($this->conformance_validator->isArtifactAnExecutionOfDefinition($execution, $art_link)) {
                $requirement = $this->requirement_retriever->getRequirementForDefinition($art_link, $user);

                $definition_representation = new DefinitionRepresentation();
                $definition_representation->build($art_link, $this->tracker_form_element_factory, $user, $requirement);

                return $definition_representation;
            }
        }

        return null;
    }

    /**
     * @return array
     */
    private function getLinkedBugsRepresentationForExecution(PFUser $user, Tracker_Artifact $execution)
    {
        $art_links           = $execution->getLinkedArtifacts($user);
        $bug_representations = array();

        foreach ($art_links as $art_link) {
            if ($this->conformance_validator->isArtifactABug($art_link) && $art_link->userCanView($user)) {
                $bug_tracker_representation = new MinimalTrackerRepresentation();
                $bug_tracker_representation->build($art_link->getTracker());

                $bug_representation = new BugRepresentation();
                $bug_representation->build($art_link, $bug_tracker_representation);
                $bug_representations[] = $bug_representation;
            }
        }

        return $bug_representations;
    }

    private function getExecutionResult(PFUser $user, Tracker_Artifact $execution) {
        $results_field = $this->tracker_form_element_factory->getUsedFieldByNameForUser($execution->getTrackerId(), ExecutionRepresentation::FIELD_RESULTS, $user);

        $changeset_value = $execution->getValue($results_field);
        if (! $changeset_value) {
            return '';
        }

        return $changeset_value->getValue();
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
