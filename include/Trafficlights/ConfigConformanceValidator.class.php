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

namespace Tuleap\Trafficlights;

use Tracker_Artifact;
use PFUser;

class ConfigConformanceValidator {

    /**
     * @var Config
     */
    private $config;

    public function __construct(Config $config) {
        $this->config = $config;
    }

    /**
     * @return boolean
     */
    public function isArtifactACampaign(Tracker_Artifact $artifact) {
        $tracker = $artifact->getTracker();
        $project = $tracker->getProject();

        $campaign_tracker_id = $this->config->getCampaignTrackerId($project);

        return $campaign_tracker_id === $tracker->getId();
    }

    /**
     * @return boolean
     */
    public function isArtifactAnExecution(Tracker_Artifact $artifact) {
        $tracker = $artifact->getTracker();
        $project = $tracker->getProject();

        $execution_tracker_id = $this->config->getTestExecutionTrackerId($project);

        return $execution_tracker_id === $tracker->getId();
    }

    /**
     * @return boolean
     */
    public function isArtifactADefinition(Tracker_Artifact $artifact) {
        $tracker = $artifact->getTracker();
        $project = $tracker->getProject();

        $definition_tracker_id = $this->config->getTestDefinitionTrackerId($project);

        return $definition_tracker_id === $tracker->getId();
    }
    
    /**
     * @return boolean
     */
    public function isArtifactAnExecutionOfCampaign(PFUser $user, Tracker_Artifact $execution, Tracker_Artifact $campaign) {
        if (! $this->isArtifactACampaign($campaign)) {
            return false;
        }

        if (! $this->isArtifactAnExecution($execution)) {
            return false;
        }

        return $this->areExecutionAndCampaignLinked($user, $execution, $campaign);
    }

    /**
     * @return boolean
     */
    public function isArtifactAnExecutionOfDefinition(Tracker_Artifact $execution, Tracker_Artifact $definition) {
        if (! $this->isArtifactADefinition($definition)) {
            return false;
        }

        if (! $this->isArtifactAnExecution($execution)) {
            return false;
        }

        $definition_project = $definition->getTracker()->getProject();
        $execution_project  = $execution->getTracker()->getProject();

        return $definition_project == $execution_project;
    }

    /**
     * @return boolean
     */
    private function areExecutionAndCampaignLinked(PFUser $user, Tracker_Artifact $execution, Tracker_Artifact $campaign)
    {
        foreach ($campaign->getLinkedArtifacts($user) as $linked_artifact) {
            if ($linked_artifact === $execution) {
                return true;
            }
        }

        foreach ($execution->getLinkedArtifacts($user) as $linked_artifact) {
            if ($linked_artifact === $campaign) {
                return true;
            }
        }

        return false;
    }
}
