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

class b201606010939_add_git_default_permission_values extends ForgeUpgrade_Bucket
{

    public function description()
    {
        return <<<EOT
Insert new entries in permission_values for git default permissions.
EOT;
    }

    public function preUp()
    {
        $this->db = $this->getApi('ForgeUpgrade_Bucket_Db');
    }

    public function up()
    {
        $sql = "INSERT INTO permissions_values (permission_type, ugroup_id, is_default)
                VALUES ('PLUGIN_GIT_DEFAULT_READ', 2, 1),
                       ('PLUGIN_GIT_DEFAULT_READ', 3, 0),
                       ('PLUGIN_GIT_DEFAULT_READ', 4, 0),
                       ('PLUGIN_GIT_DEFAULT_READ', 1, 0),
                       ('PLUGIN_GIT_DEFAULT_WRITE', 2, 0),
                       ('PLUGIN_GIT_DEFAULT_WRITE', 3, 1),
                       ('PLUGIN_GIT_DEFAULT_WRITE', 4, 0),
                       ('PLUGIN_GIT_DEFAULT_WPLUS', 2, 0),
                       ('PLUGIN_GIT_DEFAULT_WPLUS', 3, 0),
                       ('PLUGIN_GIT_DEFAULT_WPLUS', 4, 0)";

        $this->execDB($sql, 'An error occured while inserting new entries in permission_values for git default permissions');
    }

    protected function execDB($sql, $message)
    {
        $res = $this->db->dbh->exec($sql);
        if ($res === false) {
            throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete($message . implode(', ', $this->db->dbh->errorInfo()));
        }
    }
}
