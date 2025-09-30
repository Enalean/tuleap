<?php
/**
 * Copyright (c) Enalean, 2022 - present. All Rights Reserved.
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
 *
 */

// phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace,Squiz.Classes.ValidClassName.NotPascalCase
final class b202202211726_add_filename_index extends \Tuleap\ForgeUpgrade\Bucket
{
    public function description(): string
    {
        return "Add filename fulltext index on 'plugin_docman_version' table";
    }

    public function up(): void
    {
        if ($this->api->indexNameExists('plugin_docman_version', 'fltxt_filename')) {
            $this->log->info('Index already exists');
            return;
        }

        $sql = 'ALTER TABLE plugin_docman_version ADD FULLTEXT fltxt_filename(filename)';

        if ($this->api->dbh->exec($sql) === false) {
            throw new \Tuleap\ForgeUpgrade\Bucket\BucketUpgradeNotCompleteException(
                'An error occurred while adding filename fulltext index on plugin_docman_version table'
            );
        }
    }
}
