<?php
/**
 * Copyright (c) Enalean, 2016-2018. All Rights Reserved.
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
use Tuleap\DB\DataAccessObject;

class ServerPermissionDao extends DataAccessObject
{

    public function searchProjectIdsUserIsGitAdmin($user_id)
    {
        $sql = "SELECT groups.group_id AS project_id
                FROM groups
                    INNER JOIN service USING (group_id)
                    INNER JOIN user_group USING (group_id)
                WHERE short_name = ?
                    AND is_used = 1
                    AND user_id = ?
                    AND admin_flags = 'A'
                    AND groups.status = 'A'
                UNION
                SELECT permissions.object_id AS project_id
                FROM permissions
                    INNER JOIN user_group ON (permissions.object_id = user_group.group_id)
                    INNER JOIN groups USING (group_id)
                WHERE permission_type = ?
                    AND groups.status = 'A'
                    AND permissions.ugroup_id = ?
                    AND user_group.user_id = ?
                UNION
                SELECT permissions.object_id AS project_id
                FROM permissions
                    INNER JOIN groups ON (permissions.object_id = groups.group_id)
                    INNER JOIN ugroup_user USING (ugroup_id)
                WHERE permission_type = ?
                    AND groups.status = 'A'
                    AND permissions.ugroup_id > ?
                    AND ugroup_user.user_id = ?";

        return $this->getDB()->run(
            $sql,
            GitPlugin::SERVICE_SHORTNAME,
            $user_id,
            Git::PERM_ADMIN,
            ProjectUGroup::PROJECT_MEMBERS,
            $user_id,
            Git::PERM_ADMIN,
            ProjectUGroup::NONE,
            $user_id
        );
    }
}
