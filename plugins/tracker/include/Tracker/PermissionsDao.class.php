<?php
/**
 * Copyright (c) Enalean, 2013-Present. All Rights Reserved.
 *
 * Tuleap and Enalean names and logos are registered trademarks owned by
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

class Tracker_PermissionsDao extends DataAccessObject
{

    /**
     * @return int[]
     */
    public function getAuthorizedStaticUgroupIds($tracker_id)
    {
        $tracker_id             = $this->da->escapeInt($tracker_id);
        $dynamic_upper_boundary = $this->da->escapeInt(ProjectUGroup::DYNAMIC_UPPER_BOUNDARY);

        $sql = "SELECT DISTINCT ugroup_id
                FROM tracker_field AS F
                    INNER JOIN permissions ON (object_id = CAST(id AS CHAR CHARACTER SET utf8) AND permission_type LIKE 'PLUGIN_TRACKER_FIELD_%')
                WHERE F.tracker_id = $tracker_id
                  AND ugroup_id > $dynamic_upper_boundary

                UNION

                SELECT DISTINCT ugroup_id
                FROM permissions
                WHERE object_id = '$tracker_id'
                  AND (
                    permission_type LIKE 'PLUGIN_TRACKER_ACCESS_%'
                    OR permission_type = '" . Tracker::PERMISSION_ADMIN . "'
                  )
                  AND ugroup_id > $dynamic_upper_boundary

               UNION

               SELECT DISTINCT ugroup_id
               FROM tracker_workflow_transition AS T
                    INNER JOIN tracker_workflow AS W ON (T.workflow_id = W.workflow_id AND W.tracker_id = $tracker_id)
                    INNER JOIN permissions AS P ON (
                        P.object_id = CAST(T.transition_id AS CHAR CHARACTER SET utf8) AND
                        permission_type = 'PLUGIN_TRACKER_WORKFLOW_TRANSITION')
               WHERE ugroup_id > $dynamic_upper_boundary
               ";

        return $this->retrieve($sql)->instanciateWith(array($this, 'extractUgroupID'));
    }

    /**
     * Extract the ugroup id from a given row
     *
     * Internally used by getAuthorizedStaticUgroupIds() to return id instead of rows.
     *
     * @return int
     */
    public function extractUgroupID(array $row)
    {
        return $row['ugroup_id'];
    }
}
