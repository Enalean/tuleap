<?php
/**
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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

class b201810251455_add_user_ldap_index extends ForgeUpgrade_Bucket // phpcs:ignore
{
    public function description()
    {
        return 'Ad index on user ldap_id column';
    }

    public function preUp()
    {
        $this->db = $this->getApi('ForgeUpgrade_Bucket_Db');
    }

    public function up()
    {
        if (! $this->indexNameExists('user', 'idx_ldap_id')) {
            $sql = "ALTER TABLE user
                ADD INDEX idx_ldap_id(ldap_id(10))";

            $res = $this->db->dbh->exec($sql);

            if ($res === false) {
                throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete('Unable to add ldap_id index on table user');
            }
        }
    }

    public function postUp()
    {
        if (! $this->indexNameExists('user', 'idx_ldap_id')) {
            throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete(
                'idx_ldap_id on user is missing'
            );
        }
    }

    private function indexNameExists($table_name, $index)
    {
        $sql = 'SHOW INDEX FROM ' . $table_name . ' WHERE Key_name LIKE ' . $this->db->dbh->quote($index);
        $res = $this->db->dbh->query($sql);
        if ($res && $res->fetch() !== false) {
            return true;
        } else {
            return false;
        }
    }
}
