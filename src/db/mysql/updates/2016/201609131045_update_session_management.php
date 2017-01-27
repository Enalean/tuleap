<?php
/**
 * Copyright (c) Enalean, 2016. All Rights Reserved.
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

class b201609131045_update_session_management extends ForgeUpgrade_Bucket
{
    public function description()
    {
        return 'Update session management';
    }

    public function preUp()
    {
        $this->db = $this->getApi('ForgeUpgrade_Bucket_Db');
    }

    public function up()
    {
        $sql_drop_session_table = 'DROP TABLE IF EXISTS session';
        $res                    = $this->db->dbh->exec($sql_drop_session_table);
        if ($res === false) {
            throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete('An error occurred while dropping the table session');
        }

        $sql_recreate_table = 'CREATE TABLE session (
                                  id INT(11) NOT NULL PRIMARY KEY AUTO_INCREMENT,
                                  user_id INT(11) NOT NULL,
                                  session_hash CHAR(64) NOT NULL,
                                  ip_addr varchar(45) NOT NULL default "",
                                  time int(11) NOT NULL default "0",
                                  KEY idx_session_user_id (user_id),
                                  KEY idx_session_time (time)
                              ) ENGINE=InnoDB';
        $res                = $this->db->dbh->exec($sql_recreate_table);
        if ($res === false) {
            throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete('An error occurred while creating the table session');
        }
    }

    public function postUp()
    {
        if (! $this->db->tableNameExists('session')) {
            throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete('session table is missing');
        }
    }
}
