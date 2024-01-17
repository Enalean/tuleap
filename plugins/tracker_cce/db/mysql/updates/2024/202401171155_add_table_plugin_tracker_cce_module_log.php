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

// phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace,Squiz.Classes.ValidClassName.NotCamelCaps
final class b202401171155_add_table_plugin_tracker_cce_module_log extends \Tuleap\ForgeUpgrade\Bucket
{
    public function description(): string
    {
        return "Add table plugin_tracker_cce_module_log";
    }

    public function up(): void
    {
        $this->api->createTable(
            'plugin_tracker_cce_module_log',
            <<<SQL
            CREATE TABLE IF NOT EXISTS plugin_tracker_cce_module_log
            (
                id                     int(11) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
                status                 varchar(64)      NOT NULL,
                changeset_id           int(11)          NOT NULL,
                source_payload_json    text             NOT NULL,
                generated_payload_json text             NULL,
                error_message          text             NULL,
                execution_date         int(11)          NOT NULL,

                INDEX idx_changeset_id (changeset_id),
                INDEX ids_execution_date (execution_date)
            ) ENGINE = InnoDB;
            SQL
        );
    }
}
