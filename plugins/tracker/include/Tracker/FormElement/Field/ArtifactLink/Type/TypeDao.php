<?php
/**
 * Copyright (c) Enalean, 2016 - Present. All Rights Reserved.
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

namespace Tuleap\Tracker\FormElement\Field\ArtifactLink\Type;

use ParagonIE\EasyDB\EasyDB;
use Tuleap\DB\DataAccessObject;

class TypeDao extends DataAccessObject
{
    public function create($shortname, $forward_label, $reverse_label): void
    {
        $this->getDB()->tryFlatTransaction(function (EasyDB $db) use ($shortname, $forward_label, $reverse_label): void {
            $type = $this->getTypeByShortname($shortname);

            if (count($type) > 0) {
                throw new UnableToCreateTypeException(
                    sprintf(dgettext('tuleap-tracker', 'a type with %1$s as shortname already exists.'), $shortname)
                );
            }

            $db->run(
                'INSERT INTO plugin_tracker_artifactlink_natures (shortname, forward_label, reverse_label) VALUES (?, ?, ?)',
                $shortname,
                $forward_label,
                $reverse_label
            );
        });
    }

    /**
     * @psalm-return array{shortname: string, forward_label: string, reverse_label: string}[]
     */
    public function getTypeByShortname($shortname): array
    {
        $sql = 'SELECT shortname, forward_label, reverse_label FROM plugin_tracker_artifactlink_natures WHERE shortname = ?';

        return $this->getDB()->run($sql, $shortname);
    }

    public function edit($shortname, $forward_label, $reverse_label): void
    {
        $sql = 'UPDATE plugin_tracker_artifactlink_natures
                   SET forward_label = ?, reverse_label = ?
                WHERE shortname = ?';

        $this->getDB()->run($sql, $forward_label, $reverse_label, $shortname);
    }

    public function delete($shortname): void
    {
        $this->getDB()->tryFlatTransaction(function (EasyDB $db) use ($shortname): void {
            $this->deleteTypeInTableColumns($shortname);
            $this->purgeDeletedTypeInArtifactLinkTypeUsage($shortname);

            $sql = 'DELETE FROM plugin_tracker_artifactlink_natures WHERE shortname = ?';
            $db->run($sql, $shortname);
        });
    }

    private function purgeDeletedTypeInArtifactLinkTypeUsage($type_shortname): void
    {
        $sql = 'DELETE FROM plugin_tracker_projects_unused_artifactlink_types
                WHERE type_shortname = ?';

        $this->getDB()->run($sql, $type_shortname);
    }

    private function deleteTypeInTableColumns($shortname): void
    {
        $sql = "DELETE FROM tracker_report_renderer_table_columns WHERE artlink_nature = ?";

        $this->getDB()->run($sql, $shortname);
    }

    public function isOrHasBeenUsed($shortname): bool
    {
        $sql = 'SELECT 1
                  FROM tracker_changeset_value_artifactlink
                 WHERE nature = ?
                 LIMIT 1';

        $rows = $this->getDB()->run($sql, $shortname);
        return count($rows) !== 0;
    }

    /**
     * @psalm-return array{nature: string}[]
     */
    public function searchAllUsedTypesByProject($project_id): array
    {
        $sql = 'SELECT DISTINCT nature
                  FROM tracker_changeset_value_artifactlink
                 WHERE group_id = ?
                 ORDER BY nature ASC';

        return $this->getDB()->run($sql, $project_id);
    }

    /**
     * @psalm-return array{nature: string, forward_label: string, reverse_label: string}[]
     */
    public function searchAllCurrentlyUsedTypesByTrackerID(int $tracker_id): array
    {
        $sql = "SELECT DISTINCT nature, forward_label, reverse_label
                 FROM tracker_changeset_value_artifactlink
                 JOIN tracker_changeset_value ON (tracker_changeset_value_artifactlink.changeset_value_id = tracker_changeset_value.id)
                 JOIN tracker_artifact ON (tracker_changeset_value.changeset_id = tracker_artifact.last_changeset_id)
                 LEFT JOIN plugin_tracker_artifactlink_natures ON (tracker_changeset_value_artifactlink.nature = plugin_tracker_artifactlink_natures.shortname)
                 WHERE tracker_artifact.tracker_id = ?
                 ORDER BY nature ASC";

        return $this->getDB()->run($sql, $tracker_id);
    }

    /**
     * @psalm-return array{shortname: string, forward_label: string, reverse_label: string}[]
     */
    public function searchAll(): array
    {
        $sql = "SELECT shortname, forward_label, reverse_label
                FROM plugin_tracker_artifactlink_natures
                ORDER BY shortname ASC";

        return $this->getDB()->run($sql);
    }

    /**
     * @psalm-return array{shortname: string, forward_label: string, reverse_label: string}|null
     */
    public function getFromShortname($shortname): ?array
    {
        $sql = 'SELECT shortname, forward_label, reverse_label
                FROM plugin_tracker_artifactlink_natures
                WHERE shortname = ?';

        $rows = $this->getDB()->run($sql, $shortname);

        return $rows[0] ?? null;
    }

    /**
     * @psalm-return array{shortname: string}[]
     */
    public function searchForwardTypeShortNamesForGivenArtifact($artifact_id): array
    {
        $sql = "SELECT DISTINCT IFNULL(artlink.nature, '') AS shortname
                FROM tracker_artifact parent_art
                    INNER JOIN tracker_field                        AS f          ON (f.tracker_id = parent_art.tracker_id AND f.formElement_type = 'art_link' AND use_it = 1)
                    INNER JOIN tracker_changeset_value              AS cv         ON (cv.changeset_id = parent_art.last_changeset_id AND cv.field_id = f.id)
                    INNER JOIN tracker_changeset_value_artifactlink AS artlink    ON (artlink.changeset_value_id = cv.id)
                    INNER JOIN tracker_artifact                     AS linked_art ON (linked_art.id = artlink.artifact_id)
                    INNER JOIN tracker                              AS t          ON (t.id = linked_art.tracker_id)
                    INNER JOIN `groups` ON (`groups`.group_id = t.group_id)
                WHERE parent_art.id  = ?
                    AND `groups`.status = 'A'";

        return $this->getDB()->run($sql, $artifact_id);
    }

    /**
     * @psalm-return array{shortname: string}[]
     */
    public function searchReverseTypeShortNamesForGivenArtifact($artifact_id): array
    {
        $sql = "SELECT DISTINCT IFNULL(artlink.nature, '') AS shortname
                FROM tracker_artifact parent_art
                    INNER JOIN tracker_field                        AS f          ON (f.tracker_id = parent_art.tracker_id AND f.formElement_type = 'art_link' AND use_it = 1)
                    INNER JOIN tracker_changeset_value              AS cv         ON (cv.changeset_id = parent_art.last_changeset_id AND cv.field_id = f.id)
                    INNER JOIN tracker_changeset_value_artifactlink AS artlink    ON (artlink.changeset_value_id = cv.id)
                    INNER JOIN tracker_artifact                     AS linked_art ON (linked_art.id = artlink.artifact_id)
                    INNER JOIN tracker                              AS t          ON (t.id = parent_art.tracker_id)
                    INNER JOIN `groups` ON (`groups`.group_id = t.group_id)
                WHERE linked_art.id  = ?
                    AND `groups`.status = 'A'";

        return $this->getDB()->run($sql, $artifact_id);
    }

    /**
     * @return int[]
     */
    public function getForwardLinkedArtifactIds($artifact_id, $type, $limit, $offset): array
    {
        $sql = "SELECT SQL_CALC_FOUND_ROWS artlink.artifact_id AS id
                FROM tracker_artifact parent_art
                    INNER JOIN tracker_field                        AS f          ON (f.tracker_id = parent_art.tracker_id AND f.formElement_type = 'art_link' AND use_it = 1)
                    INNER JOIN tracker_changeset_value              AS cv         ON (cv.changeset_id = parent_art.last_changeset_id AND cv.field_id = f.id)
                    INNER JOIN tracker_changeset_value_artifactlink AS artlink    ON (artlink.changeset_value_id = cv.id)
                    INNER JOIN tracker_artifact                     AS linked_art ON (linked_art.id = artlink.artifact_id)
                    INNER JOIN tracker                              AS t          ON (t.id = linked_art.tracker_id)
                    INNER JOIN `groups` ON (`groups`.group_id = t.group_id)
                WHERE parent_art.id  = ?
                    AND t.deletion_date IS NULL
                    AND `groups`.status = 'A'
                    AND IFNULL(artlink.nature, '') = ?
                LIMIT ?
                OFFSET ?";

        $rows = $this->getDB()->run($sql, $artifact_id, $type, $limit, $offset);
        $ids  = [];
        foreach ($rows as $row) {
            $ids[] = $row['id'];
        }

        return $ids;
    }

    /**
     * @return int[]
     */
    public function getReverseLinkedArtifactIds($artifact_id, $type, $limit, $offset): array
    {
        $sql = "SELECT SQL_CALC_FOUND_ROWS parent_art.id AS id
                FROM tracker_artifact parent_art
                    INNER JOIN tracker_field                        AS f          ON (f.tracker_id = parent_art.tracker_id AND f.formElement_type = 'art_link' AND use_it = 1)
                    INNER JOIN tracker_changeset_value              AS cv         ON (cv.changeset_id = parent_art.last_changeset_id AND cv.field_id = f.id)
                    INNER JOIN tracker_changeset_value_artifactlink AS artlink    ON (artlink.changeset_value_id = cv.id)
                    INNER JOIN tracker_artifact                     AS linked_art ON (linked_art.id = artlink.artifact_id)
                    INNER JOIN tracker                              AS t          ON (t.id = parent_art.tracker_id)
                    INNER JOIN `groups` ON (`groups`.group_id = t.group_id)
                WHERE linked_art.id  = ?
                    AND t.deletion_date IS NULL
                    AND `groups`.status = 'A'
                    AND IFNULL(artlink.nature, '') = ?
                LIMIT ?
                OFFSET ?";

        $rows = $this->getDB()->run($sql, $artifact_id, $type, $limit, $offset);
        $ids  = [];
        foreach ($rows as $row) {
            $ids[] = $row['id'];
        }

        return $ids;
    }

    public function hasReverseLinkedArtifacts($artifact_id, $type): bool
    {
        $sql = "SELECT NULL
                FROM tracker_artifact parent_art
                    INNER JOIN tracker_field                        AS f          ON (f.tracker_id = parent_art.tracker_id AND f.formElement_type = 'art_link' AND use_it = 1)
                    INNER JOIN tracker_changeset_value              AS cv         ON (cv.changeset_id = parent_art.last_changeset_id AND cv.field_id = f.id)
                    INNER JOIN tracker_changeset_value_artifactlink AS artlink    ON (artlink.changeset_value_id = cv.id)
                    INNER JOIN tracker_artifact                     AS linked_art ON (linked_art.id = artlink.artifact_id)
                    INNER JOIN tracker                              AS t          ON (t.id = parent_art.tracker_id)
                    INNER JOIN `groups` ON (`groups`.group_id = t.group_id)
                WHERE linked_art.id  = ?
                    AND `groups`.status = 'A'
                    AND IFNULL(artlink.nature, '') = ?
                LIMIT 1";

        $rows = $this->getDB()->run($sql, $artifact_id, $type);

        return count($rows) > 0;
    }

    /**
     * @psalm-return array{shortname: string}[]
     */
    public function getUsedTypes(): array
    {
        $sql = "SELECT DISTINCT nature AS shortname
                FROM tracker_changeset_value_artifactlink
                WHERE nature IS NOT NULL";

        return $this->getDB()->run($sql);
    }
}
