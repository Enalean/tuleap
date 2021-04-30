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

namespace Tuleap\ProgramManagement\Adapter\Program\Feature;

use Tuleap\DB\DataAccessObject;
use Tuleap\ProgramManagement\Domain\Program\Backlog\Feature\FeaturesStore;
use Tuleap\ProgramManagement\Domain\Program\ProgramIdentifier;

final class FeaturesDao extends DataAccessObject implements FeaturesStore
{
    /**
     * @psalm-return array{tracker_name: string, artifact_id: int, artifact_title: string, field_title_id: int}[]
     */
    public function searchPlannableFeatures(ProgramIdentifier $program): array
    {
        $sql = '
                SELECT tracker.item_name AS tracker_name, artifact.id AS artifact_id, title_value.value AS artifact_title, title.field_id AS field_title_id
                    FROM `groups` AS project
                        INNER JOIN tracker ON tracker.group_id = project.group_id
                        INNER JOIN plugin_program_management_plan AS plan
                            ON plan.plannable_tracker_id = tracker.id
                        INNER JOIN tracker_artifact AS artifact ON artifact.tracker_id = tracker.id
                        INNER JOIN tracker_changeset ON (artifact.last_changeset_id = tracker_changeset.id)
                        -- get open artifacts
                        INNER JOIN (
                        tracker_semantic_status AS status
                            INNER JOIN tracker_changeset_value AS status_changeset ON (status.field_id = status_changeset.field_id)
                            INNER JOIN tracker_changeset_value_list AS status_value
                                ON (status_changeset.id = status_value.changeset_value_id AND status.open_value_id = status_value.bindvalue_id)
                        ) ON (tracker.id = status.tracker_id AND tracker_changeset.id = status_changeset.changeset_id)
                        -- get title value
                        INNER JOIN (
                            tracker_semantic_title AS title
                                INNER JOIN tracker_changeset_value AS title_changeset ON (title.field_id = title_changeset.field_id)
                                INNER JOIN tracker_changeset_value_text AS title_value on title_changeset.id = title_value.changeset_value_id
                        ) ON (tracker.id = title.tracker_id AND tracker_changeset.id = title_changeset.changeset_id)
                        INNER JOIN plugin_program_management_explicit_top_backlog ON (plugin_program_management_explicit_top_backlog.artifact_id = artifact.id)
                        INNER JOIN tracker_artifact_priority_rank ON plugin_program_management_explicit_top_backlog.artifact_id = tracker_artifact_priority_rank.artifact_id
                WHERE project.group_id = ?
                ORDER BY tracker_artifact_priority_rank.rank';

        return $this->getDB()->run($sql, $program->getId());
    }
}
