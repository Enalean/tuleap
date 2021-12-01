<?php
/**
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
 *
 * This file is a part of Codendi.
 *
 * Codendi is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Codendi is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Codendi. If not, see <http://www.gnu.org/licenses/>.
 */

require_once('include/DataAccessObject.class.php');

/**
 *  Data Access Object for mailing lists
 */
class MailingListDao extends DataAccessObject
{

    /**
     * Search active (=not deteted) mailing lists
     * return all active lists
     * @return DataAccessResult
     */
    public function searchAllActiveML()
    {
        $sql = "SELECT *
                FROM mail_group_list
                WHERE is_public IN (0,1)";

        return $this->retrieve($sql);
    }

    /**
     * Searches by group_list_id
     * @return DataAccessResult
     */
    public function searchByGroupListId($group_list_id)
    {
        $group_list_id = $this->da->escapeInt($group_list_id);
        $sql           = "SELECT * FROM mail_group_list
                WHERE group_list_id = $group_list_id";

        return $this->retrieve($sql);
    }

    /**
     * Searches by project id
     *
     * @param int $projectId id of the project
     *
     * @return DataAccessResult
     */
    public function searchByProject($projectId)
    {
        $projectId = $this->da->escapeInt($projectId);
        $sql       = "SELECT * FROM mail_group_list
                WHERE group_id = $projectId";

        return $this->retrieve($sql);
    }

    /**
     * Mark the list as deleted
     *
     * @param int $listId Id of the mailing list
     *
     * @return bool
     */
    public function deleteList($listId)
    {
        $listId = $this->da->escapeInt($listId);
        $sql    = "UPDATE mail_group_list SET is_public=9 " .
            " WHERE group_list_id=" . $listId;

        return $this->update($sql);
    }

    /**
     * Delete the list
     *
     * @param int $listId Id of the mailing list
     *
     * @return bool
     */
    public function deleteListDefinitively($listId)
    {
        $listId = $this->da->escapeInt($listId);
        $sql    = "DELETE FROM mail_group_list " .
            " WHERE group_list_id=" . $listId;

        return $this->update($sql);
    }

    /**
     * @return DataAccessResult|false
     */
    public function searchActiveListsInProject(int $project_id)
    {
        $project_id = $this->da->escapeInt($project_id);

        $sql = "SELECT * FROM mail_group_list
                WHERE group_id = $project_id
                  AND is_public IN (0, 1)";

        return $this->retrieve($sql);
    }

    /**
     * @return DataAccessResult|false
     */
    public function searchPublicListsInProject(int $project_id)
    {
        $project_id = $this->da->escapeInt($project_id);

        $sql = "SELECT * FROM mail_group_list
                WHERE group_id = $project_id
                  AND is_public = 1";

        return $this->retrieve($sql);
    }

    public function updateListInProject(int $list_id, int $project_id, string $description, bool $is_public): bool
    {
        $list_id     = $this->da->escapeInt($list_id);
        $project_id  = $this->da->escapeInt($project_id);
        $description = $this->da->quoteSmart($description);
        $is_public   = $is_public ? 1 : 0;

        $sql = "UPDATE mail_group_list
                SET description = $description,
                    is_public = $is_public
                WHERE group_list_id = $list_id
                  AND group_id = $project_id";

        return $this->update($sql);
    }

    public function deleteListInProject(int $list_id, int $project_id): bool
    {
        $list_id    = $this->da->escapeInt($list_id);
        $project_id = $this->da->escapeInt($project_id);

        $sql = "UPDATE mail_group_list
                SET is_public = 9
                WHERE group_list_id = $list_id
                  AND group_id = $project_id";

        return $this->update($sql);
    }

    /**
     * @return false|int
     */
    public function create(
        int $project_id,
        string $name,
        bool $is_public,
        string $password,
        int $user_id,
        string $description,
    ) {
        $project_id  = $this->da->escapeInt($project_id);
        $name        = $this->da->quoteSmart($name);
        $is_public   = $is_public ? 1 : 0;
        $password    = $this->da->quoteSmart($password);
        $user_id     = $this->da->escapeInt($user_id);
        $description = $this->da->quoteSmart($description);

        $sql = "INSERT INTO mail_group_list (group_id,list_name,is_public,password,list_admin,status,description)
                VALUES ($project_id, $name, $is_public, $password, $user_id, 1, $description)";

        return $this->updateAndGetLastId($sql);
    }

    public function isThereAnExistingListInTheProject(string $name, int $project_id): bool
    {
        $project_id = $this->da->escapeInt($project_id);
        $name       = $this->da->quoteSmart($name);

        $sql = "SELECT NULL FROM mail_group_list
                WHERE group_id = $project_id
                  AND LOWER(list_name) = $name";

        return $this->retrieveCount($sql) > 0;
    }
}
