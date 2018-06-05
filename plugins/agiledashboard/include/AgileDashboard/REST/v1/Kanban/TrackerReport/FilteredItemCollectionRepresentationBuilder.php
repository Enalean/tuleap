<?php
/**
 * Copyright (c) Enalean, 2017 - 2018. All Rights Reserved.
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

namespace Tuleap\AgileDashboard\REST\v1\Kanban\TrackerReport;

use PFUser;
use Tracker_Artifact_PriorityDao;
use Tracker_Report;
use Tuleap\AgileDashboard\Kanban\ColumnIdentifier;
use Tuleap\AgileDashboard\Kanban\TrackerReport\ReportFilterFromWhereBuilder;
use Tuleap\AgileDashboard\REST\v1\Kanban\ItemCollectionRepresentation;
use Tuleap\AgileDashboard\REST\v1\Kanban\ItemRepresentationBuilder;
use Tuleap\Tracker\REST\v1\ArtifactMatchingReportCollection;
use Tuleap\Tracker\REST\v1\ReportArtifactFactory;

class FilteredItemCollectionRepresentationBuilder
{
    /** @var ReportArtifactFactory */
    private $report_artifact_factory;
    /** @var ReportFilterFromWhereBuilder */
    private $from_where_builder;
    /** @var Tracker_Artifact_PriorityDao */
    private $priority_dao;
    /** @var ItemRepresentationBuilder */
    private $item_representation_builder;

    public function __construct(
        ReportFilterFromWhereBuilder $from_where_builder,
        ReportArtifactFactory $report_artifact_factory,
        Tracker_Artifact_PriorityDao $priority_dao,
        ItemRepresentationBuilder $item_representation_builder
    ) {
        $this->report_artifact_factory     = $report_artifact_factory;
        $this->from_where_builder          = $from_where_builder;
        $this->priority_dao                = $priority_dao;
        $this->item_representation_builder = $item_representation_builder;
    }

    public function build(
        ColumnIdentifier $column_identifier,
        PFUser $user,
        Tracker_Report $report,
        $limit,
        $offset
    ) {
        $additional_from_where = $this->from_where_builder->getFromWhere($report->getTracker(), $column_identifier);

        $artifact_collection = $this->report_artifact_factory->getArtifactsMatchingReportWithAdditionalFromWhere(
            $report,
            $additional_from_where,
            $limit,
            $offset
        );

        $item_collection = $this->getItemCollectionFromArtifactCollection(
            $column_identifier,
            $user,
            $artifact_collection
        );

        return new ItemCollectionRepresentation($item_collection, $artifact_collection->getTotalSize());
    }

    private function getItemCollectionFromArtifactCollection(
        ColumnIdentifier $column_identifier,
        PFUser $user,
        ArtifactMatchingReportCollection $artifact_collection
    ) {
        $item_collection = [];
        $artifact_ids    = [];
        foreach ($artifact_collection->getArtifacts() as $artifact) {
            if (! $artifact->userCanView($user)) {
                continue;
            }

            $item_representation = $this->item_representation_builder->buildItemRepresentationInColumn(
                $column_identifier,
                $artifact
            );

            $id                   = $artifact->getId();
            $artifact_ids[]       = $id;
            $item_collection[$id] = $item_representation;
        }

        if (! $item_collection) {
            return $item_collection;
        }

        return $this->sort($item_collection, $artifact_ids);
    }

    private function sort(array $item_collection, array $artifact_ids)
    {
        $rank = [];
        foreach ($this->priority_dao->getGlobalRanks($artifact_ids) as $row) {
            $rank[$row['rank']] = $row['artifact_id'];
        }
        ksort($rank);

        $sorted_collection = [];
        foreach ($rank as $artifact_id) {
            $sorted_collection[] = $item_collection[$artifact_id];
        }

        return $sorted_collection;
    }
}
