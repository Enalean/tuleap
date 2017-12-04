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

namespace Tuleap\AgileDashboard\REST\v1\Kanban\TrackerReport;

use PFUser;
use Tracker_Report;
use Tuleap\AgileDashboard\Kanban\ColumnIdentifier;
use Tuleap\AgileDashboard\Kanban\TrackerReport\ReportFilterFromWhereBuilder;
use Tuleap\AgileDashboard\REST\v1\Kanban\ItemCollectionRepresentation;
use Tuleap\AgileDashboard\REST\v1\Kanban\KanbanItemRepresentation;
use Tuleap\AgileDashboard\REST\v1\Kanban\TimeInfoFactory;
use Tuleap\Tracker\REST\v1\ReportArtifactFactory;

class FilteredItemCollectionRepresentationBuilder
{
    /** @var ReportArtifactFactory */
    private $report_artifact_factory;
    /** @var ReportFilterFromWhereBuilder */
    private $from_where_builder;
    /** @var TimeInfoFactory */
    private $time_info_factory;

    public function __construct(
        ReportFilterFromWhereBuilder $from_where_builder,
        ReportArtifactFactory $report_artifact_factory,
        TimeInfoFactory $time_info_factory
    ) {
        $this->report_artifact_factory = $report_artifact_factory;
        $this->from_where_builder      = $from_where_builder;
        $this->time_info_factory       = $time_info_factory;
    }

    public function build(
        ColumnIdentifier $column_identifier,
        PFUser $user,
        Tracker_Report $report,
        $limit,
        $offset
    ) {
        $additional_from_where = $this->from_where_builder->getFromWhere($report->getTracker(), $column_identifier);

        $collection          = array();
        $artifact_collection = $this->report_artifact_factory->getArtifactsMatchingReportWithAdditionalFromWhere(
            $report,
            $additional_from_where,
            $limit,
            $offset
        );

        foreach ($artifact_collection->getArtifacts() as $artifact) {
            if (! $artifact->userCanView($user)) {
                continue;
            }

            $time_info = $column_identifier->isBacklog() ? array() : $this->time_info_factory->getTimeInfo($artifact);

            $item_representation = new KanbanItemRepresentation();
            $item_representation->build(
                $artifact,
                $time_info,
                $column_identifier->getColumnId()
            );

            $collection[] = $item_representation;
        }

        return new ItemCollectionRepresentation($collection, $artifact_collection->getTotalSize());
    }
}
