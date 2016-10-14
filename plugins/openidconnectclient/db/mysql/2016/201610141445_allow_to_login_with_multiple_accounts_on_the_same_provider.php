<?php
/**
 * Copyright (c) Enalean, 2016. All Rights Reserved.
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

class b201610141445_allow_to_login_with_multiple_accounts_on_the_same_provider extends ForgeUpgrade_Bucket
{
    public function description()
    {
        return 'Allow to login with multiple accounts on the same provider to one Tuleap account';
    }

    public function preUp()
    {
        $this->db = $this->getApi('ForgeUpgrade_Bucket_Db');
    }

    public function up()
    {
        $sql = "ALTER TABLE plugin_openidconnectclient_user_mapping
                DROP PRIMARY KEY,
                ADD COLUMN id INT(11) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
                ADD INDEX idx_mapping_provider_user(provider_id, user_id)";

        $res = $this->db->dbh->exec($sql);

        if ($res === false) {
            throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete(
                'An error occurred while adding column id to the plugin_openidconnectclient_user_mapping table'
            );
        }
    }

    public function postUp()
    {
        if (! $this->db->columnNameExists('plugin_openidconnectclient_user_mapping', 'id')) {
            throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete(
                'An error occurred while adding id column to the plugin_openidconnectclient_user_mapping table'
            );
        }
    }
}
