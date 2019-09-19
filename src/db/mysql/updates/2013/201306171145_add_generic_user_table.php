<?php
/**
 * Copyright (c) Enalean SAS 2013. All rights reserved
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
 * Add generic_user table
 */
class b201306171145_add_generic_user_table extends ForgeUpgrade_Bucket
{
    public function description()
    {
        return <<<EOT
add generic_user table
EOT;
    }

    public function preUp()
    {
        $this->db = $this->getApi('ForgeUpgrade_Bucket_Db');
    }

    public function up()
    {
        $sql = "CREATE TABLE generic_user (
                    group_id INT(11) PRIMARY KEY,
                    user_id INT(11) NOT NULL
                )";
        if (! $this->db->tableNameExists('generic_user')) {
            $res = $this->db->dbh->exec($sql);
            if ($res === false) {
                throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete('An error occured while adding table generic_user');
            }
        }
    }

    public function postUp()
    {
        if (! $this->db->tableNameExists('generic_user')) {
            throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete('Table generic_user not created');
        }
    }
}
