<?php
/**
 * Copyright (c) Enalean, 2014. All Rights Reserved.
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

class b201401240954_add_git_perms_admin_values extends ForgeUpgrade_Bucket
{

    /**
     * Description of the bucket
     *
     * @return String
     */
    public function description()
    {
        return <<<EOT
Add PLUGIN_GIT_ADMIN right in table permissions
EOT;
    }

    /**
     * Get the API
     *
     * @return void
     */
    public function preUp()
    {
        $this->db = $this->getApi('ForgeUpgrade_Bucket_Db');
    }

    /**
     * Adding the data
     *
     * @return void
     */
    public function up()
    {
        $this->addDefaultGitAdminGroup();
        $this->addGenericUGroups();
    }

    private function addDefaultGitAdminGroup()
    {
        $sql = "INSERT INTO permissions_values (permission_type, ugroup_id, is_default)
                VALUES ('PLUGIN_GIT_ADMIN', 4, 1)";

        $res = $this->db->dbh->exec($sql);
        if ($res === false) {
            throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete('An error occured while adding PLUGIN_GIT_ADMIN rights in table permissions_values');
        }
    }

    private function addGenericUGroups()
    {
        $sql = "INSERT INTO permissions_values (permission_type, ugroup_id, is_default)
                VALUES ('PLUGIN_GIT_ADMIN', 1, 0),
                       ('PLUGIN_GIT_ADMIN', 2, 0),
                       ('PLUGIN_GIT_ADMIN', 3, 0)";

        $res = $this->db->dbh->exec($sql);
        if ($res === false) {
            throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete('An error occured while adding PLUGIN_GIT_ADMIN rights in table permissions_values');
        }
    }
}
