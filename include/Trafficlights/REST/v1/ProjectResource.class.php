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

use Luracast\Restler\RestException;
use Tuleap\REST\Header;
use UserManager;
use TrackerFactory;
use Tracker_ArtifactFactory;
use Tracker_FormElementFactory;
use PFUser;
use Tuleap\Trafficlights\Config;
use Tuleap\Trafficlights\ConfigConformanceValidator;
use ProjectManager;
use Tuleap\Trafficlights\Dao;
use Tracker_FormElement_Field_List_Bind;

class ProjectResource {

    const MAX_LIMIT = 1000;

    /** @var PFUser */
    private $user;

    /** @var Config */
    private $config;

    /** @var ProjectManager */
    private $project_manager;

    /** @var TrackerFactory */
    private $tracker_factory;

    /** @var Tracker_ArtifactFactory */
    private $tracker_artifact_factory;

    /** @var Tracker_FormElementFactory */
    private $tracker_form_element_factory;

    /** @var DefinitionRepresentationBuilder */
    private $definition_representation_builder;

    public function __construct() {
        $this->config                            = new Config(new Dao());
        $this->project_manager                   = ProjectManager::instance();
        $this->tracker_factory                   = TrackerFactory::instance();
        $this->tracker_artifact_factory          = Tracker_ArtifactFactory::instance();
        $this->tracker_form_element_factory      = Tracker_FormElementFactory::instance();
        $this->user_manager                      = UserManager::instance();
        $this->user                              = UserManager::instance()->getCurrentUser();
        $this->definition_representation_builder = new DefinitionRepresentationBuilder(
            $this->user_manager,
            $this->tracker_form_element_factory,
            new ConfigConformanceValidator(
                $this->config
            )
        );
    }

    /**
     * @url OPTIONS {id}/trafficlights_campaigns
     */
    public function optionsCampaigns($id) {
        Header::allowOptionsGet();
    }

    /**
     * Get campaigns
     *
     * Get testing campaigns for a given project
     *
     * @url GET {id}/trafficlights_campaigns
     *
     * @param int $id Id of the project
     * @param int $limit  Number of elements displayed per page {@from path}
     * @param int $offset Position of the first element to display {@from path}
     *
     * @return array {@type Tuleap\Trafficlights\REST\v1\CampaignRepresentation}
     */
    protected function getCampaigns($id, $limit = 10, $offset = 0) {
        $this->optionsCampaigns($id);

        $project = $this->getProject($id);

        $campaign_tracker_id = $this->config->getCampaignTrackerId($project);
        if (! $campaign_tracker_id) {
            throw new RestException(400, 'The campaign tracker id is not well configured');
        }

        $campaign_tracker = $this->tracker_factory->getTrackerById($campaign_tracker_id);
        if (! $campaign_tracker) {
            throw new RestException(404, 'The campaign tracker does not exist');
        }

        if (! $campaign_tracker->userCanView($this->user)) {
            throw new RestException(403, 'Access denied to campaign tracker');
        }

        $artifact_list = $this->tracker_artifact_factory->getArtifactsByTrackerIdUserCanView($this->user, $campaign_tracker_id);

        $result = array();

        foreach ($artifact_list as $artifact) {
            $campaign_representation = new CampaignRepresentation();
            $campaign_representation->build($artifact, $this->tracker_form_element_factory, $this->user);
            $result[$artifact->getId()] = $campaign_representation;
        }

        $this->sendPaginationHeaders($limit, $offset, count($result));

        krsort($result);

        return array_slice($result, $offset, $limit);
    }

    /**
     * @url OPTIONS {id}/trafficlights_definitions
     */
    public function optionsDefinitions($id) {
        Header::allowOptionsGet();
    }

    /**
     * Get test definitions
     *
     * Get all test projects for a given project
     *
     * @url GET {id}/trafficlights_definitions
     *
     * @param int $id Id of the project
     * @param int $limit  Number of elements displayed per page {@from path}
     * @param int $offset Position of the first element to display {@from path}
     *
     * @return array {DefinitionRepresentation}
     */
    protected function getDefinitions($id, $limit = 10, $offset = 0) {
        $this->optionsDefinitions($id);

        $project = $this->getProject($id);

        $tracker_id = $this->config->getTestDefinitionTrackerId($project);
        $tracker    = $this->tracker_factory->getTrackerById($tracker_id);
        if (! $tracker) {
            throw new RestException(400, 'The test definition tracker id is not well configured');
        }

        if (! $tracker->userCanView($this->user)) {
            throw new RestException(403, 'Access denied to the test definition tracker');
        }

        $paginated_artifacts = $this->tracker_artifact_factory->getPaginatedArtifactsByTrackerId($tracker_id, $limit, $offset, false);
        $result = array();

        foreach ($paginated_artifacts->getArtifacts() as $artifact) {
            if (! $artifact->userCanView($this->user)) {
                continue;
            }

            $definition_representation = $this->definition_representation_builder->getDefinitionRepresentation($this->user, $artifact);

            if ($definition_representation) {
                $result[] = $this->definition_representation_builder->getDefinitionRepresentation($this->user, $artifact);
            }
        }

        $this->sendPaginationHeaders($limit, $offset, $paginated_artifacts->getTotalSize());

        return $result;
    }

    /**
     * @url OPTIONS {id}/trafficlights_environments
     */
    public function optionsEnvironments($id) {
        Header::allowOptionsGet();
    }

    /**
     * Get test environments
     *
     * Get all test environments for a given project
     *
     * @url GET {id}/trafficlights_environments
     *
     * @param int $id Id of the project
     * @param int $limit  Number of elements displayed per page {@from path}
     * @param int $offset Position of the first element to display {@from path}
     *
     * @return array
     */
    protected function getEnvironments($id, $limit = 10, $offset = 0) {
        $this->optionsEnvironments($id);

        $project = $this->getProject($id);

        $tracker_id = $this->config->getTestExecutionTrackerId($project);
        $tracker    = $this->tracker_factory->getTrackerById($tracker_id);
        if (! $tracker) {
            throw new RestException(400, 'The execution tracker id is not well configured');
        }

        if (! $tracker->userCanView($this->user)) {
            throw new RestException(403, 'Access denied to the test definition tracker');
        }

        $execution_field = $this->tracker_form_element_factory->getUsedFieldByNameForUser($tracker_id, ExecutionRepresentation::FIELD_ENVIRONMENT, $this->user);

        if (! $execution_field) {
            throw new RestException(400, 'The environment field of execution tracker is not well configured');
        }


        $result = array();
        $field_as_json = $execution_field->fetchFormattedForJson();
        foreach($field_as_json['values'] as $value) {
            $environment = new EnvironmentRepresentation();
            $environment->build(
                $value['id'],
                $value['label']
            );
            $result[] = $environment;
        }

        $this->sendPaginationHeaders($limit, $offset, count($result));

        return array_slice($result, $offset, $limit);
    }

    private function getProject($id) {
        $project = $this->project_manager->getProject($id);
        if ($project->isError()) {
            throw new RestException(404, 'Project not found');
        }

        return $project;
    }

    private function sendPaginationHeaders($limit, $offset, $size) {
        Header::sendPaginationHeaders($limit, $offset, $size, self::MAX_LIMIT);
    }
}
