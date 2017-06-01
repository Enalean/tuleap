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

class WidgetDeletorTest extends \TuleapTestCase
{
    /**
     * @var DashboardWidgetLine[]
     */
    private $lines;
    /**
     * @var DashboardWidgetColumn[]
     */
    private $line_one_columns;
    /**
     * @var DashboardWidget
     */
    private $widget_one;
    /**
     * @var DashboardWidget
     */
    private $widget_two;
    /**
     * @var DashboardWidget[]
     */
    private $widgets;
    /**
     * @var DashboardWidgetColumn
     */
    private $column;

    public function setUp()
    {
        parent::setUp();

        $this->widget_one = new DashboardWidget(1, 'image', 10, 1, 0, 0);
        $this->widget_two = new DashboardWidget(2, 'image', 11, 2, 0, 0);

        $this->widgets = array($this->widget_one, $this->widget_two);

        $this->column = new DashboardWidgetColumn(1, 1, 0, $this->widgets);
    }

    public function itDeletesColumn()
    {
        $dao     = mock('Tuleap\Dashboard\Widget\DashboardWidgetDao');
        $deletor = new DashboardWidgetDeletor($dao);

        expect($dao)->removeColumn()->once();
        expect($dao)->reorderColumns()->once();

        $deletor->deleteColumn($this->column);
    }

    public function itnDeletesLine()
    {
        $dao     = mock('Tuleap\Dashboard\Widget\DashboardWidgetDao');
        $deletor = new DashboardWidgetDeletor($dao);

        expect($dao)->removeLine()->once();
        expect($dao)->reorderLines()->once();

        $deletor->deleteLineByColumn($this->column);
    }
}
