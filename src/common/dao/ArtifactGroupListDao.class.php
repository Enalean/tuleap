<?php
/**
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
 *
 * This file is a part of Codendi.
 *
 * CodeX is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * CodeX is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Codendi. If not, see <http://www.gnu.org/licenses/>.
 */

require_once('include/DataAccessObject.class.php');

class ArtifactGroupListDao extends DataAccessObject
{
    public function __construct($da)
    {
        parent::__construct($da);
        $this->table_name = 'artifact_group_list';
    }
    public function updateArtifactGroupList($artifact_id, $group_id, $name, $description, $itemname, $allow_copy, $submit_instructions, $browse_instructions, $instantiate_for_new_projects)
    {
        $artifact_id = $this->da->quoteSmart($artifact_id);
        $group_id = $this->da->quoteSmart($group_id);
        $name = $this->da->quoteSmart($name);
        $description = $this->da->quoteSmart($description);
        $itemname = $this->da->quoteSmart($itemname);
        $allow_copy = $this->da->quoteSmart($allow_copy);
        $submit_instructions = $this->da->quoteSmart($submit_instructions);
        $browse_instructions = $this->da->quoteSmart($browse_instructions);
        $instantiate_for_new_projects = $this->da->quoteSmart($instantiate_for_new_projects);

        $sql = "UPDATE $this->table_name SET 
			name=$name, 
            description=$description, 
            item_name=$itemname, 
            allow_copy=$allow_copy, 
            submit_instructions=$submit_instructions, 
			browse_instructions=$browse_instructions, 
            instantiate_for_new_projects=$instantiate_for_new_projects
			WHERE group_artifact_id=$artifact_id AND group_id=$group_id";

        return $this->update($sql);
    }

    public function updateItemName($group_id, $oldItemname, $itemname)
    {
        $group_id = $this->da->quoteSmart($group_id);
        $itemname = $this->da->quoteSmart($itemname);
        $oldItemname = $this->da->quoteSmart($oldItemname);
        $sql = "UPDATE $this->table_name SET 
			item_name=$itemname
            WHERE item_name=$oldItemname AND group_id=$group_id";

        return $this->update($sql);
    }

    public function searchNameByGroupId($group_id)
    {
        $group_id = $this->da->quoteSmart($group_id);
        $sql = "SELECT * FROM $this->table_name WHERE group_id=$group_id";
        return $this->retrieve($sql);
    }
}
