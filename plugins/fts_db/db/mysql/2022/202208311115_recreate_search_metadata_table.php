<?php
/**
 * Copyright (c) Enalean, 2022-Present. All Rights Reserved.
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
final class b202208311115_recreate_search_metadata_table extends \Tuleap\ForgeUpgrade\Bucket
{
    public function description(): string
    {
        return 'Fix search_id column in plugin_fts_db_metadata table';
    }

    public function up(): void
    {
        $this->api->dbh->exec('DROP TABLE IF EXISTS plugin_fts_db_metadata');
        $this->api->createTable(
            'plugin_fts_db_metadata',
            'CREATE TABLE IF NOT EXISTS plugin_fts_db_metadata (
                    search_id INT(11) UNSIGNED NOT NULL,
                    name VARCHAR(255) NOT NULL,
                    value VARCHAR(255) NOT NULL,
                    INDEX idx_key_value(name, value),
                    UNIQUE KEY (search_id, name)
                ) ENGINE=InnoDB'
        );
    }
}
