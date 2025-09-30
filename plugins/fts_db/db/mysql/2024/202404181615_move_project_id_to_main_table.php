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
final class b202404181615_move_project_id_to_main_table extends \Tuleap\ForgeUpgrade\Bucket
{
    public function description(): string
    {
        return 'Replace the auto-incremented ID with UUID';
    }

    public function up(): void
    {
        $this->api->addNewUUIDColumnToReplaceAutoIncrementedID('plugin_fts_db_search', 'id', 'uuid');

        $this->api->createAndPopulateNewUUIDColumn(
            'plugin_fts_db_metadata',
            'search_uuid',
            function (): void {
                if (! $this->api->columnNameExists('plugin_fts_db_search', 'id')) {
                    return;
                }
                $sql = 'UPDATE plugin_fts_db_metadata
                    JOIN plugin_fts_db_search ON (plugin_fts_db_metadata.search_id = plugin_fts_db_search.id)
                    SET plugin_fts_db_metadata.search_uuid = plugin_fts_db_search.uuid';
                $this->api->dbh->exec($sql);
            }
        );
        $this->api->dbh->exec('ALTER TABLE plugin_fts_db_metadata DROP INDEX search_id, DROP COLUMN search_id, RENAME COLUMN search_uuid TO search_id, ADD UNIQUE KEY idx_unique_search_id_name(search_id, name)');

        $this->api->dbh->exec('ALTER TABLE plugin_fts_db_search DROP COLUMN id, RENAME COLUMN uuid TO id, ADD PRIMARY KEY (id)');
    }
}
