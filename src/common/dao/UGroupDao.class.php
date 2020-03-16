<?php
/**
 * Copyright (c) Enalean, 2017 - Present. All Rights Reserved.
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
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

require_once('include/DataAccessObject.class.php');

/**
 *  Data Access Object for ProjectUGroup
 */
class UGroupDao extends DataAccessObject
{

    /**
     * Searches static ProjectUGroup by GroupId
     * return all static ugroups
     *
     * @param int $group_id Id of the project
     *
     * @return DataAccessResult
     */
    public function searchByGroupId($group_id)
    {
        $group_id = $this->da->escapeInt($group_id);
        $sql = "SELECT * 
                FROM ugroup 
                WHERE group_id = $group_id ORDER BY name";
        return $this->retrieve($sql);
    }

    /**
     * Searches by ugroup id
     *
     * @param int $ugroup_id Id of the ugroup
     *
     * @return DataAccessResult
     */
    public function searchByUGroupId($ugroup_id)
    {
        $ugroup_id = $this->da->escapeInt($ugroup_id);
        $sql = "SELECT * 
                FROM ugroup 
                WHERE ugroup_id = $ugroup_id ORDER BY name";
        return $this->retrieve($sql);
    }

    public function searchByListOfUGroupsId(array $ugroup_ids)
    {
        $ugroup_ids = $this->da->quoteSmartImplode(',', $ugroup_ids);

        $sql = "SELECT *
                FROM ugroup
                WHERE ugroup_id IN ($ugroup_ids)";

        return $this->retrieve($sql);
    }

    public function searchDynamicAndStaticByGroupId($group_id)
    {
        $group_id = $this->da->escapeInt($group_id);
        $sql = "SELECT * 
                FROM ugroup 
                WHERE group_id = $group_id OR (group_id = 100 and ugroup_id <= 100)
                ORDER BY ugroup_id";
        return $this->retrieve($sql);
    }

    public function searchStaticByGroupId($group_id)
    {
        $group_id = $this->da->escapeInt($group_id);
        $sql = "SELECT *
                FROM ugroup
                WHERE group_id = $group_id
                ORDER BY ugroup_id";
        return $this->retrieve($sql);
    }

    public function searchByGroupIdAndUGroupId($group_id, $ugroup_id)
    {
        $group_id  = $this->da->escapeInt($group_id);
        $ugroup_id = $this->da->escapeInt($ugroup_id);
        $sql = "SELECT * 
                FROM ugroup 
                WHERE group_id = $group_id AND ugroup_id = $ugroup_id";
        return $this->retrieve($sql);
    }

    public function searchNameByGroupIdAndUGroupId($project_id, $ugroup_id)
    {
        $project_id = $this->da->escapeInt($project_id);
        $ugroup_id  = $this->da->escapeInt($ugroup_id);
        $sql = "SELECT name
                FROM ugroup
                WHERE (group_id = $project_id OR group_id = 100)
                  AND ugroup_id = $ugroup_id";
        return $this->retrieve($sql);
    }

    public function searchByGroupIdAndName($group_id, $name)
    {
        $group_id  = $this->da->escapeInt($group_id);
        $name      = $this->da->quoteSmart($name);
        $sql = "SELECT *
                FROM ugroup
                WHERE group_id = $group_id AND name = $name";
        return $this->retrieve($sql);
    }

    /**
     * Searches group that user belongs to one of its static ugroup
     * return all groups
     *
     * @param int $userId Id of the user
     *
     * @return DataAccessResult
     */
    public function searchGroupByUserId($userId)
    {
        $user_id = $this->da->escapeInt($userId);
        $sql = "SELECT groups.group_id
                  FROM ugroup
                  JOIN ugroup_user USING (ugroup_id)
                  JOIN groups USING (group_id)
                WHERE user_id = $user_id
                AND status != 'D'
                ORDER BY group_name";
        return $this->retrieve($sql);
    }

    /**
     * Return all UGroups the user belongs to (cross projects)
     *
     * @param Integrer $user_id Id of user
     *
     * @return DataAccessResult
     */
    public function searchByUserId($user_id)
    {
        $user_id = $this->da->quoteSmart($user_id);

        $sql = "SELECT ug.*
                FROM ugroup_user AS ug_u
                    INNER JOIN ugroup AS ug USING (ugroup_id)
                WHERE ug_u.user_id = $user_id";

        return $this->retrieve($sql);
    }

    public function searchByUserIdTakingAccountUserProjectMembership($user_id)
    {
        $user_id               = $this->da->quoteSmart($user_id);
        $restricted            = $this->da->quoteSmart(ForgeAccess::RESTRICTED);
        $unrestricted          = $this->da->quoteSmart(Project::ACCESS_PUBLIC_UNRESTRICTED);
        $public                = $this->da->quoteSmart(Project::ACCESS_PUBLIC);
        $private               = $this->da->quoteSmart(Project::ACCESS_PRIVATE);
        $private_wo_restricted = $this->da->quoteSmart(Project::ACCESS_PRIVATE_WO_RESTRICTED);

        $sql = "SELECT ug.*
                FROM ugroup_user AS ug_u
                     INNER JOIN user USING (user_id)
                     INNER JOIN ugroup AS ug USING (ugroup_id)
                     INNER JOIN groups AS g USING (group_id)
                     LEFT JOIN user_group USING(group_id, user_id)
                     INNER JOIN forgeconfig ON (forgeconfig.name = 'access_mode')
                WHERE ug_u.user_id = $user_id
                  AND (
                    (
                        forgeconfig.value = $restricted
                        AND (
                            user_group.group_id IS NOT NULL
                            OR
                            user.status = 'A' AND g.access IN ($public, $unrestricted)
                            OR
                            user.status = 'R' AND g.access = $unrestricted
                        )
                    )
                    OR
                    (
                        forgeconfig.value <> $restricted
                        AND (
                            user_group.group_id IS NOT NULL
                            OR
                            g.access NOT IN ($private, $private_wo_restricted)
                        )
                    )
                  )";

        return $this->retrieve($sql);
    }

    /**
     * Checks ProjectUGroup  validity by GroupId
     *
     * @param int $groupId The group id
     * @param int $ugroupId The ugroup id
     *
     * @return bool
     */
    public function checkUGroupValidityByGroupId($groupId, $ugroupId)
    {
        $groupId = $this->da->escapeInt($groupId);
        $ugroupId = $this->da->escapeInt($ugroupId);

        $sql = 'SELECT NULL
                FROM ugroup 
                WHERE group_id = ' . $groupId . ' AND ugroup_id = ' . $ugroupId;
        $res = $this->retrieve($sql);
        if ($res && !$res->isError() && $res->rowCount() == 1) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Update binding option for a given ProjectUGroup
     *
     * @param int $ugroupId The bound ugroup id
     * @param int $sourceId The ugroup id we want to clone
     *
     * @return bool
     */
    public function updateUgroupBinding($ugroupId, $sourceId = null)
    {
        $ugroupId = $this->da->escapeInt($ugroupId);
        if (isset($sourceId)) {
            $sourceId      = $this->da->escapeInt($sourceId);
            $bindingclause = " SET source_id = " . $sourceId;
        } else {
            $bindingclause = " SET source_id = NULL";
        }
        $sql = "UPDATE ugroup " . $bindingclause . " WHERE ugroup_id = " . $ugroupId;
         return $this->update($sql);
    }

    /**
     * Retrieve all bound UGroups of a given ProjectUGroup
     *
     * @param int $sourceId The source ugroup id
     *
     * @return DataAccessResult
     */
    public function searchUGroupByBindingSource($sourceId)
    {
        $ugroupId = $this->da->escapeInt($sourceId);
        $sql      = "SELECT * FROM ugroup WHERE source_id = " . $sourceId;
        return $this->retrieve($sql);
    }

    /**
     * @return DataAccessResult
     */
    public function searchBindedUgroupsInProject($project_id)
    {
        $project_id = $this->da->escapeInt($project_id);

        $sql = "SELECT *
                FROM ugroup
                WHERE group_id = $project_id
                AND source_id IS NOT NULL";

        return $this->retrieve($sql);
    }

    /**
     * Retrieve the source user group from a given bound ugroup id
     *
     * @param int $ugroupId The source ugroup id
     *
     * @return DataAccessResult
     */
    public function getUgroupBindingSource($ugroupId)
    {
        $ugroupId = $this->da->escapeInt($ugroupId);
        $sql      = "SELECT source.*
                     FROM ugroup u 
                       JOIN ugroup source ON (source.ugroup_id = u.source_id)
                     WHERE u.ugroup_id = " . $ugroupId;
        return $this->retrieve($sql);
    }

    public function createUgroupFromSourceUgroup($ugroup_id, $new_project_id)
    {
        $ugroup_id      = $this->da->escapeInt($ugroup_id);
        $new_project_id = $this->da->escapeInt($new_project_id);

        $create_ugroup = "INSERT INTO ugroup (name,description,group_id)
            SELECT name,description,$new_project_id
            FROM ugroup
            WHERE ugroup_id=$ugroup_id";

        return $this->updateAndGetLastId($create_ugroup);
    }

    public function createBinding($new_project_id, $ugroup_id, $new_ugroup_id)
    {
        $ugroup_id     = $this->da->escapeInt($ugroup_id);
        $new_ugroup_id = $this->da->escapeInt($new_ugroup_id);
        $new_project_id = $this->da->escapeInt($new_project_id);

        $create_binding = "INSERT INTO ugroup_mapping (to_group_id, src_ugroup_id, dst_ugroup_id)
                           VALUES ($new_project_id, $ugroup_id, $new_ugroup_id)";

        return $this->update($create_binding);
    }

    public function searchUgroupsUserIsMemberInProject($user_id, $project_id)
    {
        $user_id    = $this->da->escapeInt($user_id);
        $project_id = $this->da->escapeInt($project_id);
        $member_id  = $this->da->escapeInt(ProjectUGroup::PROJECT_MEMBERS);
        $admin_id   = $this->da->escapeInt(ProjectUGroup::PROJECT_ADMIN);

        $sql = "SELECT ugroup.*
                FROM ugroup
                    INNER JOIN user_group ON (
                        ugroup.ugroup_id = $member_id
                        AND ugroup.group_id = 100
                        AND user_group.user_id = $user_id
                        AND user_group.group_id = $project_id
                    )
                UNION
                SELECT ugroup.*
                FROM ugroup
                    INNER JOIN user_group ON (
                        ugroup.ugroup_id = $admin_id
                        AND ugroup.group_id = 100
                        AND user_group.user_id = $user_id
                        AND user_group.group_id = $project_id
                        AND user_group.admin_flags = 'A'
                    )
                UNION
                SELECT ugroup.*
                FROM ugroup
                    INNER JOIN ugroup_user ON (
                        ugroup.ugroup_id = ugroup_user.ugroup_id
                        AND ugroup_user.user_id = $user_id
                        AND ugroup.group_id = $project_id
                    )
                ";

        return $this->retrieve($sql);
    }

    public function searchUgroupsForAdministratorInProject($user_id, $project_id)
    {
        $user_id    = $this->da->escapeInt($user_id);
        $project_id = $this->da->escapeInt($project_id);
        $member_id  = $this->da->escapeInt(ProjectUGroup::PROJECT_MEMBERS);
        $admin_id   = $this->da->escapeInt(ProjectUGroup::PROJECT_ADMIN);

        $sql = "SELECT ugroup.*
                FROM ugroup
                    INNER JOIN user_group ON (
                        ugroup.ugroup_id = $member_id
                        AND ugroup.group_id = 100
                        AND user_group.user_id = $user_id
                        AND user_group.group_id = $project_id
                    )
                UNION
                SELECT ugroup.*
                FROM ugroup
                    INNER JOIN user_group ON (
                        ugroup.ugroup_id = $admin_id
                        AND ugroup.group_id = 100
                        AND user_group.user_id = $user_id
                        AND user_group.group_id = $project_id
                        AND user_group.admin_flags = 'A'
                    )
                UNION
                SELECT ugroup.*
                FROM ugroup
                WHERE ugroup.group_id = $project_id
                ";

        return $this->retrieve($sql);
    }
}
