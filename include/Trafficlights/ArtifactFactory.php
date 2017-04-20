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

use PFUser;
use Tracker_Artifact_PaginatedArtifacts;
use Tracker_ArtifactFactory;
use Tracker_Artifact;

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

    public function getPaginatedOpenArtifactsByTrackerIdUserCanView(PFUser $user, $tracker_id, $limit, $offset)
    {
        $artifacts = array();
        foreach ($this->dao->searchPaginatedOpenByTrackerId($tracker_id, $limit, $offset) as $row) {
            $artifact = $this->tracker_artifact_factory->getInstanceFromRow($row);
            if ($artifact->userCanView($user)) {
                $artifacts[$row['id']] = $artifact;
            }
        }

        $size = (int) $this->dao->foundRows();

        return new Tracker_Artifact_PaginatedArtifacts($artifacts, $size);
    }

    public function getPaginatedClosedArtifactsByTrackerIdUserCanView(PFUser $user, $tracker_id, $limit, $offset)
    {
        $artifacts = array();
        foreach ($this->dao->searchPaginatedClosedByTrackerId($tracker_id, $limit, $offset) as $row) {
            $artifact = $this->tracker_artifact_factory->getInstanceFromRow($row);
            if ($artifact->userCanView($user)) {
                $artifacts[$row['id']] = $artifact;
            }
        }
        $size = (int) $this->dao->foundRows();

        return new Tracker_Artifact_PaginatedArtifacts($artifacts, $size);
    }

    /**
     * @param int $tracker_id The id of the tracker
     * @param int $limit      The maximum number of artifacts returned
     * @param int $offset
     *
     * @return Tracker_Artifact_PaginatedArtifacts
     */
    public function getPaginatedArtifactsByTrackerId($tracker_id, $limit, $offset, $reverse_order)
    {
        return $this->tracker_artifact_factory->getPaginatedArtifactsByTrackerId($tracker_id, $limit, $offset, $reverse_order);
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
}
