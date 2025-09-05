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

namespace Tuleap\ProgramManagement\Adapter\Program\Backlog\TopBacklog;

use Tuleap\DB\DataAccessObject;
use Tuleap\ProgramManagement\Domain\Program\Backlog\TopBacklog\VerifyFeaturePlanned;

final class PlannedFeatureDAO extends DataAccessObject implements VerifyFeaturePlanned
{
    #[\Override]
    public function isFeaturePlannedInAProgramIncrement(int $feature_id): bool
    {
        $sql = 'SELECT COUNT(planned_feature_artifact.id)
            FROM tracker_artifact AS planned_feature_artifact
            JOIN plugin_program_management_plan ON (plugin_program_management_plan.plannable_tracker_id = planned_feature_artifact.tracker_id)
            JOIN plugin_program_management_program ON (plugin_program_management_program.program_project_id = plugin_program_management_plan.project_id)
            JOIN tracker AS program_increment_tracker ON (program_increment_tracker.id = plugin_program_management_program.program_increment_tracker_id)
            JOIN tracker_artifact AS program_increment_artifact ON (program_increment_artifact.tracker_id = program_increment_tracker.id)
            JOIN tracker_changeset AS program_increment_changeset ON (program_increment_changeset.id = program_increment_artifact.last_changeset_id)
            JOIN tracker_changeset_value AS program_increment_changeset_value ON (program_increment_changeset_value.changeset_id = program_increment_changeset.id)
            JOIN tracker_changeset_value_artifactlink AS program_increment_changeset_value_artifact_link ON (
                program_increment_changeset_value_artifact_link.changeset_value_id = program_increment_changeset_value.id AND program_increment_changeset_value_artifact_link.artifact_id = planned_feature_artifact.id
            )
            WHERE planned_feature_artifact.id = ?';

        return $this->getDB()->exists($sql, $feature_id);
    }
}
