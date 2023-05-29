<?php
/**
 * Copyright (c) Enalean, 2017 - Present. All Rights Reserved.
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

namespace Tuleap\Project;

use DataAccessObject;

class UserRemoverDao extends DataAccessObject
{
    public function removeNonAdminUserFromProject($project_id, $user_id): bool
    {
        $project_id = $this->da->escapeInt($project_id);
        $user_id    = $this->da->escapeInt($user_id);
        $admin_flag = $this->da->quoteSmart('A');

        $sql = "DELETE FROM user_group
                WHERE group_id = $project_id
                  AND user_id = $user_id
                  AND admin_flags <> $admin_flag";

        return $this->update($sql);
    }

    public function removeUserFromProjectUgroups($project_id, $user_id)
    {
        $project_id = $this->da->escapeInt($project_id);
        $user_id    = $this->da->escapeInt($user_id);

        $sql = "DELETE ugroup_user
                FROM ugroup
                    INNER JOIN ugroup_user USING (ugroup_id)
                WHERE ugroup.group_id = $project_id
                AND ugroup_user.user_id = $user_id";

        return $this->update($sql);
    }
}
