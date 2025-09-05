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
use Tuleap\Option\Option;

final class FunctionLogDao extends DataAccessObject implements SaveFunctionLog, RetrieveLogsForTracker, RetrievePayloadsForChangeset, DeleteLogsPerTracker
{
    private const TABLE_NAME = 'plugin_tracker_functions_log';

    private const MAX_RECORD_PER_TRACKER = 50;

    public function __construct(private readonly Tracker_ArtifactFactory $artifact_factory)
    {
        parent::__construct();
    }

    #[\Override]
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
    #[\Override]
    public function searchLogsByTrackerId(int $tracker_id): array
    {
        return array_map(
            fn(array $row) => new FunctionLogLineWithArtifact(
                $row['log_id'],
                match ($row['status']) {
                    FunctionLogLineStatus::PASSED->value => FunctionLogLine::buildPassed(
                        $row['changeset_id'],
                        $row['execution_date'],
                    ),
                    FunctionLogLineStatus::ERROR->value  => FunctionLogLine::buildError(
                        $row['changeset_id'],
                        $row['error_message'],
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
                       log.error_message,
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

    /**
     * @psalm-return Option<FunctionLogPayloads>
     */
    #[\Override]
    public function searchPayloadsByChangesetID(int $changeset_id): Option
    {
        $row = $this->getDB()->row(
            '
            SELECT tracker_artifact.tracker_id, plugin_tracker_functions_log.source_payload_json, plugin_tracker_functions_log.generated_payload_json
            FROM plugin_tracker_functions_log
            JOIN tracker_changeset ON (tracker_changeset.id = plugin_tracker_functions_log.changeset_id)
            JOIN tracker_artifact ON (tracker_artifact.id = tracker_changeset.artifact_id)
            WHERE tracker_changeset.id = ?',
            $changeset_id
        );

        if ($row === null) {
            return Option::nothing(FunctionLogPayloads::class);
        }

        return Option::fromValue(
            new FunctionLogPayloads(
                $row['tracker_id'],
                $row['source_payload_json'],
                Option::fromNullable($row['generated_payload_json']),
            )
        );
    }

    #[\Override]
    public function saveFunctionLogLine(FunctionLogLineToSave $log_line): void
    {
        $this->getDB()->insert(
            self::TABLE_NAME,
            [
                'status'                 => $log_line->status->value,
                'changeset_id'           => $log_line->changeset_id,
                'source_payload_json'    => $log_line->source_payload_json,
                'generated_payload_json' => $log_line->generated_payload_json,
                'error_message'          => $log_line->error_message,
                'execution_date'         => $log_line->execution_date,
            ]
        );
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
