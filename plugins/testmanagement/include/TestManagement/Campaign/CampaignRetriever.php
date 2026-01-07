<?php
/**
 * Copyright (c) Enalean, 2018 - Present. All Rights Reserved.
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

use Tracker_ArtifactFactory;
use Tuleap\Cryptography\ConcealedString;
use Tuleap\Tracker\Artifact\Artifact;

readonly class CampaignRetriever
{
    public function __construct(
        private Tracker_ArtifactFactory $artifact_factory,
        private CampaignDao $campaign_dao,
    ) {
    }

    /**
     * @throws ArtifactNotFoundException
     */
    public function getById(int $id): Campaign
    {
        $artifact = $this->artifact_factory->getArtifactById($id);
        if (! $artifact) {
            throw new ArtifactNotFoundException();
        }

        return $this->getByArtifact($artifact);
    }

    public function getByArtifact(Artifact $artifact): Campaign
    {
        $configuration = $this->campaign_dao->searchByCampaignId($artifact->getId());

        if ($configuration) {
            $job = new JobConfiguration(
                $configuration['job_url'],
                $configuration['job_token'] ?? new ConcealedString('')
            );
        } else {
            $job = new NoJobConfiguration();
        }

        return new Campaign($artifact, $artifact->getTitle() ?? '', $job);
    }
}
