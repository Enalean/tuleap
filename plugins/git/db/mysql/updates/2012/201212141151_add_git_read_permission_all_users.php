<?php
/**
 * Copyright (c) Enalean, 2012. All Rights Reserved.
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

class b201212141151_add_git_read_permission_all_users extends ForgeUpgrade_Bucket
{

    public function description()
    {
        return <<<EOT
Add git read permission types to all users
EOT;
    }

    public function preUp()
    {
        $this->db = $this->getApi('ForgeUpgrade_Bucket_Db');
    }

    public function up()
    {
        if ($this->permissionTypesMissing()) {
            $sql = "INSERT INTO permissions_values (permission_type, ugroup_id, is_default)
                    VALUES ('PLUGIN_GIT_READ', 1, 0)";
            $res = $this->db->dbh->exec($sql);
            if ($res !== 1) {
                throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete('An error occured while adding permission types into permissions_values');
            }
        }
    }

    public function postUp()
    {
        if ($this->permissionTypesMissing()) {
            throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete('Git read permission to anonymous users for gitolite is missing');
        }
    }

    protected function permissionTypesMissing()
    {
        $sql = "SELECT count(*) as nb FROM permissions_values WHERE permission_type = 'PLUGIN_GIT_READ' AND ugroup_id = 1";
        $res = $this->db->dbh->query($sql);
        $row = $res->fetch();
        return $res && $row['nb'] == 0;
    }
}
