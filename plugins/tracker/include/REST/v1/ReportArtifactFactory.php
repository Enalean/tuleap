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

declare(strict_types=1);

namespace Tuleap\Tracker\REST\v1;

use Tracker_Report;
use Tuleap\Tracker\Report\Query\IProvideFromAndWhereSQLFragments;
use Tuleap\Tracker\REST\v1\Report\MatchingIdsOrderer;

class ReportArtifactFactory
{
    public function __construct(
        private readonly \Tracker_ArtifactFactory $tracker_artifact_factory,
        private readonly MatchingIdsOrderer $matching_ids_orderer,
    ) {
    }

    /**
     * @param int $limit
     * @param int $offset
     * @return ArtifactMatchingReportCollection
     */
    public function getRankedArtifactsMatchingReportWithAdditionalFromWhere(
        Tracker_Report $report,
        IProvideFromAndWhereSQLFragments $additional_from_where,
        $limit,
        $offset,
    ) {
        $matching_ids        = $report->getMatchingIdsWithAdditionalFromWhere($additional_from_where);
        $ranked_matching_ids = $this->matching_ids_orderer->orderMatchingIdsByGlobalRank($matching_ids);

        $collection = $this->getPaginatedArtifactCollection($limit, $offset, $ranked_matching_ids);
        return $this->sortPaginatedArtifactCollectionByRank(
            $collection,
            $limit,
            $offset,
            $ranked_matching_ids,
        );
    }

    /**
     * @param int $limit
     * @param int $offset
     * @return ArtifactMatchingReportCollection
     */
    public function getArtifactsMatchingReport(
        Tracker_Report $report,
        $limit,
        $offset,
    ) {
        $matching_ids = $report->getMatchingIds();

        return $this->getPaginatedArtifactCollection($limit, $offset, $matching_ids);
    }

    private function getPaginatedArtifactCollection(int $limit, int $offset, array $matching_ids): ArtifactMatchingReportCollection
    {
        if (! isset($matching_ids['id']) || ! $matching_ids['id']) {
            return new ArtifactMatchingReportCollection(
                [],
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

    private function sortPaginatedArtifactCollectionByRank(
        ArtifactMatchingReportCollection $collection,
        int $limit,
        int $offset,
        array $ranked_matching_ids,
    ): ArtifactMatchingReportCollection {
        if ($collection->getTotalSize() === 0) {
            return $collection;
        }

        $matching_artifact_ids = explode(',', $ranked_matching_ids['id']);
        $slice_matching_ids    = array_slice($matching_artifact_ids, $offset, $limit);

        $artifacts_map = [];
        foreach ($collection->getArtifacts() as $artifact) {
            $artifacts_map[$artifact->getId()] = $artifact;
        }

        $ranked_artifacts = [];
        foreach ($slice_matching_ids as $slice_ranked_matching_id) {
            if (isset($artifacts_map[(int) $slice_ranked_matching_id])) {
                $ranked_artifacts[] = $artifacts_map[(int) $slice_ranked_matching_id];
            }
        }

        return new ArtifactMatchingReportCollection(
            array_filter($ranked_artifacts),
            $collection->getTotalSize(),
        );
    }
}
