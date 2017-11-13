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

namespace Tuleap\AgileDashboard\REST\v1\Kanban\TrackerReportFilter;

use AgileDashboard_Kanban;
use Luracast\Restler\RestException;
use PFUser;
use Tracker_ReportFactory;
use Tuleap\AgileDashboard\Kanban\TrackerReportFilter\ReportFilterFromWhereBuilder;
use Tuleap\AgileDashboard\REST\v1\Kanban\KanbanBacklogRepresentation;
use Tuleap\AgileDashboard\REST\v1\Kanban\KanbanColumnRepresentation;
use Tuleap\AgileDashboard\REST\v1\Kanban\KanbanItemRepresentation;
use Tuleap\Tracker\REST\v1\ReportArtifactFactory;

class FilteredBacklogRepresentationBuilder
{
    /** @var Tracker_ReportFactory */
    private $report_factory;
    /** @var ReportArtifactFactory */
    private $report_artifact_factory;
    /** @var ReportFilterFromWhereBuilder */
    private $from_where_builder;

    public function __construct(
        Tracker_ReportFactory $report_factory,
        ReportFilterFromWhereBuilder $from_where_builder,
        ReportArtifactFactory $report_artifact_factory
    ) {
        $this->report_factory          = $report_factory;
        $this->report_artifact_factory = $report_artifact_factory;
        $this->from_where_builder      = $from_where_builder;
    }

    /**
     * @param PFUser $user
     * @param AgileDashboard_Kanban $kanban
     * @param int $tracker_report_id
     * @param int $limit
     * @param int $offset
     * @return KanbanBacklogRepresentation
     */
    public function build(
        PFUser $user,
        AgileDashboard_Kanban $kanban,
        $tracker_report_id,
        $limit,
        $offset
    ) {
        $report = $this->report_factory->getReportById($tracker_report_id, $user->getId(), false);
        if ($report === null) {
            throw new RestException(404, "The report was not found");
        }
        if ($report->getTracker()->getId() !== $kanban->getTrackerId()) {
            throw new RestException(400, "The provided report does not belong to the kanban tracker");
        }

        $additional_from_where = $this->from_where_builder->getFromWhereForBacklog($report->getTracker());

        $artifact_collection = $this->report_artifact_factory->getArtifactsMatchingReportWithAdditionalFromWhere(
            $report,
            $additional_from_where,
            $limit,
            $offset
        );

        $collection = array();
        foreach ($artifact_collection->getArtifacts() as $artifact) {
            if (! $artifact->userCanView($user)) {
                continue;
            }

            $item_representation = new KanbanItemRepresentation();
            $item_representation->build(
                $artifact,
                array(),
                KanbanColumnRepresentation::BACKLOG_COLUMN
            );

            $collection[] = $item_representation;
        }

        return new KanbanBacklogRepresentation($collection, $artifact_collection->getTotalSize());
    }
}
