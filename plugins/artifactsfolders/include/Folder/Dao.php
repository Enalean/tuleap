<?php
/**
 * Copyright (c) Enalean, 2016. All Rights Reserved.
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

namespace Tuleap\ArtifactsFolders\Folder;

use DataAccessObject;

class Dao extends DataAccessObject
{
    public function projectUsesArtifactsFolders(array $project_tracker_ids)
    {
        $project_tracker_ids = $this->da->escapeIntImplode($project_tracker_ids);

        $sql = "SELECT *
                FROM plugin_artifactsfolders_tracker_usage
                WHERE tracker_id IN ($project_tracker_ids)";

        $result = $this->da->query($sql);

        return $result->rowCount() > 0;
    }

    public function isTrackerConfiguredToContainFolders($tracker_id)
    {
        $tracker_id = $this->da->escapeInt($tracker_id);

        $sql = "SELECT NULL FROM plugin_artifactsfolders_tracker_usage WHERE tracker_id = $tracker_id";

        return count($this->retrieve($sql)) > 0;
    }

    public function create($tracker_id)
    {
        $tracker_id = $this->da->escapeInt($tracker_id);

        $sql = "INSERT INTO plugin_artifactsfolders_tracker_usage VALUES ($tracker_id)";

        return $this->update($sql);
    }
}
