<?php
/**
 * Copyright (c) Enalean, 2011. All Rights Reserved.
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

class b201107071731_add_permission_type extends ForgeUpgrade_Bucket
{

    public function description()
    {
        return <<<EOT
Add permission types in order to manage gitolite integration
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
                    VALUES ('PLUGIN_GIT_READ', 2, 1),
                           ('PLUGIN_GIT_READ', 3, 0),
                           ('PLUGIN_GIT_READ', 4, 0),
                           ('PLUGIN_GIT_WRITE', 2, 0),
                           ('PLUGIN_GIT_WRITE', 3, 1),
                           ('PLUGIN_GIT_WRITE', 4, 0),
                           ('PLUGIN_GIT_WPLUS', 2, 0),
                           ('PLUGIN_GIT_WPLUS', 3, 0),
                           ('PLUGIN_GIT_WPLUS', 4, 0);";
            $res = $this->db->dbh->exec($sql);
            if ($res !== 9) {
                throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete('An error occured while adding permission types into permissions_values');
            }
        }
    }

    public function postUp()
    {
        if ($this->permissionTypesMissing()) {
            throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete('Permission types for gitolite are missing');
        }
    }

    protected function permissionTypesMissing()
    {
        $sql = "SELECT count(*) as nb FROM permissions_values WHERE permission_type = 'PLUGIN_GIT_READ'";
        $res = $this->db->dbh->query($sql);
        $row = $res->fetch();
        return $res && $row['nb'] == 0;
    }
}
