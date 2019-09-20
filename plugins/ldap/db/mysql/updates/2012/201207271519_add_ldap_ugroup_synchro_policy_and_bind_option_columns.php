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

class b201207271519_add_ldap_ugroup_synchro_policy_and_bind_option_columns extends ForgeUpgrade_Bucket
{

    /**
     * Description of the bucket
     *
     * @return String
     */
    public function description()
    {
        return <<<EOT
Add synchro_policy & bind_option columns to plugin_ldap_ugroup table
EOT;
    }

    /**
     * Obtain the bucket API
     *
     * @retun Void
     */
    public function preUp()
    {
        $this->db = $this->getApi('ForgeUpgrade_Bucket_Db');
    }

    /**
     * Add the column
     *
     * @return Void
     */
    public function up()
    {
        $sql = "ALTER TABLE plugin_ldap_ugroup ADD COLUMN synchro_policy VARCHAR(255) NOT NULL default 'never' AFTER ldap_group_dn";
        $res = $this->db->dbh->exec($sql);
        if ($res === false) {
            throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete('An error occured while adding the column synchro_policy to the table plugin_ldap_ugroup');
        }

        $sql = "ALTER TABLE plugin_ldap_ugroup ADD COLUMN bind_option varchar(255) NOT NULL default 'bind' AFTER synchro_policy";
        $res = $this->db->dbh->exec($sql);
        if ($res === false) {
            throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete('An error occured while adding the column bind_option  to the table plugin_ldap_ugroup');
        }
    }

    /**
     * Verify the column is added
     *
     * @return Void
     */
    public function postUp()
    {
        if (!$this->db->columnNameExists('plugin_ldap_ugroup', 'synchro_policy')) {
            throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete('The column synchro_policy in table plugin_ldap_ugroup still not created');
        }
        if (!$this->db->columnNameExists('plugin_ldap_ugroup', 'bind_option')) {
            throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete('The column bind_option in table plugin_ldap_ugroup still not created');
        }
    }
}
