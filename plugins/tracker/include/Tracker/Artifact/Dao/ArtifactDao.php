<?php
/**
 * Copyright (c) Enalean, 2024-Present. All Rights Reserved.
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

declare(strict_types=1);

namespace Tuleap\Tracker\Artifact\Dao;

use ParagonIE\EasyDB\EasyStatement;
use Tracker_FormElement_Field_ArtifactLink;
use Tuleap\DB\DataAccessObject;

class ArtifactDao extends DataAccessObject
{
    /**
     * Return all artifacts linked by the given artifact (possible exclusion)
     *
     * @param non-empty-array<int> $artifact_ids Artifact ids to inspect
     * @param int[] $excluded_ids Exclude those ids from the results
     * @return list<array{
     *     id: int,
     *     tracker_id: int,
     * }>
     */
    public function getLinkedArtifactsByIds(array $artifact_ids, array $excluded_ids = []): array
    {
        $artifact_ids_statement = EasyStatement::open()->in('parent_art.id IN (?*)', $artifact_ids);
        $params                 = $artifact_ids;
        $exclude_statement      = '';
        if ($excluded_ids !== []) {
            $exclude_statement = 'AND ' . EasyStatement::open()->in('linked_art.id NOT IN (?*)', $excluded_ids);
            $params            = [...$excluded_ids, ...$params];
        }
        $sql = <<<SQL
        SELECT linked_art.*
        FROM tracker_artifact parent_art
            INNER JOIN tracker_field                        f          ON (f.tracker_id = parent_art.tracker_id AND f.formElement_type = 'art_link' AND use_it = 1)
            INNER JOIN tracker_changeset_value              cv         ON (cv.changeset_id = parent_art.last_changeset_id AND cv.field_id = f.id)
            INNER JOIN tracker_changeset_value_artifactlink artlink    ON (artlink.changeset_value_id = cv.id)
            INNER JOIN tracker_artifact                     linked_art ON (linked_art.id = artlink.artifact_id $exclude_statement)
        INNER JOIN tracker                              linked_tracker ON (linked_art.tracker_id = linked_tracker.id)
        WHERE $artifact_ids_statement AND linked_tracker.deletion_date IS NULL
        SQL;

        return $this->getDB()->run($sql, ...$params);
    }

    public function getChildrenForArtifacts(array $artifact_ids): array
    {
        $artifact_ids_statement = EasyStatement::open()->in('parent_art.id IN (?*)', $artifact_ids);
        $params                 = $artifact_ids;

        $sql      = <<<SQL
        SELECT child_art.*, parent_art.id as parent_id
        FROM tracker_artifact parent_art
            INNER JOIN tracker_field                        AS f          ON (f.tracker_id = parent_art.tracker_id AND f.formElement_type = 'art_link' AND use_it = 1)
            INNER JOIN tracker_changeset_value              AS cv         ON (cv.changeset_id = parent_art.last_changeset_id AND cv.field_id = f.id)
            INNER JOIN tracker_changeset_value_artifactlink AS artlink    ON (artlink.changeset_value_id = cv.id)
            INNER JOIN tracker_artifact                     AS child_art  ON (child_art.id = artlink.artifact_id)
            INNER JOIN tracker                              AS child_tracker ON (child_art.tracker_id = child_tracker.id)
            INNER JOIN tracker_artifact_priority_rank ON (tracker_artifact_priority_rank.artifact_id = child_art.id)
        WHERE $artifact_ids_statement
            AND child_tracker.deletion_date IS NULL
            AND artlink.nature=?
        ORDER BY tracker_artifact_priority_rank.`rank` ASC
        SQL;
        $params[] = Tracker_FormElement_Field_ArtifactLink::TYPE_IS_CHILD;

        return $this->getDB()->run($sql, ...$params);
    }
}
