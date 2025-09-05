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
 * along with Tuleap. If not, see http://www.gnu.org/licenses/.
 */

declare(strict_types=1);

namespace Tuleap\ProgramManagement\Adapter\Program\Feature;

use Tuleap\DB\DataAccessObject;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\VerifyFeatureIsPlannedInProgramIncrement;

final class FeatureDAO extends DataAccessObject implements VerifyFeatureIsPlannedInProgramIncrement
{
    #[\Override]
    public function isFeaturePlannedInProgramIncrement(int $program_increment_id, int $feature_id): bool
    {
        $sql = "SELECT NULL
                FROM tracker_artifact parent_art
                    INNER JOIN tracker_field                        AS f          ON (f.tracker_id = parent_art.tracker_id AND f.formElement_type = 'art_link' AND use_it = 1)
                    INNER JOIN tracker_changeset_value              AS cv         ON (cv.changeset_id = parent_art.last_changeset_id AND cv.field_id = f.id)
                    INNER JOIN tracker_changeset_value_artifactlink AS artlink    ON (artlink.changeset_value_id = cv.id)
                    INNER JOIN tracker_artifact                     AS linked_art ON (linked_art.id = artlink.artifact_id)
                    INNER JOIN tracker                              AS t          ON (t.id = parent_art.tracker_id)
                    INNER JOIN tracker                              AS t_linked   ON (t_linked.id = linked_art.tracker_id AND t.group_id = t_linked.group_id)
                    INNER JOIN plugin_program_management_program                  ON (plugin_program_management_program.program_increment_tracker_id = parent_art.tracker_id)
                WHERE parent_art.id = :program_increment_id AND linked_art.id = :feature_id";

        $rows = $this->getDB()->run($sql, $program_increment_id, $feature_id);

        return count($rows) > 0;
    }
}
