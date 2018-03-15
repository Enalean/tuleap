<?php
/**
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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

namespace Tuleap\TestManagement\Campaign;

use Tracker_Artifact;
use Tracker_ArtifactFactory;

class CampaignRetriever
{
    /**
     * @var CampaignDao
     */
    private $campaign_dao;
    /**
     * @var Tracker_ArtifactFactory
     */
    private $artifact_factory;

    public function __construct(Tracker_ArtifactFactory $artifact_factory, CampaignDao $campaign_dao)
    {
        $this->campaign_dao     = $campaign_dao;
        $this->artifact_factory = $artifact_factory;
    }

    /**
     * @param int s$id
     *
     * @return Campaign
     * @throws ArtifactNotFoundException
     */
    public function getById($id)
    {
        $artifact = $this->artifact_factory->getArtifactById($id);
        if (! $artifact) {
            throw new ArtifactNotFoundException();
        }

        return $this->getByArtifact($artifact);
    }

    /**
     *
     * @param Tracker_Artifact $artifact
     *
     * @return Campaign
     */
    public function getByArtifact(Tracker_Artifact $artifact)
    {
        $configuration = $this->campaign_dao->searchByCampaignId($artifact->getId());

        if ($configuration) {
            $job = new JobConfiguration($configuration['job_url']);
        } else {
            $job = new NoJobConfiguration();
        }

        return new Campaign($artifact, $artifact->getTitle(), $job);
    }
}
