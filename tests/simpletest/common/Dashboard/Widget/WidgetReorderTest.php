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

class WidgetReorderTest extends \TuleapTestCase
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
        parent::setUp();

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
            new DashboardWidgetLine(1, 1, 'user', 'one-column', 0, $this->line_one_columns)
        );
    }

    public function itReordersWidgetsInSameColumn()
    {
        $dao              = mock('Tuleap\Dashboard\Widget\DashboardWidgetDao');
        $retriever        = new DashboardWidgetRetriever($dao);
        $remover          = new DashboardWidgetRemoverInList();
        $widget_reorder   = new DashboardWidgetReorder($dao, $retriever, $remover);
        $widget_to_update = new DashboardWidget(1, 'image', 10, 1, 0, 0);

        expect($dao)->updateColumnIdByWidgetId()->never();
        expect($dao)->updateWidgetRankByWidgetId()->atLeastOnce();

        $widget_reorder->reorderWidgets($this->line_one_columns[0], $this->line_one_columns[0], $widget_to_update, 1);
    }

    public function itReordersWidgetsInNewColumn()
    {
        $dao              = mock('Tuleap\Dashboard\Widget\DashboardWidgetDao');
        $retriever        = new DashboardWidgetRetriever($dao);
        $remover          = new DashboardWidgetRemoverInList();
        $widget_reorder   = new DashboardWidgetReorder($dao, $retriever, $remover);

        stub($dao)->searchAllWidgetByColumnId()->returnsDar(array(
            'id'         => 3,
            'colum_id'   => 1,
            'rank'       => 0,
            'name'       => 'image',
            'content_id' => 12
        ));
        stub($dao)->searchAllColumnsByLineIdOrderedByRank()->returnsDar(array(
            'id'      => 1,
            'line_id' => 1,
            'rank'    => 0
        ));

        expect($dao)->updateColumnIdByWidgetId()->once();
        expect($dao)->updateWidgetRankByWidgetId()->atLeastOnce();

        $widget_reorder->reorderWidgets($this->line_one_columns[1], $this->line_one_columns[0], $this->widget_one, 2);
    }
}
