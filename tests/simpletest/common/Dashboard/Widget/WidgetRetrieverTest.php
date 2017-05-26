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

namespace Tuleap\Dashboard\User;

use Tuleap\Dashboard\Widget\DashboardWidget;
use Tuleap\Dashboard\Widget\DashboardWidgetColumn;
use Tuleap\Dashboard\Widget\DashboardWidgetLine;
use Tuleap\Dashboard\Widget\DashboardWidgetRetriever;

class WidgetRetrieverTest extends \TuleapTestCase
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
     * @var DashboardWidget[]
     */
    private $line_one_column_two_widgets;
    /**
     * @var DashboardWidget[]
     */
    private $line_one_column_one_widgets;
    /**
     * @var DashboardWidget[]
     */
    private $line_one_column_three_widgets;
    /**
     * @var DashboardWidget
     */
    private $widget_one;
    /**
     * @var DashboardWidget
     */
    private $widget_two;
    /**
     * @var DashboardWidget
     */
    private $widget_three;
    /**
     * @var DashboardWidget
     */
    private $widget_four;
    /**
     * @var DashboardWidget
     */
    private $widget_five;

    public function setUp()
    {
        $this->widget_one   = new DashboardWidget(1, 'image', 10, 1, 0, 0);
        $this->widget_two   = new DashboardWidget(2, 'image', 11, 2, 0, 0);
        $this->widget_three = new DashboardWidget(3, 'image', 12, 1, 1, 0);
        $this->widget_four  = new DashboardWidget(4, 'image', 13, 2, 1, 0);
        $this->widget_five  = new DashboardWidget(5, 'image', 14, 3, 0, 0);

        $this->line_one_column_one_widgets   = array($this->widget_one, $this->widget_three);
        $this->line_one_column_two_widgets   = array($this->widget_two, $this->widget_three);
        $this->line_one_column_three_widgets = array($this->widget_five);

        $this->line_one_columns = array(
            new DashboardWidgetColumn(1, 1, 0, $this->line_one_column_one_widgets),
            new DashboardWidgetColumn(2, 1, 1, $this->line_one_column_two_widgets),
            new DashboardWidgetColumn(3, 1, 2, $this->line_one_column_three_widgets)
        );

        $this->lines = array(
            new DashboardWidgetLine(
                1,
                1,
                'user',
                'one-column',
                0,
                $this->line_one_columns
            )
        );
    }

    public function itReturnsAllWidgets()
    {
        $dao       = mock('Tuleap\Dashboard\Widget\DashboardWidgetDao');
        $retriever = new DashboardWidgetRetriever($dao);

        stub($dao)->searchAllLinesByDashboardIdOrderedByRank()->returnsDar(array(
            'id'             => 1,
            'dashboard_id'   => 1,
            'dashboard_type' => 'user',
            'layout'         => 'one-column',
            'rank'           => 0
        ));
        stub($dao)->searchAllColumnsByLineIdOrderedByRank()->returnsDarFromArray(array(
            array('id' => 1, 'line_id' => 1, 'rank' => 0),
            array('id' => 2, 'line_id' => 1, 'rank' => 1),
            array('id' => 3, 'line_id' => 1, 'rank' => 2)
        ));
        stub($dao)->searchAllWidgetByColumnId(1)->returnsDarFromArray(array(
            array('id' => 1, 'column_id' => 1, 'rank' => 0, 'name' => 'image', 'content_id' => 10, 'is_minimized' => 0),
            array('id' => 3, 'column_id' => 1, 'rank' => 1, 'name' => 'image', 'content_id' => 12, 'is_minimized' => 0)
        ));
        stub($dao)->searchAllWidgetByColumnId(2)->returnsDarFromArray(array(
            array('id' => 2, 'column_id' => 2, 'rank' => 0, 'name' => 'image', 'content_id' => 11, 'is_minimized' => 0),
            array('id' => 4, 'column_id' => 2, 'rank' => 1, 'name' => 'image', 'content_id' => 13, 'is_minimized' => 0)
        ));
        stub($dao)->searchAllWidgetByColumnId(3)->returnsDarFromArray(array(
            array('id' => 5, 'column_id' => 3, 'rank' => 0, 'name' => 'image', 'content_id' => 14, 'is_minimized' => 0)
        ));

        $lines                = $retriever->getAllWidgets(1, 'user');
        $columns              = $lines[0]->getWidgetColumns();
        $column_one_widgets   = $columns[0]->getWidgets();
        $column_two_widgets   = $columns[1]->getWidgets();
        $column_three_widgets = $columns[2]->getWidgets();

        $this->assertArrayNotEmpty($lines);

        $this->assertIsA($lines[0], 'Tuleap\Dashboard\Widget\DashboardWidgetLine');
        $this->assertIsA($columns[0], 'Tuleap\Dashboard\Widget\DashboardWidgetColumn');
        $this->assertIsA($column_one_widgets[0], 'Tuleap\Dashboard\Widget\DashboardWidget');

        $this->assertCount($lines, 1);
        $this->assertCount($columns, 3);
        $this->assertCount($column_one_widgets, 2);
        $this->assertCount($column_two_widgets, 2);
        $this->assertCount($column_three_widgets, 1);
    }

    public function itReturnsEmptyArrayIfThereAreNotWidgets()
    {
        $dao       = mock('Tuleap\Dashboard\Widget\DashboardWidgetDao');
        $retriever = new DashboardWidgetRetriever($dao);

        stub($dao)->searchAllLinesByDashboardIdOrderedByRank()->returnsEmptyDar();
        stub($dao)->searchAllColumnsByLineIdOrderedByRank()->returnsEmptyDar();
        stub($dao)->searchAllWidgetByColumnId()->returnsEmptyDar();

        $widget_lines = $retriever->getAllWidgets(1, 'user');
        $this->assertArrayEmpty($widget_lines);
    }

    public function itReturnsColumnsByLineById()
    {
        $dao       = mock('Tuleap\Dashboard\Widget\DashboardWidgetDao');
        $retriever = new DashboardWidgetRetriever($dao);

        stub($dao)->searchAllColumnsByLineIdOrderedByRank()->returnsDarFromArray(array(
            array('id' => 1, 'line_id' => 1, 'rank' => 0),
            array('id' => 2, 'line_id' => 1, 'rank' => 1),
            array('id' => 3, 'line_id' => 1, 'rank' => 2)
        ));

        $columns = $retriever->getColumnsByLineById(1);

        $this->assertArrayNotEmpty($columns);

        $this->assertIsA($columns[0], 'Tuleap\Dashboard\Widget\DashboardWidgetColumn');

        $this->assertCount($columns, 3);
    }

    public function itReturnsEmptyArrayIfThereAreNotColumnsByLineById()
    {
        $dao       = mock('Tuleap\Dashboard\Widget\DashboardWidgetDao');
        $retriever = new DashboardWidgetRetriever($dao);

        stub($dao)->searchAllColumnsByLineIdOrderedByRank()->returnsEmptyDar();

        $columns = $retriever->getColumnsByLineById(1);

        $this->assertArrayEmpty($columns);
    }

    public function itReturnsWidgetById()
    {
        $dao       = mock('Tuleap\Dashboard\Widget\DashboardWidgetDao');
        $retriever = new DashboardWidgetRetriever($dao);

        stub($dao)->searchWidgetById()->returnsDar(array(
            'id'           => 1,
            'column_id'    => 1,
            'rank'         => 0,
            'name'         => 'image',
            'content_id'   => 10,
            'is_minimized' => 0
        ));

        $widget   = $retriever->getWidgetById(1);
        $expected = new DashboardWidget(1, 'image', 10, 1, 0, 0);

        $this->assertEqual($expected, $widget);
    }

    public function itThrowsExceptionIfThereIsNoWidget()
    {
        $dao       = mock('Tuleap\Dashboard\Widget\DashboardWidgetDao');
        $retriever = new DashboardWidgetRetriever($dao);

        stub($dao)->searchWidgetById()->returnsEmptyDar();

        $this->expectException();

        $retriever->getWidgetById(1);
    }
}
