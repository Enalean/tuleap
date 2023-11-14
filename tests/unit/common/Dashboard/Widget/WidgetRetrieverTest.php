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

use Tuleap\Dashboard\Widget\DashboardWidget;
use Tuleap\Dashboard\Widget\DashboardWidgetRetriever;

class WidgetRetrieverTest extends \Tuleap\Test\PHPUnit\TestCase
{
    public function testItReturnsAllWidgets()
    {
        $dao       = $this->createMock(\Tuleap\Dashboard\Widget\DashboardWidgetDao::class);
        $retriever = new DashboardWidgetRetriever($dao);

        $dao->method('searchAllLinesByDashboardIdOrderedByRank')->willReturn(\TestHelper::arrayToDar([
            'id' => 1,
            'dashboard_id' => 1,
            'dashboard_type' => 'user',
            'layout' => 'one-column',
            'rank' => 0,
        ]));
        $dao->method('searchAllColumnsByLineIdOrderedByRank')->willReturn(\TestHelper::argListToDar([
            ['id' => 1, 'line_id' => 1, 'rank' => 0],
            ['id' => 2, 'line_id' => 1, 'rank' => 1],
            ['id' => 3, 'line_id' => 1, 'rank' => 2],
        ]));
        $dao->method('searchAllWidgetByColumnId')->withConsecutive([1], [2], [3])->willReturnOnConsecutiveCalls(
            \TestHelper::argListToDar([
                ['id' => 1, 'column_id' => 1, 'rank' => 0, 'name' => 'image', 'content_id' => 10],
                ['id' => 3, 'column_id' => 1, 'rank' => 1, 'name' => 'image', 'content_id' => 12],
            ]),
            \TestHelper::argListToDar([
                ['id' => 2, 'column_id' => 2, 'rank' => 0, 'name' => 'image', 'content_id' => 11],
                ['id' => 4, 'column_id' => 2, 'rank' => 1, 'name' => 'image', 'content_id' => 13],
            ]),
            \TestHelper::argListToDar([
                ['id' => 5, 'column_id' => 3, 'rank' => 0, 'name' => 'image', 'content_id' => 14],
            ])
        );

        $lines                = $retriever->getAllWidgets(1, 'user');
        $columns              = $lines[0]->getWidgetColumns();
        $column_one_widgets   = $columns[0]->getWidgets();
        $column_two_widgets   = $columns[1]->getWidgets();
        $column_three_widgets = $columns[2]->getWidgets();

        self::assertNotEmpty($lines);

        self::assertInstanceOf(\Tuleap\Dashboard\Widget\DashboardWidgetLine::class, $lines[0]);
        self::assertInstanceOf(\Tuleap\Dashboard\Widget\DashboardWidgetColumn::class, $columns[0]);
        self::assertInstanceOf(\Tuleap\Dashboard\Widget\DashboardWidget::class, $column_one_widgets[0]);

        self::assertCount(1, $lines);
        self::assertCount(3, $columns);
        self::assertCount(2, $column_one_widgets);
        self::assertCount(2, $column_two_widgets);
        self::assertCount(1, $column_three_widgets);
    }

    public function testItReturnsEmptyArrayIfThereAreNotWidgets()
    {
        $dao       = $this->createMock(\Tuleap\Dashboard\Widget\DashboardWidgetDao::class);
        $retriever = new DashboardWidgetRetriever($dao);

        $dao->method('searchAllLinesByDashboardIdOrderedByRank')->willReturn(\TestHelper::emptyDar());
        $dao->method('searchAllColumnsByLineIdOrderedByRank')->willReturn(\TestHelper::emptyDar());
        $dao->method('searchAllWidgetByColumnId')->willReturn(\TestHelper::emptyDar());

        $widget_lines = $retriever->getAllWidgets(1, 'user');
        self::assertEmpty($widget_lines);
    }

    public function testItReturnsColumnsByLineById()
    {
        $dao       = $this->createMock(\Tuleap\Dashboard\Widget\DashboardWidgetDao::class);
        $retriever = new DashboardWidgetRetriever($dao);

        $dao->method('searchAllColumnsByLineIdOrderedByRank')->willReturn(\TestHelper::argListToDar([
            ['id' => 1, 'line_id' => 1, 'rank' => 0],
            ['id' => 2, 'line_id' => 1, 'rank' => 1],
            ['id' => 3, 'line_id' => 1, 'rank' => 2],
        ]));

        $columns = $retriever->getColumnsByLineById(1);

        self::assertNotEmpty($columns);

        self::assertInstanceOf(\Tuleap\Dashboard\Widget\DashboardWidgetColumn::class, $columns[0]);

        self::assertCount(3, $columns);
    }

    public function testItReturnsEmptyArrayIfThereAreNotColumnsByLineById()
    {
        $dao       = $this->createMock(\Tuleap\Dashboard\Widget\DashboardWidgetDao::class);
        $retriever = new DashboardWidgetRetriever($dao);

        $dao->method('searchAllColumnsByLineIdOrderedByRank')->willReturn(\TestHelper::emptyDar());

        $columns = $retriever->getColumnsByLineById(1);

        self::assertEmpty($columns);
    }

    public function testItReturnsWidgetById()
    {
        $dao       = $this->createMock(\Tuleap\Dashboard\Widget\DashboardWidgetDao::class);
        $retriever = new DashboardWidgetRetriever($dao);

        $dao->method('searchWidgetById')->willReturn(\TestHelper::arrayToDar([
            'id' => 1,
            'column_id' => 1,
            'rank' => 0,
            'name' => 'image',
            'content_id' => 10,
            'is_minimized' => 0,
        ]));

        $widget   = $retriever->getWidgetById(1);
        $expected = new DashboardWidget(1, 'image', 10, 1, 0, 0);

        self::assertEquals($expected, $widget);
    }

    public function testItThrowsExceptionIfThereIsNoWidget()
    {
        $dao       = $this->createMock(\Tuleap\Dashboard\Widget\DashboardWidgetDao::class);
        $retriever = new DashboardWidgetRetriever($dao);

        $dao->method('searchWidgetById')->willReturn(\TestHelper::emptyDar());

        self::expectException(\Exception::class);

        $retriever->getWidgetById(1);
    }
}
