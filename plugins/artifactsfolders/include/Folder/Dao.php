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
use Tracker_FormElement_Field_ArtifactLink;
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

        $sql = "SELECT NULL
                FROM plugin_artifactsfolders_tracker_usage
                WHERE tracker_id = $tracker_id";

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

    public function searchFoldersInProject($project_id)
    {
        $project_id = $this->da->escapeInt($project_id);
        $is_child   = $this->da->quoteSmart(Tracker_FormElement_Field_ArtifactLink::NATURE_IS_CHILD);

        $sql = "SELECT A.*, parent.id AS parent_id, CVT.value AS title, CVT.body_format AS title_format
                FROM tracker_artifact AS A
                    INNER JOIN tracker AS T ON (T.id = A.tracker_id AND T.group_id = $project_id)
                    INNER JOIN plugin_artifactsfolders_tracker_usage AS folder_tracker USING (tracker_id)
                    LEFT JOIN (
                        tracker_changeset_value_artifactlink AS artlink
                        INNER JOIN tracker_changeset_value AS cv ON (
                            cv.id = artlink.changeset_value_id
                            AND nature = $is_child
                        )
                        INNER JOIN tracker_artifact AS parent ON (
                            parent.last_changeset_id = cv.changeset_id
                        )
                    ) ON (artlink.artifact_id = A.id AND parent.tracker_id = A.tracker_id)
                    LEFT JOIN (
                        tracker_semantic_title AS ST
                        INNER JOIN tracker_changeset_value AS CV ON (CV.field_id = ST.field_id)
                        INNER JOIN tracker_changeset_value_text AS CVT ON (CV.id = CVT.changeset_value_id)
                    ) ON (A.tracker_id = ST.tracker_id AND CV.changeset_id = A.last_changeset_id)";

        return $this->retrieve($sql);
    }

    public function addInFolderNature($changeset_id, $field_id, $artifact_folder_id, $nature)
    {
        $changeset_id       = $this->da->escapeInt($changeset_id);
        $field_id           = $this->da->escapeInt($field_id);
        $artifact_folder_id = $this->da->escapeInt($artifact_folder_id);
        $nature             = $this->da->quoteSmart($nature);

        $sql = "INSERT INTO tracker_changeset_value_artifactlink
                SELECT cv.id, $artifact_folder_id, item_name, group_id, $nature
                FROM tracker_artifact AS a
                    INNER JOIN tracker AS t ON (
                        t.id = a.tracker_id
                        AND a.id = $artifact_folder_id
                    )
                    INNER JOIN tracker_changeset_value AS cv ON (
                        cv.field_id = $field_id
                        AND cv.changeset_id = $changeset_id
                    )
                ON DUPLICATE KEY UPDATE nature = $nature";

        return $this->update($sql);
    }

    public function removeInFolderLink($changeset_id, $field_id, $artifact_folder_id)
    {
        $changeset_id       = $this->da->escapeInt($changeset_id);
        $field_id           = $this->da->escapeInt($field_id);
        $artifact_folder_id = $this->da->escapeInt($artifact_folder_id);

        $sql = "DELETE cval.*
                FROM tracker_changeset_value_artifactlink AS cval
                    INNER JOIN tracker_changeset_value AS cv ON (
                        cval.changeset_value_id = cv.id
                        AND cv.field_id = $field_id
                        AND cv.changeset_id = $changeset_id
                    )
                WHERE cval.artifact_id = $artifact_folder_id";

        return $this->update($sql);
    }
}
