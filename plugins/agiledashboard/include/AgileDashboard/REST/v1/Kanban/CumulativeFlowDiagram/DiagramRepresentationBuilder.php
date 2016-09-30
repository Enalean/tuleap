<?php
/**
 * Copyright (c) Enalean, 2016. All Rights Reserved.
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

use DataAccessException;
use DateInterval;
use DateTime;
use Tracker_ArtifactFactory;
use Tuleap\AgileDashboard\KanbanCumulativeFlowDiagramDao;
use Tuleap\AgileDashboard\REST\v1\Kanban\KanbanColumnRepresentation;
use AgileDashboard_KanbanColumnFactory;
use PFUser;
use AgileDashboard_Kanban;

class DiagramRepresentationBuilder
{
    const BACKLOG_BINDVALUE_ID = 100;
    const MAX_POSSIBLE_POINTS  = 90;

    /**
     * @var KanbanCumulativeFlowDiagramDao
     */
    private $kanban_cumulative_flow_diagram_dao;

    /**
     * @var AgileDashboard_KanbanColumnFactory
     */
    private $kanban_column_factory;

    /**
     * @var Tracker_ArtifactFactory
     */
    private $artifact_factory;

    public function __construct(
        KanbanCumulativeFlowDiagramDao $kanban_cumulative_flow_diagram_dao,
        AgileDashboard_KanbanColumnFactory $kanban_column_factory,
        Tracker_ArtifactFactory $artifact_factory
    ) {
        $this->kanban_cumulative_flow_diagram_dao = $kanban_cumulative_flow_diagram_dao;
        $this->kanban_column_factory              = $kanban_column_factory;
        $this->artifact_factory                   = $artifact_factory;
    }

    /**
     * @return DiagramRepresentation
     * @throws DataAccessException
     * @throws TooMuchPointsException
     */
    public function build(
        AgileDashboard_Kanban $kanban,
        PFUser $user,
        DateTime $start_date,
        DateTime $end_date,
        $interval_between_point
    ) {
        $dates = $this->getDates($start_date, $end_date, $interval_between_point);

        $archive_representation          = $this->getArchiveColumnRepresentation($kanban, $user, $dates);
        $open_column_representations     = $this->getOpenColumnsRepresentation($kanban, $user, $dates);
        $ordered_cumulative_flow_columns = array_merge(array($archive_representation), $open_column_representations);

        $diagram_representation = new DiagramRepresentation();
        $diagram_representation->build(
            $ordered_cumulative_flow_columns
        );

        return $diagram_representation;
    }

    /**
     * @return array
     * @throws TooMuchPointsException
     */
    private function getDates(DateTime $start_date, DateTime $end_date, $interval_between_point)
    {
        $dates = array();

        $period = new DateInterval('P' . $interval_between_point . 'D');
        while ($end_date >= $start_date) {
            $dates[] = $end_date->format('Y-m-d');
            $end_date->sub($period);
        }

        if (count($dates) > self::MAX_POSSIBLE_POINTS) {
            throw new TooMuchPointsException();
        }

        return array_reverse($dates);
    }

    /**
     * @return DiagramColumnRepresentation
     * @throws DataAccessException
     */
    private function getArchiveColumnRepresentation(AgileDashboard_Kanban $kanban, PFUser $user, array $dates)
    {
        $archive_items_count = array_fill_keys($dates, 0);
        $items_in_archive    = $this->kanban_cumulative_flow_diagram_dao->searchKanbanItemsInArchive(
            $kanban->getTrackerId(),
            $dates
        );
        if ($items_in_archive === false) {
            throw new DataAccessException();
        }
        foreach ($items_in_archive as $row) {
            $artifact = $this->artifact_factory->getInstanceFromRow($row);
            if ($artifact->userCanView($user)) {
                $archive_items_count[$row['day']] += 1;
            }
        }

        $archive_representation_item_counts = array();
        foreach ($archive_items_count as $day => $kanban_item_counts) {
            $diagram_representation = new DiagramPointRepresentation();
            $diagram_representation->build(
                $day,
                $kanban_item_counts
            );
            $archive_representation_item_counts[] = $diagram_representation;
        }

        $archive_representation = new DiagramColumnRepresentation();
        $archive_representation->build(
            KanbanColumnRepresentation::ARCHIVE_COLUMN,
            'Archive',
            $archive_representation_item_counts
        );

        return $archive_representation;
    }

    /**
     * @return DiagramColumnRepresentation[]
     * @throws DataAccessException
     */
    private function getOpenColumnsRepresentation(AgileDashboard_Kanban $kanban, PFUser $user, array $dates)
    {
        $items_count_grouped_by_column = array();
        $items_in_open_columns         = $this->kanban_cumulative_flow_diagram_dao->searchKanbanItemsInOpenColumns(
            $kanban->getTrackerId(),
            $dates
        );
        if ($items_in_open_columns === false) {
            throw new DataAccessException();
        }

        $items_count_grouped_by_column[self::BACKLOG_BINDVALUE_ID] = array_fill_keys($dates, 0);
        $columns = $this->kanban_column_factory->getAllKanbanColumnsForAKanban($kanban, $user);
        foreach ($columns as $column) {
            $items_count_grouped_by_column[$column->getId()] = array_fill_keys($dates, 0);
        }

        foreach ($items_in_open_columns as $row) {
            $artifact = $this->artifact_factory->getInstanceFromRow($row);
            if ($artifact->userCanView($user)) {
                $items_count_grouped_by_column[$row['column_id']][$row['day']] += 1;
            }
        }

        $open_column_representations_item_counts = array();
        foreach ($items_count_grouped_by_column as $column_id => $items_count) {
            foreach ($items_count as $day => $count) {
                $diagram_representation = new DiagramPointRepresentation();
                $diagram_representation->build(
                    $day,
                    $count
                );
                $open_column_representations_item_counts[$column_id][] = $diagram_representation;
            }
        }

        $ordered_open_column_representations = array();
        $reversed_columns                    = array_reverse($columns);

        foreach ($reversed_columns as $column) {
            $values = $open_column_representations_item_counts[$column->getId()];

            $column_representation = new DiagramColumnRepresentation();
            $column_representation->build(
                $column->getId(),
                $column->getLabel(),
                $values
            );

            $ordered_open_column_representations[] = $column_representation;
        }

        $ordered_open_column_representations[] = $this->getBacklogColumnRepresentation(
            $open_column_representations_item_counts
        );

        return $ordered_open_column_representations;
    }

    /**
     * @return DiagramColumnRepresentation
     */
    private function getBacklogColumnRepresentation(array $open_column_representations_item_counts)
    {
        $backlog_representation = new DiagramColumnRepresentation();
        $backlog_representation->build(
            KanbanColumnRepresentation::BACKLOG_COLUMN,
            'Backlog',
            $open_column_representations_item_counts[self::BACKLOG_BINDVALUE_ID]
        );
        return $backlog_representation;
    }
}
