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
final class b202209151420_move_project_id_to_main_table extends \Tuleap\ForgeUpgrade\Bucket
{
    public function description(): string
    {
        return 'Move the project ID of an item from the generic K/V metadata table to main table';
    }

    public function up(): void
    {
        if (! $this->api->columnNameExists('plugin_fts_db_search', 'project_id')) {
            $this->api->dbh->exec('ALTER TABLE plugin_fts_db_search ADD COLUMN project_id INT(11)');
        }

        $this->api->addIndex(
            'plugin_fts_db_search',
            'idx_project_id',
            'ALTER TABLE plugin_fts_db_search ADD INDEX idx_project_id(project_id)'
        );

        $this->api->dbh->exec('UPDATE plugin_fts_db_search, plugin_fts_db_metadata
            SET plugin_fts_db_search.project_id = plugin_fts_db_metadata.value
            WHERE plugin_fts_db_search.id = plugin_fts_db_metadata.search_id AND plugin_fts_db_metadata.name = "project_id"');

        $this->api->dbh->exec('DELETE FROM plugin_fts_db_metadata WHERE plugin_fts_db_metadata.name = "project_id"');
    }
}
