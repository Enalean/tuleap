<?php
/**
 * Copyright (c) Enalean, 2024-present. All Rights Reserved.
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

// phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace,Squiz.Classes.ValidClassName.NotPascalCase
final class b202403271630_add_async_events_table extends \Tuleap\ForgeUpgrade\Bucket
{
    public function description(): string
    {
        return 'Create new async_events table';
    }

    public function up(): void
    {
        $this->api->createTable(
            'async_events',
            <<<EOS
            CREATE TABLE async_events (
                id BINARY(16) NOT NULL PRIMARY KEY,
                queue_name VARCHAR(255) NOT NULL,
                topic VARCHAR(255) NOT NULL,
                payload JSON NOT NULL,
                enqueue_timestamp INT UNSIGNED NOT NULL,
                enqueue_timestamp_microsecond MEDIUMINT UNSIGNED NOT NULL,
                nb_added_in_queue TINYINT UNSIGNED NOT NULL,
                INDEX idx_queue_name (queue_name)
            )
            EOS
        );
    }
}
