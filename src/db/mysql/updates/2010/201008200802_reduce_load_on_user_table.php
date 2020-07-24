<?php
/**
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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

class b201008200802_reduce_load_on_user_table extends ForgeUpgrade_Bucket
{
    public function description()
    {
        return <<<EOT
Create a new dedicated table for user access on frequently updated fields to reduce load on user table.
EOT;
    }

    public function preUp()
    {
        $this->db = $this->getApi('ForgeUpgrade_Bucket_Db');
    }

    public function up()
    {
        $sql = 'CREATE TABLE user_access (
                    user_id int(11) NOT NULL DEFAULT "0",
                    last_access_date int(11) NOT NULL DEFAULT 0,
                    prev_auth_success INT(11) NOT NULL DEFAULT 0,
                    last_auth_success INT(11) NOT NULL DEFAULT 0,
                    last_auth_failure INT(11) NOT NULL DEFAULT 0,
                    nb_auth_failure INT(11) NOT NULL DEFAULT 0,
                    PRIMARY KEY  (user_id)
                )';
        $this->db->createTable('user_access', $sql);

        $sql = 'INSERT INTO user_access
                           SELECT user_id, last_access_date, 
                                  prev_auth_success, last_auth_success, 
                                  last_auth_failure, nb_auth_failure 
                           FROM user';
        if ($this->db->tableNameExists('user') && $this->db->tableNameExists('user_access')) {
            $res = $this->db->dbh->exec($sql);
            if ($res === false) {
                throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete('An error occured copying from  table user to table user_access');
            }
        }

        $sql = 'ALTER TABLE user DROP COLUMN last_access_date';
        if ($this->db->tableNameExists('user') && $this->db->columnNameExists('user', 'last_access_date')) {
            $res = $this->db->dbh->exec($sql);
            if ($res === false) {
                throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete('An error occured while deleting last_access_date column from user table');
            }
        }

        $sql = 'ALTER TABLE user DROP COLUMN prev_auth_success';
        if ($this->db->tableNameExists('user') && $this->db->columnNameExists('user', 'prev_auth_success')) {
            $res = $this->db->dbh->exec($sql);
            if ($res === false) {
                throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete('An error occured while deleting prev_auth_success column from user table');
            }
        }

        $sql = 'ALTER TABLE user DROP COLUMN last_auth_success';
        if ($this->db->tableNameExists('user') && $this->db->columnNameExists('user', 'last_auth_success')) {
            $res = $this->db->dbh->exec($sql);
            if ($res === false) {
                throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete('An error occured while deleting last_auth_success column from user table');
            }
        }

        $sql = 'ALTER TABLE user DROP COLUMN last_auth_failure';
        if ($this->db->tableNameExists('user') && $this->db->columnNameExists('user', 'last_auth_failure')) {
            $res = $this->db->dbh->exec($sql);
            if ($res === false) {
                throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete('An error occured while deleting last_auth_failure column from user table');
            }
        }

        $sql = 'ALTER TABLE user DROP COLUMN  nb_auth_failure';
        if ($this->db->tableNameExists('user') && $this->db->columnNameExists('user', 'nb_auth_failure')) {
            $res = $this->db->dbh->exec($sql);
            if ($res === false) {
                throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete('An error occured while deleting nb_auth_failure column from user table');
            }
        }
    }

    public function postUp()
    {
        if (! $this->db->tableNameExists('user_access')) {
            throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete('user_access table is missing');
        }
        if ($this->db->columnNameExists('user', 'last_access_date')) {
            throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete('last_access_date column is not deleted from user table');
        }
        if ($this->db->columnNameExists('user', 'prev_auth_success')) {
            throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete('prev_auth_success column is not deleted from user table');
        }
        if ($this->db->columnNameExists('user', 'last_auth_success')) {
            throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete('last_auth_success column is not deleted from user table');
        }
        if ($this->db->columnNameExists('user', 'last_auth_failure')) {
            throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete('last_auth_failure column is not deleted from user table');
        }
        if ($this->db->columnNameExists('user', 'nb_auth_failure')) {
            throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete('nb_auth_failure column is not deleted from user table');
        }
    }
}
