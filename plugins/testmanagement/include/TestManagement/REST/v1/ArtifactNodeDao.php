<?php
/**
 * Copyright (c) Enalean, 2015-present. All Rights Reserved.
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
 * MERCHANTABILITY or FITNEsemantic_status FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

namespace Tuleap\TestManagement\REST\v1;

use DataAccessObject;
use Tracker_Artifact;

class ArtifactNodeDao extends DataAccessObject
{

    /**
     * @return \DataAccessResult|false
     * @psalm-ignore-falsable-return
     */
    public function getTitlesStatusAndTypes(array $artifact_ids)
    {
        $artifact_ids = $this->da->escapeIntImplode($artifact_ids);
        $sql = "SELECT artifact.id, tracker.color, tracker.item_name, tracker.name as tracker_label, cvt_title.value as title, IF(cvl_status.bindvalue_id IS NULL, '" . Tracker_Artifact::STATUS_CLOSED . "', '" . Tracker_Artifact::STATUS_OPEN . "') AS status_semantic, lbsv_status.label AS status_label
                    FROM tracker_artifact AS artifact
                LEFT JOIN (
                    tracker_changeset_value AS cv_status
                    INNER JOIN tracker_semantic_status semantic_status ON (
                        cv_status.field_id = semantic_status.field_id
                    )
                    INNER JOIN tracker_changeset_value_list cvl_status ON (
                        cv_status.id = cvl_status.changeset_value_id
                        AND cvl_status.bindvalue_id = semantic_status.open_value_id
                    )
                    INNER JOIN tracker_field_list_bind_static_value lbsv_status ON (
                        lbsv_status.id = cvl_status.bindvalue_id
                    )
                ) ON (artifact.last_changeset_id = cv_status.changeset_id)
                LEFT JOIN (
                    tracker_changeset_value                 AS cv_title
                    INNER JOIN tracker_semantic_title       AS semantic_title  ON (
                        cv_title.field_id = semantic_title.field_id
                    )
                    INNER JOIN tracker_changeset_value_text AS cvt_title ON (
                        cv_title.id = cvt_title.changeset_value_id
                    )
                ) ON (artifact.last_changeset_id = cv_title.changeset_id)
                INNER JOIN tracker ON (tracker.id = artifact.tracker_id)
                WHERE artifact.id IN ($artifact_ids)";
        return $this->retrieve($sql);
    }

    /**
     * Retrieve all artifacts that point to the given one
     *
     * @param int $artifact_id
     *
     * @return \DataAccessResult|false
     * @psalm-ignore-falsable-return
     */
    public function getReverseLinkedArtifacts($artifact_id)
    {
        $artifact_id = $this->da->escapeInt($artifact_id);

        $sql = "SELECT DISTINCT a.*
                FROM tracker_changeset_value_artifactlink AS artlink
                    JOIN tracker_changeset_value          AS cv ON (cv.id = artlink.changeset_value_id)
                    JOIN tracker_artifact                 AS a  ON (a.last_changeset_id = cv.changeset_id)
                    JOIN tracker                          AS t  ON (t.id = a.tracker_id)
                WHERE artlink.artifact_id = $artifact_id";

        return $this->retrieve($sql);
    }

    /**
     * @return \DataAccessResult|false
     * @psalm-ignore-falsable-return
     */
    public function getCrossReferencesFromArtifact(int $artifact_id)
    {
        $artifact_id = $this->da->escapeInt($artifact_id);

        $sql = "SELECT target_id as id
                FROM cross_references
                WHERE source_id = $artifact_id
                    AND source_type = " . $this->da->quoteSmart(Tracker_Artifact::REFERENCE_NATURE) . "
                    AND target_type = source_type";

        return $this->retrieve($sql);
    }

    /**
     * @return \DataAccessResult|false
     * @psalm-ignore-falsable-return
     */
    public function getReverseCrossReferencesFromArtifact(int $artifact_id)
    {
        $artifact_id = $this->da->escapeInt($artifact_id);

        $sql = "SELECT source_id as id
                FROM cross_references
                WHERE target_id = $artifact_id
                    AND target_type = " . $this->da->quoteSmart(Tracker_Artifact::REFERENCE_NATURE) . "
                    AND source_type = target_type";

        return $this->retrieve($sql);
    }
}
