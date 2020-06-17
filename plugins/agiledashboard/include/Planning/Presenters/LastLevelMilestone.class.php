<?php
/**
 * Copyright (c) Enalean, 2014. All Rights Reserved.
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

/*
 * This represents a milestone at the bottom of a planning hierarchy
 */
class Planning_Presenter_LastLevelMilestone
{

     /** @var Planning_Presenter_MilestoneSummaryPresenter[] */
    public $milestone_summary_presenters;

    /** @var string */
    public $milestone_type_name;

    public function __construct($milestone_summary_presenters, $tracker_name)
    {
        $this->milestone_summary_presenters = $milestone_summary_presenters;
        $this->milestone_type_name          = $tracker_name;
    }

    public function no_milestone()
    {
        return dgettext('tuleap-agiledashboard', 'There are no milestones for this period');
    }
}
