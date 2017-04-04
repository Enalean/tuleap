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
    public function getMostRecentlySeenArtifacts(\PFUser $user)
    {
        $recently_visited_rows = $this->dao->searchVisitByUserId($user->getId());
        $artifacts_id = array();
        foreach ($recently_visited_rows as $recently_visited_row) {
            $artifacts_id[] = $recently_visited_row['artifact_id'];
        }

        return $this->artifact_factory->getArtifactsByArtifactIdList($artifacts_id);
    }
}
