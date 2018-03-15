<?php
/**
 * Copyright (c) Enalean, 2016-2018. All Rights Reserved.
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
use Tuleap\TestManagement\ArtifactFactory;
use Tuleap\TestManagement\Campaign\Campaign;
use Tuleap\TestManagement\Campaign\CampaignRetriever;
use Tuleap\TestManagement\Criterion\ISearchOnMilestone;
use Tuleap\TestManagement\Criterion\ISearchOnStatus;
use Tuleap\TestManagement\PaginatedCampaignsRepresentations;
use UserManager;

class CampaignRepresentationBuilder {

    /**
     * @var UserManager
     */
    private $user_manager;

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

    public function __construct(
        UserManager $user_manager,
        Tracker_FormElementFactory $tracker_form_element_factory,
        ArtifactFactory $testmanagement_artifact_factory,
        CampaignRetriever $campaign_retriever
    ) {
        $this->user_manager                    = $user_manager;
        $this->tracker_form_element_factory    = $tracker_form_element_factory;
        $this->testmanagement_artifact_factory = $testmanagement_artifact_factory;
        $this->campaign_retriever              = $campaign_retriever;
    }

    public function getCampaignRepresentation(PFUser $user, Campaign $campaign)
    {
        $campaign_representation = new CampaignRepresentation();
        $campaign_representation->build(
            $campaign,
            $this->tracker_form_element_factory,
            $user
        );

        return $campaign_representation;
    }

    /**
     * @param PFUser $user
     * @param $campaign_tracker_id
     * @param ISearchOnStatus $status_criterion
     * @param ISearchOnMilestoneId $milestone_criterion
     * @param $limit
     * @param $offset
     * @return PaginatedCampaignsRepresentations
     */
    public function getPaginatedCampaignsRepresentations(
        PFUser $user,
        $campaign_tracker_id,
        ISearchOnStatus $status_criterion,
        ISearchOnMilestone $milestone_criterion,
        $limit,
        $offset
    ) {
        $campaign_representations = array();
        $milestone_id = $milestone_criterion->getMilestoneId();

        if ($status_criterion->shouldRetrieveOnlyClosedCampaigns()) {
            $paginated_campaigns = $this->testmanagement_artifact_factory->getPaginatedClosedArtifactsByTrackerIdUserCanView($user, $campaign_tracker_id, $milestone_id, $limit, $offset);
        } else if ($status_criterion->shouldRetrieveOnlyOpenCampaigns()) {
            $paginated_campaigns = $this->testmanagement_artifact_factory->getPaginatedOpenArtifactsByTrackerIdUserCanView($user, $campaign_tracker_id, $milestone_id, $limit, $offset);
        } else {
            $paginated_campaigns = $this->testmanagement_artifact_factory->getPaginatedArtifactsByTrackerIdUserCanView($user, $campaign_tracker_id, $milestone_id, $limit, $offset, true);
        }

        foreach ($paginated_campaigns->getArtifacts() as $artifact) {
            $campaign = $this->campaign_retriever->getByArtifact($artifact);
            $campaign_representation = new CampaignRepresentation();
            $campaign_representation->build($campaign, $this->tracker_form_element_factory, $user);
            $campaign_representations[] = $campaign_representation;
        }

        $paginated_campaigns_representations = new PaginatedCampaignsRepresentations(
            $campaign_representations,
            $paginated_campaigns->getTotalSize()
        );

        return $paginated_campaigns_representations;
    }
}
