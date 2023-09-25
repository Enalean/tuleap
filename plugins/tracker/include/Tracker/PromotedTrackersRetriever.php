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

namespace Tuleap\Tracker;

use Tracker;

final class PromotedTrackersRetriever implements RetrievePromotedTrackers
{
    public function __construct(
        private readonly PromotedTrackerDao $dao,
        private readonly \TrackerFactory $tracker_factory,
    ) {
    }

    /**
     * @return Tracker[]
     */
    public function getTrackers(\PFUser $current_user, \Project $project): array
    {
        $trackers = [];
        foreach ($this->dao->searchByProjectId((int) $project->getID()) as $row) {
            $tracker = $this->tracker_factory->getInstanceFromRow($row);
            if ($tracker->userCanSubmitArtifact($current_user)) {
                $trackers[] = $tracker;
            }
        }

        return $trackers;
    }
}
