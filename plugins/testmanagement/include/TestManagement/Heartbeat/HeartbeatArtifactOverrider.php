<?php
/**
 * Copyright (c) Enalean, 2020 - Present. All Rights Reserved.
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

declare(strict_types=1);

namespace Tuleap\TestManagement\Heartbeat;

use Tuleap\TestManagement\Config;
use Tuleap\Tracker\Artifact\Heartbeat\OverrideArtifactsInFavourOfAnOther;

class HeartbeatArtifactOverrider
{
    public function overrideArtifacts(Config $config, OverrideArtifactsInFavourOfAnOther $event): void
    {
        $artifacts            = $event->getArtifacts();
        $project              = $event->getProject();
        $campaign_tracker_id  = (int) $config->getCampaignTrackerId($project);
        $execution_tracker_id = (int) $config->getTestExecutionTrackerId($project);

        $overridden_artifacts = [];

        foreach ($artifacts as $artifact) {
            $tracker_id = (int) $artifact->getTrackerId();
            if ($tracker_id !== $execution_tracker_id && $tracker_id !== $campaign_tracker_id) {
                $overridden_artifacts[$artifact->getId()] = $artifact;
            }
        }

        $event->overrideArtifacts($overridden_artifacts);
    }
}
