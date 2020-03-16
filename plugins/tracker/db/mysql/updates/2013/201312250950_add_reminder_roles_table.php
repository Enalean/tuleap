<?php
/**
 * Copyright (c) STMicroelectronics 2013. All rights reserved
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

class b201312250950_add_reminder_roles_table extends ForgeUpgrade_Bucket
{

    public function description()
    {
        return 'Add tracker reminder roles table and update the index';
    }

    public function preUp()
    {
        $this->db = $this->getApi('ForgeUpgrade_Bucket_Db');
    }

    public function up()
    {
        $sql = "CREATE TABLE IF NOT EXISTS tracker_reminder_notified_roles (
                    reminder_id INT(11) UNSIGNED NOT NULL,
                    role_id TINYINT(1) UNSIGNED NOT NULL
                );";
        $result = $this->db->createTable('tracker_reminder_notified_roles', $sql);

        if ($result === false) {
            $error_message = implode(', ', $this->db->dbh->errorInfo());
            throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete($error_message);
        }

        //Remove the NOT NULL Constraint on ugroups column
        $sql = "ALTER TABLE tracker_reminder
                    MODIFY COLUMN ugroups VARCHAR(255) NULL;";
        $result = $this->db->dbh->exec($sql);

        if ($result === false) {
            $error_message = implode(', ', $this->db->dbh->errorInfo());
            throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete($error_message);
        }

        //Retrieve the index name since it is unnamed
        $sql = "SELECT CONSTRAINT_NAME FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE  WHERE TABLE_NAME ='tracker_reminder' and column_name in('tracker_id', 'field_id') group by CONSTRAINT_NAME;";
        $res = $this->db->dbh->query($sql);
        if ($res === false) {
            throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete('An error occured while fetching the constraint name: ' . implode(', ', $this->db->dbh->errorInfo()));
        }
        $row = $res->fetch();
        $index = $row['CONSTRAINT_NAME'];
        $res->closeCursor();
        $sql = "ALTER TABLE `tracker_reminder` DROP INDEX " . $index . ";";
        $result = $this->db->dbh->exec($sql);
        if ($result === false) {
                $error_message = implode(', ', $this->db->dbh->errorInfo());
                throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete($error_message);
        }
    }

    public function postUp()
    {
        if (!$this->db->tableNameExists('tracker_reminder_notified_roles')) {
            throw new ForgeUpgrade_Bucket_Exception_UpgradeNotCompleteException('tracker_reminder_notified_roles');
        }
    }
}
