<?php
/**
 * Copyright (c) Enalean, 2017. All Rights Reserved.
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

namespace Tuleap\ArtifactsFolders\Converter;

use DataAccessObject;

class ConverterDao extends DataAccessObject
{
    public function searchArtifactsLinkedToFolderInProject($project_id)
    {
        $project_id = $this->da->escapeInt($project_id);

        $sql = "SELECT
                    A.id AS item_id,
                    folder.id AS folder_id
                FROM tracker_artifact AS A
                INNER JOIN tracker AS T
                    ON (T.id = A.tracker_id AND T.group_id = $project_id)
                INNER JOIN tracker_changeset_value_artifactlink AS artlink
                INNER JOIN tracker_changeset_value AS cv
                    ON (cv.id = artlink.changeset_value_id AND cv.changeset_id = A.last_changeset_id AND nature = '_in_folder')
                INNER JOIN tracker_artifact AS folder
                    ON (folder.id = artlink.artifact_id)
                INNER JOIN tracker AS folder_tracker
                    ON (folder_tracker.id = folder.tracker_id AND folder_tracker.group_id = $project_id)
                INNER JOIN plugin_artifactsfolders_tracker_usage AS usg
                    ON (usg.tracker_id = folder.tracker_id)
        ";

        return $this->retrieve($sql);
    }

    public function disableFolderConfigurationsForProject($project_id)
    {
        $project_id = $this->da->escapeInt($project_id);

        $sql = "DELETE usg.*
                FROM plugin_artifactsfolders_tracker_usage usg
                INNER JOIN tracker AS T
                    ON (T.id = usg.tracker_id)
                WHERE T.group_id = $project_id
        ";

        return $this->update($sql);
    }

    public function getFolderConfigurationForProject($project_id)
    {
        $project_id = $this->da->escapeInt($project_id);

        $sql = "SELECT usg.*
                FROM plugin_artifactsfolders_tracker_usage usg
                INNER JOIN tracker AS T
                    ON (T.id = usg.tracker_id)
                WHERE T.group_id = $project_id
        ";

        return $this->retrieve($sql);
    }
}
