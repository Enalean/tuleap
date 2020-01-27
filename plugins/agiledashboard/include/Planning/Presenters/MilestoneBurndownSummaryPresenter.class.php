<?php
/**
 * Copyright (c) Enalean, 2014 - Present. All Rights Reserved.
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

use Tuleap\AgileDashboard\Planning\Presenters\AlternativeBoardLinkPresenter;

class Planning_Presenter_MilestoneBurndownSummaryPresenter extends Planning_Presenter_MilestoneSummaryPresenterAbstract
{

    /** @var Tracker_Chart_Data_Burndown */
    private $burndown_data;

    public function __construct(
        Planning_Milestone $milestone,
        $plugin_path,
        ?AlternativeBoardLinkPresenter $alternative_board_link,
        Tracker_Chart_Data_Burndown $burndown_data
    ) {
        parent::__construct($milestone, $plugin_path, $alternative_board_link);

        $this->burndown_data = $burndown_data;
    }

    public function has_burndown()
    {
        return true;
    }

    public function burndown_data()
    {
        return $this->burndown_data->getJsonRepresentation();
    }
}
