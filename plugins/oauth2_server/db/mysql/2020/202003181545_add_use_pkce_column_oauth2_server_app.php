<?php
/**
 * Copyright (c) Enalean, 2020-Present. All Rights Reserved.
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
final class b202003181545_add_use_pkce_column_oauth2_server_app extends \Tuleap\ForgeUpgrade\Bucket
{
    public function description(): string
    {
        return 'Add an "use_pkce" column on the plugin_oauth2_server_app table';
    }

    public function preUp(): void
    {
        $this->db = $this->getApi('ForgeUpgrade_Bucket_Db');
    }

    public function up(): void
    {
        if (! $this->db->columnNameExists('plugin_oauth2_server_app', 'use_pkce')) {
            $sql = 'ALTER TABLE plugin_oauth2_server_app ADD COLUMN use_pkce BOOLEAN NOT NULL DEFAULT FALSE';
            $res = $this->db->dbh->exec($sql);
            if ($res === false) {
                throw new \Tuleap\ForgeUpgrade\Bucket\BucketUpgradeNotCompleteException('Unable to add use_pkce column on the plugin_oauth2_server_app table');
            }
        }
        $res = $this->db->dbh->exec('ALTER TABLE plugin_oauth2_server_app ALTER COLUMN use_pkce DROP DEFAULT');
        if ($res === false) {
            throw new \Tuleap\ForgeUpgrade\Bucket\BucketUpgradeNotCompleteException('Unable to drop default value of the use_pkce column on the plugin_oauth2_server_app table');
        }
    }
}
