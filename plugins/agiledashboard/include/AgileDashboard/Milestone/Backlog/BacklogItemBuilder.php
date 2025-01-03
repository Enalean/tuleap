<?php
/**
 * Copyright Enalean (c) 2013 - Present. All rights reserved.
 *
 * Tuleap and Enalean names and logos are registrated trademarks owned by
 * Enalean SAS. All other trademarks or names are properties of their respective
 * owners.
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

use Tuleap\Tracker\Artifact\Artifact;

/**
 * I build BacklogItem{,Collection}
 */
class AgileDashboard_Milestone_Backlog_BacklogItemBuilder implements AgileDashboard_Milestone_Backlog_IBuildBacklogItemAndBacklogItemCollection
{
    public function getCollection()
    {
        return new AgileDashboard_Milestone_Backlog_BacklogItemCollection();
    }

    public function getItem(Artifact $artifact, $redirect_to_self, $is_inconsistent)
    {
        return new AgileDashboard_Milestone_Backlog_BacklogItem($artifact, $is_inconsistent);
    }
}
