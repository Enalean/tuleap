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

class b201209251711_add_table_bind_ugroups extends ForgeUpgrade_Bucket
{

    public function description()
    {
        return 'Add a new table to store bind ugroups';
    }

    public function preUp()
    {
        $this->db = $this->getApi('ForgeUpgrade_Bucket_Db');
    }

    public function up()
    {
        $sql = "CREATE TABLE tracker_field_list_bind_ugroups_value(
                    id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
                    field_id INT(11) NOT NULL,
                    ugroup_id INT(11) NOT NULL,
                    is_hidden TINYINT(1) NOT NULL DEFAULT '0',
                    UNIQUE KEY idx(field_id, ugroup_id)
                ) ENGINE=InnoDB";
        $this->createTable('tracker_field_list_bind_ugroups_value', $sql);
    }

    private function createTable($name, $sql)
    {
        $result = $this->db->createTable($name, $sql);

        if ($result === false) {
            $error_message = implode(', ', $this->db->dbh->errorInfo());
            throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete($error_message);
        }
    }
}
