<?php
/**
 * Copyright (c) Enalean, 2012-Present. All Rights Reserved.
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

use Tuleap\Chart\Chart;
use Tuleap\TimezoneRetriever;

/**
 * I'm responsible of
 * - displaying a Burndown chart
 * - prepare data for display
 */
class Tracker_Chart_BurndownView extends Tracker_Chart_Burndown
{

    /**
     * @var Tracker_Chart_Data_Burndown
     */
    private $burndown_data;

    public function __construct(Tracker_Chart_Data_Burndown $burndown_data)
    {
        $this->burndown_data = $burndown_data;
    }

    /**
     * @return Chart
     */
    public function buildGraph()
    {
        $user_timezone = date_default_timezone_get();
        date_default_timezone_set(TimezoneRetriever::getServerTimezone());

        $graph = new Chart($this->width, $this->height);
        $graph->SetScale("datlin");

        $graph->title->Set($this->title);
        $graph->subtitle->Set($this->description);

        $colors = $graph->getThemedColors();

        $graph->xaxis->SetTickLabels($this->burndown_data->getHumanReadableDates());

        $remaining_effort = new LinePlot($this->burndown_data->getRemainingEffort());
        $graph->Add($remaining_effort);
        $remaining_effort->SetColor($colors[1] . ':0.7');
        $remaining_effort->SetWeight(2);
        $remaining_effort->SetLegend('Remaining effort');
        $remaining_effort->mark->SetType(MARK_FILLEDCIRCLE);
        $remaining_effort->mark->SetColor($colors[1] . ':0.7');
        $remaining_effort->mark->SetFillColor($colors[1]);
        $remaining_effort->mark->SetSize(3);

        $ideal_burndown = new LinePlot($this->burndown_data->getIdealEffort());
        $graph->Add($ideal_burndown);
        $ideal_burndown->SetColor($colors[0] . ':1.25');
        $ideal_burndown->SetLegend('Ideal Burndown');

        $graph->legend->SetPos(0.05, 0.5, 'right', 'center');
        $graph->legend->SetColumns(1);

        date_default_timezone_set($user_timezone);

        return $graph;
    }
}
