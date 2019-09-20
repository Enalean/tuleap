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

class b201602151330_create_unlinked_account_table extends ForgeUpgrade_Bucket
{
    public function description()
    {
        return 'Create table plugin_openidconnectclient_unlinked_account for OpenID Connect Client plugin';
    }

    public function preUp()
    {
        $this->db = $this->getApi('ForgeUpgrade_Bucket_Db');
    }

    public function up()
    {
        $sql = 'CREATE TABLE IF NOT EXISTS plugin_openidconnectclient_unlinked_account (
                  id VARCHAR(32) NOT NULL,
                  provider_id INT(11) UNSIGNED NOT NULL,
                  openidconnect_identifier TEXT NOT NULL,
                  PRIMARY KEY(id)
                );';

        $res = $this->db->dbh->exec($sql);
        if ($res === false) {
            throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete(
                'An error occurred while creating the plugin_openidconnectclient_unlinked_account table for OpenID Connect Client plugin'
            );
        }
    }
}
