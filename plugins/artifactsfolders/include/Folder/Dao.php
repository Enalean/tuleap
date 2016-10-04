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
use Tuleap\ArtifactsFolders\Nature\NatureInFolderPresenter;

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

    public function searchFoldersTheArtifactBelongsTo($artifact_id)
    {
        $artifact_id = $this->da->escapeInt($artifact_id);
        $in_folder   = $this->da->quoteSmart(NatureInFolderPresenter::NATURE_IN_FOLDER);

        $sql = "SELECT a.id AS artifact_id, folder.*
                FROM tracker_artifact AS a
                    INNER JOIN tracker_changeset AS c ON (a.last_changeset_id = c.id AND a.id = $artifact_id)
                    INNER JOIN tracker_changeset_value AS cv ON (cv.changeset_id = c.id)
                    INNER JOIN tracker_changeset_value_artifactlink AS al ON (
                        al.changeset_value_id = cv.id
                        AND al.nature = $in_folder
                    )
                    INNER JOIN tracker_artifact AS folder ON (folder.id = al.artifact_id)
                    INNER JOIN plugin_artifactsfolders_tracker_usage AS folder_tracker ON (
                        folder.tracker_id = folder_tracker.tracker_id
                    )
                ";

        return $this->retrieve($sql);
    }
}
