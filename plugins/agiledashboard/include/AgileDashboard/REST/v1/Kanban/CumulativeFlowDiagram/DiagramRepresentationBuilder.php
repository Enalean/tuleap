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

namespace Tuleap\AgileDashboard\REST\v1\Kanban\CumulativeFlowDiagram;

use AgileDashboard_Kanban;
use DataAccessException;
use DateTime;
use PFUser;
use Tuleap\AgileDashboard\KanbanCumulativeFlowDiagramDao;

class DiagramRepresentationBuilder
{
    /** @var KanbanCumulativeFlowDiagramDao */
    private $kanban_cumulative_flow_diagram_dao;
    /** @var OrderedColumnRepresentationsBuilder */
    private $column_builder;

    public function __construct(
        KanbanCumulativeFlowDiagramDao $kanban_cumulative_flow_diagram_dao,
        OrderedColumnRepresentationsBuilder $column_builder
    ) {
        $this->kanban_cumulative_flow_diagram_dao = $kanban_cumulative_flow_diagram_dao;
        $this->column_builder                     = $column_builder;
    }

    /**
     * @return DiagramRepresentation
     * @throws DataAccessException
     * @throws TooManyPointsException
     */
    public function build(
        AgileDashboard_Kanban $kanban,
        PFUser $user,
        DateTime $start_date,
        DateTime $end_date,
        $interval_between_point
    ) {
        $dates = $this->column_builder->getDates($start_date, $end_date, $interval_between_point);

        $cumulative_flow_columns_representation = $this->getColumnsRepresentation($kanban, $user, $dates);

        return new DiagramRepresentation($cumulative_flow_columns_representation);
    }

    /**
     * @return DiagramColumnRepresentation[]
     * @throws DataAccessException
     */
    private function getColumnsRepresentation(
        AgileDashboard_Kanban $kanban,
        PFUser $user,
        array $dates
    ) {
        $items_in_columns = $this->kanban_cumulative_flow_diagram_dao->searchKanbanItemsByDates(
            $kanban->getTrackerId(),
            $dates
        );
        if ($items_in_columns === false) {
            throw new DataAccessException();
        }

        return $this->column_builder->build($kanban, $user, $dates, $items_in_columns);
    }
}
