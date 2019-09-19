<?php
/**
 * Copyright (c) Enalean SAS 2014. All rights reserved
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
 * Add a table to store UGroup permissions that are valid for all the forge
 */
class b201403061540_add_forge_permissions_table extends ForgeUpgrade_Bucket
{
    public function description()
    {
        return "Add a table to store UGroup permissions that are valid for all the forge and update ugroup table";
    }

    public function preUp()
    {
        $this->db = $this->getApi('ForgeUpgrade_Bucket_Db');
    }

    public function up()
    {
        $sql = "CREATE TABLE IF NOT EXISTS ugroup_forge_permission (
                    ugroup_id INT(11) NOT NULL,
                    permission_id INT(11) NOT NULL,
                    INDEX idx_user_group_id (ugroup_id)
                )";

        if (! $this->db->tableNameExists('ugroup_forge_permission')) {
            $res = $this->db->dbh->exec($sql);
            if ($res === false) {
                throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete('An error occured while adding table ugroup_forge_permission');
            }
        }

        $sql = "ALTER TABLE ugroup
                    CHANGE group_id group_id int(11) NULL";

        $res = $this->db->dbh->exec($sql);
        if ($res === false) {
            throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete('An error occured while updating column group_id in TABLE ugroup');
        }
    }
}
