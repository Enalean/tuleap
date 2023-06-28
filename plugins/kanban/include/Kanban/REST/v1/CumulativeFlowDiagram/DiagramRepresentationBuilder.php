<?php
/**
 * Copyright (c) Enalean, 2016 - Present. All Rights Reserved.
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

namespace Tuleap\Kanban\REST\v1\CumulativeFlowDiagram;

use Tuleap\Kanban\Kanban;
use DateTime;
use PFUser;
use Tuleap\Kanban\KanbanCumulativeFlowDiagramDao;

final class DiagramRepresentationBuilder
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
        Kanban $kanban,
        PFUser $user,
        DateTime $start_date,
        DateTime $end_date,
        int $interval_between_point,
    ): DiagramRepresentation {
        $dates = $this->column_builder->getDates($start_date, $end_date, $interval_between_point);

        $cumulative_flow_columns_representation = $this->getColumnsRepresentation($kanban, $user, $dates);

        return new DiagramRepresentation($cumulative_flow_columns_representation);
    }

    /**
     * @return DiagramColumnRepresentation[]
     */
    private function getColumnsRepresentation(
        Kanban $kanban,
        PFUser $user,
        array $dates,
    ): array {
        $items_in_columns = $this->kanban_cumulative_flow_diagram_dao->searchKanbanItemsByDates(
            $kanban->getTrackerId(),
            $dates
        );

        return $this->column_builder->build($kanban, $user, $dates, $items_in_columns);
    }
}
