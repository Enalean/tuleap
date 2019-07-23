<?php
/**
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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
 *
 */

declare(strict_types=1);

namespace Tuleap\Tracker;

class PermissionsFunctionsWrapper
{
    public function getFieldUGroupsPermissions(\Tracker_FormElement $form_element)
    {
        $tracker = $form_element->getTracker();
        if (! $tracker) {
            throw new \RuntimeException('A formelement must belong to a tracker');
        }
        return plugin_tracker_permission_get_field_tracker_ugroups_permissions($tracker->getGroupId(), $tracker->getId(), [$form_element]);
    }

    public function getTrackerUGroupsPermissions(\Tracker $tracker)
    {
        return plugin_tracker_permission_get_tracker_ugroups_permissions($tracker->getGroupId(), $tracker->getId());
    }
}
