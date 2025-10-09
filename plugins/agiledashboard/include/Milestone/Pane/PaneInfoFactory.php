<?php
/**
 * Copyright (c) Enalean, 2013 - Present. All Rights Reserved.
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

namespace Tuleap\AgileDashboard\Milestone\Pane;

use PFUser;
use Planning_Milestone;
use Tuleap\AgileDashboard\Milestone\Pane\Details\DetailsPaneInfo;
use Tuleap\AgileDashboard\Milestone\Pane\Planning\SubmilestoneFinder;
use Tuleap\AgileDashboard\Milestone\Pane\Planning\PlanningV2PaneInfo;

/**
 * I build panes info for a Planning_Milestone
 */
class PaneInfoFactory
{
    public function __construct(
        private readonly SubmilestoneFinder $submilestone_finder,
    ) {
    }

    public function getDetailsPaneInfo(Planning_Milestone $milestone): DetailsPaneInfo
    {
        return new DetailsPaneInfo($milestone);
    }

    public function getPlanningV2PaneInfo(PFUser $user, Planning_Milestone $milestone): ?PlanningV2PaneInfo
    {
        $submilestone_tracker = $this->submilestone_finder->findFirstSubmilestoneTracker($user, $milestone);
        if (! $submilestone_tracker) {
            return null;
        }

        return new PlanningV2PaneInfo($milestone, $submilestone_tracker);
    }
}
