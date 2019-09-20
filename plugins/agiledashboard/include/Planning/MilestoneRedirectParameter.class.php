<?php
/**
 * Copyright (c) Enalean, 2013. All Rights Reserved.
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

/**
 * I build url parameter to redirect to the right milestone
 */
class Planning_MilestoneRedirectParameter
{

    /** @return string */
    public function getPlanningRedirectToSelf(Planning_Milestone $milestone, $pane_identifier)
    {
        $planning_id = (int) $milestone->getPlanningId();

        $artifact_id = $milestone->getArtifactId();

        return "planning[$pane_identifier][$planning_id]=$artifact_id";
    }

    /** @return string */
    public function getPlanningRedirectToNew(Planning_Milestone $milestone, $pane_identifier)
    {
        $planning_id = (int) $milestone->getPlanningId();

        return "planning[$pane_identifier][$planning_id]=-1";
    }
}
