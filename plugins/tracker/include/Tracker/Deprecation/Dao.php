<?php
/**
 * Copyright (c) Enalean, 2016. All Rights Reserved.
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

namespace Tuleap\Tracker\Deprecation;

use DataAccessObject;

class Dao extends DataAccessObject
{
    public function searchDeprecatedTrackersFields()
    {
        $sql = "SELECT project.group_id, tracker_field.tracker_id, tracker_field_computed.field_id
            FROM groups project, tracker, tracker_field, tracker_field_computed
            WHERE project.status = 'A'
              AND project.group_id = tracker.group_id
              AND tracker.id = tracker_field.tracker_id
              AND tracker_field.id = tracker_field_computed.field_id
              AND formElement_type = 'computed'
              AND (fast_compute = 0 OR target_field_name <> tracker_field.name)
              AND use_it = 1
              AND deletion_date IS NULL
            ORDER BY group_name";

        return $this->retrieve($sql);
    }

    public function searchDeprecatedTrackersFieldsByProject($project_id)
    {
        $project_id = $this->da->escapeInt($project_id);
        $sql = "SELECT project.group_id, tracker_field.tracker_id, tracker_field_computed.field_id
            FROM groups project, tracker, tracker_field, tracker_field_computed
            WHERE project.status = 'A'
              AND project.group_id = tracker.group_id
              AND tracker.id = tracker_field.tracker_id
              AND tracker_field.id = tracker_field_computed.field_id
              AND project.group_id= $project_id
              AND formElement_type = 'computed'
              AND (fast_compute = 0 OR target_field_name <> tracker_field.name)
              AND use_it = 1
              AND deletion_date IS NULL
            ORDER BY group_name";

        return $this->retrieve($sql);
    }


    public function searchDeprecatedFieldsByTracker($tracker_id)
    {
        $tracker_id = $this->da->escapeInt($tracker_id);
        $sql = "SELECT project.group_id, tracker_field.tracker_id, tracker_field_computed.field_id
            FROM groups project, tracker, tracker_field, tracker_field_computed
            WHERE project.status = 'A'
              AND project.group_id = tracker.group_id
              AND tracker.id = tracker_field.tracker_id
              AND tracker_field.id = tracker_field_computed.field_id
              AND tracker.id = $tracker_id
              AND formElement_type = 'computed'
              AND (fast_compute = 0 OR target_field_name <> tracker_field.name)
              AND use_it = 1
              AND deletion_date IS NULL
            ORDER BY group_name";

        return $this->retrieve($sql);
    }

    public function searchDeprecatedFieldsById($field_id)
    {
        $field_id = $this->da->escapeInt($field_id);
        $sql = "SELECT project.group_id, tracker_field.tracker_id, tracker_field_computed.field_id
            FROM groups project, tracker, tracker_field, tracker_field_computed
            WHERE project.status = 'A'
              AND project.group_id = tracker.group_id
              AND tracker.id = tracker_field.tracker_id
              AND tracker_field.id = tracker_field_computed.field_id
              AND tracker_field.id  = $field_id
              AND formElement_type = 'computed'
              AND (fast_compute = 0 OR target_field_name <> tracker_field.name)
              AND use_it = 1
            ORDER BY group_name";

        return $this->retrieve($sql);
    }
}
