<?php
/**
 * Copyright (c) Enalean, 2026-Present. All Rights Reserved.
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

// phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace,Squiz.Classes.ValidClassName.NotPascalCase
final class b202601161730_move_fork_create_delete_async_queue extends \Tuleap\ForgeUpgrade\Bucket
{
    public function description(): string
    {
        return 'Move system events creating, deleting and forking Git repositories to the async queue';
    }

    public function up(): void
    {
        $this->api->dbh->exec(
            "INSERT INTO async_events
             SELECT UUID_TO_BIN(UUID()) AS id, 'app_user_events' AS queue_name, 'tuleap.git.repository-change' AS topic, CONCAT('{\"repository_id\":', CAST(parameters AS UNSIGNED), '}') AS payload, UNIX_TIMESTAMP() AS enqueue_timestamp, 0 AS enqueue_timestamp_microsecond, 0 AS nb_added_in_queue
             FROM system_event WHERE type = 'GIT_REPO_UPDATE' AND status = 'NEW'"
        );
        $this->api->dbh->exec(
            "INSERT INTO async_events
             SELECT UUID_TO_BIN(UUID()) AS id, 'app_user_events' AS queue_name, 'tuleap.git.repository-change' AS topic, CONCAT('{\"repository_id\":', SUBSTRING_INDEX(parameters, '::', -1), '}') AS payload, UNIX_TIMESTAMP() AS enqueue_timestamp, 0 AS enqueue_timestamp_microsecond, 0 AS nb_added_in_queue
             FROM system_event WHERE type = 'GIT_REPO_DELETE' AND status = 'NEW'"
        );
        $this->api->dbh->exec(
            "INSERT INTO async_events
             SELECT UUID_TO_BIN(UUID()) AS id, 'app_user_events' AS queue_name, 'tuleap.git.repository-fork' AS topic, CONCAT('{\"repository_id\":', SUBSTRING_INDEX(parameters, '::', -1), '}') AS payload, UNIX_TIMESTAMP() AS enqueue_timestamp, 0 AS enqueue_timestamp_microsecond, 0 AS nb_added_in_queue
             FROM system_event WHERE type = 'GIT_REPO_FORK' AND status = 'NEW'"
        );
    }
}
