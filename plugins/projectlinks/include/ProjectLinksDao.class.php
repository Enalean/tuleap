<?php
/**
 * Copyright Enalean (c) 2018. All rights reserved.
 * Copyright (c) STMicroelectronics, 2009. All Rights Reserved.
 *
 * Originally written by Manuel Vacelet, 2009
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

/**
 * Database access for Project links
 */
class ProjectLinksDao extends DataAccessObject
{

    /**
     * Search all links of a given type
     *
     * @param int $linkTypeId
     * @return DataAccessResult
     */
    public function searchLinksByType($linkTypeId)
    {
        $sql = 'SELECT rel.*, g.group_name' .
               ' FROM plugin_projectlinks_relationship rel' .
               '  JOIN groups g ON (g.group_id = rel.target_group_id)' .
               ' WHERE link_type_id = ' . db_ei($linkTypeId) .
               ' ORDER BY g.group_name';
        return $this->retrieve($sql);
    }

    /**
     * Search all the links from $groupId project to other prjs.

     * @param int $groupId Group id
     * @return DataAccessResult
     */
    public function searchForwardLinks($groupId)
    {
        $sql = 'SELECT name AS link_name, type, groups.group_id,
                  group_name, unix_group_name, uri_plus, link_id, creation_date,
                  master_group_id, target_group_id, link_type.link_type_id
                FROM plugin_projectlinks_relationship AS rel
                  INNER JOIN plugin_projectlinks_link_type AS link_type 
                    USING (link_type_id)
                  INNER JOIN groups
                    ON (groups.group_id = rel.target_group_id)
                WHERE master_group_id = ' . db_ei($groupId) . '
                  AND status = "A"
                ORDER BY name, type, group_name';
        return $this->retrieve($sql);
    }

    /**
     * Search all the links that point to $groupId project
     *
     * @param int $groupId Group id
     * @return DataAccessResult
     */
    public function searchBackLinks($groupId)
    {
        $sql = 'SELECT reverse_name AS link_name, type, groups.group_id,
                  group_name, unix_group_name, uri_plus, link_id, creation_date,
                  master_group_id, target_group_id,  link_type.link_type_id
                FROM plugin_projectlinks_relationship AS rel
                  INNER JOIN plugin_projectlinks_link_type AS link_type 
                    USING (link_type_id)
                  INNER JOIN groups
                    ON (groups.group_id = rel.master_group_id)
                WHERE target_group_id = ' . db_ei($groupId) . '
                  AND status = "A"
            ORDER BY name, type, group_name';
        return $this->retrieve($sql);
    }

    /**
     * Return true if there are links from or toward this project or if there is
     * at least one link type defined in the project.
     *
     * @param int $groupId
     *
     * @return bool
     */
    public function projectUsesProjectLinks($groupId)
    {
        $sql = 'SELECT NULL' .
               ' FROM plugin_projectlinks_link_type' .
               ' WHERE group_id = ' . $groupId .
               ' LIMIT 1';
        $dar = $this->retrieve($sql);
        if ($dar && !$dar->isError() && $dar->rowCount() == 1) {
            return true;
        } else {
            $sql = 'SELECT NULL' .
                   ' FROM plugin_projectlinks_relationship' .
                   ' WHERE target_group_id = ' . $groupId .
                   ' OR master_group_id = ' . $groupId .
                   ' LIMIT 1';
            $dar = $this->retrieve($sql);
            if ($dar && !$dar->isError() && $dar->rowCount() == 1) {
                return true;
            }
        }
        return false;
    }
}
