<?php
/**
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
 * Copyright (c) Enalean, 2017-2018. All Rights Reserved.
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

class Tracker_ReportDao extends DataAccessObject
{
    public function __construct()
    {
        parent::__construct();
        $this->table_name = 'tracker_report';
    }

    public function searchById($id, $user_id)
    {
        $id      = $this->da->escapeInt($id);
        $user_id = $this->da->escapeInt($user_id);
        $sql = "SELECT *
                FROM $this->table_name
                WHERE id = $id
                  AND (user_id IS NULL
                      OR user_id = $user_id)";
        return $this->retrieve($sql);
    }

    public function searchByTrackerId($tracker_id, $user_id)
    {
        $tracker_id = $this->da->escapeInt($tracker_id);
        $user_stm   = " ";
        if ($user_id) {
            $user_stm = "user_id = " . $this->da->escapeInt($user_id) . " OR ";
        }

        $sql = "SELECT *
                FROM $this->table_name
                WHERE tracker_id = $tracker_id
                  AND ($user_stm user_id IS NULL)
                ORDER BY name";
        return $this->retrieve($sql);
    }
    public function searchDefaultByTrackerId($tracker_id)
    {
        $tracker_id = $this->da->escapeInt($tracker_id);
        $sql = "SELECT *
                FROM $this->table_name
                WHERE tracker_id = $tracker_id
                  AND user_id IS NULL
                ORDER BY is_default DESC, name ASC
                LIMIT 1";
        return $this->retrieve($sql);
    }

    public function searchDefaultReportByTrackerId($tracker_id)
    {
        $tracker_id = $this->da->escapeInt($tracker_id);
        $sql = "SELECT *
                FROM $this->table_name
                WHERE tracker_id = $tracker_id
                  AND is_default = 1";
        return $this->retrieve($sql);
    }

    public function searchByUserId($user_id)
    {
        $user_id = $user_id ? '= ' . $this->da->escapeInt($user_id) : 'IS NULL';

        $sql = "SELECT *
                FROM $this->table_name
                WHERE user_id $user_id
                ORDER BY name";
        return $this->retrieve($sql);
    }

    public function create(
        $name,
        $description,
        $current_renderer_id,
        $parent_report_id,
        $user_id,
        $is_default,
        $tracker_id,
        $is_query_displayed,
        $is_in_expert_mode,
        $expert_query
    ) {
        $name                = $this->da->quoteSmart($name);
        $description         = $this->da->quoteSmart($description);
        $current_renderer_id = $this->da->escapeInt($current_renderer_id);
        $parent_report_id    = $this->da->escapeInt($parent_report_id);
        $user_id             = $user_id ? $this->da->escapeInt($user_id) : 'NULL';
        $is_default          = $this->da->escapeInt($is_default);
        $tracker_id          = $this->da->escapeInt($tracker_id);
        $is_query_displayed  = $this->da->escapeInt($is_query_displayed);
        $is_in_expert_mode   = $this->da->escapeInt($is_in_expert_mode);
        $expert_query        = $this->da->quoteSmart($expert_query);
        $sql = "INSERT INTO $this->table_name
                (name, description, current_renderer_id, parent_report_id, user_id, is_default, tracker_id, is_query_displayed, is_in_expert_mode, expert_query)
                VALUES ($name, $description, $current_renderer_id, $parent_report_id, $user_id, $is_default, $tracker_id, $is_query_displayed, $is_in_expert_mode, $expert_query)";
        return $this->updateAndGetLastId($sql);
    }

    public function save(
        $id,
        $name,
        $description,
        $current_renderer_id,
        $parent_report_id,
        $user_id,
        $is_default,
        $tracker_id,
        $is_query_displayed,
        $is_in_expert_mode,
        $expert_query,
        $updated_by_id
    ) {
        $id                  = $this->da->escapeInt($id);
        $name                = $this->da->quoteSmart($name);
        $description         = $this->da->quoteSmart($description);
        $current_renderer_id = $this->da->escapeInt($current_renderer_id);
        $parent_report_id    = $parent_report_id ? $this->da->escapeInt($parent_report_id) : 'NULL';
        $user_id             = $user_id ? $this->da->escapeInt($user_id) : 'NULL';
        $is_default          = $this->da->escapeInt($is_default);
        $tracker_id          = $this->da->escapeInt($tracker_id);
        $is_query_displayed  = $this->da->escapeInt($is_query_displayed);
        $is_in_expert_mode   = $this->da->escapeInt($is_in_expert_mode);
        $expert_query        = $this->da->quoteSmart($expert_query);
        $updated_by_id       = $this->da->escapeInt($updated_by_id);
        $updated_at          = $_SERVER['REQUEST_TIME'];
        $sql = "UPDATE $this->table_name SET
                   name                = $name,
                   description         = $description,
                   current_renderer_id = $current_renderer_id,
                   parent_report_id    = $parent_report_id,
                   user_id             = $user_id,
                   is_default          = $is_default,
                   tracker_id          = $tracker_id,
                   is_query_displayed  = $is_query_displayed,
                   is_in_expert_mode   = $is_in_expert_mode,
                   expert_query        = $expert_query,
                   updated_by          = $updated_by_id,
                   updated_at          = $updated_at
                WHERE id = $id ";
        return $this->update($sql);
    }

    public function delete($id)
    {
        $sql = "DELETE FROM $this->table_name WHERE id = " . $this->da->escapeInt($id);
        return $this->update($sql);
    }

    public function duplicate($from_report_id, $to_tracker_id)
    {
        $from_report_id = $this->da->escapeInt($from_report_id);
        $to_tracker_id  = $this->da->escapeInt($to_tracker_id);
        $sql = "INSERT INTO $this->table_name (project_id, user_id, tracker_id, is_default, name, description, current_renderer_id, parent_report_id, is_query_displayed, is_in_expert_mode, expert_query)
                SELECT project_id, user_id, $to_tracker_id, is_default, name, description, current_renderer_id, $from_report_id, is_query_displayed, is_in_expert_mode, expert_query
                FROM $this->table_name
                WHERE id = $from_report_id";
        return $this->updateAndGetLastId($sql);
    }


    /**
     * Not really report table specific but we have to find a place.
     * Search for matching artifacts of a report.
     *
     * @param int   $group_id         The id of the project
     * @param int   $tracker_id       The id of the tracker
     * @param array $additional_from  If you have to join on some table put them here
     * @param array $additional_where If you have to select the results, help yourself!
     * @param bool  $user_is_admin True if the user is superuser
     * @param array $permissions
     * @param string $ugroups          Ugroups of the current user to check the permissions
     * @param array $static_ugroups
     * @param array $dynamic_ugroups
     * @param int $contributor_field_id The field id corresponding to the contributor semantic
     * @return DataAccessResult
     */
    public function searchMatchingIds($group_id, $tracker_id, $additional_from, $additional_where, PFUser $user, $permissions, $contributor_field_id)
    {
        $instances         = array('artifact_type' => $tracker_id);
        $ugroups           = $user->getUgroups($group_id, $instances);
        $static_ugroups    = $user->getStaticUgroups($group_id);
        $dynamic_ugroups   = $user->getDynamicUgroups($group_id, $instances);
        $user_is_admin     = $this->userIsAdmin($user, $group_id, $permissions, $ugroups);

        $tracker_id = $this->da->escapeInt($tracker_id);

        $from   = " FROM tracker_artifact AS artifact
                 INNER JOIN tracker_changeset AS c ON (artifact.last_changeset_id = c.id)";
        $where  = " WHERE artifact.tracker_id = $tracker_id ";

        $artifact_perms = $this->getSqlFragmentForArtifactPermissions($user_is_admin, $ugroups);
        $from  .= $artifact_perms['from'];
        $where .= $artifact_perms['where'];

        if ($this->submitterOnlyApplies($user_is_admin, $permissions, $ugroups)) {
            $where .= ' AND artifact.submitted_by = ' . $user->getId() . ' ';
        }

        if (count($additional_from)) {
            $from  .= implode("\n", $additional_from);
        }
        if (count($additional_where)) {
            $where .= ' AND ( ' . implode(' ) AND ( ', $additional_where) . ' ) ';
        }

        // $sqls => SELECT UNION SELECT UNION SELECT ...
        $sqls = $this->getSqlFragmentsAccordinglyToTrackerPermissions($user_is_admin, $from, $where, $group_id, $tracker_id, $permissions, $ugroups, $static_ugroups, $dynamic_ugroups, $contributor_field_id);

        if (count($sqls) == 0) {
            return new DataAccessResultEmpty();
        } else {
            $this->setGroupConcatLimit();
            $sql = " SELECT id, last_changeset_id";
            $sql .= " FROM (" . implode(' UNION ', $sqls) . ") AS R GROUP BY id, last_changeset_id";

            return $this->retrieve($sql);
        }
    }

    private function userIsAdmin(PFUser $user, $group_id, $permissions, $ugroups)
    {
        return $user->isSuperUser() ||
               $user->isMember($group_id, 'A') ||
               $this->hasPermissionFor(Tracker::PERMISSION_ADMIN, $permissions, $ugroups);
    }

    private function submitterOnlyApplies($user_is_admin, $permissions, $ugroups)
    {
            return $this->hasPermissionFor(Tracker::PERMISSION_SUBMITTER_ONLY, $permissions, $ugroups) &&
                ! ($user_is_admin ||
                    $this->hasPermissionFor(Tracker::PERMISSION_FULL, $permissions, $ugroups) ||
                    $this->hasPermissionFor(Tracker::PERMISSION_SUBMITTER, $permissions, $ugroups) ||
                    $this->hasPermissionFor(Tracker::PERMISSION_ASSIGNEE, $permissions, $ugroups));
    }

    public function getSqlFragmentsAccordinglyToTrackerPermissions($user_is_admin, $from, $where, $group_id, $tracker_id, $permissions, $ugroups, $static_ugroups, $dynamic_ugroups, $contributor_field_id)
    {
        $sqls = array();
        //Does the user member of at least one group which has ACCESS_FULL or is super user?
        if ($user_is_admin || $this->hasPermissionFor(Tracker::PERMISSION_FULL, $permissions, $ugroups) || $this->submitterOnlyApplies($user_is_admin, $permissions, $ugroups)) {
            $sqls[] = "SELECT c.artifact_id AS id, c.id AS last_changeset_id " . $from . " " . $where;
        } else {
            $sqls = $this->getSqlFragmentsAccordinglyToAssigneeOrSubmitterAccessPermissions($from, $where, $group_id, $tracker_id, $permissions, $ugroups, $static_ugroups, $dynamic_ugroups, $contributor_field_id);
        }
        return $sqls;
    }

    public function getSqlFragmentForArtifactPermissions($user_is_admin, array $ugroups)
    {
        $res = array('from' => '', 'where' => '');
        if (!$user_is_admin) {
            $ugroups = $this->da->quoteSmartImplode(',', $ugroups);
            $res['from']  = " LEFT JOIN permissions
                              ON (permissions.object_id = CAST(c.artifact_id AS CHAR CHARACTER SET utf8)
                                  AND permissions.permission_type = '" . Tracker_Artifact::PERMISSION_ACCESS . "')
                            ";
            $res['where'] = " AND (artifact.use_artifact_permissions = 0
                                   OR (permissions.ugroup_id IN ($ugroups)))
                       ";
        }
        return $res;
    }

    private function getSqlFragmentsAccordinglyToAssigneeOrSubmitterAccessPermissions($from, $where, $group_id, $tracker_id, $permissions, $ugroups, $static_ugroups, $dynamic_ugroups, $contributor_field_id)
    {
        $sqls = array();

        //Does the user member of at least one group which has ACCESS_SUBMITTER ?
        if ($this->hasPermissionFor(Tracker::PERMISSION_SUBMITTER, $permissions, $ugroups)) {
            $sqls = array_merge($sqls, $this->getSqlFragmentForAccessToArtifactSubmittedByGroup($from, $where, $group_id, $tracker_id, $permissions[Tracker::PERMISSION_SUBMITTER], $static_ugroups, $dynamic_ugroups));
        }

        //Does the user member of at least one group which has ACCESS_ASSIGNEE ?
        if ($contributor_field_id && $this->hasPermissionFor(Tracker::PERMISSION_ASSIGNEE, $permissions, $ugroups)) {
            $sqls = array_merge($sqls, $this->getSqlFragmentForAccessToArtifactAssignedToGroup($from, $where, $group_id, $tracker_id, $permissions[Tracker::PERMISSION_ASSIGNEE], $static_ugroups, $dynamic_ugroups, $contributor_field_id));
        }

        return $sqls;
    }

    private function hasPermissionFor($permission_type, $permissions, $ugroups)
    {
        return isset($permissions[$permission_type]) && count(array_intersect($ugroups, $permissions[$permission_type])) > 0;
    }

    private function getSqlFilterForSubmittedByGroup($from, $where, $join_user_constraint)
    {
        $sql = "SELECT c.artifact_id AS id, c.id AS last_changeset_id
                $from
                  $join_user_constraint
                $where";
        return $sql;
    }

    private function getSqlFragmentForAccessToArtifactSubmittedByGroup($from, $where, $group_id, $tracker_id, $allowed_ugroups, $static_ugroups, $dynamic_ugroups)
    {
        $sqls = array();

        $tracker_id = $this->da->escapeInt($tracker_id);
        $group_id   = $this->da->escapeInt($group_id);

        // {{{ The static ugroups
        if ($this->hasPermissionForStaticUgroup($static_ugroups, $allowed_ugroups)) {
            $ugroups              = $this->da->quoteSmartImplode(',', array_intersect($static_ugroups, $allowed_ugroups));
            $join_user_constraint = " INNER JOIN ugroup_user uu ON (
                                          artifact.submitted_by = uu.user_id
                                          AND uu.ugroup_id IN ($ugroups)) ";
            $sqls[] = $this->getSqlFilterForSubmittedByGroup($from, $where, $join_user_constraint);
        }
        // }}}

        // {{{ tracker_admins
        if ($this->hasPermissionForDynamicUgroup(ProjectUGroup::TRACKER_ADMIN, $dynamic_ugroups, $allowed_ugroups)) {
            $join_user_constraint = " INNER JOIN tracker_perm AS p ON (
                                          artifact.submitted_by = p.user_id
                                          AND p.tracker_id = $tracker_id
                                          AND p.perm_level >= 2) ";
            $sqls[] = $this->getSqlFilterForSubmittedByGroup($from, $where, $join_user_constraint);
        }
        //}}}

        // {{{ project_members
        if ($this->hasPermissionForDynamicUgroup(ProjectUGroup::PROJECT_MEMBERS, $dynamic_ugroups, $allowed_ugroups)) {
            $join_user_constraint = " INNER JOIN user_group AS ug ON (
                                          artifact.submitted_by = ug.user_id
                                          AND ug.group_id = $group_id) ";
            $sqls[] = $this->getSqlFilterForSubmittedByGroup($from, $where, $join_user_constraint);
        }
        //}}}

        // {{{ project_admins
        if ($this->hasPermissionForDynamicUgroup(ProjectUGroup::PROJECT_ADMIN, $dynamic_ugroups, $allowed_ugroups)) {
            $join_user_constraint = " INNER JOIN user_group ug ON (
                                          artifact.submitted_by = ug.user_id
                                          AND ug.group_id = $group_id
                                          AND ug.admin_flags = 'A') ";
            $sqls[] = $this->getSqlFilterForSubmittedByGroup($from, $where, $join_user_constraint);
        }
        //}}}

        return $sqls;
    }


    private function getSqlFilterForContributorGroup($from, $where, $contributor_field_id, $join_user_constraint)
    {
        $sql = "SELECT c.artifact_id AS id, c.id AS last_changeset_id
                $from
                  INNER JOIN tracker_changeset_value AS tcv ON (
                    tcv.field_id = $contributor_field_id
                    AND tcv.changeset_id = c.id)
                  INNER JOIN tracker_changeset_value_list AS tcvl ON (
                    tcvl.changeset_value_id = tcv.id)
                  $join_user_constraint
                $where";
        return $sql;
    }

    private function getSqlFragmentForAccessToArtifactAssignedToGroup($from, $where, $group_id, $tracker_id, $allowed_ugroups, $static_ugroups, $dynamic_ugroups, $contributor_field_id)
    {
        $sqls = array();

        $tracker_id           = $this->da->escapeInt($tracker_id);
        $group_id             = $this->da->escapeInt($group_id);
        $contributor_field_id = $this->da->escapeInt($contributor_field_id);

        // {{{ The static ugroups
        if ($this->hasPermissionForStaticUgroup($static_ugroups, $allowed_ugroups)) {
            $ugroups              = $this->da->quoteSmartImplode(',', array_intersect($static_ugroups, $allowed_ugroups));
            $join_user_constraint = "
                INNER JOIN ugroup_user AS uu ON (
                    uu.user_id = tcvl.bindvalue_id
                    AND uu.ugroup_id IN ($ugroups)
                )
            ";
            $sqls[] = $this->getSqlFilterForContributorGroup($from, $where, $contributor_field_id, $join_user_constraint);
        }
        // }}}

        // {{{ tracker_admins
        if ($this->hasPermissionForDynamicUgroup(ProjectUGroup::TRACKER_ADMIN, $dynamic_ugroups, $allowed_ugroups)) {
            $join_user_constraint = "
                INNER JOIN tracker_perm AS p ON (
                    p.user_id = tcvl.bindvalue_id
                    AND p.tracker_id = $tracker_id
                    AND p.perm_level >= 2
                )
            ";
            $sqls[] = $this->getSqlFilterForContributorGroup($from, $where, $contributor_field_id, $join_user_constraint);
        }
        //}}}

        // {{{ project_members
        if ($this->hasPermissionForDynamicUgroup(ProjectUGroup::PROJECT_MEMBERS, $dynamic_ugroups, $allowed_ugroups)) {
            $join_user_constraint = "
                INNER JOIN user_group AS ug ON (
                    ug.user_id = tcvl.bindvalue_id
                    AND ug.group_id = $group_id
                )
            ";
            $sqls[] = $this->getSqlFilterForContributorGroup($from, $where, $contributor_field_id, $join_user_constraint);
        }
        //}}}

        // {{{ project_admins
        if ($this->hasPermissionForDynamicUgroup(ProjectUGroup::PROJECT_ADMIN, $dynamic_ugroups, $allowed_ugroups)) {
            $join_user_constraint = "
                INNER JOIN user_group AS ug ON (
                    ug.user_id = tcvl.bindvalue_id
                    AND ug.group_id = $group_id
                    AND ug.admin_flags = 'A'
                )
            ";
            $sqls[] = $this->getSqlFilterForContributorGroup($from, $where, $contributor_field_id, $join_user_constraint);
        }
        //}}}

        return $sqls;
    }

    private function hasPermissionForStaticUgroup($static_ugroups, $allowed_ugroups)
    {
        return count(array_intersect($static_ugroups, $allowed_ugroups)) > 0;
    }

    private function hasPermissionForDynamicUgroup($ugroupId, $dynamic_ugroups, $allowed_groups)
    {
        return in_array($ugroupId, $dynamic_ugroups) &&
               in_array($ugroupId, $allowed_groups);
    }
}
