<?php
/**
 * Copyright (c) Enalean, 2022-Present. All Rights Reserved.
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

// phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace,Squiz.Classes.ValidClassName.NotPascalCase
final class b202202071123_add_oauth2_server_app_type extends \Tuleap\ForgeUpgrade\Bucket
{
    public function description(): string
    {
        return 'Add server type in plugin_oauth2_server_app table';
    }

    public function up(): void
    {
        if ($this->api->columnNameExists('plugin_oauth2_server_app', 'app_type')) {
            return;
        }

        $sql = "ALTER TABLE plugin_oauth2_server_app ADD COLUMN app_type VARCHAR(255) NOT NULL DEFAULT 'plugin_oauth2'";

        if ($this->api->dbh->exec($sql) === false) {
            throw new \Tuleap\ForgeUpgrade\Bucket\BucketUpgradeNotCompleteException(
                'An error occurred while adding app_type column to the table plugin_oauth2_server_app'
            );
        }
    }
}
