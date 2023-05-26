<?php
/**
 * Copyright Enalean (c) 2017 - Present. All rights reserved.
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

namespace Tuleap\Project\Admin\ProjectMembers;

use DataAccessObject;
use UserHelper;

class ProjectMembersDAO extends DataAccessObject
{
    public function searchProjectMembers($project_id)
    {
        $escaped_project_id = $this->da->escapeInt($project_id);
        $sql_order          = UserHelper::instance()->getDisplayNameSQLOrder();

        $sql = "SELECT
                    user.realname,
                    user.user_id,
                    user.user_name,
                    user.email,
                    user.status,
                    user_group.admin_flags,
                    user_group.wiki_flags,
                    user_group.forum_flags,
                    user_group.news_flags,
                    GROUP_CONCAT(DISTINCT ugroup_user.ugroup_id) AS ugroups_ids
                FROM user_group
                    INNER JOIN user
                        ON (
                            user.user_id = user_group.user_id
                        )
                    LEFT JOIN (
                            ugroup_user
                            INNER JOIN ugroup
                                ON (
                                    ugroup.group_id = $escaped_project_id
                                    AND ugroup_user.ugroup_id = ugroup.ugroup_id
                                )
                        )
                        ON (user.user_id = ugroup_user.user_id)
                WHERE user_group.group_id = $escaped_project_id
                GROUP BY user.user_id, user.user_name, user.realname, user.email, user.status, user_group.admin_flags, user_group.wiki_flags, user_group.forum_flags, user_group.news_flags
                ORDER BY $sql_order";

        return $this->retrieve($sql);
    }
}
