<?php
/**
 * Copyright (c) Enalean, 2016-present. All Rights Reserved.
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
use Tracker_FormElementFactory;
use TrackerFactory;
use Tuleap\TestManagement\ArtifactFactory;
use Tuleap\TestManagement\Campaign\Campaign;
use Tuleap\TestManagement\Campaign\CampaignRetriever;
use Tuleap\TestManagement\Campaign\TestExecutionTestStatusDAO;
use Tuleap\TestManagement\Config;
use Tuleap\TestManagement\Criterion\ISearchOnMilestone;
use Tuleap\TestManagement\Criterion\ISearchOnStatus;
use Tuleap\TestManagement\PaginatedCampaignsRepresentations;

class CampaignRepresentationBuilder
{
    /**
     * @var TrackerFactory
     */
    private $tracker_factory;

    /**
     * @var Tracker_FormElementFactory
     */
    private $tracker_form_element_factory;

    /**
     * @var ArtifactFactory
     */
    private $testmanagement_artifact_factory;
    /**
     * @var CampaignRetriever
     */
    private $campaign_retriever;
    /**
     * @var Config
     */
    private $test_management_config;
    /**
     * @var TestExecutionTestStatusDAO
     */
    private $test_execution_test_status_dao;

    public function __construct(
        TrackerFactory $tracker_factory,
        Tracker_FormElementFactory $tracker_form_element_factory,
        ArtifactFactory $testmanagement_artifact_factory,
        CampaignRetriever $campaign_retriever,
        Config $test_management_config,
        TestExecutionTestStatusDAO $test_execution_test_status_dao
    ) {
        $this->tracker_factory                 = $tracker_factory;
        $this->tracker_form_element_factory    = $tracker_form_element_factory;
        $this->testmanagement_artifact_factory = $testmanagement_artifact_factory;
        $this->campaign_retriever              = $campaign_retriever;
        $this->test_management_config          = $test_management_config;
        $this->test_execution_test_status_dao  = $test_execution_test_status_dao;
    }

    public function getCampaignRepresentation(PFUser $user, Campaign $campaign): CampaignRepresentation
    {
        return CampaignRepresentation::build(
            $campaign,
            $this->test_management_config,
            $this->tracker_factory,
            $this->tracker_form_element_factory,
            $this->test_execution_test_status_dao,
            $user
        );
    }

    /**
     * @param $campaign_tracker_id
     * @param $limit
     * @param $offset
     *
     * @return PaginatedCampaignsRepresentations
     */
    public function getPaginatedCampaignsRepresentations(
        PFUser $user,
        int $campaign_tracker_id,
        ISearchOnStatus $status_criterion,
        ISearchOnMilestone $milestone_criterion,
        int $limit,
        int $offset
    ) {
        $campaign_representations = [];
        $milestone_id = (int) $milestone_criterion->getMilestoneId();

        if ($status_criterion->shouldRetrieveOnlyClosedCampaigns()) {
            $paginated_campaigns = $this->testmanagement_artifact_factory->getPaginatedClosedArtifactsByTrackerIdUserCanView($user, $campaign_tracker_id, $milestone_id, $limit, $offset);
        } elseif ($status_criterion->shouldRetrieveOnlyOpenCampaigns()) {
            $paginated_campaigns = $this->testmanagement_artifact_factory->getPaginatedOpenArtifactsByTrackerIdUserCanView($user, $campaign_tracker_id, $milestone_id, $limit, $offset);
        } else {
            $paginated_campaigns = $this->testmanagement_artifact_factory->getPaginatedArtifactsByTrackerIdUserCanView($user, $campaign_tracker_id, $milestone_id, $limit, $offset, true);
        }

        foreach ($paginated_campaigns->getArtifacts() as $artifact) {
            $campaign                   = $this->campaign_retriever->getByArtifact($artifact);
            $campaign_representations[] = $this->getCampaignRepresentation($user, $campaign);
        }

        $paginated_campaigns_representations = new PaginatedCampaignsRepresentations(
            $campaign_representations,
            $paginated_campaigns->getTotalSize()
        );

        return $paginated_campaigns_representations;
    }
}
