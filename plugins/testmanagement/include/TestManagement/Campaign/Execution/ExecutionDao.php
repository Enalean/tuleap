<?php
/**
 * Copyright (c) Enalean, 2018 - present. All Rights Reserved.
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

namespace Tuleap\TestManagement\Campaign\Execution;

use ParagonIE\EasyDB\EasyStatement;
use Tuleap\DB\DataAccessObject;

class ExecutionDao extends DataAccessObject
{
    /**
     * @param int[] $executions_ids
     */
    public function searchDefinitionsChangesetIdsForExecution(array $executions_ids): array
    {
        $statement = EasyStatement::open()->in('execution_artifact_id IN (?*)', $executions_ids);

        return $this->getDB()->run(
            "SELECT * FROM plugin_testmanagement_execution WHERE $statement",
            ...$statement->values()
        );
    }

    /**
     * @return int | false
     */
    public function searchDefinitionChangesetIdForExecution(int $executions_id)
    {
        $sql = "SELECT definition_changeset_id FROM plugin_testmanagement_execution WHERE execution_artifact_id = ?";
        return $this->getDB()->single(
            $sql,
            [$executions_id]
        );
    }

    public function updateExecutionToUseLatestVersionOfDefinition(string $execution_id, int $definition_changeset_id): void
    {
        $sql = 'REPLACE INTO plugin_testmanagement_execution (execution_artifact_id, definition_changeset_id)
                VALUES (?, ?)';

        $this->getDB()->run($sql, $execution_id, $definition_changeset_id);
    }

    public function removeExecution(int $execution_id): void
    {
        $this->getDB()->delete('plugin_testmanagement_execution', [
            'execution_artifact_id' => $execution_id
        ]);
    }

    public function searchByExecutionTrackerId(int $execution_tracker_id): array
    {
        $sql = 'SELECT exec.*
            FROM plugin_testmanagement_execution AS exec
            INNER JOIN tracker_artifact AS art
                ON (art.id = exec.execution_artifact_id AND art.tracker_id = ?)';

        return $this->getDB()->run($sql, $execution_tracker_id);
    }

    public function updateExecutionToUseSpecificVersionOfDefinition(
        int $execution_artifact_id,
        int $execution_tracker_id,
        int $definition_changeset_id,
        int $definition_tracker_id
    ): void {
        $sql = 'REPLACE INTO plugin_testmanagement_execution
            SELECT exec.id, def_changeset.id
            FROM tracker_artifact AS exec,
                 tracker_artifact AS def
                INNER JOIN tracker_changeset AS def_changeset
                    ON (def.id = def_changeset.artifact_id)
            WHERE
                exec.id = ? AND exec.tracker_id = ?
                AND def_changeset.id = ? AND def.tracker_id = ?';

        $this->getDB()->run(
            $sql,
            $execution_artifact_id,
            $execution_tracker_id,
            $definition_changeset_id,
            $definition_tracker_id
        );
    }

    /**
     * @psalm-return list<array{id:int, tracker_id: int, last_changeset_id: int, submitted_by:int, submitted_on: int, use_artifact_permissions: bool, per_tracker_id: int, last_update_date: int, last_updated_by_id:int}>
     */
    public function searchLastTestExecUpdate(int $project_id, int $nb_max, array $current_user_ugroup_ids): array
    {
        $user_ugroup_ids = EasyStatement::open()->in('?*', $current_user_ugroup_ids);

        $sql = "SELECT test_campaign.id as id, test_campaign.*, test_exec_changeset.submitted_on AS last_update_date, test_exec.submitted_by AS last_updated_by_id
    FROM tracker_artifact AS test_campaign
             JOIN tracker_changeset_value AS campaign_cv ON (campaign_cv.changeset_id = test_campaign.last_changeset_id)
             JOIN tracker_changeset_value_artifactlink AS campaign_artlink ON (campaign_artlink.changeset_value_id = campaign_cv.id)
             JOIN tracker_artifact AS test_exec ON (test_exec.id = campaign_artlink.artifact_id)
             JOIN tracker_changeset_value AS test_exec_cv ON (test_exec_cv.changeset_id = test_exec.last_changeset_id )
             JOIN tracker_changeset AS test_exec_changeset ON (test_exec_changeset.id = test_exec_cv.changeset_id )
             JOIN tracker_changeset_value_artifactlink AS test_exec_artlink ON (test_exec_artlink.changeset_value_id = test_exec_cv.id)
             JOIN tracker_artifact AS artifact ON (artifact.id = test_exec_artlink.artifact_id)
             JOIN tracker ON (tracker.id = artifact.tracker_id)
             JOIN plugin_testmanagement AS testmanagement_config ON (testmanagement_config.project_id = tracker.group_id)
             LEFT JOIN permissions ON (permissions.object_id = CAST(test_exec.id AS CHAR CHARACTER SET utf8)
                   AND permissions.permission_type = 'PLUGIN_TRACKER_ARTIFACT_ACCESS')
            WHERE testmanagement_config.campaign_tracker_id = test_campaign.tracker_id
              AND testmanagement_config.test_execution_tracker_id = test_exec.tracker_id
              AND testmanagement_config.project_id = ?
              AND (test_exec.use_artifact_permissions = 0 OR permissions.ugroup_id IN ($user_ugroup_ids))
            GROUP BY test_campaign.id, test_exec_changeset.submitted_on
            LIMIT ?";

        $parameters_for_finding_all_test_exec_per_test_def = array_merge(
            $user_ugroup_ids->values(),
        );

        return $this->getDB()->safeQuery(
            $sql,
            array_merge(
                [$project_id],
                $parameters_for_finding_all_test_exec_per_test_def,
                [$nb_max]
            ),
            \PDO::FETCH_UNIQUE
        );
    }
}
