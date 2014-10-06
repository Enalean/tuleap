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

namespace Tuleap\Testing\REST\v1;

use Luracast\Restler\RestException;
use Tuleap\REST\Header;
use Tracker_ArtifactFactory;
use Tracker_FormElementFactory;
use ProjectManager;
use UserManager;
use PFUser;
use Tuleap\Testing\ConfigConformanceValidator;
use Tuleap\Testing\Config;
use Tuleap\Testing\Dao;
use Tracker_Artifact;
use TrackerFactory;
use Tracker_REST_Artifact_ArtifactCreator;
use Tracker_REST_Artifact_ArtifactValidator;

class CampaignsResource {

    const MAX_LIMIT = 50;

    /** @var UserManager */
    private $user_manager;

    /** @var Tracker_ArtifactFactory */
    private $artifact_factory;

    /** @var Tracker_FormElementFactory */
    private $formelement_factory;

    /** @var ConfigConformanceValidator */
    private $conformance_validator;

    /** @var ExecutionRepresentationBuilder */
    private $execution_representation_builder;

    /** @var AssignedToRepresentationBuilder */
    private $assigned_to_representation_builder;

    /** @var ProjectManager */
    private $project_manager;

    public function __construct() {
        $this->project_manager       = ProjectManager::instance();
        $this->user_manager          = UserManager::instance();
        $tracker_factory             = TrackerFactory::instance();
        $this->artifact_factory      = Tracker_ArtifactFactory::instance();
        $this->formelement_factory   = Tracker_FormElementFactory::instance();
        $config                      = new Config(new Dao());
        $this->conformance_validator = new ConfigConformanceValidator(
            $config
        );

        $this->assigned_to_representation_builder = new AssignedToRepresentationBuilder(
            $this->formelement_factory,
            $this->user_manager
        );
        $this->execution_representation_builder   = new ExecutionRepresentationBuilder(
            $this->user_manager,
            $this->formelement_factory,
            $this->conformance_validator,
            $this->assigned_to_representation_builder
        );

        $artifact_creator = new Tracker_REST_Artifact_ArtifactCreator(
            new Tracker_REST_Artifact_ArtifactValidator(
                $this->formelement_factory
            ),
            $this->artifact_factory,
            $tracker_factory
        );

        $execution_creator = new ExecutionCreator(
            $this->formelement_factory,
            $config,
            $this->project_manager,
            $tracker_factory,
            $artifact_creator
        );

        $this->campaign_creator = new CampaignCreator(
            $this->formelement_factory,
            $config,
            $this->project_manager,
            $tracker_factory,
            $artifact_creator,
            $execution_creator
        );
    }

    /**
     * @url OPTIONS {id}
     */
    public function optionsId($id) {
        Header::allowOptionsGet();
    }

    /**
     * Get campaign
     *
     * Get testing campaign by its id
     *
     * @url GET {id}
     *
     * @param int $id Id of the campaign
     *
     * @return Tuleap\Testing\REST\v1\CampaignRepresentation
     */
    protected function getId($id) {
        $this->optionsId($id);

        $user     = $this->user_manager->getCurrentUser();
        $campaign = $this->getCampaignFromId($id, $user);

        $campaign_representation = new CampaignRepresentation();
        $campaign_representation->build(
            $campaign,
            $this->formelement_factory,
            $user
        );

        return $campaign_representation;
    }

    /**
     * @url OPTIONS {id}/testing_executions
     */
    public function optionsExecutions($id) {
        Header::allowOptionsGet();
    }

    /**
     * Get executions
     *
     * Get executions of a given campaign
     *
     * @url GET {id}/testing_executions
     *
     * @param int $id Id of the campaign
     * @param int $limit  Number of elements displayed per page {@from path}
     * @param int $offset Position of the first element to display {@from path}
     *
     * @return array {@type Tuleap\Testing\REST\v1\ExecutionRepresentation}
     */
    protected function getExecutions($id, $limit = 10, $offset = 0) {
        $this->optionsExecutions($id);

        $user     = $this->user_manager->getCurrentUser();
        $campaign = $this->getCampaignFromId($id, $user);

        $execution_representations = $this->execution_representation_builder->getPaginatedExecutionsRepresentationsForCampaign(
            $user,
            $campaign,
            $limit,
            $offset
        );

        $this->sendPaginationHeaders($limit, $offset, $execution_representations->getTotalSize());

        return $execution_representations->getRepresentations();
    }

    /**
     * @url OPTIONS {id}/testing_assignees
     */
    public function optionsAssignees($id) {
        Header::allowOptionsGet();
    }

    /**
     * Get assignees
     *
     * Get all users that are assigned to at least one test execution of the
     * given campaign
     *
     * @url GET {id}/testing_assignees
     *
     * @param int $id Id of the campaign
     * @param int $limit  Number of elements displayed per page {@from path}
     * @param int $offset Position of the first element to display {@from path}
     *
     * @return array {@type Tuleap\User\REST\UserRepresentation}
     */
    protected function getAssignees($id, $limit = 10, $offset = 0) {
        $this->optionsAssignees($id);

        $user     = $this->user_manager->getCurrentUser();
        $campaign = $this->getCampaignFromId($id, $user);

        $assignees = $this->getAssigneesForCampaign($user, $campaign);

        $this->sendPaginationHeaders($limit, $offset, count($assignees));

        return array_slice($assignees, $offset, $limit);
    }

    /**
     * @url OPTIONS {id}/testing_environments
     */
    public function optionsEnvironments($id) {
        Header::allowOptionsGet();
    }

    /**
     * Get environments
     *
     * Get all environments that are used by at least one test execution of the
     * given campaign
     *
     * @url GET {id}/testing_environments
     *
     * @param int $id Id of the campaign
     * @param int $limit  Number of elements displayed per page {@from path}
     * @param int $offset Position of the first element to display {@from path}
     *
     * @return array {@type Tuleap\User\REST\UserRepresentation}
     */
    protected function getEnvironments($id, $limit = 10, $offset = 0) {
        $this->optionsEnvironments($id);

        $user     = $this->user_manager->getCurrentUser();
        $campaign = $this->getCampaignFromId($id, $user);

        $environments = $this->getEnvironmentsForCampaign($user, $campaign);

        $this->sendPaginationHeaders($limit, $offset, count($environments));

        return array_slice($environments, $offset, $limit);
    }

    /**
     * @url OPTIONS
     */
    public function options() {
        Header::allowOptionsPost();
    }

    /**
     * POST campaign
     *
     * Create a new campaign
     *
     * @url POST
     *
     * @param int    $project_id   Id of the project the campaign will belong to
     * @param string $label        The label of the new campaign
     * @param array  $environments Associated environments and test definitions
     */
    protected function post($project_id, $label, $environments) {
        $this->options();
        return $this->campaign_creator->createCampaignAndExecutions(
            UserManager::instance()->getCurrentUser(),
            $project_id,
            $label,
            $environments
        );
    }

    private function getEnvironmentsForCampaign(PFUser $user, Tracker_Artifact $campaign) {
        $environments = array();

        $execution_representations = $this->execution_representation_builder->getAllExecutionsRepresentationsForCampaign($user, $campaign);
        foreach ($execution_representations as $execution_representation) {
            $environments[] = $execution_representation->environment;
        }

        return array_unique($environments);
    }

    private function getAssigneesForCampaign(PFUser $user, Tracker_Artifact $campaign) {
        $assignees = array();

        $executions = $this->execution_representation_builder->getExecutionsForCampaign($user, $campaign);
        foreach ($executions as $execution) {
            $assigned_to_representation = $this->assigned_to_representation_builder->getAssignedToRepresentationForExecution($user, $execution);

            if (! $assigned_to_representation) {
                continue;
            }

            if (isset($assignees[$assigned_to_representation->id])) {
                continue;
            }

            $assignees[$assigned_to_representation->id] = $assigned_to_representation;
        }

        return $assignees;
    }

    private function getCampaignFromId($id, PFUser $user) {
        $campaign = $this->artifact_factory->getArtifactById($id);

        if (! $this->isACampaign($campaign)) {
            throw new RestException(404, 'The campaign does not exist');
        }

        if (! $campaign->userCanView($user)) {
            throw new RestException(403, 'Access denied to this campaign');
        }

        return $campaign;
    }

    private function sortByCategoryAndId(array &$execution_representations) {
        usort($execution_representations, function ($a, $b) {
            $def_a = $a->definition;
            $def_b = $b->definition;

            $category_cmp = strnatcasecmp($def_a->category, $def_b->category);
            if ($category_cmp !== 0) {
                return $category_cmp;
            }

            return strcmp($def_a->id, $def_b->id);
        });
    }

    private function isACampaign($campaign) {
        return $campaign && $this->conformance_validator->isArtifactACampaign($campaign);
    }

    private function sendPaginationHeaders($limit, $offset, $size) {
        Header::sendPaginationHeaders($limit, $offset, $size, self::MAX_LIMIT);
    }
}