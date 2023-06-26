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

namespace Tuleap\Kanban\REST\v1\TrackerReport;

use AgileDashboard_Kanban;
use DateTime;
use PFUser;
use Tracker_Report;
use Tuleap\Kanban\KanbanCumulativeFlowDiagramDao;
use Tuleap\Kanban\REST\v1\CumulativeFlowDiagram\DiagramColumnRepresentation;
use Tuleap\Kanban\REST\v1\CumulativeFlowDiagram\DiagramRepresentation;
use Tuleap\Kanban\REST\v1\CumulativeFlowDiagram\OrderedColumnRepresentationsBuilder;
use Tuleap\Kanban\REST\v1\CumulativeFlowDiagram\TooManyPointsException;

final class FilteredDiagramRepresentationBuilder
{
    public function __construct(
        private readonly KanbanCumulativeFlowDiagramDao $kanban_cumulative_flow_diagram_dao,
        private readonly OrderedColumnRepresentationsBuilder $column_builder,
    ) {
    }

    /**
     * @throws TooManyPointsException
     */
    public function build(
        AgileDashboard_Kanban $kanban,
        PFUser $user,
        DateTime $start_date,
        DateTime $end_date,
        int $interval_between_point,
        Tracker_Report $report,
    ): DiagramRepresentation {
        $dates = $this->column_builder->getDates($start_date, $end_date, $interval_between_point);

        $cumulative_flow_columns_representation = $this->getFilteredColumnsRepresentation(
            $kanban,
            $user,
            $dates,
            $report
        );

        return new DiagramRepresentation($cumulative_flow_columns_representation);
    }

    /**
     * @return DiagramColumnRepresentation[]
     */
    private function getFilteredColumnsRepresentation(
        AgileDashboard_Kanban $kanban,
        PFUser $user,
        array $dates,
        Tracker_Report $report,
    ): array {
        $matching_ids = $report->getMatchingIds();
        if (! $matching_ids['id']) {
            return $this->column_builder->build($kanban, $user, $dates, []);
        }

        $matching_artifact_ids = explode(',', $matching_ids['id']);

        $items_in_columns = $this->kanban_cumulative_flow_diagram_dao->searchKanbanItemsByDatesWithArtifactIds(
            $kanban->getTrackerId(),
            $matching_artifact_ids,
            $dates
        );

        return $this->column_builder->build($kanban, $user, $dates, $items_in_columns);
    }
}
