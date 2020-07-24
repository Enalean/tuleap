<?php
/**
 * Copyright (c) Enalean, 2014 - 2018. All Rights Reserved.
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
use Tuleap\AgileDashboard\Milestone\Pane\TopPlanning\TopPlanningV2PaneInfo;

class AgileDashboard_PaneInfoIdentifier
{
    /**
     * @param string $pane_info_identifier
     *
     * @return bool
     */
    public function isPaneAPlanningV2($pane_info_identifier)
    {
        return in_array($pane_info_identifier, [
            PlanningV2PaneInfo::IDENTIFIER,
            TopPlanningV2PaneInfo::IDENTIFIER
        ]);
    }
}
