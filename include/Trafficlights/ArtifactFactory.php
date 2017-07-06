<?php
/**
 * Copyright (c) Enalean, 2016. All Rights Reserved.
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

namespace Tuleap\Trafficlights;

use Project;
use PFUser;
use Tracker_Artifact_PaginatedArtifacts;
use Tracker_ArtifactFactory;
use Tracker_Artifact;
use Tuleap\Trafficlights\Nature\NatureCoveredByPresenter;

class ArtifactFactory
{

    /**
     * @var Config
     */
    private $config;

    /**
     * @var ConfigConformanceValidator
     */
    private $conformance_validator;

    /** @var Tracker_ArtifactFactory */
    private $tracker_artifact_factory;

    /** @var ArtifactDao */
    private $dao;

    public function __construct(
        Config $config,
        ConfigConformanceValidator $conformance_validator,
        Tracker_ArtifactFactory $tracker_artifact_factory,
        ArtifactDao $dao
    ) {
        $this->config                   = $config;
        $this->conformance_validator    = $conformance_validator;
        $this->tracker_artifact_factory = $tracker_artifact_factory;
        $this->dao                      = $dao;
    }

    public function getArtifactById($id)
    {
        return $this->tracker_artifact_factory->getArtifactById($id);
    }

    public function getArtifactByIdUserCanView(PFUser $user, $id)
    {
        return $this->tracker_artifact_factory->getArtifactByIdUserCanView($user, $id);
    }

    /**
     * Given a list of artifact ids, return corresponding artifact objects if any
     *
     * @param array $artifact_ids
     *
     * @return array of Tracker_Artifact
     */
    public function getArtifactsByIdListUserCanView(PFUser $user, array $artifact_ids) {
        $artifacts = array();
        foreach ($artifact_ids as $artifact_id) {
            $artifact = $this->getArtifactById($artifact_id);

            if ($artifact && $artifact->userCanView($user)) {
                $artifacts[$artifact_id] = $artifact;
            }
        }
        return $artifacts;
    }

    /**
     * @param PFUser  $user           The user for which we're retrieving the campaign
     * @param int     $tracker_id     The id of the tracker
     * @param int     $milestone_id   The id of the milestone that should be linked by the campaigns
     * @param int     $limit          The maximum number of artifacts returned
     * @param int     $offset
     *
     * @return Tracker_Artifact_PaginatedArtifacts
     */
    public function getPaginatedOpenArtifactsByTrackerIdUserCanView(PFUser $user, $tracker_id, $milestone_id, $limit, $offset)
    {
        $artifacts = array();
        foreach ($this->dao->searchPaginatedOpenByTrackerId($tracker_id, $milestone_id, $limit, $offset) as $row) {
            $artifact = $this->tracker_artifact_factory->getInstanceFromRow($row);
            if ($artifact->userCanView($user)) {
                $artifacts[$row['id']] = $artifact;
            }
        }

        $size = (int) $this->dao->foundRows();

        return new Tracker_Artifact_PaginatedArtifacts($artifacts, $size);
    }

    /**
     * @param PFUser  $user           The user for which we're retrieving the campaign
     * @param int     $tracker_id     The id of the tracker
     * @param int     $milestone_id   The id of the milestone that should be linked by the campaigns
     * @param int     $limit          The maximum number of artifacts returned
     * @param int     $offset
     *
     * @return Tracker_Artifact_PaginatedArtifacts
     */
    public function getPaginatedClosedArtifactsByTrackerIdUserCanView(PFUser $user, $tracker_id, $milestone_id, $limit, $offset)
    {
        $artifacts = array();
        foreach ($this->dao->searchPaginatedClosedByTrackerId($tracker_id, $milestone_id, $limit, $offset) as $row) {
            $artifact = $this->tracker_artifact_factory->getInstanceFromRow($row);
            if ($artifact->userCanView($user)) {
                $artifacts[$row['id']] = $artifact;
            }
        }
        $size = (int) $this->dao->foundRows();

        return new Tracker_Artifact_PaginatedArtifacts($artifacts, $size);
    }

    /**
     * @param PFUser  $user           The user for which we're retrieving the campaign
     * @param int     $tracker_id     The id of the tracker
     * @param int     $milestone_id   The id of the milestone that should be linked by the campaigns
     * @param int     $limit          The maximum number of artifacts returned
     * @param int     $offset
     * @param bool    $reverse_order  Should the order of the returned campaigns be reversed?
     *
     * @return Tracker_Artifact_PaginatedArtifacts
     */
    public function getPaginatedArtifactsByTrackerIdUserCanView(PFUser $user, $tracker_id, $milestone_id, $limit, $offset, $reverse_order)
    {
        $artifacts = array();
        foreach ($this->dao->searchPaginatedByTrackerId($tracker_id, $milestone_id, $limit, $offset, $reverse_order) as $row) {
            $artifact = $this->tracker_artifact_factory->getInstanceFromRow($row);
            if ($artifact->userCanView($user)) {
                $artifacts[$row['id']] = $artifact;
            }
        }

        $size = (int) $this->dao->foundRows();

        return new Tracker_Artifact_PaginatedArtifacts($artifacts, $size);
    }

    /**
     * @param PFUser            $user       The user for which we're retrieving the campaign
     * @param Tracker_Artifact  $execution  The execution whose campaign we're retrieving
     *
     * @return Tracker_Artifact
     */
    public function getCampaignForExecution(PFUser $user, Tracker_Artifact $execution)
    {
        $campaign_tracker_id = $this->config->getCampaignTrackerId($execution->getTracker()->getProject());
        $campaigns = $this->tracker_artifact_factory->getArtifactsByTrackerId($campaign_tracker_id);

        foreach ($campaigns as $campaign) {
            if ($this->conformance_validator->isArtifactAnExecutionOfCampaign($user, $execution, $campaign)) {
                return $campaign;
            }
        }

        return null;
    }

    public function getCoverTestDefinitionsUserCanViewForMilestone(PFUser $user, Project $project, $milestone_id)
    {
        $artifacts            = array();
        $test_def_tracker_id  = $this->config->getTestDefinitionTrackerId($project);

        $results = $this->dao->searchPaginatedLinkedArtifactsByLinkNatureAndTrackerId(
            $milestone_id,
            NatureCoveredByPresenter::NATURE_COVERED_BY,
            $test_def_tracker_id,
            PHP_INT_MAX,
            0
        );

        foreach ($results as $row) {
            $artifact = $this->tracker_artifact_factory->getInstanceFromRow($row);
            if ($artifact->userCanView($user)) {
                $artifacts[] = $artifact;
            }
        }
        return $artifacts;
    }
}
