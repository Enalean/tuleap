<?php
/**
 * Copyright (c) Enalean, 2015-Present. All Rights Reserved.
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
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
 *
 */

namespace Tuleap\Tracker\FormElement\Field\ArtifactLink;

use Tuleap\Tracker\FormElement\Field\FieldValueDao;

class ArtifactLinkFieldValueDao extends FieldValueDao
{
    public function searchById($changeset_value_id)
    {
        $changeset_value_id = $this->da->escapeInt($changeset_value_id);

        $sql = "SELECT cv.*, a.tracker_id, a.last_changeset_id
                FROM tracker_changeset_value_artifactlink AS cv
                    INNER JOIN tracker_artifact AS a ON (a.id = cv.artifact_id)
                    INNER JOIN tracker_artifact_priority_rank ON (tracker_artifact_priority_rank.artifact_id = a.id)
                    INNER JOIN tracker ON (tracker.id = a.tracker_id)
                    INNER JOIN `groups` ON (`groups`.group_id = tracker.group_id)
                WHERE changeset_value_id = $changeset_value_id
                    AND `groups`.status = 'A'
                    AND tracker.deletion_date IS NULL
                ORDER BY tracker_artifact_priority_rank.`rank`";

        return $this->retrieve($sql);
    }

    public function searchReverseLinksById($artifact_id)
    {
        $artifact_id = $this->da->escapeInt($artifact_id);

        $sql = "SELECT DISTINCT a.id as artifact_id, a.last_changeset_id, t.group_id, t.item_name as keyword, t.id as tracker_id, artlink.nature as nature
                FROM tracker_changeset_value_artifactlink AS artlink
                    JOIN tracker_changeset_value          AS cv ON (cv.id = artlink.changeset_value_id)
                    JOIN tracker_artifact                 AS a  ON (a.last_changeset_id = cv.changeset_id)
                    JOIN tracker                          AS t  ON (t.id = a.tracker_id)
                    JOIN `groups` ON (`groups`.group_id = t.group_id)
                WHERE artlink.artifact_id = $artifact_id
                    AND t.deletion_date IS NULL
                    AND `groups`.status = 'A'";

        return $this->retrieve($sql);
    }

    /**
     * @psalm-return \DataAccessResult|false
     */
    public function searchReverseLinksByIdAndSourceTrackerId(int $artifact_id, int $source_tracker_id)
    {
        $artifact_id = $this->da->escapeInt($artifact_id);

        $sql = "SELECT DISTINCT a.id as artifact_id
                FROM tracker_changeset_value_artifactlink AS artlink
                    JOIN tracker_changeset_value          AS cv ON (cv.id = artlink.changeset_value_id)
                    JOIN tracker_artifact                 AS a  ON (a.last_changeset_id = cv.changeset_id)
                    JOIN tracker                          AS t  ON (t.id = a.tracker_id)
                    JOIN `groups` ON (`groups`.group_id = t.group_id)
                WHERE artlink.artifact_id = $artifact_id
                    AND tracker_id = $source_tracker_id
                    AND t.deletion_date IS NULL
                    AND `groups`.status = 'A'";

        return $this->retrieve($sql);
    }

    public function searchIsChildReverseLinksById($artifact_id)
    {
        $artifact_id = $this->da->escapeInt($artifact_id);
        $is_child    = $this->da->quoteSmart(ArtifactLinkField::TYPE_IS_CHILD);

        $sql = "SELECT DISTINCT a.id as artifact_id, a.last_changeset_id, t.group_id, t.item_name as keyword, t.id as tracker_id, artlink.nature as nature
                FROM tracker_changeset_value_artifactlink AS artlink
                    JOIN tracker_changeset_value          AS cv ON (cv.id = artlink.changeset_value_id)
                    JOIN tracker_artifact                 AS a  ON (a.last_changeset_id = cv.changeset_id)
                    JOIN tracker                          AS t  ON (t.id = a.tracker_id)
                    JOIN `groups` ON (`groups`.group_id = t.group_id)
                WHERE artlink.artifact_id = $artifact_id
                    AND t.deletion_date IS NULL
                    AND `groups`.status = 'A'
                    AND nature = $is_child";

        return $this->retrieve($sql);
    }

    public function create($changeset_value_id, $type, array $artifact_ids, $keyword, $group_id)
    {
        $changeset_value_id = $this->da->escapeInt($changeset_value_id);
        $type               = $type ? $this->da->quoteSmart($type) : 'NULL';
        $keyword            = $this->da->quoteSmart($keyword);
        $group_id           = $this->da->escapeInt($group_id);

        $sql_values = [];
        foreach ($artifact_ids as $id) {
            $id           = $this->da->escapeInt($id);
            $sql_values[] = "($changeset_value_id, $type, $id, $keyword, $group_id)";
        }

        $sql = 'INSERT INTO tracker_changeset_value_artifactlink
                    (changeset_value_id, nature, artifact_id, keyword, group_id)
                VALUES' . implode(',', $sql_values);

        return $this->update($sql);
    }

    public function keep($from, $to)
    {
        $from = $this->da->escapeInt($from);
        $to   = $this->da->escapeInt($to);
        $sql  = "INSERT INTO tracker_changeset_value_artifactlink(changeset_value_id, nature, artifact_id, keyword, group_id)
                SELECT $to, nature, artifact_id, keyword, group_id
                FROM tracker_changeset_value_artifactlink
                WHERE changeset_value_id = $from";
        return $this->update($sql);
    }

    public function createNoneValue($tracker_id, $field_id)
    {
        $changeset_value_ids = $this->createNoneChangesetValue($tracker_id, $field_id);
        if ($changeset_value_ids === false) {
            return false;
        }

        return true;
    }

    public function updateItemName($group_id, $oldKeyword, $keyword)
    {
        $group_id   = $this->da->quoteSmart($group_id);
        $keyword    = $this->da->quoteSmart($keyword);
        $oldKeyword = $this->da->quoteSmart($oldKeyword);
        $sql        = "UPDATE tracker_changeset_value_artifactlink SET
			keyword=$keyword
            WHERE keyword=$oldKeyword AND group_id=$group_id";
        return $this->update($sql);
    }

    public function deleteReference($artifact_id)
    {
        $artifact_id = $this->da->escapeInt($artifact_id);
        $sql         = "DELETE FROM tracker_changeset_value_artifactlink
                WHERE artifact_id = $artifact_id";
        return $this->update($sql);
    }
}
