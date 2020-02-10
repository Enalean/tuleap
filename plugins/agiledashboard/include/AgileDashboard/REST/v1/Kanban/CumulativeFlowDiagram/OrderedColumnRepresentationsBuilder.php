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

namespace Tuleap\AgileDashboard\REST\v1\Kanban\CumulativeFlowDiagram;

use AgileDashboard_Kanban;
use AgileDashboard_KanbanColumnFactory;
use DateInterval;
use DateTime;
use PFUser;
use Tracker_ArtifactFactory;
use Tuleap\AgileDashboard\Kanban\ColumnIdentifier;

class OrderedColumnRepresentationsBuilder
{
    public const BACKLOG_BINDVALUE_ID = 100;
    public const MAX_POSSIBLE_POINTS  = 90;

    /** @var AgileDashboard_KanbanColumnFactory */
    private $kanban_column_factory;
    /** @var Tracker_ArtifactFactory */
    private $artifact_factory;

    public function __construct(
        AgileDashboard_KanbanColumnFactory $kanban_column_factory,
        Tracker_ArtifactFactory $artifact_factory
    ) {
        $this->kanban_column_factory = $kanban_column_factory;
        $this->artifact_factory      = $artifact_factory;
    }

    /**
     * @return array
     * @throws TooManyPointsException
     */
    public function getDates(DateTime $start_date, DateTime $end_date, $interval_between_point)
    {
        $dates = array();

        $period = new DateInterval('P' . $interval_between_point . 'D');
        while ($end_date >= $start_date) {
            $dates[] = $end_date->format('Y-m-d');
            $end_date->sub($period);
        }

        if (count($dates) > self::MAX_POSSIBLE_POINTS) {
            throw new TooManyPointsException();
        }

        return array_reverse($dates);
    }

    /**
     * @param array $dates
     * @param $items_in_columns
     * @return array
     */
    public function build(
        AgileDashboard_Kanban $kanban,
        PFUser $user,
        array $dates,
        $items_in_columns
    ) {
        $items_count_for_archive = array_fill_keys($dates, 0);
        $items_count_grouped_by_open_column = array(
            self::BACKLOG_BINDVALUE_ID => array_fill_keys($dates, 0)
        );
        $columns = $this->kanban_column_factory->getAllKanbanColumnsForAKanban(
            $kanban,
            $user
        );
        foreach ($columns as $column) {
            $items_count_grouped_by_open_column[$column->getId()] = array_fill_keys($dates, 0);
        }

        foreach ($items_in_columns as $row) {
            $artifact = $this->artifact_factory->getInstanceFromRow($row);
            if ($artifact->userCanView($user)) {
                if (isset($items_count_grouped_by_open_column[$row['column_id']])) {
                    $items_count_grouped_by_open_column[$row['column_id']][$row['day']] += 1;
                } else {
                    $items_count_for_archive[$row['day']] += 1;
                }
            }
        }

        $ordered_column_representations = array_merge(
            array($this->buildArchiveColumnRepresentation($items_count_for_archive)),
            $this->buildOpenColumnsRepresentation($items_count_grouped_by_open_column, $columns)
        );

        return $ordered_column_representations;
    }

    /**
     * @return DiagramColumnRepresentation[]
     */
    private function buildOpenColumnsRepresentation(array $items_count_grouped_by_open_column, array $open_columns)
    {
        $open_column_representations_item_counts = array();
        foreach ($items_count_grouped_by_open_column as $column_id => $items_count) {
            foreach ($items_count as $day => $count) {
                $diagram_point_representation = new DiagramPointRepresentation();
                $diagram_point_representation->build(
                    $day,
                    $count
                );
                $open_column_representations_item_counts[$column_id][] = $diagram_point_representation;
            }
        }

        $ordered_open_column_representations = array();

        $reversed_columns = array_reverse($open_columns);
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

        $ordered_open_column_representations[] = $this->buildBacklogColumnRepresentation(
            $open_column_representations_item_counts
        );

        return $ordered_open_column_representations;
    }

    /**
     * @return DiagramColumnRepresentation
     */
    private function buildBacklogColumnRepresentation(array $open_column_representations_item_counts)
    {
        $backlog_representation = new DiagramColumnRepresentation();
        $backlog_representation->build(
            ColumnIdentifier::BACKLOG_COLUMN,
            'Backlog',
            $open_column_representations_item_counts[self::BACKLOG_BINDVALUE_ID]
        );
        return $backlog_representation;
    }

    /**
     * @return DiagramColumnRepresentation
     */
    private function buildArchiveColumnRepresentation(array $archive_items_count)
    {
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
            ColumnIdentifier::ARCHIVE_COLUMN,
            'Archive',
            $archive_representation_item_counts
        );

        return $archive_representation;
    }
}
