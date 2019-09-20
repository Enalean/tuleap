<?php
/**
* Copyright Enalean (c) 2015. All rights reserved.
*
* Tuleap and Enalean names and logos are registrated trademarks owned by
* Enalean SAS. All other trademarks or names are properties of their respective
* owners.
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

class b201502061635_add_planning_priority_change_permission extends ForgeUpgrade_Bucket
{

    public function description()
    {
        return 'Add priority change permission in permissions_values table';
    }

    public function preUp()
    {
        $this->db = $this->getApi('ForgeUpgrade_Bucket_Db');
    }

    public function up()
    {
        $sql = "INSERT INTO permissions_values (permission_type, ugroup_id, is_default)
                VALUES ('PLUGIN_AGILEDASHBOARD_PLANNING_PRIORITY_CHANGE', 2, 0),
                       ('PLUGIN_AGILEDASHBOARD_PLANNING_PRIORITY_CHANGE', 3, 1),
                       ('PLUGIN_AGILEDASHBOARD_PLANNING_PRIORITY_CHANGE', 4, 0);";

        $res = $this->db->dbh->exec($sql);

        if (! $res) {
            throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete('An error occured while adding permission types into permissions_values');
        }
    }

    public function postUp()
    {
        if ($this->permissionTypesMissing()) {
            throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete('Permission types for agiledashboard planning are missing');
        }
    }

    protected function permissionTypesMissing()
    {
        $sql = "SELECT count(*) as nb FROM permissions_values WHERE permission_type = 'PLUGIN_AGILEDASHBOARD_PLANNING_PRIORITY_CHANGE'";
        $res = $this->db->dbh->query($sql);
        $row = $res->fetch();
        return $res && $row['nb'] == 0;
    }
}
