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

class b201701131030_create_table_user_lost_password extends ForgeUpgrade_Bucket
{
    public function description()
    {
        return 'Create table user_lost_password';
    }

    public function preUp()
    {
        $this->db = $this->getApi('ForgeUpgrade_Bucket_Db');
    }

    public function up()
    {
        $sql = 'CREATE TABLE user_lost_password (
                   id INT(11) UNSIGNED NOT NULL PRIMARY KEY AUTO_INCREMENT,
                   user_id INT(11) NOT NULL,
                   verifier VARCHAR(255) NOT NULL,
                   INDEX idx_user_id (user_id)
                )';

        $this->db->dbh->exec($sql);
    }

    public function postUp()
    {
        if (! $this->db->tableNameExists('user_lost_password')) {
            throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete('user_lost_password table is missing');
        }
    }
}
