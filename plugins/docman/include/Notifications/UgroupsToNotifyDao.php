<?php
/**
 * Copyright (c) Enalean, 2017. All Rights Reserved.
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

namespace Tuleap\Docman\Notifications;

use DataAccessObject;

class UgroupsToNotifyDao extends DataAccessObject
{
    public function searchUgroupsByItemIdAndType($item_id, $type)
    {
        $item_id = $this->da->escapeInt($item_id);
        $type    = $this->da->quoteSmart($type);

        $sql = "SELECT ugroup.*
            FROM plugin_docman_notification_ugroups AS notification
            INNER JOIN ugroup
            ON (
                ugroup.ugroup_id = notification.ugroup_id
                AND notification.item_id = $item_id
                AND notification.type = $type
            )";

        return $this->retrieve($sql);
    }

    public function deleteByUgroupId($project_id, $ugroup_id)
    {
        $project_id = $this->da->escapeInt($project_id);
        $ugroup_id  = $this->da->escapeInt($ugroup_id);

        $sql = "DELETE notification.*
            FROM plugin_docman_item AS item
            INNER JOIN plugin_docman_notification_ugroups AS notification
            ON (
                item.item_id = notification.item_id
                AND item.group_id = $project_id
                AND notification.ugroup_id = $ugroup_id
            )
        ";

        return $this->update($sql);
    }

    public function deleteByItemId($item_id)
    {
        $item_id = $this->da->escapeInt($item_id);

        $sql = "DELETE FROM plugin_docman_notification_ugroups
                WHERE item_id = $item_id";

        return $this->update($sql);
    }

    public function delete($item_id, $ugroup_id, $type)
    {
        $item_id   = $this->da->escapeInt($item_id);
        $type      = $this->da->quoteSmart($type);
        $ugroup_id = $this->da->escapeInt($ugroup_id);

        $sql = "DELETE
            FROM plugin_docman_notification_ugroups
            WHERE item_id = $item_id
            AND ugroup_id = $ugroup_id
            AND type = $type
            ";

        return $this->update($sql);
    }

    public function searchUGroupByUGroupIdAndItemIdAndType($item_id, $ugroup_id, $type)
    {
        $item_id   = $this->da->escapeInt($item_id);
        $type      = $this->da->quoteSmart($type);
        $ugroup_id = $this->da->escapeInt($ugroup_id);

        $sql = "SELECT *
            FROM plugin_docman_notification_ugroups
            WHERE item_id = $item_id
            AND ugroup_id = $ugroup_id
            AND type = $type
            ";

        return $this->retrieve($sql);
    }
}
