<?php
/**
 * Copyright (c) Enalean, 2017. All Rights Reserved.
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
use Tuleap\TestManagement\ArtifactDao;
use Tuleap\TestManagement\Config;

class RequirementRetriever
{

    /**
     * @var Tracker_ArtifactFactory
     */
    private $tracker_artifact_factory;

    /**
     * @var ArtifactDao
     */
    private $artifact_dao;

    /**
     * @var Config
     */
    private $config;

    public function __construct(
        Tracker_ArtifactFactory $tracker_artifact_factory,
        ArtifactDao $artifact_dao,
        Config $config
    ) {
        $this->tracker_artifact_factory = $tracker_artifact_factory;
        $this->artifact_dao             = $artifact_dao;
        $this->config                   = $config;
    }

    /**
     * @return Tracker_Artifact | null
     */
    public function getRequirementForDefinition(Tracker_Artifact $definition, PFUser $user)
    {
        $requirement_id = $this->artifact_dao->searchFirstRequirementId(
            $definition->getId(),
            $this->config->getTestExecutionTrackerId($definition->getTracker()->getProject())
        );

        return $this->tracker_artifact_factory->getArtifactByIdUserCanView($user, $requirement_id['id']);
    }
}
