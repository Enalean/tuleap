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

namespace Tuleap\Dashboard\Widget;

class WidgetReorderTest extends \Tuleap\Test\PHPUnit\TestCase
{
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
    private $widget_five;

    protected function setUp(): void
    {
        $this->widget_one   = new DashboardWidget(1, 'image', 10, 1, 0, 0);
        $this->widget_two   = new DashboardWidget(2, 'image', 11, 2, 0, 0);
        $this->widget_three = new DashboardWidget(3, 'image', 12, 1, 1, 0);
        $this->widget_five  = new DashboardWidget(5, 'image', 14, 3, 0, 0);

        $this->line_one_column_one_widgets   = [$this->widget_one, $this->widget_three];
        $this->line_one_column_two_widgets   = [$this->widget_two, $this->widget_three];
        $this->line_one_column_three_widgets = [$this->widget_five];

        $this->line_one_columns = [
            new DashboardWidgetColumn(1, 0, $this->line_one_column_one_widgets),
            new DashboardWidgetColumn(2, 1, $this->line_one_column_two_widgets),
            new DashboardWidgetColumn(3, 2, $this->line_one_column_three_widgets),
        ];
    }

    public function testItReordersWidgetsInSameColumn()
    {
        $dao              = $this->createMock(\Tuleap\Dashboard\Widget\DashboardWidgetDao::class);
        $remover          = new DashboardWidgetRemoverInList();
        $widget_reorder   = new DashboardWidgetReorder($dao, $remover);
        $widget_to_update = new DashboardWidget(1, 'image', 10, 1, 0, 0);

        $dao->expects(self::never())->method('updateColumnIdByWidgetId');
        $dao->expects(self::atLeastOnce())->method('updateWidgetRankByWidgetId');

        $widget_reorder->reorderWidgets($this->line_one_columns[0], $this->line_one_columns[0], $widget_to_update, 1);
    }

    public function testItReordersWidgetsInNewColumn()
    {
        $dao            = $this->createMock(\Tuleap\Dashboard\Widget\DashboardWidgetDao::class);
        $remover        = new DashboardWidgetRemoverInList();
        $widget_reorder = new DashboardWidgetReorder($dao, $remover);

        $dao->method('searchAllWidgetByColumnId')->willReturn(\TestHelper::arrayToDar([
            'id' => 3,
            'colum_id' => 1,
            'rank' => 0,
            'name' => 'image',
            'content_id' => 12,
        ]));
        $dao->method('searchAllColumnsByLineIdOrderedByRank')->willReturn(\TestHelper::arrayToDar([
            'id' => 1,
            'line_id' => 1,
            'rank' => 0,
        ]));

        $dao->expects(self::once())->method('updateColumnIdByWidgetId');
        $dao->expects(self::atLeastOnce())->method('updateWidgetRankByWidgetId');

        $widget_reorder->reorderWidgets($this->line_one_columns[1], $this->line_one_columns[0], $this->widget_one, 2);
    }
}
