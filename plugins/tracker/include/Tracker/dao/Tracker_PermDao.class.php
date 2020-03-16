<?php
/**
 * Copyright (c) Enalean, 2011 - 2016. All Rights Reserved.
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
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

class Tracker_PermDao extends DataAccessObject
{

    public function searchAccessPermissionsByTrackerId($tracker_id)
    {
        $tracker_id = $this->da->escapeInt($tracker_id);

        $sql = "SELECT *
                FROM permissions
                WHERE (permission_type LIKE 'PLUGIN_TRACKER_ACCESS%'
                    OR permission_type = '" . Tracker::PERMISSION_ADMIN . "')
                    AND object_id='$tracker_id'
                ORDER BY ugroup_id";

        return $this->retrieve($sql);
    }

    public function searchAccessPermissionsByFieldId($field_id)
    {
        $field_id = $this->da->escapeInt($field_id);

        $sql = "SELECT *
                FROM permissions
                WHERE permission_type LIKE 'PLUGIN_TRACKER_FIELD%'
                    AND object_id='$field_id'
                ORDER BY ugroup_id";

        return $this->retrieve($sql);
    }
}
