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

namespace Tuleap\Dashboard\Widget;

use Codendi_Request;
use Widget;

class WidgetCreator
{
    /**
     * @var DashboardWidgetDao
     */
    private $dao;

    public function __construct(DashboardWidgetDao $dao)
    {
        $this->dao = $dao;
    }

    public function create($owner_id, $owner_type, $dashboard_id, Widget $widget, Codendi_Request $request)
    {
        $content_id = (int) $widget->create($request);

        $this->dao->create($owner_id, $owner_type, $dashboard_id, $widget->getId(), $content_id);
    }

    /**
     * @param $dashboard_id
     * @param $dashboard_type
     * @param DashboardWidgetLine[] $lines
     * @param $new_line_rank
     * @return int
     */
    public function createLine($dashboard_id, $dashboard_type, array $lines, $new_line_rank)
    {
        array_splice($lines, $new_line_rank, 0, [true]);
        foreach ($lines as $index => $line) {
            if ($line === true) {
                $new_line_rank = $index;
            } else {
                $this->dao->updateWidgetRankByLineId($line->getId(), $index);
            }
        }
        return $this->dao->createLine($dashboard_id, $dashboard_type, $new_line_rank);
    }

    /**
     * @param $new_line_id
     * @param DashboardWidgetColumn[] $columns
     * @param $new_column_rank
     * @return int
     */
    public function createColumn($new_line_id, array $columns, $new_column_rank)
    {
        array_splice($columns, $new_column_rank, 0, [true]);
        foreach ($columns as $index => $column) {
            if ($column === true) {
                $new_column_rank = $index;
            } else {
                $this->dao->updateWidgetRankByColumnId($column->getId(), $index);
            }
        }
        return $this->dao->createColumn($new_line_id, $new_column_rank);
    }
}
