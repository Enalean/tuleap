<?php
/**
 * Copyright (c) Enalean, 2014. All rights reserved
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/
 */

class b201411061153_add_name_to_table_plugin_git_mirrors extends ForgeUpgrade_Bucket
{

    public function description()
    {
        return <<<EOT
Modify plugin_git_mirrors table to add name column.
EOT;
    }

    public function preUp()
    {
        $this->db = $this->getApi('ForgeUpgrade_Bucket_Db');
    }

    public function up()
    {
        $sql = "ALTER TABLE plugin_git_mirrors
            ADD COLUMN name VARCHAR(255) NOT NULL";

        $res = $this->db->dbh->exec($sql);
        if ($res === false) {
            throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete('An error occured while adding column name in plugin_git_mirrors table.');
        }

        $sql2 = "UPDATE plugin_git_mirrors AS target
            LEFT JOIN plugin_git_mirrors AS source ON source.id = target.id
            SET target.name = source.url";

        $res2 = $this->db->dbh->exec($sql2);

        if ($res2 === false) {
            throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete('An error occured while adding default value to column name in plugin_git_mirrors table.');
        }
    }
}
