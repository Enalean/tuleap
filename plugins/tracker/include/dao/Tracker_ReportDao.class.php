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

class Tracker_ReportDao extends DataAccessObject {
    function __construct() {
        parent::__construct();
        $this->table_name = 'tracker_report';
    }
    
    function searchById($id, $user_id) {
        $id      = $this->da->escapeInt($id);
        $user_id = $this->da->escapeInt($user_id);
        $sql = "SELECT *
                FROM $this->table_name
                WHERE id = $id 
                  AND (user_id IS NULL 
                      OR user_id = $user_id)";
        return $this->retrieve($sql);
    }
    
    function searchByTrackerId($tracker_id, $user_id) {
        $tracker_id = $this->da->escapeInt($tracker_id);
        $user_stm   = " ";
        if ($user_id) {
            $user_stm = "user_id = ". $this->da->escapeInt($user_id) ." OR ";
        }
        
        $sql = "SELECT *
                FROM $this->table_name
                WHERE tracker_id = $tracker_id
                  AND ($user_stm user_id IS NULL)
                ORDER BY name";
        return $this->retrieve($sql);
    }
    function searchDefaultByTrackerId($tracker_id) {
        $tracker_id = $this->da->escapeInt($tracker_id);
        $sql = "SELECT *
                FROM $this->table_name
                WHERE tracker_id = $tracker_id
                  AND user_id IS NULL
                ORDER BY is_default DESC, name ASC
                LIMIT 1";
        return $this->retrieve($sql);
    }
    
    function searchDefaultReportByTrackerId($tracker_id) {
        $tracker_id = $this->da->escapeInt($tracker_id);
        $sql = "SELECT *
                FROM $this->table_name
                WHERE tracker_id = $tracker_id
                  AND is_default = 1";
        return $this->retrieve($sql);
    }
    
    function searchByUserId($user_id) {
        $user_id = $user_id ? '= '. $this->da->escapeInt($user_id) : 'IS NULL';
        
        $sql = "SELECT *
                FROM $this->table_name
                WHERE user_id $user_id
                ORDER BY name";
        return $this->retrieve($sql);
    }
    
    function create($name, $description, $current_renderer_id, $parent_report_id, $user_id, $is_default, $tracker_id, $is_query_displayed) {
        $name                = $this->da->quoteSmart($name);
        $description         = $this->da->quoteSmart($description);
        $current_renderer_id = $this->da->escapeInt($current_renderer_id);
        $parent_report_id    = $this->da->escapeInt($parent_report_id);
        $user_id             = $user_id ? $this->da->escapeInt($user_id) : 'NULL';
        $is_default          = $this->da->escapeInt($is_default);
        $tracker_id          = $this->da->escapeInt($tracker_id);
        $is_query_displayed  = $this->da->escapeInt($is_query_displayed);
        $sql = "INSERT INTO $this->table_name 
                (name, description, current_renderer_id, parent_report_id, user_id, is_default, tracker_id, is_query_displayed)
                VALUES ($name, $description, $current_renderer_id, $parent_report_id, $user_id, $is_default, $tracker_id, $is_query_displayed)";
        return $this->updateAndGetLastId($sql);
    }
    
    function save($id, $name, $description, $current_renderer_id, $parent_report_id, $user_id, $is_default, $tracker_id, $is_query_displayed, $updated_by_id) {
        $id                  = $this->da->escapeInt($id);
        $name                = $this->da->quoteSmart($name);
        $description         = $this->da->quoteSmart($description);
        $current_renderer_id = $this->da->escapeInt($current_renderer_id);
        $parent_report_id    = $parent_report_id ? $this->da->escapeInt($parent_report_id) : 'NULL';
        $user_id             = $user_id ? $this->da->escapeInt($user_id) : 'NULL';
        $is_default          = $this->da->escapeInt($is_default);
        $tracker_id          = $this->da->escapeInt($tracker_id);
        $is_query_displayed  = $this->da->escapeInt($is_query_displayed);
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
                   updated_by          = $updated_by_id,
                   updated_at          = $updated_at
                WHERE id = $id ";
        return $this->update($sql);
    }
    
    function delete($id) {
        $sql = "DELETE FROM $this->table_name WHERE id = ". $this->da->escapeInt($id);
        return $this->update($sql);
    }
    
    function duplicate($from_report_id, $to_tracker_id) {
        $from_report_id = $this->da->escapeInt($from_report_id);
        $to_tracker_id  = $this->da->escapeInt($to_tracker_id);
        $sql = "INSERT INTO $this->table_name (project_id, user_id, tracker_id, is_default, name, description, current_renderer_id, parent_report_id, is_query_displayed)
                SELECT project_id, user_id, $to_tracker_id, is_default, name, description, current_renderer_id, $from_report_id, is_query_displayed
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
     * @param bool  $user_is_superuser True if the user is superuser
     * @param array $permissions
     * @param string $ugroups          Ugroups of the current user to check the permissions
     * @param array $static_ugroups
     * @param array $dynamic_ugroups 
     * @param int $contributor_field_id The field id corresponding to the contributor semantic
     * @return DataAccessResult
     */
    public function searchMatchingIds($group_id, $tracker_id, $additional_from, $additional_where, $user_is_superuser, $permissions, $ugroups, $static_ugroups, $dynamic_ugroups, $contributor_field_id) {
        $tracker_id = $this->da->escapeInt($tracker_id);        
        
        $from   = " FROM tracker_artifact AS artifact
                 INNER JOIN tracker_changeset AS c ON (artifact.last_changeset_id = c.id)";
        $where  = " WHERE artifact.tracker_id = $tracker_id ";
        
        if(!$user_is_superuser) {
            //artifact permissions
            $from   .= " LEFT JOIN permissions ON (permissions.object_id = c.artifact_id AND permissions.permission_type = 'PLUGIN_TRACKER_ARTIFACT_ACCESS') ";
            $where  .= " AND (artifact.use_artifact_permissions = 0 OR  (permissions.ugroup_id IN (". implode(', ', $ugroups) .")))";
        }
        
        if (count($additional_from)) {
            $from  .= implode("\n", $additional_from);
        }
        if (count($additional_where)) {
            $where .= ' AND ( '. implode(' ) AND ( ', $additional_where) .' ) ';
        }
        
        // $sqls => SELECT UNION SELECT UNION SELECT ...
        $sqls = array();
        //Does the user member of at least one group which has ACCESS_FULL or is super user?
        if ($user_is_superuser || (isset($permissions['PLUGIN_TRACKER_ACCESS_FULL']) && count(array_intersect($ugroups, $permissions['PLUGIN_TRACKER_ACCESS_FULL'])) > 0)) {
            
            $sqls[] = "SELECT c.artifact_id AS id, c.id AS last_changeset_id ". $from ." ". $where;
            
        } else {
            //Does the user member of at least one group which has ACCESS_SUBMITTER ?
            if (isset($permissions['PLUGIN_TRACKER_ACCESS_SUBMITTER']) && count(array_intersect($ugroups, $permissions['PLUGIN_TRACKER_ACCESS_SUBMITTER'])) > 0) {
                // {{{ The static ugroups
                if (count(array_intersect($static_ugroups, $permissions['PLUGIN_TRACKER_ACCESS_SUBMITTER'])) > 0) {
                    $sqls[] = "SELECT c.artifact_id AS id, c.id AS last_changeset_id ".
                           $from ." INNER JOIN ugroup_user uu ON (
                                artifact.submitted_by = uu.user_id
                                AND uu.ugroup_id IN (". $this->da->quoteSmart(implode(', ', $static_ugroups)).")
                           ) ".
                           $where;
                }
                // }}}
                
                // {{{ project_members
                if (in_array($GLOBALS['UGROUP_PROJECT_MEMBERS'], $dynamic_ugroups) &&
                    in_array($GLOBALS['UGROUP_PROJECT_MEMBERS'], $permissions['PLUGIN_TRACKER_ACCESS_SUBMITTER'])) 
                {
                    $sqls[] = "SELECT c.artifact_id AS id, c.id AS last_changeset_id ".
                           $from ." INNER JOIN user_group AS ug ON ( 
                                artifact.submitted_by = ug.user_id 
                                AND ug.group_id = ". $this->da->escapeInt($group_id) ."
                           ) ".
                           $where;
                }
                //}}}
                // {{{ project_admins
                if (in_array($GLOBALS['UGROUP_PROJECT_ADMIN'], $dynamic_ugroups) &&
                    in_array($GLOBALS['UGROUP_PROJECT_ADMIN'], $permissions['TRACKER_ACCESS_SUBMITTER'])) 
                {
                    $sqls[] = "SELECT c.artifact_id AS id, c.id AS last_changeset_id ".
                           $from ." INNER JOIN user_group ug ON (
                                artifact.submitted_by = ug.user_id 
                                AND ug.group_id = ". $this->da->escapeInt($group_id) ." 
                                AND ug.admin_flags = 'A'
                           ) ".
                           $where;
                }
                //}}}
            }
            
            //Does the user member of at least one group which has ACCESS_ASSIGNEE ?
            if (isset($permissions['TRACKER_ACCESS_ASSIGNEE']) && count(array_intersect($ugroups, $permissions['PLUGIN_TRACKER_ACCESS_ASSIGNEE'])) > 0) {
                if ($contributor_field_id) {
                    // {{{ The static ugroups
                    if (count(array_intersect($static_ugroups, $permissions['PLUGIN_TRACKER_ACCESS_ASSIGNEE'])) > 0) {
                        $sqls[] = "SELECT c.artifact_id AS id, c.id AS last_changeset_id ".
                               $from ." INNER JOIN tracker_changeset_value AS tcv ON (
                                    tcv.field_id = ". $this->da->escapeInt($contributor_field_id) ."
                                    AND tcv.changeset_id = c.id
                               ) INNER JOIN tracker_changeset_value_list AS tcvl ON (
                                    tcvl.changeset_value_id = tcv.id
                               ) INNER JOIN ugroup_user AS uu ON (
                                    uu.user_id = tcvl.bindvalue_id
                                    AND uu.ugroup_id IN (". $this->da->quoteSmart(implode(', ', $static_ugroups)) .")
                               ) ".
                               $where;
                    }
                    // }}}
                    
                    // {{{ project_members
                    if (in_array($GLOBALS['UGROUP_PROJECT_MEMBERS'], $dynamic_ugroups) &&
                        in_array($GLOBALS['UGROUP_PROJECT_MEMBERS'], $permissions['PLUGIN_TRACKER_ACCESS_ASSIGNEE'])) {
                        $sqls[] = "SELECT c.artifact_id AS id, c.id AS last_changeset_id ".
                               $from ." INNER JOIN tracker_changeset_value AS tcv ON (
                                    tcv.field_id = ". $this->da->escapeInt($contributor_field_id) ."
                                    AND tcv.changeset_id = c.id
                               ) INNER JOIN tracker_changeset_value_list AS tcvl ON (
                                    tcvl.changeset_value_id = tcv.id
                               ) INNER JOIN user_group AS ug ON (
                                    ug.user_id = tcvl.bindvalue_id
                                    AND ug.group_id = ". $this->da->escapeInt($group_id) ."
                               ) ".
                               $where;
                    }
                    //}}}
                    // {{{ project_admins
                    if (in_array($GLOBALS['UGROUP_PROJECT_ADMIN'], $dynamic_ugroups) &&
                        in_array($GLOBALS['UGROUP_PROJECT_ADMIN'], $permissions['PLUGIN_TRACKER_ACCESS_ASSIGNEE'])) {
                        $sqls[] = "SELECT c.artifact_id AS id, c.id AS last_changeset_id ".
                               $from ." INNER JOIN tracker_changeset_value AS tcv ON (
                                    tcv.field_id = ". $this->da->escapeInt($contributor_field_id) ."
                                    AND tcv.changeset_id = c.id
                               ) INNER JOIN tracker_changeset_value_list AS tcvl ON (
                                    tcvl.changeset_value_id = tcv.id
                               ) INNER JOIN user_group AS ug ON (
                                    ug.user_id = tcvl.bindvalue_id
                                    AND ug.group_id = ". $this->da->escapeInt($group_id) ."
                                    AND ug.admin_flags = 'A'
                               ) ".
                               $where;
                    }
                    //}}}
                }
            }
        }
        
        ////optimize the query execution by using GROUP_CONCAT
        //// see http://dev.mysql.com/doc/refman/5.1/en/group-by-functions.html#function_group-concat
        //// Warning group_concat is truncated by group_concat_max_len system variable
        //// Please adjust the settings in /etc/my.cnf to be sure to retrieve all matching artifacts.
        //// The default is 1024 (1K) wich is not enough. For example 50000 matching artifacts take ~ 500K
        $sql = " SELECT GROUP_CONCAT(DISTINCT id) AS id, GROUP_CONCAT(DISTINCT last_changeset_id) AS last_changeset_id ";
        $sql .= " FROM (". implode(' UNION ', $sqls) .") AS R ";
        
        return $this->retrieve($sql);
    }
}
?>
