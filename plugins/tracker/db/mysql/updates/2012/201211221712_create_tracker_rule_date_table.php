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

class b201211221712_create_tracker_rule_date_table extends ForgeUpgrade_Bucket
{

    public function description()
    {
        return 'Add tracker_rule_date table';
    }

    public function preUp()
    {
        $this->db = $this->getApi('ForgeUpgrade_Bucket_Db');
    }

    public function up()
    {
        $sql = "CREATE TABLE IF NOT EXISTS tracker_rule_date(
            tracker_rule_id int(11) unsigned NOT NULL PRIMARY KEY,
            source_field_id int(11) unsigned NOT NULL,
            target_field_id int(11) unsigned NOT NULL,
            comparator varchar(2) NOT NULL,
            KEY tracker_rule_id (tracker_rule_id)
          ) ENGINE=InnoDB;";

        $this->createTable('tracker_rule_date', $sql);
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
