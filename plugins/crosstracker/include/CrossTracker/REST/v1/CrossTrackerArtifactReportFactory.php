<?php
/**
 * Copyright (c) Enalean, 2017. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
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
 *
 */

namespace Tuleap\CrossTracker\REST\v1;

use PFUser;
use Tracker;
use Tuleap\CrossTracker\CrossTrackerArtifactReportDao;

class CrossTrackerArtifactReportFactory
{
    /**
     * @var CrossTrackerArtifactReportDao
     */
    private $artifact_report_dao;
    /**
     * @var \Tracker_ArtifactFactory
     */
    private $artifact_factory;

    public function __construct(
        CrossTrackerArtifactReportDao $artifact_report_dao,
        \Tracker_ArtifactFactory $artifact_factory
    ) {
        $this->artifact_report_dao = $artifact_report_dao;
        $this->artifact_factory    = $artifact_factory;
    }

    /**
     * @param Tracker[] $trackers
     * @param PFUser    $current_user
     * @param           $limit
     * @param           $offset
     *
     * @return PaginatedCollectionOfCrossTrackerArtifacts
     */
    public function getArtifactsFromGivenTrackers(array $trackers, PFUser $current_user, $limit, $offset)
    {
        $artifacts = array();

        if (count($trackers) === 0) {
            return new PaginatedCollectionOfCrossTrackerArtifacts($artifacts, 0);
        }

        $trackers_id = $this->getTrackersId($trackers);

        $result     = $this->artifact_report_dao->searchArtifactsFromTracker($trackers_id, $limit, $offset);
        $total_size = $this->artifact_report_dao->foundRows();
        foreach ($result as $artifact) {
            $artifact = $this->artifact_factory->getArtifactById($artifact['id']);
            if ($artifact->userCanView()) {
                $artifact_representation = new CrossTrackerArtifactReportRepresentation();
                $artifact_representation->build($artifact, $current_user);
                $artifacts[] = $artifact_representation;
            }
        }

        return new PaginatedCollectionOfCrossTrackerArtifacts($artifacts, $total_size);
    }

    private function getTrackersId(array $trackers)
    {
        $id = array();

        foreach ($trackers as $tracker) {
            $id[] = $tracker->getId();
        }

        return $id;
    }
}
