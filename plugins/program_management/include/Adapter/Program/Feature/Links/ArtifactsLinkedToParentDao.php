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

namespace Tuleap\ProgramManagement\Adapter\Program\Feature\Links;

use Tuleap\DB\DataAccessObject;

class ArtifactsLinkedToParentDao extends DataAccessObject
{
    /**
     * @psalm-return array{id:int}[]
     */
    public function getArtifactsLinkedToId(int $artifact_id, int $program_increment_id): array
    {
        $sql = "SELECT linked_art.id
                FROM tracker_artifact parent_art
                         INNER JOIN tracker_field                           AS f          ON (f.tracker_id = parent_art.tracker_id AND f.formElement_type = 'art_link' AND use_it = 1)
                         INNER JOIN tracker_changeset_value                 AS cv         ON (cv.changeset_id = parent_art.last_changeset_id AND cv.field_id = f.id)
                         INNER JOIN tracker_changeset_value_artifactlink    AS artlink    ON (artlink.changeset_value_id = cv.id)
                         INNER JOIN tracker_artifact                        AS linked_art ON (linked_art.id = artlink.artifact_id)
                         INNER JOIN tracker                                 AS t          ON (t.id = linked_art.tracker_id)
                         INNER JOIN plugin_program_management_plan          AS plan       ON t.id = plan.plannable_tracker_id
                WHERE parent_art.id  = ?
                  AND t.deletion_date IS NULL
                  AND plan.program_increment_tracker_id = ?";

        return $this->getDB()->run($sql, $artifact_id, $program_increment_id);
    }
}
