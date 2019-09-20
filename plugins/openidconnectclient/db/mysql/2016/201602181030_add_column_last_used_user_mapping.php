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

class b201602181030_add_column_last_used_user_mapping extends ForgeUpgrade_Bucket
{

    public function description()
    {
        return 'Add last used in OpenID Connect user mapping';
    }

    public function preUp()
    {
        $this->db = $this->getApi('ForgeUpgrade_Bucket_Db');
    }

    public function up()
    {
        $this->addColumn();
        $this->dropDefault();
    }

    private function addColumn()
    {
        $sql = "ALTER TABLE plugin_openidconnectclient_user_mapping ADD COLUMN last_used INT(11) UNSIGNED NOT NULL DEFAULT 0";
        $res = $this->db->dbh->exec($sql);
        if ($res === false) {
            throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete('An error occurred while adding last used column in user mapping: ' . implode(', ', $this->db->dbh->errorInfo()));
        }
    }

    private function dropDefault()
    {
        $sql = "ALTER TABLE plugin_openidconnectclient_user_mapping ALTER COLUMN last_used DROP DEFAULT";
        $res = $this->db->dbh->exec($sql);
        if ($res === false) {
            throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete('An error occurred while modifying last used column in user mapping: ' . implode(', ', $this->db->dbh->errorInfo()));
        }
    }

    public function postUp()
    {
        if (! $this->db->columnNameExists('plugin_openidconnectclient_user_mapping', 'last_used')) {
            throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete('An error occurred while adding last used column in user mapping');
        }
    }
}
