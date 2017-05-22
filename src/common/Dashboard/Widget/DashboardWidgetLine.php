<?php
/**
 * Copyright (c) Enalean, 2017. All rights reserved
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/
 */

namespace Tuleap\Dashboard\Widget;

class DashboardWidgetLine
{
    private $id;
    private $dashboard_id;
    private $dashboard_type;
    private $layout;
    private $rank;

    /**
     * @var DashboardWidgetColumn[]
     */
    private $widget_columns;

    public function __construct(
        $id,
        $dashboard_id,
        $dashboard_type,
        $layout,
        $rank,
        array $widget_columns
    ) {
        $this->id             = $id;
        $this->dashboard_id   = $dashboard_id;
        $this->dashboard_type = $dashboard_type;
        $this->layout         = $layout;
        $this->rank           = $rank;
        $this->widget_columns = $widget_columns;
    }

    /**
     * @param DashboardWidgetColumn $column
     */
    public function addWidgetColumn(DashboardWidgetColumn $column)
    {
        $this->widget_columns[] = $column;
    }

    /**
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getLayout()
    {
        return $this->layout;
    }

    /**
     * @return DashboardWidgetColumn[]
     */
    public function getWidgetColumns()
    {
        return $this->widget_columns;
    }
}
