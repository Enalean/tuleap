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

class b201211211457_refactor_tracker_rule_tables extends ForgeUpgrade_Bucket
{

    public function description()
    {
        return 'Refactor the tracker_rule tables';
    }

    public function preUp()
    {
        $this->db = $this->getApi('ForgeUpgrade_Bucket_Db');
    }

    public function up()
    {
        $sql = "CREATE TABLE IF NOT EXISTS tracker_rule_list(
            tracker_rule_id int(11) unsigned NOT NULL PRIMARY KEY,
            source_field_id int(11) unsigned NOT NULL default '0',
            source_value_id int(11) unsigned NOT NULL default '0',
            target_field_id int(11) unsigned NOT NULL default '0',
            target_value_id int(11) unsigned default NULL,
            KEY tracker_rule_id (tracker_rule_id)
          ) ENGINE=InnoDB;";

        $this->createTable('tracker_rule_list', $sql);

        $sql = "INSERT INTO tracker_rule_list (
                    tracker_rule_id, 
                    source_field_id, 
                    source_value_id, 
                    target_field_id,
                    target_value_id
                ) 
                SELECT id, source_field_id, source_value_id, target_field_id, target_value_id
                FROM tracker_rule ";

        $result = $this->db->dbh->exec($sql);

        if ($result === false) {
            $error_message = implode(', ', $this->db->dbh->errorInfo());
            throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete($error_message);
        }

        $sql = "ALTER TABLE tracker_rule
                    DROP COLUMN source_field_id,
                    DROP COLUMN source_value_id,
                    DROP COLUMN target_field_id,
                    DROP COLUMN target_value_id
                ";

         $result = $this->db->dbh->exec($sql);

        if ($result === false) {
            $error_message = implode(', ', $this->db->dbh->errorInfo());
            throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete($error_message);
        }
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
