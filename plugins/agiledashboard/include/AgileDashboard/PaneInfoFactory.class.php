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

    /** @var PFUser */
    private $user;

    /** @var AgileDashboard_Milestone_Pane_Planning_SubmilestoneFinder */
    private $submilestone_finder;

    /** @var string */
    private $theme_path;

    public function __construct(
        PFUser $user,
        AgileDashboard_Milestone_Pane_Planning_SubmilestoneFinder $submilestone_finder,
        $theme_path
    ) {
        $this->user                         = $user;
        $this->submilestone_finder          = $submilestone_finder;
        $this->theme_path                   = $theme_path;
    }

    public function getDetailsPaneInfo(Planning_Milestone $milestone)
    {
        return new DetailsPaneInfo($milestone, $this->theme_path);
    }

    public function getPlanningV2PaneInfo(Planning_Milestone $milestone)
    {
        $submilestone_tracker = $this->submilestone_finder->findFirstSubmilestoneTracker($milestone);
        if (! $submilestone_tracker) {
            return;
        }

        return new PlanningV2PaneInfo($milestone, $this->theme_path, $submilestone_tracker);
    }
}
