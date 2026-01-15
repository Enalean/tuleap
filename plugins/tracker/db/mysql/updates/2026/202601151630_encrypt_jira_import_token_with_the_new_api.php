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
final class b202601151630_encrypt_jira_import_token_with_the_new_api extends \Tuleap\ForgeUpgrade\Bucket
{
    public function description(): string
    {
        return 'Move to UUID and re-encrypt JIRA tokens using the current Tuleap cryptography API';
    }

    public function up(): void
    {
        $this->api->addNewUUIDColumnToReplaceAutoIncrementedID('plugin_tracker_pending_jira_import', 'id', 'uuid');
        $this->api->dbh->exec('ALTER TABLE plugin_tracker_pending_jira_import DROP COLUMN id, RENAME COLUMN uuid TO id, ADD PRIMARY KEY (id)');
        $this->api->reencrypt2025ContentWithTheCurrentCryptographyAPI(
            'plugin_tracker_pending_jira_import',
            'id',
            'encrypted_jira_token'
        );

        $this->api->dbh->exec('DELETE FROM async_events WHERE topic = "tuleap.tracker.creation.jira"');
        $this->api->dbh->exec(
            "INSERT INTO async_events
            SELECT
                id,
                'app_user_events' AS queue_name,
                'tuleap.tracker.creation.jira' AS topic,
                CONCAT('{\"pending_jira_import_id\":\"', BIN_TO_UUID(id), '\"}') AS payload,
                UNIX_TIMESTAMP() AS enqueue_timestamp,
                0 AS enqueue_timestamp_microsecond,
                0 AS nb_added_in_queue
            FROM plugin_tracker_pending_jira_import"
        );
    }
}
