<?php
/**
 * Copyright (c) Enalean, 2021 - Present. All Rights Reserved.
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

namespace Tuleap\ProgramManagement\Adapter\Team\MirroredTimeboxes;

use Tuleap\DB\DataAccessObject;

class MirroredTimeboxesDao extends DataAccessObject
{
    public function getMirroredTimeboxes(int $artifact_id, string $nature): array
    {
        $sql = "SELECT parent_art.id AS id
                FROM tracker_artifact parent_art
                         INNER JOIN tracker_field                           AS f          ON (f.tracker_id = parent_art.tracker_id AND f.formElement_type = 'art_link' AND use_it = 1)
                         INNER JOIN tracker_changeset_value                 AS cv         ON (cv.changeset_id = parent_art.last_changeset_id AND cv.field_id = f.id)
                         INNER JOIN tracker_changeset_value_artifactlink    AS artlink    ON (artlink.changeset_value_id = cv.id)
                         INNER JOIN tracker_artifact                        AS linked_art ON (linked_art.id = artlink.artifact_id)
                         INNER JOIN tracker                                 AS t          ON (t.id = parent_art.tracker_id)
                         INNER JOIN plugin_program_management_team_projects AS team       ON t.group_id = team.team_project_id

                WHERE linked_art.id  = ?
                  AND t.deletion_date IS NULL
                  AND IFNULL(artlink.nature, '') = ?";

        return $this->getDB()->run($sql, $artifact_id, $nature);
    }

    public function getTimeboxFromMirroredTimeboxId(int $mirrored_timebox_id, string $nature): ?int
    {
        $sql = "SELECT linked_art.id
                FROM tracker_artifact parent_art
                         INNER JOIN tracker_field                           AS f          ON (f.tracker_id = parent_art.tracker_id AND f.formElement_type = 'art_link' AND use_it = 1)
                         INNER JOIN tracker_changeset_value                 AS cv         ON (cv.changeset_id = parent_art.last_changeset_id AND cv.field_id = f.id)
                         INNER JOIN tracker_changeset_value_artifactlink    AS artlink    ON (artlink.changeset_value_id = cv.id)
                         INNER JOIN tracker_artifact                        AS linked_art ON (linked_art.id = artlink.artifact_id)
                         INNER JOIN tracker                                 AS t          ON (t.id = parent_art.tracker_id)
                         INNER JOIN plugin_program_management_team_projects AS team       ON t.group_id = team.team_project_id
                WHERE parent_art.id  = ?
                  AND t.deletion_date IS NULL
                  AND IFNULL(artlink.nature, '') = ?;";

        $timebox_id = $this->getDB()->cell($sql, $mirrored_timebox_id, $nature);
        if (! $timebox_id) {
            return null;
        }

        return $timebox_id;
    }
}
