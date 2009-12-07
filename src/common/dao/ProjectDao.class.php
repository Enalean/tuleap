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

class ProjectDao extends DataAccessObject {
    public function __construct($da) {
        parent::__construct($da);
        $this->table_name = 'groups';
    }

    public function searchById($id) {
        $sql = "SELECT *".
               " FROM ".$this->table_name.
               " WHERE group_id = ".$this->da->quoteSmart($id);
        return $this->retrieve($sql);
    }

    public function searchByStatus($status) {
        $status = $this->da->quoteSmart($status);
        $sql = "SELECT *
                FROM $this->table_name
                WHERE status = $status";
        return $this->retrieve($sql);
    }
    
    public function searchByUnixGroupName($unixGroupName){
        $unixGroupName= $this->da->quoteSmart($unixGroupName);
        $sql = "SELECT * 
                FROM $this->table_name
                WHERE unix_group_name=$unixGroupName";
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
     * @param Integer $limit
     * @param Integer $userId
     * @param Boolean $isMember
     * @param Boolean $isAdmin
     * 
     * @return DataAccessResult
     */
    public function searchProjectsNameLike($name, $limit, $userId=null, $isMember=false, $isAdmin=false) {
        $join    = '';
        $where   = '';
        $groupby = '';
        if ($userId !== null) {
            if ($isMember || $isAdmin) {
                // Manage if we search project the user is member or admin of
                $join  .= ' JOIN user_group ug ON (ug.group_id = g.group_id)';
                $where .= ' AND ug.user_id = '.$this->da->escapeInt($userId);
                if ($isAdmin) {
                    $where .= ' AND ug.admin_flags = "A"';
                }
            } else {
                // Either public projects or private projects the user is member of
                $join  .= ' LEFT JOIN user_group ug ON (ug.group_id = g.group_id)';
                $where .= ' AND (g.is_public = 1'.
                          ' OR (g.is_public = 0 and ug.user_id IS NOT NULL))';
            }
            $groupby .= ' GROUP BY g.group_id';
        } else {
            // If no user_id provided, only return public projects
            $where .= ' AND g.is_public = 1';
        }

        $sql = "SELECT SQL_CALC_FOUND_ROWS g.*".
               " FROM ".$this->table_name." g".
               $join.
               " WHERE (g.group_name like ".$this->da->quoteSmart($name.'%').
               " OR g.unix_group_name like ".$this->da->quoteSmart($name.'%').")".
               " AND g.status='A'".
               $where.
               $groupby.
               " ORDER BY group_name".
               " LIMIT ".$this->da->escapeInt($limit);
        return $this->retrieve($sql);
    }
}

?>
