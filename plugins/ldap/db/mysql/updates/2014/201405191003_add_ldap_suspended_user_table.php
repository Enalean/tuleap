<?php
/**
 * Copyright (c) STMicroelectronics, 2014. All Rights Reserved.
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

class b201405191003_add_ldap_suspended_user_table extends ForgeUpgrade_Bucket
{

    /**
     * Description of the bucket
     *
     * @return String
     */
    public function description()
    {
        return <<<EOT
Add plugin_ldap_suspended_user table
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
     * Add the table
     *
     * @return Void
     */
    public function up()
    {
        $sql = "CREATE TABLE plugin_ldap_suspended_user (
                 user_id int(11) NOT NULL,
                 deletion_date int(11) NOT NULL
                )
        ";
        $res = $this->db->dbh->exec($sql);
        if ($res === false) {
            throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete('An error occured while adding the table plugin_ldap_suspended_user');
        }
    }

    /**
     * Verify the table is added
     *
     * @return Void
     */
    public function postUp()
    {
        if (! $this->db->tableNameExists('plugin_ldap_suspended_user')) {
            throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete('The table plugin_ldap_suspended_user still not created');
        }
    }
}
