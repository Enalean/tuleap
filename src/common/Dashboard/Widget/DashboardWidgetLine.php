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
    private $layout;

    /**
     * @var DashboardWidgetColumn[]
     */
    private $widget_columns;

    public function __construct(
        $id,
        $layout,
        array $widget_columns
    ) {
        $this->id             = $id;
        $this->layout         = $layout;
        $this->widget_columns = $widget_columns;
    }

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
