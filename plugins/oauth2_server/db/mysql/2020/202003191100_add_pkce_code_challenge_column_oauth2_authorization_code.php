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
final class b202003191100_add_pkce_code_challenge_column_oauth2_authorization_code extends ForgeUpgrade_Bucket
{
    public function description(): string
    {
        return 'Add an "pkce_code_challenge" column on the plugin_oauth2_authorization_code table';
    }

    public function preUp(): void
    {
        $this->db = $this->getApi('ForgeUpgrade_Bucket_Db');
    }

    public function up(): void
    {
        if (! $this->db->columnNameExists('plugin_oauth2_authorization_code', 'pkce_code_challenge')) {
            $sql = 'ALTER TABLE plugin_oauth2_authorization_code ADD COLUMN pkce_code_challenge BINARY(32)';
            $res = $this->db->dbh->exec($sql);
            if ($res === false) {
                throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete('Unable to add pkce_code_challenge column on the plugin_oauth2_authorization_code table');
            }
        }
    }
}
