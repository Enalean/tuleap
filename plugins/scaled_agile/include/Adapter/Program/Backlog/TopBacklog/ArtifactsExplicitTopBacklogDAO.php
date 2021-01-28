<?php
/**
 * Copyright (c) Enalean, 2021-Present. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
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

declare(strict_types=1);

namespace Tuleap\ScaledAgile\Adapter\Program\Backlog\TopBacklog;

use Tuleap\DB\DataAccessObject;

class ArtifactsExplicitTopBacklogDAO extends DataAccessObject
{
    public function removeArtifactFromExplicitTopBacklog(int $artifact_id): void
    {
        $this->getDB()->run('DELETE FROM plugin_scaled_agile_explicit_top_backlog WHERE artifact_id = ?', $artifact_id);
    }

    public function removeArtifactsPlannedInAProgramIncrement(int $potential_program_increment_id): void
    {
        $this->getDB()->run(
            'DELETE plugin_scaled_agile_explicit_top_backlog.*
            FROM tracker_changeset_value_artifactlink
            JOIN tracker_changeset_value ON (tracker_changeset_value.id = tracker_changeset_value_artifactlink.changeset_value_id)
            JOIN tracker_changeset ON (tracker_changeset.id = tracker_changeset_value.changeset_id)
            JOIN tracker_artifact ON (tracker_artifact.last_changeset_id = tracker_changeset.id)
            JOIN plugin_scaled_agile_plan ON (tracker_artifact.tracker_id = plugin_scaled_agile_plan.program_increment_tracker_id)
            JOIN plugin_scaled_agile_explicit_top_backlog ON (plugin_scaled_agile_explicit_top_backlog.artifact_id = tracker_changeset_value_artifactlink.artifact_id)
            WHERE tracker_changeset.artifact_id = ?',
            $potential_program_increment_id
        );
    }
}
