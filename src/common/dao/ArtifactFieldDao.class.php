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
 *  Data Access Object for ArtifactField
 */
class ArtifactFieldDao extends DataAccessObject
{
    /**
    * Gets all tables of the db
    * @return DataAccessResult
    */
    public function & searchAll()
    {
        $sql = "SELECT * FROM artifact_field";
        return $this->retrieve($sql);
    }

    /**
    * Searches field_id for (multi_)assigned_to By artifactTypeId
    * @return DataAccessResult
    */
    public function & searchAssignedToFieldIdByArtifactTypeId($artifactTypeId)
    {
        $sql = sprintf(
            " SELECT field_id " .
                       " FROM artifact_field " .
                       " WHERE group_artifact_id = %s " .
                       "   AND (field_name = 'assigned_to' OR field_name = 'multi_assigned_to') ",
            $artifactTypeId
        );
        return $this->retrieve($sql);
    }
}
