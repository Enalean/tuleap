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

namespace Tuleap\Tracker\RecentlyVisited;

use Tuleap\User\History\HistoryEntry;
use Tuleap\User\History\HistoryQuickLink;

class VisitRetriever
{
    /**
     * @var RecentlyVisitedDao
     */
    private $dao;
    /**
     * @var \Tracker_ArtifactFactory
     */
    private $artifact_factory;

    public function __construct(RecentlyVisitedDao $dao, \Tracker_ArtifactFactory $artifact_factory)
    {
        $this->dao              = $dao;
        $this->artifact_factory = $artifact_factory;
    }

    /**
     * @return \Tracker_Artifact[]
     * @throws \DataAccessException
     */
    public function getMostRecentlySeenArtifacts(\PFUser $user, $nb_maximum_artifacts)
    {
        $recently_visited_rows = $this->dao->searchVisitByUserId($user->getId(), $nb_maximum_artifacts);
        $artifacts_id = array();
        foreach ($recently_visited_rows as $recently_visited_row) {
            $artifacts_id[] = $recently_visited_row['artifact_id'];
        }

        return $this->artifact_factory->getArtifactsByArtifactIdList($artifacts_id);
    }

    /**
     * @return HistoryEntry[]
     * @throws \DataAccessException
     */
    public function getVisitHistory(\PFUser $user, $max_length_history)
    {
        $history               = array();
        $recently_visited_rows = $this->dao->searchVisitByUserId($user->getId(), $max_length_history);
        foreach ($recently_visited_rows as $recently_visited_row) {
            $artifact = $this->artifact_factory->getArtifactById($recently_visited_row['artifact_id']);
            if ($artifact === null) {
                continue;
            }

            $tracker     = $artifact->getTracker();
            $quick_links = array(
                new HistoryQuickLink(
                    sprintf(dgettext('tuleap-tracker', 'See all %s'), $tracker->getItemName()),
                    $tracker->getUri()
                )
            );
            $history[]   = new HistoryEntry(
                $recently_visited_row['created_on'],
                $artifact->getXRef(),
                $artifact->getUri(),
                $artifact->getTitle(),
                $tracker->getColor(),
                TRACKER_SERVICE_ICON,
                $artifact->getTracker()->getProject(),
                $quick_links
            );
        }

        return $history;
    }
}
