<?php
/**
 * Copyright (c) Enalean, 2017. All Rights Reserved.
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

class b201701311615_feedback_supports_new_session_identifier extends ForgeUpgrade_Bucket
{
    public function description()
    {
        return 'Update feedback management to use the new session management';
    }

    public function preUp()
    {
        $this->db = $this->getApi('ForgeUpgrade_Bucket_Db');
    }

    public function up()
    {
        $sql_drop_session_table = 'DROP TABLE IF EXISTS feedback';
        $res                    = $this->db->dbh->exec($sql_drop_session_table);
        if ($res === false) {
            throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete('An error occurred while dropping the table feedback');
        }

        $sql_recreate_table = 'CREATE TABLE feedback (
                                  session_id INT(11) UNSIGNED,
                                  feedback TEXT NOT NULL,
                                  created_at DATETIME NOT NULL,
                                  PRIMARY KEY (session_id)
                               ) ENGINE=InnoDB';
        $res                = $this->db->dbh->exec($sql_recreate_table);
        if ($res === false) {
            throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete('An error occurred while creating the table session');
        }
    }

    public function postUp()
    {
        if (! $this->db->tableNameExists('feedback')) {
            throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete('feedback table is missing');
        }
    }
}
