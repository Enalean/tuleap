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
final class b202003131445_add_app_id_column_oauth2_auth_code extends ForgeUpgrade_Bucket
{
    public function description(): string
    {
        return 'Add an "app_id" column on the plugin_oauth2_authorization_code table';
    }

    public function preUp(): void
    {
        $this->db = $this->getApi('ForgeUpgrade_Bucket_Db');
    }

    public function up(): void
    {
        $sql = 'DELETE FROM plugin_oauth2_authorization_code';
        $res = $this->db->dbh->exec($sql);
        if ($res === false) {
            throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete('Unable to clear the plugin_oauth2_authorization_code table');
        }

        if (! $this->db->columnNameExists('plugin_oauth2_authorization_code', 'app_id')) {
            $sql = 'ALTER TABLE plugin_oauth2_authorization_code ADD COLUMN app_id INT(11) NOT NULL, ADD INDEX idx_app_id (app_id)';
            $res = $this->db->dbh->exec($sql);
            if ($res === false) {
                throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete('Unable to add app_id column on the plugin_oauth2_authorization_code table');
            }
        }
    }
}
