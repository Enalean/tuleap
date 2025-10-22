<?php
/**
 * Copyright (c) Enalean, 2014 - Present. All Rights Reserved.
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

use Tuleap\DB\DataAccessObject;

class UserPermissionsDao extends DataAccessObject
{
    public const string PROJECT_ADMIN_FLAG = 'A';
    public const string WIKI_ADMIN_FLAG    = '2';
    public function isUserPartOfProjectMembers($project_id, $user_id)
    {
        $sql  = 'SELECT NULL FROM user_group WHERE group_id = ? AND user_id = ?';
        $rows = $this->getDB()->run($sql, $project_id, $user_id);

        return count($rows) > 0;
    }

    public function addUserAsProjectMember(int $project_id, int $user_id)
    {
        $sql = 'INSERT INTO user_group(group_id, user_id) VALUES (?, ?)';
        $this->getDB()->run($sql, $project_id, $user_id);
    }

    public function addUserAsProjectAdmin($project_id, $user_id)
    {
        $sql = 'UPDATE user_group
                SET admin_flags = ?
                WHERE group_id = ?
                  AND user_id = ?';

        $this->getDB()->run($sql, self::PROJECT_ADMIN_FLAG, $project_id, $user_id);
    }

    public function removeUserFromProjectAdmin($project_id, $user_id)
    {
        $sql = "UPDATE user_group
                SET admin_flags = ''
                WHERE group_id = ?
                  AND user_id = ?";

        $this->getDB()->run($sql, $project_id, $user_id);
    }

    /**
     * @return bool
     */
    public function isThereOtherProjectAdmin($project_id, $user_id)
    {
        $sql = "SELECT NULL
                FROM user_group
                JOIN user ON (user.user_id = user_group.user_id)
                WHERE (user.status='A' OR user.status='R' OR user.status='S') AND
                  group_id = ? AND user_group.user_id != ? AND admin_flags = ?";

        $rows = $this->getDB()->run($sql, $project_id, $user_id, self::PROJECT_ADMIN_FLAG);

        return count($rows) > 0;
    }

    /**
     * @return bool
     */
    public function isUserPartOfProjectAdmins($project_id, $user_id)
    {
        $sql = 'SELECT NULL
                FROM user_group
                WHERE group_id = ? AND user_id = ? AND admin_flags = ?';

        $rows = $this->getDB()->run($sql, $project_id, $user_id, self::PROJECT_ADMIN_FLAG);

        return count($rows) > 0;
    }

    public function addUserAsWikiAdmin($project_id, $user_id)
    {
        $sql = 'UPDATE user_group
                SET wiki_flags = ?
                WHERE group_id = ?
                  AND user_id = ?';

        $this->getDB()->run($sql, self::WIKI_ADMIN_FLAG, $project_id, $user_id);
    }

    public function removeUserFromWikiAdmin($project_id, $user_id)
    {
        $sql = 'UPDATE user_group
                SET wiki_flags = 0
                WHERE group_id = ?
                  AND user_id = ?';

        $this->getDB()->run($sql, $project_id, $user_id);
    }

    public function isUserPartOfWikiAdmins($project_id, $user_id)
    {
        $sql = 'SELECT NULL
                FROM user_group
                WHERE group_id = ? AND user_id = ? AND wiki_flags = ?';

        $rows = $this->getDB()->run($sql, $project_id, $user_id, self::WIKI_ADMIN_FLAG);

        return count($rows) > 0;
    }
}
