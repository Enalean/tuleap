<?php
/**
 * Copyright (c) Enalean, 2013. All Rights Reserved.
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

class b201304230915_create_burndown_field_table extends ForgeUpgrade_Bucket
{

    public function description()
    {
        return 'Add burndown build table.';
    }

    public function preUp()
    {
        $this->db = $this->getApi('ForgeUpgrade_Bucket_Db');
    }

    public function up()
    {
        $sql = "CREATE TABLE IF NOT EXISTS tracker_field_burndown (
                    field_id int(11) UNSIGNED NOT NULL PRIMARY KEY,
                    include_weekends tinyint(1) NOT NULL
                ) ENGINE=InnoDB";
        $this->db->createTable('tracker_field_burndown', $sql);

        $sql = "INSERT INTO tracker_field_burndown (field_id, include_weekends)
                    SELECT id, 1 FROM tracker_field WHERE formElement_type='burndown'";
        $this->executeQuery($sql);
    }

    private function executeQuery($sql)
    {
        $res = $this->db->dbh->query($sql);
        if ($res === false) {
            throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete('An error occured while populating tracker_field_burndown: ' . implode(', ', $this->db->dbh->errorInfo()));
        }
    }

    public function postUp()
    {
        if (!$this->db->tableNameExists('tracker_field_burndown')) {
            throw new ForgeUpgrade_Bucket_Exception_UpgradeNotCompleteException('tracker_field_burndown');
        }
    }
}
