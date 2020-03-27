<?php
/**
 * Copyright (c) Enalean, 2014-present. All Rights Reserved.
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
use Tracker_ArtifactFactory;
use Tracker_FormElementFactory;
use Tuleap\TestManagement\ArtifactDao;
use Tuleap\TestManagement\Campaign\Execution\DefinitionForExecutionRetriever;
use Tuleap\TestManagement\Campaign\Execution\DefinitionNotFoundException;
use Tuleap\TestManagement\Campaign\Execution\ExecutionDao;
use Tuleap\TestManagement\Campaign\Execution\PaginatedExecutions;
use Tuleap\TestManagement\ConfigConformanceValidator;
use Tuleap\TestManagement\REST\v1\Execution\StepsResultsRepresentationBuilder;
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
    /**
     * @var ExecutionDao
     */
    private $execution_dao;
    /**
     * @var DefinitionForExecutionRetriever
     */
    private $definition_retriever;
    /**
     * @var StepsResultsRepresentationBuilder
     */
    private $steps_results_representation_builder;
    /**
     * @var \Codendi_HTMLPurifier
     */
    private $purifier;

    public function __construct(
        UserManager $user_manager,
        Tracker_FormElementFactory $tracker_form_element_factory,
        ConfigConformanceValidator $conformance_validator,
        AssignedToRepresentationBuilder $assigned_to_representation_builder,
        ArtifactDao $artifact_dao,
        Tracker_ArtifactFactory $artifact_factory,
        RequirementRetriever $requirement_retriever,
        DefinitionForExecutionRetriever $definition_retriever,
        ExecutionDao $execution_dao,
        StepsResultsRepresentationBuilder $steps_results_representation_builder,
        \Codendi_HTMLPurifier $purifier
    ) {
        $this->user_manager                         = $user_manager;
        $this->tracker_form_element_factory         = $tracker_form_element_factory;
        $this->conformance_validator                = $conformance_validator;
        $this->assigned_to_representation_builder   = $assigned_to_representation_builder;
        $this->artifact_dao                         = $artifact_dao;
        $this->artifact_factory                     = $artifact_factory;
        $this->requirement_retriever                = $requirement_retriever;
        $this->definition_retriever                 = $definition_retriever;
        $this->execution_dao                        = $execution_dao;
        $this->steps_results_representation_builder = $steps_results_representation_builder;
        $this->purifier                             = $purifier;
    }

    /**
     * @return \Tuleap\TestManagement\REST\v1\SlicedExecutionRepresentations
     *
     * @param false|int $execution_tracker_id
     */
    public function getPaginatedExecutionsRepresentationsForCampaign(
        PFUser $user,
        Tracker_Artifact $artifact,
        $execution_tracker_id,
        int $limit,
        int $offset
    ) {
        $executions      = $this->getSlicedExecutionsForCampaign($artifact, $user, (int) $execution_tracker_id, $limit, $offset);
        $representations = $this->getListOfRepresentations($user, $executions);

        return new SlicedExecutionRepresentations($representations, $executions->getTotalSize());
    }

    /**
     *
     * @return \Tuleap\TestManagement\REST\v1\ExecutionRepresentation
     * @throws DefinitionNotFoundException
     */
    public function getExecutionRepresentation(PFUser $user, Tracker_Artifact $execution)
    {
        $definitions_changeset_ids = $this->getDefinitionsChangesetIdsForExecutions([$execution->getId()]);

        return $this->getExecutionRepresentationWithSpecificChangesetForDefinition($user, $execution, $definitions_changeset_ids);
    }

    /**
     * @param array            $definitions_changeset_ids
     *
     * @return \Tuleap\TestManagement\REST\v1\ExecutionRepresentation
     * @throws DefinitionNotFoundException
     */
    private function getExecutionRepresentationWithSpecificChangesetForDefinition(
        PFUser $user,
        Tracker_Artifact $execution,
        array $definitions_changeset_ids
    ) {
        $previous_result_representation = $this->getPreviousResultRepresentationForExecution($user, $execution);

        $definition = $this->definition_retriever->getDefinitionRepresentationForExecution($user, $execution);
        $definition_representation = $this->getDefinitionRepresentationForExecution(
            $user,
            $execution,
            $definition,
            $definitions_changeset_ids
        );
        $execution_representation = new ExecutionRepresentation();
        $execution_representation->build(
            $execution->getId(),
            $execution->getStatus(),
            $this->getExecutionResult($user, $execution),
            $execution->getLastUpdateDate(),
            $this->assigned_to_representation_builder->getAssignedToRepresentationForExecution($user, $execution),
            $previous_result_representation,
            $definition_representation,
            $this->getLinkedBugsRepresentationForExecution($user, $execution),
            (int) $this->getExecutionTime($user, $execution),
            $this->steps_results_representation_builder->build($user, $execution, $definition)
        );
        return $execution_representation;
    }

    /**
     *
     * @return array
     */
    private function getListOfRepresentations(PFUser $user, PaginatedExecutions $executions)
    {
        $executions_representations = [];
        $definitions_changeset_ids  = $executions->getDefinitionsChangesetIds();

        foreach ($executions->getArtifacts() as $execution) {
            try {
                $executions_representations[] = $this->getExecutionRepresentationWithSpecificChangesetForDefinition(
                    $user,
                    $execution,
                    $definitions_changeset_ids
                );
            } catch (DefinitionNotFoundException $e) {
                // Ignore, the user may not be allowed to read the Definition
            }
        }

        return $executions_representations;
    }

    /**
     * @return PaginatedExecutions
     */
    private function getSlicedExecutionsForCampaign(
        Tracker_Artifact $campaign_artifact,
        PFUser $user,
        int $execution_tracker_id,
        int $limit,
        int $offset
    ) {
        $artifact_links_data = $this->artifact_dao->searchPaginatedExecutionArtifactsForCampaign(
            $campaign_artifact->getId(),
            $execution_tracker_id,
            $limit,
            $offset
        );

        $total_size     = (int) $this->artifact_dao->foundRows();
        $executions     = [];
        $executions_ids = [];

        foreach ($artifact_links_data as $row) {
            $artifact = $this->artifact_factory->getInstanceFromRow($row);

            if ($artifact->userCanView($user)) {
                $executions[]     = $artifact;
                $executions_ids[] = $row['id'];
            }
        }

        $definitions_changeset_ids = $this->getDefinitionsChangesetIdsForExecutions($executions_ids);

        return new PaginatedExecutions($executions, $total_size, $definitions_changeset_ids);
    }

    private function getDefinitionsChangesetIdsForExecutions(array $executions_ids): array
    {
        if (empty($executions_ids)) {
            return [];
        }

        $definitions_changeset_ids = [];

        $rows = $this->execution_dao->searchDefinitionsChangesetIdsForExecution($executions_ids);
        foreach ($rows as $row) {
            $definitions_changeset_ids[$row['execution_artifact_id']] = $row['definition_changeset_id'];
        }

        return $definitions_changeset_ids;
    }

    private function getDefinitionRepresentationForExecution(
        PFUser $user,
        Tracker_Artifact $execution,
        Tracker_Artifact $definition,
        array $definitions_changeset_ids
    ): DefinitionRepresentation {
        $definition_representation = new DefinitionRepresentation($this->purifier);
        $definition_representation->build(
            $definition,
            $this->tracker_form_element_factory,
            $user,
            $this->getSpecificDefinitionChangesetForExecution($execution, $definition, $definitions_changeset_ids),
            $this->requirement_retriever->getRequirementForDefinition($definition, $user)
        );

        return $definition_representation;
    }

    /**
     * @param array            $definitions_changeset_ids
     *
     * @return null|\Tracker_Artifact_Changeset
     */
    private function getSpecificDefinitionChangesetForExecution(
        Tracker_Artifact $execution,
        Tracker_Artifact $definition,
        array $definitions_changeset_ids
    ) {
        if (isset($definitions_changeset_ids[$execution->getId()])) {
            return $definition->getChangeset($definitions_changeset_ids[$execution->getId()]);
        }
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

    private function getExecutionResult(PFUser $user, Tracker_Artifact $execution): string
    {
        $changeset_value = $this->getFieldChangeValue($user, $execution, ExecutionRepresentation::FIELD_RESULTS);
        if (! $changeset_value) {
            return '';
        }

        return $changeset_value->getValue();
    }

    private function getExecutionTime(PFUser $user, Tracker_Artifact $execution): string
    {
        $changeset_value = $this->getFieldChangeValue($user, $execution, ExecutionRepresentation::FIELD_TIME);
        if (! $changeset_value) {
            return '';
        }

        return $changeset_value->getValue();
    }

    private function getPreviousResultRepresentationForExecution(
        PFUser $user,
        Tracker_Artifact $execution
    ): ?PreviousResultRepresentation {
        $last_changeset = $execution->getLastChangeset();
        if (! $last_changeset) {
            return null;
        }

        $submitted_by = $this->user_manager->getUserById($last_changeset->getSubmittedBy());
        $user_representation = new UserRepresentation();
        if ($submitted_by) {
            $user_representation->build($submitted_by);
        }

        $has_been_run_at_least_once = ! $execution->isFirstChangeset($last_changeset);

        $previous_result_representation = new PreviousResultRepresentation();
        $previous_result_representation->build(
            $last_changeset->getSubmittedOn(),
            $user_representation,
            $execution->getStatusForChangeset($last_changeset),
            $this->getExecutionResult($user, $execution),
            $has_been_run_at_least_once
        );

        return $previous_result_representation;
    }

    /**
     * @param  string          $field_name
     *
     * @return null|\Tracker_Artifact_ChangesetValue
     */
    private function getFieldChangeValue(PFUser $user, Tracker_Artifact $execution, $field_name)
    {
        $results_field = $this->tracker_form_element_factory->getUsedFieldByNameForUser(
            $execution->getTrackerId(),
            $field_name,
            $user
        );
        if (! $results_field) {
            return null;
        }

        return $execution->getValue($results_field);
    }
}
