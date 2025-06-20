<?php
/**
 * Copyright (c) Enalean, 2021 - present. All Rights Reserved.
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

namespace Tuleap\Tracker\Semantic\Timeframe;

class SemanticTimeframeSuitableTrackersOtherSemanticsCanBeImpliedFromRetriever
{
    private SemanticTimeframeDao $dao;
    private \TrackerFactory $tracker_factory;
    private \Tracker_FormElementFactory $form_element_factory;

    public function __construct(
        SemanticTimeframeDao $dao,
        \TrackerFactory $tracker_factory,
        \Tracker_FormElementFactory $form_element_factory,
    ) {
        $this->dao                  = $dao;
        $this->tracker_factory      = $tracker_factory;
        $this->form_element_factory = $form_element_factory;
    }

    /**
     * @return \Tuleap\Tracker\Tracker[]
     */
    public function getTrackersWeCanUseToImplyTheSemanticOfTheCurrentTrackerFrom(\Tuleap\Tracker\Tracker $current_tracker): array
    {
        $project_trackers  = $this->tracker_factory->getTrackersByGroupId((int) $current_tracker->getGroupId());
        $suitable_trackers = [];

        foreach ($project_trackers as $project_tracker) {
            if (
                $project_tracker->getId() === $current_tracker->getId() ||
                ! $this->isTrackerEligible($project_tracker)
            ) {
                continue;
            }
            $suitable_trackers[$project_tracker->getId()] = $project_tracker;
        }
        return $suitable_trackers;
    }

    private function isTrackerEligible(\Tuleap\Tracker\Tracker $project_tracker): bool
    {
        if (! $this->hasTrackerAnArtifactLinkField($project_tracker)) {
            return false;
        }

        $config = $this->dao->searchByTrackerId($project_tracker->getId());
        if (! $config) {
            return false;
        }
        if ($config['implied_from_tracker_id'] !== null) {
            return false;
        }
        return true;
    }

    private function hasTrackerAnArtifactLinkField(\Tuleap\Tracker\Tracker $tracker): bool
    {
        $artifact_link_field = $this->form_element_factory->getUsedArtifactLinkFields($tracker);
        return ! empty($artifact_link_field);
    }
}
