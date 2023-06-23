<?php
/**
 * Copyright (c) Enalean, 2017 - Present. All Rights Reserved.
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

namespace Tuleap\Kanban\REST\v1\TrackerReport;

use PFUser;
use Tracker_Report;
use Tuleap\AgileDashboard\Kanban\ColumnIdentifier;
use Tuleap\AgileDashboard\Kanban\TrackerReport\ReportFilterFromWhereBuilder;
use Tuleap\Kanban\REST\v1\ItemCollectionRepresentation;
use Tuleap\Kanban\REST\v1\ItemRepresentationBuilder;
use Tuleap\Kanban\REST\v1\KanbanItemRepresentation;
use Tuleap\Tracker\REST\v1\ArtifactMatchingReportCollection;
use Tuleap\Tracker\REST\v1\ReportArtifactFactory;

final class FilteredItemCollectionRepresentationBuilder
{
    public function __construct(
        private readonly ReportFilterFromWhereBuilder $from_where_builder,
        private readonly ReportArtifactFactory $report_artifact_factory,
        private readonly ItemRepresentationBuilder $item_representation_builder,
    ) {
    }

    public function build(
        ColumnIdentifier $column_identifier,
        PFUser $user,
        Tracker_Report $report,
        int $limit,
        int $offset,
    ): ItemCollectionRepresentation {
        $additional_from_where = $this->from_where_builder->getFromWhere($report->getTracker(), $column_identifier);

        $artifact_collection = $this->report_artifact_factory->getRankedArtifactsMatchingReportWithAdditionalFromWhere(
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

    /**
     * @return KanbanItemRepresentation[]
     */
    private function getItemCollectionFromArtifactCollection(
        ColumnIdentifier $column_identifier,
        PFUser $user,
        ArtifactMatchingReportCollection $artifact_collection,
    ): array {
        $item_collection = [];
        foreach ($artifact_collection->getArtifacts() as $artifact) {
            if (! $artifact->userCanView($user)) {
                continue;
            }

            $item_representation = $this->item_representation_builder->buildItemRepresentationInColumn(
                $column_identifier,
                $artifact
            );

            $item_collection[] = $item_representation;
        }

        return $item_collection;
    }
}
