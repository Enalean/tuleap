<?php
/**
 * Copyright (c) Enalean, 2012. All Rights Reserved.
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

class Tracker_Migration_V3_CcFieldDao extends DataAccessObject
{

    public function addCCField($tv5_id)
    {
        $tv5_id = $this->da->escapeInt($tv5_id);
        $sql = "INSERT INTO tracker_field(tracker_id, parent_id, formElement_type, name, label, description, use_it, rank, scope, required, notifications)
              SELECT $tv5_id, S1.id, 'tbl', 'cc', 'CC', '', 1, 1, 'P', 0, 1
              FROM tracker_fieldset_$tv5_id AS S1
                WHERE $tv5_id = S1.tracker_id
                  AND S1.name = 'CC List'";
        $field_id = $this->updateAndGetLastId($sql);

        $this->addOpenList($field_id);
        $this->addList($field_id);
        $this->addListBindUsers($field_id);
        $this->setPermissionsForEveryone($field_id);
    }

    private function addOpenList($field_id)
    {
        $sql = "INSERT INTO tracker_field_openlist(field_id, hint)
                SELECT f.id, 'Type in a search term'
                FROM tracker_field AS f
                WHERE f.id = $field_id";
        return $this->update($sql);
    }

    private function addList($field_id)
    {
        $sql = "INSERT INTO tracker_field_list(field_id, bind_type)
                SELECT f.id, 'users'
                FROM tracker_field AS f
                WHERE f.id = $field_id";
        return $this->update($sql);
    }

    private function addListBindUsers($field_id)
    {
        $sql = "INSERT INTO tracker_field_list_bind_users(field_id, value_function)
                SELECT f.id, 'ugroup_2'
                FROM tracker_field AS f
                WHERE f.id = $field_id";
        return $this->update($sql);
    }

    private function setPermissionsForEveryone($field_id)
    {
        $sql = "INSERT INTO permissions(permission_type, object_id, ugroup_id) VALUES
                ('PLUGIN_TRACKER_FIELD_READ', $field_id, 1),
                ('PLUGIN_TRACKER_FIELD_SUBMIT', $field_id, 1),
                ('PLUGIN_TRACKER_FIELD_UPDATE', $field_id, 1)";
        return $this->update($sql);
    }
}
