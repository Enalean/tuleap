<?php
/**
 * Copyright (c) Enalean, 2013. All Rights Reserved.
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

class ProjectHierarchyDao extends DataAccessObject
{

    /**
     * @param int $group_id
     * @param int $parent_group_id
     * @return bool
     */
    public function addParentProject($group_id, $parent_group_id)
    {
        $group_id        = $this->da->escapeInt($group_id);
        $parent_group_id = $this->da->escapeInt($parent_group_id);

        $sql = "INSERT INTO project_parent (group_id, parent_group_id)
               VALUES ($group_id, $parent_group_id)";

        return $this->update($sql);
    }

    /**
     * @param int $group_id
     * @param int $parent_group_id
     * @return bool
     */
    public function updateParentProject($group_id, $parent_group_id)
    {
        $group_id        = $this->da->escapeInt($group_id);
        $parent_group_id = $this->da->escapeInt($parent_group_id);

        $sql = "UPDATE project_parent
               SET parent_group_id = $parent_group_id
               WHERE group_id = $group_id";

        return $this->update($sql);
    }

    /**
     * @param int $group_id
     * @return bool
     */
    public function removeParentProject($group_id)
    {
        $group_id = $this->da->escapeInt($group_id);

        $sql = "DELETE FROM project_parent
               WHERE group_id = $group_id";

        return $this->update($sql);
    }

    /**
     * @param int $group_id
     * @return DataAccessResult
     */
    public function getParentProject($group_id)
    {
        $group_id = $this->da->escapeInt($group_id);

        $sql = "SELECT groups.*
               FROM groups
               JOIN project_parent ON (groups.group_id = project_parent.parent_group_id)
               WHERE project_parent.group_id = $group_id";

        return $this->retrieve($sql);
    }

    /**
     * @param int $group_id
     * @return DataAccessResult
     */
    public function getChildProjects($group_id)
    {
        $group_id = $this->da->escapeInt($group_id);

        $sql = "SELECT groups.*
               FROM groups
               JOIN project_parent ON (groups.group_id = project_parent.group_id)
               WHERE project_parent.parent_group_id = $group_id";

        return $this->retrieve($sql);
    }
}
