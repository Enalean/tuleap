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
use Tuleap\CrossTracker\CrossTrackerReport;
use Tuleap\CrossTracker\Report\Query\Advanced\ExpertQueryValidator;

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

    /** @var ExpertQueryValidator */
    private $expert_query_validator;

    public function __construct(
        CrossTrackerArtifactReportDao $artifact_report_dao,
        \Tracker_ArtifactFactory $artifact_factory,
        ExpertQueryValidator $expert_query_validator
    ) {
        $this->artifact_report_dao    = $artifact_report_dao;
        $this->artifact_factory       = $artifact_factory;
        $this->expert_query_validator = $expert_query_validator;
    }

    /**
     * @param CrossTrackerReport $report
     * @param PFUser $current_user
     * @param int $limit
     * @param int $offset
     *
     * @return PaginatedCollectionOfCrossTrackerArtifacts
     */
    public function getArtifactsMatchingReport(
        CrossTrackerReport $report,
        PFUser $current_user,
        $limit,
        $offset
    ) {
        if ($report->getExpertQuery() === "") {
            return $this->getArtifactsFromGivenTrackers(
                $report->getTrackers(),
                $current_user,
                $limit,
                $offset
            );
        } else {
            return $this->getArtifactsMatchingExpertQuery(
                $report
            );
        }
    }

    /**
     * @param Tracker[] $trackers
     * @param PFUser    $current_user
     * @param           $limit
     * @param           $offset
     *
     * @return PaginatedCollectionOfCrossTrackerArtifacts
     */
    private function getArtifactsFromGivenTrackers(array $trackers, PFUser $current_user, $limit, $offset)
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

    /**
     * @param CrossTrackerReport $report
     *
     * @return PaginatedCollectionOfCrossTrackerArtifacts
     *
     */
    private function getArtifactsMatchingExpertQuery(
        CrossTrackerReport $report
    ) {
        $this->expert_query_validator->validateExpertQuery($report);

        return new PaginatedCollectionOfCrossTrackerArtifacts(array(), 0);
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
