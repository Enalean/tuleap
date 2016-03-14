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

namespace Tuleap\Git\RemoteServer\Gerrit\Permission;

use GitPlugin;
use Git;
use ProjectUGroup;
use DataAccessObject;

class ServerPermissionDao extends DataAccessObject {

    public function searchProjectIdsUserIsGitAdmin($user_id) {

        $service_shortname    = $this->da->quoteSmart(GitPlugin::SERVICE_SHORTNAME);
        $git_admin_permission = $this->da->quoteSmart(Git::PERM_ADMIN);

        $user_id                   = $this->da->escapeInt($user_id);
        $project_members_ugroup_id = $this->da->escapeInt(ProjectUGroup::PROJECT_MEMBERS);
        $none_ugroup_id            = $this->da->escapeInt(ProjectUGroup::NONE);

        $sql = "SELECT groups.group_id AS project_id
                FROM groups
                    INNER JOIN service USING (group_id)
                    INNER JOIN user_group USING (group_id)
                WHERE short_name = $service_shortname
                    AND is_used = 1
                    AND user_id = $user_id
                    AND admin_flags = 'A'
                    AND groups.status = 'A'
                UNION
                SELECT permissions.object_id AS project_id
                FROM permissions
                    INNER JOIN user_group ON (permissions.object_id = user_group.group_id)
                    INNER JOIN groups USING (group_id)
                WHERE permission_type = $git_admin_permission
                    AND groups.status = 'A'
                    AND permissions.ugroup_id = $project_members_ugroup_id
                    AND user_group.user_id = $user_id
                UNION
                SELECT permissions.object_id AS project_id
                FROM permissions
                    INNER JOIN groups ON (permissions.object_id = groups.group_id)
                    INNER JOIN ugroup_user USING (ugroup_id)
                WHERE permission_type = $git_admin_permission
                    AND groups.status = 'A'
                    AND permissions.ugroup_id > $none_ugroup_id
                    AND ugroup_user.user_id = $user_id";

        return $this->retrieve($sql);
    }

}
