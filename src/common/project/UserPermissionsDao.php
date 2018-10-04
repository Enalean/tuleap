<?php
/**
 * Copyright (c) Enalean, 2014 - 2018. All Rights Reserved.
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

class UserPermissionsDao extends DataAccessObject
{
    const PROJECT_ADMIN_FLAG = 'A';
    const WIKI_ADMIN_FLAG    = '2';
    const FORUM_ADMIN_FLAG   = '2';
    const NEWS_WRITER_FLAG   = '1';
    const NEWS_ADMIN_FLAG    = '2';

    public function __construct()
    {
        parent::__construct();

        $this->enableExceptionsOnError();
    }

    public function isUserPartOfProjectMembers($project_id, $user_id)
    {
        $project_id = $this->da->escapeInt($project_id);
        $user_id    = $this->da->escapeInt($user_id);

        $sql = "SELECT NULL FROM user_group WHERE group_id = $project_id AND user_id = $user_id";

        return count($this->retrieve($sql)) > 0;
    }

    public function addUserAsProjectAdmin($project_id, $user_id)
    {
        $project_id = $this->da->escapeInt($project_id);
        $user_id    = $this->da->escapeInt($user_id);
        $admin_flag = $this->da->quoteSmart(self::PROJECT_ADMIN_FLAG);

        $sql = "UPDATE user_group
                SET admin_flags = $admin_flag
                WHERE group_id = $project_id
                  AND user_id = $user_id";

        return $this->update($sql);
    }

    /**
     * @return int
     */
    public function removeUserFromProjectAdmin($project_id, $user_id)
    {
        $project_id = $this->da->escapeInt($project_id);
        $user_id    = $this->da->escapeInt($user_id);

        $sql = "UPDATE user_group
                SET admin_flags = ''
                WHERE group_id = $project_id
                  AND user_id = $user_id";

        return $this->update($sql);
    }

    /**
     * @return bool
     */
    public function isThereOtherProjectAdmin($project_id, $user_id)
    {
        $project_id = $this->da->escapeInt($project_id);
        $user_id    = $this->da->escapeInt($user_id);
        $admin_flag = $this->da->quoteSmart(self::PROJECT_ADMIN_FLAG);

        $sql = "SELECT NULL
                FROM user_group
                JOIN user ON (user.user_id = user_group.user_id)
                WHERE (user.status='A' OR user.status='R' OR user.status='S') AND
                  group_id = $project_id AND user_group.user_id != $user_id AND admin_flags = $admin_flag";

        return count($this->retrieve($sql)) > 0;
    }

    public function isUserPartOfProjectAdmins($project_id, $user_id)
    {
        $project_id = $this->da->escapeInt($project_id);
        $user_id    = $this->da->escapeInt($user_id);
        $admin_flag = $this->da->quoteSmart(self::PROJECT_ADMIN_FLAG);


        $sql = "SELECT NULL
                FROM user_group
                WHERE group_id = $project_id AND user_id = $user_id AND admin_flags = $admin_flag";

        return count($this->retrieve($sql)) > 0;
    }

    public function addUserAsWikiAdmin($project_id, $user_id)
    {
        $project_id = $this->da->escapeInt($project_id);
        $user_id    = $this->da->escapeInt($user_id);
        $admin_flag = $this->da->escapeInt(self::WIKI_ADMIN_FLAG);

        $sql = "UPDATE user_group
                SET wiki_flags = $admin_flag
                WHERE group_id = $project_id
                  AND user_id = $user_id";

        return $this->update($sql);
    }

    public function removeUserFromWikiAdmin($project_id, $user_id)
    {
        $project_id = $this->da->escapeInt($project_id);
        $user_id    = $this->da->escapeInt($user_id);

        $sql = "UPDATE user_group
                SET wiki_flags = 0
                WHERE group_id = $project_id
                  AND user_id = $user_id";

        return $this->update($sql);
    }

    public function isUserPartOfWikiAdmins($project_id, $user_id)
    {
        $project_id = $this->da->escapeInt($project_id);
        $user_id    = $this->da->escapeInt($user_id);
        $admin_flag = $this->da->escapeInt(self::WIKI_ADMIN_FLAG);


        $sql = "SELECT NULL
                FROM user_group
                WHERE group_id = $project_id AND user_id = $user_id AND wiki_flags = $admin_flag";

        return count($this->retrieve($sql)) > 0;
    }

    public function addUserAsForumAdmin($project_id, $user_id)
    {
        $project_id = $this->da->escapeInt($project_id);
        $user_id    = $this->da->escapeInt($user_id);
        $admin_flag = $this->da->escapeInt(self::FORUM_ADMIN_FLAG);

        $sql = "UPDATE user_group
                SET forum_flags = $admin_flag
                WHERE group_id = $project_id
                  AND user_id = $user_id";

        return $this->update($sql);
    }

    public function removeUserFromForumAdmin($project_id, $user_id)
    {
        $project_id = $this->da->escapeInt($project_id);
        $user_id    = $this->da->escapeInt($user_id);

        $sql = "UPDATE user_group
                SET forum_flags = 0
                WHERE group_id = $project_id
                  AND user_id = $user_id";

        return $this->update($sql);
    }

    public function isUserPartOfForumAdmins($project_id, $user_id)
    {
        $project_id = $this->da->escapeInt($project_id);
        $user_id    = $this->da->escapeInt($user_id);
        $admin_flag = $this->da->escapeInt(self::FORUM_ADMIN_FLAG);


        $sql = "SELECT NULL
                FROM user_group
                WHERE group_id = $project_id AND user_id = $user_id AND forum_flags = $admin_flag";

        return count($this->retrieve($sql)) > 0;
    }

    public function addUserAsNewsEditor($project_id, $user_id)
    {
        $project_id = $this->da->escapeInt($project_id);
        $user_id    = $this->da->escapeInt($user_id);
        $news_flag  = $this->da->escapeInt(self::NEWS_WRITER_FLAG);

        $sql = "UPDATE user_group
                SET news_flags = $news_flag
                WHERE group_id = $project_id
                  AND user_id = $user_id";

        return $this->update($sql);
    }

    public function removeUserFromNewsEditor($project_id, $user_id)
    {
        $project_id = $this->da->escapeInt($project_id);
        $user_id    = $this->da->escapeInt($user_id);

        $sql = "UPDATE user_group
                SET news_flags = 0
                WHERE group_id = $project_id
                  AND user_id = $user_id";

        return $this->update($sql);
    }

    public function isUserPartOfNewsEditors($project_id, $user_id)
    {
        $project_id = $this->da->escapeInt($project_id);
        $user_id    = $this->da->escapeInt($user_id);
        $news_flag  = $this->da->escapeInt(self::NEWS_WRITER_FLAG);


        $sql = "SELECT NULL
                FROM user_group
                WHERE group_id = $project_id AND user_id = $user_id AND news_flags = $news_flag";

        return count($this->retrieve($sql)) > 0;
    }

    public function addUserAsNewsAdmin($project_id, $user_id)
    {
        $project_id = $this->da->escapeInt($project_id);
        $user_id    = $this->da->escapeInt($user_id);
        $news_flag = $this->da->escapeInt(self::NEWS_ADMIN_FLAG);

        $sql = "UPDATE user_group
                SET news_flags = $news_flag
                WHERE group_id = $project_id
                  AND user_id = $user_id";

        return $this->update($sql);
    }

    public function removeUserFromNewsAdmin($project_id, $user_id)
    {
        $project_id = $this->da->escapeInt($project_id);
        $user_id    = $this->da->escapeInt($user_id);
        $news_flag  = $this->da->escapeInt(self::NEWS_WRITER_FLAG);

        $sql = "UPDATE user_group
                SET news_flags = $news_flag
                WHERE group_id = $project_id
                  AND user_id = $user_id";

        return $this->update($sql);
    }

    public function isUserPartOfNewsAdmins($project_id, $user_id)
    {
        $project_id = $this->da->escapeInt($project_id);
        $user_id    = $this->da->escapeInt($user_id);
        $news_flag  = $this->da->escapeInt(self::NEWS_ADMIN_FLAG);


        $sql = "SELECT NULL
                FROM user_group
                WHERE group_id = $project_id AND user_id = $user_id AND news_flags = $news_flag";

        return count($this->retrieve($sql)) > 0;
    }
}
