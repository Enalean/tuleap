<?php
/**
 * Copyright (c) Enalean, 2016 - Present. All rights reserved
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

class ProjectDao extends DataAccessObject
{
    public const GROUP_ID        = 'group_id';
    public const STATUS          = 'status';
    public const UNIX_GROUP_NAME = 'unix_group_name';

    public function getFoundRows()
    {
        $sql = 'SELECT FOUND_ROWS() as nb';
        $dar = $this->retrieve($sql);
        if ($dar && ! $dar->isError() && $dar->rowCount() == 1) {
            $row = $dar->current();
            return $row['nb'];
        } else {
            return -1;
        }
    }

    public function searchById($id)
    {
        $sql = "SELECT *" .
               " FROM `groups` " .
               " WHERE group_id = " . $this->da->quoteSmart($id);
        return $this->retrieve($sql);
    }

    public function searchByStatus($status)
    {
        $status = $this->da->quoteSmart($status);
        $sql    = "SELECT SQL_CALC_FOUND_ROWS *
                FROM `groups`
                WHERE status = $status
                ORDER BY group_name";
        return $this->retrieve($sql);
    }

    public function countByStatusAndUser(int $user_id, string $status): int
    {
        $user_id = $this->da->escapeInt($user_id);
        $status  = $this->da->quoteSmart($status);

        $sql = "SELECT SQL_CALC_FOUND_ROWS 1
                FROM `groups` AS g
                  JOIN user_group ug USING (group_id)
                WHERE g.status = $status
                  AND ug.user_id = $user_id
                  AND ug.admin_flags = 'A'";
        $this->retrieve($sql);
        return (int) $this->foundRows();
    }

    public function countByStatus(string $project_status): int
    {
        $project_status = $this->da->quoteSmart($project_status);

        $dar = $this->retrieve('SELECT count(*) AS nb FROM `groups` WHERE status = ' . $project_status);
        if ($dar === false) {
            return 0;
        }

        return (int) $dar->getRow()['nb'];
    }

    public function searchByUnixGroupName($unixGroupName)
    {
        $unixGroupName = $this->da->quoteSmart($unixGroupName);
        $sql           = "SELECT *
                FROM `groups`
                WHERE unix_group_name=$unixGroupName";
        return $this->retrieve($sql);
    }

    public function searchByCaseInsensitiveUnixGroupName($unixGroupName)
    {
        $unixGroupName = $this->da->quoteSmart($unixGroupName);
        $sql           = "SELECT *
                FROM `groups`
                WHERE LOWER(unix_group_name)=LOWER($unixGroupName)";
        return $this->retrieve($sql);
    }

    /**
     * Look for active projects, based on their name (unix/public)
     *
     * This method returns only active projects. If no $userId provided, only
     * public project are returned.
     * If $userId is provided, both public and private projects the user is member
     * of are returned
     * If $userId is provided, you can also choose to restrict the result set to
     * the projects the user is member of or is admin of.
     *
     * @param String  $name
     * @param int $limit
     * @param int $userId
     * @param bool $isMember
     * @param bool $isAdmin
     * @param bool $isPrivate Display private projects if true
     *
     * @return \Tuleap\DB\Compat\Legacy2018\LegacyDataAccessResultInterface
     */
    public function searchProjectsNameLike($name, $limit, $userId = null, $isMember = false, $isAdmin = false, $isPrivate = false, $offset = 0)
    {
        $join    = '';
        $where   = '';
        $groupby = '';

        $access_private               = $this->da->quoteSmart(Project::ACCESS_PRIVATE);
        $access_private_wo_restricted = $this->da->quoteSmart(Project::ACCESS_PRIVATE_WO_RESTRICTED);

        $public = ' g.access NOT IN (' . $access_private . ', ' . $access_private_wo_restricted . ')';
        if ($isPrivate) {
            $public = ' 1 ';
        }
        if ($userId != null) {
            if ($isMember || $isAdmin) {
                // Manage if we search project the user is member or admin of
                $join  .= ' JOIN user_group ug ON (ug.group_id = g.group_id)';
                $where .= ' AND ug.user_id = ' . $this->da->escapeInt($userId);
                if ($isAdmin) {
                    $where .= ' AND ug.admin_flags = "A"';
                }
            } else {
                // Either public projects or private projects the user is member of
                $join  .= ' LEFT JOIN user_group ug ON (ug.group_id = g.group_id)';
                $where .= ' AND (' . $public .
                          '     OR (g.access IN (' . $access_private . ', ' . $access_private_wo_restricted . ')
                                    AND ug.user_id = ' . $this->da->escapeInt($userId) . '
                                )
                            )';
            }
            $groupby .= ' GROUP BY g.group_id';
        } else {
            // If no user_id provided, only return public projects
            $where .= ' AND ' . $public;
        }

        $sql = "SELECT SQL_CALC_FOUND_ROWS g.*" .
               " FROM `groups` AS g" .
               $join .
               " WHERE (g.group_name like " . $this->da->quoteSmart($name . '%') .
               " OR g.unix_group_name like " . $this->da->quoteSmart($name . '%') . ")" .
               " AND g.status='A'" .
               $where .
               $groupby .
               " ORDER BY group_name" .
               " LIMIT " . $this->da->escapeInt($offset) . ", " . $this->da->escapeInt($limit);

        return $this->retrieve($sql);
    }

    public function searchSiteTemplates()
    {
        $sql = "SELECT *
         FROM `groups`
         WHERE type='2'
             AND status IN ('A','s')";
        return $this->retrieve($sql);
    }

    public function searchPendingProjects()
    {
        $project_status = $this->da->quoteSmart(Project::STATUS_PENDING);

        $sql = "SELECT * FROM `groups` WHERE status=$project_status ORDER BY register_time";

        return $this->retrieve($sql);
    }

    public function searchProjectsUserIsAdmin($user_id)
    {
        return $this->searchActiveProjectsByUserStatus($user_id, "AND user_group.admin_flags = 'A'");
    }

    public function searchActiveProjectsForUser($user_id)
    {
        return $this->searchActiveProjectsByUserStatus($user_id);
    }

    private function searchActiveProjectsByUserStatus($user_id, $where = '')
    {
        $user_id = $this->da->escapeInt($user_id);
        $sql     = "SELECT `groups`.*
            FROM `groups`
              JOIN user_group USING (group_id)
            WHERE user_group.user_id = $user_id
              $where
              AND `groups`.status='A'
            ORDER BY `groups`.group_name ASC";
        return $this->retrieve($sql);
    }

    public function searchAllActiveProjectsForUser($user_id)
    {
        $user_id = $this->da->escapeInt($user_id);

        $sql = "SELECT `groups`.*
        FROM `groups`
          JOIN user_group USING (group_id)
        WHERE user_group.user_id = $user_id
          AND `groups`.status='A'

        UNION DISTINCT

        SELECT `groups`.*
        FROM `groups`
          INNER JOIN ugroup USING (group_id)
          INNER JOIN ugroup_user USING (ugroup_id)
        WHERE ugroup_user.user_id = $user_id
          AND `groups`.status='A'";

        return $this->retrieve($sql);
    }

    public function updateStatus($id, $status)
    {
        $sql = 'UPDATE `groups`' .
            ' SET status = ' . $this->da->quoteSmart($status) .
            ' WHERE group_id = ' . $this->da->escapeInt($id);
        return $this->update($sql);
    }

    /**
     * Update the http_domain and service when renaming the group
     * @param Project $project
     * @param String  $new_name
     * @return bool
     */
    public function renameProject($project, $new_name)
    {
        //Update 'groups' table
        $sql        = ' UPDATE `groups` SET unix_group_name= ' . $this->da->quoteSmart($new_name) . ' ,
                 http_domain=REPLACE (http_domain,' . $this->da->quoteSmart($project->getUnixName(false)) . ',' . $this->da->quoteSmart($new_name) . ')
                 WHERE group_id= ' . $this->da->quoteSmart($project->getID());
        $res_groups = $this->update($sql);

        //Update 'service' table
        if ($res_groups) {
            $sql_summary = ' UPDATE service SET link= REPLACE (link,' . $this->da->quoteSmart($project->getUnixName()) . ',' . $this->da->quoteSmart(strtolower($new_name)) . ')
                              WHERE short_name="summary"
                              AND group_id= ' . $this->da->quoteSmart($project->getID());
            $res_summary = $this->update($sql_summary);
            if ($res_summary) {
                $sql_homePage = ' UPDATE service SET link= REPLACE (link,' . $this->da->quoteSmart($project->getUnixName()) . ',' . $this->da->quoteSmart(strtolower($new_name)) . ')
                                  WHERE short_name="homepage"
                                  AND group_id= ' . $this->da->quoteSmart($project->getID());
                return $this->update($sql_homePage);
            }
        }
        return false;
    }

    /**
     * Return all projects matching given parameters
     *
     * @return Array ('projects' => DataAccessResult, 'numrows' => int)
     */
    public function returnAllProjects($offset, $limit, $status = false, $groupName = false)
    {
        $cond          = [];
        $project_limit = "";
        if ($limit != 0) {
            $project_limit .= ' LIMIT ' . $this->da->escapeInt($offset) . ', ' . $this->da->escapeInt($limit);
        }
        if (is_array($status)) {
            if (! empty($status)) {
                $cond[] = 'status IN (' . $this->da->quoteSmartImplode(',', $status) . ')';
            }
        } else {
            if ($status != false) {
                $cond[] = 'status=' . $this->da->quoteSmart($status);
            }
        }

        if ($groupName != false) {
            $pattern = $this->da->quoteSmart('%' . $groupName . '%');
            $cond[]  = '(group_name LIKE ' . $pattern . ' OR group_id LIKE ' . $pattern . ' OR unix_group_name LIKE ' . $pattern . ')';
        }

        if (count($cond) > 0) {
            $stm = ' WHERE ' . implode(' AND ', $cond);
        } else {
            $stm = '';
        }

        $sql = 'SELECT SQL_CALC_FOUND_ROWS *
                FROM `groups` ' . $stm . '
                ORDER BY group_name
                ASC ' . $project_limit;

        return ['projects' => $this->retrieve($sql), 'numrows' => $this->foundRows()];
    }

    public function searchProjectsWithNumberOfMembers(
        $offset,
        $limit,
        $status = false,
        $project_name = false,
    ) {
        $conditions = [];
        $offset     = $this->da->escapeInt($offset);
        $limit      = $this->da->escapeInt($limit);

        if (is_array($status)) {
            if (! empty($status)) {
                $conditions[] = 'status IN (' . $this->da->quoteSmartImplode(',', $status) . ')';
            }
        } elseif ($status != false) {
            $conditions[] = 'status = ' . $this->da->quoteSmart($status);
        }

        if ($project_name != false) {
            $pattern      = $this->da->quoteSmart('%' . $project_name . '%');
            $conditions[] = "(project.group_name LIKE $pattern OR project.group_id LIKE $pattern OR project.unix_group_name LIKE $pattern)";
        }

        if (count($conditions) > 0) {
            $where_condition = 'WHERE ' . implode(' AND ', $conditions);
        } else {
            $where_condition = '';
        }

        $sql = "
            SELECT SQL_CALC_FOUND_ROWS project.*, count(user_group.user_id) as nb_members
            FROM `groups` AS project
            LEFT JOIN user_group USING (group_id)
            $where_condition
            GROUP BY project.group_id
            ORDER BY project.group_name ASC
            LIMIT $offset, $limit
        ";

        return $this->retrieve($sql);
    }

    public function getMyAndPublicProjectsForREST(PFUser $user, $offset, $limit)
    {
        $user_id = $this->da->escapeInt($user->getId());
        $offset  = $this->da->escapeInt($offset);
        $limit   = $this->da->escapeInt($limit);

        $private_type               = $this->da->quoteSmart(Project::ACCESS_PRIVATE);
        $private_wo_restricted_type = $this->da->quoteSmart(Project::ACCESS_PRIVATE_WO_RESTRICTED);

        if ($user->isSuperUser()) {
            $sql = "SELECT SQL_CALC_FOUND_ROWS `groups`.*
                    FROM `groups`
                    WHERE status = 'A'
                      AND group_id > 100
                    ORDER BY group_id ASC
                    LIMIT $offset, $limit";
        } else {
            $sql = "SELECT SQL_CALC_FOUND_ROWS DISTINCT `groups`.*
                    FROM `groups`
                      JOIN user_group USING (group_id)
                    WHERE status = 'A'
                      AND group_id > 100
                      AND (access NOT IN ($private_type, $private_wo_restricted_type)
                        OR user_group.user_id = $user_id)
                    ORDER BY group_id ASC
                    LIMIT $offset, $limit";
        }

        return $this->retrieve($sql);
    }

    public function getMyProjectsForREST(PFUser $user, $offset, $limit)
    {
        $user_id = $this->da->escapeInt($user->getId());
        $offset  = $this->da->escapeInt($offset);
        $limit   = $this->da->escapeInt($limit);

        $sql = "SELECT SQL_CALC_FOUND_ROWS DISTINCT `groups`.*
                    FROM `groups`
                      JOIN user_group USING (group_id)
                    WHERE status = 'A'
                      AND group_id > 100
                      AND user_group.user_id = $user_id
                    ORDER BY group_id ASC
                    LIMIT $offset, $limit";

        return $this->retrieve($sql);
    }

    public function getProjectICanAdminForREST(PFUser $user, int $offset, int $limit)
    {
        $user_id = $this->da->escapeInt($user->getId());
        $offset  = $this->da->escapeInt($offset);
        $limit   = $this->da->escapeInt($limit);

        $sql = "SELECT SQL_CALC_FOUND_ROWS DISTINCT `groups`.*
                    FROM `groups`
                      JOIN user_group USING (group_id)
                    WHERE status = 'A'
                      AND user_group.admin_flags = 'A'
                      AND user_group.user_id = $user_id
                    ORDER BY group_id ASC
                    LIMIT $offset, $limit";

        return $this->retrieve($sql);
    }

    public function searchMyAndPublicProjectsForRESTByShortname($shortname, PFUser $user, $offset, $limit)
    {
        $user_id   = $this->da->escapeInt($user->getId());
        $offset    = $this->da->escapeInt($offset);
        $limit     = $this->da->escapeInt($limit);
        $shortname = $this->da->quoteSmart($shortname);

        $private_type               = $this->da->quoteSmart(Project::ACCESS_PRIVATE);
        $private_wo_restricted_type = $this->da->quoteSmart(Project::ACCESS_PRIVATE_WO_RESTRICTED);

        if ($user->isSuperUser()) {
            $sql = "SELECT SQL_CALC_FOUND_ROWS `groups`.*
                    FROM `groups`
                    WHERE status = 'A'
                      AND group_id > 100
                      AND unix_group_name = $shortname
                    ORDER BY group_id ASC
                    LIMIT $offset, $limit";
        } else {
            $sql = "SELECT SQL_CALC_FOUND_ROWS DISTINCT `groups`.*
                    FROM `groups`
                      JOIN user_group USING (group_id)
                    WHERE status = 'A'
                      AND group_id > 100
                      AND unix_group_name = $shortname
                      AND (access NOT IN ($private_type, $private_wo_restricted_type)
                        OR user_group.user_id = $user_id)
                    ORDER BY group_id ASC
                    LIMIT $offset, $limit";
        }

        return $this->retrieve($sql);
    }

    public function searchByPublicStatus($is_public)
    {
        if ($is_public) {
            $access_clause = 'access NOT IN(' .
                $this->da->quoteSmart(Project::ACCESS_PRIVATE) . ', ' .
                $this->da->quoteSmart(Project::ACCESS_PRIVATE_WO_RESTRICTED) .
                ')';
        } else {
            $access_clause = 'access IN(' .
                $this->da->quoteSmart(Project::ACCESS_PRIVATE) . ', ' .
                $this->da->quoteSmart(Project::ACCESS_PRIVATE_WO_RESTRICTED) .
                ')';
        }

        $sql = "SELECT *
                FROM `groups`
                WHERE $access_clause
                AND status = 'A'";
        return $this->retrieve($sql);
    }

    /**
     * Filled the ugroups to be notified when admin action is needed
     *
     * @param int $groupId
     * @param Array   $ugroups
     *
     * @return bool
     */
    public function setMembershipRequestNotificationUGroup($groupId, $ugroups)
    {
        $sql = ' DELETE FROM groups_notif_delegation WHERE group_id =' . $this->da->quoteSmart($groupId);
        if (! $this->update($sql)) {
            return false;
        }
        foreach ($ugroups as $ugroupId) {
            $sql = ' INSERT INTO groups_notif_delegation (group_id, ugroup_id)
                 VALUE (' . $this->da->quoteSmart($groupId) . ', ' . $this->da->quoteSmart($ugroupId) . ')
                 ON DUPLICATE KEY UPDATE ugroup_id = ' . $this->da->quoteSmart($ugroupId);
            if (! $this->update($sql)) {
                return false;
            }
        }
        return true;
    }

     /**
     * Returns the ugroup to be notified when admin action is needed for given project
     *
     * @param int $groupId
     *
     * @return \Tuleap\DB\Compat\Legacy2018\LegacyDataAccessResultInterface
     */
    public function getMembershipRequestNotificationUGroup($groupId)
    {
        $sql = ' SELECT ugroup_id FROM groups_notif_delegation WHERE group_id = ' . $this->da->quoteSmart($groupId);
        return $this->retrieve($sql);
    }

    /**
     * Deletes the ugroup to be notified for given project
     *
     * @param int $groupId
     *
     * @return bool
     */
    public function deleteMembershipRequestNotificationUGroup($groupId)
    {
        $groupId = $this->da->escapeInt($groupId);
        $sql     = 'DELETE FROM groups_notif_delegation WHERE group_id = ' . $groupId;
        return $this->update($sql);
    }

    /**
     * Deletes the message set for a given project
     *
     * @param int $groupId
     *
     * @return bool
     */
    public function deleteMembershipRequestNotificationMessage($groupId)
    {
        $groupId = $this->da->escapeInt($groupId);
        $sql     = 'DELETE FROM groups_notif_delegation_message WHERE group_id = ' . $groupId;
        return $this->update($sql);
    }

    /**
     * Returns the message to be displayed to requester asking access for a given project
     *
     * @param int $groupId
     *
     * @return \Tuleap\DB\Compat\Legacy2018\LegacyDataAccessResultInterface
     */
    public function getMessageToRequesterForAccessProject($groupId)
    {
        $sql = 'SELECT msg_to_requester FROM groups_notif_delegation_message WHERE group_id=' . $this->da->quoteSmart($groupId);
        return $this->retrieve($sql);
    }

    /**
     * Updates the message to be displayed to requester asking access for a given project
     *
     * @param int $groupId
     * @param String  $message
     */
    public function setMessageToRequesterForAccessProject($groupId, $message)
    {
        $sql = 'INSERT INTO groups_notif_delegation_message (group_id, msg_to_requester) VALUES (' . $this->da->quoteSmart($groupId) . ', ' . $this->da->quoteSmart($message) . ')' .
                ' ON DUPLICATE KEY UPDATE msg_to_requester=' . $this->da->quoteSmart($message);
        return $this->update($sql);
    }

    public function searchGlobal($words, $offset, $exact)
    {
        return $this->searchGlobalPaginated($words, $offset, $exact, 26);
    }

    public function searchGlobalPaginated($words, $offset, $exact, $limit)
    {
        return $this->searchGlobalParams($words, $offset, $exact, '', '', $limit);
    }

    public function searchGlobalForRestrictedUsers($words, $offset, $exact, $user_id)
    {
        return $this->searchGlobalPaginatedForRestrictedUsers($words, $offset, $exact, $user_id, 26);
    }

    public function searchGlobalPaginatedForRestrictedUsers($words, $offset, $exact, $user_id, $limit)
    {
        $user_id = $this->da->escapeInt($user_id);
        $from    = " JOIN user_group ON (user_group.group_id = `groups`.group_id)";
        $where   = " AND user_group.user_id = $user_id";
        return $this->searchGlobalParams($words, $offset, $exact, $from, $where, $limit);
    }

    private function searchGlobalParams($words, $offset, $exact, $from = '', $where = '', $limit = 26)
    {
        $offset = $this->da->escapeInt($offset);
        $limit  = $this->da->escapeInt($limit);
        if ($exact === true) {
            $group_name = $this->searchExactMatch($words);
            $short_desc = $this->searchExactMatch($words);
            $long_desc  = $this->searchExactMatch($words);
        } else {
            $group_name = $this->searchExplodeMatch('group_name', $words);
            $short_desc = $this->searchExplodeMatch('short_description', $words);
            $long_desc  = $this->searchExplodeMatch('unix_group_name', $words);
        }

        $private               = $this->da->quoteSmart(Project::ACCESS_PRIVATE);
        $private_wo_restricted = $this->da->quoteSmart(Project::ACCESS_PRIVATE_WO_RESTRICTED);

        $sql = "SELECT DISTINCT group_name, unix_group_name, `groups`.group_id, short_description
                FROM `groups`
                    LEFT JOIN group_desc_value ON (group_desc_value.group_id = `groups`.group_id)
                    $from
                WHERE status='A'
                AND access NOT IN ($private, $private_wo_restricted)
                AND (
                        (group_name LIKE $group_name)
                     OR (short_description LIKE $short_desc)
                     OR (unix_group_name LIKE $long_desc)
                     OR (group_desc_value.value LIKE $long_desc)
                )
                $where
                LIMIT $offset, $limit";

        return $this->retrieve($sql);
    }

    public function setIsPrivate($project_id)
    {
        $access     = $this->da->quoteSmart(Project::ACCESS_PRIVATE);
        $project_id = $this->da->escapeInt($project_id);

        $sql = "UPDATE `groups` SET access = $access WHERE group_id = $project_id";

        return $this->update($sql);
    }

    public function setIsPrivateWORestricted($project_id)
    {
        $access     = $this->da->quoteSmart(Project::ACCESS_PRIVATE_WO_RESTRICTED);
        $project_id = $this->da->escapeInt($project_id);

        $sql = "UPDATE `groups` SET access = $access WHERE group_id = $project_id";

        return $this->update($sql);
    }

    public function setIsPublic($project_id)
    {
        $project_id = $this->da->escapeInt($project_id);
        $access     = $this->da->quoteSmart(Project::ACCESS_PUBLIC);

        $sql = "UPDATE `groups` SET access = $access WHERE group_id = $project_id";

        return $this->update($sql);
    }

    public function setUnrestricted($project_id)
    {
        $project_id = $this->da->escapeInt($project_id);
        $access     = $this->da->quoteSmart(Project::ACCESS_PUBLIC_UNRESTRICTED);

        $sql = "UPDATE `groups` SET access = $access WHERE group_id = $project_id";

        return $this->update($sql);
    }

    public function switchUnrestrictedToPublic()
    {
        return $this->switchAccess(Project::ACCESS_PUBLIC_UNRESTRICTED, Project::ACCESS_PUBLIC);
    }

    public function switchPrivateWithoutRestrictedToPrivate()
    {
        return $this->switchAccess(Project::ACCESS_PRIVATE_WO_RESTRICTED, Project::ACCESS_PRIVATE);
    }

    private function switchAccess($old, $new)
    {
        $new_access = $this->da->quoteSmart($new);
        $old_access = $this->da->quoteSmart($old);
        $sql        = "UPDATE `groups` SET access = $new_access WHERE access = $old_access";

        return $this->update($sql);
    }

    public function setTruncatedEmailsUsage($project_id, $usage)
    {
        $project_id = $this->da->escapeInt($project_id);
        $usage      = $this->da->escapeInt($usage);

        $sql = "UPDATE `groups` SET truncated_emails = $usage WHERE group_id = $project_id";

        return $this->update($sql);
    }

    public function getProjectMembers($project_id)
    {
        $project_id                 = $this->da->quoteSmart($project_id);
        $private_without_restricted = $this->da->quoteSmart(Project::ACCESS_PRIVATE_WO_RESTRICTED);

        return $this->retrieve(
            "SELECT user.user_id AS user_id, user.user_name AS user_name, user.realname AS realname
             FROM user_group INNER JOIN user USING(user_id) INNER JOIN `groups` USING(group_id)
             WHERE user_group.group_id = $project_id
                 AND (
                    user.status = 'A'
                    OR (
                        user.status = 'R'
                        AND `groups`.access <> $private_without_restricted
                    )
                 )
             "
        );
    }

    public function getProjectsWithStatusForREST($project_status, $offset, $limit)
    {
        $project_status = $this->da->quoteSmart($project_status);
        $offset         = $this->da->escapeInt($offset);
        $limit          = $this->da->escapeInt($limit);

        $sql = "SELECT SQL_CALC_FOUND_ROWS `groups`.*
                FROM `groups`
                WHERE status = $project_status
                  AND group_id > 100
                ORDER BY group_id ASC
                LIMIT $offset, $limit";

        return $this->retrieve($sql);
    }

    public function getProjectsGroupByStatus()
    {
        return $this->retrieve("SELECT status, count(*) AS project_nb FROM `groups` GROUP BY status");
    }

    public function countProjectRegisteredBefore($timestamp)
    {
        $timestamp = $this->da->escapeInt($timestamp);
        $status    = $this->da->quoteSmart(Project::STATUS_ACTIVE);

        $sql = "SELECT count(*) AS nb FROM `groups` WHERE register_time >= $timestamp AND status = $status";

        $row = $this->retrieve($sql)->getRow();

        return $row['nb'];
    }
}
