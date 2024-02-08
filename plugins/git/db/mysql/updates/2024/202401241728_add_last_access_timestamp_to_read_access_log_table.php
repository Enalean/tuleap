<?php
/**
 * Copyright (c) Enalean, 2024-present. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
 *
 *  Tuleap is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  Tuleap is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

// phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace,Squiz.Classes.ValidClassName.NotCamelCaps
final class b202401241728_add_last_access_timestamp_to_read_access_log_table extends \Tuleap\ForgeUpgrade\Bucket
{
    public function description(): string
    {
        return 'Add date last access timestamp column in plugin_git_log_read_daily and convert already existing date to timestamp in the created column';
    }

    public function up(): void
    {
        if (
            ! $this->api->columnNameExists('plugin_git_log_read_daily', 'day_last_access_timestamp')
        ) {
            $this->api->dbh->exec(
                'ALTER TABLE plugin_git_log_read_daily ADD COLUMN day_last_access_timestamp INT(11) UNSIGNED NOT NULL'
            );
        }
        if (! $this->api->indexNameExists('plugin_git_log_read_daily', 'timestamp_idx')) {
            $this->api->addIndex(
                'plugin_git_log_read_daily',
                'last_access_timestamp_idx',
                'CREATE INDEX last_access_timestamp_idx ON plugin_git_log_read_daily(day_last_access_timestamp, repository_id)'
            );
        }
        $this->api->dbh->exec('UPDATE plugin_git_log_read_daily SET day_last_access_timestamp = UNIX_TIMESTAMP(day)');
    }
}
