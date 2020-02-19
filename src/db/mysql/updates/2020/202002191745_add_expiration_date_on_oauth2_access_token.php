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
final class b202002191745_add_expiration_date_on_oauth2_access_token extends ForgeUpgrade_Bucket
{
    public function description(): string
    {
        return 'Add an expiration date on OAuth2 access token table';
    }

    public function preUp(): void
    {
        $this->db = $this->getApi('ForgeUpgrade_Bucket_Db');
    }

    public function up(): void
    {
        $sql = 'DELETE oauth2_access_token.*, oauth2_access_token_scope.* FROM oauth2_access_token, oauth2_access_token_scope';
        $res = $this->db->dbh->exec($sql);
        if ($res === false) {
            throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete('Unable to clear the oauth2_access_token and oauth2_access_token_scope tables');
        }

        if (! $this->db->columnNameExists('oauth2_access_token', 'expiration_date')) {
            $sql = 'ALTER TABLE oauth2_access_token ADD COLUMN expiration_date INT(11) UNSIGNED NOT NULL, ADD INDEX idx_expiration_date(expiration_date)';
            $res = $this->db->dbh->exec($sql);
            if ($res === false) {
                throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete('Unable to add expiration_date on the oauth2_access_token table');
            }
        }

        if (! $this->db->indexNameExists('oauth2_access_token', 'idx_expiration_date')) {
            $sql = 'ALTER TABLE oauth2_access_token ADD INDEX idx_expiration_date(expiration_date)';
            $res = $this->db->dbh->exec($sql);
            if ($res === false) {
                throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete('Unable to add an index for expiration_date on the oauth2_access_token table');
            }
        }
    }
}
