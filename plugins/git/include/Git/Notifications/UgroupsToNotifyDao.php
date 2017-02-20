<?php
/**
 * Copyright Enalean (c) 2017. All rights reserved.
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

namespace Tuleap\Git\Notifications;

use DataAccessObject;

class UgroupsToNotifyDao extends DataAccessObject
{
    public function searchUgroupsByRepositoryId($repository_id)
    {
        $repository_id = $this->da->escapeInt($repository_id);

        $sql = "SELECT ugroup.*
                FROM plugin_git_post_receive_notification_ugroup AS notif
                    INNER JOIN ugroup
                    ON (
                        ugroup.ugroup_id = notif.ugroup_id
                        AND notif.repository_id = $repository_id
                    )";

        return $this->retrieve($sql);
    }

    public function delete($repository_id, $ugroup_id)
    {
        $repository_id = $this->da->escapeInt($repository_id);
        $ugroup_id       = $this->da->escapeInt($ugroup_id);

        $sql = "DELETE
                FROM plugin_git_post_receive_notification_ugroup
                WHERE repository_id = $repository_id
                  AND ugroup_id = $ugroup_id";

        return $this->update($sql);
    }

    public function deleteByUgroupId($project_id, $ugroup_id)
    {
        $project_id = $this->da->escapeInt($project_id);
        $ugroup_id  = $this->da->escapeInt($ugroup_id);

        $sql = "DELETE notif.*
                FROM plugin_git AS repo
                    INNER JOIN plugin_git_post_receive_notification_ugroup AS notif
                    ON (
                      repo.repository_id = notif.repository_id
                      AND repo.project_id = $project_id
                      AND notif.ugroup_id = $ugroup_id
                    )";

        return $this->update($sql);
    }

    public function deleteByRepositoryId($repository_id)
    {
        $repository_id = $this->da->escapeInt($repository_id);

        $sql = "DELETE
                FROM plugin_git_post_receive_notification_ugroup
                WHERE repository_id = $repository_id";

        return $this->update($sql);
    }
}
