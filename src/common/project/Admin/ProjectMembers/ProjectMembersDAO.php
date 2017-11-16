<?php
/**
 * Copyright Enalean (c) 2017. All rights reserved.
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

namespace Tuleap\Project\Admin\ProjectMembers;

use DataAccessObject;

class ProjectMembersDAO extends DataAccessObject
{
    public function searchProjectMembers($project_id)
    {

        $escaped_project_id = $this->da->escapeInt($project_id);

        $sql = "SELECT user.realname,
                       user.user_id,
                       user.user_name,
                       user.status,
                       user.has_avatar,
                       IF(generic_user.group_id, 1, 0) AS is_generic
                FROM user_group
                    INNER JOIN user ON (user.user_id = user_group.user_id)
                    LEFT JOIN generic_user ON (
                        generic_user.user_id = user.user_id AND
                        generic_user.group_id = $escaped_project_id
                    )
                WHERE user_group.group_id = $escaped_project_id
                ORDER BY user.realname";

        return $this->retrieve($sql);
    }
}
