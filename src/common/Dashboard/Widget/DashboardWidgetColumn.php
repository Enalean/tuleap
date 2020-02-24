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

class DashboardWidgetColumn
{
    private $id;
    private $line_id;

    /**
     * @var DashboardWidget[]
     */
    private $widgets;

    public function __construct(
        $id,
        $line_id,
        array $widgets
    ) {
        $this->id      = $id;
        $this->line_id = $line_id;
        $this->widgets = $widgets;
    }

    public function addWidget(DashboardWidget $widget)
    {
        $this->widgets[] = $widget;
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
    public function getLineId()
    {
        return $this->line_id;
    }

    /**
     * @return DashboardWidget[]
     */
    public function getWidgets()
    {
        return $this->widgets;
    }
}
