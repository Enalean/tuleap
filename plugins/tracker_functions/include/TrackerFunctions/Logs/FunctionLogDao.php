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

namespace Tuleap\TrackerFunctions\Logs;

use Tracker_ArtifactFactory;
use Tuleap\DB\DataAccessObject;

final class FunctionLogDao extends DataAccessObject implements SaveFunctionLog, RetrieveLogsForTracker, DeleteLogsPerTracker
{
    private const TABLE_NAME = 'plugin_tracker_functions_log';

    private const MAX_RECORD_PER_TRACKER = 50;

    public function __construct(private readonly Tracker_ArtifactFactory $artifact_factory)
    {
        parent::__construct();
    }

    public function deleteLogsPerTracker(int $tracker_id): void
    {
        $this->getDB()->run(
            <<<SQL
            DELETE rm_log
            FROM plugin_tracker_functions_log AS rm_log
                INNER JOIN tracker_changeset AS changeset ON rm_log.changeset_id = changeset.id
                INNER JOIN tracker_artifact AS artifact ON changeset.artifact_id = artifact.id
            WHERE artifact.tracker_id = ?
            SQL,
            $tracker_id
        );
    }

    /**
     * @return FunctionLogLineWithArtifact[]
     */
    public function searchLogsByTrackerId(int $tracker_id): array
    {
        return array_map(
            fn(array $row) => new FunctionLogLineWithArtifact(
                $row['log_id'],
                match ($row['status']) {
                    FunctionLogLine::STATUS_PASSED => FunctionLogLine::buildPassed(
                        $row['changeset_id'],
                        $this->getDB()->cell('SELECT log.source_payload_json FROM plugin_tracker_functions_log AS log WHERE log.changeset_id = ?', $row['changeset_id']),
                        $this->getDB()->cell('SELECT log.generated_payload_json FROM plugin_tracker_functions_log AS log WHERE log.changeset_id = ?', $row['changeset_id']),
                        $row['execution_date'],
                    ),
                    FunctionLogLine::STATUS_ERROR  => FunctionLogLine::buildError(
                        $row['changeset_id'],
                        $this->getDB()->cell('SELECT log.source_payload_json FROM plugin_tracker_functions_log AS log WHERE log.changeset_id = ?', $row['changeset_id']),
                        $this->getDB()->cell('SELECT log.error_message FROM plugin_tracker_functions_log AS log WHERE log.changeset_id = ?', $row['changeset_id']),
                        $row['execution_date'],
                    )
                },
                $this->artifact_factory->getInstanceFromRow($row),
            ),
            $this->getDB()->run(
                <<<EOSQL
                SELECT log.id AS log_id,
                       log.status,
                       log.changeset_id,
                       log.execution_date,
                       artifact.*,
                       CVT.value AS title,
                       CVT.body_format AS title_format
                FROM plugin_tracker_functions_log AS log
                    INNER JOIN tracker_changeset AS changeset ON (log.changeset_id = changeset.id)
                    INNER JOIN tracker_artifact AS artifact ON (
                        changeset.artifact_id = artifact.id
                        AND artifact.tracker_id = ?
                    )
                    LEFT JOIN (
                        tracker_changeset_value AS CV
                        INNER JOIN tracker_semantic_title as ST ON (CV.field_id = ST.field_id)
                        INNER JOIN tracker_changeset_value_text AS CVT ON (CV.id = CVT.changeset_value_id)
                    ) ON (artifact.last_changeset_id = CV.changeset_id)
                ORDER BY log.execution_date DESC
                EOSQL,
                $tracker_id,
            )
        );
    }

    public function saveFunctionLogLine(FunctionLogLine $log_line): void
    {
        $this->getDB()->insert(self::TABLE_NAME, $log_line->toArray());
        $this->cleanupLogs($log_line->changeset_id);
    }

    /**
     * Delete logs outside the quantity limit
     * @see self::MAX_RECORD_PER_TRACKER
     */
    private function cleanupLogs(int $changeset_id): void
    {
        $this->getDB()->run(
            <<<SQL
            DELETE rm_log
            FROM plugin_tracker_functions_log AS rm_log
                INNER JOIN tracker_changeset AS changeset ON rm_log.changeset_id = changeset.id
                INNER JOIN tracker_artifact AS artifact ON changeset.artifact_id = artifact.id
                INNER JOIN (
                    SELECT tracker_artifact.tracker_id
                    FROM tracker_changeset INNER JOIN tracker_artifact ON (tracker_changeset.artifact_id = tracker_artifact.id AND tracker_changeset.id = ?)
                ) AS current_tracker ON (current_tracker.tracker_id = artifact.tracker_id)
                LEFT JOIN (
                    SELECT keep_log.id
                    FROM plugin_tracker_functions_log AS keep_log
                        INNER JOIN tracker_changeset AS changeset ON keep_log.changeset_id = changeset.id
                        INNER JOIN tracker_artifact AS artifact ON changeset.artifact_id = artifact.id
                        INNER JOIN (
                            SELECT tracker_artifact.tracker_id
                            FROM tracker_changeset INNER JOIN tracker_artifact ON (tracker_changeset.artifact_id = tracker_artifact.id AND tracker_changeset.id = ?)
                        ) AS current_tracker ON (current_tracker.tracker_id = artifact.tracker_id)
                    ORDER BY keep_log.execution_date DESC
                    LIMIT ?
                ) AS keep_log ON rm_log.id = keep_log.id
            WHERE keep_log.id IS NULL
            SQL,
            $changeset_id,
            $changeset_id,
            self::MAX_RECORD_PER_TRACKER,
        );
    }
}
