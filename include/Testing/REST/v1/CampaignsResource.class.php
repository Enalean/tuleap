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
use UserManager;
use Tuleap\Testing\ConfigConformanceValidator;
use Tuleap\Testing\Config;
use Tuleap\Testing\Dao;
use Tuleap\User\REST\UserRepresentation;
use PFUser;
use Tracker_Artifact;

class CampaignsResource {

    const MAX_LIMIT = 50;

    /** @var UserManager */
    private $user_manager;

    /** @var Tracker_ArtifactFactory */
    private $tracker_artifact_factory;

    /** @var Tracker_FormElementFactory */
    private $tracker_form_element_factory;

    /** @var ConfigConformanceValidator */
    private $conformance_validator;

    public function __construct() {
        $this->user_manager                 = UserManager::instance();
        $this->tracker_artifact_factory     = Tracker_ArtifactFactory::instance();
        $this->tracker_form_element_factory = Tracker_FormElementFactory::instance();
        $this->conformance_validator        = new ConfigConformanceValidator(
            new Config(new Dao())
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
        $user     = $this->user_manager->getCurrentUser();
        $campaign = $this->getCampaignFromId($id, $user);

        Header::allowOptionsGet();
        $campaign_representation = new CampaignRepresentation();
        $campaign_representation->build(
            $campaign,
            $this->tracker_form_element_factory,
            $user
        );

        return $campaign_representation;
    }

    /**
     * Get assignees
     *
     * Get all users that are assigned to at least one test execution of the
     * given campaign
     *
     * @url GET {id}/assignees
     *
     * @param int $id Id of the campaign
     * @param int $limit  Number of elements displayed per page {@from path}
     * @param int $offset Position of the first element to display {@from path}
     *
     * @return array {@type Tuleap\User\REST\UserRepresentation}
     */
    protected function getAssignees($id, $limit = 10, $offset = 0) {
        $user     = $this->user_manager->getCurrentUser();
        $campaign = $this->getCampaignFromId($id, $user);

        $assignees = $this->getAssigneesForCampaign($user, $campaign);

        $this->sendPaginationHeaders($limit, $offset, count($assignees));

        return array_slice($assignees, $offset, $limit);
    }

    private function getAssigneesForCampaign(PFUser $user, Tracker_Artifact $campaign) {
        $assignees = array();

        $executions = $this->getExecutionsForCampaign($user, $campaign);
        foreach ($executions as $execution) {
            $field_value = $this->getExecutionAssignedToFieldValue($user, $execution);
            $user_id = array_pop($field_value);
            if (! $user_id) {
                continue;
            }

            if (isset($assignees[$user_id])) {
                continue;
            }

            $user = $this->user_manager->getUserById($user_id);
            if (! $user) {
                continue;
            }

            $user_representation = new UserRepresentation();
            $user_representation->build($user);

            $assignees[$user_id] = $user_representation;
        }

        return $assignees;
    }

    private function getExecutionsForCampaign(PFUser $user, Tracker_Artifact $campaign) {
        $executions = array();

        foreach ($campaign->getLinkedArtifacts($user) as $child_artifact) {
            if ($this->conformance_validator->isArtifactAnExecutionOfCampaign($child_artifact, $campaign)) {
                $executions[] = $child_artifact;
            }
        }

        return $executions;
    }

    private function getExecutionAssignedToFieldValue(PFUser $user, Tracker_Artifact $execution) {
        $assigned_to_field = $this->tracker_form_element_factory
            ->getUsedFieldByNameForUser(
                $execution->getTrackerId(),
                'assigned_to',
                $user
            );

        return $execution->getValue($assigned_to_field)->getValue();
    }

    private function getCampaignFromId($id, PFUser $user) {
        $campaign = $this->tracker_artifact_factory->getArtifactById($id);

        if (! $this->isACampaign($campaign)) {
            throw new RestException(404, 'The campaign does not exist');
        }

        if (! $campaign->userCanView($user)) {
            throw new RestException(403, 'Access denied to this campaign');
        }

        return $campaign;
    }

    private function isACampaign($campaign) {
        return $campaign && $this->conformance_validator->isArtifactACampaign($campaign);
    }

    private function sendPaginationHeaders($limit, $offset, $size) {
        Header::sendPaginationHeaders($limit, $offset, $size, self::MAX_LIMIT);
    }
}