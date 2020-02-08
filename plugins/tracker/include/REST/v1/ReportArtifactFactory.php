<?php
/**
 * Copyright (c) Enalean, 2017-Present. All Rights Reserved.
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

namespace Tuleap\Tracker\REST\v1;

use Tracker_Report;
use Tuleap\Tracker\Report\Query\IProvideFromAndWhereSQLFragments;

class ReportArtifactFactory
{
    private $tracker_artifact_factory;

    public function __construct(\Tracker_ArtifactFactory $tracker_artifact_factory)
    {
        $this->tracker_artifact_factory = $tracker_artifact_factory;
    }

    /**
     * @param int $limit
     * @param int $offset
     * @return ArtifactMatchingReportCollection
     */
    public function getArtifactsMatchingReportWithAdditionalFromWhere(
        Tracker_Report $report,
        IProvideFromAndWhereSQLFragments $additional_from_where,
        $limit,
        $offset
    ) {
        $matching_ids = $report->getMatchingIdsWithAdditionalFromWhere($additional_from_where);

        return $this->getPaginatedArtifactCollection($limit, $offset, $matching_ids);
    }

    /**
     * @param int $limit
     * @param int $offset
     * @return ArtifactMatchingReportCollection
     */
    public function getArtifactsMatchingReport(
        Tracker_Report $report,
        $limit,
        $offset
    ) {
        $matching_ids = $report->getMatchingIds();

        return $this->getPaginatedArtifactCollection($limit, $offset, $matching_ids);
    }

    private function getPaginatedArtifactCollection($limit, $offset, $matching_ids)
    {
        if (! isset($matching_ids['id']) || ! $matching_ids['id']) {
            return new ArtifactMatchingReportCollection(
                array(),
                0
            );
        }

        $matching_artifact_ids = explode(',', $matching_ids['id']);
        $total_size            = count($matching_artifact_ids);
        $slice_matching_ids    = array_slice($matching_artifact_ids, $offset, $limit);

        $artifacts = $this->tracker_artifact_factory->getArtifactsByArtifactIdList($slice_matching_ids);

        return new ArtifactMatchingReportCollection(
            array_filter($artifacts),
            $total_size
        );
    }
}
