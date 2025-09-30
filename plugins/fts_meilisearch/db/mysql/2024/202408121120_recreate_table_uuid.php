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

// phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace,Squiz.Classes.ValidClassName.NotPascalCase
final class b202408121120_recreate_table_uuid extends \Tuleap\ForgeUpgrade\Bucket
{
    public function description(): string
    {
        return 'Re-create table to use UUID';
    }

    public function up(): void
    {
        $this->api->dropTable('plugin_fts_meilisearch_item');
        $this->api->dropTable('plugin_fts_meilisearch_metadata');
        $this->api->createTable(
            'plugin_fts_meilisearch_item',
            'CREATE TABLE IF NOT EXISTS plugin_fts_meilisearch_item (
                    id BINARY(16) NOT NULL PRIMARY KEY,
                    type VARCHAR(255) NOT NULL,
                    project_id INT(11),
                    INDEX idx_type(type),
                    INDEX idx_project(project_id)
                ) ENGINE=InnoDB;'
        );
        $this->api->createTable(
            'plugin_fts_meilisearch_metadata',
            'CREATE TABLE IF NOT EXISTS plugin_fts_meilisearch_metadata (
                    item_id BINARY(16) NOT NULL,
                    name VARCHAR(255) NOT NULL,
                    value VARCHAR(255) NOT NULL,
                    INDEX idx_key_value(name, value),
                    UNIQUE KEY (item_id, name)
                ) ENGINE=InnoDB;'
        );
    }
}
