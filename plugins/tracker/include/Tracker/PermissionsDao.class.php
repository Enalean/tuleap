<?php
/**
 * Copyright (c) Enalean, 2013. All Rights Reserved.
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

class Tracker_PermissionsDao extends DataAccessObject {

    public function getAuthorizedUgroupIdsForFields($tracker_id) {
        $tracker_id = $this->da->escapeInt($tracker_id);

        $sql = "SELECT DISTINCT ugroup_id
                FROM tracker_field AS F
                    INNER JOIN permissions ON (object_id = id AND permission_type LIKE 'PLUGIN_TRACKER_FIELD_%')
                WHERE F.tracker_id = $tracker_id";

        $ugroup_ids = array();
        foreach ($this->retrieve($sql) as $row) {
            $ugroup_ids[] = $row['ugroup_id'];
        }

        return $ugroup_ids;
    }
}
?>
