<?php
/**
 * Copyright (c) STMicroelectronics, 2012. All Rights Reserved.
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

/**
 *
 */
class b201207271519_add_ldap_ugroup_is_synchro_and_is_members_preserved_columns extends ForgeUpgrade_Bucket {

    /**
     * Description of the bucket
     *
     * @return String
     */
    public function description() {
        return <<<EOT
Add is_synchronized & is_member_preserved columns to plugin_ldap_ugroup table
EOT;
    }

    /**
     * Obtain the bucket API
     *
     * @retun Void
     */
    public function preUp() {
        $this->db = $this->getApi('ForgeUpgrade_Bucket_Db');
    }

    /**
     * Add the column
     *
     * @return Void
     */
    public function up() {
        $sql = "ALTER TABLE plugin_ldap_ugroup ADD COLUMN is_synchronized TINYINT(4) NOT NULL DEFAULT 0 AFTER ldap_group_dn";
        $res = $this->db->dbh->exec($sql);
        if ($res === false) {
            throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete('An error occured while adding the column is_synchronized to the table plugin_ldap_ugroup');
        }
        
        $sql = "ALTER TABLE plugin_ldap_ugroup ADD COLUMN is_members_preserved TINYINT(4) NOT NULL DEFAULT 1 AFTER is_synchronized";
        $res = $this->db->dbh->exec($sql);
        if ($res === false) {
            throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete('An error occured while adding the column is_members_preserved  to the table plugin_ldap_ugroup');
        }
    }

    /**
     * Verify the column is added
     *
     * @return void
     */
    public function postUp() {
        if (!$this->db->columnNameExists('plugin_ldap_ugroup', 'is_synchronized')) {
            throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete('The column is_synchronized in table plugin_ldap_ugroup still not created');
        }
        if (!$this->db->columnNameExists('plugin_ldap_ugroup', 'is_members_preserved')) {
            throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete('The column is_members_preserved in table plugin_ldap_ugroup still not created');
        }
    }

}
?>