<?php
/**
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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
 *
 */

namespace Tuleap\Dashboard\User;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tuleap\Dashboard\Widget\DashboardWidget;
use Tuleap\Dashboard\Widget\DashboardWidgetColumn;
use Tuleap\Dashboard\Widget\DashboardWidgetLine;
use Tuleap\Dashboard\Widget\DashboardWidgetRetriever;

class WidgetRetrieverTest extends TestCase
{
    use MockeryPHPUnitIntegration;

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

    protected function setUp(): void
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

    public function testItReturnsAllWidgets()
    {
        $dao       = \Mockery::spy(\Tuleap\Dashboard\Widget\DashboardWidgetDao::class);
        $retriever = new DashboardWidgetRetriever($dao);

        $dao->shouldReceive('searchAllLinesByDashboardIdOrderedByRank')->andReturns(\TestHelper::arrayToDar(array(
            'id'             => 1,
            'dashboard_id'   => 1,
            'dashboard_type' => 'user',
            'layout'         => 'one-column',
            'rank'           => 0
        )));
        $dao->shouldReceive('searchAllColumnsByLineIdOrderedByRank')->andReturns(\TestHelper::argListToDar(array(
            array('id' => 1, 'line_id' => 1, 'rank' => 0),
            array('id' => 2, 'line_id' => 1, 'rank' => 1),
            array('id' => 3, 'line_id' => 1, 'rank' => 2)
        )));
        $dao->shouldReceive('searchAllWidgetByColumnId')->with(1)->andReturns(\TestHelper::argListToDar(array(
            array('id' => 1, 'column_id' => 1, 'rank' => 0, 'name' => 'image', 'content_id' => 10, 'is_minimized' => 0),
            array('id' => 3, 'column_id' => 1, 'rank' => 1, 'name' => 'image', 'content_id' => 12, 'is_minimized' => 0)
        )));
        $dao->shouldReceive('searchAllWidgetByColumnId')->with(2)->andReturns(\TestHelper::argListToDar(array(
            array('id' => 2, 'column_id' => 2, 'rank' => 0, 'name' => 'image', 'content_id' => 11, 'is_minimized' => 0),
            array('id' => 4, 'column_id' => 2, 'rank' => 1, 'name' => 'image', 'content_id' => 13, 'is_minimized' => 0)
        )));
        $dao->shouldReceive('searchAllWidgetByColumnId')->with(3)->andReturns(\TestHelper::argListToDar(array(
            array('id' => 5, 'column_id' => 3, 'rank' => 0, 'name' => 'image', 'content_id' => 14, 'is_minimized' => 0)
        )));

        $lines                = $retriever->getAllWidgets(1, 'user');
        $columns              = $lines[0]->getWidgetColumns();
        $column_one_widgets   = $columns[0]->getWidgets();
        $column_two_widgets   = $columns[1]->getWidgets();
        $column_three_widgets = $columns[2]->getWidgets();

        $this->assertNotEmpty($lines);

        $this->assertInstanceOf(\Tuleap\Dashboard\Widget\DashboardWidgetLine::class, $lines[0]);
        $this->assertInstanceOf(\Tuleap\Dashboard\Widget\DashboardWidgetColumn::class, $columns[0]);
        $this->assertInstanceOf(\Tuleap\Dashboard\Widget\DashboardWidget::class, $column_one_widgets[0]);

        $this->assertCount(1, $lines);
        $this->assertCount(3, $columns);
        $this->assertCount(2, $column_one_widgets);
        $this->assertCount(2, $column_two_widgets);
        $this->assertCount(1, $column_three_widgets);
    }

    public function testItReturnsEmptyArrayIfThereAreNotWidgets()
    {
        $dao       = \Mockery::spy(\Tuleap\Dashboard\Widget\DashboardWidgetDao::class);
        $retriever = new DashboardWidgetRetriever($dao);

        $dao->shouldReceive('searchAllLinesByDashboardIdOrderedByRank')->andReturns(\TestHelper::emptyDar());
        $dao->shouldReceive('searchAllColumnsByLineIdOrderedByRank')->andReturns(\TestHelper::emptyDar());
        $dao->shouldReceive('searchAllWidgetByColumnId')->andReturns(\TestHelper::emptyDar());

        $widget_lines = $retriever->getAllWidgets(1, 'user');
        $this->assertEmpty($widget_lines);
    }

    public function testItReturnsColumnsByLineById()
    {
        $dao       = \Mockery::spy(\Tuleap\Dashboard\Widget\DashboardWidgetDao::class);
        $retriever = new DashboardWidgetRetriever($dao);

        $dao->shouldReceive('searchAllColumnsByLineIdOrderedByRank')->andReturns(\TestHelper::argListToDar(array(
            array('id' => 1, 'line_id' => 1, 'rank' => 0),
            array('id' => 2, 'line_id' => 1, 'rank' => 1),
            array('id' => 3, 'line_id' => 1, 'rank' => 2)
        )));

        $columns = $retriever->getColumnsByLineById(1);

        $this->assertNotEmpty($columns);

        $this->assertInstanceOf(\Tuleap\Dashboard\Widget\DashboardWidgetColumn::class, $columns[0]);

        $this->assertCount(3, $columns);
    }

    public function testItReturnsEmptyArrayIfThereAreNotColumnsByLineById()
    {
        $dao       = \Mockery::spy(\Tuleap\Dashboard\Widget\DashboardWidgetDao::class);
        $retriever = new DashboardWidgetRetriever($dao);

        $dao->shouldReceive('searchAllColumnsByLineIdOrderedByRank')->andReturns(\TestHelper::emptyDar());

        $columns = $retriever->getColumnsByLineById(1);

        $this->assertEmpty($columns);
    }

    public function testItReturnsWidgetById()
    {
        $dao       = \Mockery::spy(\Tuleap\Dashboard\Widget\DashboardWidgetDao::class);
        $retriever = new DashboardWidgetRetriever($dao);

        $dao->shouldReceive('searchWidgetById')->andReturns(\TestHelper::arrayToDar(array(
            'id'           => 1,
            'column_id'    => 1,
            'rank'         => 0,
            'name'         => 'image',
            'content_id'   => 10,
            'is_minimized' => 0
        )));

        $widget   = $retriever->getWidgetById(1);
        $expected = new DashboardWidget(1, 'image', 10, 1, 0, 0);

        $this->assertEquals($expected, $widget);
    }

    public function testItThrowsExceptionIfThereIsNoWidget()
    {
        $dao       = \Mockery::spy(\Tuleap\Dashboard\Widget\DashboardWidgetDao::class);
        $retriever = new DashboardWidgetRetriever($dao);

        $dao->shouldReceive('searchWidgetById')->andReturns(\TestHelper::emptyDar());

        $this->expectException(\Exception::class);

        $retriever->getWidgetById(1);
    }
}
