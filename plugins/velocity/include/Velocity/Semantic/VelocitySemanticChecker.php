<?php
/**
 * Copyright (c) Enalean, 2018 - Present. All Rights Reserved.
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

namespace Tuleap\Velocity\Semantic;

class VelocitySemanticChecker
{
    public function hasAtLeastOneTrackerCorrectlyConfigured(
        BacklogRequiredTrackerCollection $required_tracker_collection,
        ChildrenRequiredTrackerCollection $children_trackers_without_velocity_semantic,
    ) {
        if (count($children_trackers_without_velocity_semantic->getChildrenTrackers()) > 0 && $children_trackers_without_velocity_semantic->hasAtLeastOneChildrenWithVelocitySemanticForBacklogTrackers()) {
            return true;
        }

        return ! $required_tracker_collection->areAllBacklogTrackersMisconfigured();
    }
}
