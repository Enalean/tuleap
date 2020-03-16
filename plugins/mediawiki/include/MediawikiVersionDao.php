<?php
/**
 * Copyright (c) Enalean, 2015. All Rights Reserved.
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

class MediawikiVersionDao extends DataAccessObject
{

    public function saveMediawikiVersionForProject($project_id, $version)
    {
        $project_id = $this->da->escapeInt($project_id);
        $version    = $this->da->quoteSmart($version);

        $sql = "REPLACE INTO plugin_mediawiki_version (project_id, mw_version)
                 VALUES ($project_id, $version)";

        return $this->update($sql);
    }

    public function getVersionForProject($project_id)
    {
        $project_id = $this->da->escapeInt($project_id);

        $sql = "SELECT mw_version
                FROM plugin_mediawiki_version
                WHERE project_id = $project_id";

        return $this->retrieveFirstRow($sql);
    }

    public function getAllMediawikiToMigrate($from_version)
    {
        $sql = "SELECT groups.group_id " . $this->getSearchVersionQuery($from_version);
        return $this->retrieve($sql);
    }

    public function countMediawikiToMigrate($from_version)
    {
        $sql = "SELECT COUNT(*) as nb " . $this->getSearchVersionQuery($from_version);
        $row = $this->retrieveFirstRow($sql);
        return $row['nb'];
    }

    private function getSearchVersionQuery($from_version)
    {
        $from_version = $this->da->quoteSmart($from_version);
        return "FROM groups
                INNER JOIN plugin_mediawiki_version mw_version ON (mw_version.project_id = groups.group_id)
                WHERE mw_version = $from_version
                AND groups.status IN ('A', 's')";
    }
}
