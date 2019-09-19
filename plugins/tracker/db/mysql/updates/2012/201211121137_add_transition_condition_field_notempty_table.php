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

class b201211121137_add_transition_condition_field_notempty_table extends ForgeUpgrade_Bucket
{

    public function description()
    {
        return 'Add a table not empty field condition on transitions';
    }

    public function preUp()
    {
        $this->db = $this->getApi('ForgeUpgrade_Bucket_Db');
    }

    public function up()
    {
        $sql = "CREATE TABLE  tracker_workflow_transition_condition_field_notempty(
                    id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
                    transition_id INT(11) NOT NULL,
                    field_id INT(11) NOT NULL
                ) ENGINE=InnoDB";
        $this->createTable('tracker_workflow_transition_condition_field_notempty', $sql);
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
