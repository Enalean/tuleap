<?php
/**
 * Copyright (c) Enalean, 2013 - 2018. All Rights Reserved.
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

use Tuleap\AgileDashboard\Milestone\Pane\Planning\PlanningV2PaneInfo;
use Tuleap\AgileDashboard\Milestone\Pane\Details\DetailsPaneInfo;

/**
 * I build panes info for a Planning_Milestone
 */
class AgileDashboard_PaneInfoFactory
{
    /** @var AgileDashboard_Milestone_Pane_Planning_SubmilestoneFinder */
    private $submilestone_finder;

    public function __construct(
        AgileDashboard_Milestone_Pane_Planning_SubmilestoneFinder $submilestone_finder
    ) {
        $this->submilestone_finder          = $submilestone_finder;
    }

    public function getDetailsPaneInfo(Planning_Milestone $milestone)
    {
        return new DetailsPaneInfo($milestone);
    }

    public function getPlanningV2PaneInfo(Planning_Milestone $milestone)
    {
        $submilestone_tracker = $this->submilestone_finder->findFirstSubmilestoneTracker($milestone);
        if (! $submilestone_tracker) {
            return;
        }

        return new PlanningV2PaneInfo($milestone, $submilestone_tracker);
    }
}
