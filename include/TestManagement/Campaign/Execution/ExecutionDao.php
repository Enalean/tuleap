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
}
